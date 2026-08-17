<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5aa.2 — leerer Projekt-Text greift auf den
 * systemweiten Legal-Text zurueck. Beide Zweige (Impressum, AGB) werden
 * abgeklopft: gefuellter Projekt-Wert bleibt Projekt-Wert, leerer Wert
 * loest den Fallback aus. Regression-Schutz gegen Copy-Paste-Fehler in
 * `ProjectLegalText::imprintFor()` / `termsFor()`.
 */

use App\Models\Imprint;
use App\Models\TermsConditions;
use App\Models\User;
use App\Support\ProjectLegalText;

beforeEach(function () {
    // Systemtexte: strukturierte Adresse + einfacher AGB-Text.
    Imprint::create([
        'name' => ['firstname' => 'System', 'lastname' => 'Betreiber'],
        'address' => ['address' => 'Musterstr. 1', 'postcode' => '10115'],
        'contact' => ['phone' => '030 123', 'email' => 'kontakt@example.test'],
    ]);
    TermsConditions::create([
        'terms_conditions' => '<p>System-AGB Fallback</p>',
    ]);
});

it('gibt bei leerem Projekt-Impressum den zusammengesetzten Systemtext zurueck', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['imprint' => '']);

    $imprint = ProjectLegalText::imprintFor($project);

    expect($imprint)->toContain('System Betreiber')
        ->and($imprint)->toContain('Musterstr. 1')
        ->and($imprint)->toContain('kontakt@example.test');
});

it('behaelt bei gefuelltem Projekt-Impressum den Projekt-Wert', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['imprint' => 'Angepasstes Projekt-Impressum']);

    expect(ProjectLegalText::imprintFor($project))->toBe('Angepasstes Projekt-Impressum');
});

it('gibt bei leerem Projekt-AGB den System-AGB zurueck', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['terms' => '']);

    expect(ProjectLegalText::termsFor($project))->toBe('<p>System-AGB Fallback</p>');
});

it('behaelt bei gefuelltem Projekt-AGB den Projekt-Wert', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner, ['terms' => '<p>Projekt-AGB</p>']);

    expect(ProjectLegalText::termsFor($project))->toBe('<p>Projekt-AGB</p>');
});
