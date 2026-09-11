<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Project;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Q4-Etappe 2 / I6 (2026-08-27): Vorschau- und PDF-Download-Endpunkte
 * fuer Projekte. Extrahiert aus dem `ProjectController` (God-Object,
 * 8 Concerns). Beide Methoden gaten gegen `view` auf dem Project —
 * das war schon in E.7b Sub-Welle 3 als Hotfix nachgezogen worden.
 */
class ProjectPreviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function previewProject(Request $request): Response|View|RedirectResponse
    {
        $project = Project::withPreviewTree()->findOrFail($request['project']);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Web-Preview eines fremden Projekts war ohne Gate erreichbar
        // — Reader-via-URL.
        $this->authorize('view', $project);

        // Q4-Etappe 5 / G3 (2026-09-08): Multi-Page-Reader-Setting.
        // Bei `reader_layout = multi-page` auf das erste Kapitel
        // umleiten, sonst wie bisher als One-Pager rendern.
        // PDF-/Print-Kontexte (?pdf=1) bleiben immer beim One-Pager,
        // damit die PDF-Pipeline nichts umschreiben muss.
        if ($project->usesMultiPageReader() && ! $request->has('pdf')) {
            /** @var Chapter|null $firstChapter */
            $firstChapter = $project->chapters->sortBy('position')->first();
            if ($firstChapter !== null) {
                // Q4-Etappe 5 / G6 Nachreview (2026-09-09): Farb-
                // Parameter (colorAccent/colorChapter) sowie andere
                // Query-Params vom Form am Projekt-Index weitergeben
                // — sonst kippen sie im Multi-Page-Redirect raus.
                return redirect()->route('preview.chapter', array_merge(
                    $request->query(),
                    [
                        'project' => $project->id,
                        'chapter' => $firstChapter->id,
                    ]
                ));
            }
            // Keine Kapitel → fällt auf One-Pager zurück (zeigt
            // leeren Header + Fußzeile, kein Broken State).
        }

        $parameters = $this->parametersFromRequest($request, $project->id);

        return view('preview.index', compact('project', 'parameters'));
    }

    /**
     * Q4-Etappe 5 / G3 (2026-09-08): Multi-Page-Reader — rendert
     * genau ein Kapitel mit Sidebar-Navigation zu den anderen.
     * Nutzt dieselben Content-Partials wie der One-Pager.
     */
    public function previewChapter(Request $request, int $chapter): View
    {
        // `chapter` kommt als Route-Slot (/preview/chapters/{chapter}),
        // `project` als Query-Parameter — analog zum bestehenden
        // /preview-Endpoint, der project historisch aus dem Request
        // liest (kein /projects/{project}-Prefix in dieser Group).
        $projectId = (int) $request['project'];
        abort_if($projectId <= 0, 404);

        $projectModel = Project::withPreviewTree()->findOrFail($projectId);
        $this->authorize('view', $projectModel);

        /** @var Chapter|null $chapterModel */
        $chapterModel = $projectModel->chapters->firstWhere('id', $chapter);
        abort_if($chapterModel === null, 404);

        $parameters = $this->parametersFromRequest($request, $projectModel->id);

        return view('preview.chapter', [
            'project' => $projectModel,
            'chapter' => $chapterModel,
            'parameters' => $parameters,
        ]);
    }

    /**
     * Gemeinsame Query-Param-Auswertung für Preview-Endpunkte.
     * Vorher direkt in previewProject inline — jetzt geteilt mit
     * previewChapter, damit beide Endpunkte dieselben Farb- und
     * Modus-Parameter kennen.
     *
     * @return array<string, mixed>
     */
    private function parametersFromRequest(Request $request, int $projectId): array
    {
        $parameters = [];

        if (isset($request['colorAccent'])) {
            $parameters['colorAccent'] = $request['colorAccent'];
        }
        if (isset($request['colorChapter'])) {
            $parameters['colorChapter'] = $request['colorChapter'];
        }
        $parameters['backgroundSecond'] = isset($request['backgroundSecond']) ? 'hintergrundgrau' : 'hintergrundweiss';
        if (isset($request['collapse'])) {
            $parameters['collapse'] = 1;
        }
        if (isset($request['pdf'])) {
            $parameters['pdf'] = 1;
        }
        $parameters['id'] = $projectId;

        return $parameters;
    }

    /**
     * Q4-Etappe 7 · E7-6 / Etappe-6-Rest (2026-09-11): Übersicht aller
     * Abbildungen des Projekts, gruppiert nach Kapitel / Abschnitt.
     * Rail-Fußlink „Alle Abbildungen" aus dem Multi-Page-Reader zeigt
     * hierhin.
     */
    public function previewAllImages(Request $request): View
    {
        $project = Project::withPreviewTree()->findOrFail($request['project']);
        $this->authorize('view', $project);

        return view('preview.all-images', compact('project'));
    }

    /**
     * Q4-Etappe 7 · Etappe-6-Rest (2026-09-11): Bildnachweise-Sammel-
     * seite — bisher zeigte der Fußzeilen-Link auf `#bildnachweise`
     * ins Nichts. Sammelt Copyright/Origin-Angaben projektweit.
     */
    public function previewCredits(Request $request): View
    {
        $project = Project::withPreviewTree()->findOrFail($request['project']);
        $this->authorize('view', $project);

        return view('preview.credits', compact('project'));
    }

    /**
     * Q4-Etappe 7 · Etappe-6-Rest (2026-09-11): Barrierefreiheits-
     * erklaerung — statische Seite, damit der Fußzeilen-Link
     * `#barrierefreiheit` ein echtes Ziel hat.
     */
    public function previewA11y(Request $request): View
    {
        $project = Project::withPreviewTree()->findOrFail($request['project']);
        $this->authorize('view', $project);

        return view('preview.a11y', compact('project'));
    }

    /**
     * Q4-Etappe 7 · E7-6 / Etappe-6-Rest (2026-09-11): PDF-Ausgabe für
     * genau ein Kapitel. Rail-Fußlink „Kapitel als PDF" aus dem
     * Multi-Page-Reader zeigt hierhin. Rendert dieselbe PDF-Pipeline
     * wie das Gesamtprojekt, aber mit einer auf ein Kapitel gefilterten
     * Projekt-Instanz.
     */
    public function downloadChapterPdf(Request $request, int $chapter): void
    {
        $project = Project::withPreviewTree()->findOrFail($request['project']);
        $this->authorize('view', $project);

        // Chapters-Collection auf das gewünschte Kapitel reduzieren,
        // damit das bestehende PDF-Layout ohne Änderungen weiterlaufen
        // kann.
        $project->setRelation(
            'chapters',
            $project->chapters->where('id', $chapter)->values(),
        );

        $parameters = [
            'backgroundSecond' => 'hintergrundgrau',
            'pdf' => 1,
        ];

        $html = view('preview.pdf.layout', compact('project', 'parameters'))->render();

        $options = new Options;
        $options->setChroot(['/var/www/html/public/']);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream();
    }

    /**
     * Generate PDF.
     */
    public function downloadPreview(Request $request): void
    {
        $parameters = [];

        if (isset($request->colorAccent)) {
            $parameters['colorAccent'] = $request->colorAccent;
        }
        if (isset($request->colorChapter)) {
            $parameters['colorChapter'] = $request->colorChapter;
        }
        $parameters['backgroundSecond'] = (isset($request->backgroundSecond)) ? 'hintergrundgrau' : 'hintergrundweiss';
        if (isset($request->collapse)) {
            $parameters['collapse'] = $request->collapse;
        }
        if (isset($request->pdf)) {
            $parameters['pdf'] = 1;
        }

        $project = Project::withPreviewTree()->findOrFail($request->id);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // PDF-Download fremder Projekte ohne Gate war erreichbar.
        $this->authorize('view', $project);

        // Q4-Etappe 6 · G7 (2026-09-10): PDF-Neubau. Das alte
        // preview/pdf.blade.php (~1.100 LoC, Duplikat des Web-Readers
        // mit dompdf-Anpassungen) ist ersetzt durch preview/pdf/layout
        // plus reduzierte Content-Partials — sw/w-Ausgabe mit Charakter-
        // Akzent, Audio/Video als Hinweiszeile statt Player-Frame.
        $html = view('preview.pdf.layout', compact('project', 'parameters'))->render();

        $options = new Options;
        $options->setChroot(['/var/www/html/public/']);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream();
    }
}
