<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

See LICENSE.
 */

declare(strict_types=1);

use App\Support\GalleryForm;

/*
|--------------------------------------------------------------------------
| GalleryForm-Enum (Q4-Etappe 6 · G6-2)
|--------------------------------------------------------------------------
|
| Die Anzahl-Regel entscheidet über die Darstellungsform der Galerie:
|   0-4 Bilder → Band
|   ab 5       → Kontaktbogen
|   sequence=true überschreibt beide → Sequenz
|
| Editor-Statuspanel und Reader-Rendering rufen dieselbe resolve()-
| Methode; dieser Test fixiert die Regel für beide Aufrufer.
*/

it('resolve gibt Band für 0 bis 4 Bilder zurück', function () {
    expect(GalleryForm::resolve(0))->toBe(GalleryForm::BAND);
    expect(GalleryForm::resolve(1))->toBe(GalleryForm::BAND);
    expect(GalleryForm::resolve(4))->toBe(GalleryForm::BAND);
});

it('resolve gibt Kontaktbogen ab 5 Bildern zurück', function () {
    expect(GalleryForm::resolve(5))->toBe(GalleryForm::KONTAKTBOGEN);
    expect(GalleryForm::resolve(9))->toBe(GalleryForm::KONTAKTBOGEN);
    expect(GalleryForm::resolve(42))->toBe(GalleryForm::KONTAKTBOGEN);
});

it('resolve gibt Sequenz zurück, wenn sequence=true (unabhängig von der Anzahl)', function () {
    expect(GalleryForm::resolve(0, true))->toBe(GalleryForm::SEQUENZ);
    expect(GalleryForm::resolve(3, true))->toBe(GalleryForm::SEQUENZ);
    expect(GalleryForm::resolve(20, true))->toBe(GalleryForm::SEQUENZ);
});

it('nextFormIfIncrement kündigt den Formwechsel bei count=4 an', function () {
    $current = GalleryForm::resolve(4);

    expect($current->nextFormIfIncrement(4, false))->toBe(GalleryForm::KONTAKTBOGEN);
});

it('nextFormIfIncrement liefert null, wenn kein Wechsel kommt', function () {
    $current = GalleryForm::resolve(5);

    expect($current->nextFormIfIncrement(5, false))->toBeNull();
});

it('nextFormIfDecrement kündigt den Formwechsel bei count=5 an', function () {
    $current = GalleryForm::resolve(5);

    expect($current->nextFormIfDecrement(5, false))->toBe(GalleryForm::BAND);
});

it('nextFormIfDecrement liefert null bei count=0', function () {
    $current = GalleryForm::resolve(0);

    expect($current->nextFormIfDecrement(0, false))->toBeNull();
});

it('label liefert eine übersetzte Beschriftung pro Form', function () {
    // Die Locale-Keys müssen existieren, sonst gibt trans() den Key zurück
    // — der `str_contains`-Check erkennt den Fallback und macht die
    // Test-Assertion für Larastan sauber (kein Pest-Expectation<string|null>).
    foreach (GalleryForm::cases() as $form) {
        $label = $form->label();
        expect($label)->toBeString();
        expect(str_contains($label, 'gallery_form_'))->toBeFalse();
    }
});

it('scopeHint liefert einen übersetzten Kontext pro Form', function () {
    foreach (GalleryForm::cases() as $form) {
        $hint = $form->scopeHint();
        expect($hint)->toBeString();
        expect(str_contains($hint, 'gallery_form_'))->toBeFalse();
    }
});
