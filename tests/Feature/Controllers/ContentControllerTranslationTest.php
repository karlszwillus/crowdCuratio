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

use App\Http\Controllers\ContentController;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use App\Services\SourceService;
use App\Support\PermissionName;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ContentController — Translation-Pfade
|--------------------------------------------------------------------------
|
| Deckt die zwei Translation-Helper translateField (Source-en-
| Übersetzung) und saveTranslatedText (Text-Body-en-Übersetzung)
| ab. Beide werden von saveText im Translation-Modus aufgerufen
| und sind heute ungetested — Translation-Refactor steht in
| einem späteren Block aus, bis dahin sichert dieser Test den
| Vertrag.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('translateField schreibt en-Übersetzung auf das Source-name-Feld', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    // Source via Service anlegen (translatable-konform).
    $sourceService = new SourceService;
    $sourceId = $sourceService->findOrCreateId('DE-Quelle', 'Origin');

    /** @var ContentController $controller */
    $controller = app(ContentController::class);

    // Vorsicht: die Methode-Signatur translateField($id, $field,
    // $translated) ist parameter-name-irreführend — `$field` ist
    // der EN-Value, das Ziel-Modell-Feld ist hartkodiert auf
    // `name`. `$translated` ist das is_translated-Flag (truthy/
    // falsy). Wird im Service-Refactor späterer Block geradezogen.
    $controller->translateField($sourceId, 'EN-Quelle', true);

    $source = Source::findOrFail($sourceId);

    expect($source->getTranslation('name', 'en'))->toBe('EN-Quelle');
    expect($source->getTranslation('name', 'de'))->toBe('DE-Quelle');
    expect((bool) $source->is_translated)->toBeTrue();
});

it('saveTranslatedText schreibt en-Übersetzung auf den Text-Body', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $text = makeText(['text' => json_encode(['de' => 'DE-Body'])]);

    $request = new Request;
    $request->merge([
        'textId' => $text->id,
        'text' => '<p>EN-Body</p>',
        'isTranslated' => true,
    ]);

    /** @var ContentController $controller */
    $controller = app(ContentController::class);

    $controller->saveTranslatedText($request);

    $text->refresh();

    expect($text->getTranslation('text', 'en'))->toContain('EN-Body');
    expect($text->getTranslation('text', 'de'))->toBe('DE-Body');
    expect((bool) $text->is_translated)->toBeTrue();
});

it('saveTranslatedText überspringt en-Update beim "undefined"-Sentinel', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $text = makeText();
    $text->setTranslation('text', 'en', 'Bestehende EN-Übersetzung');
    $text->save();

    $request = new Request;
    $request->merge([
        'textId' => $text->id,
        'text' => 'undefined',
        'isTranslated' => true,
    ]);

    /** @var ContentController $controller */
    $controller = app(ContentController::class);

    $controller->saveTranslatedText($request);

    $text->refresh();

    // 'undefined'-Sentinel → bestehende EN-Übersetzung bleibt unangetastet.
    expect($text->getTranslation('text', 'en'))->toBe('Bestehende EN-Übersetzung');
});

it('saveTranslatedText filtert script-Tags aus dem EN-Body', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole('Admin');
    $this->actingAs($owner);

    $text = makeText();

    $request = new Request;
    $request->merge([
        'textId' => $text->id,
        'text' => '<p>Hallo</p><script>alert(1)</script>',
        'isTranslated' => true,
    ]);

    /** @var ContentController $controller */
    $controller = app(ContentController::class);

    $controller->saveTranslatedText($request);

    $text->refresh();

    expect($text->getTranslation('text', 'en'))->not->toContain('<script>');
    expect($text->getTranslation('text', 'en'))->not->toContain('</script>');
    expect($text->getTranslation('text', 'en'))->toContain('Hallo');
});
