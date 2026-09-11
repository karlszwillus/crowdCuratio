{{--
    crowdCuratio - Curating together virtually
    Copyright (C) 2026 - berlinHistory e.V.

    Q4-Etappe 7 · E7-5 (2026-09-11, Karl-Feedback): Export-/Publish-
    Modal als wiederverwendbare Blade-Komponente. War vorher inline in
    `chapters/index.blade.php` — dadurch funktionierte der
    „Veröffentlichen"-Trigger nur auf dem Bearbeiten-Tab. Jetzt haengt
    das Modal an <x-projects.editor-actions> und ist damit auf jedem
    Chrome-Screen (Bearbeiten, Metadaten, Uebersetzen, Quellen,
    Berechtigungen) erreichbar.

    Zaehlungen und Publish-Check-Ergebnisse werden hier selbst
    berechnet — die Komponente laedt das Projekt mit `withPreviewTree`
    frisch. Ein zusaetzlicher Query pro Modal-Rendering, dafuer
    entkoppelt vom Seiten-Kontext.

    Props:
    - `project` — Basis-Projekt (id reicht, Rest wird nachgeladen).
--}}

@props(['project'])

@php
    /** @var \App\Models\Project $modalProject */
    $modalProject = \App\Models\Project::withPreviewTree()->findOrFail($project->id);

    $exportChapterCount = count($modalProject->chapters);
    $exportEntryCount = 0;
    $exportBlockCount = 0;
    foreach ($modalProject->chapters as $exportChapter) {
        $exportEntryCount += count($exportChapter->entries ?? []);
        foreach ($exportChapter->entries ?? [] as $exportEntry) {
            $exportBlockCount += count($exportEntry->mediaContent ?? []);
        }
    }

    // Publish-Check analog zum bisherigen Inline-Block.
    $publishCheckMissing = collect();
    foreach ($modalProject->chapters as $publishCheckChapter) {
        foreach ($publishCheckChapter->entries as $publishCheckEntry) {
            foreach ($publishCheckEntry->mediaContent as $publishCheckMc) {
                if ($publishCheckMc->content_type === \App\Models\Gallery::class && isset($publishCheckMc->gallery)) {
                    foreach ($publishCheckMc->gallery->images as $publishCheckImage) {
                        $publishCheckFields = collect([
                            empty(trim(strip_tags((string) $publishCheckImage->description))) ? __('publish_check_field_description') : null,
                            $publishCheckImage->copyrightImage ? null : __('publish_check_field_copyright'),
                            $publishCheckImage->originImage ? null : __('publish_check_field_origin'),
                        ])->filter()->values();
                        if ($publishCheckFields->isNotEmpty()) {
                            $publishCheckMissing->push([
                                'title' => trim($publishCheckImage->alt ?? '') !== '' ? $publishCheckImage->alt : __('gallery_image_untitled'),
                                'fields' => $publishCheckFields->implode(', '),
                                'anchor' => '#anchor_MediaContent_'.$publishCheckMc->id,
                            ]);
                        }
                    }
                }
                if ($publishCheckMc->content_type === \App\Models\Audiovisual::class && isset($publishCheckMc->audiovisual) && ! empty($publishCheckMc->audiovisual->link)) {
                    $publishCheckAv = $publishCheckMc->audiovisual;
                    $publishCheckAvFields = collect([
                        empty(trim(strip_tags((string) $publishCheckAv->copyright))) ? __('publish_check_field_copyright') : null,
                        empty(trim(strip_tags((string) $publishCheckAv->source))) ? __('publish_check_field_origin') : null,
                        empty(trim(strip_tags((string) $publishCheckAv->transcript))) ? __('publish_check_field_transcript') : null,
                    ])->filter()->values();
                    if ($publishCheckAvFields->isNotEmpty()) {
                        $publishCheckAvLabel = $publishCheckAv->type === 'audio' ? __('audio') : __('video');
                        $publishCheckMissing->push([
                            'title' => $publishCheckAvLabel.' · '.$publishCheckEntry->name,
                            'fields' => $publishCheckAvFields->implode(', '),
                            'anchor' => '#anchor_MediaContent_'.$publishCheckMc->id,
                        ]);
                    }
                }
            }
        }
    }
@endphp

<x-ui.modal id="previewModal" :title="__('export_modal_title')" size="lg">
    {{-- Karl 2026-09-11: Akzent-Auswahl im Export-Dialog entfaellt.
         Die Farbe kommt aus den Metadaten (Charakter-Default plus
         optionale Charakter-Alternative); Reader und PDF lesen sie
         direkt am Projekt. Ein zusaetzlicher Override an dieser Stelle
         hat Redakteure verwirrt und die Metadaten-Auswahl heimlich
         ueberschrieben. --}}
    <div x-data="{
             format: 'html',
             language: 'de',
             collapse: false,
         }">
        <form id="exportForm" action="{{ route('preview') }}" method="get">
            @csrf
            <input name="project" type="hidden" value="{{ $modalProject->id }}">

            <fieldset class="mb-6">
                <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                    {{ __('export_format_label') }}
                </legend>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label
                        :class="format === 'html' ? 'border-primary bg-primary/5' : 'border-line-200 bg-canvas-bg'"
                        class="flex cursor-pointer flex-col gap-1 rounded-md border-2 p-3 hover:border-ink-300">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="format_ui" value="html" x-model="format" class="size-4 accent-primary"/>
                            <span class="text-body font-semibold text-ink-900">{{ __('export_format_html_title') }}</span>
                        </div>
                        <p class="text-caption text-ink-500">{{ __('export_format_html_desc') }}</p>
                    </label>
                    <label
                        :class="format === 'pdf' ? 'border-primary bg-primary/5' : 'border-line-200 bg-canvas-bg'"
                        class="flex cursor-pointer flex-col gap-1 rounded-md border-2 p-3 hover:border-ink-300">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="format_ui" value="pdf" x-model="format" class="size-4 accent-primary"/>
                            <span class="text-body font-semibold text-ink-900">{{ __('export_format_pdf_title') }}</span>
                        </div>
                        <p class="text-caption text-ink-500">{{ __('export_format_pdf_desc') }}</p>
                    </label>
                </div>
                <input type="checkbox" name="pdf" value="1" x-show="false" :checked="format === 'pdf'" class="hidden"/>
            </fieldset>

            <fieldset class="mb-6">
                <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                    {{ __('export_check_label') }}
                </legend>
                @if ($publishCheckMissing->isNotEmpty())
                    @php
                        $checkFields = [];
                        foreach ($publishCheckMissing as $r) {
                            foreach (array_map('trim', explode(',', $r['fields'])) as $f) {
                                if ($f !== '') $checkFields[$f] = true;
                            }
                        }
                        $checkFieldList = implode(' · ', array_keys($checkFields));
                    @endphp
                    <details class="group rounded-md border border-warning-bg bg-warning-bg/30 px-4 py-3">
                        <summary class="flex cursor-pointer items-baseline justify-between gap-3 text-caption text-ink-900">
                            <span>
                                {{ trans_choice('export_check_missing_count', count($publishCheckMissing), ['count' => count($publishCheckMissing), 'fields' => $checkFieldList]) }}
                            </span>
                            <span class="text-caption text-ink-500 group-open:hidden">{{ __('export_check_show') }}</span>
                            <span class="text-caption text-ink-500 hidden group-open:inline">{{ __('export_check_hide') }}</span>
                        </summary>
                        <ul class="mt-2 space-y-1 text-caption text-ink-700">
                            @foreach ($publishCheckMissing as $publishCheckRow)
                                <li class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span>
                                        <span class="font-medium text-ink-900">{{ $publishCheckRow['title'] }}</span>
                                        <span class="text-ink-500"> — {{ $publishCheckRow['fields'] }}</span>
                                    </span>
                                    <a href="{{ route('projects.edit', $modalProject->id) . $publishCheckRow['anchor'] }}"
                                       data-dismiss="modal"
                                       class="text-caption text-primary hover:underline">
                                        {{ __('export_check_view') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @else
                    <p class="rounded-md bg-success-bg px-3 py-2 text-caption text-success">
                        ✓ {{ __('publish_check_all_clear') }}
                    </p>
                @endif
                <p class="mt-2 text-caption text-ink-500">{{ __('export_check_footer_hint') }}</p>
            </fieldset>

            <fieldset class="mb-6">
                <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                    {{ __('export_language_label') }}
                </legend>
                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center gap-2 text-body text-ink-900">
                        <input type="radio" name="language" value="de" x-model="language" class="size-4 accent-primary"/>
                        <span>{{ __('translate_lang_de') }}</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-body text-ink-900">
                        <input type="radio" name="language" value="en" x-model="language" class="size-4 accent-primary"/>
                        <span>{{ __('translate_lang_en') }}</span>
                    </label>
                </div>
                <p class="mt-2 text-caption text-ink-500">{{ __('export_language_consequence') }}</p>
            </fieldset>

            <fieldset class="mb-6">
                <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                    {{ __('export_display_label') }}
                </legend>
                <div class="flex flex-col gap-3">
                    <label class="inline-flex items-start gap-3">
                        <input type="checkbox" name="collapse" x-model="collapse" class="mt-1 size-4 accent-primary"/>
                        <span>
                            <span class="block text-body text-ink-900">{{ __('export_toggle_collapse_title') }}</span>
                            <span class="block text-caption text-ink-500">{{ __('export_toggle_collapse_desc') }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <input type="hidden" name="backgroundSecond" value="1"/>
        </form>
    </div>

    <x-slot:footer>
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <p class="text-caption text-ink-500">
                {{ __('export_scope', ['chapters' => $exportChapterCount, 'entries' => $exportEntryCount, 'blocks' => $exportBlockCount]) }}
            </p>
            <div class="flex items-center gap-2">
                <button type="button" data-dismiss="modal"
                        class="inline-flex items-center rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 hover:bg-line-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    {{ __('cancel') }}
                </button>
                <button type="submit" form="exportForm"
                        class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <x-icon name="download" size="4"/>
                    <span>{{ __('publish') }}</span>
                </button>
            </div>
        </div>
    </x-slot:footer>
</x-ui.modal>
