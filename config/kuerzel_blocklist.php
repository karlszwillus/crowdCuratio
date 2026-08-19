<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C) 2026 - berlinHistory e.V.
 *
 * Phase 5ac.2 (Profil-Redesign § 2): Sperrliste fuer Nutzer-Kuerzel.
 *
 * Zweck: freie Kuerzel-Wahl auf einer Museums-Plattform erzeugt
 * Kombinationen, die nicht sichtbar werden duerfen (NS-Kuerzel und
 * uebliche Beleidigungs-Kuerzel). Die Sperrliste greift SOWOHL beim
 * Speichern eines manuell eingegebenen Kuerzels ALS AUCH bei den aus
 * dem Namen automatisch abgeleiteten Vorbelegungen — sonst haetten
 * NS-Namenskombinationen einen freien Slot.
 *
 * Pflege in Runde 1: hier per PR. Bei Bedarf spaeter DB-Tabelle mit
 * Admin-UI (5ac-Backlog). Vergleich case-insensitive, Umlaute werden
 * normalisiert (siehe App\Support\InitialsBlocklist).
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Historisch belastete Kuerzel
    |--------------------------------------------------------------------------
    | NS-Kuerzel und -Formeln, die auf einer Public-History-Plattform
    | nicht als Autor-Kuerzel taugen. Ergaenzung nur nach Rueck-
    | sprache im Team.
    */
    'historical' => [
        'NS', 'SS', 'SA', 'HJ', 'BDM', 'RAD', 'AH', 'JH',
        'HH', 'NPD', 'HKN', '88', '18', '14',
        'KKK', 'WPWW',
    ],

    /*
    |--------------------------------------------------------------------------
    | Beleidigungen und Diskriminierendes
    |--------------------------------------------------------------------------
    | Kurzformen, die als Kuerzel oberhalb einer Namens-Karte oder in
    | einer Autorenzeile schlicht nicht funktionieren.
    */
    'slurs' => [
        'FCK', 'ACAB', 'SEX', 'FUX', 'ARS',
    ],
];
