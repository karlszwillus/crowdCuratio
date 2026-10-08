<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

use App\Support\AccentContrast;

it('rechnet die WCAG-Kontrast-Eckwerte korrekt', function () {
    expect(AccentContrast::ratio('#ffffff', '#000000'))->toEqualWithDelta(21.0, 0.01)
        ->and(AccentContrast::ratio('#777777', '#ffffff'))->toEqualWithDelta(4.48, 0.01);
});

it('waehlt weissen Text auf dunklen Presets und dunklen auf hellen', function () {
    expect(AccentContrast::onColor('#a8392f'))->toBe(AccentContrast::LIGHT)
        ->and(AccentContrast::onColor('#2f4a63'))->toBe(AccentContrast::LIGHT)
        ->and(AccentContrast::onColor('#e0b04a'))->toBe(AccentContrast::DARK)
        ->and(AccentContrast::onColor('#f5f1e8'))->toBe(AccentContrast::DARK);
});

it('waehlt bei mittleren Akzenten dunklen Text, wo die alte Schwelle weiss setzte', function () {
    // Rosé: alte Formel (Luminance 0.49 < 0.55) → weiss mit nur ~3.8:1.
    expect(AccentContrast::ratio('#c0687a', AccentContrast::LIGHT))->toBeLessThan(4.5)
        ->and(AccentContrast::onColor('#c0687a'))->toBe(AccentContrast::DARK)
        ->and(AccentContrast::passesAa('#c0687a'))->toBeTrue();
});

it('meldet Akzente, die mit keinem Kandidaten 4.5:1 erreichen', function () {
    // Mittelblau (~4.37:1 bestes Verhaeltnis): weder weiss noch dunkel kommt auf AA.
    expect(AccentContrast::bestRatio('#3d7fbf'))->toBeLessThan(4.5)
        ->and(AccentContrast::passesAa('#3d7fbf'))->toBeFalse();
});

it('versteht Kurzform und lehnt Unsinn ab', function () {
    expect(AccentContrast::onColor('#fff'))->toBe(AccentContrast::DARK);
    AccentContrast::onColor('rot');
})->throws(InvalidArgumentException::class);
