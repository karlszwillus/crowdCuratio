# crowdCuratio

> **Curating together virtually** — eine Web-Plattform, auf der
> Redaktionsteams gemeinsam Wissens­projekte aufbauen. Mehrsprachige
> Texte und Medien (Bilder, Audiovisuelles, Galerien) werden mit
> Quellen- und Copyright-Angaben in eine hierarchische Struktur
> kuratiert, kommentiert und am Ende als PDF oder Web-Vorschau
> ausgespielt.

Trägerinnen: [berlinHistory e.V.](https://berlinhistory.app/) und
Aktives Museum Berlin. Lizenziert unter GPL-3.0-or-later.

## Stack (Stand Mai 2026)

| Schicht        | Aktuell                                         |
|----------------|-------------------------------------------------|
| PHP            | 8.1 (im Container)                              |
| Framework      | Laravel 8.x (ein Major-Upgrade-Pfad ist geplant)|
| Auth/Roles     | Laravel Breeze + spatie/laravel-permission      |
| Frontend       | Blade · Tailwind 2 · Alpine 2 · Laravel Mix     |
| PDF-Export     | dompdf 2 + mpdf 8 (Konsolidierung folgt)        |
| DB             | MySQL 8 mit utf8mb4, strict-Mode                |
| Dev-Container  | Laravel Sail (Ubuntu 22.04 jammy)               |
| Tests          | Pest                                            |

## Setup

Voraussetzungen: Docker Desktop (oder Colima), Git. Auf Apple Silicon
läuft der Container nativ — keine Rosetta nötig.

```bash
git clone https://github.com/berlinHistory/crowdCuratio.git
cd crowdCuratio
cp .env.example .env
```

In der `.env` mindestens setzen:

```env
APP_NAME=crowdCuratio
DB_PASSWORD=secret               # nur lokal, in Produktion ersetzen
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=einStarkesPasswort
MAIL_FROM_ADDRESS=noreply@example.com
```

Dann den Stack hochziehen. Beim ersten Mal braucht es `docker compose
run`, weil `./vendor/bin/sail` nach dem ersten `composer install`
existiert:

```bash
docker compose up -d --build
docker compose run --rm laravel.test composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

Wer den Sail-Wrapper nicht mag, kann jeden Befehl mit
`docker compose run --rm laravel.test …` ersetzen — Sail ist
Komfort, keine Voraussetzung.

Der Standard-`db:seed` legt sieben Permissions, drei Rollen
(Editor, Reviewer, Reader) und einen Admin-User mit den
`ADMIN_*`-Credentials aus deiner `.env` an. Nach erfolgreichem Seed
solltest du die `ADMIN_PASSWORD`-Zeile in der `.env` wieder leeren —
das Initial-Passwort ist dann nicht mehr aus dem Environment
rekonstruierbar.

App: <http://localhost:8084>
Mailpit-Dashboard (für ausgehende Mails): <http://localhost:8025>
phpMyAdmin: <http://localhost:8080> (nur Loopback)

## Tests

```bash
./vendor/bin/sail pest
```

Aktuell deckt die Suite die Authorization-Schichten für Project,
Chapter und Entry ab (17 Tests, davon 4 für den Create-Pfad-Bypass
NF-LAR-003). Weiteres Coverage entsteht in der Sicherheitsnetz-Phase.

## Dokumentation

| Wo                         | Was                                                       |
|----------------------------|-----------------------------------------------------------|
| `CHANGELOG.md`             | nutzersichtbare Änderungen, Keep-a-Changelog              |
| `LICENSE`                  | GPL-3.0-or-later                                          |
| `docs/smoke.md`            | Smoke-Pfad-Inventar (Login bis Invitation, 10 Pfade)      |
| `licenses.md`              | Lizenzen der eingesetzten Drittpakete                     |

Architektur-Entscheidungen liegen in der internen Werkbank
(`.werkbank/ADR/`, nicht im Repo-Diff): 0001 Ziel-Stack (PHP 8.4 /
Laravel 12), 0002 composer.lock eingecheckt, 0010 InnoDB für alle
Tabellen, 0011 utf8mb4-Konvertierung, 0013 Authorization über
Laravel-Policies + Spatie-Permission. Wer Zugang braucht, fragt im
Team.

## Mitwirken

Conventional Commits:
`type(scope): kurze beschreibung` mit Body, der das *Warum* erklärt.
Pro PR: kleiner, thematisch fokussierter Diff.

## Kontakt

Siehe Ansprechpartner bei [berlinHistory e.V.](https://berlinhistory.app/).
