@props([
    'variant' => 'inhalt',
    'title' => null,
    'headingLevel' => 2,
])

@php
    // Hierarchie: chapter > abschnitt > inhalt (Glossar Phase 5).
    // Variant beeinflusst Border, Padding und Heading-Größe.
    $variantClasses = [
        'chapter'  => 'border-2 border-ink-900 p-6 rounded-lg',
        'abschnitt' => 'border border-ink-700 p-5 rounded-md',
        'inhalt'   => 'border border-ink-400 p-4 rounded-md',
    ];

    $headingSize = [
        'chapter' => 'text-title font-bold text-ink-900',
        'abschnitt' => 'text-heading font-semibold text-ink-900',
        'inhalt' => 'text-body font-semibold text-ink-900',
    ];

    $tag = 'h'.max(1, min(6, (int) $headingLevel));
    $base = 'block bg-white';
    $classes = trim($base.' '.($variantClasses[$variant] ?? $variantClasses['inhalt']));
@endphp

<section {{ $attributes->merge(['class' => $classes]) }}>
    @if ($title)
        <{{ $tag }} class="mb-3 {{ $headingSize[$variant] ?? $headingSize['inhalt'] }}">
            {{ $title }}
        </{{ $tag }}>
    @endif

    {{ $slot }}
</section>
