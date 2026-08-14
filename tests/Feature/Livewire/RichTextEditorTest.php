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
| <livewire:rich-text-editor> — Quill-Inline-Editor (Phase 5c.6.c.4)
|--------------------------------------------------------------------------
|
| Pinnt das Server-Verhalten der Volt-Komponente: Autorisierung,
| Validation, Persistenz und `saved`/`save-failed`-Events. Die
| Quill-Bridge selbst (Alpine + Quill.js) läuft im Browser, dafür
| gibt es später einen E2E-Smoke — nicht hier.
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

it('rendert den aktuellen HTML-Wert für die Quill-Bridge', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText(['text' => '<p>Hallo <strong>Welt</strong></p>']));

    Livewire::actingAs($owner)
        ->test('rich-text-editor', ['model' => $text, 'field' => 'text'])
        ->assertSee('richTextEditor', false)
        ->assertSee('Hallo', false);
});

it('speichert HTML in das Modell', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText(['text' => '<p>Alt</p>']));

    Livewire::actingAs($owner)
        ->test('rich-text-editor', ['model' => $text, 'field' => 'text'])
        ->call('save', '<p>Neu <em>und</em> formatiert</p>')
        ->assertDispatched('saved');

    $text->refresh();
    expect((string) $text->text)->toBe('<p>Neu <em>und</em> formatiert</p>');
});

it('verweigert Fremd-Save via authorize', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);

    $project = makeProject($owner);
    $text = attachToProject($project, makeText(['text' => '<p>Original</p>']));

    Livewire::actingAs($stranger)
        ->test('rich-text-editor', ['model' => $text, 'field' => 'text'])
        ->call('save', '<p>Hijack</p>')
        ->assertForbidden();

    $text->refresh();
    expect((string) $text->text)->toBe('<p>Original</p>');
});

it('validiert gegen die übergebenen Rules', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText(['text' => '<p>Original</p>']));

    // Erzwingt required + min:20 — leerer Content oder ganz kurzer
    // fällt raus, `save-failed` wird dispatched, DB bleibt.
    Livewire::actingAs($owner)
        ->test('rich-text-editor', [
            'model' => $text,
            'field' => 'text',
            'rules' => 'required|string|min:20',
        ])
        ->call('save', '<p>x</p>')
        ->assertHasErrors('value')
        ->assertDispatched('save-failed');

    $text->refresh();
    expect((string) $text->text)->toBe('<p>Original</p>');
});
