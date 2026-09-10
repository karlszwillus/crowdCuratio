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

use App\Services\ProjectImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| ProjectImageService::store
|--------------------------------------------------------------------------
|
| Der Service kapselt das Logo-Upload für Project. Drei Pfade
| testen den Vertrag:
|
|   - kein Bild → null
|   - Bild → Dateiname zurück, Datei in Storage::disk('public')
|   - Dateiname folgt dem date-time-extension-Muster
*/

it('liefert null, wenn kein UploadedFile übergeben wird', function () {
    /** @var TestCase $this */
    $service = new ProjectImageService;

    $result = $service->store(null);

    expect($result)->toBeNull();
});

it('speichert das Bild auf der public-Disk und liefert den Dateinamen zurück', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $file = UploadedFile::fake()->image('logo.png');

    $service = new ProjectImageService;
    $result = $service->store($file);

    expect($result)->toBeString();
    expect($result)->toEndWith('.png');

    Storage::disk('public')->assertExists('/uploads/images/'.$result);
});

it('komponiert den Dateinamen aus Datum, Zufalls-Suffix und Original-Extension', function () {
    /** @var TestCase $this */
    Storage::fake('public');

    $file = UploadedFile::fake()->image('beispiel.jpg');

    $service = new ProjectImageService;
    $result = $service->store($file);

    // Q4-Etappe 6 (2026-09-10): Format YYYYMMDD_<random10>.jpg — kollisions-
    // sicher bei parallelem Upload, siehe ImageService::uploadImageFile.
    expect($result)->toMatch('/^\d{8}_[A-Za-z0-9]{10}\.jpg$/');
});
