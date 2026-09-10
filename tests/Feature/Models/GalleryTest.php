<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

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

use App\Models\Gallery;

/*
|--------------------------------------------------------------------------
| Gallery-Model
|--------------------------------------------------------------------------
|
| Q4-Etappe 6 · G6-1: `sequence`-Feld als redaktionelle Ansage,
| dass die Bilder als Pager-Bühne statt als Kontaktbogen gerendert
| werden sollen. Cast als bool, Default false.
*/

it('sequence ist per Default false', function () {
    $gallery = makeGallery();
    $gallery->refresh();

    expect($gallery->sequence)->toBeFalse();
});

it('sequence castet als bool', function () {
    $gallery = makeGallery(['sequence' => 1]);
    $gallery->refresh();

    expect($gallery->sequence)->toBeBool()->toBeTrue();
});

it('sequence ist massen-zuweisbar', function () {
    $gallery = Gallery::create([
        'title' => 'Serie',
        'sequence' => true,
    ]);

    expect($gallery->sequence)->toBeTrue();
});
