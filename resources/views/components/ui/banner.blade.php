@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $typeClasses = [
        'success' => 'bg-success-bg text-success border-success/20',
        'warning' => 'bg-warning-bg text-warning border-warning/20',
        'danger'  => 'bg-danger-bg text-danger border-danger/20',
        'info'    => 'bg-info-bg text-info border-info/20',
    ];

    $iconName = [
        'success' => 'circle-check',
        'warning' => 'alert-triangle',
        'danger'  => 'circle-alert',
        'info'    => 'info',
    ];

    // success und info: aria-live="polite" (nicht-unterbrechend)
    // warning und danger: aria-live="assertive" (unterbrechend; A11y-Regel)
    $live = in_array($type, ['warning', 'danger'], true) ? 'assertive' : 'polite';
    $role = in_array($type, ['warning', 'danger'], true) ? 'alert' : 'status';

    $base = 'flex items-start gap-3 rounded-md border px-4 py-3 text-body';
    $classes = trim($base.' '.($typeClasses[$type] ?? $typeClasses['info']));
@endphp

<div
    role="{{ $role }}"
    aria-live="{{ $live }}"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <x-ui.icon :name="$iconName[$type] ?? 'info'" :size="20" class="mt-0.5"/>

    <div class="flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif

        <div @class(['text-body', 'mt-1' => $title])>
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <x-ui.icon-button
            :label="__('close')"
            variant="ghost"
            x-data
            @click="$el.closest('[role]')?.remove()"
            class="-mr-2 -mt-1 h-8 w-8"
        >
            <x-ui.icon name="x" :size="16"/>
        </x-ui.icon-button>
    @endif
</div>
