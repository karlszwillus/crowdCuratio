@props([
    'id',
    'title' => null,
    'size' => 'md',
    'closable' => true,
    'headingLevel' => 2,
    'labelledby' => null,
    'header' => null,
    'footer' => null,
])

@php
    if (empty($id)) {
        throw new \InvalidArgumentException(
            'x-ui.modal benötigt das Pflicht-Prop "id" (für data-target und JS-Manager).'
        );
    }

    // Modal-Markup-Pattern für den Vanilla-Modal-Manager (resources/js/modal.js):
    // - äußeres <div class="modal fade" id="..."> wird per `.modal.in/.show` ein-/ausgeblendet
    // - <div class="modal-backdrop"> setzt der Manager selbst ein
    // - inneres Markup ist token-basiert (kein .modal-dialog/.modal-content mehr)
    //
    // Damit fällt die Bootstrap-3-Struktur-Familie aus dem Compat-CSS,
    // sobald alle Views auf <x-ui.modal> umgezogen sind.

    $sizeMap = [
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'lg' => 'max-w-4xl',
    ];
    $dialogMax = $sizeMap[$size] ?? $sizeMap['md'];

    $headingTag = 'h'.max(1, min(6, (int) $headingLevel));
    $labelId = $labelledby ?: ($title ? $id.'-title' : null);
@endphp

<div
    class="modal fade"
    id="{{ $id }}"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    @if ($labelId) aria-labelledby="{{ $labelId }}" @endif
    aria-hidden="true"
    x-data="{ open: false }"
    @cc-modal-shown="open = true"
    @cc-modal-hidden="open = false"
    x-trap.noscroll.inert="open"
>
    {{-- Q3-Followup B1 (2026-08-20): Modal-Body scrollt, wenn der
         Inhalt hoeher wird als der Viewport (bisher lief der Modal
         unter den Viewport-Rand — z.B. „Ausstellung exportieren").
         Aeusserer Wrapper begrenzt die Hoehe auf calc(100vh-4rem),
         der Body-Slot bekommt overflow-y-auto. Header und Footer
         bleiben sichtbar, weil sie ausserhalb des scrollenden Bodys
         sitzen. --}}
    <div class="relative mx-auto my-8 flex max-h-[calc(100vh-4rem)] w-auto flex-col {{ $dialogMax }}" role="document">
        <div class="relative flex flex-col overflow-hidden rounded-md border border-ink-400 bg-white shadow-lg">
            @if ($title || $closable || isset($header))
                <header class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-ink-400 px-4 py-3">
                    @isset($header)
                        <div class="text-heading font-semibold text-ink-900">
                            {{ $header }}
                        </div>
                    @elseif ($title)
                        <{{ $headingTag }} id="{{ $labelId }}" class="m-0 text-heading font-semibold text-ink-900">
                            {{ $title }}
                        </{{ $headingTag }}>
                    @else
                        <span></span>
                    @endisset

                    @if ($closable)
                        <button
                            type="button"
                            class="-m-1 cursor-pointer rounded-md border-0 bg-transparent p-2 text-heading text-ink-700 hover:bg-ink-400/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                            data-dismiss="modal"
                            aria-label="{{ __('close') }}"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    @endif
                </header>
            @endif

            <div class="flex-1 overflow-y-auto px-4 py-3">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="flex flex-shrink-0 items-center justify-end gap-2 border-t border-ink-400 px-4 py-3">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</div>
