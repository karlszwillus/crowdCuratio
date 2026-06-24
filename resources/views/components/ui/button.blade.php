@props([
    'variant' => 'primary',
    'type' => 'button',
    'size' => 'md',
    'disabled' => false,
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

    $classes = trim($base.' '.($variantClasses[$variant] ?? $variantClasses['primary']).' '.($sizeClasses[$size] ?? $sizeClasses['md']));
@endphp

<button
    type="{{ $type }}"
    @if ($disabled) disabled aria-disabled="true" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $slot }}
</button>
