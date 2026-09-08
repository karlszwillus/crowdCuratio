<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Models\Chapter;
use App\Models\Entry;
use App\Models\User;
use Tests\TestCase;

/**
 * Q4-Etappe 4 / C1f (2026-09-08): Chapter- und Entry-Anlage
 * redirectet zurück in den Editor mit URL-Fragment auf das neu
 * entstandene Element, damit der Browser (bzw. der livewire:navigated-
 * Scroll-Listener) direkt dorthin scrollt — analog zum Inline-Add-
 * Flow für Content-Blöcke.
 */
it('Chapter-Add redirected mit Fragment auf den neuen Chapter', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $response = $this->actingAs($owner)
        ->from(route('projects.edit', $project->id))
        ->post(route('chapters.store'), [
            'chapterTitle' => 'Neues Kapitel',
            'projectId' => $project->id,
        ]);

    $chapter = Chapter::where('project_id', $project->id)->latest('id')->first();
    expect($chapter)->not->toBeNull();

    $response->assertRedirect(
        route('projects.edit', $project->id).'#anchor_Chapter_'.$chapter->id
    );
});

it('Entry-Add redirected mit Fragment auf den neuen Entry', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $response = $this->actingAs($owner)
        ->from(route('projects.edit', $project->id))
        ->post(route('entries.store'), [
            'entryTitle' => 'Neuer Abschnitt',
            'chapterId' => $chapter->id,
        ]);

    $entry = Entry::where('chapter_id', $chapter->id)->latest('id')->first();
    expect($entry)->not->toBeNull();

    $response->assertRedirect(
        route('projects.edit', $project->id).'#anchor_Entry_'.$entry->id
    );
});
