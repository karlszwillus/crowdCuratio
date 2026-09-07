<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\User;
use Tests\TestCase;

/**
 * Q4-Etappe 3 / C0b Regression (2026-09-07): ProjectController::update
 * hat citation_depth und source_required initial nicht persistiert —
 * die Radio-Auswahl sprang nach dem Speichern wieder zurück.
 */
it('speichert citation_depth beim Projekt-Update', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    expect($project->citation_depth ?? 'simple')->toBe('simple');

    $this->actingAs($owner)
        ->from(route('projects.edit', $project))
        ->put(route('projects.update', $project), [
            'name' => 'Name',
            'imprint' => 'Impressum',
            'citation_depth' => 'full',
            'source_required' => '1',
        ])
        ->assertRedirect();

    $fresh = $project->fresh();
    expect($fresh->citation_depth)->toBe('full');
    expect($fresh->requiresSources())->toBeTrue();
});

it('kann source_required abschalten und persistiert das', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $project->update(['citation_depth' => 'full', 'source_required' => true]);

    $this->actingAs($owner)
        ->from(route('projects.edit', $project))
        ->put(route('projects.update', $project), [
            'name' => 'Name',
            'imprint' => 'Impressum',
            'citation_depth' => 'full',
            // source_required weggelassen — Checkbox nicht gesetzt
        ])
        ->assertRedirect();

    expect($project->fresh()->requiresSources())->toBeFalse();
});
