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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| <livewire:audio-uploader> — Inline-Datei-Uploader für Audios (5c.6.c.3)
|--------------------------------------------------------------------------
|
| Zeigt bei type=audio den aktuellen Dateinamen und einen File-Input,
| über den eine neue Audiodatei hochgeladen werden kann. Uploader
| ruft AudiovisualService::resolveLink() für den server-generierten
| Dateinamen (NF-SEC-201).
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
    Role::firstOrCreate(['name' => RoleName::READER->value, 'guard_name' => 'web'])
        ->syncPermissions([PermissionName::VIEW->value]);

    Storage::fake('public');
});

it('rendert die Meta-Zeile fuer die aktuelle Audiodatei und ein file-Input', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $project = makeProject($owner);
    $audiovisual = attachToProject($project, makeAudiovisual([
        'type' => 'audio',
        'link' => 'aktuell.mp3',
    ]));

    // 5z.7: Der Uploader zeigt nicht mehr den Storage-Key, sondern eine
    // Meta-Zeile aus Format (Extension in Caps), Groesse und Upload-Datum.
    // Im Test ist die Datei nicht im Fake-Storage — Format ist trotzdem
    // aus dem Dateinamen ableitbar, also faellt "MP3" ins Markup.
    Livewire::actingAs($owner)
        ->test('audio-uploader', ['audiovisual' => $audiovisual])
        ->assertSee('MP3', false)
        ->assertSee('type="file"', false)
        ->assertSee('wire:model="file"', false);
});

it('speichert eine neue Audiodatei und dispatched saved', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $project = makeProject($owner);
    $audiovisual = attachToProject($project, makeAudiovisual([
        'type' => 'audio',
        'link' => 'alt.mp3',
    ]));

    // Fake mit korrektem MIME-Type — die Validation-Rule prüft
    // gegen mimetypes, nicht gegen Extension.
    $file = UploadedFile::fake()->create('neu.mp3', 512, 'audio/mpeg');

    Livewire::actingAs($owner)
        ->test('audio-uploader', ['audiovisual' => $audiovisual])
        ->set('file', $file)
        ->assertDispatched('saved');

    $audiovisual->refresh();
    expect($audiovisual->link)->not->toBe('alt.mp3');
    expect($audiovisual->link)->toHaveLength(10); // Str::random(10)
});

it('lehnt eine PDF-Datei ab', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $project = makeProject($owner);
    $audiovisual = attachToProject($project, makeAudiovisual([
        'type' => 'audio',
        'link' => 'stabil.mp3',
    ]));

    $file = UploadedFile::fake()->create('gefaehrlich.pdf', 100, 'application/pdf');

    Livewire::actingAs($owner)
        ->test('audio-uploader', ['audiovisual' => $audiovisual])
        ->set('file', $file)
        ->assertHasErrors('file')
        ->assertDispatched('save-failed');

    $audiovisual->refresh();
    expect($audiovisual->link)->toBe('stabil.mp3');
});

it('verweigert Fremd-Upload via authorize', function () {
    /** @var TestCase $this */
    /** @var User $stranger */
    $stranger = User::factory()->create();
    $stranger->assignRole(RoleName::READER->value);

    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);
    $project = makeProject($owner);

    $audiovisual = attachToProject($project, makeAudiovisual([
        'type' => 'audio',
        'link' => 'stabil.mp3',
    ]));

    $file = UploadedFile::fake()->create('neu.mp3', 100, 'audio/mpeg');

    Livewire::actingAs($stranger)
        ->test('audio-uploader', ['audiovisual' => $audiovisual])
        ->set('file', $file)
        ->assertForbidden();

    $audiovisual->refresh();
    expect($audiovisual->link)->toBe('stabil.mp3');
});
