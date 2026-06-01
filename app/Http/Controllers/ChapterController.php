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

use App\Data\ChapterData;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Models\Chapter;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Services\ChapterService;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Services\ContentReorderService;
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
    public function __construct(
        private readonly ChapterService $chapters,
        private readonly ContentReorderService $reorder,
        private readonly CommentService $comments,
    ) {
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
     * Authorization + Validation kommen aus StoreChapterRequest,
     * Position-Calculation aus ChapterService::create.
     */
    public function store(StoreChapterRequest $request): RedirectResponse
    {
        $projectId = (int) $request->validated()['projectId'];

        $this->chapters->create(ChapterData::fromRequest($request), $projectId);

        return redirect()->route('projects.edit', $projectId)
            ->with('success', __('message_add_chapter_success'));
    }

    /**
     * Update an existing chapter (PATCH /chapters/{chapter}).
     *
     * Route-Model-Binding über $chapter; Authorization + Validation
     * kommen aus UpdateChapterRequest. Die Verzweigung
     * "Translation vs. Direktschreiben" liegt im DTO und im
     * ChapterService.
     */
    public function update(UpdateChapterRequest $request, Chapter $chapter): RedirectResponse
    {
        $this->chapters->update($chapter, ChapterData::fromRequest($request));

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
     * Comment chapter — neuer Top-Level-Kommentar.
     */
    public function commentChapter(Request $request, Chapter $chapter): RedirectResponse
    {
        $request->validate(['comment' => 'required']);

        $this->comments->addComment($chapter, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Retrieve all comment of current chapter
     *
     * @return RedirectResponse
     */
    public function getChapterComment($id)
    {
        $comment = new CommentRetrieve;
        $comments = $comment->getComments('App\Models\Chapter', $id);

        return redirect()->back()->with(['comments' => $comments]);
    }

    /**
     * Routet eine save-Submission (Edit/Delete/Reply).
     *
     * Akzeptiert zusätzlich den name=edit-Legacy-Pfad — eine
     * zweite Edit-Submission, die das Frontend in einem alten
     * Modal-Flow verwendet.
     */
    public function saveComment(Request $request, Chapter $chapter): RedirectResponse
    {
        if (isset($request['name']) && $request['name'] === 'edit') {
            $this->comments->editComment((int) $request['pk'], (string) $request['value']);

            return redirect()->back()->with('success', 'Comment edited successfully');
        }

        $this->comments->dispatchSaveAction($chapter, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments. Method-Name ist
     * historisch irreführend — siehe ProjectController::setStatusProject.
     */
    public function setStatus(Request $request, Chapter $chapter): JsonResponse
    {
        $this->comments->setCommentStatus((int) $request['id'], (int) $request['status']);

        return response()->json(['success' => true]);
    }

    /**
     * Update position and relationship through drag and drop.
     *
     * F-API-009: Authorization-Gate vor dem Schreibpfad. Wir lesen
     * aus dem Payload das Ziel-Project (über
     * ContentReorderService::resolveProject) und prüfen
     * `ProjectPolicy::update`. Der eigentliche Reorder wandert
     * danach in den ContentReorderService.
     *
     * Die volle Zerlegung in drei dedizierte PATCH-Endpunkte
     * (chapter.reorder, entry.reorder, content.reorder) bleibt
     * Refactoring-Material.
     *
     * @return JsonResponse
     */
    public function saveDragAndDrop(Request $request)
    {
        $payload = $request['data'];
        $data = isset($payload['data']) ? $payload['data'] : [];

        if (count($data) === 0) {
            return response()->json('Nothing to update');
        }

        $element = $payload['element'] ?? null;
        $project = $this->reorder->resolveProject($element, $payload, $data);

        if ($project === null) {
            // Ziel nicht auflösbar: leere oder bösartige IDs. Kein
            // Schreibpfad ausgeführt.
            return response()->json('Nothing to update');
        }

        $this->authorize('update', $project);

        Log::info($payload);

        switch ($element) {
            case 'chapter':
                $this->reorder->reorderChapters($data);
                break;

            case 'entry':
                $this->reorder->reorderEntries((int) $payload['chapter'], $data);
                break;

            case 'content':
                $targetEntry = $payload['entry'] ?? null;
                $this->reorder->reorderContent(
                    $targetEntry !== null ? (int) $targetEntry : null,
                    $data,
                );
                break;
        }

        return response()->json('Updated successfully');
    }
}
