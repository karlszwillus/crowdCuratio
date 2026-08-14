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

use App\Models\Source;
use App\Models\User;
use App\Support\PermissionName;
use App\Support\RoleName;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <livewire:source-picker> — Copyright/Quelle-Autocomplete (5c.6.c.4-Followup)
|--------------------------------------------------------------------------
|
| Pinnt Suche, Auswahl und „Neu anlegen" der inline Source-Verknüpfung.
| Ersetzt den Bootstrap-3-Typeahead im Modify-Modal.
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

it('filtert Vorschläge nach type und Substring', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    Source::create(['name' => 'Deutsches Historisches Museum', 'type' => 'Copyright', 'is_translated' => false]);
    Source::create(['name' => 'Deutsche Digitale Bibliothek', 'type' => 'Copyright', 'is_translated' => false]);
    Source::create(['name' => 'Bundesarchiv', 'type' => 'Origin', 'is_translated' => false]);

    $component = Livewire::actingAs($owner)
        ->test('source-picker', [
            'model' => $text,
            'field' => 'copyright',
            'relation' => 'copyrightText',
            'sourceType' => 'Copyright',
        ])
        ->call('startEdit')
        ->set('query', 'Deutsche');

    $names = array_column($component->get('results'), 'name');
    expect($names)->toContain('Deutsches Historisches Museum');
    expect($names)->toContain('Deutsche Digitale Bibliothek');
    expect($names)->not->toContain('Bundesarchiv');
});

it('wählt eine existierende Source und speichert die FK', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    $source = Source::create(['name' => 'DDR-Museum', 'type' => 'Copyright', 'is_translated' => false]);

    Livewire::actingAs($owner)
        ->test('source-picker', [
            'model' => $text,
            'field' => 'copyright',
            'relation' => 'copyrightText',
            'sourceType' => 'Copyright',
        ])
        ->call('selectSource', $source->id)
        ->assertDispatched('saved');

    $text->refresh();
    expect((int) $text->copyright)->toBe((int) $source->id);
});

it('legt neue Source an, wenn Query kein Match hat', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    Livewire::actingAs($owner)
        ->test('source-picker', [
            'model' => $text,
            'field' => 'copyright',
            'relation' => 'copyrightText',
            'sourceType' => 'Copyright',
        ])
        ->set('query', 'Ganz neuer Rechteinhaber')
        ->call('createAndSelect')
        ->assertDispatched('saved');

    // Source::name ist HasTranslations (JSON-Spalte), deshalb LIKE
    // gegen die JSON-Repräsentation — nicht Gleichheit auf String.
    $created = Source::where('name', 'like', '%Ganz neuer Rechteinhaber%')
        ->where('type', 'Copyright')
        ->first();
    expect($created)->not->toBeNull();

    $text->refresh();
    expect((int) $text->copyright)->toBe((int) $created->id);
});

it('erzeugt kein Duplikat bei case-insensitivem Match', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);
    $text = attachToProject($project, makeText());

    $existing = Source::create(['name' => 'Bundesarchiv', 'type' => 'Copyright', 'is_translated' => false]);

    Livewire::actingAs($owner)
        ->test('source-picker', [
            'model' => $text,
            'field' => 'copyright',
            'relation' => 'copyrightText',
            'sourceType' => 'Copyright',
        ])
        ->set('query', 'bundesarchiv')
        ->call('createAndSelect');

    // Die Text-Factory legt beim Anlegen von $text bereits zwei
    // eigene Source-Zeilen an (Origin + Copyright), deshalb hier
    // nur auf den namensgleichen Bundesarchiv-Kandidat prüfen —
    // der darf durch createAndSelect nicht doppelt erzeugt werden.
    $bundesarchivCount = Source::where('type', 'Copyright')
        ->where('name', 'like', '%Bundesarchiv%')
        ->count();
    expect($bundesarchivCount)->toBe(1);

    $text->refresh();
    expect((int) $text->copyright)->toBe((int) $existing->id);
});

it('verweigert Fremd-Update via authorize', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::READER->value);
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);

    $project = makeProject($owner);
    $text = attachToProject($project, makeText());
    $source = Source::create(['name' => 'Fremde Quelle', 'type' => 'Copyright', 'is_translated' => false]);

    Livewire::actingAs($stranger)
        ->test('source-picker', [
            'model' => $text,
            'field' => 'copyright',
            'relation' => 'copyrightText',
            'sourceType' => 'Copyright',
        ])
        ->call('selectSource', $source->id)
        ->assertForbidden();

    $text->refresh();
    expect((int) $text->copyright)->not->toBe((int) $source->id);
});
