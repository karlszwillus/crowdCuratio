# Architektur — crowdCuratio

Diese Datei beschreibt die wichtigen Architektur-Strukturen der
Anwendung. Sie ist für Entwickler gedacht, die am Code arbeiten.

Inhalt:

1. [Domänenmodell](#domänenmodell)
2. [Authorization-Modell](#authorization-modell)
3. [Service-Layer](#service-layer)
4. [Routing- und Controller-Schichtung](#routing--und-controller-schichtung)
5. [Test-Pyramide](#test-pyramide)
6. [Was nicht in dieses Dokument gehört](#was-nicht-in-dieses-dokument-gehört)

---

## Domänenmodell

Die Kerndomäne ist eine vier-stufige Hierarchie für gemeinsam
kuratierte Wissens-Projekte:

```
Project ─┬─ Chapter ─┬─ Entry ─┬─ MediaContent (Pivot)
         │           │         │     ├─ content:  Text | Image | Gallery | Audiovisual
         │           │         │     └─ parent:   Entry
         │           │         └─ Entry-Comments
         │           └─ Chapter-Comments
         └─ Project-Comments
```

**Konventionen:**

- **Project** ist die Wurzel. Hat einen `user_id`-Owner. Hat
  `project_user_permissions`-Pivot für Eingeladene mit
  per-Project-Permission (Spatie-Permission v6, project-scoped via
  `ProjectPermissionService::userHasPermissionOnProject`).
- **Chapter** gehört zu genau einem Project (`project_id`).
- **Entry** gehört zu genau einem Chapter (`chapter_id`).
- **MediaContent** ist der Pivot zwischen Entry und den vier
  Content-Modellen. Polymorph aufgebaut: `content_id`/`content_type`
  für das verlinkte Content-Modell, `parent_id`/`parent_type` für
  den Parent (heute durchgehend Entry).
- **Gallery** hat zusätzlich `Image::gallery_id`-FK für die
  enthaltenen Bilder. Images hängen also nicht direkt am
  MediaContent-Pivot, sondern über ihre Gallery.
- **Comment** ist polymorph (`commentable_type`/`commentable_id`)
  und kann an Project, Chapter, Entry, Text, Image, Gallery,
  Audiovisual oder MediaContent hängen.

**Navigation zum Project:**

Jedes Content-Modell hat eine `project()`-Methode, die zum
Project-Root navigiert. Pattern:

```php
// Text::project() / Audiovisual::project() / Gallery::project()
public function project(): ?Project
{
    $pivot = $this->mediaContents()->first();
    return $pivot?->parent?->chapter?->project;
}

// Image::project() (indirekt via Gallery)
public function project(): ?Project
{
    return $this->gallery?->project();
}
```

`CommentService::resolveProjectForComment(int $commentId): ?Project`
fasst diese Pfade zusammen — wird von den `setCommentStatus*`-
Endpunkten genutzt, weil deren Route keinen `{project}`-Param hat.

---

## Authorization-Modell

Mehrschichtig, mit klarer Trennung zwischen globalen und
project-scoped Pfaden.

### Schichten

1. **Auth-Middleware** auf den Routes (`auth`) — der Endbenutzer
   ist eingeloggt.
2. **FormRequest-`authorize()`** für Schreibpfade. Sie laden im
   Idealfall das Modell und gaten project-scoped. Wenn das nicht
   möglich ist (Multi-Endpoint-Request wie `StoreCommentRequest`),
   prüft der Request nur Auth, und der Controller gate't separat.
3. **Controller-`$this->authorize(...)`** auf dem geladenen
   Modell — Policy entscheidet project-scoped.
4. **Service-Layer** baut nur fachliche Operationen, keine
   Auth-Logik. Außer dort, wo der Service ein Modell auflöst, das
   in der Route nicht direkt verfügbar ist
   (`CommentService::resolveProjectForComment`).

### Policies

Alle Modell-Policies erben von `OwnerScopedPolicy`. Sie bietet
zwei Pflicht-Methoden:

- **`before(User $user, string $ability): ?bool`** — Admin-Shortcut:
  ein User mit `RoleName::ADMIN`-Rolle darf alles auf seiner
  Policy. Returns `true`/`null` (kein Deny).
- **`check(User $user, ?Project $project, PermissionName $permission): bool`**
  — delegiert an `ProjectPermissionService::userHasPermissionOnProject`,
  der zuerst auf Owner-Shortcut prüft (`$user->id ===
  $project->user_id`), dann auf project-scoped Permission via
  `project_user_permissions`-Pivot.

Die konkreten Policies (`ProjectPolicy`, `ChapterPolicy`,
`EntryPolicy`, `TextPolicy`, `ImagePolicy`, `GalleryPolicy`,
`AudiovisualPolicy`) implementieren `view`/`update`/`delete`/
`comment` (und je nach Modell `create`/`createIn`/`publish`).

### Permission-Namen und Spatie

Permissions liegen in `App\Support\PermissionName` als Backed-Enum
(`view`, `add`, `edit`, `delete`, `publish`, `comment`, `invite`).
Sie werden in der Spatie-Tabelle `permissions` geseedet und via
`HasRoles`-Trait an User verteilt.

**Wichtig:** In `config/permission.php` ist
`register_permission_check_method` auf `false` gesetzt. Damit
registriert Spatie das App-weite `Gate::before` _nicht_, das
sonst project-scoped Policies kurzschließen würde
(`checkPermissionTo($ability)` läuft global, ignoriert das
Modell-Argument). Permission-Namen wie `view`/`delete`/`comment`
kollidieren strukturell mit Policy-Methoden-Namen — der
Off-Schalter ist deshalb Pflicht.

**Konsequenz:**

- **Modell-scoped Auth** läuft über `$user->can('view', $project)`
  / `@can('view', $project)` / `$this->authorize('view',
  $project)`. Policy + Owner-Shortcut + Permission-Pivot.
- **Globale Permission-Checks** (z. B. „darf User überhaupt
  Projects anlegen?") gehen über
  `$user->hasPermissionTo(PermissionName::ADD->value)` bzw. das
  Blade-Directive `@hasPermissionTo('add')`. Direkter Trait-Zugriff
  ohne Gate-Roundtrip.

### Admin-Pfad

`$user->hasRole(RoleName::ADMIN->value)` — Spatie-Role. Greift in
jeder Policy via `before()`. Plus die Permission-Verwaltung
(`UserController`, `RoleController`) ist hinter
`hasPermissionTo(...)` gegated.

---

## Service-Layer

Controller bleiben dünn; fachliche Operationen wandern in Services.
Konvention: jeder Service deckt ein Modell oder eine Klasse von
Operationen ab.

### Aktive Services

| Service                        | Verantwortung                                                                                                |
|--------------------------------|--------------------------------------------------------------------------------------------------------------|
| `TextService`                  | Text-Modell create/update/destroy, attachToEntry/detachFromEntries.                                          |
| `ImageService`                 | Image-Upload, Storage-Operationen, Soft-Delete-Pfad.                                                         |
| `GalleryService`               | Gallery-Modell create/update/destroy, attachToEntry/detachFromEntries.                                       |
| `AudiovisualService`           | Audio-Upload + YouTube-URL-Normalisierung, attachToEntry/destroy.                                            |
| `CommentService`               | Comment-Schreibpfade (add, reply, edit, delete, setStatus). `resolveProjectForComment` als Auth-Helfer.      |
| `CommentRetrieve`              | Comment-Lesepfade (eager-loaded Trees pro commentable).                                                      |
| `ContentReorderService`        | Drag-and-Drop-Reorder für Chapter, Entry, MediaContent. `resolveProject` zentralisiert die Auth-Auflösung.   |
| `ProjectPermissionService`     | Project-scoped Permission-Logik: `userHasPermissionOnProject`, `listProjectsForUser`, `setForUserOnProject`. |
| `ProjectImageService`          | Project-Logo-Upload + Filename-Resolution mit server-generiertem Dateinamen.                                  |
| `SourceService`                | Source-Modell für Origin/Copyright (`findOrCreateId`).                                                        |
| `UserService`                  | User-spezifische Helfer für Permission-View-Generation (Edit-Maske).                                         |
| `LogService`                   | Activity-Log-spezifische Joins für die Project-Edit-Maske.                                                   |

### Konventionen pro Service

- **Constructor-Injection** mit `private readonly`:
  `public function __construct(private readonly TextService $texts) {}`.
- **Methoden-Naming:** `create($data, $parentId)`, `update($model, $data)`, `destroy($model)`,
  `attachToEntry($modelId, $entryId)`, `detachFromEntries($modelId)`.
- **Service-Ergebnisse sind Modelle**, keine DTOs.
- **Keine Auth-Logik in Services** — außer in
  `resolveProjectForComment` und
  `ContentReorderService::resolveProject`, die Hilfsdaten für
  Controller-Auth liefern.

---

## Routing- und Controller-Schichtung

`routes/web.php` hat zwei klar separierte Blöcke:

1. **Öffentliche Routen** (`/login`, `/register`, `auth.policy`,
   `auth.terms`) — von `auth`-Middleware nicht abgedeckt.
2. **Auth-geschützte Routen** in `Route::group(['middleware' =>
   'auth'], ...)`. Hier liegen alle Curating-Endpunkte.

**Controller-Verantwortung:**

| Controller                | Hauptverantwortung                                              |
|---------------------------|-----------------------------------------------------------------|
| `ProjectController`       | Project-CRUD, Preview, History, Permission-Verwaltung.          |
| `ChapterController`       | Chapter-CRUD + Comments + Drag-and-Drop.                        |
| `EntryController`         | Entry-CRUD + Comments + Translation.                            |
| `ContentController`       | Text/Image/Gallery-CRUD + Comments + Translation-Helfer.        |
| `AudiovisualController`   | Audiovisual-CRUD + Comments.                                    |
| `UserController`          | User-Verwaltung (Permission-View, Invitation).                  |
| `RoleController`          | Role-CRUD.                                                      |
| `RegisteredUserController`| Self-Registration.                                              |

**Authorize-Sweep-Konvention:** Wenn ein Refactor einen Controller
anfasst, gehört zur Akzeptanz ein expliziter Sweep über _alle_
public Methoden des Controllers mit `authorize()`-Pflicht-Check pro
Methode. Im selben Refactor die semantisch nachbarschaftlichen
Controller mit-durchsweepen.

---

## Test-Pyramide

| Schicht                        | Wo                                            | Was                                                              |
|--------------------------------|-----------------------------------------------|------------------------------------------------------------------|
| **Unit / Service-Tests**       | `tests/Feature/Services/*`                    | TextService, AudiovisualService, GalleryService, CommentService. |
| **Model-Tests**                | `tests/Feature/Models/*`                      | MediaContent-Morph-Beziehungen, Content-Project-Navigation.      |
| **Policy-Tests**               | `tests/Feature/Policies/*`                    | OwnerScopedPolicy-Vererbung pro Content-Modell.                  |
| **Controller-Auth-Tests**      | `tests/Feature/Http/*AuthorizationTest.php`   | Pinning für Sicherheits-Sweeps.                                  |
| **Happy-Path-Tests**           | `tests/Feature/HappyPathTest.php`             | Login → Project anlegen → Bild-Upload → PDF-Export.              |
| **CI-Coverage-Schwelle**       | `phpunit.xml`, `.github/workflows/ci.yml`     | Hard-Fail bei < 55 %.                                            |

**Test-Konventionen:**

- **`forgetCachedPermissions()` in jedem Auth-Test-`beforeEach`** —
  sonst läuft der Test gegen einen kalten Spatie-Cache und kann
  Bugs maskieren (`checkPermissionTo` throw't dann
  `PermissionDoesNotExist`, Laravel interpretiert das als false,
  Tests werden fälschlich grün).
- **Charakterisierungs-Tests vor strukturellen Eingriffen** —
  Service-Extraktionen entstehen mit Pinning-Tests im gleichen
  Branch.

---

## Was nicht in dieses Dokument gehört

- **PDF-Export-Pipeline.** Aktuell zwei Engines (dompdf + mpdf)
  parallel. Konsolidierung in eigener Iteration.
- **Asset-Storage-Strategie** (`Storage::disk('public')`-Pfade,
  Upload-Targets).
- **Frontend-Build-Pipeline** (Mix → Vite, Tailwind-Versions-Sprung).
- **utf8mb4-Migrations-Strategie** auf Produktion.
- **Bug-Historie und Wellen-Verlauf** — liegt im `CHANGELOG.md`.

---

Vor größeren Eingriffen prüfen, ob die hier festgehaltenen
Konventionen noch dem aktuellen Stand entsprechen.
