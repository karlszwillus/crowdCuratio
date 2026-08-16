@props([
    'commentableType',
    'commentableId',
    'count' => 0,
    'title' => null,
])

@php
    /**
     * Phase 5x.9: ersetzt die frueheren <a href="?comment=…">-Links, die
     * einen Full-Page-Reload zum Oeffnen des Kommentar-Panels ausloesten.
     * Der Button feuert stattdessen `panel:load-and-open` als DOM-Event,
     * das der Panel-Wrapper und die Livewire-Liste gleichzeitig
     * verarbeiten.
     */
    $label = $title ?? __('add_comment');
    $payload = json_encode([
        'commentableType' => $commentableType,
        'commentableId' => (int) $commentableId,
    ], JSON_HEX_QUOT | JSON_HEX_APOS);
@endphp

<button
    type="button"
    x-data
    @click="window.dispatchEvent(new CustomEvent('panel:load-and-open', { detail: {{ $payload }} }))"
    title="{{ $label }}"
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => 'addComment inline-flex size-11 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary']) }}
>
    @if ($count > 0)
        <span class="relative">
            <x-icon name="message-square" size="5" />
            <span class="absolute -right-2 -top-2 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-caption font-medium text-primary-on">
                {{ $count }}
            </span>
        </span>
    @else
        <x-icon name="message-square" size="5" />
    @endif
</button>
