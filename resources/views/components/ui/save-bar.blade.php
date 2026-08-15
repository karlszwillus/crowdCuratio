@props([
    /**
     * Alpine-Expression fuer die Dirty-Bedingung. Wird 1:1 an
     * x-show weitergereicht. Beispiel: `isDirty`, `changedFields > 0`.
     * Der umgebende Alpine-Scope muss die Variable/Methode kennen.
     */
    'dirtyExpr' => 'false',

    /**
     * Alpine-Expression, die beim Klick auf Save ausgeloest wird.
     * Beispiel: `save()`, `$wire.persist()`, `submit'.
     */
    'saveExpr' => null,

    /**
     * Optionale Alpine-Expression fuer den Discard-/Reset-Button.
     * Wenn null, wird kein Discard-Button gerendert.
     */
    'discardExpr' => null,

    /** Label ueber dem Save-Button — Default „Ungespeicherte Aenderungen". */
    'label' => null,
])

@php
    // Karl-Entscheidung 5d.5 (2026-08-15): expliziter Save-Button, kein
    // Undo-Toast. Konsistent zum bestehenden Auto-Save-Muster fuer
    // Inline-Editoren (5c) — nur eben mit einer sichtbaren Sammel-Bar
    // fuer Batch-Aenderungen (Rechte-Toggles, Formular-Slider u. a.).

    $ariaLabel = $label ?? __('unsaved_changes');
@endphp

<div
    x-show="{{ $dirtyExpr }}"
    x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    role="region"
    aria-label="{{ $ariaLabel }}"
    class="pointer-events-auto fixed inset-x-0 bottom-0 z-30 border-t border-line-200 bg-paper-0/95
           px-6 py-3 backdrop-blur supports-[backdrop-filter]:bg-paper-0/85"
>
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
        <span class="inline-flex items-center gap-2 text-body text-ink-700">
            <span class="size-2 rounded-full bg-warning" aria-hidden="true"></span>
            {{ $ariaLabel }}
        </span>

        <div class="flex items-center gap-2">
            @if ($discardExpr)
                <button
                    type="button"
                    @click="{{ $discardExpr }}"
                    class="inline-flex items-center rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                           hover:bg-ink-900/5
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                >
                    {{ __('discard') }}
                </button>
            @endif

            <button
                type="button"
                @click="{{ $saveExpr }}"
                class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                       hover:opacity-90
                       focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
                <x-icon name="check" size="4"/>
                {{ __('save_changes') }}
            </button>
        </div>
    </div>
</div>
