{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

I1.2 (2026-08-21): Entry-Add-Modal auf v7 mit Alpine.

Das Modal wird per `entry-modal:open`-Event geoeffnet, das Payload
traegt `{chapterId, chapterName}`. Alpine merkt sich die Werte,
haengt sie ins hidden Feld chapterId und zeigt den Kapitelnamen im
Kopf an. Reset laeuft beim Schliessen — kein hidden.bs.modal-Handler
und keine .addEntry-jQuery-Handler mehr in chapters/index.

Modify-Pfad ist mit Phase 5c.6.b weg — Entry-Titel/Subtitle/
Beschreibung werden direkt im Entry-Card editiert.

Quill-Editor: der Container `#entryDescription` wird von chapters/
index.blade.php-JS initialisiert und beim Submit in ein hidden
Textarea `entryDescription` uebertragen. Das bleibt so wie bisher.
--}}

<div
    x-data="{
        open: false,
        chapterId: null,
        chapterName: '',
        submitting: false,
        openModal(detail) {
            this.chapterId = detail?.chapterId ?? null;
            this.chapterName = detail?.chapterName ?? '';
            this.open = true;
            this.$nextTick(() => this.$refs.title?.focus());
        },
        closeModal() {
            this.open = false;
            this.submitting = false;
            if (this.$refs.form) this.$refs.form.reset();
            const qed = document.getElementById('entryDescription');
            if (qed && window.Quill) {
                const q = Quill.find(qed);
                if (q) q.setText('');
            }
        },
    }"
    @entry-modal:open.window="openModal($event.detail)"
    @keydown.escape.window="if (open) closeModal()"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        @click.self="closeModal()"
        class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="entry-modal-title"
    >
        <div class="flex max-h-[calc(100vh-4rem)] w-full max-w-2xl flex-col overflow-hidden rounded-md border border-line-200 bg-paper-0 shadow-lg">
            <header class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                <h2 id="entry-modal-title" class="text-heading font-semibold text-ink-900">
                    {{ __('add_entry') }}
                    <span class="ml-2 text-caption font-normal text-ink-500" x-text="chapterName"></span>
                </h2>
                <button type="button" @click="closeModal()"
                        class="rounded-md p-2 text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        aria-label="{{ __('close') }}">
                    <x-icon name="x" size="5"/>
                </button>
            </header>

            <form x-ref="form" method="POST" action="{{ route('entries.store') }}"
                  enctype="multipart/form-data"
                  @submit="
                      submitting = true;
                      // Quill-HTML in ein hidden Feld schreiben — Quill
                      // haelt den Content im DOM, aber ohne assoziiertes
                      // input-Feld. Analog zum frueheren jQuery-Submit.
                      const q = window.Quill && Quill.find(document.getElementById('entryDescription'));
                      if (q) {
                          let hidden = $refs.form.querySelector('input[name=entryDescription]');
                          if (!hidden) {
                              hidden = document.createElement('input');
                              hidden.type = 'hidden';
                              hidden.name = 'entryDescription';
                              $refs.form.appendChild(hidden);
                          }
                          hidden.value = q.root.innerHTML;
                      }
                  "
                  class="flex-1 overflow-y-auto px-4 py-4">
                @csrf
                <input type="hidden" name="chapterId" :value="chapterId"/>

                <div class="space-y-4">
                    <div>
                        <label for="entryTitle" class="mb-1 block text-caption font-semibold text-ink-700">
                            {{ __('entry_title') }}
                        </label>
                        <input id="entryTitle" name="entryTitle" type="text"
                               x-ref="title"
                               class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                    </div>

                    <div>
                        <label for="entrySubtitle" class="mb-1 block text-caption font-semibold text-ink-700">
                            {{ __('entry_subtitle') }}
                        </label>
                        <input id="entrySubtitle" name="entrySubtitle" type="text"
                               class="block w-full rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                    </div>

                    <div>
                        <label class="mb-1 block text-caption font-semibold text-ink-700">
                            {{ __('entry_description') }}
                        </label>
                        <div id="entryDescription" class="rounded-md border border-line-200 bg-canvas-bg"></div>
                    </div>
                </div>
            </form>

            <footer class="flex flex-shrink-0 items-center justify-end gap-2 border-t border-line-200 px-4 py-3">
                <button type="button" @click="closeModal()"
                        class="rounded-md border border-ink-300 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active">
                    {{ __('cancel') }}
                </button>
                <button type="submit" form="{{ /* interne Form ohne id → submit ueber Alpine.$refs.form.submit() */ '' }}"
                        @click="$refs.form.requestSubmit(); submitting = true"
                        :disabled="submitting"
                        class="rounded-md bg-primary px-4 py-1.5 text-caption font-semibold text-paper-0 hover:opacity-90 disabled:opacity-40">
                    <span x-show="!submitting">{{ __('save') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('save') }} …</span>
                </button>
            </footer>
        </div>
    </div>
</div>
