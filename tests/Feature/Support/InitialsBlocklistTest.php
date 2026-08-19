<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Pest-Tests fuer Phase 5ac.2 — Kuerzel-Sperrliste + Vorschlaege.
 *
 * Prueft die Normalisierung (Umlaute, Case, Punkte) und dass die
 * Vorschlags-Funktion keine gesperrten Werte ausspuckt.
 */

use App\Support\InitialsBlocklist;

it('blockt case-insensitive und mit Punkten', function () {
    expect(InitialsBlocklist::isBlocked('SS'))->toBeTrue()
        ->and(InitialsBlocklist::isBlocked('ss'))->toBeTrue()
        ->and(InitialsBlocklist::isBlocked('S.S.'))->toBeTrue()
        ->and(InitialsBlocklist::isBlocked('S S'))->toBeTrue();
});

it('normalisiert Umlaute in der Blocklist', function () {
    // 'SS' ist in der Liste, 'Sß' (mit ss ligature) muss auch matchen
    expect(InitialsBlocklist::isBlocked('Sß'))->toBeTrue();
});

it('laesst unverfaengliche Kuerzel durch', function () {
    expect(InitialsBlocklist::isBlocked('AB'))->toBeFalse()
        ->and(InitialsBlocklist::isBlocked('KMS'))->toBeFalse()
        ->and(InitialsBlocklist::isBlocked(null))->toBeFalse()
        ->and(InitialsBlocklist::isBlocked(''))->toBeFalse();
});

it('liefert Vorschlaege ohne gesperrte Kuerzel', function () {
    // „Karl Szwillus" — Standard-Vorschlag waere KAR/KA/K.S.
    $suggestions = InitialsBlocklist::suggestFor('Karl', 'Szwillus');
    expect($suggestions)->not->toBeEmpty();
    foreach ($suggestions as $s) {
        expect(InitialsBlocklist::isBlocked($s))->toBeFalse();
    }
});
