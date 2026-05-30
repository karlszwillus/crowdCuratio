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

use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
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
        // SoftDeletes-Scope schließt trashed implizit aus (F-DB-014).
        // withEditTree() lädt die volle Hierarchie für chapters/index
        // eager — sonst beißt preventLazyLoading (Phase 2 / C.1).
        $project = Project::withEditTree()->findOrFail($request['id']);
        $permissions = Permission::all();
        // F-DB-013: vorher Role::where('id', 'not like', '1') —
        // LIKE-Vergleich auf INT-Spalte mit hartkodierter ID.
        // Sauber: per Rollen-Name filtern.
        $listRole = Role::where('name', '!=', 'Admin')->pluck('name', 'id');

        return view('chapters.index', compact('project', 'listPermissions', 'permissions', 'listRole'));
    }

    /**
     * Store a newly created chapter (POST /chapters).
     *
     * Authorization + Validation kommen aus StoreChapterRequest.
     * Update läuft jetzt über PATCH /chapters/{chapter}; die alte
     * isset($chapterId)-Verzweigung ist entfallen (Phase 2 / D.4).
     */
    public function store(StoreChapterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $pos = Chapter::where('project_id', $data['projectId'])
            ->orderBy('position', 'desc')
            ->first();

        Chapter::create([
            'project_id' => $data['projectId'],
            'name' => $data['chapterTitle'],
            'subtitle' => $data['chapterSubtitle'] ?? null,
            'description' => $data['chapterDescription'] ?? null,
            'position' => ($pos->position ?? 0) + 1,
        ]);

        return redirect()->route('projects.edit', $data['projectId'])
            ->with('success', __('message_add_chapter_success'));
    }

    /**
     * Update an existing chapter (PATCH /chapters/{chapter}).
     *
     * Route-Model-Binding über $chapter; Authorization + Validation
     * kommen aus UpdateChapterRequest. Phase 2 / D.4, ADR-0017.
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('translationChapter')) {
            $chapter->setTranslation('name', 'en', $data['chapterTitle']);
            $chapter->setTranslation('subtitle', 'en', $data['chapterSubtitle'] ?? '');
            if (($data['chapterDescription'] ?? '') !== 'undefined') {
                $chapter->setTranslation('description', 'en', $data['chapterDescription'] ?? '');
            }
        } else {
            $chapter->name = $data['chapterTitle'];
            $chapter->subtitle = $data['chapterSubtitle'] ?? null;
            $chapter->description = $data['chapterDescription'] ?? null;
        }

        $chapter->is_translated = $request->boolean('isTranslated');

        $chapter->save();

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
