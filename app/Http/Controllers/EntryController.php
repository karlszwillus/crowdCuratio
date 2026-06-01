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
use App\Http\Requests\StoreEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
use App\Models\Entry;
use App\Services\CommentRetrieve;
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Entry::findOrFail($id);

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
        $entry = Entry::findOrFail($id);

        $this->authorize('delete', $entry);

        $entry->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_entry_success'));
    }

    /**
     * Comment entry
     *
     * @return RedirectResponse
     */
    public function commentEntry(Request $request, Entry $entry)
    {
        $request->validate(
            [
                'comment' => 'required',
            ]
        );

        return $entry->commentAsUser($request, 'App\Models\Entry');
    }

    /**
     * Retrieve all comment of current entry
     *
     * @return JsonResponse
     */
    public function getEntryComment($id)
    {
        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\Entry', $id);
    }

    /**
     * Save current entry
     *
     * @return RedirectResponse
     */
    public function saveCommentEntry(Request $request, Entry $entry)
    {
        if (isset($request['name']) && $request['name'] == 'edit') {
            return $entry->editAsUser($request);
        }

        if (isset($request['btn_submit'])) {
            if ($request['btn_submit'] == 'Edit') {
                return $entry->editAsUser($request);
            } elseif ($request['btn_submit'] == 'delete') {
                return $entry->deleteAsUser($request['id']);
            } else {
                return $entry->replyAsUser($request);
            }
        }
    }

    /**
     * Set status entry
     *
     * @return JsonResponse
     */
    public function setStatusEntry(Request $request, Entry $entry)
    {
        $data = $entry->status($request);

        return response()->json($data);
    }
}
