<?php

use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Test-Helper für Feature-Tests, die ein realistisches
 * Curating-Setup brauchen. Liegen hier zentral, damit
 * AuthorizationTest, HappyPathTest und künftige Feature-Suites
 * sie ohne Duplizierung nutzen können.
 *
 * `makeProject` setzt `user_id` über den Property-Setter, weil
 * die Spalte nicht in `Project::$fillable` ist (F-SEC-010).
 */
function makeProject(User $owner, array $overrides = []): Project
{
    $data = array_merge([
        'name' => 'Original Name',
        'imprint' => 'Original Impressum',
        'terms' => 'Original AGB',
        'status' => 'draft',
        'description' => 'Original Beschreibung',
    ], $overrides);

    $userId = $overrides['user_id'] ?? $owner->id;
    unset($data['user_id']);

    $project = new Project;
    $project->fill($data);
    $project->user_id = $userId;
    $project->save();

    return $project;
}

function makeChapter(Project $project, array $overrides = []): Chapter
{
    return Chapter::create(array_merge([
        'project_id' => $project->id,
        'name' => 'Original Kapitel-Titel',
        'subtitle' => 'Original Untertitel',
        'description' => 'Original Beschreibung',
        'position' => 0,
    ], $overrides));
}

function makeEntry(Chapter $chapter, array $overrides = []): Entry
{
    return Entry::create(array_merge([
        'chapter_id' => $chapter->id,
        'name' => 'Original Entry-Titel',
        'subtitle' => 'Original Untertitel',
        'description' => 'Original Beschreibung',
        'position' => 0,
    ], $overrides));
}

/**
 * Test-Hilfen für die Content-Modelle (Block-F-Vorbereitung).
 *
 * Die Factories und Helper hier ermöglichen Charakterisierungs-
 * und Service-Tests für die ContentController-/AudiovisualController-
 * Schreibpfade, die vorher mangels Test-Infrastruktur nicht
 * abgedeckt werden konnten.
 *
 * Source-Helper liefert eine Source-Zeile (FK-Ziel für Text und
 * Image); per State 'Origin' oder 'Copyright'. makeText / makeImage
 * legen die nötigen Source-Zeilen implizit mit an, makeGallery /
 * makeAudiovisual sind standalone.
 */
function makeSource(array $overrides = []): Source
{
    return Source::factory()->create($overrides);
}

function makeText(array $overrides = []): Text
{
    return Text::factory()->create($overrides);
}

function makeImage(array $overrides = []): Image
{
    return Image::factory()->create($overrides);
}

function makeGallery(array $overrides = []): Gallery
{
    return Gallery::factory()->create($overrides);
}

function makeAudiovisual(array $overrides = []): Audiovisual
{
    return Audiovisual::factory()->create($overrides);
}

/**
 * Hängt ein Content-Modell (Text / Image / Audiovisual / Gallery) an
 * ein Project über die kanonische Kette Chapter → Entry → MediaContent.
 *
 * Wird von Volt-Component-Tests genutzt, deren Save-Pfad über
 * `Gate::authorize('update', $model->project())` läuft — ohne den
 * Pivot liefert `->project()` null und Gate wirft 403. Zentraler
 * Helper statt in jedem Test kopiert (5c.7 Konsolidierung).
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @param  TModel  $content
 * @return TModel
 */
function attachToProject(\App\Models\Project $project, \Illuminate\Database\Eloquent\Model $content): \Illuminate\Database\Eloquent\Model
{
    $chapter = makeChapter($project);
    $entry = makeEntry($chapter);
    \App\Models\MediaContent::create([
        'content_id' => $content->id,
        'content_type' => $content::class,
        'parent_id' => $entry->id,
        'parent_type' => \App\Models\Entry::class,
        'position' => 1,
    ]);

    return $content;
}
