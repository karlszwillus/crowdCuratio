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

    // Wir rufen den `svg()`-Helper aus blade-ui-kit/blade-icons
    // direkt: `svg('lucide-trash')` splittet auf Set `lucide` und
    // Icon `trash` (dank Prefix-Registrierung im Lucide-Package).
    // Ergebnis ist ein Inline-SVG-String; wir platzieren ihn mit
    // `{!! !!}` und mergen Klassen sowie ARIA-Attribute.
    $lucideName = 'lucide-'.$requested;

    // Klassen zusammenbauen: erst unsere Defaults (size-N,
    // inline-block, shrink-0), dann per Aufruf gesetzte extra
    // Klassen anhängen.
    //
    // Statische Zuordnung, damit Tailwinds JIT die Klassen scannen
    // kann — `'size-'.$size` als dynamische Konkatenation würde
    // Tailwind übersehen und die Icons rendern in ihrer intrinsischen
    // (viewBox-)Größe, sichtbar als 300-px-Kacheln.
    $sizeClass = match ((int) $size) {
        4 => 'size-4',
        6 => 'size-6',
        default => 'size-5',
    };

    $extraClass = trim((string) $attributes->get('class', ''));
    $iconClass = trim($sizeClass.' inline-block shrink-0 '.$extraClass);

    // Restliche Attribute (ohne `class`) plus ARIA-Defaults.
    $svgAttrs = collect($attributes->getAttributes())
        ->except('class')
        ->all();

    $svgAttrs = array_merge(
        $svgAttrs,
        $decorative
            ? ['aria-hidden' => 'true', 'focusable' => 'false']
            : ['role' => 'img', 'aria-label' => (string) $label],
    );
@endphp

{!! svg($lucideName, $iconClass, $svgAttrs)->toHtml() !!}
