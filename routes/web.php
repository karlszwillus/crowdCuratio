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
use App\Http\Controllers\AudiovisualController;
use App\Http\Controllers\Auth\MyWelcomeController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\ContentCommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataFactBlockController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\GalleryBlockController;
use App\Http\Controllers\ImageBlockController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectPermissionController;
use App\Http\Controllers\ProjectPreviewController;
use App\Http\Controllers\ProjectTranslationController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QuoteBlockController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TextBlockController;
use App\Http\Controllers\UserController;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\WelcomeNotification\WelcomesNewUsers;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get(
    '/',
    function () {
        return redirect('/login');
    }
);

Route::group(
    ['middleware' => ['web', WelcomesNewUsers::class/* ProtectAgainstSpam::class, */]],
    function () {
        Route::get('welcome/{user}', [MyWelcomeController::class, 'showWelcomeForm'])->name(
            'welcome'
        );
        Route::post('welcome/{user}', [MyWelcomeController::class, 'savePassword']);
    }
);

Route::group(
    // Block D / D.3: 'admin'-Alias entfernt, role:Admin direkt
    // — der Settings-Bereich war hier vergessen worden, weil
    // er routenseitig (und nicht controllerseitig) geschützt wird.
    ['middleware' => ['auth', 'role:Admin']],
    function () {
        Route::resource('/settings', SettingController::class);
        // Q4-Etappe 3 / C0-8b (2026-09-07): GUI-Wrapper fuer den
        // Migrations-Assistenten (`SourceMigrationService`), analog
        // zum Artisan-Command `sources:migrate`. Admin-only.
        Route::view('/admin/sources/migration', 'admin.sources.migration')
            ->name('admin.sources.migration');
    }
);

// Phase 5e.1: Dashboard-Sicht (Screen 09) mit vier Sektionen
// (Wiederaufnahme, Meine Projekte, Mir zugeteilt, Letzte
// Kommentare). Data-Loading im DashboardController.
Route::get(
    '/dashboard',
    [DashboardController::class, '__invoke']
)->middleware(['auth'])->name('dashboard');

Route::get('auth.policy', [PublicController::class, 'projectPolicy'])->name('auth.policy');
Route::get('auth.terms', [PublicController::class, 'projectTerms'])->name('auth.terms');

Route::group(
    ['middleware' => ['auth']],
    function () {
        // Q4-Etappe 8 · E3d (2026-09-12): Sub-Route mit zwei Segmenten
        // MUSS vor Route::resource('/projects') stehen — Route::resource
        // registriert u.a. `DELETE /projects/{project}` mit Wildcard,
        // sonst greift der Router die Sub-Route nicht mehr sauber
        // (matcht Zweit-Segment nicht, faellt aber auf 404 statt weiter
        // zu wandern). Namen bleiben unveraendert.
        Route::delete('/projects/{projectId}/users/{userId}', [ProjectPermissionController::class, 'deleteUserFromProject'])->name(
            'project.user_delete'
        );
        Route::resource('/projects', ProjectController::class);
        // Phase 5d.4: Berechtigungssicht (Screen 3B). Loest die alte
        // Modal-Kaskade aus projects/create ab.
        Route::get('/projects/{project}/permissions', [ProjectController::class, 'permissions'])
            ->name('projects.permissions');
        // Q4-Etappe 3 / C0d (2026-09-07): Quellenverwaltung pro Projekt.
        // Volt-Component `project-sources` gated per Route-Middleware
        // `auth` und intern in mount() ueber `authorize('update', $project)`.
        Route::get('/projects/{project}/sources', function (Project $project) {
            Gate::authorize('update', $project);

            return view('projects.sources', compact('project'));
        })->name('projects.sources');
        Route::post('/projects/{project}/metadata/adopt-system-text',
            [ProjectController::class, 'adoptSystemLegalText'])
            ->name('projects.metadata.adopt_system_text');
        Route::resource('/roles', RoleController::class);
        Route::resource('/chapters', ChapterController::class);
        Route::resource('/entries', EntryController::class);
        // Route::resource('/contents', \App\Http\Controllers\ContentController::class);
        Route::post('/texts', [TextBlockController::class, 'saveText'])->name('text.store');
        Route::get('/texts/{id}/edit', [TextBlockController::class, 'editText'])->name('text.edit');
        Route::delete('/texts/{id}', [TextBlockController::class, 'destroyText'])->name(
            'text.delete'
        );

        // Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block-Endpunkte.
        // Anlegen läuft über den ContentInsertionService (Inline-
        // Add-Bar), Speichern läuft inline über rich-text-editor /
        // inline-editor / source-picker. Direkt-Endpunkte gibt es
        // nur für Delete und das Kind-Dropdown.
        Route::delete('/quotes/{id}', [QuoteBlockController::class, 'destroy'])
            ->name('quote.delete');

        // Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block-Endpunkte.
        // Anlegen über ContentInsertionService, Bearbeiten inline
        // via inline-editor + data-facts-rows-editor. Nur Delete
        // klassisch.
        Route::delete('/data-facts/{id}', [DataFactBlockController::class, 'destroy'])
            ->name('data-facts.delete');
        Route::post('/check/email', [ProjectPermissionController::class, 'checkEmail'])->name('check.email');
        // Q3-Härtung F2 (2026-08-19) / SEC-02: vorher GET ohne Auth-Guard,
        // jeder eingeloggte User konnte fuer beliebige User-IDs eine
        // Welcome-Mail ausloesen und welcome_valid_until verlaengern
        // (CSRF-frei durch GET). Jetzt POST + throttle + Admin-Guard im
        // Controller.
        Route::post(
            '/users/{id}/actions/resend-invitation',
            [UserController::class, 'resendInvitation']
        )->middleware('throttle:6,1')->name(
            'resend.invitation'
        );
        Route::post('/images', [ImageBlockController::class, 'saveImage'])->name('image.store');
        // Bild-Sortierung + Drop wandern mit I11 (2026-09-12) unter das
        // `/api/internal/`-Prefix — Definition oben im API-Group.
        Route::get('/images/{id}/edit', [ImageBlockController::class, 'editImage'])->name(
            'image.edit'
        );
        Route::delete('/images/{id}', [ImageBlockController::class, 'destroyImage'])->name(
            'image.delete'
        );
        // B12 (2026-08-20): User-Anlage laeuft jetzt ueber
        // `UserController::create`/`store` unter `/users/create` und
        // `POST /users`. Die alten Register-Pfade bleiben als 301-
        // Redirects eine Release-Iteration lang stehen, damit
        // Bookmarks und externe Verweise nicht broecheln — analog
        // ADR-0030 (URL-Konvention Plural).
        //
        // Auth-Schicht: `Route::resource('/users', UserController::class)`
        // greift, weil `UserController` per Constructor-Middleware
        // `role:Admin` auf create/store/index/edit/update/destroy hat
        // (Defense-in-depth zur `RegisterRequest::authorize()`-Pruefung
        // und zum Admin-Gate im UserOnboardingService).
        // GET-Redirect fuer bestehende Bookmarks — Release-Iteration
        // lang, dann weg. POST braucht keine Rueckwaertskompat, weil
        // kein Bookmark POSTet — die alte /register-POST-Route ist
        // ersatzlos entfallen (siehe B12-Migration in CHANGELOG).
        Route::redirect('/register', '/users/create', 301)->name('register');
        Route::resource('/users', UserController::class);
        // Q4-Etappe 1 / I8 (2026-08-27): Self-Service-Endpunkte
        // wandern auf `ProfileController`. Route-Namen bleiben, damit
        // alle Blade-`route()`-Aufrufe unveraendert weiterlaufen.
        Route::get('/profile', [ProfileController::class, 'profile'])->name(
            'profile'
        );
        // Block E / Welle E.3: Self-Edit-Pfad eigene Route mit eigenem
        // FormRequest (UpdateOwnProfileRequest). Target ist immer
        // auth()->user(), kein {user}-Param nötig.
        Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name(
            'profile.update'
        );
        // Q4-Etappe 2 / I11 (2026-08-27): Interne AJAX-Endpunkte
        // (JSON in / JSON out) unter dem Prefix `/api/internal/`
        // gebuendelt. Route-Namen bleiben unveraendert — Blade-`route()`-
        // Aufrufer generieren automatisch die neue URL. Weichenstellung
        // fuer eine spaetere Phase-6-`/api/v1/`-Struktur mit
        // ApiResource-Transformern und Versioning.
        // Q4-Etappe 8 · I11 (2026-09-12): Alle internen JSON-Endpunkte
        // gebuendelt unter dem Prefix `/api/internal/`. Namen bleiben
        // unveraendert, damit route()-Aufrufer transparent die neuen
        // URLs erzeugen. Drag-Reorder + Gallery-Reorder/Drop wandern
        // hier hinein — sie liefern JsonResponse und gehoerten
        // konzeptuell schon immer dorthin.
        Route::prefix('api/internal')->group(function () {
            Route::post('/profile/locale', [ProfileController::class, 'updateLocale'])->name('profile.locale');
            Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
            Route::post('/profile/check-initials', [ProfileController::class, 'checkInitials'])->name('profile.check_initials');

            // Reorder (Drag&Drop-Ende). Throttle greift Tastatur-
            // Spam-Faelle mit vielen Einzel-Updates.
            Route::post('/reorder', [ChapterController::class, 'saveDragAndDrop'])
                ->middleware('throttle:60,1')
                ->name('chapter.drag');

            // Gallery-Bilder: Sortierung + Multi-Drop.
            Route::post('/galleries/{gallery}/images/reorder', [GalleryBlockController::class, 'reorderImages'])
                ->name('gallery.images.reorder');
            Route::post('/galleries/{gallery}/images/drop', [GalleryBlockController::class, 'dropImage'])
                ->name('gallery.images.drop');
        });
        // Phase 5ac.4: eigener Save fuer Passwort-Wechsel.
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        // B2 (2026-08-21) / DSGVO: Konto-Loeschung mit 30-Tage-Frist.
        Route::post('/profile/schedule-deletion', [ProfileController::class, 'scheduleDeletion'])->name('profile.schedule_deletion');
        Route::post('/profile/cancel-deletion', [ProfileController::class, 'cancelScheduledDeletion'])->name('profile.cancel_deletion');
        Route::get('/users/{id}/permissions', [ProjectPermissionController::class, 'givePermissionToUser'])->name(
            'permission.project'
        );
        Route::post('/comments/chapter', [ChapterController::class, 'commentChapter'])->name(
            'comments.chapter'
        );
        // Drag&Drop-Reorder wandert mit I11 (2026-09-12) unter das
        // `/api/internal/`-Prefix — Definition oben im API-Group.
        Route::get(
            '/comments/chapter/{id}/',
            [ChapterController::class, 'getChapterComment']
        )->name(
            'comments.chapter.show'
        );
        Route::post(
            '/comments/chapter/{id}/save',
            [ChapterController::class, 'saveComment']
        )->name(
            'comments.chapter.save'
        );
        Route::post('/comments/entry', [EntryController::class, 'commentEntry'])->name(
            'comments.entry'
        );
        Route::post(
            '/comments/chapter/status',
            [ChapterController::class, 'setCommentStatusChapter']
        )->name(
            'comments.chapter.status'
        );
        Route::get('/comments/entry/{id}/', [EntryController::class, 'getEntryComment'])->name(
            'comments.entry.show'
        );
        Route::post(
            '/comments/entry/{id}/save',
            [EntryController::class, 'saveCommentEntry']
        )->name(
            'comments.entry.save'
        );
        Route::post(
            '/comments/entry/status',
            [EntryController::class, 'setCommentStatusEntry']
        )->name(
            'comments.entry.status'
        );
        Route::post('/comments/text', [ContentCommentController::class, 'commentText'])->name(
            'comments.text'
        );
        Route::get('/comments/text/{id}/', [ContentCommentController::class, 'getTextComment'])->name(
            'comments.text.show'
        );
        Route::post(
            '/comments/text/{id}/save',
            [ContentCommentController::class, 'saveCommentText']
        )->name(
            'comments.text.save'
        );
        Route::post(
            '/comments/text/status',
            [ContentCommentController::class, 'setCommentStatusText']
        )->name(
            'comments.text.status'
        );

        // Q4-Etappe 4 / F1 (2026-09-08): Zitat-Block-Comments —
        // analog zur Text-Kette, alle vier Endpunkte.
        Route::post('/comments/quote', [ContentCommentController::class, 'commentQuote'])->name('comments.quote');
        Route::get('/comments/quote/{id}/', [ContentCommentController::class, 'getQuoteComment'])->name('comments.quote.show');
        Route::post('/comments/quote/{id}/save', [ContentCommentController::class, 'saveCommentQuote'])->name('comments.quote.save');
        Route::post('/comments/quote/status', [ContentCommentController::class, 'setCommentStatusQuote'])->name('comments.quote.status');

        // Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block-Comments.
        Route::post('/comments/data-facts', [ContentCommentController::class, 'commentDataFacts'])->name('comments.data_facts');
        Route::get('/comments/data-facts/{id}/', [ContentCommentController::class, 'getDataFactsComment'])->name('comments.data_facts.show');
        Route::post('/comments/data-facts/{id}/save', [ContentCommentController::class, 'saveCommentDataFacts'])->name('comments.data_facts.save');
        Route::post('/comments/data-facts/status', [ContentCommentController::class, 'setCommentStatusDataFacts'])->name('comments.data_facts.status');
        Route::post(
            '/texts/reset',
            [TextBlockController::class, 'resetText']
        )->name(
            'text.reset'
        );
        Route::post('/comments/image', [ContentCommentController::class, 'commentImage'])->name(
            'comments.image'
        );
        Route::get('/comments/image/{id}/', [ContentCommentController::class, 'getImageComment'])->name(
            'comments.image.show'
        );
        Route::post(
            '/comments/image/{id}/save',
            [ContentCommentController::class, 'saveCommentImage']
        )->name(
            'comments.image.save'
        );
        Route::post(
            '/comments/image/status',
            [ContentCommentController::class, 'setCommentStatusImage']
        )->name(
            'comments.image.status'
        );
        // Q4-Etappe 2 / I6 (2026-08-27): Kommentar-Endpunkte im
        // ProjectCommentController. Route-Namen bleiben.
        Route::post('/comments/project', [ProjectCommentController::class, 'commentProject'])->name(
            'comments.project'
        );
        Route::get(
            '/comments/project/{id}/',
            [ProjectCommentController::class, 'getProjectComment']
        )->name(
            'comments.project.show'
        );
        Route::get(
            '/texts/{id}/log',
            [ProjectController::class, 'getCurrentLog']
        )->name(
            'log.text'
        );
        Route::get(
            '/roles/{id}/check',
            [RoleController::class, 'roleHasUsers']
        )->name(
            'role.check'
        );
        Route::post(
            '/roles/{id}/replace/{alt}',
            [RoleController::class, 'customizedDelete']
        )->name(
            'customizedDelete'
        );
        Route::post(
            '/comments/project/{id}/save',
            [ProjectCommentController::class, 'saveCommentProject']
        )->name(
            'comments.project.save'
        );
        Route::post(
            '/comments/project/status',
            [ProjectCommentController::class, 'setCommentStatusProject']
        )->name(
            'comments.project.status'
        );
        Route::post(
            '/projects/permissions/update',
            [ProjectPermissionController::class, 'setPermissionForUserOnProject']
        )->name(
            'project.permission'
        );
        // Q4-Etappe 2 / I11 (2026-08-27): Source-Autocomplete unter dem
        // Prefix `/api/internal/` (analog Locale/Theme/Initials).
        Route::get('/api/internal/sources/autocomplete', [ContentController::class, 'autocomplete'])->name(
            'autocomplete'
        );
        Route::get(
            '/image/{file}',
            function ($file) {
                // Uploads landen via UploadTrait::uploadOne in disk='public'
                // (storage/app/public/uploads/images/). Ohne ->disk('public')
                // sucht Storage::response auf der Default-Disk ('local',
                // storage/app/) und liefert nichts — siehe Finding F-LAR-010
                // bzw. AM-B-1.
                return Storage::disk('public')->response('uploads/images/'.$file);
            }
        )->name('image');
        Route::get(
            '/audio/{file}',
            function ($file) {
                return Storage::disk('public')->response('uploads/audio/'.$file);
            }
        )->name('audio');

        Route::get('lang/{lang}', [LanguageController::class, 'switchLang'])->name('lang.switch');

        Route::post(
            '/logs/reset',
            [ProjectController::class, 'resetValue']
        )->name(
            'log.reset'
        );

        Route::get(
            '/allComments',
            [ContentCommentController::class, 'listComments']
        )->name(
            'all.comments'
        );

        // Q4-Etappe 8 · E3d (2026-09-11, ADR-0030): Uebersetzen-Routen
        // auf Plural + Route-Model-Binding umgestellt. Route-Namen
        // wandern auf `projects.translations.edit` / `.update`.
        Route::get(
            '/projects/{project}/translations',
            [ProjectTranslationController::class, 'translateCurrentProject']
        )->name('projects.translations.edit');

        Route::post(
            '/projects/{project}/translations',
            [ProjectTranslationController::class, 'saveTranslations']
        )->name('projects.translations.update');

        // 301-Redirect fuer alte Bookmarks — GET nur, POST hat kein
        // Bookmark-Aequivalent und wird vom Frontend hart umgestellt.
        Route::redirect('/project/{id}/translate', '/projects/{id}/translations', 301);

        // Phase 5ab.2: Verlauf-Panel-Feed und Wiederherstellen.
        Route::get(
            '/revisions/{subjectType}/{subjectId}',
            [RevisionController::class, 'index']
        )->whereNumber('subjectId')->name('revisions.index');

        Route::post(
            '/revisions/{revision}/restore',
            [RevisionController::class, 'restore']
        )->name('revisions.restore');

        // Q4-Etappe 2 / I7 (2026-08-27): Tote Route `save.translation.text`
        // entfernt. Ziel-Methode war `ContentController::saveTranslatedText`
        // — als `private` markiert (E.7b 4a-Hotfix-II.b) und ohne Aufrufer
        // in Blade oder JS. Ein POST haette 500 geworfen. Der
        // Translation-Body-Save laeuft ueber TextBlockController::saveText
        // im `translationMode`-Pfad.

        // Q4-Etappe 8 · E3d (2026-09-11, ADR-0030): Metadaten auf
        // Plural + Route-Model-Binding.
        Route::get(
            '/projects/{project}/metadata',
            [ProjectController::class, 'editMetaData']
        )->name('projects.metadata');

        Route::redirect('/project/{id}/metadata', '/projects/{id}/metadata', 301);

        Route::post(
            '/comments/{id}/update/{status}',
            [ContentCommentController::class, 'updateCommentStatus']
        )->name(
            'comments.status.update'
        );

        Route::post(
            '/galleries',
            [GalleryBlockController::class, 'saveGallery']
        )->name(
            'save.gallery'
        );

        Route::get(
            '/galleries/{id}/edit',
            [GalleryBlockController::class, 'editGallery']
        )->name(
            'gallery.edit'
        );

        Route::delete('/galleries/{id}', [GalleryBlockController::class, 'destroyGallery'])->name(
            'gallery.delete'
        );

        Route::post(
            '/audiovisuals',
            [AudiovisualController::class, 'store']
        )->name(
            'save.audiovisual'
        );

        Route::delete('/audiovisuals/{id}', [AudiovisualController::class, 'delete'])->name(
            'audiovisual.delete'
        );

        Route::post(
            '/comments/{id}/audiovisual',
            [AudiovisualController::class, 'saveCommentAudiovisual']
        )->name(
            'comments.audiovisual.save'
        );

        Route::post('/comments/audiovisual', [AudiovisualController::class, 'commentAudiovisual'])->name(
            'comments.audiovisual'
        );

        Route::post(
            '/comments/{id}/gallery',
            [ContentCommentController::class, 'saveCommentGallery']
        )->name(
            'comments.gallery.save'
        );

        Route::post('/comments/gallery', [ContentCommentController::class, 'commentGallery'])->name(
            'comments.gallery'
        );

        // Q4-Etappe 2 / I6 (2026-08-27): Preview/PDF-Download im
        // ProjectPreviewController. Route-Namen bleiben.
        // Q4-Etappe 5 / G3 (2026-09-08): Multi-Page-Reader-Route.
        // Rendert genau ein Kapitel des Projekts mit Kapitel-
        // Navigation. Wird nur bei reader_layout=multi-page
        // aktiv genutzt; der /preview-Endpoint redirected je nach
        // Setting auf das erste Kapitel.
        Route::get('/preview/chapters/{chapter}', [ProjectPreviewController::class, 'previewChapter'])
            ->name('preview.chapter');
        Route::get('/preview', [ProjectPreviewController::class, 'previewProject'])->name(
            'preview'
        );

        // Q4-Etappe 7 · E7-6 / Etappe-6-Rest (2026-09-11): Reader-Rail
        // Fußlinks. „Alle Abbildungen" listet die Bilder des Projekts,
        // „Kapitel als PDF" rendert eine PDF-Version genau eines
        // Kapitels über die bestehende dompdf-Pipeline.
        Route::get('/preview/all-images', [ProjectPreviewController::class, 'previewAllImages'])
            ->name('preview.all_images');
        Route::get('/preview/chapters/{chapter}/pdf', [ProjectPreviewController::class, 'downloadChapterPdf'])
            ->name('preview.chapter_pdf');
        // Fußzeile-Sammelspalte-Ziele fuer Reader-Ausgabe (Etappe-6-Rest).
        Route::get('/preview/credits', [ProjectPreviewController::class, 'previewCredits'])
            ->name('preview.credits');
        Route::get('/preview/accessibility', [ProjectPreviewController::class, 'previewA11y'])
            ->name('preview.a11y');

        // NF-CODE-006: tote Route `image.preview` (Default-Disk `local`,
        // Pfad `img/`) entfernt. Phase-0-Grep über resources/ und app/
        // zeigt keine Caller — weder im Blade noch im Controller. Wenn
        // sich später ein externer Direktlink zeigt, ist das image-Pattern
        // /image/{file} (disk `public`, /uploads/images/) der saubere
        // Ersatz, nicht eine Wiederauferstehung dieser Route.

        Route::get('/preview/download', [ProjectPreviewController::class, 'downloadPreview'])->name(
            'download'
        );

        // Q4-Etappe 8 · E3d (2026-09-11, ADR-0030): Reader-Nebenseite
        // fuer Impressum/AGB (bislang „/copyright"). Der Endpoint
        // erwartet den Projekt-Kontext ueber `?parameters[id]=...`
        // und ist damit noch nicht auf sauberes Model-Binding
        // umstellbar — Rename auf `preview.legal` und Alt-Pfad-Redirect.
        Route::get('/preview/legal', [ProjectController::class, 'projectMetadata'])
            ->name('preview.legal');
        Route::redirect('/copyright', '/preview/legal', 301);

        // Q4-Etappe 8 · E3d (2026-09-12, ADR-0030): 301-Redirects fuer
        // Kommentar-GET-Pfade (Bookmarks). POSTs sind Frontend-only
        // und werden mit dem Rename hart mitgezogen.
        foreach (['chapter', 'entry', 'text', 'quote', 'data-facts', 'image', 'project'] as $type) {
            Route::redirect("/comment/$type/{id}", "/comments/$type/{id}", 301);
        }

        // Q4-Etappe 8 · E3d (2026-09-12, ADR-0030): 301-Redirects fuer
        // Content-Action- und Nebengets. POST-/DELETE-Pfade sind
        // Frontend-only und wurden hart umgestellt — Named Routes
        // bleiben unveraendert.
        Route::redirect('/edit/{id}/text', '/texts/{id}/edit', 301);
        Route::redirect('/edit/{id}/image', '/images/{id}/edit', 301);
        Route::redirect('/log/text/{id}', '/texts/{id}/log', 301);
        Route::redirect('/gallery/{id}/edit', '/galleries/{id}/edit', 301);
        Route::redirect('/role/check/{id}', '/roles/{id}/check', 301);
        Route::redirect('/permission/user/{id}', '/users/{id}/permissions', 301);

    }
);

require __DIR__.'/auth.php';
