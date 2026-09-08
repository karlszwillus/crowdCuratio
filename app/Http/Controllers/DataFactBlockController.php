<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DataFactBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block-Endpunkte.
 *
 * Anlegen läuft über den ContentInsertionService (Inline-Add-Bar),
 * Bearbeiten (Titel/Untertitel/Zeilen) inline über inline-editor
 * und data-facts-rows-editor. Direkt-Endpunkt gibt es nur für
 * Delete.
 */
class DataFactBlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $block = DataFactBlock::findOrFail($id);
        $this->authorize('delete', $block);
        $block->delete();

        return redirect('projects/'.$request->project.'/edit')
            ->with('success', __('message_delete_data_facts_success'));
    }
}
