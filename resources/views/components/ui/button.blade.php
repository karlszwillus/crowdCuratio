@props([
    'variant' => 'primary',
    'type' => 'button',
    'size' => 'md',
    'disabled' => false,
    /**
     * Rollen-bewusste Sperre (Phase 5d.1). Anders als `disabled`:
     * Button bleibt sichtbar und fokussierbar, aria-disabled=true,
     * KEIN natives `disabled`, Schloss-Icon links vom Label,
     * Tooltip mit Grund. Fuer Reader/Editor-Rechte, damit die
     * Persona versteht was sie NICHT darf statt den Trigger
     * nicht zu finden (Persona-Befund B-K-B-04).
     */
    'locked' => false,
    /** Tooltip-Text auf dem locked-Button; Pflicht wenn locked=true. */
    'lockedReason' => null,
])

@php
    $variantClasses = [
        'primary'   => 'bg-primary text-primary-on hover:opacity-90 focus-visible:outline-primary',
        'secondary' => 'bg-ink-900 text-white hover:opacity-90 focus-visible:outline-ink-900',
        'ghost'     => 'bg-transparent text-ink-900 hover:bg-ink-900/5 focus-visible:outline-ink-900',
        'danger'    => 'bg-danger text-white hover:opacity-90 focus-visible:outline-danger',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-caption gap-1.5',
        'md' => 'px-4 py-2 text-body gap-2',
        'lg' => 'px-5 py-2.5 text-heading gap-2',
    ];

    $base = 'inline-flex items-center justify-center rounded-md font-medium '
          . 'transition-opacity transition-colors '
          . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 '
          . 'disabled:opacity-50 disabled:cursor-not-allowed';

    // is-disabled ist der Style-Anker fuer den locked-Zustand
    // (siehe app.css, Phase 5d.1). Er darf NICHT das native
    // disabled-Verhalten spielen, sondern nur visuell.
    if ($locked) {
        $base .= ' is-disabled';
    }

    $classes = trim($base.' '.($variantClasses[$variant] ?? $variantClasses['primary']).' '.($sizeClasses[$size] ?? $sizeClasses['md']));
@endphp

<button
    type="{{ $type }}"
    @if ($disabled) disabled aria-disabled="true" @endif
    @if ($locked && ! $disabled) aria-disabled="true" @endif
    @if ($locked && $lockedReason) title="{{ $lockedReason }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($locked)
        <x-icon name="lock" size="4"/>
    @endif
    {{ $slot }}
</button>
