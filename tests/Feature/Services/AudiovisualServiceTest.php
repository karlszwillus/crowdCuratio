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

use App\Data\AudiovisualData;
use App\Models\Audiovisual;
use App\Models\MediaContent;
use App\Models\User;
use App\Services\AudiovisualService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| AudiovisualService
|--------------------------------------------------------------------------
|
| Deckt die vier Schreibpfade ab: create + MediaContent-Attach,
| update mit Translation-Verzweigung, destroy (Eloquent-Soft-
| Delete), resolveLink (YouTube-Konversion + Audio-Upload).
*/

beforeEach(function () {
    Storage::fake('public');
    $this->service = new AudiovisualService;
});

it('resolveLink wandelt eine YouTube-URL in den embed-Pfad', function () {
    $link = $this->service->resolveLink('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    expect($link)->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

it('resolveLink lädt Audio hoch und liefert den generierten Dateinamen', function () {
    $file = UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg');

    $link = $this->service->resolveLink(null, $file);

    expect($link)->toBeString();
    expect(strlen($link))->toBe(10); // Str::random(10)
    Storage::disk('public')->assertExists('/uploads/audio/'.$link);
});

it('resolveLink lässt andere URLs unverändert durch', function () {
    $link = $this->service->resolveLink('https://example.com/audio.mp3');

    expect($link)->toBe('https://example.com/audio.mp3');
});

it('create legt Audiovisual mit MediaContent-Attach an Entry an', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new AudiovisualData(
        link: 'https://www.youtube.com/embed/abc12345678',
        source: 'Test-Quelle',
        copyright: 'Test-Copyright',
        type: 'video',
    );

    $av = $this->service->create($data, $entry->id);

    expect($av->id)->toBeInt();
    expect($av->link)->toBe('https://www.youtube.com/embed/abc12345678');

    $media = MediaContent::where('media_content_id', $av->id)
        ->where('media_contentable_id', $entry->id)
        ->where('media_contentable_type', Audiovisual::class)
        ->first();
    expect($media)->not->toBeNull();
});

it('update im direkten Pfad aktualisiert nicht-null-Felder', function () {
    $av = makeAudiovisual(['link' => 'alter-link', 'source' => 'Alte Quelle']);

    $data = new AudiovisualData(
        link: 'neuer-link',
        source: 'Neue Quelle',
        copyright: 'Neues Copyright',
        type: 'audio',
    );

    $this->service->update($av, $data);
    $av->refresh();

    expect($av->link)->toBe('neuer-link');
    expect($av->source)->toBe('Neue Quelle');
    expect($av->copyright)->toBe('Neues Copyright');
    expect($av->type)->toBe('audio');
});

it('update im Translation-Pfad schreibt en-Übersetzungen', function () {
    $av = makeAudiovisual(['link' => 'DE-link', 'source' => 'DE-Quelle']);

    $data = new AudiovisualData(
        link: 'EN-link',
        source: 'EN-Quelle',
        copyright: 'EN-Copyright',
        isTranslation: true,
        isTranslated: true,
    );

    $this->service->update($av, $data);
    $av->refresh();

    expect($av->getTranslation('link', 'en'))->toBe('EN-link');
    expect($av->getTranslation('source', 'en'))->toBe('EN-Quelle');
    expect($av->getTranslation('copyright', 'en'))->toBe('EN-Copyright');
    expect($av->getTranslation('link', 'de'))->toBe('DE-link');
    expect((bool) $av->is_translated)->toBeTrue();
});

it('destroy soft-deleted Audiovisual und seinen MediaContent', function () {
    /** @var User $owner */
    $owner = User::factory()->create();
    $project = makeProject($owner);
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);

    $data = new AudiovisualData(link: 'X', source: 'O', copyright: 'C', type: 'video');
    $av = $this->service->create($data, $entry->id);

    $this->service->destroy($av);

    expect(Audiovisual::find($av->id))->toBeNull();
    expect(Audiovisual::withTrashed()->find($av->id))->not->toBeNull();
    $media = MediaContent::where('media_content_id', $av->id)->first();
    expect($media)->toBeNull(); // soft-deleted via Eloquent
});
