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
| Reader-Namensraum-Wachhund
|--------------------------------------------------------------------------
|
| Der Reader laedt ausschliesslich public/css/reader.css — Editor-
| Utilities aus dem Vite-Bundle (`text-ink-500`, `text-title`,
| `border-line-200` …) existieren dort nicht und fallen still auf
| Browser-Defaults zurueck. Dieser Test scannt resources/views/preview/
| und schlaegt an, sobald jemand Editor-Vokabular in eine Reader-Sicht
| kopiert. Reader-Sichten nutzen `cc-*`-Klassen und Reader-Tokens.
*/

$editorUtilities = '/(?<![\w-])(text-(ink|line)-\d{2,3}|text-(title|heading|body|caption|mono-caps)|(bg|border)-(ink|line)-\d{2,3}|(text|bg|border)-primary|grid-cols-\d+)\b/';

it('Reader-Sichten nutzen keine Editor-Utilities', function () use ($editorUtilities) {
    $violations = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views/preview')));

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        // PDF-Templates haben eigenes Inline-CSS und laufen nicht ueber reader.css.
        if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR)) {
            continue;
        }
        // Blade-Kommentare ignorieren.
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getPathname()));

        preg_match_all('/class="([^"]*)"/', $source, $matches);
        foreach ($matches[1] as $classList) {
            if (preg_match_all($editorUtilities, $classList, $hits)) {
                $relative = str_replace(resource_path().'/', '', $file->getPathname());
                $violations[] = "{$relative}: ".implode(', ', array_unique($hits[0]));
            }
        }
    }

    expect($violations)->toBe([], "\nEditor-Utilities in Reader-Sichten:\n  ".implode("\n  ", $violations));
});
