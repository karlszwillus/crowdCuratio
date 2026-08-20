<?php

/**
 * crowdCuratio - Curating together virtually
 * Copyright (C)2026 - berlinHistory e.V.
 *
 * Q3-Politur G10 (2026-08-20) / ADR-0029:
 * HTML-Purifier-Konfiguration fuer Rich-Text-Felder aus dem Quill-
 * Editor. Der 'rich'-Preset ist die einzige aktive Config und die
 * Whitelist ist streng: nur die Tags und Attribute, die der Editor
 * tatsaechlich erzeugt. Alles andere fliegt raus.
 *
 * Verwendung: `Purifier::clean($html, 'rich')` bzw. ueber die
 * Blade-Directive `@rich($value)` (siehe AppServiceProvider).
 */

declare(strict_types=1);

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'ignoreNonStrings' => false,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'default' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'p,br,strong,em,u,s,ul,ol,li,a[href|target|rel],blockquote,h2,h3,h4',
            'CSS.AllowedProperties' => '',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],
        'rich' => [
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            // ADR-0029: Whitelist der Tags aus dem Quill-Editor. `a`
            // darf href/target/rel tragen, alles andere ist Struktur.
            'HTML.Allowed' => 'p,br,strong,em,u,s,ul,ol,li,a[href|target|rel],blockquote,h2,h3,h4',
            // Q3-Politur G10-Followup (2026-08-20): script/style
            // koennen sonst als Text-Node „durchrutschen" (Purifier
            // strippt das Tag, der Inhalt bleibt sichtbar). Als
            // ForbiddenElements markiert wird das ganze Element
            // inklusive Content entfernt.
            'HTML.ForbiddenElements' => ['script', 'style'],
            // Kein Inline-Styling — verhindert
            // `style="background:url(javascript:…)"`-Angriffe.
            'CSS.AllowedProperties' => '',
            // Nur https und http als Link-Schema — Purifier akzeptiert
            // sonst auch mailto, tel und andere.
            'URI.AllowedSchemes' => ['http' => true, 'https' => true],
            // Externe Links bekommen automatisch target=_blank und
            // die zugehoerigen rel-Werte gegen tabnabbing.
            'HTML.TargetBlank' => true,
            'HTML.TargetNoopener' => true,
            'HTML.TargetNoreferrer' => true,
            'HTML.Nofollow' => false,
            // Auto-Paragraph macht Text ohne <p> zu einem <p>-Block —
            // nicht was wir wollen; Quill liefert ohnehin schon
            // umschlossene Blocks.
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            // Serializer-Cache aus (schreibt sonst PHP-Files ins
            // storage-Directory bei jedem Aufruf). Bei unserem
            // Traffic-Profil sind die HTML-Rechenkosten minimal.
            'Cache.SerializerPath' => storage_path('app/purifier'),
        ],
    ],
];
