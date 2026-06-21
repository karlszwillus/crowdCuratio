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

use App\Data\AudiovisualData;
use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\MediaContent;
use App\Traits\UploadTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Kapselt die Schreibpfade auf Audiovisual-Modelle (Block F.6).
 *
 * Übernimmt die `store`-/`delete`-Logik aus dem
 * `AudiovisualController`: YouTube-URL-Normalisierung in den
 * `/embed/`-Pfad, Audio-File-Upload mit Server-generiertem
 * Dateinamen (NF-SEC-201), MediaContent-Attach an Entry.
 *
 * `destroy` macht Soft-Delete via Eloquent (Audiovisual und
 * MediaContent nutzen beide SoftDeletes-Trait, kein
 * DB::table-Bypass nötig — daher kein NF-LAR-009-Issue hier).
 */
class AudiovisualService
{
    use UploadTrait;

    /**
     * Legt ein neues Audiovisual an und hängt es per MediaContent
     * an einen Entry.
     */
    public function create(AudiovisualData $data, int $entryId): Audiovisual
    {
        $audiovisual = Audiovisual::create([
            'link' => $data->link,
            'source' => $data->source,
            'copyright' => $data->copyright,
            'type' => $data->type,
        ]);

        $this->attachToEntry($audiovisual->id, $entryId);

        return $audiovisual;
    }

    /**
     * Aktualisiert ein bestehendes Audiovisual. Je nach
     * `isTranslation`-Flag im DTO werden die Felder direkt
     * gesetzt oder via setTranslation('en', ...) als englische
     * Übersetzung. Bei direktem Pfad werden nur nicht-null-Felder
     * überschrieben (bewahrt das alte saveAudiovisual-Verhalten,
     * bei dem leere Felder bestehende DB-Werte nicht löschen).
     */
    public function update(Audiovisual $audiovisual, AudiovisualData $data): Audiovisual
    {
        if ($data->isTranslation) {
            if ($data->link !== null) {
                $audiovisual->setTranslation('link', 'en', $data->link);
            }
            $audiovisual->setTranslation('copyright', 'en', $data->copyright ?? '');
            $audiovisual->setTranslation('source', 'en', $data->source ?? '');
        } else {
            if ($data->link !== null) {
                $audiovisual->link = $data->link;
            }
            if ($data->type !== null) {
                $audiovisual->type = $data->type;
            }
            if ($data->copyright !== null) {
                $audiovisual->copyright = $data->copyright;
            }
            if ($data->source !== null) {
                $audiovisual->source = $data->source;
            }
        }

        $audiovisual->is_translated = $data->isTranslated;
        $audiovisual->save();

        return $audiovisual;
    }

    /**
     * Soft-deleted das Audiovisual und seinen MediaContent-Eintrag
     * — beide über Eloquent (kein NF-LAR-009-Bypass nötig, weil
     * Audiovisual und MediaContent SoftDeletes-Trait nutzen).
     */
    public function destroy(Audiovisual $audiovisual): void
    {
        MediaContent::where('media_content_id', $audiovisual->id)
            ->where('media_contentable_type', Audiovisual::class)
            ->delete();

        $audiovisual->delete();
    }

    /**
     * Normalisiert den Link für die Speicherung:
     * - Audio-Upload: server-generierten Dateinamen liefern
     * - YouTube-URL: in den /embed/-Pfad überführen
     * - Sonstige URLs: unverändert durchlassen
     */
    public function resolveLink(?string $link, ?UploadedFile $audio = null): ?string
    {
        if ($audio !== null) {
            return $this->uploadAudio($audio);
        }

        if ($link === null) {
            return null;
        }

        $youtubeId = $this->youtubeId($link);

        if ($youtubeId !== null) {
            return 'https://www.youtube.com/embed/'.$youtubeId;
        }

        return $link;
    }

    /**
     * Audio-Upload nach `/uploads/audio/` auf der `public`-Disk.
     * Server-generierter Dateiname (NF-SEC-201) — kein
     * Client-Input im Path.
     */
    private function uploadAudio(UploadedFile $audio): string
    {
        $name = Str::random(10);
        $this->uploadOne($audio, '/uploads/audio/', 'public', $name);

        return $name;
    }

    /**
     * Extrahiert die 11-stellige YouTube-Video-ID aus einer URL.
     * Liefert null, wenn die URL nicht als YouTube-URL erkannt
     * wird.
     */
    private function youtubeId(string $url): ?string
    {
        if (strlen($url) <= 11) {
            return null;
        }

        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

        if (preg_match($pattern, $url, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /**
     * MediaContent-Eintrag für das Audiovisual auf einen Entry.
     * Position als max+1 innerhalb des Entry.
     */
    private function attachToEntry(int $audiovisualId, int $entryId): void
    {
        $lastPosition = MediaContent::where('media_contentable_id', $entryId)
            ->orderByDesc('position')
            ->value('position');

        // Phase 4 / Block E.7b Sub-Welle 2d (ADR-0022): Doppel-
        // schreibung alte + neue Morph-Spalten. Cleanup in 2/4.
        MediaContent::create([
            'position' => ($lastPosition ?? 0) + 1,
            'media_content_id' => $audiovisualId,
            'media_contentable_id' => $entryId,
            'media_contentable_type' => Audiovisual::class,
            'content_id' => $audiovisualId,
            'content_type' => Audiovisual::class,
            'parent_id' => $entryId,
            'parent_type' => Entry::class,
        ]);
    }
}
