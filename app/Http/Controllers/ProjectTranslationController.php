<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Revision;
use App\Models\Text;
use App\Models\TranslationSourceReference;
use App\Services\TranslationOutdatedMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Q4-Etappe 2 / I6 (2026-08-27): Uebersetzungs-Endpunkte fuer
 * Projekte, extrahiert aus dem `ProjectController` (God-Object).
 * Die Sync-Warn-Logik (`buildOutdatedTranslationMap`) lebt jetzt im
 * neuen `TranslationOutdatedMapService`.
 *
 * Enthaelt zwei Endpunkte:
 *  - `translateCurrentProject($id)` — Uebersetzen-Sicht mit Sync-
 *    Warnung fuer alle uebersetzten Felder.
 *  - `saveTranslations(Request, $id)` — Bulk-Save aller englischen
 *    Uebersetzungen.
 *
 * `allData()` bleibt private in dieser Klasse — wird von `translate-
 * CurrentProject` fuer die Prozent-Anzeige gebraucht und hat sonst
 * keinen Aufrufer im Projekt.
 */
class ProjectTranslationController extends Controller
{
    public function __construct(
        private readonly TranslationOutdatedMapService $outdated,
    ) {
        $this->middleware('auth');
    }

    /**
     * Uebersetzen-Sicht mit Sync-Warnungen.
     */
    public function translateCurrentProject(Project $project): View
    {
        // Q4-Etappe 8 · E3d (2026-09-11): Route-Model-Binding via
        // `{project}`; die alte `$id`-Signatur ist damit weg.
        $this->authorize('update', $project);

        App::setlocale('de');
        $data = $this->allData($project->id);

        // 5aa.3-Followup: Die neue Blade-Sicht rendert Text/Gallery/
        // Audiovisual direkt aus der `mediaContent`-Kette. Weil
        // `Model::shouldBeStrict()` Lazy-Loading verbietet, ziehen wir
        // die polymorphen Ziel-Modelle hier gezielt nach; `allData`
        // bleibt fuer seine eigene Prozent-Rechnung unveraendert.
        $tree = Project::withTranslateTree()->findOrFail($project->id);
        foreach ($tree->chapters as $chapter) {
            foreach ($chapter->entries as $entry) {
                foreach ($entry->mediaContent as $mc) {
                    $mc->loadMissing('text', 'gallery.images', 'audiovisual');
                }
            }
        }
        $data['data'] = $tree->chapters;

        // Phase 5ab.5 (Design v6 § 4): Sync-Warnung „Original nach
        // Uebersetzung geaendert" — Logik im TranslationOutdatedMapService.
        $outdatedFields = $this->outdated->mapFor($tree);

        // Phase 5d.4-Followup: $project fuer die einheitliche
        // Tab-Leiste (<x-projects.tabs>) mitliefern.
        return view('translate.index', compact('data', 'project', 'outdatedFields'));
    }

    /**
     * Phase 5aa.3: Bulk-Save aller englischen Uebersetzungen einer
     * Projekt-Uebersetzen-Sicht in einem Rutsch.
     *
     * Erwartet einen `translations`-Payload der Form
     * `{ 'Chapter.5.name': 'English name', 'Text.42.text': '...' }`.
     * Der Key-Prefix ist der Kurzname des Modells; die Save-Kette
     * ruft `setTranslation(field, 'en', value)` und `save()` auf.
     *
     * Nicht-erlaubte Modelltypen oder Modelle aus fremden Projekten
     * werden uebersprungen (Authorization pro Modell ueber ProjectPolicy).
     */
    public function saveTranslations(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        // Q4-Etappe 8 · E3d (2026-09-11): Model-Binding statt `$id`.
        $this->authorize('update', $project);

        $payload = $request->input('translations', []);
        if (! is_array($payload)) {
            $payload = [];
        }

        $modelMap = [
            'Chapter' => Chapter::class,
            'Entry' => Entry::class,
            'Text' => Text::class,
            'Gallery' => Gallery::class,
            'Image' => Image::class,
            'Audiovisual' => Audiovisual::class,
        ];

        foreach ($payload as $key => $value) {
            [$modelKey, $modelId, $field] = array_pad(explode('.', $key, 3), 3, null);
            if (! isset($modelMap[$modelKey]) || $modelId === null || $field === null) {
                continue;
            }
            /** @var Chapter|Entry|Text|Gallery|Image|Audiovisual|null $model */
            $model = $modelMap[$modelKey]::find($modelId);
            if ($model === null) {
                continue;
            }

            // Gehoert das Modell wirklich zu diesem Projekt? Die
            // `project()`-Kette der Modelle gibt bei einigen (Chapter,
            // Entry) eine Relation, bei anderen (Text, Audiovisual) das
            // Model direkt zurueck — beide Zweige normalisieren.
            $modelProject = null;
            $result = $model->project();
            if ($result instanceof Relation) {
                $modelProject = $result->getResults();
            } elseif ($result instanceof Project) {
                $modelProject = $result;
            }
            if ($modelProject === null || (int) $modelProject->id !== (int) $project->id) {
                continue;
            }
            if (! in_array($field, $model->translatable ?? [], true)) {
                continue;
            }

            $model->setTranslation($field, 'en', (string) $value);
            $model->save();

            // Phase 5ab.5 (Design v6 § 4): Sync-Marker fuer „Original
            // nach Uebersetzung geaendert". Wir merken die aktuelle
            // Fassung des Subjects als Referenz. Aendert der Kurator
            // spaeter das Original, waechst die Version — und wir
            // koennen den Warn-Chip zeigen.
            $latestRevisionId = Revision::query()
                ->where('subject_type', $model::class)
                ->where('subject_id', $model->getKey())
                ->latest('created_at')
                ->value('id');
            if ($latestRevisionId !== null) {
                TranslationSourceReference::updateOrCreate(
                    [
                        'subject_type' => $model::class,
                        'subject_id' => $model->getKey(),
                        'field' => $field,
                        'locale' => 'en',
                    ],
                    ['source_revision_id' => $latestRevisionId]
                );
            }
        }

        // 5aa.3-Followup: Auto-Save-on-Blur schickt AJAX — dann JSON-Antwort,
        // sonst wie bisher zurueck zur Uebersetzen-Sicht mit Success-Meldung.
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('projects.translations.edit', $project)
            ->with('success', __('message_edit_project_success'));
    }

    /**
     * Sammelt Chapter/Entry/Text/Gallery/Audiovisual-Baum fuer die
     * Uebersetzen-Sicht plus die Prozent-Zahl (uebersetzt / total).
     *
     * `public`, damit die Feature-Tests
     * (`tests/Feature/Controllers/ProjectControllerLogTest.php`) den
     * Baum-Aufbau direkt pinnen koennen — analog zum Original-Verhalten
     * im ProjectController vor dem I6-Split.
     *
     * @return array{data: array<int, mixed>, percentageOfTranslation: float, projectId: int}
     */
    public function allData(int $id): array
    {
        // Strict-Mode: chapters/entries/mediaContent muessen eager
        // geladen sein, weil die Schleife unten direkt auf
        // $project->chapters, $chapter->entries und
        // $entry->mediaContent zugreift.
        $project = Project::withTranslateTree()->findOrFail($id);
        $data = [];
        $isTranslated = 0;
        $total = 0;

        foreach ($project->chapters as $chapter) {
            $data[$chapter->id] = $chapter;
            if ($chapter->is_translated == 1) {
                $isTranslated++;
            }
            $total++;
            $entries = [];
            foreach ($chapter->entries as $entry) {
                $entries[$entry->id] = $entry;
                if ($entry->is_translated == 1) {
                    $isTranslated++;
                }
                $total++;
                $array = [];
                if (count($entry->mediaContent) > 0) {
                    $collection = $entry->mediaContent->toArray();
                    usort(
                        $collection,
                        function ($item1, $item2) {
                            return $item1['position'] <=> $item2['position'];
                        }
                    );

                    foreach ($collection as $item) {
                        // E.7b Welle 4b (ADR-0022): Diskriminator-Check
                        // auf content_type / content_id (neue Spalten).
                        // Doppelschreibung in den Services haelt die alten
                        // gleichwertig bis Welle 4d.
                        if ($item['content_type'] == Text::class) {
                            // Strict-Mode: originText/copyrightText
                            // werden unten gleich gelesen, deshalb
                            // gleich mit-eager-laden.
                            $text = Text::with(['originText', 'copyrightText'])
                                ->find($item['content_id']);
                            if ($text) {
                                $text->media_id = $item['id'];
                                $array[] = $text;
                                if ($text->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;

                                // Q4-Etappe 7 · E7-1-Nebenfund (2026-09-11):
                                // originText und copyrightText koennen seit
                                // dem nullable-Umbau der image_sources
                                // (Q3) auch bei Text-Sources leer sein.
                                // Fehlende Source zaehlt nicht in die
                                // Uebersetzungs-Statistik.
                                if ($text->originText !== null) {
                                    if ($text->originText->is_translated == 1) {
                                        $isTranslated++;
                                    }
                                    $total++;
                                }

                                if ($text->copyrightText !== null) {
                                    if ($text->copyrightText->is_translated == 1) {
                                        $isTranslated++;
                                    }
                                    $total++;
                                }
                            }
                        } elseif ($item['content_type'] == Audiovisual::class) {
                            $audiovisual = Audiovisual::find($item['content_id']);
                            if ($audiovisual) {
                                $audiovisual->media_id = $item['id'];
                                $array[] = $audiovisual;
                                if ($audiovisual->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;
                            }
                        } else {
                            // E.7b Welle 4b: ehemals media_contentable_type
                            // == 'App\Models\Image' (historischer Schiefstand).
                            // Neue Spalte content_type fuehrt sauber Gallery::class.
                            // Strict-Mode: images wird unten gleich
                            // gelesen, deshalb mit-eager-laden.
                            $gallery = Gallery::with('images')->find($item['content_id']);
                            if ($gallery) {
                                $gallery->media_id = $item['id'];
                                $gallery->image_list = $gallery->images;
                                $array[] = $gallery;

                                if ($gallery->is_translated == 1) {
                                    $isTranslated++;
                                }
                                $total++;
                            }
                        }
                    }
                }

                $entries[$entry->id]->media = $array;
            }
            $data[$chapter->id]->entry = $entries;
        }

        $percentage = 0;

        if ($isTranslated > 0) {
            $percentage = round(($isTranslated / $total) * 100, 2);
        }

        return ['data' => $data, 'percentageOfTranslation' => $percentage, 'projectId' => $id];
    }
}
