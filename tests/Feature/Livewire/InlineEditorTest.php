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
use App\Support\RoleName;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <livewire:inline-editor> — Inline-Feld-Editor (Phase 5c.1)
|--------------------------------------------------------------------------
|
| Pinnt das Verhalten der Volt-Komponente, die klassische Edit-Modale
| ersetzt: Feld-Update per wire:model.blur plus 1500ms-Debounce,
| Autorisierung via Gate::authorize('update', $project), Validation
| gegen die übergebenen Rules, dispatched Events für den Auto-Save-
| Indikator (kommt in 5c.2).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions([PermissionName::VIEW->value]);
});

it('InlineEditor rendert das aktuelle Feld-Value in einem <input>', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Ursprünglicher Titel']);

    Livewire::actingAs($owner)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'name'])
        ->assertSee('Ursprünglicher Titel', false)
        ->assertSee('wire:model.blur="value"', false);
});

it('InlineEditor mit multiline=true rendert ein <textarea>', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['description' => 'Lange Beschreibung']);

    Livewire::actingAs($owner)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'description', 'multiline' => true])
        ->assertSee('<textarea', false)
        ->assertSee('Lange Beschreibung', false);
});

it('InlineEditor persistiert Value-Änderung ins Modell', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    Livewire::actingAs($owner)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'name'])
        ->set('value', 'Neuer Titel');

    $chapter->refresh();
    expect($chapter->name)->toBe('Neuer Titel');
});

it('InlineEditor dispatched saved-Event nach erfolgreichem Update', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    Livewire::actingAs($owner)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'name'])
        ->set('value', 'Neuer Titel')
        ->assertDispatched('saved');
});

it('InlineEditor verweigert Fremd-Update via authorize', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);

    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Nicht dein Kapitel']);

    Livewire::actingAs($stranger)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'name'])
        ->set('value', 'Hijack-Versuch')
        ->assertForbidden();

    $chapter->refresh();
    expect($chapter->name)->toBe('Nicht dein Kapitel');
});

it('InlineEditor validiert gegen die übergebenen Rules', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Rules: name muss min 3 Zeichen haben — zu kurzer Wert löst
    // ValidationException aus, save-failed-Event wird dispatched
    // (für Toast in 5c.3), Feld bleibt unverändert.
    Livewire::actingAs($owner)
        ->test('inline-editor', [
            'model' => $chapter,
            'field' => 'name',
            'rules' => 'required|string|min:3',
        ])
        ->set('value', 'x')
        ->assertHasErrors('value')
        ->assertDispatched('save-failed');

    $chapter->refresh();
    expect($chapter->name)->toBe('Original');
});

it('InlineEditor rendert aria-invalid und aria-describedby im Fehler-Zustand', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Nach dem gescheiterten Update sollte das HTML `aria-invalid="true"`
    // am Input und `aria-describedby` mit der Fehler-Message-ID
    // rendern. Screen-Reader hören dann sowohl den Feld-Fehler-Status
    // als auch den konkreten Fehlertext.
    $rendered = Livewire::actingAs($owner)
        ->test('inline-editor', [
            'model' => $chapter,
            'field' => 'name',
            'rules' => 'required|string|min:3',
        ])
        ->set('value', 'x');

    $rendered->assertSee('aria-invalid="true"', false);
    $rendered->assertSee('aria-describedby="inline-editor-error-name"', false);
    $rendered->assertSee('id="inline-editor-error-name"', false);
});

it('InlineEditor mit options rendert ein <select> und die aktuellen Optionen', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Text']);

    // Chapter hat kein Native-Select-Feld; wir nutzen den Test-
    // stellvertretend für die Render-Logik. In der Praxis nutzt
    // Audiovisual den Select-Modus für type=audio|video.
    Livewire::actingAs($owner)
        ->test('inline-editor', [
            'model' => $chapter,
            'field' => 'name',
            'options' => ['Text' => 'Text', 'Bild' => 'Bild'],
        ])
        ->assertSee('<select', false)
        ->assertSee('<option value="Text"', false)
        ->assertSee('<option value="Bild"', false)
        ->assertSee('selected', false);
});

it('InlineEditor mit options persistiert Auswahl-Änderung', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Alt']);

    Livewire::actingAs($owner)
        ->test('inline-editor', [
            'model' => $chapter,
            'field' => 'name',
            'options' => ['Alt' => 'Alt', 'Neu' => 'Neu'],
        ])
        ->set('value', 'Neu')
        ->assertDispatched('saved');

    $chapter->refresh();
    expect($chapter->name)->toBe('Neu');
});

it('InlineEditor rendert kein aria-invalid im Erfolg-Zustand', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $chapter = makeChapter($project, ['name' => 'Original']);

    // Beim Initial-Render ohne Fehler-Zustand darf aria-invalid nicht
    // gerendert werden — das würde Screen-Reader falsch alarmieren.
    Livewire::actingAs($owner)
        ->test('inline-editor', ['model' => $chapter, 'field' => 'name'])
        ->assertDontSee('aria-invalid', false)
        ->assertDontSee('aria-describedby="inline-editor-error-', false);
});
