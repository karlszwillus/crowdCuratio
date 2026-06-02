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

use App\Data\TextData;
use App\Models\Comment;
use App\Models\MediaContent;
use App\Models\Text;

/**
 * Kapselt die Schreibpfade auf Text-Modelle (Block F.3).
 *
 * Übernimmt die `saveText`-/`updateText`-Logik aus dem
 * `ContentController`: Body-Bereinigung (Script-Tag-Filter),
 * Source-Lookups über den `SourceService` und beim Create das
 * `attachMedia`-Anhängen an einen Entry über `MediaContent`.
 *
 * `destroy` nutzt heute den `DB::table()`-basierten
 * Soft-Delete-Bypass auf Comment + MediaContent (NF-LAR-009).
 * F.7 wird das auf Eloquent + Soft-Delete-Trait umstellen.
 */
class TextService
{
    public function __construct(
        private readonly SourceService $sources,
    ) {}

    /**
     * Legt einen neuen Text an, hängt ihn per `MediaContent` an
     * einen Entry und liefert das Text-Modell zurück.
     */
    public function create(TextData $data, int $entryId): Text
    {
        $copyright = $this->sources->findOrCreateId($data->copyrightName, 'Copyright');
        $origin = $this->sources->findOrCreateId($data->originName, 'Origin');

        $cleanBody = $this->stripScriptTags($data->body);

        $id = Text::insertGetId([
            'text' => json_encode([app()->getLocale() => $cleanBody]),
            'origin' => $origin,
            'copyright' => $copyright,
            'created_at' => now(),
        ]);

        $this->attachToEntry($id, $entryId);

        /** @var Text $text */
        $text = Text::findOrFail($id);

        return $text;
    }

    /**
     * Aktualisiert einen bestehenden Text mit neuen Source-IDs
     * und sauberem Body.
     */
    public function update(Text $text, TextData $data): Text
    {
        $copyright = $this->sources->findOrCreateId($data->copyrightName, 'Copyright');
        $origin = $this->sources->findOrCreateId($data->originName, 'Origin');

        $text->text = $this->stripScriptTags($data->body);
        $text->origin = $origin;
        $text->copyright = $copyright;
        $text->is_translated = $data->isTranslated;
        $text->updated_at = now();
        $text->save();

        return $text;
    }

    /**
     * Soft-deleted den Text und seine zugehörigen Comment- und
     * MediaContent-Einträge. Aktuell über `DB::update`-Bypass,
     * F.7 stellt das auf Eloquent + SoftDeletes-Trait um.
     */
    public function destroy(Text $text): void
    {
        $this->detachFromEntries($text->id);

        $text->delete();
    }

    /**
     * Filter aus dem alten saveText-Body: entfernt
     * `<script>`/`</script>`-Tags aus dem User-Input.
     */
    private function stripScriptTags(string $body): string
    {
        return str_replace(['<script>', '</script>'], ['', ''], $body);
    }

    /**
     * MediaContent-Eintrag für den Text auf einen Entry. Wird vom
     * Create-Pfad genutzt — der Update-Pfad ändert die Zuordnung
     * nicht.
     */
    private function attachToEntry(int $textId, int $entryId): void
    {
        MediaContent::firstOrCreate([
            'media_content_id' => $textId,
            'media_contentable_id' => $entryId,
            'media_contentable_type' => Text::class,
        ]);
    }

    /**
     * Soft-deleted die Comment- und MediaContent-Einträge eines
     * Texts via Eloquent (NF-LAR-009-Fix: vorher
     * update(['deleted_at' => now()]) — Bypass der SoftDeletes-
     * Trait-Hooks).
     */
    private function detachFromEntries(int $textId): void
    {
        Comment::where('commentable_id', $textId)
            ->where('commentable_type', Text::class)
            ->delete();

        MediaContent::where('media_contentable_id', $textId)
            ->where('media_contentable_type', Text::class)
            ->delete();
    }
}
