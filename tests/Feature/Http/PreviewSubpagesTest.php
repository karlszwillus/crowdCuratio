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
use App\Support\PermissionName;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Reader-Nebenseiten + Kapitel-PDF — Authorization und Eingabe-Guards
|--------------------------------------------------------------------------
|
| Die vier Endpunkte (Alle Abbildungen, Bildnachweise, Barrierefreiheit,
| Kapitel-PDF) hatten keine Tests. Regel: jede Route bekommt einen
| Authorization-Test.
|
| Der Happy-Path des Kapitel-PDFs streamt per dompdf direkt in den
| Output und bleibt manueller Smoke-Punkt (siehe PdfExportTest).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Reader', 'guard_name' => 'web'])->syncPermissions(['view']);
});

dataset('reader-nebenseiten', [
    'Alle Abbildungen' => ['preview.all_images', 'reader_all_images_title'],
    'Bildnachweise' => ['preview.credits', 'reader_credits_title'],
    'Barrierefreiheit' => ['preview.a11y', 'reader_a11y_title'],
]);

it('rendert die Nebenseite für die Eigentümerin', function (string $route, string $titleKey) {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner, ['name' => 'Häusergeschichten']);
    makeEntry(makeChapter($project));

    $this->actingAs($owner)
        ->get(route($route, ['project' => $project->id]))
        ->assertOk()
        ->assertSee('cc-page__title', false)
        ->assertSee(__($titleKey))
        ->assertSee('Häusergeschichten');
})->with('reader-nebenseiten');

it('verweigert die Nebenseite für Fremde', function (string $route) {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $intruder */
    $intruder = User::factory()->create();
    $intruder->assignRole('Reader');
    $project = makeProject($owner);

    $this->actingAs($intruder)
        ->get(route($route, ['project' => $project->id]))
        ->assertForbidden();
})->with('reader-nebenseiten');

it('antwortet mit 404 bei fehlendem oder ungültigem project-Parameter', function (string $route, mixed $project) {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRole('Reader');

    $params = $project === null ? [] : ['project' => $project];

    $this->actingAs($user)->get(route($route, $params))->assertNotFound();
})->with('reader-nebenseiten')->with([
    'fehlt' => [null],
    'leer' => [''],
    'Text' => ['abc'],
    'negativ' => ['-3'],
]);

it('leitet Gäste von den Nebenseiten zum Login', function (string $route) {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);

    $this->get(route($route, ['project' => $project->id]))->assertRedirect(route('login'));
})->with('reader-nebenseiten');

it('Kapitel-PDF: Kapitel aus fremdem Projekt liefert 404 statt leerem PDF', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Reader');
    $project = makeProject($owner);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $foreignChapter = makeChapter(makeProject($stranger));

    $this->actingAs($owner)
        ->get(route('preview.chapter_pdf', ['chapter' => $foreignChapter->id, 'project' => $project->id]))
        ->assertNotFound();
});

it('Kapitel-PDF: Fremde bekommen 403', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    /** @var User $intruder */
    $intruder = User::factory()->create();
    $intruder->assignRole('Reader');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $this->actingAs($intruder)
        ->get(route('preview.chapter_pdf', ['chapter' => $chapter->id, 'project' => $project->id]))
        ->assertForbidden();
});

it('Kapitel-PDF: ohne project-Parameter 404', function () {
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $chapter = makeChapter(makeProject($user));

    $this->actingAs($user)
        ->get(route('preview.chapter_pdf', ['chapter' => $chapter->id]))
        ->assertNotFound();
});
