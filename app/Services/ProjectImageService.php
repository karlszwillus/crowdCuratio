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

namespace App\Services;

use App\Traits\UploadTrait;
use Illuminate\Http\UploadedFile;

/**
 * Kapselt das Upload-Handling für Project-Logos.
 *
 * Erste konkrete Anwendung der ADR-0020-Service-Layer-Konvention:
 * eine Use-Case-Gruppe (Bild-Upload für Project), klare Eingabe-/
 * Ausgabe-Signatur, Constructor-Injection im Controller statt
 * `new ProjectImageService()`-Aufrufen.
 *
 * Der UploadTrait wird vorerst weiter verwendet — die Ablösung
 * durch direkte `Storage::disk('public')`-Aufrufe steht als
 * Folgeaufgabe im Phase-4-Refactor-Backlog.
 */
class ProjectImageService
{
    use UploadTrait;

    /**
     * Speichert ein Project-Logo unter `/uploads/images/` auf der
     * `public`-Disk und liefert den generierten Dateinamen zurück.
     *
     * Liefert `null`, wenn kein File übergeben wurde — der Aufrufer
     * kann dann sauber unterscheiden zwischen "kein Bild" und
     * "Bild erfolgreich gespeichert".
     */
    public function store(?UploadedFile $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $filename = date('Ymd').'_'.time().'.'.$image->extension();
        $folder = '/uploads/images/';

        $this->uploadOne($image, $folder, 'public', $filename);

        return $filename;
    }
}
