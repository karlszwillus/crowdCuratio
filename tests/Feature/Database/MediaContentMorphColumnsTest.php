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

use App\Models\Audiovisual;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Text;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| media_content — Morph-Columns (E.7b Sub-Welle 2a, ADR-0022)
|--------------------------------------------------------------------------
|
| Pinnt die Schema-Veränderung und das Backfill-Mapping. Insbesondere
| den historischen Spezialfall: GalleryService hat Image::class als
| media_contentable_type geschrieben (statt Gallery::class) — die
| Migration setzt das beim Mapping auf Gallery::class um, wenn die
| media_content_id auf eine bestehende galleries-Row zeigt.
*/

const MIGRATION_PATH = 'database/migrations/2026_06_20_180000_add_morph_columns_to_media_content.php';

it('Endzustand: neue Spalten content_id, content_type, parent_id, parent_type existieren', function () {
    /** @var TestCase $this */
    expect(Schema::hasColumns('media_content', [
        'content_id',
        'content_type',
        'parent_id',
        'parent_type',
    ]))->toBeTrue();
});

it('Backfill: Text-Pivot wird auf content_type = Text::class gemappt', function () {
    /** @var TestCase $this */
    Artisan::call('migrate:rollback', ['--path' => MIGRATION_PATH]);

    $text = Text::factory()->create();
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    DB::table('media_content')->insert([
        'media_content_id' => $text->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Text::class,
        'position' => 0,
    ]);

    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    $row = DB::table('media_content')
        ->where('media_content_id', $text->id)
        ->where('media_contentable_id', $entry->id)
        ->first();

    expect($row->content_id)->toBe($text->id);
    expect($row->content_type)->toBe(Text::class);
    expect($row->parent_id)->toBe($entry->id);
    expect($row->parent_type)->toBe(Entry::class);
});

it('Backfill: Image::class-Tag mit Match in galleries wird zu Gallery::class umgesetzt', function () {
    /** @var TestCase $this */
    Artisan::call('migrate:rollback', ['--path' => MIGRATION_PATH]);

    $gallery = Gallery::factory()->create();
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    // Historischer Schiefstand: GalleryService schrieb Image::class
    // als type, media_content_id zeigt aber auf eine Gallery-Row.
    DB::table('media_content')->insert([
        'media_content_id' => $gallery->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Image::class,
        'position' => 0,
    ]);

    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    $row = DB::table('media_content')
        ->where('media_content_id', $gallery->id)
        ->where('media_contentable_id', $entry->id)
        ->first();

    expect($row->content_id)->toBe($gallery->id);
    expect($row->content_type)->toBe(Gallery::class);
    expect($row->parent_type)->toBe(Entry::class);
});

it('Backfill: Audiovisual-Pivot wird 1:1 nach content_type = Audiovisual::class gemappt', function () {
    /** @var TestCase $this */
    Artisan::call('migrate:rollback', ['--path' => MIGRATION_PATH]);

    $av = Audiovisual::factory()->create();
    /** @var User $owner */
    $owner = User::factory()->create();
    $entry = makeEntry(makeChapter(makeProject($owner)));

    DB::table('media_content')->insert([
        'media_content_id' => $av->id,
        'media_contentable_id' => $entry->id,
        'media_contentable_type' => Audiovisual::class,
        'position' => 0,
    ]);

    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    $row = DB::table('media_content')
        ->where('media_content_id', $av->id)
        ->where('media_contentable_id', $entry->id)
        ->first();

    expect($row->content_type)->toBe(Audiovisual::class);
});

it('Roundtrip: down() entfernt die Spalten, up() legt sie wieder an', function () {
    /** @var TestCase $this */
    expect(Schema::hasColumn('media_content', 'content_id'))->toBeTrue();

    Artisan::call('migrate:rollback', ['--path' => MIGRATION_PATH]);

    expect(Schema::hasColumn('media_content', 'content_id'))->toBeFalse();
    expect(Schema::hasColumn('media_content', 'content_type'))->toBeFalse();
    expect(Schema::hasColumn('media_content', 'parent_id'))->toBeFalse();
    expect(Schema::hasColumn('media_content', 'parent_type'))->toBeFalse();

    Artisan::call('migrate', ['--path' => MIGRATION_PATH]);

    expect(Schema::hasColumn('media_content', 'content_id'))->toBeTrue();
    expect(Schema::hasColumn('media_content', 'content_type'))->toBeTrue();
    expect(Schema::hasColumn('media_content', 'parent_id'))->toBeTrue();
    expect(Schema::hasColumn('media_content', 'parent_type'))->toBeTrue();
});
