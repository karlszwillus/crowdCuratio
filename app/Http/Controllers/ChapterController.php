<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\MediaContent;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Services\CommentRetrieve;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ChapterController extends Controller
{
    /**
     * Instantiate a new ChapterController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        $listPermissions = [
            'view' => [],
            'add' => [],
            'edit' => [],
            'delete' => [],
            'publish' => [],
            'comment' => [],
        ];
        // SoftDeletes-Scope auf Project schließt trashed schon implizit
        // aus — kein whereNull('deleted_at') nötig (F-DB-014).
        //
        // Eager-Loading-Baum für chapters/index.blade.php — die View
        // rendert die komplette Hierarchie inkl. Kommentaren auf
        // jeder Ebene. Ohne with(...) wirft preventLazyLoading
        // (Phase 2 / C.1) eine LazyLoadingViolationException auf
        // entry->comments, item->text->comments etc.
        $project = Project::with([
            'chapters.comments',
            'chapters.entries.comments',
            'chapters.entries.mediaContent.comments',
            'chapters.entries.mediaContent.text.comments',
            'chapters.entries.mediaContent.text.copyrightText',
            'chapters.entries.mediaContent.text.originText',
            'chapters.entries.mediaContent.audiovisual.comments',
            'chapters.entries.mediaContent.gallery.comments',
            'chapters.entries.mediaContent.gallery.images.comments',
            'chapters.entries.mediaContent.gallery.images.copyrightImage',
            'chapters.entries.mediaContent.gallery.images.originImage',
        ])->findOrFail($request['id']);
        $permissions = Permission::all();
        // F-DB-013: vorher Role::where('id', 'not like', '1') —
        // LIKE-Vergleich auf INT-Spalte mit hartkodierter ID.
        // Sauber: per Rollen-Name filtern.
        $listRole = Role::where('name', '!=', 'Admin')->pluck('name', 'id');

        return view('chapters.index', compact('project', 'listPermissions', 'permissions', 'listRole'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {

        if (isset($request['chapterId']) && $request['chapterId'] != '') {
            $this->update($request);

            return redirect()->back()->with('success', __('message_edit_chapter_success'));
        } else {
            // NF-LAR-003: Owner-Check vor dem Anlegen — Permission 'add'
            // allein reicht nicht, weil sie projekt­übergreifend gilt.
            $project = Project::findOrFail($request['projectId']);
            $this->authorize('createIn', [Chapter::class, $project]);

            $pos = Chapter::where('project_id', $project->id)->orderBy('position', 'desc')->first();
            $position = 0;
            if (! empty($pos->position)) {
                $position = $pos->position;
            }
            Chapter::create(
                [
                    'project_id' => $project->id,
                    'name' => $request['chapterTitle'],
                    'subtitle' => $request['chapterSubtitle'],
                    'description' => $request['chapterDescription'],
                    'position' => $position + 1,
                ]
            );

            return redirect()->route('projects.edit', [$project->id])->with('success', __('message_add_chapter_success'));

        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return $this
     */
    public function update(Request $request)
    {
        $chapter = Chapter::findorFail($request['chapterId']);

        $this->authorize('update', $chapter);

        if (isset($request['translationChapter'])) {
            $chapter->setTranslation('name', 'en', $request['chapterTitle']);
            $chapter->setTranslation('subtitle', 'en', $request['chapterSubtitle']);
            if ($request['chapterDescription'] != 'undefined') {
                $chapter->setTranslation('description', 'en', $request['chapterDescription']);
            }
        } else {
            $chapter->name = $request['chapterTitle'];
            $chapter->subtitle = $request['chapterSubtitle'];
            $chapter->description = $request['chapterDescription'];
        }

        $chapter->is_translated = isset($request['isTranslated']) ? 1 : 0;

        $chapter->save();

        // Vorher: return $this; — gibt den Controller selbst zurück
        // und bricht jede assertOk-Verifikation mit TypeError, weil
        // Symfony's Response::setContent() einen String erwartet. Im
        // Frontend ignoriert das JS den Body, deshalb fiel es bisher
        // nicht auf. back() ist semantisch das, was im Browser passiert.
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return JsonResponse
     */
    public function edit($id)
    {
        $data = Chapter::findOrFail($id);

        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $chapter = Chapter::findOrFail($id);

        $this->authorize('delete', $chapter);

        $chapter->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_chapter_success'));
    }

    /**
     * Comment chapter
     *
     * @return RedirectResponse
     */
    public function commentChapter(Request $request, Chapter $chapter)
    {
        $request->validate(
            [
                'comment' => 'required',
            ]
        );

        return $chapter->commentAsUser($request);
    }

    /**
     * Retrieve all comment of current chapter
     *
     * @return JsonResponse
     */
    public function getChapterComment($id)
    {
        $comment = new CommentRetrieve;
        $comments = $comment->getComments('App\Models\Chapter', $id);

        return redirect()->back()->with(['comments' => $comments]);
    }

    /**
     * Save current comment
     *
     * @return RedirectResponse
     */
    public function saveComment(Request $request, Chapter $chapter)
    {

        if (isset($request['name']) && $request['name'] == 'edit') {
            return $chapter->editAsUser($request);
        }

        if (isset($request['btn_submit'])) {
            if ($request['btn_submit'] == 'Edit') {
                return $chapter->editAsUser($request);
            } elseif ($request['btn_submit'] == 'delete') {
                return $chapter->deleteAsUser($request['id']);
            } else {
                return $chapter->replyAsUser($request);
            }
        }
    }

    /**
     * Set status
     *
     * @return JsonResponse
     */
    public function setStatus(Request $request, Chapter $chapter)
    {
        $data = $chapter->status($request);

        return response()->json($data);
    }

    /**
     * Update position and relationship through drag and drop.
     *
     * @return JsonResponse
     */
    public function saveDragAndDrop(Request $request)
    {
        $request = $request['data'];
        $data = isset($request['data']) ? $request['data'] : [];

        Log::info($request);
        if (count($data) > 0) {
            switch ($request['element']) {
                case 'chapter':
                    foreach ($data as $key => $value) {
                        Chapter::where('id', $value)->update(['position' => $key + 1]);
                    }
                    break;
                case 'entry':
                    foreach ($data as $key => $value) {
                        if (is_null($value)) {
                            continue;
                        }
                        Entry::where('id', $value)->update(['chapter_id' => $request['chapter'], 'position' => $key + 1]);
                    }

                    break;
                case 'content':
                    foreach ($data as $key => $value) {
                        if ($request['entry']) {
                            MediaContent::where('id', $value)->update(['media_contentable_id' => $request['entry'], 'position' => $key + 1]);
                        } else {
                            MediaContent::where('id', $value)->update(['position' => $key + 1]);
                        }

                    }

                    break;
            }
        }

        return response()->json('Updated successfully');
    }
}
