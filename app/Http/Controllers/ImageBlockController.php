<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ImageData;
use App\Http\Requests\StoreImageBlockRequest;
use App\Models\Entry;
use App\Models\Image;
use App\Services\ImageService;
use App\Services\SourceTranslationService;
use App\Support\PermissionName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Image-Block-Endpunkte aus dem
 * `ContentController` extrahiert. Save (inkl. Translation-Pfad) / Edit
 * (JSON) / Delete.
 */
class ImageBlockController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly SourceTranslationService $sourceTranslator,
    ) {
        $this->middleware('auth');
    }

    /**
     * Save or update image.
     *
     * NF-SEC-201: Signatur auf `StoreImageBlockRequest` umgestellt.
     * MIME-Whitelist (jpeg/jpg/png/gif/webp) und 4-MB-Limit fuer
     * `image` und `newImage` greifen jetzt schon vor dem Methoden-
     * Body.
     */
    public function saveImage(StoreImageBlockRequest $request): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b (revidiert nach HappyPath-Befund):
        // Defense-in-Depth nur dort, wo kein Modell-Argument fuer
        // ein project-scoped authorize() vorhanden ist (Source-
        // Translation via SourceTranslationService). Sonst entscheidet
        // das nachgelagerte authorize('update', $image|$gallery|$entry).

        // Translation-Pfad: setzt alt-Uebersetzung + delegiert
        // Source-Uebersetzungen via SourceTranslationService.
        //
        // Stakeholder-Fix Juni 2026: Haertung gegen
        // `ConvertEmptyStringsToNull` (Laravel-11-Default). `isset()`
        // auf den Request-Bag-Keys ist post-Middleware unzuverlaessig
        // — Keys koennen present sein mit Wert `null`. `filled()`
        // testet idiomatisch "vorhanden und nicht leer/null".
        // Phase-5-Backlog-Sammler (2026-08-16): das Translate-Blade
        // sendet <input type="hidden" name="translationMode"> OHNE
        // value — `filled()` waere false. `has()` prueft nur die
        // Key-Anwesenheit.
        if ($request->has('translationMode')) {
            // E.7b 4a-Hotfix-II.d: Source-Translation hat keinen
            // Project-Bezug (Sources sind global geteilt). Reader-
            // Schutz via globale 'edit'-Permission als Defense-in-Depth.
            if ($request->filled('originId') || $request->filled('copyrightId')) {
                if (! $request->user()->hasPermissionTo(PermissionName::EDIT->value)) {
                    abort(403);
                }
            }
            if ($request->filled('originId')) {
                $this->sourceTranslator->translate(
                    (int) $request['originId'],
                    (string) $request['originField'],
                    $request['isTranslated'] ?? null,
                );
            }
            if ($request->filled('copyrightId')) {
                $this->sourceTranslator->translate(
                    (int) $request['copyrightId'],
                    (string) $request['copyrightField'],
                    $request['isTranslated'] ?? null,
                );
            }
            if ($request->filled('altField')) {
                $image = Image::findOrFail($request['imageId']);
                // E.7b 4a-Hotfix-II.b: project-scoped Gate fuer Image.
                $this->authorize('update', $image);
                $image->setTranslation('alt', 'en', $request['altField']);
                $image->save();
            }

            return redirect()->back()->with('success', __('message_edit_image_success'));
        }

        $request->validate([
            'copyrightImage' => 'required',
            'originImage' => 'required',
        ]);

        $data = ImageData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: siehe saveGallery — gleiches
        // Pattern, gleicher Fix. `ConvertEmptyStringsToNull` macht
        // `imageId=""` zu `null`; ohne `filled()` lief die alte
        // Bedingung in `Image::findOrFail(null)` → 404.
        if ($request->filled('imageId')) {
            $image = Image::findOrFail($request['imageId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $image);
            $newFile = $request->hasFile('newImage') ? $request->file('newImage') : null;
            $this->images->update($image, $data, $newFile);

            return redirect()->back()->with('success', __('message_edit_image_success'));
        }

        $request->validate(['image' => 'required']);

        // E.7b 4a-Hotfix-II.d: Create-Pfad gated ueber Entry statt
        // Gallery — eine frisch angelegte Gallery hat ggf. noch keine
        // Pivot-Verbindung und `Gallery::project()` wuerde null geben.
        // Entry hat klare Project-Verbindung via Chapter.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->images->create($data, $request->file('image'), (int) $request['galleryId']);

        return redirect()->back()->with('success', __('message_add_image_success'));
    }

    /**
     * Get selected image to be modified (JSON).
     */
    public function editImage(int $id): JsonResponse
    {
        $image = Image::findOrFail($id);
        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Image-Daten
        // ausliefern.
        $this->authorize('view', $image);
        $data = [
            'id' => $image->id,
            'image' => $image->image,
            'url' => $image->url,
            'alt' => $image->alt,
            'origin' => $image->originImage->name,
            'copyright' => $image->copyrightImage->name,
        ];

        return response()->json($data);
    }

    /**
     * Delete Image.
     */
    public function destroyImage(Request $request, int $id): RedirectResponse
    {
        $image = Image::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): ImagePolicy::delete.
        $this->authorize('delete', $image);
        $this->images->destroy($image);

        return redirect('projects/'.$request->project.'/edit')
            ->with('success', __('message_delete_image_success'));
    }
}
