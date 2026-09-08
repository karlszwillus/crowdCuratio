<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QuoteBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block-Endpunkte.
 *
 * Der Zitat-Block wird über den Inline-Add-Flow angelegt
 * (ContentInsertionService) und im Editor inline über
 * rich-text-editor / inline-editor / source-picker befüllt —
 * ein eigener Save-Endpoint entfällt. Nur Delete und das
 * Kind-Dropdown sind klassische Endpunkte.
 */
class QuoteBlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $quote = QuoteBlock::findOrFail($id);

        // Delete-Gate an die Content-Auth-Kette anlehnen: Projekt-
        // Owner + Editor+delete dürfen. Analog TextBlockController.
        $this->authorize('delete', $quote);

        $quote->delete();

        return redirect('projects/'.$request->project.'/edit')
            ->with('success', __('message_delete_quote_success'));
    }

    // Q4-Etappe 4 / F3 (2026-09-08): updateKind() ist mit dem
    // Livewire-quote-kind-selector abgelöst — Kind-Änderungen
    // laufen inline ohne Redirect, konsistent zum Rest der
    // Editor-Kette.
}
