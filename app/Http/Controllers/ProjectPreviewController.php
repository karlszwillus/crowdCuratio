<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

    public function previewProject(Request $request): View
    {
        $parameters = [];

        if (isset($request['colorAccent'])) {
            $parameters['colorAccent'] = $request['colorAccent'];
        }
        if (isset($request['colorChapter'])) {
            $parameters['colorChapter'] = $request['colorChapter'];
        }
        $parameters['backgroundSecond'] = (isset($request['backgroundSecond'])) ? 'hintergrundgrau' : 'hintergrundweiss';
        if (isset($request['collapse'])) {
            $parameters['collapse'] = 1;
        }
        if (isset($request['pdf'])) {
            $parameters['pdf'] = 1;
        }
        $parameters['id'] = $request['project'];
        $project = Project::withPreviewTree()->findOrFail($request['project']);

        // Block E.7b Sub-Welle 3-Hotfix (ADR-0022, ADR-0013):
        // Web-Preview eines fremden Projekts war ohne Gate erreichbar
        // — Reader-via-URL.
        $this->authorize('view', $project);

        return view('preview.index', compact('project', 'parameters'));
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

        $html = view('preview.pdf', compact('project', 'parameters'))->render();

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
