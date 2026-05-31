<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

trait UploadTrait
{
    /**
     * NF-SEC-201: Disk-Whitelist. crowdCuratio kennt aktuell nur die
     * `public`-Disk für User-Uploads; jeder andere Wert wird hier
     * abgewiesen. Defensive Schicht für künftige Aufrufer — die
     * Disk-Wahl darf nie aus Request-Daten kommen.
     *
     * Den Trait selbst durch direkte `Storage::disk()`-Aufrufe zu
     * ersetzen, ist als F-LAR-015 für die Refactoring-Welle
     * vorgemerkt.
     */
    public function uploadOne(UploadedFile $uploadedFile, $folder = null, $disk = 'public', $filename = null)
    {
        if (! in_array($disk, ['public'], true)) {
            throw new \InvalidArgumentException(
                "UploadTrait::uploadOne erlaubt nur die `public`-Disk, '{$disk}' wurde übergeben."
            );
        }

        $name = ! is_null($filename) ? $filename : Str::random(25);

        return $uploadedFile->storeAs($folder, $name, $disk);
    }
}
