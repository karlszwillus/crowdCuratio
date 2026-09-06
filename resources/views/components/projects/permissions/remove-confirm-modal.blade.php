@props(['show', 'candidateName' => ''])

{{--
    Q4-Etappe 2 (2026-08-27): Confirmation-Dialog fuer das Entfernen
    eines Users aus dem Projekt. Struktur analog zum invite-modal —
    Alpine-Root, Escape schliesst, wire-Bindings loesen gegen den
    Volt-Root auf.

    Props:
    - $show           bool     aus $showRemoveModal
    - $candidateName  string   Anzeigename fuer die Bestaetigungsfrage
--}}

@if ($show)
    <div
        class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="remove-title"
        wire:click.self="cancelRemove"
        x-data
        x-init="$nextTick(() => document.getElementById('removeCancelButton')?.focus())"
        @keydown.escape.window="$wire.cancelRemove()"
    >
        <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
            <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                <h3 id="remove-title" class="text-heading font-semibold text-ink-900">
                    {{ __('permissions_remove_user_title') }}
                </h3>
                <button
                    type="button"
                    wire:click="cancelRemove"
                    aria-label="{{ __('close') }}"
                    class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                >
                    <x-icon name="x" size="4"/>
                </button>
            </header>

            <div class="p-5">
                <p class="text-body text-ink-900">
                    {{ __('permissions_remove_user_confirm', ['name' => $candidateName]) }}
                </p>
                <p class="mt-2 text-caption text-ink-500">
                    {{ __('permissions_remove_user_hint') }}
                </p>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        id="removeCancelButton"
                        type="button"
                        wire:click="cancelRemove"
                        class="inline-flex items-center rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                               hover:bg-ink-900/5
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                    >
                        {{ __('cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="confirmRemoveUser"
                        class="inline-flex items-center gap-1.5 rounded-md bg-danger px-4 py-2 text-body font-medium text-danger-on
                               hover:opacity-90
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger"
                    >
                        <x-icon name="trash" size="4"/>
                        {{ __('permissions_remove_user_confirm_button') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
