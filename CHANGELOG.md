# Changelog

Alle nennenswerten Änderungen an crowdCuratio werden in dieser Datei dokumentiert.

Das Format orientiert sich an [Keep a Changelog 1.1.0](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning 2.0.0](https://semver.org/lang/de/).

Sektionen je Release: `Hinzugefügt`, `Geändert`, `Veraltet`, `Entfernt`,
`Behoben`, `Sicherheit`.

## [Unreleased]

### Hinzugefügt (CommentService-Extraktion)

- **`CommentService`** in `app/Services/`. Kapselt die fünf
  Schreibpfade auf Comments — `addComment`, `replyToComment`,
  `editComment`, `deleteComment`, `setCommentStatus` — plus
  `dispatchSaveAction`, der die `btn_submit`-Switch-Logik
  (Edit/Delete/Reply) zentralisiert, die heute in sieben
  Controller-Methoden über die fünf Comment-tragenden Controller
  dupliziert war. Acht Pest-Tests in
  `tests/Feature/Services/CommentServiceTest.php` decken die fünf
  Methoden plus die vier dispatch-Switch-Pfade ab.
- **Comment-Charakterisierungs-Tests** in
  `tests/Feature/Refactor/CommentPfadeCharacterizationTest.php`.
  Zehn Pest-Tests fixieren das beobachtbare Verhalten der
  Comment-Endpunkte vor der Extraktion: Project / Chapter /
  Entry für add, save-Switch (Edit/Delete/Reply) und
  setStatus, plus der gemeinsame `updateStatus`-GET-Endpoint.
  Content (Text/Image/Gallery) und Audiovisual sind strukturell
  identisch, brauchen aber Source-/Audiovisual-Test-Factories,
  die noch nicht existieren — Refactor läuft trotzdem über alle
  fünf Controller, Smoke deckt sie ab.

### Geändert (CommentService-Extraktion)

- **Fünf Controller per Constructor-Injection auf
  `CommentService`** umgestellt. `ProjectController`,
  `ChapterController`, `EntryController`, `ContentController`
  und `AudiovisualController` konsumieren den Service jetzt
  über readonly-Properties. Alle 15 Comment-Endpunkt-Methoden
  delegieren — die switch-cases auf `btn_submit` sind aus den
  sieben Controller-Methoden raus und liegen einmal im Service
  als `dispatchSaveAction`.

### Geändert (Comment-Naming-Sweep)

- **`setStatus*`-Method-Names auf `setCommentStatus*` umbenannt**
  (Project, Chapter, Entry, Text, Image). Die Methoden setzen
  einen Comment-Status, nicht den Status des jeweiligen Models —
  der alte Name war historisch und irreführend. Plus
  `ContentController::updateStatus` → `updateCommentStatus`. Die
  passenden Route-Namen sind jetzt konsistent als
  `comment.<model>.status` (vorher `<model>.status`, plus zwei
  unbenannte Routes); die `update.status`-Route heißt jetzt
  `comment.update.status`.
- **Gallery- und Audiovisual-Method-Names entwirrt.** Die
  Methoden waren paarweise vertauscht — `commentGallery` und
  `commentAudiovisual` machten den Save-Switch, während
  `galleryCommentSave` und `audiovisualCommentSave` den
  Neu-Kommentar anlegten. Nach dem Sweep heißen Methoden, die
  einen neuen Kommentar anlegen, `comment<Model>`, und Methoden,
  die eine save-Submission routen, `saveComment<Model>` —
  symmetrisch zu Project/Chapter/Entry/Text/Image. Bei
  Audiovisual sind zusätzlich die Route-Namen vertauscht und der
  `pathReply` / `pathComment`-Eintrag in `CommentRetrieve`
  mit-korrigiert.
- **Blade-Stelle in `chapters/index.blade.php`** auf die neuen
  Route-Namen umgestellt (`comment.<model>.status`).

### Entfernt (CommentService-Extraktion)

- **`app/Traits/CommentTrait.php` gelöscht.** Die fünf
  Trait-Methoden (`commentAsUser`, `replyAsUser`, `editAsUser`,
  `deleteAsUser`, `status`) wandern in den `CommentService`.
  Die `comments()`-MorphMany-Relation lebte schon direkt in den
  acht Modellen (Project, Chapter, Entry, MediaContent, Text,
  Image, Gallery, Audiovisual) — der Trait war nur noch
  Methoden-Container, jetzt ersatzlos weg.
- **`use CommentTrait;`-Aufrufe aus acht Modellen entfernt.**
  Modell-Bodies sind dadurch dünner; mit dem Eloquent-Strict-
  Mode passt die explizite Relation-Definition pro Modell
  besser zu der Codebase als die implizite Trait-Aufklebung.

**Phase 3 — Major-Upgrade-Welle abgeschlossen (2026-05-31).** Sieben
sequenzielle Sprünge nach ADR-0003: PHP 8.1 → 8.2 → 8.3 → 8.4 in drei
Schritten (mit verschränktem PHP-8.4-+-Laravel-9-Sprung wegen
Larastan-v1-PHPStan-Inkompatibilität), Laravel 8 → 9 → 10 → 11 → 12 in
vier Schritten. Spatie-Pakete in den jeweils kompatiblen Majors
(Permission ^6, Activitylog ^4, Translatable ^6, Welcome-Notification
^2.5, Ignition ^2). Tooling-Wellen: Larastan v1 → v2 → v3 (mit
PHPStan v2, Repo-Move `nunomaduro/larastan` → `larastan/larastan`),
Pest v1 → v2 → v3, PHPUnit 9 → 10 → 11, Carbon v3. Vier
abandoned-Packages strukturell durch Major-Sprünge eliminiert
(`swiftmailer/swiftmailer`, `facade/ignition`, `laravelcollective/html`,
`fideloper/proxy`). Pest **58 grüne Tests** (vor Phase 3 waren es 40),
Larastan-Baseline auf **15 Items** geschmolzen (Phase-2-Ende 198 v1;
im Verlauf v2-130 → v3-15 nach PHPDoc-Welle und vier Smell-Fixes im
`ProjectController`). Quick-Smoke Pfad 4 nach jedem Sprung grün.
CVE-2025-27515 (Laravel File-Validation-Bypass) strukturell zu
(Laravel 12 ≫ 10.48.29). Coverage 26,68 %, CI-Schwelle 25 %.

Die einzelnen Sprünge werden unten in Block-G-zu-Block-B-Reihenfolge
beschrieben.

### Hinzugefügt

- **Bootstrap-Charakterisierungs-Tests** in `tests/Feature/Refactor/`.
  Drei Pest-Tests fixieren das beobachtbare Verhalten des Kernel-
  Bootstraps an den drei Schichten, an denen der Switch greift:
  Web-Stack-Middleware (Auth-Redirect auf einer geschützten
  Resource-Route), Route-Middleware-Alias (`role:Admin` lehnt einen
  Reader auf der Register-Route mit 403 ab), Exception-Rendering
  (unbekannte Route → 404). Test-First-Doktrin in Aktion: erst die
  Tests grün gegen den alten Kernel-Stil, dann der Refactor.
- **Coverage-Vorlauf vor dem Refactor-Block.** Achtzehn neue
  Pest-Tests in vier Files füllen die größten ungetesteten
  Service-Pfade: fünf Konstruktor-Tests in
  `tests/Unit/LogServiceTest.php` (Switch-Cases per Reflection),
  vier Tests in `tests/Feature/Services/UserServiceTest.php`
  (globale vs. project-scoped Permissions, Projekt-Isolation),
  vier Tests in `tests/Feature/Services/CommentRetrieveTest.php`
  (Comment-Render je commentable_type, Owner-Flag, Reply-
  Schachtelung), fünf Tests in
  `tests/Feature/Services/LogServiceTest.php` (history,
  getParentText, textLog-Boundary). Coverage steigt von 26,68 %
  auf 35 %.
- **CI-Coverage-Schwelle auf 30 angehoben.** `composer.json`
  `test-coverage --min` von 25 auf 30. Erster Schritt der
  Coverage-Trajektorie auf 55 % bis Ende der Refactor-Welle.

- **`ProjectImageService`** in `app/Services/`. Kapselt das Logo-
  Upload für Projects: `store(?UploadedFile $image): ?string` legt
  das Bild unter `/uploads/images/` auf der `public`-Disk ab und
  liefert den generierten Dateinamen zurück (oder `null`, wenn
  kein File übergeben wurde). Wird vom `ProjectController` per
  Constructor-Injection genutzt; die `UploadTrait`-Klassen-Bindung
  im Controller entfällt. Drei Pest-Tests in
  `tests/Feature/Services/ProjectImageServiceTest.php` decken
  null-Input, Happy-Path mit `Storage::fake('public')` und das
  Dateinamen-Muster `YYYYMMDD_<unix-ts>.<ext>` ab.
- **`ProjectPermissionService`** in `app/Services/`. Zentralisiert
  die zehn project-scoped Permission-Operationen, die vorher als
  `protected`-Helper über den `ProjectController` verteilt waren:
  Listing der berechtigten User, Lesen der globalen Spatie-
  Permissions und der project-scoped Pivot-Einträge, Set-Semantik
  beim Setzen neuer Permissions (alte werden vorher gelöscht,
  Invitation wird aufgeräumt und neu aufgesetzt), vollständiges
  Entfernen eines Users aus einem Project. Sechs Pest-Tests in
  `tests/Feature/Services/ProjectPermissionServiceTest.php` decken
  die fünf Kern-Methoden ab.
- **`ProjectData`-DTO** in `app/Data/`. Readonly-Klasse mit
  Constructor-Property-Promotion und einer `fromRequest(FormRequest,
  ?string $logo)`-Factory. Ersetzt das `mapData()`-Cargo im
  `ProjectController`, das die `FormRequest`-Validation umgangen
  und mit `isset($request[...])` wieder selbst gelesen hat. Der
  Logo-Dateiname wird beim Bauen des DTOs vom
  `ProjectImageService` reingereicht, nicht aus dem Request
  rückgeführt — strukturelle Verstärkung der NF-SEC-007-Härtung.
- **`ChapterService`** in `app/Services/`. Kapselt die zwei
  Schreibpfade auf Chapter: `create(ChapterData, int $projectId)`
  mit Position-Calculation (`max(position) + 1`, leere Projects
  starten bei 1) und `update(Chapter, ChapterData)` mit
  Translation-Verzweigung (direkter Schreibpfad vs.
  `setTranslation('en', ...)`). Fünf Pest-Tests in
  `tests/Feature/Services/ChapterServiceTest.php` decken die
  Position-Logik und beide Update-Pfade ab, inkl. des
  `'undefined'`-Sentinels für die Translation-Description.
- **`ContentReorderService`** in `app/Services/`. Zentralisiert
  die drei Drag-and-Drop-Schreibpfade über Chapter / Entry /
  MediaContent plus `resolveProject(...)` für den Authorize-Gate
  im Controller. Wird vom `ChapterController::saveDragAndDrop`
  konsumiert und steht für den `EntryController` bereit. Sieben
  Pest-Tests in
  `tests/Feature/Services/ContentReorderServiceTest.php` decken
  die Reorder-Operationen und alle Project-Resolution-Pfade ab.
- **`ChapterData`-DTO** in `app/Data/`. Readonly-Klasse mit
  `fromRequest(FormRequest)`-Factory. Normalisiert die
  Frontend-Feldnamen (chapterTitle / chapterSubtitle /
  chapterDescription) auf die Modell-Feldnamen und kapselt die
  Translation-Flags (`translationChapter`, `isTranslated`), die
  der `UpdateChapterRequest` zusätzlich trägt.
- **`EntryService`** in `app/Services/`. Strukturell parallel zu
  `ChapterService`: `create(EntryData, int $chapterId)` mit
  Position-Calculation und `update(Entry, EntryData)` mit
  Translation-Verzweigung. Fünf Pest-Tests in
  `tests/Feature/Services/EntryServiceTest.php` decken die beiden
  Pfade ab.
- **`EntryData`-DTO** in `app/Data/`. Spiegelung von
  `ChapterData` für die Entry-Mutations — normalisiert die
  Frontend-Feldnamen (entryTitle / entrySubtitle /
  entryDescription) und kapselt die Translation-Flags
  (`translationEntry`, `isTranslated`).

### Geändert (Service-Layer-Pilot)

- **`ProjectController` per Constructor-Injection auf zwei
  Services umgestellt.** Statt `use UploadTrait;` und zehn
  privaten Helper-Methoden konsumiert der Controller jetzt
  `ProjectImageService` und `ProjectPermissionService` über
  readonly-Properties. `store()` und `update()` arbeiten gegen
  das `ProjectData`-DTO statt gegen `$request[...]`-Reads.
  `setPermissionForUserOnProject`, `givePermissionToUser`,
  `inviteUserForProject`, `deleteUserFromProject` und
  `editMetaData` delegieren an den Permission-Service —
  `UserHasPermission`-, `Invitation`- und `ModelHasRole`-Reads
  liegen nicht mehr im Controller.
- **`ChapterController` per Constructor-Injection auf zwei
  Services umgestellt.** `ChapterService` übernimmt
  Position-Calculation in `store()` und die
  Translation-Verzweigung in `update()`; `ContentReorderService`
  übernimmt den `saveDragAndDrop`-Schreibpfad mitsamt der
  Project-Auflösung für den Authorize-Gate. Beide Methoden
  delegieren jetzt und sind nur noch HTTP-Mapping — der
  Controller-Body schrumpft entsprechend. Die
  `resolveDragTargetProject`-`protected`-Helper-Methode entfällt.
- **`EntryController` per Constructor-Injection auf den
  `EntryService` umgestellt.** `store()` und `update()` arbeiten
  jetzt gegen das `EntryData`-DTO und delegieren Position-
  Calculation und Translation-Verzweigung an den Service. Der
  Controller-Body schrumpft entsprechend.

### Entfernt (Service-Layer-Pilot)

- **`ProjectController::mapData()` (32 LoC) entfällt.** Wird
  durch `ProjectData::fromRequest()` ersetzt; der
  `status`-Default kommt jetzt im `store()` als expliziter
  `array_merge`-Eintrag dazu, nicht mehr aus einer
  Helper-Methode.
- **Fünf `protected`-Helper aus `ProjectController` entfallen**
  — Body und Verantwortung wandern in den
  `ProjectPermissionService`: `getUsersForThisProject`,
  `getCurrentUsersPermissions`, `getSelectedPermissionUser`,
  `getSelectedPermissionUserPluck` und `getRoleSelectedUser`.
  Die letzten beiden waren ohnehin tot (keine Aufrufer im
  Controller, keine Route-Bindings).
- **Tote Imports im `ProjectController` aufgeräumt.**
  `App\Models\Image` und `Mpdf\Pdf` waren als `use`-Statements
  vorhanden, aber im Klassen-Body nicht referenziert. Plus die
  jetzt obsoleten Imports `App\Models\Invitation`,
  `App\Models\ModelHasRole`, `App\Models\UserHasPermission`
  (wandern alle in den Permission-Service).

### Geändert (Bootstrap-Migration)

- **Application-Bootstrap auf Laravel-11+-Closure-API umgestellt.**
  `bootstrap/app.php` ist jetzt
  `Application::configure(basePath: ...)
   ->withRouting(...)
   ->withMiddleware(...)
   ->withExceptions(...)
   ->create()`. Die `web`-Group bekommt `Language` per
  `$middleware->web(append: [...])` angehängt, die Custom-Aliase
  (`role`, `permission`, `role_or_permission`, `admin`, `guest`)
  werden im `$middleware->alias(...)`-Block registriert,
  `TrimStrings`-Ausnahmen und der Guest-Redirect zur
  `route('login')` werden direkt im Bootstrap-Closure gesetzt. Der
  alte `bootstrap/app.php`-Application-Singleton-Stil mit drei
  expliziten Kernel-Bindings entfällt.

### Entfernt (Bootstrap-Migration)

- **`app/Http/Kernel.php` und `app/Console/Kernel.php` gelöscht.**
  Die Verantwortlichkeiten beider Klassen (Middleware-Stack,
  Middleware-Groups, Route-Middleware-Aliase, Schedule, Commands)
  wandern in die `bootstrap/app.php`-Closures. Custom-Commands
  unter `app/Console/Commands/` werden in Laravel 11+ automatisch
  geladen — der `$this->load(__DIR__.'/Commands')`-Aufruf war
  nicht mehr nötig.
- **`app/Exceptions/Handler.php` gelöscht** (60 LoC, ausschließlich
  Boilerplate ohne Custom-Verhalten). `$dontFlash` für Passwort-
  Felder wandert in den `withExceptions(function (Exceptions
  $exceptions) { … })`-Closure. Das `app/Exceptions/`-Verzeichnis
  ist damit weg.
- **Sechs Stock-Middleware-Subklassen aus `app/Http/Middleware/`
  gelöscht** — alle waren 1:1-Subklassen der Framework-Defaults
  ohne projekt-spezifische Logik: `Authenticate` (Redirect-zur-
  Login-Route wandert in `$middleware->redirectGuestsTo(...)`),
  `EncryptCookies` (leere `$except`-Liste), `PreventRequestsDuringMaintenance`
  (leere `$except`-Liste), `TrimStrings` (Passwort-Felder wandern
  in `$middleware->trimStrings(except: [...])`), `TrustProxies`
  (extends-Base ohne Override), `VerifyCsrfToken` (leere
  `$except`-Liste).

### Behoben

- **Latente LazyLoading-Verletzung in `LogService::history` und
  `LogService::textLog`.** Beide Methoden griffen auf
  `$activity->causer->name` zu, ohne die `causer`-Relation
  eager-zu-laden. Unter `Model::shouldBeStrict()` wirft das eine
  `LazyLoadingViolationException`. Im realen Produktivpfad lief
  das durch Glück: entweder wurden die Methoden nie über eine
  Strict-Mode-getestete Route getriggert, oder Spatie's
  LogsActivity-Hook hatte den `causer` schon im Hydrations-
  Cache. Fix: `->with('causer')` in beiden Methoden ergänzt.

- **Rate-Limit-Tests für die Guest-Auth-Routen.** Drei Pest-Feature-
  Tests in `tests/Feature/AuthRateLimitTest.php` decken den neuen
  `throttle:6,1`-Limiter auf `POST /login`,
  `POST /forgot-password` und `POST /reset-password` ab — jeder
  Test feuert sieben Requests aus derselben Session und verifiziert,
  dass der siebte mit HTTP 429 abgelehnt wird.
- **Erster Unit-Test-Slot im Projekt** (`tests/Unit/`). Drei Tests
  für `LogService::highlightTextDifference` in
  `tests/Unit/LogServiceTest.php` decken die größte ungetestete
  Service-Methode ab (identische Strings, leerer Alt-String,
  Diff-Markup-Verifikation) und liefern eine Vorlage für weitere
  Unit-Tests in Phase 4.

### Geändert

- **Rate-Limit auf den Guest-Auth-Routen.** `POST /login`,
  `POST /forgot-password` und `POST /reset-password` tragen jetzt
  `throttle:6,1` als zusätzliche Middleware. Konsistent mit den
  `verification.*`-Routen, die schon seit Breeze gedrosselt sind.
  Verhindert Credential-Stuffing auf Login und Spam auf den
  Password-Reset-Endpunkten.
- **`composer audit` im CI auf Hard-Fail.** Der Soft-Fail-Übergang
  aus Phase 2 ist abgeschlossen — die Laravel-8-CVEs sind durch
  Phase 3 strukturell weg. Ein neuer CVE im Lock bricht ab jetzt
  den Build statt nur einen Hinweis im Log zu hinterlassen.
  `continue-on-error: true` und `|| true` raus.
- **`php artisan config:cache` läuft im CI-Pest-Job vor der Suite.**
  Defense-in-depth gegen versehentliche `env()`-Calls außerhalb von
  `config/`: Larastan fängt das statisch, der zusätzliche Cache-
  Step fängt es dynamisch.
- **`MyCustomWelcomeNotification`-Konstruktor** auf
  `Carbon $validUntil` statt `CarbonInterface $validUntil`. Die
  Eltern-Klasse `Spatie\WelcomeNotification\WelcomeNotification`
  typed die Property selbst als `Carbon`; die redundante
  `$this->validUntil = $validUntil`-Zuweisung nach
  `parent::__construct()` ist mit raus.
- **`LogService::highlightTextDifference` und
  `ProjectController::highlightTextDifference`** von PascalCase
  auf camelCase umbenannt (sechs Aufrufer in zwei Dateien).
  Konsistent mit Laravel- und PSR-12-Standard für Methodennamen.
- **`LogService::__construct`: `'App\Models\gallery'` → `Gallery::class`.**
  Der kleine `g` war ein Tippfehler aus der Phase-1-
  Bestandsaufnahme, der auf einem case-sensitive Linux-Filesystem
  einen `ClassNotFoundException` ausgelöst hätte. `::class` macht
  solche Fallen strukturell unmöglich.
- **`Text::$fillable` bereinigt** — `'id'` und `'position'` raus.
  Die `texts.position`-Spalte ist seit der Migration
  `2021_07_28_163554_drop_foreign_key_table` nicht mehr in der DB,
  die Mass-Assignment-Liste hatte sie aber nie verloren — unter
  Strict-Mode hat Spatie-Activitylog beim `save()` über
  `$fillable` iteriert und auf das nicht-hydratisierte Attribut
  zugegriffen, was eine `MissingAttributeException` ausgelöst hat.
  `'id'` gehört grundsätzlich nicht in `$fillable` — Primary Key
  wird von Eloquent verwaltet. Die Schema-Bereinigung der toten
  `position`-Spalte selbst wandert in den Phase-4-Schema-Refactor
  (ADR-0012).

- **Eloquent Strict-Mode voll aktiviert.** `Model::shouldBeStrict()`
  im `AppServiceProvider` bündelt jetzt drei Schutzschichten in
  einem Aufruf statt nur `preventLazyLoading`: zusätzlich
  `preventAccessingMissingAttributes` (wirft beim Zugriff auf
  nicht-geladene oder nicht-existierende Spalten) und
  `preventSilentlyDiscardingAttributes` (wirft, wenn `fill()` /
  `create()` Felder erhält, die nicht in `$fillable` stehen). Weiter
  nur außerhalb von Production aktiv (Sail-Dev, CI-Pest), damit
  Live-User keine späte Regression erleben.
- **`@property`-Annotationen an sieben Modellen.** Class-Level-
  PHPDoc auf `Audiovisual`, `Chapter`, `Entry`, `Gallery`, `Project`,
  `Source` und `Text` mit den jeweiligen DB-Feldern, Relations und
  den Runtime-Snapshots (`$media_id`, `$image_list`, `$media`,
  `$entry`), die in `ProjectController::allData()` dynamisch
  zugewiesen werden. Voraussetzung für den Strict-Mode-Switch und
  für den `@property`-getriebenen PHPStan-Inferenz-Pfad.
- **`ProjectController::setImage()`** PHPDoc-Return `@return $this`
  → `@return string`. Die Methode gab seit jeher den generierten
  Filename als String zurück; der falsche Doc-Hint hatte den
  Aufrufer-Check `if ($logo !== '')` zum stillen statischen
  Phantom-Befund gemacht.
- **`ProjectController::getSource()` + `ContentController::getSource()`
  refaktoriert.** Die `$id = ''`-Variable und der unerreichbare
  `return $this` am Methodenende sind raus, Early-Return aus der
  Schleife, klarer `@return int`. Die Methode bleibt vorerst in
  beiden Controllers dupliziert — Zusammenführung in einen
  `SourceService` wandert in Phase 4.
- **Redundante `'created_at' => now()`-Zuweisungen entfernt** in
  vier Eloquent-Mass-Assignment-Pfaden: `Image::firstOrCreate`
  (`ContentController::saveImage`), `Invitation::firstOrCreate`
  (`ProjectController::setPermissionForUserOnProject`),
  `UserHasPermission::create` + `Invitation::create`
  (`RegisteredUserController::store`). Eloquent setzt Timestamps
  automatisch — das manuelle Setzen war Cargo und wird unter
  `preventSilentlyDiscardingAttributes` als
  `MassAssignmentException` sichtbar. Query-Builder-Pfade
  (`Source::insertGetId`, `Text::insertGetId`) behalten ihr
  `'created_at'`, weil der Query Builder keine Timestamps
  automatisch setzt.

### Entfernt

- **Auskommentierter Switch-Case-Block in `CommentTrait::commentAsUser`.**
  Zweiundzwanzig Zeilen toter Code in einem `/* ... */`-Kommentar,
  die seit der ersten Bestandsaufnahme in der Datei standen. Plus
  den dazugehörigen `} else {` / `// }`-Marker, der den aktiven
  Pfad eingerahmt hatte.

### Behoben

- **Blade-Expressions in HTML-Kommentaren werden jetzt nicht mehr
  ausgewertet.** Vier Stellen in drei Blade-Templates
  (`chapters/index.blade.php` Z. 409 + 475, `auth/register.blade.php`
  Z. 102, `projects/description.blade.php` Z. 148) hatten
  auskommentierten HTML-Code, in dem `{{ ... }}`-Expressions
  stehengeblieben sind. Blade interpretiert solche Expressions auch
  innerhalb von HTML-Kommentaren — der Kommentar versteckt nur das
  gerenderte HTML, nicht die PHP-Auswertung. Im
  `chapters/index.blade.php`-Fall hat das beim Quick-Smoke eine
  `MissingAttributeException` auf `$item->alt` ausgelöst
  (`$item` ist ein `MediaContent`, hat kein `alt`-Property). Fix:
  HTML-Kommentare `<!-- ... -->` durch Blade-Kommentare
  `{{-- ... --}}` ersetzt; Blade überspringt den Block jetzt
  komplett.

- **Laravel 11 → 12.** `composer.json` `laravel/framework` auf
  `^12.0`. Spatie-Pakete (`permission ^6`, `activitylog ^4`,
  `translatable ^6`, `welcome-notification ^2.5`,
  `ignition ^2`) bleiben — alle Laravel-12-kompatibel mit den
  Laravel-11-Versionen. Einziger erzwungener Begleitsprung:
  Larastan (siehe folgender Punkt).
- **`nunomaduro/larastan` → `larastan/larastan ^3`.** Repo-Move
  beim v3-Major: das Paket lebt nicht mehr unter
  `nunomaduro/larastan`, sondern unter `larastan/larastan`.
  Composer-Constraint und der `include`-Pfad in `phpstan.neon`
  (`./vendor/nunomaduro/larastan/extension.neon` →
  `./vendor/larastan/larastan/extension.neon`) entsprechend
  angepasst. v3 bringt PHPStan v2 mit — strengere Kovarianz-
  Regeln auf überschriebenen PHPDoc-Property-Types und eine
  neue Regel `larastan.noEnvCallsOutsideOfConfig`. Beides hat
  in unserem Code Folgearbeiten ausgelöst (siehe folgende Punkte).
- **`$fillable`-PHPDoc in 18 Modellen** von
  `@var array<int, string>` auf `@var list<string>` umgestellt.
  Eloquent-`Model::$fillable` ist im Basis-PHPDoc seit Längerem
  als `list<string>` getypt; PHPStan v2 verlangt jetzt Kovarianz
  und schimpft bei der älteren Form. Mechanische Welle in
  `Audiovisual`, `Chapter`, `Comment`, `Entry`, `Gallery`,
  `Image`, `Invitation`, `MediaContent`, `ModelHasPermission`,
  `ModelHasRole`, `Permission`, `PermissionDescription`,
  `Project`, `Role`, `Source`, `Text`, `User`
  (zusätzlich `$hidden`), `UserHasPermission`.
- **`CreateAdminUserSeeder` von `env()` auf `config()` umgestellt.**
  Larastan v3 verbietet `env()`-Calls außerhalb des `config/`-
  Verzeichnisses (`larastan.noEnvCallsOutsideOfConfig`), weil
  sie nach `php artisan config:cache` `null` zurückgeben. Die
  vier Stellen im Seeder (`ADMIN_EMAIL`, `ADMIN_PASSWORD`,
  `ADMIN_NAME`, `ADMIN_LAST_NAME`) lesen jetzt aus
  `config('admin.*')`; die ENV-Variablen werden in der neuen
  `config/admin.php` einmalig gelesen. Inhaltlich keine
  Änderung — Aufruf-Indirektion eine Schicht tiefer.
- **`isset()` auf nicht-nullable Collection durch `isNotEmpty()`
  ersetzt.** Drei Stellen in `ProjectController::history` und
  `LogService::history` / `LogService::textLog` prüften
  `isset($activity->changes)` als Heuristik für
  „Activity-Eintrag hat Property-Diff". Spatie-Activity-`changes`
  ist seit v4 eine Collection (nicht nullable) — `isset()`
  liefert dort immer `true`, und PHPStan v2 sieht das jetzt
  präzise genug, um zu meckern. Umgestellt auf
  `$activity->changes->isNotEmpty()`, was die ursprüngliche
  Absicht direkt ausdrückt.
- **`ProjectController::getUsersForThisProject()` PHPDoc-Return
  korrigiert.** Doc-Block sagte `@return bool`, die Methode gab
  aber seit jeher ein indiziertes Array zurück. Auf
  `@return array<int, array<string, mixed>>` angepasst — Bestands-
  Bug aus der alten Baseline.
- **Larastan-Baseline auf v3 regeneriert.** Vorher 130 v2-Einträge,
  jetzt 15 v3-Einträge. Was bleibt: 11 Magic-Property-Accesses auf
  Eloquent-Relations (`$entry->chapter->project`, `$activity->changes`-
  ähnliche Inferenz-Lücken in vier Dateien), plus vier echte
  Smell-Befunde in `ProjectController` (`setImage()`-Return-Typ-
  Tippfehler, zwei `== ''`-Pfade mit toter Bedingung, ein
  `view()`-Argument-Typ-Mismatch). Beides Phase-4-Hygiene:
  `@property`-Annotationen am Class-Level der Models lösen den
  Großteil ohne Code-Eingriff.

### Anmerkung zur Verifikation (Block G)

Pest grün auf PHP 8.4 + Laravel 12, Larastan v3 stabil (15 Items
in Baseline, keine Bypass-Errors), Pint grün, Quick-Smoke Pfad 4
(Login → Project anlegen → Bild-Upload → PDF-Export) grün. Spatie-
Pakete unverändert — kein erzwungener Major-Bump in Block G.

- **Laravel 10 → 11.** `composer.json` `laravel/framework` auf
  `^11.0`. Mit ziehen: `nunomaduro/collision ^8`, `laravel/breeze ^2`,
  `pestphp/pest ^3`, `pestphp/pest-plugin-laravel ^3`,
  `phpunit/phpunit ^11`. Spatie-Pakete (`permission ^6`,
  `activitylog ^4`, `translatable ^6`, `welcome-notification ^2.5`,
  `ignition ^2`) bleiben — alle Laravel-11-kompatibel mit den
  Laravel-10-Versionen.
- **`laravelcollective/html` (abandoned) raus, native Blade-Forms.**
  Die Library wurde nur in zwei Templates (`roles/create.blade.php`,
  `roles/edit.blade.php`) genutzt — acht `Form::*`-Aufrufe. Umstellung
  auf natives `<form>` mit `@csrf`, `@method('PATCH')`, `@checked` und
  `old('field', $model->field)`-Fallback. Eine Dependency weniger, kein
  externes Form-Builder-Paket mehr im Stack. ADR-0003 hatte das schon
  vor dem Laravel-9-Sprung empfohlen, hier akut geworden weil
  `laravelcollective/html` ^6.4 nicht Laravel-11-kompatibel ist.
- **Pest-3-Inferenz für Larastan.** Pest 3 hat die Test-Case-Bindung
  intern geändert, Larastan v2 sah `$this` in Pest-Closures nur noch
  als PHPUnit-Default. 45 `/** @var \Tests\TestCase $this */`-Hints
  in `AuthorizationTest.php` und `HappyPathTest.php` ergänzt, damit
  `$this->actingAs(...)`, `$this->post(...)` etc. wieder als Laravel-
  Methoden inferiert werden.
- **Carbon v3 in `MyCustomWelcomeNotification`.** Methodennamen-
  Wechsel: `diffInRealMinutes()` ist in v3 weg, ersetzt durch
  `diffInMinutes(absolute: true)`. Plus Int-Cast für die Translation-
  String-Interpolation.
- **`Entry::getAllMediaAttribute()` aufgeräumt.** Vorher iterierte die
  Methode über einen Relation-Builder direkt und gab den gleichen
  Builder zurück — der `foreach`-Loop war toter Code, hat `$data`
  befüllt und wegfallen lassen. Laravel-11-strict-Inferenz wirft das
  jetzt sichtbar. Vereinfacht auf `return $this->mediaContent;`
  (Property-Access auf die geladene Collection).
- **`MediaContent`-PHPDoc-Returns** korrigiert. Drei Methoden
  (`image()`, `text()`, `audiovisual()`) deklarierten `MorphToMany`
  als Return-Type, gaben aber `BelongsTo` zurück (Bestands-Bug aus
  der alten Larastan-Baseline). Doc-Strings angepasst. Die eigentliche
  Schema-/Relation-Entscheidung wartet auf ADR-0012 in Phase 4.
- **Laravel 9 → 10.** `composer.json` `laravel/framework` auf `^10.0`.
  Mit ziehen: `spatie/laravel-permission ^6`, `spatie/laravel-translatable
  ^6`, `spatie/laravel-ignition ^2`, `nunomaduro/collision ^7`,
  `laravel/breeze ^1.21`, `pestphp/pest ^2`, `pestphp/pest-plugin-laravel
  ^2`, `phpunit/phpunit ^10`. Pest 2 + PHPUnit 10 ist ein paralleler
  Major-Sprung, der mit Laravel 10 zusammen passiert.
- **Spatie-Permission v5 → v6 Middleware-Namespace.**
  `Spatie\Permission\Middlewares\*` → `Spatie\Permission\Middleware\*`
  (Singular). Imports in `app/Http/Kernel.php` entsprechend angepasst.
- **PHPUnit-10-Konfig** in `phpunit.xml` migriert auf das neue Schema —
  `<coverage>`-Block umgestellt, deprecated Attribute entfernt.
- **`.gitignore`** ergänzt um `.phpunit.cache/` (PHPUnit-10-Verzeichnis)
  und `*.bak` (Backup-Pattern für Schema-Migrations).
- **PHP 8.3 → 8.4 + Laravel 8 → 9 (verschränkt).** ADR-0003 hatte
  PHP-erst-dann-Laravel festgelegt; an PHP 8.4 stieß Larastan v1 an
  ein hartes PHPStan-Versions-Limit (`IS_READONLY`-Type-Mismatch in
  PHPStan-BetterReflection-Stubs), und Larastan v2 verlangt Laravel 9.
  Pragmatische Korrektur: beide Major-Sprünge in einem Branch
  zusammengelegt. Elf Composer-Constraints simultan gebumpt:
  `laravel/framework ^9`, `nunomaduro/larastan ^2`,
  `spatie/laravel-{permission ^5, activitylog ^4, translatable ^5}`,
  `laravelcollective/html ^6.4`, `laravel/breeze ^1.9`,
  `nunomaduro/collision ^6`, `facade/ignition` raus →
  `spatie/laravel-ignition ^1`, `fideloper/proxy` raus.
- **Spatie-Activitylog v3 → v4 — neue API-Konvention.** Statische
  Properties (`$logName`, `$logFillable`, `$logOnlyDirty`,
  `$submitEmptyLogs`) sind in v4 entfernt, jedes Modell mit
  `LogsActivity` braucht jetzt eine `getActivitylogOptions():
  LogOptions`-Methode. 18 Modelle entsprechend angepasst, zwei neue
  Schema-Spalten (`batch_uuid`, `event`) per veröffentlichten
  Migrations in der DB ergänzt.
- **`App\Http\Middleware\TrustProxies`** extends jetzt
  `Illuminate\Http\Middleware\TrustProxies` (Laravel-9-eigene
  Implementation, `fideloper/proxy` obsolet).
- **Laravel-9-PHPDoc-Generics nachgezogen.** 30+ App-Stellen:
  `@var array` → `@var array<int, string>` etc. auf
  `$middleware`/`$middlewareGroups`/`$routeMiddleware`,
  `$except` in 4 Middleware-Klassen, `$fillable` in 18 Modellen,
  `$policies`/`$listen` in den Providern, plus
  `App\Exceptions\Handler` Generics.
- **Test-Suite-Annotationen.** 51 `/** @var \App\Models\User */`-
  Hints in 5 Test-Dateien — Larastan-v2-Inferenz hatte
  `User::factory()->create()` als `Illuminate\Database\Eloquent\Model`
  gesehen und Folgeoperationen (`assignRole`, `givePermissionTo`,
  `makeProject`-Helper, `actingAs`, `$user->email/id`) als Type-
  Mismatch gemeldet.
- **Larastan-Baseline komplett regeneriert** — 130 Einträge gegen
  v2-Inferenz, ersetzt die Phase-2-Baseline (~200 v1-Einträge,
  davon viele Duplikate). Phase-4-Plan „Larastan-Baseline aktiv
  abbauen" gewinnt damit eine präzise Liste.
- **PHP 8.2 → 8.3.** `composer.json` `require.php` von `^8.2` auf
  `^8.3`. Container-Build neu unter `docker/8.3/`. CI-Workflow auf
  allen vier Jobs auf PHP 8.3. Ubuntu noble und Node 22 bleiben aus
  dem 8.2-Sprung stehen, ebenso der `chmod 0644`-Fix für die
  `99-sail.ini` und der `storage:link`-Auto-Setup. Sprung verlief
  ohne Code-Eingriff, kein Refactor nötig.
- **PHP 8.1 → 8.2.** `composer.json` `require.php` von `^7.3|^8.0` auf
  `^8.2`. Container-Build neu unter `docker/8.2/`. CI-Workflow auf allen
  vier Jobs (Pest, composer audit, Larastan, Pint) auf PHP 8.2.
- **Ubuntu 22.04 jammy → 24.04 noble.** Im selben Container-Sprung. Die
  PPA-Quelle `ondrej/php` zieht jetzt aus dem noble-Pool. Defensive
  Vorbereitung: `ubuntu`-User mit UID 1000 wird vor dem `sail`-User-
  Anlegen entfernt, weil noble den User per Default mitliefert und es
  sonst zur GID-Kollision käme.
- **Node 20 LTS → Node 22 LTS.** NodeSource-Setup-Script auf
  `setup_22.x`. Node 20 ist seit Oktober 2025 in Maintenance, Node 22
  ist die aktive LTS bis Oktober 2027.
- **`storage:link` läuft automatisch beim Container-Start.**
  `docker/8.2/start-container` prüft idempotent, ob
  `public/storage` als Symlink existiert, und legt ihn sonst an.
  Ohne den Symlink bricht jeder File-Upload mit „failed to upload" —
  beim PHP-8.2-Image-Rebuild verschwand der Symlink stillschweigend,
  weil Composer keinen Post-Install-Hook für `storage:link` rufen
  kann. Ab jetzt selbstreparierend.

### Anmerkung zur Verifikation

Pest 58 grün auf PHP 8.2, Larastan stabil, Pint grün, Coverage über
25 %. Spatie-Activity-Stub-Lücken (`$created_at`) sind in der
Larastan-Baseline aufgenommen — keine eigene Sache von uns, gehört zu
Spatie-Phase-4-TODO.

### Hinzugefügt

- `app/Http/Requests/StoreImageBlockRequest.php` und
  `app/Http/Requests/StoreAudiovisualRequest.php` als dedizierte
  FormRequests für die Image- und Audio-/Video-Upload-Routen.
- Sechs neue Pest-Tests in `tests/Feature/AuthorizationTest.php`
  decken die Upload-Härtung, den Mass-Assignment-Schutz auf
  `Project.user_id` und den Owner-Check vor Drag&Drop ab. Suite
  jetzt 46 grün.

### Geändert

- **Upload-Routen** in `ContentController::saveImage` und
  `AudiovisualController::store` laufen über die neuen
  FormRequests mit MIME-Whitelist (jpeg, jpg, png, gif, webp für
  Bilder; mp3, mp4, wav, ogg, m4a für Audio) und Size-Limit (4 MB
  für Bilder, 20 MB für Audio).
- **`AudiovisualController::uploadAudio()`** generiert den
  Dateinamen jetzt durchgängig per `Str::random(10)`. Der
  vorherige `getClientOriginalName()`-Zwischenwert war ein
  Path-Traversal-Vektor.
- **`UploadTrait::uploadOne()`** prüft den `disk`-Parameter
  gegen eine Whitelist (`public`). Defensive Schicht für künftige
  Aufrufer — die Disk-Wahl darf nie aus Request-Daten kommen.
- **Drag&Drop-Reorder-Route** (`POST /drag`) prüft jetzt via
  Project-Policy, ob der eingeloggte User Owner oder Admin des
  Ziel-Projekts ist. Routen-Layout sonst unverändert; die
  Zerlegung in drei dedizierte Reorder-Endpunkte (chapter,
  entry, content) bleibt Refactoring-Material.

- Acht Happy-Path-Tests in `tests/Feature/HappyPathTest.php` —
  Project / Chapter / Entry / Text-Block / Image-Block /
  Audio-Upload, dazu Admin-Invitation mit Notification-Dispatch
  und Permission-Cascade über die Editor-Rolle. Erweitert die
  Pest-Suite von 46 auf 54 grüne Tests. Sicherheitsnetz für den
  bevorstehenden Major-Upgrade-Sprung — die Authorization-Suite
  prüft „darf der User das?", diese Suite prüft „macht die App
  das, was sie soll?".
- Test-Helper `makeProject`, `makeChapter`, `makeEntry` liegen
  zentral in `tests/Pest.php` und sind über Feature-Suites
  hinweg verfügbar.
- **PCOV** als Coverage-Driver im PHP-Container (`php8.1-pcov`
  im Dockerfile, `pcov.directory = /var/www/html/app` in der
  `php.ini`). Aktiv nur bei `--coverage` — kein Overhead im
  normalen App-Lauf. Composer-Script `composer test-coverage`
  läuft `pest --coverage --min=25` und schlägt fehl, wenn die
  Coverage unter 25 % fällt. Aktueller Stand: **26.68 %** — die
  Schwelle ist eng angesetzt, damit Coverage-Verlust durch
  Phase-3-Refactorings im CI sofort sichtbar wird.

### Sicherheit

- **Upload-Härtung in den Image- und Audio-Routen**
  ([`3b69353`](https://github.com/berlinHistory/crowdCuratio/commit/3b69353)).
  Vorher liefen `POST /image/store` und `POST /save-audiovisual`
  ohne MIME- oder Size-Validation. Ein eingeloggter User konnte
  beliebige Dateitypen hochladen — ausführbare Dateien wären als
  zufällig benannte Files in der `public`-Disk gelandet, mit
  potenzieller Wirkung je nach Web-Server-Konfiguration.
- **Mass-Assignment-Schutz für `Project.user_id`**
  ([`3b69353`](https://github.com/berlinHistory/crowdCuratio/commit/3b69353)).
  Die Spalte ist nicht mehr in `Project::$fillable` — auch wenn
  ein Request `user_id` mitsenden würde, kann sie nicht über
  Mass-Assignment ins Modell wandern. Der Controller setzt
  `user_id` ausschließlich aus `Auth::user()->id`.
- **Owner-Check vor Drag&Drop-Reorder**
  ([`3b69353`](https://github.com/berlinHistory/crowdCuratio/commit/3b69353)).
  Bis zum Fix konnte jeder eingeloggte User Chapter, Entries und
  MediaContent in fremden Projekten umsortieren oder zwischen
  Chaptern verschieben — die Route war nur durch
  `auth`-Middleware geschützt, ohne Project-Eigentums-Check.

## [0.9.0] — 2026-05-30 — Sicherheitsnetz

Erste Modernisierungs-Welle nach der Repo-Übernahme. CI-Schicht,
40 Pest-Tests, Authorization über Laravel-Policies, dedizierte
FormRequests für alle mutierenden Routen, Mass-Assignment-Schutz für
privilegierte Felder, Härtung von Docker-Stack und Datenbank-Layer.
Vier in der initialen Tiefenanalyse identifizierte Sicherheits-Blocker
geschlossen, plus ein Privilege-Escalation-Hotfix gegen die
Registrierungs-Route.

### Hinzugefügt

- `CHANGELOG.md` als verbindliche Änderungsspur.
- `composer.lock` wird ab sofort committet — Reproduzierbarkeit
  und `composer audit`-Baseline möglich.
- **CI-Schicht auf GitHub Actions** (`.github/workflows/ci.yml`):
  sechs parallele Jobs auf jedem PR und Push nach `main` — Pest
  gegen SQLite-in-memory, `composer audit`, `npm audit`, Larastan,
  Pint und ein Changelog-Diff-Check, der erzwingt, dass jeder PR
  den Changelog berührt (mit Opt-out via Label `skip-changelog`).
- **Larastan ^1.0** (Laravel-8-kompatibel) mit `phpstan.neon` auf
  Level 5 und `phpstan-baseline.neon` für die Bestandsbefunde —
  neue Verstöße brechen den Build.
- **Laravel Pint** im Laravel-Preset, Hard-Fail im CI. Baseline-
  Sweep über die gesamte Codebasis als isolierter Style-Commit,
  dessen SHA in `.git-blame-ignore-revs` steht — `git blame`
  springt über die Whitespace-/Brace-/Import-Änderungen hinweg.
- **Dependabot** für Composer, npm und GitHub Actions (wöchentlich).
  Major-Bumps für Laravel, Spatie-Pakete, axios, alpine, tailwind
  und Mix sind bewusst ausgenommen — sie gehören in den
  koordinierten Upgrade-Sweep.
- **Pest-Suite mit 40 Tests** — Authorization-Bypass-Szenarien für
  Project / Chapter / Entry, Create-Pfad-Owner-Checks,
  FormRequest-Pflichtfeld-Tests, MIME-Whitelist-Test für das
  Project-Logo, PATCH-Sanity-Tests für Chapter und Entry,
  Pfad-Schutz für die Registrierung.
- **Laravel-Policy-Schicht** für Project, Chapter und Entry
  (`app/Policies/`), inklusive `createIn`-Methode für den
  Owner-Check beim Anlegen.
- **`App\Support\PermissionName`** zentralisiert die sieben
  Permission-Strings (`view`, `add`, …) als public-Konstanten —
  Seeder, Policies und Tests nutzen die Konstanten.
- **Sieben FormRequest-Klassen** unter `app/Http/Requests/`:
  `StoreChapterRequest`, `UpdateChapterRequest`,
  `StoreEntryRequest`, `UpdateEntryRequest`, `StoreProjectRequest`,
  `UpdateProjectRequest`, `Auth\RegisterRequest`. Jede delegiert
  `authorize()` an die zuständige Policy und definiert `rules()`
  mit Standard-Validation.
- **Console-Command `db:audit-fk`**
  (`app/Console/Commands/AuditForeignKeys.php`): Read-only-Default
  produziert eine Markdown-Tabelle mit Orphan-Foreign-Keys
  (`texts.origin`, `texts.copyright` gegen `sources.id`). Der
  `--fix --confirm`-Pfad schreibt vorher ein JSON-Protokoll nach
  `storage/logs/` und setzt orphan-Werte transaktional auf NULL.
- **`database/seeders/RoleTableSeeder.php`** legt drei
  Default-Rollen an (Editor, Reviewer, Reader) — der
  User-Invitation-Workflow läuft im Standard-Setup wieder durch.
- **`docs/smoke.md`** als belastbares Baseline-Inventar — zehn
  manuell verifizierte Haupt-Pfade von Login bis Invitation-Flow.
- **`doctrine/dbal ^3`** als Require — wird für `Schema::dropColumn`
  benötigt, das in SQLite (CI-Pfad) und in Produktions-Migrations
  durch den Doctrine-Schema-Manager läuft.

### Geändert

- **PHP 8.0 → PHP 8.1**, Ubuntu 20.04 → 22.04 (`jammy`),
  Node 15 → Node 20 LTS, `dompdf/dompdf ^1.2` → `^2.0` (acht
  Security-Advisories in 1.2.x). Container-Build neu unter
  `docker/8.1/`.
- **Datenbank-Layer:** Charset auf `utf8mb4` (vorher `utf8mb3`),
  `strict = true` aktiviert — Zero-Dates, GROUP-BY-Verstöße und
  Inserts ohne Pflichtfelder werfen ab sofort hörbar Fehler statt
  still durchzulaufen.
- **`docker-compose.yml`:** Image-Tags gepinnt (`mysql:8.0`,
  `redis:7-alpine`, `getmeili/meilisearch:v1.6`, `phpmyadmin:5.2`,
  `axllent/mailpit:v1.20`). Mailhog durch Mailpit ersetzt, das
  `selenium`-Image entfernt (kein arm64-Support). Healthchecks
  für meilisearch und mailpit, MySQL-Healthcheck mit Root-
  Credentials (vorher lieferte `mysqladmin ping` unter MySQL 8
  ein `Access denied`, das Docker als „läuft" fehlinterpretierte).
  `restart: unless-stopped` auf mysql, redis, meilisearch,
  mailpit. Forward-Ports von mysql, redis, meilisearch und dem
  mailpit-Dashboard an `127.0.0.1` gebunden (nur der SMTP-Port
  von mailpit bleibt offen, weil der App-Container ihn intern
  erreicht).
- **Dockerfile:** Composer aus dem offiziellen `composer:2`-Image
  übernommen statt `curl http://...`-Pipe — deterministische
  Version, signierte Distribution. `apt-key` durch
  `signed-by`-Keyrings unter `/etc/apt/keyrings` ersetzt
  (`apt-key` ist seit Ubuntu 22.04 deprecated und in 24.04
  entfernt). `EXPOSE 80` statt `EXPOSE 8000` — Compose mappte
  ohnehin auf Container-Port 80.
- **`.env.example`:** Sail-taugliche Defaults (`DB_HOST=mysql`,
  `REDIS_HOST=redis`, `MAIL_HOST=mailpit`, `MAIL_FROM_ADDRESS`
  vorbelegt). `ADMIN_*`-Variablen für den Admin-Seeder
  dokumentiert. `WWWUSER` / `WWWGROUP` als kommentierter Hinweis
  für Linux-Hosts mit abweichender UID/GID. `APP_DEBUG`-
  Warnkommentar — Stacktraces dürfen nicht in Produktion.
- **`CreateAdminUserSeeder`** liest `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` / `ADMIN_NAME` / `ADMIN_LAST_NAME` aus dem
  Environment, bricht beim Fehlen mit `RuntimeException` ab,
  idempotent (`firstOrCreate`).
- **`DatabaseSeeder`** ruft jetzt `PermissionTableSeeder` →
  `RoleTableSeeder` → `CreateAdminUserSeeder` in dieser
  Reihenfolge. `PreviewSeeder` bleibt manuell.
- **Authorization über Policies:** `ProjectController`,
  `ChapterController`, `EntryController` rufen
  `$this->authorize(...)` in allen mutierenden Methoden auf.
  Views nutzen `Auth::user()->can('update', $project)` statt
  Custom-Gates.
- **PATCH-Route-Trennung** für Chapter und Entry: Update läuft
  jetzt über `PATCH /chapters/{chapter}` bzw.
  `PATCH /entries/{entry}` mit Route-Model-Binding, statt über
  POST mit `$request['chapterId']`-Verzweigung im Controller.
  Das zugehörige Frontend-JS in `chapters/index.blade.php`
  zieht per `_method`-Hidden-Field mit.
- **Eloquent-Hygiene:**
  - `Model::preventLazyLoading()` ausserhalb der Produktion —
    N+1-Pattern werfen in Dev, Tests und CI sofort eine
    `LazyLoadingViolationException`. Fünf Controller-Pfade laden
    `Project` jetzt mit explizitem `with(...)`-Baum.
  - Drei Local Scopes auf dem `Project`-Model
    (`withEditTree`, `withPreviewTree`, `withCopyrightTree`)
    konsolidieren die Eager-Loading-Bäume.
  - Explizite `$casts` auf `Chapter`, `Entry`, `User` für
    `is_translated`, `welcome_valid_until`, `is_admin` und
    `create_project`.
  - `Role::where('id', 'not like', '1')` an vier Stellen durch
    `Role::where('name', '!=', 'Admin')` ersetzt — LIKE auf
    INT-Spalte mit hardkodierter Admin-ID war semantisch schief.
- **File-Upload-Validation** in `StoreProjectRequest` und
  `UpdateProjectRequest`: `project_image` als File mit
  MIME-Whitelist (jpeg, jpg, png, gif, webp) und 4 MB Limit.
- **`Validator::make` in `RegisteredUserController`** durch
  `RegisterRequest` ersetzt.
- **Pint-Baseline-Sweep** über die gesamte Codebasis (isolierter
  Style-Commit). Pest-Suite vor und nach dem Sweep identisch
  grün.
- **`.gitignore`:** `composer.lock` ist jetzt eingecheckt,
  `.DS_Store` und Smoke-Artefakte ignoriert.

### Behoben

- **Foto-Upload-Anzeige für Project-Logos und Image-Blöcke:**
  die `image`/`audio`-Routen liefen gegen die Default-Disk
  `local`, während Uploads auf der `public`-Disk landen.
  Wechsel auf `Storage::disk('public')->response(...)` rendert
  hochgeladene Bilder wieder.
- **User-Invitation-Workflow:** Default-Rollen fehlten,
  `MAIL_*`-Defaults waren leer. Mit dem Role-Seeder und
  vernünftigen Mail-Defaults läuft der Einladungs-Flow inkl.
  Welcome-Mail wieder durch.
- **`drop_foreign_key_table`-Migration** lief auf frischer DB
  in MySQL-Fehler 1091, weil sie Spalten droppte, die
  `create_texts_table` / `create_image_table` nie angelegt
  hatten. Jetzt mit `Schema::hasColumn`-Guard.
- **Drei Migrations mit fehlenden oder destruktiven
  `down()`-Operationen** gehärtet:
  - `add_welcome_valid_until_field_to_users` hatte keine
    `down()`, jetzt mit `dropColumn`-Guard.
  - `customize_has_permissions_table::down()` droppte die
    falsche Spalte (`project_id` statt `user_id`).
  - `convert_texts_to_innodb::down()` ist jetzt eine
    `RuntimeException` — eine Rück-Konvertierung auf MyISAM
    würde die Foreign-Key-Constraints still verwerfen und ist
    ehrlich verboten statt scheinbar funktionsfähig.
- **`ChapterController::update` und `EntryController::update`**
  gaben bisher `return $this;` zurück — die Versuche, die
  Controller-Instance als Response zu serialisieren, wären als
  `TypeError` hochgegangen. Korrigiert zu `return back();`.
- **DB-Defaults** für `users.is_admin`, `users.create_project`
  und `users.last_name`, plus explizite `position`-Werte im
  `PermissionTableSeeder` — alles latente Schema-Lücken, die
  der `strict = true`-Modus sichtbar gemacht hat.

### Entfernt

- **Sechs tote Legacy-Auth-Controller** (`LoginController`,
  `RegisterController`, `ConfirmPasswordController`,
  `ForgotPasswordController`, `ResetPasswordController`,
  `VerificationController`). Referenzierten
  `Illuminate\Foundation\Auth\*`-Traits, die in Laravel 8+
  nicht mehr existieren, und waren seit dem Breeze-Umzug
  ohne Caller. Auth läuft jetzt durchgängig über die
  Breeze-Klassen.
- **Tote PHP-7.4-Build-Variante** `docker/7.4/` — nach dem
  Umzug auf 8.1 von keiner Compose-Datei mehr referenziert.
- **`selenium`-Image** aus dem Compose-Stack (kein arm64-
  Support).
- **`app/Traits/SourceTrait.php`** plus seine zwei Aufrufer im
  `ContentController`. Der Trait hatte genau eine Methode
  (`checkMeta`), die nirgends aufgerufen wurde; ihre Signature
  (required-Parameter nach optional) war seit PHP 8.0
  deprecated und ab 8.4 fatal.
- **Tote Image-Preview-Route** `/image/{file}/preview`, die
  den Storage-Disk-Fix nie mitbekommen hatte und ohne Caller
  im Code stand.
- **Fünf Custom-Gate-Closures aus `AuthServiceProvider::boot`**
  (`edit-`, `add-`, `delete-`, `publish-`, `comment-project`).
  Die Owner-Logik war semantisch schief (`$user->id === $project`).
  View-Aufrufe in `chapters/index.blade.php` (zehn Stellen) auf
  die Project-Policy umgehängt.
- **Vier redundante `whereNull('deleted_at')`-Aufrufe** in
  Eloquent-Queries (`ChapterController`, `ProjectController`,
  `CommentRetrieve`). Models nutzen durchgängig `SoftDeletes`,
  der Default-Scope schließt trashed Rows implizit aus.
- **Stock-Breeze-Tests** für das Self-Service-Signup-Modell, das
  crowdCuratio nicht hat (`tests/Feature/RegistrationTest`, drei
  `ExampleTest`-Stubs).
- **Self-Service-Registrierungs-Routen** in `routes/auth.php`
  (`GET` und `POST /register` mit `guest`-Middleware). Lebten
  parallel zur Admin-Registrierung in `routes/web.php` und
  stifteten Verwirrung. crowdCuratio kennt keinen Gast-
  Registrierungs-Pfad — neue User werden nur durch Admins
  eingeladen.
- **`.idea/`-Tracking**: PHPStorm-Workspace-State bleibt lokal,
  wandert nicht mehr ins Repo.

### Sicherheit

- **Authorization-Bypass über direkte HTTP-Aufrufe** geschlossen
  ([`7ce63dc`](https://github.com/berlinHistory/crowdCuratio/commit/7ce63dc),
  [`6a213e2`](https://github.com/berlinHistory/crowdCuratio/commit/6a213e2)).
  Project-, Chapter-, Entry-Mutationen prüfen sowohl in der
  Controller-Action als auch in der View, ob der eingeloggte User
  Eigentümer oder Admin ist. Bisher reichte ein direkter HTTP-
  Aufruf gegen die Update-/Destroy-Routen, um fremde Daten zu
  ändern. Belegt durch die Pest-Suite (13 Authorization-Bypass-
  Szenarien grün).
- **Create-Pfad-Bypass für Chapter und Entry** geschlossen
  ([`f586d56`](https://github.com/berlinHistory/crowdCuratio/commit/f586d56)).
  Die Update-/Destroy-Tests hatten den Bypass beim Anlegen
  übersehen — jeder eingeloggte User konnte Chapter und Entry in
  fremden Projekten erzeugen. Neue `createIn`-Policy-Methode plus
  vier Pest-Tests.
- **Logo-Upload-Validation und Path-Traversal-Pfad** geschlossen
  ([`871f6d0`](https://github.com/berlinHistory/crowdCuratio/commit/871f6d0)).
  `ProjectController::update` las `$request['logo']` blind und
  schrieb den Wert in die DB — ein Path-Traversal-Vektor. Logo
  kommt jetzt ausschließlich aus der validierten Upload-Routine,
  `project_image` wird als File mit MIME-Whitelist und 4 MB Limit
  validiert.
- **Privilege-Escalation über `POST /register` geschlossen**
  ([`81055ac`](https://github.com/berlinHistory/crowdCuratio/commit/81055ac)).
  Bis zum Hotfix konnte jeder eingeloggte User (Reader, Reviewer,
  Editor) `POST /register` mit `adminUser=1` aufrufen und sich
  ein Admin-Konto anlegen — `User::$fillable` enthielt `is_admin`
  und `create_project`, der FormRequest war ohne Authorization,
  die Route hatte keinen Rollenfilter. Defense-in-depth-Fix:
  privilegierte Felder aus `User::$fillable` raus, Route hängt
  an `role:Admin`, der Controller setzt die Felder zusätzlich
  nur, wenn der Caller selbst die Admin-Rolle hat. Vier neue
  Pest-Tests sichern den Pfad ab (non-Admin → 403, Admin mit
  `adminUser=1` → neuer Admin, Admin ohne `adminUser` →
  regulärer User, Gast → Login-Redirect).
- **MyISAM → InnoDB für die `texts`-Tabelle**
  ([`5ae90c2`](https://github.com/berlinHistory/crowdCuratio/commit/5ae90c2)).
  Engine-Konvertierung plus Reinstall der Source-Foreign-Keys,
  die unter MyISAM still verworfen wurden — Datenintegrität für
  Quellenangaben wieder gewährleistet.
- **`facade/ignition`-RCE (CVE-2021-3129)** entschärft: durch
  `composer install` mit Lock zieht der Build die geprüfte
  Version 2.17.7 ein, nicht die anfälligen 2.5.0/.1.
- **Charset auf `utf8mb4`** (vorher `utf8mb3`): 4-Byte-Glyphen
  (Emoji etc.) werden ab sofort gespeichert statt gestrippt.
- **MySQL `strict`-Mode** an: zero-dates, GROUP-BY-Verstöße,
  Inserts ohne Pflichtfelder werfen ab sofort hörbar Fehler
  statt still durchzulaufen.
- **File-Upload-Disk auf `public`** umgestellt — File-URLs
  funktionieren, Lateral-Movement-Fläche im lokalen Dev-Netz
  reduziert. Mailpit-Web-Dashboard und phpMyAdmin nur noch auf
  Loopback erreichbar.
- **CVE-2025-27515 — Laravel File Validation Bypass**
  (Severity moderate, betrifft `laravel/framework < 10.48.29`).
  crowdCuratio läuft auf Laravel 8.12 und ist formal in der
  Range. Konkret nicht ausnutzbar: der Angriffspfad braucht eine
  Wildcard-Validation der Form `files.*|image|mimes:…`, die im
  Code aktuell nirgends auftaucht (grep über `app/` ohne
  Treffer). Endgültig zu mit dem Laravel-9-Sprung im Upgrade-
  Pfad.
- **Bekannte offene Lasten aus `composer audit`:** zwei
  abandoned Pakete. `swiftmailer/swiftmailer` (transitive
  Abhängigkeit aus Laravel 8) fällt mit dem Laravel-9-Sprung
  automatisch raus, weil Symfony Mailer übernimmt.
  `laravelcollective/html` braucht einen aktiven Ersatz und ist
  für die Refactoring-Welle vorgemerkt.

---

## [0.8.0] — 2026-05-28 — Übernahme-Baseline

Ausgangspunkt der Modernisierung. Stand des Initial Commit zum
Repo-Übernahmezeitpunkt.

### Funktionsumfang (Stand des Initial Commit)

- Kuratierungs-Hierarchie: Project → Chapter → Entry → Text/Image/Audiovisual/Gallery.
- Quellen- und Copyright-Verknüpfung via `sources`.
- Mehrsprachigkeit über `spatie/laravel-translatable`.
- Rollen, Permissions und projektbezogene Permissions
  (`spatie/laravel-permission` + Custom-Erweiterung `user_has_permissions`).
- Kommentare (polymorph, Threads über `parent_id`), Status-Feld.
- Activity-Log (`spatie/laravel-activitylog`).
- Einladungs-Flow (`spatie/laravel-welcome-notification` +
  eigene `invitations`-Tabelle).
- PDF-Export (dompdf und mpdf parallel installiert).
- Pro Projekt: Impressum, AGB, Datenschutzerklärung, Mail-Einstellungen.

### Stack (Stand des Initial Commit)

- PHP `^7.3 | ^8.0` (Docker-Build: PHP 8.0).
- Laravel 8.12.
- Tailwind 2, Alpine 2.7, Laravel Mix 6.
- MySQL 8 via Sail, Redis, Meilisearch, Mailhog, Selenium im Compose.
- Tests: Stock-Breeze-Auth-Tests, keine Fachtests.

### Bekannte Lasten

- PHP 8.0 und Laravel 8.12 sind End-of-Life.
- `composer.lock` in `.gitignore`.
- Fat-Controller (`ProjectController` ~1.086 LoC, `ContentController` ~822 LoC).
- Zwei PDF-Libraries parallel.
- Eigene Permission-Modell-Variante weicht vom Spatie-Standard ab.

---

[Unreleased]: https://github.com/berlinHistory/crowdCuratio/compare/v0.9.0...HEAD
[0.9.0]: https://github.com/berlinHistory/crowdCuratio/releases/tag/v0.9.0
[0.8.0]: https://github.com/berlinHistory/crowdCuratio/releases/tag/v0.8.0
