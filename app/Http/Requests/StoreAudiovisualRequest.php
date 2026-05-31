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

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest für `POST /save-audiovisual`
 * (AudiovisualController::store).
 *
 * NF-SEC-201 / Vor-Phase-3-Härtung. Zwei sich gegenseitig
 * ausschließende Upload-Modi:
 *
 *  - Audio-Datei-Upload: `audio` (File mit MIME-Whitelist und
 *    20-MB-Limit).
 *  - Externer Video-Embed: `link` (URL, üblicherweise YouTube,
 *    wird vom Controller in einen Embed-Link umgeschrieben).
 *
 * Beide sind nullable, weil die Route auch im Translation-Modus
 * (Update einer bestehenden Audiovisual-Ressource ohne neuen
 * Upload) angesteuert wird.
 *
 * MIME-Whitelist beschränkt sich auf die im Curating-Alltag
 * tatsächlich verwendeten Audio-Formate. Video-Dateien als
 * Direkt-Upload sind bewusst nicht erlaubt — Video-Embeds laufen
 * über den `link`-Pfad. Die 20-MB-Grenze ist eine bewusste
 * Kompromiss-Größe: groß genug für ein normales Audio-Sample,
 * klein genug, um DoS-Vektoren über große Uploads einzudämmen.
 *
 * authorize() ist hier offen — Owner-Check kommt mit Phase 4
 * (NF-LAR-010, zusammen mit den anderen Content-Block-Policies).
 */
class StoreAudiovisualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'audio' => 'sometimes|nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav,audio/ogg,audio/x-m4a|max:20480',
            'newImage' => 'sometimes|nullable|file|mimetypes:audio/mpeg,audio/mp4,audio/wav,audio/x-wav,audio/ogg,audio/x-m4a|max:20480',
            'link' => 'sometimes|nullable|string|max:2048',
        ];
    }
}
