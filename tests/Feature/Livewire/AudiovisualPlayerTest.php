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
| <livewire:audiovisual-player> — Audio/Video-Player (Phase 5c.6.c.3)
|--------------------------------------------------------------------------
|
| Pinnt das Verhalten der Volt-Komponente, die den Player rendert:
| audio-Tag bei type=audio, iframe bei type=video. Reagiert auf das
| `saved`-Event des inline-editor, wenn ein Audiovisual-Feld
| geändert wurde, indem der Player die Datenbank frisch lädt.
*/

beforeEach(function () {
    foreach (PermissionName::all() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => RoleName::ADMIN->value, 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

it('rendert ein <audio>-Element bei type=audio', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $audiovisual = makeAudiovisual([
        'type' => 'audio',
        'link' => 'sample.mp3',
    ]);

    Livewire::actingAs($owner)
        ->test('audiovisual-player', ['audiovisual' => $audiovisual])
        ->assertSee('<audio', false)
        ->assertSee('controls', false)
        ->assertDontSee('<iframe', false);
});

it('rendert ein <iframe> bei type=video', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $audiovisual = makeAudiovisual([
        'type' => 'video',
        'link' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    ]);

    // 5z.7/5z.8: Der Video-Player rendert jetzt einen Plyr-Container mit
    // `data-plyr-provider="youtube"` und der extrahierten YouTube-ID —
    // Zwei-Klick-Einbettung, das <iframe> laedt Plyr erst nach dem ersten
    // Play-Klick nach.
    Livewire::actingAs($owner)
        ->test('audiovisual-player', ['audiovisual' => $audiovisual])
        ->assertSee('data-plyr-provider="youtube"', false)
        ->assertSee('data-plyr-embed-id="dQw4w9WgXcQ"', false)
        ->assertDontSee('<audio', false);
});

it('lädt das Audiovisual frisch, wenn ein saved-Event für das eigene Modell kommt', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $audiovisual = makeAudiovisual([
        'type' => 'audio',
        'link' => 'alt.mp3',
    ]);

    // Simuliert den Fall: der Inline-Editor für `type` speichert und
    // dispatched `saved`. Der Player muss danach seine DB-Kopie
    // aktualisieren — hier ändern wir den Type in der DB und
    // dispatchen das Event dann direkt, wie es der Editor täte.
    $audiovisual->update(['type' => 'video', 'link' => 'https://player.vimeo.com/video/42']);

    // 5z.8 § 5: Vimeo-Links sind fuer den Plyr-YouTube-Provider nicht
    // einbettbar — der Player rendert den 16:9-Fehler-Panel mit dem
    // Link im Body. Wir pruefen nur das Rendering, nicht das iframe.
    Livewire::actingAs($owner)
        ->test('audiovisual-player', ['audiovisual' => $audiovisual->fresh()])
        ->call('refreshFromSave', field: 'type', model: 'Audiovisual', id: $audiovisual->id)
        ->assertSee('https://player.vimeo.com/video/42', false);
});

it('ignoriert saved-Events für andere Modelle', function () {
    /** @var TestCase $this */
    /** @var User $owner */
    $owner = User::factory()->create();
    $owner->assignRole(RoleName::ADMIN->value);

    $audiovisual = makeAudiovisual([
        'type' => 'audio',
        'link' => 'stabil.mp3',
    ]);

    // Chapter-Save darf den Player nicht zum Reload triggern —
    // der Reload verursacht sonst bei jedem Editor-Speichern
    // unnötig eine SQL-Query pro Player auf der Seite.
    Livewire::actingAs($owner)
        ->test('audiovisual-player', ['audiovisual' => $audiovisual])
        ->call('refreshFromSave', field: 'name', model: 'Chapter', id: 999)
        ->assertSee('stabil.mp3', false)
        ->assertSee('<audio', false);
});
