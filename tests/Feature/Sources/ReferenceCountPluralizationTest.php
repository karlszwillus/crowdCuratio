<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 */

/**
 * Q4-Etappe 3 / C0d Regression (2026-09-07): Der Plural-String für
 * `sources_admin_referenced_n_times` hatte keine Range-Marker, deshalb
 * hat Laravel bei count=1 fälschlich die 0-Form „Noch nicht referenziert."
 * gewählt. Fix: explizite `{0}|{1}|[2,*]`-Marker in de.json und en.json.
 */
it('trans_choice liefert bei count=0 die Null-Form (de)', function () {
    app()->setLocale('de');
    expect(trans_choice('sources_admin_referenced_n_times', 0, ['count' => 0]))
        ->toBe('Noch nicht referenziert.');
});

it('trans_choice liefert bei count=1 die Singular-Form (de)', function () {
    app()->setLocale('de');
    expect(trans_choice('sources_admin_referenced_n_times', 1, ['count' => 1]))
        ->toBe('Wird 1 mal referenziert.');
});

it('trans_choice liefert bei count=5 die Plural-Form (de)', function () {
    app()->setLocale('de');
    expect(trans_choice('sources_admin_referenced_n_times', 5, ['count' => 5]))
        ->toBe('Wird 5 mal referenziert.');
});

it('trans_choice liefert bei count=0 die Null-Form (en)', function () {
    app()->setLocale('en');
    expect(trans_choice('sources_admin_referenced_n_times', 0, ['count' => 0]))
        ->toBe('Not yet referenced.');
});

it('trans_choice liefert bei count=1 die Singular-Form (en)', function () {
    app()->setLocale('en');
    expect(trans_choice('sources_admin_referenced_n_times', 1, ['count' => 1]))
        ->toBe('Referenced 1 time.');
});

it('trans_choice liefert bei count=5 die Plural-Form (en)', function () {
    app()->setLocale('en');
    expect(trans_choice('sources_admin_referenced_n_times', 5, ['count' => 5]))
        ->toBe('Referenced 5 times.');
});
