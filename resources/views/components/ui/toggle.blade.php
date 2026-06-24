@props([
    'label',
    'checked' => false,
    'name' => null,
    'disabled' => false,
    'id' => null,
])

@php
    if (empty($label)) {
        throw new \InvalidArgumentException(
            'x-ui.toggle benötigt das Pflicht-Prop "label" (wird zum aria-label).'
        );
    }

    $toggleId = $id ?? ($name ? $name.'-toggle' : 'toggle-'.uniqid());

    // Tailwind-Klassen für den Switch. Die Hintergrundfarbe wechselt
    // per data-state="on|off" (gesetzt via Alpine `:data-state`).
    $trackBase = 'relative inline-flex h-6 w-11 shrink-0 rounded-full '
               . 'transition-colors '
               . 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary '
               . 'disabled:opacity-50 disabled:cursor-not-allowed';
    $trackOn = 'bg-primary';
    $trackOff = 'bg-ink-400';

    $thumbBase = 'pointer-events-none absolute top-0.5 left-0.5 h-5 w-5 rounded-full '
               . 'bg-white shadow transition-transform';
@endphp

<button
    type="button"
    role="switch"
    id="{{ $toggleId }}"
    aria-label="{{ $label }}"
    @if ($disabled) disabled aria-disabled="true" @endif
    x-data="{ on: @js((bool) $checked) }"
    x-init="$el.setAttribute('aria-checked', on ? 'true' : 'false')"
    @click="on = !on; $el.setAttribute('aria-checked', on ? 'true' : 'false'); $dispatch('change', { value: on })"
    @keydown.space.prevent="on = !on; $el.setAttribute('aria-checked', on ? 'true' : 'false'); $dispatch('change', { value: on })"
    :class="on ? '{{ $trackOn }}' : '{{ $trackOff }}'"
    {{ $attributes->merge(['class' => $trackBase]) }}
>
    {{-- Visuell verstecktes <input>, damit ein normales Form-Submit den Wert mitschickt. --}}
    @if ($name)
        <input
            type="hidden"
            name="{{ $name }}"
            :value="on ? '1' : '0'"
        />
    @endif

    <span aria-hidden="true" :class="on ? '{{ $thumbBase }} translate-x-5' : '{{ $thumbBase }} translate-x-0'"></span>
</button>
