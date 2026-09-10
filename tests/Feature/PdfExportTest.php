<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| PDF-Export-Smoke (Q4-Etappe 6 · G7)
|--------------------------------------------------------------------------
|
| Fixiert den kritischen Pfad des neuen PDF-Layouts: das Blade
| unter `preview.pdf.layout` rendert für ein Projekt mit Kapitel,
| Abschnitt und Text-Content sauber und ohne Exception. Der volle
| dompdf-Roundtrip (HTML → PDF) läuft im tatsächlichen Download-
| Endpoint mit `$dompdf->stream()`, das PHP-Exit ist — der bleibt
| als manueller Smoke-Punkt (`docs/smoke.md`).
*/

it('preview.pdf.layout rendert ein minimales Projekt ohne Exception', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    makeEntry($chapter);

    $project->refresh()->load([
        'chapters.entries.mediaContent.text',
        'chapters.entries.mediaContent.gallery.images',
        'chapters.entries.mediaContent.audiovisual',
        'chapters.entries.mediaContent.quoteBlock.source',
        'chapters.entries.mediaContent.dataFactBlock',
    ]);

    $html = view('preview.pdf.layout', [
        'project' => $project,
        'parameters' => [],
    ])->render();

    expect($html)->toBeString();
    expect($html)->toContain('<html');
    expect($html)->toContain('</html>');
    expect($html)->toContain($project->name);
    expect($html)->toContain($chapter->name);
});

it('preview.pdf.layout trägt die Akzent-Farbe des Projekt-Charakters', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    // Charakter „archiv" → Akzent #2f4a63 (Handoff-Palette).
    $project->character = Project::CHARACTER_ARCHIV;
    $project->save();
    makeChapter($project);

    $project->refresh()->load(['chapters.entries.mediaContent']);

    $html = view('preview.pdf.layout', [
        'project' => $project,
        'parameters' => [],
    ])->render();

    expect($html)->toContain('#2f4a63');
});
