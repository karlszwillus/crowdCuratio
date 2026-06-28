<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

use App\Models\User;
use App\Services\LogService;
use App\Support\PermissionName;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| LogService::history und LogService::textLog
|--------------------------------------------------------------------------
|
| history liefert eine kondensierte Liste der Activity-Einträge für
| ein Subject — id, userName und created_at. textLog liefert die
| Property-Diffs als highlight-Markup für die History-Ansicht.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('history liefert eine leere Liste, wenn keine Activities für das Subject existieren', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // makeChapter triggert Spatie-LogsActivity → 'created'-Eintrag.
    // Für diesen Test wollen wir den Zustand "keine Activities".
    Activity::query()->delete();

    $service = new LogService('chapter');
    $logs = $service->history($chapter->id);

    expect($logs)
        ->toBeArray()
        ->toBeEmpty();
});

it('history liefert pro Activity einen Eintrag mit id, userName und created_at, wenn changes nicht leer sind', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create(['name' => 'Anna', 'last_name' => 'Beispiel']);
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // Auto-Created-Activity aus dem LogsActivity-Trait wegräumen,
    // damit der Test nur den manuell angelegten Eintrag sieht.
    Activity::query()->delete();

    $this->actingAs($owner);

    // Direkte Activity-Erzeugung — bewusst ohne den LogsActivity-
    // Trait-Weg, damit wir die Such-Logik unabhängig vom Trigger-
    // Pfad testen können.
    Activity::create([
        'log_name' => 'Chapter',
        'description' => 'updated',
        'subject_type' => 'App\\Models\\Chapter',
        'subject_id' => $chapter->id,
        'causer_type' => 'App\\Models\\User',
        'causer_id' => $owner->id,
        'properties' => [
            'old' => ['name' => 'Vorher'],
            'attributes' => ['name' => 'Nachher'],
            'language' => 'de',
        ],
    ]);

    $service = new LogService('chapter');
    $logs = $service->history($chapter->id);

    expect($logs)
        ->toBeArray()
        ->toHaveCount(1)
        ->and($logs[0])
        ->toHaveKeys(['id', 'userName', 'created_at']);

    expect($logs[0]['userName'])->toContain('Anna');
    expect($logs[0]['userName'])->toContain('Beispiel');
});

it('history filtert Activities ohne changes-Diff aus', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    // Auto-Created-Activity aus dem LogsActivity-Trait wegräumen.
    Activity::query()->delete();

    Activity::create([
        'log_name' => 'Chapter',
        'description' => 'created',
        'subject_type' => 'App\\Models\\Chapter',
        'subject_id' => $chapter->id,
        'causer_type' => 'App\\Models\\User',
        'causer_id' => $owner->id,
        'properties' => [],
    ]);

    $service = new LogService('chapter');
    $logs = $service->history($chapter->id);

    expect($logs)->toBeEmpty();
});

it('textLog liefert ein leeres Array, wenn keine Activities mit Property-Diff existieren', function () {
    /** @var TestCase $this */
    //
    // Anmerkung: der vollständige textLog-Pfad (Property-Diff-Render
    // mit highlight-Markup) ist heute mit zwei latenten Bugs
    // versehen, die unter Strict-Mode beim Zugriff auf
    // $value->entry_name / $value->chapter_name werfen — Spalten,
    // die in der activity_log-Tabelle nicht existieren. Im echten
    // Produktiv-Aufruf via ProjectController::getCurrentLog wird
    // LogService sogar ohne Konstruktor-Argument instanziiert, der
    // innere Schleifenkörper läuft also nie und der Bug bleibt
    // versteckt. Ein vollständiger Charakterisierungs-Test gehört
    // in den Phase-4-ActivityHistoryService-Refactor (Block F);
    // hier testen wir den äußeren Pfad (leere Activities → leeres
    // Resultat), den wir robust grün halten können.
    //
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    Activity::query()->delete();

    $service = new LogService('chapter');
    $changes = $service->textLog($chapter->id);

    expect($changes)
        ->toBeArray()
        ->toBeEmpty();
});

it('getParentText liefert für entries den chapter_name und entry_name als Join-Ergebnis', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Kapitel Eins']);
    $entry = makeEntry($chapter, ['name' => 'Entry Eins']);

    $service = new LogService('entry');
    $result = $service->getParentText($entry->id);

    expect($result)->toHaveCount(1);

    $row = $result->first();

    // chapter_name ist JSON-encoded (Spatie-Translatable), wir prüfen
    // auf Substring statt strict equality.
    expect((string) $row->chapter_name)->toContain('Kapitel Eins');
    expect((string) $row->entry_name)->toContain('Entry Eins');
});

it('highlightTextDifference markiert neuen Anteil grün und alten Anteil rot', function () {
    /** @var TestCase $this */
    $service = new LogService('chapter');

    $result = $service->highlightTextDifference('Hallo Welt', 'Hallo schöne Welt');

    expect($result)
        ->toHaveKey('old')
        ->toHaveKey('new');

    expect($result['new'])
        ->toContain('background-color:#ccffcc')
        ->toContain('schöne');

    expect($result['old'])
        ->toContain('background-color:#ffcccc')
        ->toContain('<del');
});

it('highlightTextDifference packt identische Strings stabil in das Diff-Markup', function () {
    /** @var TestCase $this */
    $service = new LogService('chapter');

    $result = $service->highlightTextDifference('gleicher Text', 'gleicher Text');

    expect($result['old'])->toContain('gleicher Text');
    expect($result['new'])->toContain('gleicher Text');
});

it('getParentText liefert für texts den Kontext via media_content-Join', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Kapitel Texts']);
    $entry = makeEntry($chapter, ['name' => 'Entry Texts']);
    $text = makeText();

    \App\Models\MediaContent::create([
        'content_id' => $text->id,
        'content_type' => \App\Models\Text::class,
        'parent_id' => $entry->id,
        'parent_type' => \App\Models\Entry::class,
        'position' => 1,
    ]);

    $service = new LogService('text');
    $result = $service->getParentText($text->id);

    expect($result)->toHaveCount(1);

    $row = $result->first();
    expect((string) $row->chapter_name)->toContain('Kapitel Texts');
    expect((string) $row->entry_name)->toContain('Entry Texts');
});
