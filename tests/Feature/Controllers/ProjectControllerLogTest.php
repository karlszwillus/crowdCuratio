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

use App\Http\Controllers\ProjectController;
use App\Models\Chapter;
use App\Models\User;
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectController — history + allData
|--------------------------------------------------------------------------
|
| Direkte Methoden-Aufrufe für die zwei großen Activity-/Translate-
| Pfade. Beide sind public, aber heute via Routes nur indirekt
| erreichbar (edit-View mit log/model-Query-Param, translate-Route).
|
| Setup: app()->make(ProjectController::class) lässt Laravel die
| DI auflösen, danach Aufruf der Methoden mit Modell-IDs.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('allData liefert für ein leeres Project ein 100%-Translation-Paket', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    /** @var ProjectController $controller */
    $controller = app(ProjectController::class);

    $result = $controller->allData($project->id);

    expect($result)
        ->toBeArray()
        ->toHaveKey('projectId', $project->id)
        ->toHaveKey('data', [])
        ->toHaveKey('percentageOfTranslation', 0);
});

it('allData zählt Chapters/Entries und deren Übersetzungs-Status', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);

    // is_translated ist nicht in Chapter/Entry::$fillable —
    // unter Strict-Mode wirft Mass-Assignment. Property-Setter
    // statt Factory-Override.
    $chapter1 = makeChapter($project);
    $chapter1->is_translated = true;
    $chapter1->save();

    makeChapter($project); // is_translated=0 ist Default

    $entry1 = makeEntry($chapter1);
    $entry1->is_translated = true;
    $entry1->save();

    makeEntry($chapter1);

    /** @var ProjectController $controller */
    $controller = app(ProjectController::class);

    $result = $controller->allData($project->id);

    expect($result['data'])->toHaveCount(2);
    // 4 items insgesamt (2 chapters + 2 entries), 2 davon translated → 50 %
    expect($result['percentageOfTranslation'])->toBe(50.0);
});

/**
 * Helper: ruft die private `ProjectController::history()` via
 * Reflection auf. Welle-3-Hotfix-II (ADR-0022, Block E.7b):
 * history() ist jetzt `private`, weil sie nur intern von edit()
 * aufgerufen wird (keine eigene Route). Diese Charakterisierungs-
 * Tests sollen das Verhalten dennoch direkt pinnen, ohne den
 * gesamten edit()-Pfad aufzubauen. Eine spätere Welle 4 dürfte
 * `history()` zu einem Service extrahieren (ActivityHistoryService),
 * dann werden diese Tests umgezogen und der Reflection-Trick fällt.
 *
 * @return array<int, array<string, mixed>>
 */
function invokeHistory(ProjectController $controller, string $model, int $id): array
{
    $method = new ReflectionMethod($controller, 'history');
    /** @var array<int, array<string, mixed>> $logs */
    $logs = $method->invoke($controller, $model, $id);

    return $logs;
}

it('history liefert für ein Modell ohne Activity-Einträge ein leeres Log-Array', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project);

    /** @var ProjectController $controller */
    $controller = app(ProjectController::class);

    $logs = invokeHistory($controller, 'Chapter', $chapter->id);

    expect($logs)->toBeArray()->toBeEmpty();
});

it('history liefert für ein Chapter mit Update-Activity einen Log-Eintrag', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Update triggert Spatie\Activitylog — eine Activity mit
    // properties->language='de' (über Chapter::tapActivity) und
    // description != 'created' wird angelegt.
    Chapter::find($chapter->id)->update(['name' => 'Aktualisiert']);

    /** @var ProjectController $controller */
    $controller = app(ProjectController::class);

    $logs = invokeHistory($controller, 'Chapter', $chapter->id);

    expect($logs)->toBeArray();
    expect(count($logs))->toBeGreaterThan(0);
    expect($logs[0])->toHaveKeys(['id', 'userName', 'created_at']);
});
