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

use App\Http\Requests\StoreEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
use App\Models\Entry;
use App\Services\CommentRetrieve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    /**
     * Instantiate a new EntryController instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created entry (POST /entries).
     *
     * Authorization + Validation kommen aus StoreEntryRequest.
     * Update läuft jetzt über PATCH /entries/{entry} (Phase 2 / D.5).
     */
    public function store(StoreEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $pos = Entry::where('chapter_id', $data['chapterId'])
            ->orderBy('position', 'desc')
            ->first();

        Entry::create([
            'chapter_id' => $data['chapterId'],
            'name' => $data['entryTitle'],
            'subtitle' => $data['entrySubtitle'] ?? null,
            'description' => $data['entryDescription'] ?? null,
            'position' => ($pos->position ?? 0) + 1,
        ]);

        return redirect()->back()->with('success', __('message_add_entry_success'));
    }

    /**
     * Update an existing entry (PATCH /entries/{entry}).
     *
     * Route-Model-Binding über $entry. Phase 2 / D.5, ADR-0017.
     */
    public function update(UpdateEntryRequest $request, Entry $entry): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('translationEntry')) {
            $entry->setTranslation('name', 'en', $data['entryTitle']);
            $entry->setTranslation('subtitle', 'en', $data['entrySubtitle'] ?? '');
            if (($data['entryDescription'] ?? '') !== 'undefined') {
                $entry->setTranslation('description', 'en', $data['entryDescription'] ?? '');
            }
        } else {
            $entry->name = $data['entryTitle'];
            $entry->subtitle = $data['entrySubtitle'] ?? null;
            $entry->description = $data['entryDescription'] ?? null;
        }

        $entry->is_translated = $request->boolean('isTranslated');

        $entry->save();

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\RedirectResponse
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
     * @return \Illuminate\Http\RedirectResponse
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
