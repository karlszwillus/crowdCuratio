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

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Charakterisierungs-Tests vor dem Build-Stack-Reset
 * (Mix → Vite, Tailwind 2 → 4).
 *
 * Diese Tests prüfen die strukturelle Integrität der gerenderten
 * Hauptseiten — nicht das exakte HTML (das ändert sich durch die
 * Tailwind-4-Class-Renames erwartet), sondern die Anwesenheit
 * der erwarteten interaktiven Elemente: Forms, Buttons, Inputs,
 * Tabellen, Hierarchie-Karten.
 *
 * Bricht ein Test nach der Migration, fehlt ein Element strukturell
 * — kein Style-Drift.
 */
beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'PermissionTableSeeder']);
    Artisan::call('db:seed', ['--class' => 'RoleTableSeeder']);
});

it('login page renders form with email, password and submit button', function () {
    /** @var TestCase $this */
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('lang="de"', false);
    $response->assertSee('<form', false);
    $response->assertSee('type="email"', false);
    $response->assertSee('type="password"', false);
    $response->assertSee('type="submit"', false);
});

it('project list page renders datatable with new-project link for editor', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);

    $response = $this->actingAs($owner)->get('/projects');

    $response->assertOk();
    $response->assertSee('lang="de"', false);
    $response->assertSee($project->name);
    $response->assertSeeText('Neu hinzufügen');
});

it('project edit view renders chapter card and add-chapter button for owner', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);
    $chapter = makeChapter($project);

    $response = $this->actingAs($owner)->get("/projects/{$project->id}/edit");

    $response->assertOk();
    $response->assertSee('lang="de"', false);
    $response->assertSee($chapter->name);
    $response->assertSeeText('Kapitel hinzufügen');
});

it('image upload modal renders mandatory copyright and source fields', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Editor');
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    makeEntry($chapter);

    $response = $this->actingAs($owner)->get("/projects/{$project->id}/edit");

    $response->assertOk();
    // Pflichtfeld-Markierung — Sternchen-Konvention. Steht als
    // placeholder="…"-Attribut, daher assertSee statt assertSeeText.
    $response->assertSee('* (Pflichtfeld)', false);
    // Modal-Form für Bild-Upload — entryId und galleryId als hidden inputs.
    $response->assertSee('name="entryId"', false);
    $response->assertSee('name="galleryId"', false);
});

// Preview-Layout-Charakterisierung bewusst nicht hier — die Preview-
// Route hat ein Token-Gating, das die Sicherungs-Schicht-Logik
// (rein strukturell, ohne Auth-/Token-Setup) nicht sauber abbildet.
// Das `lang="de"`-Attribut auf den drei Preview-Templates ist
// statisch im Markup und durch den Hotfix-Welle-0-Diff abgedeckt.
