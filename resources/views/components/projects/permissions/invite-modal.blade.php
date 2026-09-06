@props(['show', 'error'])

{{--
    Q4-Etappe 1 / I4 (2026-08-27): Invite-Modal aus project-permissions
    extrahiert. Einziger Alpine-Root in dieser Komponenten-Familie —
    x-data + x-init laufen weiter, weil die Blade-Component am Root
    des Livewire-Volt-Components sitzt.

    Props:
    - $show   bool    aus $showInviteModal
    - $error  string  aus $inviteError
--}}

@if ($show)
    <div
        class="fixed inset-0 z-40 flex items-center justify-center bg-ink-900/40 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="invite-title"
        wire:click.self="closeInvite"
        {{-- Q3-Politur G2 (2026-08-20) / A11Y-03 · A11Y-04:
             Escape schliesst, Fokus faellt auf das E-Mail-Feld. --}}
        x-data
        x-init="$nextTick(() => document.getElementById('inviteEmail')?.focus())"
        @keydown.escape.window="$wire.closeInvite()"
    >
        <div class="w-full max-w-md rounded-lg border border-line-200 bg-paper-0 shadow-lg">
            <header class="flex items-center justify-between border-b border-line-200 px-5 py-3">
                <h3 id="invite-title" class="text-heading font-semibold text-ink-900">
                    {{ __('invite') }}
                </h3>
                <button
                    type="button"
                    wire:click="closeInvite"
                    aria-label="{{ __('close') }}"
                    class="rounded-md p-1 text-ink-500 hover:bg-ink-900/5 hover:text-ink-900
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                >
                    <x-icon name="x" size="4"/>
                </button>
            </header>

            <form wire:submit.prevent="submitInvite" class="p-5">
                <label for="inviteEmail" class="mb-1 block text-caption font-medium text-ink-700">
                    {{ __('email') }}
                </label>
                <input
                    type="email"
                    id="inviteEmail"
                    wire:model="inviteEmail"
                    required
                    autocomplete="email"
                    placeholder="name@example.org"
                    @if ($error) aria-invalid="true" aria-describedby="inviteError" @endif
                    class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900
                           focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                />
                @if ($error)
                    <p id="inviteError" role="alert"
                       class="mt-1 text-caption text-danger">
                        {{ $error }}
                    </p>
                @endif

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button
                        type="button"
                        wire:click="closeInvite"
                        class="inline-flex items-center rounded-md bg-transparent px-4 py-2 text-body font-medium text-ink-700
                               hover:bg-ink-900/5
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink-900"
                    >
                        {{ __('cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on
                               hover:opacity-90
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <x-icon name="send" size="4"/>
                        {{ __('invite') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
