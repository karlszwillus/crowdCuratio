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

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Vokabular-Sweep (Phase 5e.2/5e.7)
|--------------------------------------------------------------------------
|
| Wach-Hund gegen den Rueckfall auf Alt-Vokabeln. Wer per Copy-Paste
| aus einer Alt-View ein `Eintrag`-String zurueckbringt, faellt hier
| auf. Das Glossar steht in docs/architecture.md (5e.6) und in der
| Werkbank; hier fixieren wir es als Test.
|
| Wir scannen zwei Quellen:
|   1. resources/lang/de.json     — alle Uebersetzungs-Values
|   2. resources/views/ (rekursiv, .blade.php) — hardcodierte
|      deutsche Strings, die __('...') umgangen haben
|
| Ausnahmen: PHP-Kommentare in Blade-Views (`{{-- ... --}}` und
| `// ...`) und DB-Match-Werte (`'Draft'` in `where('status', ...)`)
| bleiben unberuehrt — der Test greift nur auf sichtbare Textteile.
*/

$oldVocab = [
    // Substantiv-Migration Handoff v4 → Glossar
    '/\bEintrag\b/u' => 'Eintrag → Abschnitt',
    '/\bEintr[aä]ge\b/u' => 'Einträge → Abschnitte',
    '/\bBlock\b/u' => 'Block → Inhalt',
    '/\bBl[oö]cke\b/u' => 'Blöcke → Inhalte',
    '/\bItem\b/u' => 'Item → Inhalt',
    // Genderung
    '/\bAutorIn\b/u' => 'AutorIn → Autor:in',
    '/\bBenutzerIn\b/u' => 'BenutzerIn → Nutzer:in',
    // Anglizismen
    '/\bDraft\b/u' => 'Draft → Entwurf',
];

it('resources/lang/de.json enthaelt keine Alt-Vokabeln mehr', function () use ($oldVocab) {
    $path = resource_path('lang/de.json');
    $translations = json_decode((string) file_get_contents($path), true);
    expect($translations)->toBeArray();

    $violations = [];
    foreach ($translations as $key => $value) {
        if (! is_string($value)) {
            continue;
        }
        foreach ($oldVocab as $pattern => $hint) {
            if (preg_match($pattern, $value)) {
                $violations[] = "{$key}: {$value}  ({$hint})";
            }
        }
    }

    expect($violations)->toBe([], "\nAlt-Vokabeln gefunden:\n  ".implode("\n  ", $violations));
});

it('Blade-Views enthalten keine hardcodierten Alt-Vokabeln im Textknoten', function () use ($oldVocab) {
    $violations = [];
    $files = collect(rglobBlade(resource_path('views')))
        ->reject(fn (string $f) => str_contains($f, '/vendor/'));

    foreach ($files as $file) {
        $content = (string) file_get_contents($file);

        // Blade-Kommentare rausfiltern — die duerfen Alt-Vokabeln
        // enthalten (historische Notizen).
        $content = preg_replace('/\{\{--.*?--\}\}/us', '', $content);
        // Einzeilige PHP-Kommentare in @php-Bloecken auch.
        $content = preg_replace('/^\s*\/\/.*$/mu', '', $content);
        // DB-Match-Werte in Quoted-Strings mit ->where oder ähnlich —
        // wir ignorieren Strings innerhalb von PHP-Code (`'Draft'`).
        // Der Text im gerenderten HTML kommt aus __('...')-Aufrufen.
        // Daher nehmen wir grosszuegig alles zwischen > und < als
        // "sichtbaren Textknoten".
        preg_match_all('/>([^<>]{3,})</u', $content, $matches);
        $visibleText = implode("\n", $matches[1]);

        foreach ($oldVocab as $pattern => $hint) {
            if (preg_match($pattern, $visibleText, $hit)) {
                $rel = str_replace(resource_path('views').'/', '', $file);
                $violations[] = sprintf('%s: %s  (%s)', $rel, $hit[0], $hint);
            }
        }
    }

    expect($violations)->toBe([], "\nAlt-Vokabeln in Blade-Text gefunden:\n  ".implode("\n  ", $violations));
});

/**
 * Rekursives Glob fuer Blade-Views. `**\/*.blade.php` gibt es in
 * PHP-Standard-`glob()` nicht.
 *
 * @return array<int, string>
 */
function rglobBlade(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}
