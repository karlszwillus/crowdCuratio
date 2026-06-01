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

use App\Services\LogService;

/*
|--------------------------------------------------------------------------
| LogService — highlightTextDifference
|--------------------------------------------------------------------------
|
| highlightTextDifference vergleicht zwei Strings und liefert ein
| Array mit ['old' => <Original mit <del>>, 'new' => <Neues mit
| <span background>>]. Reine Funktion über Strings, gehört zu den
| ersten Unit-Tests im Projekt — schließt die größte ungetestete
| Methode im LogService und liefert eine Vorlage für weitere
| LogService-Unit-Tests in Phase 4.
|
| Der Konstruktor von LogService verlangt einen Modell-Selektor;
| 'text' liefert einen gültigen Pfad und stört das pure
| String-Verhalten nicht.
*/

it('hebt den abweichenden Teil zwischen Alt und Neu hervor', function () {
    $service = new LogService('text');

    $result = $service->highlightTextDifference('Hallo Welt', 'Hallo Mars');

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['old', 'new'])
        ->and($result['old'])
        ->toContain('Welt')
        ->toContain('<del')
        ->and($result['new'])
        ->toContain('Mars')
        ->toContain('<span');
});

it('liefert für identische Strings keine sichtbare Diff-Auszeichnung im Text', function () {
    $service = new LogService('text');

    $result = $service->highlightTextDifference('Identischer Inhalt', 'Identischer Inhalt');

    // Bei identischen Strings ist das Diff-Segment leer; die Markup-
    // Tags bleiben strukturell drin, der Original-Text aber unberührt.
    expect($result)
        ->toBeArray()
        ->toHaveKeys(['old', 'new'])
        ->and($result['old'])
        ->toContain('Identischer Inhalt')
        ->and($result['new'])
        ->toContain('Identischer Inhalt');
});

it('behandelt einen leeren Alt-String, sodass der gesamte Neu-String als Diff markiert ist', function () {
    $service = new LogService('text');

    $result = $service->highlightTextDifference('', 'Komplett neuer Text');

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['old', 'new'])
        ->and($result['new'])
        ->toContain('Komplett neuer Text')
        ->toContain('<span');
});

/*
|--------------------------------------------------------------------------
| LogService — Konstruktor-Switch
|--------------------------------------------------------------------------
|
| Der Konstruktor mappt einen String-Selektor ('text', 'image',
| 'entry', 'chapter', 'gallery') auf das jeweilige Model, die
| zugehörige Tabelle und das Display-Property. Reine Switch-Logik,
| Reflexion macht die protected-Properties testbar.
*/

$readPrivate = function (LogService $service, string $name) {
    $reflection = new ReflectionObject($service);
    $property = $reflection->getProperty($name);
    $property->setAccessible(true);

    return $property->getValue($service);
};

it('belegt für \'text\' das Text-Model, die texts-Tabelle und text als Property', function () use ($readPrivate) {
    $service = new LogService('text');

    expect($readPrivate($service, 'model'))->toBe('App\\Models\\Text');
    expect($readPrivate($service, 'table'))->toBe('texts');
    expect($readPrivate($service, 'property'))->toBe('text');
});

it('belegt für \'image\' das Image-Model, die images-Tabelle und name als Property', function () use ($readPrivate) {
    $service = new LogService('image');

    expect($readPrivate($service, 'model'))->toBe('App\\Models\\Image');
    expect($readPrivate($service, 'table'))->toBe('images');
    expect($readPrivate($service, 'property'))->toBe('name');
});

it('belegt für \'entry\' das Entry-Model, die entries-Tabelle und name als Property', function () use ($readPrivate) {
    $service = new LogService('entry');

    expect($readPrivate($service, 'model'))->toBe('App\\Models\\Entry');
    expect($readPrivate($service, 'table'))->toBe('entries');
    expect($readPrivate($service, 'property'))->toBe('name');
});

it('belegt für \'chapter\' das Chapter-Model, die chapters-Tabelle und name als Property', function () use ($readPrivate) {
    $service = new LogService('chapter');

    expect($readPrivate($service, 'model'))->toBe('App\\Models\\Chapter');
    expect($readPrivate($service, 'table'))->toBe('chapters');
    expect($readPrivate($service, 'property'))->toBe('name');
});

it('belegt für \'gallery\' den Gallery::class-Konstanten-Wert, die galleries-Tabelle und name als Property', function () use ($readPrivate) {
    $service = new LogService('gallery');

    expect($readPrivate($service, 'model'))->toBe(\App\Models\Gallery::class);
    expect($readPrivate($service, 'table'))->toBe('galleries');
    expect($readPrivate($service, 'property'))->toBe('name');
});
