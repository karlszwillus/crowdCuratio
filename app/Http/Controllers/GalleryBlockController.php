<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GalleryData;
use App\Models\Entry;
use App\Models\Gallery;
use App\Services\ContentReorderService;
use App\Services\GalleryService;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Gallery-Block-Endpunkte aus dem
 * `ContentController` extrahiert. Enthaelt Sortierung, Drop-Upload,
 * Save (Create/Update), Edit (JSON) und Delete.
 */
class GalleryBlockController extends Controller
{
    public function __construct(
        private readonly GalleryService $galleries,
        private readonly ImageService $images,
        private readonly ContentReorderService $reorder,
    ) {
        $this->middleware('auth');
    }

    /**
     * Phase 5y.6: Bild-Reihenfolge innerhalb einer Galerie speichern.
     * Erwartet einen `ids`-Payload mit der neuen Reihenfolge; die
     * Positionen werden von 1 hochgezaehlt.
     */
    public function reorderImages(Request $request, Gallery $gallery): JsonResponse
    {
        // Project-scoped Gate — die Galerie muss beschrieben werden
        // koennen, damit die Reihenfolge geaendert werden darf.
        $this->authorize('update', $gallery);

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }

        $this->reorder->reorderImages($gallery->id, $ids);

        return response()->json(['ok' => true]);
    }

    /**
     * Phase 5y.9: Optimistischer Drop-Upload einer einzelnen Datei in
     * eine Galerie. Nimmt genau ein File, ohne Copyright/Quelle, und
     * gibt die neue Bild-ID plus URL als JSON zurueck. Frontend zieht
     * daraus die Ghost-Kachel zu einer echten und laedt am Ende einmal
     * die Seite neu, damit alle Blade-Bereiche (Angaben-Status,
     * Publish-Check, Header-Anzahl) konsistent sind.
     */
    public function dropImage(Request $request, Gallery $gallery): JsonResponse
    {
        $this->authorize('update', $gallery);

        $request->validate([
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:4096',
        ]);

        $image = $this->images->createFromDrop($request->file('file'), $gallery->id);

        return response()->json([
            'ok' => true,
            'image' => [
                'id' => $image->id,
                'position' => $image->position,
                'url' => route('image', $image->image),
            ],
        ]);
    }

    /**
     * Save or update gallery.
     */
    public function saveGallery(Request $request): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.d: Auth laeuft project-scoped auf
        // Gallery (Update-Pfad) oder Entry (Create-Pfad).

        $data = GalleryData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: `ConvertEmptyStringsToNull`
        // schreibt leere Hidden-Inputs (Neuanlage: `galleryId=""`) zu
        // `null` um. `$request->filled('galleryId')` ist die
        // idiomatische Laravel-Form: true, wenn Input present UND
        // nicht leer/null.
        if ($request->filled('galleryId')) {
            $gallery = Gallery::findOrFail($request['galleryId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $gallery);
            $this->galleries->update($gallery, $data);

            return redirect()->back()->with('success', __('message_update_success'));
        }

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Entry laden + gaten.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->galleries->create($data, $entry->id);

        return redirect()->back()->with('success', __('message_gallery_success'));
    }

    /**
     * Get gallery (JSON).
     */
    public function editGallery(int $id): JsonResponse
    {
        $gallery = Gallery::where('id', $id)->first();

        if ($gallery === null) {
            abort(404);
        }

        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Gallery-
        // Daten ausliefern.
        $this->authorize('view', $gallery);

        return response()->json($gallery);
    }

    /**
     * Destroy gallery.
     */
    public function destroyGallery(Request $request, int $id): RedirectResponse
    {
        $gallery = Gallery::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): GalleryPolicy::delete.
        $this->authorize('delete', $gallery);
        $this->galleries->destroy($gallery);

        return redirect('projects/'.$request->project.'/edit')
            ->with('success', __('message_delete_text_success'));
    }
}
