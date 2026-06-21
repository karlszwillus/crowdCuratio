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

use App\Data\EntryData;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
use App\Models\Entry;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EntryController extends Controller
{
    /**
     * Instantiate a new EntryController instance.
     */
    public function __construct(
        private readonly EntryService $entries,
        private readonly CommentService $comments,
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created entry (POST /entries).
     *
     * Authorization + Validation kommen aus StoreEntryRequest,
     * Position-Calculation aus EntryService::create.
     */
    public function store(StoreEntryRequest $request): RedirectResponse
    {
        $chapterId = (int) $request->validated()['chapterId'];

        $this->entries->create(EntryData::fromRequest($request), $chapterId);

        return redirect()->back()->with('success', __('message_add_entry_success'));
    }

    /**
     * Update an existing entry (PATCH /entries/{entry}).
     *
     * Route-Model-Binding über $entry; Authorization + Validation
     * kommen aus UpdateEntryRequest. Translation-Verzweigung liegt
     * im DTO und im EntryService.
     */
    public function update(UpdateEntryRequest $request, Entry $entry): RedirectResponse
    {
        $this->entries->update($entry, EntryData::fromRequest($request));

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        // E.7b 4a-Hotfix-II: Methode hat heute keinen Body (Stub),
        // aber via Route::resource('/entries',...) ist sie aufrufbar.
        // Auth-Gate damit der Stub nicht später ohne Schutz Inhalte
        // ausliefert.
        $entry = Entry::findOrFail($id);
        $this->authorize('view', $entry);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $entry = Entry::findOrFail($id);

        // E.7b 4a-Hotfix-II: vorher ungated — Reader konnten
        // JSON-Daten fremder Entries via /entries/{id}/edit ziehen.
        $this->authorize('view', $entry);

        return response()->json($entry);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $entry = Entry::findOrFail($id);

        $this->authorize('delete', $entry);

        $entry->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_entry_success'));
    }

    /**
     * Comment entry — neuer Top-Level-Kommentar.
     *
     * Route hat kein {entry} in der URL, deshalb laden wir das
     * Modell explizit aus $request->id (siehe Hinweis im
     * ProjectController::commentProject).
     */
    public function commentEntry(StoreCommentRequest $request): RedirectResponse
    {
        $entry = Entry::findOrFail($request->validated('id'));

        // E.7b 4a-Hotfix-II: StoreCommentRequest::authorize() prüft
        // nur Auth-User. Hier project-scoped gate nachreichen.
        $this->authorize('comment', $entry);

        $this->comments->addComment($entry, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Retrieve all comment of current entry
     *
     * @return JsonResponse
     */
    public function getEntryComment($id)
    {
        // E.7b 4a-Hotfix-II: Entry laden + authorize.
        $entry = Entry::findOrFail($id);
        $this->authorize('view', $entry);

        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\Entry', $id);
    }

    /**
     * Routet eine save-Submission (Edit/Delete/Reply).
     *
     * Route hat {id} (nicht {entry}), Laravel kann Entry deshalb
     * nicht aus dem Route-Parameter binden — wir laden es
     * explizit (siehe ChapterController::saveComment).
     */
    public function saveCommentEntry(Request $request): RedirectResponse
    {
        // E.7b 4a-Hotfix-II: Entry immer laden + authorize, auch im
        // name=edit-Pfad. Vorher konnten fremde Comments editiert werden.
        $entry = Entry::findOrFail($request->route('id'));
        $this->authorize('comment', $entry);

        if (isset($request['name']) && $request['name'] === 'edit') {
            $this->comments->editComment((int) $request['pk'], (string) $request['value']);

            return redirect()->back()->with('success', 'Comment edited successfully');
        }

        $this->comments->dispatchSaveAction($entry, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments auf einem Entry.
     */
    public function setCommentStatusEntry(Request $request): JsonResponse
    {
        // E.7b 4a-Hotfix-II: das `Entry $entry`-Argument war ohne
        // {entry}-Route-Parameter ein toter Auth-Hook. Jetzt: via
        // Comment-Id Project auflösen und gate.
        $commentId = (int) $request['id'];
        $project = $this->comments->resolveProjectForComment($commentId);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus($commentId, (int) $request['status']);

        return response()->json(['success' => true]);
    }
}
