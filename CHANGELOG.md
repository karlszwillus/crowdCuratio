# Changelog

Alle nennenswerten Änderungen an crowdCuratio werden in dieser Datei dokumentiert.

Das Format orientiert sich an [Keep a Changelog 1.1.0](https://keepachangelog.com/de/1.1.0/),
die Versionierung folgt [Semantic Versioning 2.0.0](https://semver.org/lang/de/).

Sektionen je Release: `Hinzugefügt`, `Geändert`, `Veraltet`, `Entfernt`,
`Behoben`, `Sicherheit`.

## [Unreleased]

Stand nach Phase 1 (Stabilisierung + Sofortmaßnahmen) inkl. Reviewer-
Nachschlag (Phase 1.5). 40+ Commits auf `phase-1/setup-reset`,
alle vier in Phase 0 identifizierten Blocker geschlossen, dazu vier
neue Findings aus der Post-Phase-1-Review behoben.

ADR-Grundlagen für diese Welle: ADR-0001 (Ziel-Stack PHP 8.4 /
Laravel 12), ADR-0002 (composer.lock eingecheckt), ADR-0010 (InnoDB
für alle Tabellen), ADR-0011 (utf8mb4-Konvertierung), ADR-0013
(Authorization über Laravel-Policies + Spatie-Permission).

### Hinzugefügt

- `CHANGELOG.md` als verbindliche Änderungsspur.
- `composer.lock` wird ab sofort committet (Reproduzierbarkeit,
  `composer audit`-Baseline möglich).
- Pest als Test-Framework, Authorization-Bypass-Suite mit 13
  reproduzierbaren Szenarien für Project, Chapter und Entry.
- `app/Policies/ProjectPolicy.php`, `ChapterPolicy.php`,
  `EntryPolicy.php` — saubere Laravel-Policy-Schicht für
  besitzer-/admin-basierte Authorization.
- `database/seeders/RoleTableSeeder.php` legt drei Default-Rollen an
  (Editor, Reviewer, Reader) — User-Invitation im Standard-Setup wieder
  durchführbar.
- `docs/smoke.md` als belastbares Baseline-Inventar (10 Smoke-Pfade).

### Geändert

- `.gitignore`: `.werkbank/` lokal, `composer.lock` jetzt eingecheckt,
  `.DS_Store` und Smoke-Artefakte ignoriert.
- `docker-compose.yml`: Image-Tags gepinnt (`mysql:8.0`,
  `redis:7-alpine`, `getmeili/meilisearch:v1.6`, `phpmyadmin:5.2`),
  `mailhog → axllent/mailpit:v1.20`, `selenium` entfernt (Phase 2
  bei Bedarf mit `seleniarm`), `phpmyadmin` und Mailpit-Dashboard
  nur auf Loopback, `depends_on` mit `service_healthy`.
- `docker/8.0` → `docker/8.1` (PHP 8.1), Ubuntu 20.04 → 22.04
  (`jammy`), `ondrej/php`-PPA auf jammy-Arm64, Node 15 → Node 20 LTS.
- `config/database.php`: `charset = utf8mb4`, `collation =
  utf8mb4_unicode_ci`, `strict = true`.
- `dompdf/dompdf ^1.2` → `^2.0` (8 Security-Advisories in 1.2.x).
- `.env.example`: Sail-taugliche Defaults (`DB_HOST=mysql`,
  `REDIS_HOST=redis`, `MAIL_HOST=mailpit`, `MAIL_FROM_ADDRESS`
  vorbelegt, `ADMIN_*`-Variablen dokumentiert,
  `APP_DEBUG`-Warnkommentar).
- `CreateAdminUserSeeder`: liest `ADMIN_EMAIL` / `ADMIN_PASSWORD` /
  `ADMIN_NAME` / `ADMIN_LAST_NAME` aus dem Environment, bricht beim
  Fehlen mit `RuntimeException` ab. Idempotent.
- `DatabaseSeeder` ruft jetzt `PermissionTableSeeder` →
  `RoleTableSeeder` → `CreateAdminUserSeeder`. `PreviewSeeder` bleibt
  manuell.
- `ProjectController::update/destroy`, `ChapterController` und
  `EntryController` rufen `$this->authorize(...)` auf. Views nutzen
  `Auth::user()->can('update', $project)` statt der alten
  Eigenbau-Gates.

### Behoben

- **Foto-Upload-Anzeige (Stakeholder-Bug AM-B-1):** die
  `image`/`audio`-Routen liefen gegen die Default-Disk `local`, während
  Uploads auf der Disk `public` landen. Wechsel auf
  `Storage::disk('public')->response(…)` rendert hochgeladene Bilder
  wieder (incl. Project-Logo).
- **User-Invitation-Workflow (Stakeholder-Bug AM-D-3):** Default-Rollen
  fehlten, MAIL-Defaults waren leer. Mit dem Role-Seeder und
  vernünftigen `MAIL_*`-Defaults läuft der Einladungs-Flow inkl.
  Welcome-Mail wieder durch.
- `drop_foreign_key_table`-Migration läuft auf frischer DB nicht mehr
  in 1091, weil sie Spalten droppt, die `create_texts_table` /
  `create_image_table` nie angelegt haben — jetzt mit
  `Schema::hasColumn`-Guard.
- `ChapterController::update` und `EntryController::update` gaben
  bisher `return $this;` zurück — der Controller-Instance-Return wäre
  beim Versuch, das Response zu serialisieren, mit `TypeError`
  hochgegangen. Korrigiert zu `return back();`.
- DB-Defaults für `users.is_admin`, `users.create_project` und
  `users.last_name`, plus explizite `position`-Werte im
  `PermissionTableSeeder` — alles latente Schema-Lücken, die `strict =
  true` jetzt sichtbar gemacht hat.

### Entfernt

- Spurloses `selenium`-Image aus dem Compose-Stack (kein arm64).
- Stock-Breeze-Tests, die das Self-Service-Signup-Modell testen, das
  crowdCuratio nicht hat (`tests/Feature/RegistrationTest`, drei
  `ExampleTest`-Stubs).
- Tote PHP-7.4-Build-Variante `docker/7.4/` (NF-DOCKER-014) — wurde
  nach dem Umzug auf 8.1 von keiner Compose-Datei mehr referenziert.
- Tote Image-Preview-Route `/image/{file}/preview` (NF-CODE-006), die
  den Storage-Disk-Fix nie mitbekommen hatte und ohne Caller im Code
  steht.
- Fünf Custom-Gate-Closures aus `AuthServiceProvider::boot()`
  (`edit-`, `add-`, `delete-`, `publish-`, `comment-project`); ihre
  Owner-Logik war ohnehin semantisch schief
  (`$user->id === $project`). View-Aufrufe in
  `chapters/index.blade.php` (10 Stellen) jetzt auf die Project-Policy
  umgehängt.

### Sicherheit

- **Authorization-Bypass über direkte HTTP-Aufrufe** geschlossen
  (Stakeholder-Risiko Phase-0 B-3 / F-SEC-007 + B-4 / F-LAR-001).
  Project / Chapter / Entry-Mutationen prüfen ab sofort sowohl in der
  Controller-Action als auch in der View, ob der eingeloggte User
  Eigentümer oder Admin ist. Belegt durch Pest-Suite
  `tests/Feature/AuthorizationTest.php`.
- **Create-Pfad-Bypass in Chapter/Entry** geschlossen (Reviewer-Befund
  NF-LAR-003). `ChapterController::store` und `EntryController::store`
  haben jetzt einen Owner-Check (`createIn`-Policy-Methode); die
  ursprüngliche D.4-Suite hat nur Update/Destroy abgedeckt. Vier neue
  Pest-Tests sichern das ab (Intruder 403, Admin 302).
- **Logo-Upload-Validation und Path-Traversal-Pfad** geschlossen
  (NF-SEC-007). `ProjectController::update` las `$request['logo']`
  blind und schrieb den Wert in die DB — Path-Traversal-Vektor. Logo
  kommt jetzt ausschließlich aus `setImage()`, `project_image` wird
  als File mit MIME-Whitelist und 4 MB Limit validiert.
- **`facade/ignition`-RCE (CVE-2021-3129)** entschärft: durch
  `composer install` mit Lock zieht der Build die geprüfte Version
  2.17.7 ein, nicht die anfälligen 2.5.0/.1.
- **MyISAM-Datenintegritäts-Bug** in der `texts`-Tabelle behoben:
  Engine-Konvertierung auf InnoDB plus Reinstall der
  Source-Foreign-Keys, die unter MyISAM still verworfen wurden.
- **Charset auf `utf8mb4`** (vorher `utf8mb3`): 4-Byte-Glyphen werden
  ab sofort gespeichert statt gestrippt.
- **MySQL `strict`-Mode** ist an: zero-dates, GROUP-BY-Verstöße,
  Inserts ohne Required-Felder werfen ab sofort hörbar Fehler statt
  still durchzulaufen.
- File-Upload-`public`-Disk und Mailpit-/phpMyAdmin-Loopback-Binding
  reduzieren die Lateral-Movement-Fläche im lokalen Dev-Netz.

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

Details und Roadmap siehe interne Werkbank (`.werkbank/KONTEXT.md`).

---

[Unreleased]: https://github.com/berlinHistory/crowdCuratio/compare/v0.8.0...HEAD
[0.8.0]: https://github.com/berlinHistory/crowdCuratio/releases/tag/v0.8.0
