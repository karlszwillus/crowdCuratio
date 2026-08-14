@props([
    /**
     * Icon-Name. Bevorzugt Lucide-Name (z. B. `pencil`, `trash-2`,
     * `chevron-down`). Bootstrap-Icons-Altnamen (`bi-pencil`,
     * `bi-trash`) werden über `config/icon-mapping.php` auf Lucide
     * übersetzt — nur als Migrations-Shim, wird am Ende von 5-D.2
     * entfernt.
     */
    'name' => '',

    /**
     * Größe in Tailwind-Utility-Stufe: 4 (16 px), 5 (20 px), 6 (24 px).
     * Andere Werte sind laut Icon-System-Konvention (KONTEXT.md § 8)
     * nicht erlaubt.
     */
    'size' => 5,

    /**
     * Für dekorative Icons `true` (Default) → `aria-hidden="true"`
     * wird an das SVG gehängt, Screenreader ignorieren es. Für
     * sprechende Icons `false` und `label` setzen — dann wird
     * `role="img" aria-label="…"` gerendert.
     */
    'decorative' => true,

    /** ARIA-Label für nicht-dekorative Icons. */
    'label' => null,
])

@php
    // Bootstrap-Icons-Altnamen (`bi-…`) auf Lucide übersetzen.
    // Konvention: `<x-icon name="pencil" />` (semantischer Ziel-
    // Name). Migrations-Shim für Bestandsviews akzeptiert auch
    // `bi-pencil` und schlägt im Mapping nach.
    $requested = ltrim((string) $name, ' ');
    $mapping = config('icon-mapping', []);

    // Bootstrap-Icons-Altnamen (`bi-…`) auf Lucide übersetzen.
    if (str_starts_with($requested, 'bi-')) {
        $key = substr($requested, 3);
        $requested = $mapping[$key] ?? $key;
    } elseif (isset($mapping[$requested])) {
        // Lucide-Alias (z. B. `alert-triangle` → `triangle-alert`
        // nach dem Rename in Lucide 0.359).
        $requested = $mapping[$requested];
    }

    $lucideComponent = 'lucide-'.$requested;

    $mergedClasses = trim('size-'.$size.' inline-block shrink-0 '.$attributes->get('class', ''));
    $svgAttributes = collect($attributes->getAttributes())
        ->except('class')
        ->all();

    $svgAttributes = array_merge(
        $svgAttributes,
        $decorative
            ? ['aria-hidden' => 'true', 'focusable' => 'false']
            : ['role' => 'img', 'aria-label' => $label],
    );
@endphp

{!! svg($lucideComponent, $mergedClasses, $svgAttributes)->toHtml() !!}
