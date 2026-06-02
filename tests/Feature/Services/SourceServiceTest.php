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
use App\Services\SourceService;

/*
|--------------------------------------------------------------------------
| SourceService
|--------------------------------------------------------------------------
|
| Deckt die find-or-create-Logik ab, die vorher als
| getSource-Method-Duplikat in ProjectController und
| ContentController lebte.
*/

it('findOrCreateId legt eine neue Source an, wenn keine mit dem Namen existiert', function () {
    $service = new SourceService;

    $id = $service->findOrCreateId('Neue Quelle', 'Origin');

    $source = Source::find($id);
    expect($source)->not->toBeNull();
    expect($source->name)->toBe('Neue Quelle');
    expect($source->type)->toBe('Origin');
});

it('findOrCreateId liefert die ID einer bestehenden Source statt eine neue anzulegen', function () {
    $existing = Source::factory()->origin()->create(['name' => 'Bestehende Quelle']);

    $service = new SourceService;

    $id = $service->findOrCreateId('Bestehende Quelle', 'Origin');

    expect($id)->toBe($existing->id);
    expect(Source::where('name', 'Bestehende Quelle')->count())->toBe(1);
});

it('findOrCreateId unterscheidet zwischen Type Origin und Type Copyright', function () {
    Source::factory()->origin()->create(['name' => 'Gleiche Bezeichnung']);

    $service = new SourceService;

    $copyrightId = $service->findOrCreateId('Gleiche Bezeichnung', 'Copyright');

    $copyright = Source::find($copyrightId);
    expect($copyright->type)->toBe('Copyright');
    expect(Source::where('name', 'Gleiche Bezeichnung')->count())->toBe(2);
});
