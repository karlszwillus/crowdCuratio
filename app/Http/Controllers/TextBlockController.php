<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TextData;
use App\Models\Entry;
use App\Models\Text;
use App\Services\SourceTranslationService;
use App\Services\TextService;
use App\Support\PermissionName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Text-Block-Endpunkte aus dem
 * `ContentController` extrahiert. Vier oeffentliche Methoden fuer
 * Save / Edit / Delete / Reset; private Helper fuer den Translation-
 * Body-Pfad.
 */
class TextBlockController extends Controller
{
    public function __construct(
        private readonly TextService $texts,
        private readonly SourceTranslationService $sourceTranslator,
    ) {
        $this->middleware('auth');
    }

    /**
     * Save or update text (inkl. Translation-Pfad).
     */
    public function saveText(Request $request): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.d: Auth liegt jetzt project-scoped
        // direkt am jeweiligen Pfad — Source-Translation (originId/
        // copyrightId) bekommt die globale 'edit'-Permission als
        // Huerde (Sources sind global geteilt), Text-Update und
        // Create gehen ueber authorize('update', $text|$entry).
        // Owner-Shortcut in OwnerScopedPolicy faengt Project-Owner
        // auch ohne globale 'edit'-Permission ab.

        // Translation-Pfad: schreibt Uebersetzungen in den Body und
        // die Source-Namen, kein Body-Update via TextService. Bleibt
        // bis zur Translation-Refaktorierung (spaeterer Block) auf
        // den Inline-Aufrufen.
        //
        // Stakeholder-Fix Juni 2026: Haertung gegen
        // `ConvertEmptyStringsToNull` (analog saveImage), damit
        // `null`-Werte nicht in `saveTranslatedText`/`SourceTranslationService`
        // mit `findOrFail(null)` enden.
        // Phase-5-Backlog-Sammler (2026-08-16): das Translate-Blade
        // sendet <input type="hidden" name="translationMode"> OHNE
        // value — `filled()` waere false. `has()` prueft nur die
        // Key-Anwesenheit.
        if ($request->has('translationMode')) {
            if ($request->filled('textId')) {
                // E.7b 4a-Hotfix-II.b: project-scoped Gate.
                $text = Text::findOrFail($request['textId']);
                $this->authorize('update', $text);
                $this->saveTranslatedText($request);
            }
            // E.7b 4a-Hotfix-II.d: Source-Translation ist global —
            // Defense-in-Depth ueber globale 'edit'-Permission.
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

            return redirect()->back()->with('success', __('message_edit_text_success'));
        }

        $request->validate([
            'contentText' => 'required',
            'copyrightText' => 'required',
            'originText' => 'required',
        ]);

        $data = TextData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: siehe saveGallery — gleiches
        // Pattern, gleicher Fix. `ConvertEmptyStringsToNull` macht
        // `textId=""` zu `null`; ohne `filled()` laeuft die alte
        // Bedingung in `Text::findOrFail(null)` → 404.
        if ($request->filled('textId')) {
            $text = Text::findOrFail($request['textId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $text);
            $this->texts->update($text, $data);

            return redirect()->back()->with('success', __('message_edit_text_success'));
        }

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Entry laden + gaten,
        // weil Text dort angefuegt wird.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->texts->create($data, $entry->id);

        return redirect()->back()->with('success', __('message_add_text_success'));
    }

    /**
     * Get selected text to be modified (JSON).
     */
    public function editText(int $id): JsonResponse
    {
        $text = Text::findOrFail($id);
        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Text-Daten
        // ausliefern.
        $this->authorize('view', $text);
        $data = [
            'id' => $text->id,
            'text' => $text->text,
            'origin' => $text->originText->name,
            'copyright' => $text->copyrightText->name,
        ];

        return response()->json($data);
    }

    /**
     * Delete Text.
     */
    public function destroyText(Request $request, int $id): RedirectResponse
    {
        $text = Text::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): TextPolicy::delete.
        $this->authorize('delete', $text);
        $this->texts->destroy($text);

        return redirect('projects/'.$request->project.'/edit')
            ->with('success', __('message_delete_text_success'));
    }

    /**
     * Reset text.
     */
    public function resetText(Request $request): RedirectResponse
    {
        $model = Text::findOrFail($request['idReset']);
        // E.7b 4a-Hotfix-II.b: project-scoped Gate.
        $this->authorize('update', $model);

        $model->text = $request['valueReset'];
        $model->save();

        return redirect()->back()->with('success', 'Text reset successfully');
    }

    /**
     * Schreibt die englische Uebersetzung des Text-Bodys. Wird nur
     * intern aus `saveText()` aufgerufen (`translationMode`-Pfad).
     */
    private function saveTranslatedText(Request $request): void
    {
        $text = Text::findOrFail($request['textId']);

        // filter text before saving
        if ($request['text'] != 'undefined') {
            $strClean = str_replace(['<script>', '</script>'], ['', ''], (string) $request['text']);
            $text->setTranslation('text', 'en', $strClean);
        }
        $text->is_translated = isset($request['isTranslated']) ? 1 : 0;
        $text->save();
    }
}
