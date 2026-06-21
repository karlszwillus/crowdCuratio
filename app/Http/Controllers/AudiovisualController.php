<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

use App\Data\AudiovisualData;
use App\Http\Requests\StoreAudiovisualRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Audiovisual;
use App\Models\Entry;
use App\Services\AudiovisualService;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AudiovisualController extends Controller
{
    /**
     * Instantiate a new AudioVisualController instance.
     */
    public function __construct(
        private readonly CommentService $comments,
        private readonly AudiovisualService $audiovisuals,
    ) {
        $this->middleware('auth');
    }

    public function index() {}

    /**
     * Store or update audiovisual
     *
     * NF-SEC-201: Signatur auf `StoreAudiovisualRequest`. MIME-
     * Whitelist (audio/mpeg, audio/mp4, audio/wav, audio/ogg,
     * audio/x-m4a) und 20-MB-Limit greifen vor dem Methoden-Body.
     *
     * @return RedirectResponse
     */
    public function store(StoreAudiovisualRequest $request)
    {
        // E.7b 4a-Hotfix-II.d: Auth läuft project-scoped auf
        // Audiovisual (Update-Pfad) oder Entry (Create-Pfad) —
        // kein translateField hier, daher keine globale Hürde nötig.

        // Audio-Upload oder YouTube-URL-Konversion vor dem
        // DTO-Bau — der Service normalisiert beides auf einen
        // String, der direkt in `link` gespeichert wird.
        $normalizedLink = $this->audiovisuals->resolveLink(
            $request['link'] ?? null,
            $request->file('audio'),
        );

        $data = AudiovisualData::fromRequest($request, $normalizedLink);

        // Stakeholder-Fix Juni 2026: siehe ContentController::saveGallery —
        // dasselbe `ConvertEmptyStringsToNull`-Stolperstein-Pattern.
        // `audiovisualId=""` wird zur Pipeline zu `null`, was die alte
        // `!== ''`-Bedingung blind macht und `findOrFail(null)` → 404
        // rendert. `$request->filled(...)` deckt beide Fälle ab.
        if ($request->filled('audiovisualId')) {
            $audiovisual = Audiovisual::findOrFail($request['audiovisualId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $audiovisual);
            $this->audiovisuals->update($audiovisual, $data);

            return redirect()->back()->with('success', __('message_update_success'));
        }

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Entry laden + gaten.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->audiovisuals->create($data, $entry->id);

        return redirect()->back()->with('success', __('message_add_success'));
    }

    /**
     * Delete audiovisual
     */
    public function delete(Request $request, $id): RedirectResponse
    {
        $audiovisual = Audiovisual::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): AudiovisualPolicy::delete.
        $this->authorize('delete', $audiovisual);
        $this->audiovisuals->destroy($audiovisual);

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_success'));
    }

    /**
     * Routet eine save-Submission auf einem Audiovisual
     * (Edit/Delete/Reply).
     */
    public function saveCommentAudiovisual(Request $request, Audiovisual $audiovisual): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b: project-scoped Gate via Audiovisual.
        $this->authorize('comment', $audiovisual);

        $commentable = isset($request['question'])
            ? (Audiovisual::find($request['question']) ?? $audiovisual)
            : $audiovisual;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Neuer Top-Level-Kommentar auf einem Audiovisual.
     *
     * Route hat kein {audiovisual} in der URL, deshalb laden wir
     * das Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentAudiovisual(StoreCommentRequest $request): RedirectResponse
    {
        $audiovisual = Audiovisual::findOrFail($request->validated('id'));
        // E.7b 4a-Hotfix-II.b: project-scoped Gate nachgereicht.
        $this->authorize('comment', $audiovisual);

        $this->comments->addComment($audiovisual, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }
}
