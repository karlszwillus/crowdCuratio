{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

Phase 5ab.5 (Design v6 § 7): Bestaetigungs-Dialog fuer das
Wiederherstellen einer Fassung.

Der Panel-Button feuert `history:restore-request` mit
`{revisionId, version, hasTranslations}`. Der Dialog:
- sagt zuerst, was NICHT passiert („aktuelle Fassung geht nicht verloren")
- warnt bei vorhandener Uebersetzung, dass sie erhalten bleibt aber als
  „Original nach der Uebersetzung geaendert" markiert wird (§ 4, § 7)
- Button --ink-900, NICHT --danger — die Aktion ist nicht destruktiv
- POST auf revisions.restore, dann Erfolgs-Banner mit Rueckgaengig-Hinweis
--}}

<div
    x-cloak
    x-data="{
        open: false,
        revisionId: null,
        version: null,
        hasTranslations: false,
        busy: false,
        error: null,
        openDialog(payload) {
            this.revisionId = payload.revisionId;
            this.version = payload.version;
            this.hasTranslations = payload.hasTranslations || false;
            this.error = null;
            this.busy = false;
            this.open = true;
        },
        closeDialog() {
            if (this.busy) return;
            this.open = false;
        },
        async confirmRestore() {
            if (this.busy || !this.revisionId) return;
            this.busy = true;
            this.error = null;
            try {
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                const url = @js(url('/revisions')) + '/' + this.revisionId + '/restore';
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                    },
                });
                if (!res.ok) {
                    throw new Error('restore failed with status ' + res.status);
                }
                const data = await res.json();
                this.open = false;
                window.ccToast?.(
                    @js(__('history_restore_success', ['version' => ''])).replace(
                        ':version',
                        data.newVersion || ''
                    ),
                    'success'
                );
                window.ccAnnounce?.(@js(__('history_restore_announce')));
                // Kleines Delay, damit die Erfolgsmeldung sichtbar bleibt
                // und der Nutzer sehen kann, was gerade passiert ist.
                setTimeout(() => window.location.reload(), 900);
            } catch (e) {
                this.error = @js(__('history_restore_error'));
                this.busy = false;
            }
        },
    }"
    @history:restore-request.window="openDialog($event.detail)"
    @keydown.escape.window="if (open && !busy) closeDialog()"
>
    <div
        x-show="open"
        @click="closeDialog()"
        class="fixed inset-0 z-50 bg-ink-900/40"
        style="transition: opacity 200ms ease-out;"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="history-restore-title"
        class="fixed left-1/2 top-1/2 z-50 w-[32rem] max-w-[calc(100vw-2rem)] -translate-x-1/2 -translate-y-1/2 rounded-lg border border-line-200 bg-paper-0 p-6 shadow-floating"
    >
        <h2 id="history-restore-title" class="text-title font-semibold text-ink-900">
            {{ __('history_restore_dialog_title') }}
        </h2>

        <div class="mt-3 space-y-3 text-body text-ink-700">
            <p>
                <span x-text="@js(__('history_restore_body_intro'))"></span>
                <strong>v<span x-text="version"></span></strong>.
            </p>
            <p class="rounded-md border border-line-200 bg-canvas-bg p-3 text-caption text-ink-700">
                {{ __('history_restore_body_kept') }}
            </p>
            <p x-show="hasTranslations" class="rounded-md border border-warning bg-warning-bg p-3 text-caption text-warning">
                {{ __('history_restore_body_translation_warning') }}
            </p>
            <p x-show="error" class="rounded-md border border-danger bg-danger-bg p-3 text-caption text-danger" x-text="error"></p>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2">
            <button
                type="button"
                @click="closeDialog()"
                :disabled="busy"
                class="rounded-md border border-line-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active disabled:opacity-50"
            >{{ __('cancel') }}</button>
            <button
                type="button"
                @click="confirmRestore()"
                :disabled="busy"
                class="rounded-md bg-ink-900 px-3 py-1.5 text-caption text-paper-0 hover:bg-ink-700 disabled:opacity-50"
            >
                <span x-show="!busy">{{ __('history_restore_confirm') }}</span>
                <span x-show="busy">{{ __('history_restore_busy') }}…</span>
            </button>
        </div>
    </div>
</div>
