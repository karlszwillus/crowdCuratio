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

use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 / Block E.7b Sub-Welle 2a — Schema-Refactor für
 * `media_content`. Siehe ADR-0022.
 *
 * Hintergrund: Die bestehenden Pivot-Spalten heißen
 * `media_content_id` (Content-FK), `media_contentable_id`
 * (Parent-FK) und `media_contentable_type` (eine Tag-Spalte mit
 * der Content-Klasse, NICHT mit dem Parent-Typ wie ein Laravel-
 * `morphTo` annehmen würde). Folge: Eingebaute Polymorphic-
 * Helfer greifen nicht richtig, und `GalleryService::attachToEntry`
 * speichert historisch `Image::class` statt `Gallery::class` —
 * der korrespondierende `detachFromEntries`-Cleanup matcht das nie.
 *
 * Diese Migration legt vier neue Spalten an und befüllt sie aus
 * den Bestandsdaten. Die alten Spalten bleiben mit gleichem Inhalt
 * stehen, damit Schreibpfade während der Übergangswelle weiter
 * funktionieren. Cleanup (Drop der alten Spalten) erfolgt in
 * Sub-Welle 4 nach Smoke + Service-Umstellung.
 *
 * Neue Spalten:
 *   - content_id      : ID des Content-Modells (Text/Image/Gallery/Audiovisual)
 *   - content_type    : Klasse des Content-Modells
 *                       (Mapping aus media_contentable_type, mit
 *                        Spezialfall für Image::class-Tags, die
 *                        auf existierende galleries-Rows zeigen —
 *                        die werden auf Gallery::class umgesetzt)
 *   - parent_id       : ID des Parents (heute Entry)
 *   - parent_type     : Klasse des Parents — `Entry::class` für
 *                       alle Bestands-Rows laut Audit
 *                       (db:audit-media-content / Parent-Probe)
 *
 * Idempotenz: prüft auf Existenz der neuen Spalten, befüllt nur
 * Rows, deren `content_id` noch NULL ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_content')) {
            return;
        }

        // Spalten anlegen, wenn nicht schon da. Nullable, damit das
        // ALTER bei vorhandenen Rows nicht crasht — die Befüllung
        // läuft direkt danach im selben Migrations-Lauf.
        if (! Schema::hasColumn('media_content', 'content_id')) {
            Schema::table('media_content', function (Blueprint $table) {
                $table->unsignedInteger('content_id')->nullable()->after('position');
                $table->string('content_type')->nullable()->after('content_id');
                $table->unsignedInteger('parent_id')->nullable()->after('content_type');
                $table->string('parent_type')->nullable()->after('parent_id');

                $table->index(['content_id', 'content_type'], 'media_content_content_morph_idx');
                $table->index(['parent_id', 'parent_type'], 'media_content_parent_morph_idx');
            });
        }

        $this->backfillNewColumns();
    }

    public function down(): void
    {
        if (! Schema::hasTable('media_content')) {
            return;
        }

        if (Schema::hasColumn('media_content', 'content_id')) {
            Schema::table('media_content', function (Blueprint $table) {
                $table->dropIndex('media_content_content_morph_idx');
                $table->dropIndex('media_content_parent_morph_idx');
                $table->dropColumn(['content_id', 'content_type', 'parent_id', 'parent_type']);
            });
        }
    }

    /**
     * Befüllt content_id, content_type, parent_id, parent_type
     * aus den bestehenden Spalten. Mapping:
     *
     *   content_id   ← media_content_id
     *   parent_id    ← media_contentable_id
     *   parent_type  ← Entry::class (laut Audit-Parent-Probe)
     *
     *   content_type ← Spezialfall: Image::class-Tags, deren
     *                  media_content_id eine bestehende Gallery
     *                  trifft, werden auf Gallery::class
     *                  umgesetzt (historischer Schiefstand aus
     *                  GalleryService::attachToEntry). Sonst 1:1
     *                  aus media_contentable_type.
     *
     * Idempotent: nur Rows mit NULL content_id werden geschrieben.
     */
    private function backfillNewColumns(): void
    {
        $galleryIds = DB::table('galleries')->pluck('id')->all();
        $imageClass = Image::class;
        $galleryClass = Gallery::class;

        DB::table('media_content')
            ->whereNull('content_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($galleryIds, $imageClass, $galleryClass) {
                foreach ($rows as $row) {
                    $oldType = $row->media_contentable_type;

                    $contentType = $oldType;
                    if ($oldType === $imageClass && in_array($row->media_content_id, $galleryIds, true)) {
                        $contentType = $galleryClass;
                    }

                    DB::table('media_content')
                        ->where('id', $row->id)
                        ->update([
                            'content_id' => $row->media_content_id,
                            'content_type' => $contentType,
                            'parent_id' => $row->media_contentable_id,
                            'parent_type' => Entry::class,
                        ]);
                }
            });
    }
};
