@props([
    'label',
    'variant' => 'ghost',
    'type' => 'button',
    'disabled' => false,
])

@php
    // `label` ist Pflicht — die Komponente rendert kein sichtbares
    // Text-Label, nur das Icon. Ohne aria-label wäre der Button für
    // Screenreader stumm.
    if (empty($label)) {
        throw new \InvalidArgumentException(
            'x-ui.icon-button benötigt das Pflicht-Prop "label" (wird zum aria-label).'
        );
    }

    $variantClasses = [
        'primary' => 'bg-primary text-primary-on hover:opacity-90 focus-visible:outline-primary',
        'ghost'   => 'bg-transparent text-ink-900 hover:bg-ink-900/5 focus-visible:outline-ink-900',
        'danger'  => 'bg-transparent text-danger hover:bg-danger/10 focus-visible:outline-danger',
    ];

    // 44 × 44 erfüllt das WCAG-2.2-Target-Token (`--target-min: 44px`).
    $base = 'inline-flex items-center justify-center rounded-md '
          . 'h-11 w-11 '
          . 'transition-opacity transition-colors '
          . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
          . 'disabled:opacity-50 disabled:cursor-not-allowed';

    $classes = trim($base.' '.($variantClasses[$variant] ?? $variantClasses['ghost']));
@endphp

<button
    type="{{ $type }}"
    aria-label="{{ $label }}"
    @if ($disabled) disabled aria-disabled="true" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
