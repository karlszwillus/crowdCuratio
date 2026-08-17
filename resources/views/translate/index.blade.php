<!--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>. -->

@extends('projects.layout')

@section('content')

    {{-- 5aa.3 § 4 in 5e-Vokabular: Struktur-Baum bleibt (im Layout-Sidebar),
         Kopfleiste mit Sprachpaar-Wähler und Filter, Zwei-Spalten-Tabelle
         mit Chips wie 13A, Fortschritts-Balken im Fuss. --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4
                border-b border-line-200 pb-3">
        <div class="min-w-0 flex-1">
            <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($project)"/>
        </div>
        <x-projects.tabs :project="$project" active="translate"/>
    </div>

    @if ($message = Session::get('success'))
        <div class="mb-4 rounded-md border border-success-bg bg-success-bg/40 px-4 py-3 text-body text-success">
            {{ $message }}
        </div>
    @endif

    <div x-data="{ onlyUntranslated: false }">
        {{-- Kopfzeile: Titel + Sprachpaar-Wähler + Filter + Speicherhinweis. --}}
        <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="mb-1 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                    {{ __('translate_language_pair_label') }}
                </p>
                <h1 class="text-title font-semibold text-ink-900">
                    <label class="cursor-pointer">
                        {{ __('translate_page_title') }}
                    </label>
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <select class="rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary" disabled>
                        <option>{{ __('translate_lang_de') }}</option>
                    </select>
                    <span class="text-body text-ink-500">→</span>
                    <select class="rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary">
                        <option value="en">{{ __('translate_lang_en') }}</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 text-body text-ink-700">
                    <input type="checkbox" x-model="onlyUntranslated" class="size-4 rounded border-line-200 text-primary focus:ring-primary"/>
                    <span>{{ __('translate_filter_only_untranslated') }}</span>
                </label>
                <p class="text-caption text-ink-500">{{ __('translate_save_hint') }}</p>
            </div>
        </header>

        {{-- 5aa.3: Alle englischen Übersetzungen wandern als benannter
             `translations[<Model>.<id>.<field>]`-Payload an den
             Bulk-Save-Endpoint. Auto-Save-on-Blur pro Feld ist die
             nächste Ausbaustufe. --}}
        <form action="{{ route('translate.save', $project->id) }}" method="POST" id="translate_form">
            @csrf

            @php
                // 5aa.3: Kleiner Helper — zwei Spalten mit Original links + Uebersetzungs-Input rechts.
                // Der `data-translated` Attribute wird von der Alpine-Filter-Logik gelesen, um
                // uebersetzte Felder auszublenden wenn „Nur unuebersetzte" aktiv ist.
                $renderField = function ($label, $originalHtml, $translationHtml, $inputName, $placeholder, $isTranslated) {
                    return view('translate.field-partial', compact(
                        'label', 'originalHtml', 'translationHtml', 'inputName', 'placeholder', 'isTranslated'
                    ));
                };
            @endphp

            @isset($data['data'])
                @foreach($data['data'] as $chapterIndex => $chapter)
                    @php
                        // Zaehlt Original- und uebersetzte Felder in dieser Kapitel-Gruppe.
                        $groupFields = [
                            ['label' => __('chapter_title'), 'original' => $chapter->name, 'translation' => $chapter->getTranslation('name', 'en', false)],
                            ['label' => __('chapter_subtitle'), 'original' => $chapter->subtitle, 'translation' => $chapter->getTranslation('subtitle', 'en', false)],
                            ['label' => __('chapter_description'), 'original' => $chapter->description, 'translation' => $chapter->getTranslation('description', 'en', false)],
                        ];
                        $groupTotal = count($groupFields);
                        $groupDone = collect($groupFields)->filter(fn ($f) => ! empty(trim(strip_tags((string) $f['translation']))))->count();
                    @endphp

                    <section class="mb-8 rounded-md border border-line-200 bg-paper-0">
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                            <span class="inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                <x-icon name="square" size="3"/>
                                <span>{{ __('translate_group_chapter') }} {{ $chapterIndex + 1 }}</span>
                                <span class="text-ink-500">·</span>
                                <span class="truncate text-ink-500">{{ $chapter->name }}</span>
                            </span>
                            <span data-section-counter class="text-caption {{ $groupDone === $groupTotal ? 'text-success' : 'text-warning' }}">
                                {{ $groupDone === $groupTotal ? '✓' : '⚠' }}
                                {{ trans_choice('translate_group_count_all', $groupTotal, ['done' => $groupDone, 'total' => $groupTotal]) }}
                            </span>
                        </header>

                        <div class="hidden bg-line-100 px-4 py-2 text-caption font-mono uppercase tracking-wider text-ink-500 md:grid md:grid-cols-2 md:gap-4">
                            <span>{{ __('translate_lang_de') }} · {{ __('translate_column_original') }}</span>
                            <span>{{ __('translate_lang_en') }} · {{ __('translate_column_translation') }}</span>
                        </div>

                        <div class="divide-y divide-line-200">
                            @include('translate.field-partial', ['label' => __('chapter_title'), 'originalHtml' => $chapter->name, 'translationHtml' => $chapter->getTranslation('name', 'en', false), 'inputName' => "translations[Chapter.$chapter->id.name]", 'placeholder' => __('translate_placeholder_title')])
                            @include('translate.field-partial', ['label' => __('chapter_subtitle'), 'originalHtml' => $chapter->subtitle, 'translationHtml' => $chapter->getTranslation('subtitle', 'en', false), 'inputName' => "translations[Chapter.$chapter->id.subtitle]", 'placeholder' => __('translate_placeholder_subtitle')])
                            @include('translate.field-partial', ['label' => __('chapter_description'), 'originalHtml' => $chapter->description, 'translationHtml' => $chapter->getTranslation('description', 'en', false), 'inputName' => "translations[Chapter.$chapter->id.description]", 'placeholder' => __('translate_placeholder_description'), 'multiline' => true])
                        </div>
                    </section>

                    @if(isset($chapter->entries) && count($chapter->entries) > 0)
                        @foreach($chapter->entries as $entryIndex => $entry)
                            @php
                                $entryFields = [
                                    $entry->getTranslation('name', 'en', false),
                                    $entry->getTranslation('subtitle', 'en', false),
                                    $entry->getTranslation('description', 'en', false),
                                ];
                                $entryTotal = count($entryFields);
                                $entryDone = collect($entryFields)->filter(fn ($v) => ! empty(trim(strip_tags((string) $v))))->count();
                            @endphp
                            <section class="mb-8 ml-6 rounded-md border border-line-200 bg-paper-0">
                                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                                    <span class="inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                        <x-icon name="pilcrow" size="3"/>
                                        <span>{{ __('translate_group_entry') }} {{ $entryIndex + 1 }}</span>
                                        <span class="text-ink-500">·</span>
                                        <span class="truncate text-ink-500">{{ $entry->name }}</span>
                                    </span>
                                    <span data-section-counter class="text-caption {{ $entryDone === $entryTotal ? 'text-success' : 'text-warning' }}">
                                        {{ $entryDone === $entryTotal ? '✓' : '⚠' }}
                                        {{ trans_choice('translate_group_count_all', $entryTotal, ['done' => $entryDone, 'total' => $entryTotal]) }}
                                    </span>
                                </header>

                                <div class="divide-y divide-line-200">
                                    @include('translate.field-partial', ['label' => __('entry_title'), 'originalHtml' => $entry->name, 'translationHtml' => $entry->getTranslation('name', 'en', false), 'inputName' => "translations[Entry.$entry->id.name]", 'placeholder' => __('translate_placeholder_title')])
                                    @include('translate.field-partial', ['label' => __('entry_subtitle'), 'originalHtml' => $entry->subtitle, 'translationHtml' => $entry->getTranslation('subtitle', 'en', false), 'inputName' => "translations[Entry.$entry->id.subtitle]", 'placeholder' => __('translate_placeholder_subtitle')])
                                    @include('translate.field-partial', ['label' => __('entry_description'), 'originalHtml' => $entry->description, 'translationHtml' => $entry->getTranslation('description', 'en', false), 'inputName' => "translations[Entry.$entry->id.description]", 'placeholder' => __('translate_placeholder_description'), 'multiline' => true])
                                </div>
                            </section>

                            {{-- 5aa.3-Followup: Inhalte des Eintrags — Text, Galerie und
                                 Audio/Video mit ihren übersetzbaren Feldern. --}}
                            @foreach($entry->mediaContent ?? [] as $mc)
                                @php
                                    $mcType = $mc->content_type ?? null;
                                    $mcClass = null;
                                    $mcTitle = null;
                                    if ($mcType === \App\Models\Text::class && isset($mc->text)) {
                                        $mcClass = 'Text';
                                        $mcTitle = 'Text';
                                    } elseif ($mcType === \App\Models\Gallery::class && isset($mc->gallery)) {
                                        $mcClass = 'Gallery';
                                        $mcTitle = $mc->gallery->title ?: 'Galerie';
                                    } elseif ($mcType === \App\Models\Audiovisual::class && isset($mc->audiovisual)) {
                                        $mcClass = 'Audiovisual';
                                        $mcTitle = $mc->audiovisual->type === 'audio' ? 'Audio' : 'Video';
                                    }
                                @endphp
                                @if ($mcClass === 'Text')
                                    <section class="mb-8 ml-12 rounded-md border border-line-200 bg-paper-0">
                                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                                            <span class="inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                                <x-icon name="type" size="3"/>
                                                <span>{{ __('translate_group_block') }} · {{ $mcTitle }}</span>
                                            </span>
                                        </header>
                                        <div class="divide-y divide-line-200">
                                            @include('translate.field-partial', ['label' => __('text'), 'originalHtml' => $mc->text->text, 'translationHtml' => $mc->text->getTranslation('text', 'en', false), 'inputName' => "translations[Text.{$mc->text->id}.text]", 'placeholder' => __('translate_placeholder_text'), 'multiline' => true])
                                        </div>
                                    </section>
                                @elseif ($mcClass === 'Gallery')
                                    <section class="mb-8 ml-12 rounded-md border border-line-200 bg-paper-0">
                                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                                            <span class="inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                                <x-icon name="image" size="3"/>
                                                <span>{{ __('translate_group_block') }} · {{ __('block_type_gallery') }} „{{ $mcTitle }}"</span>
                                            </span>
                                        </header>
                                        <div class="divide-y divide-line-200">
                                            @include('translate.field-partial', ['label' => __('gallery_title'), 'originalHtml' => $mc->gallery->title, 'translationHtml' => $mc->gallery->getTranslation('title', 'en', false), 'inputName' => "translations[Gallery.{$mc->gallery->id}.title]", 'placeholder' => __('translate_placeholder_title')])
                                            @include('translate.field-partial', ['label' => __('gallery_subtitle'), 'originalHtml' => $mc->gallery->subtitle, 'translationHtml' => $mc->gallery->getTranslation('subtitle', 'en', false), 'inputName' => "translations[Gallery.{$mc->gallery->id}.subtitle]", 'placeholder' => __('translate_placeholder_subtitle')])
                                            @include('translate.field-partial', ['label' => __('gallery_description'), 'originalHtml' => $mc->gallery->description, 'translationHtml' => $mc->gallery->getTranslation('description', 'en', false), 'inputName' => "translations[Gallery.{$mc->gallery->id}.description]", 'placeholder' => __('translate_placeholder_description'), 'multiline' => true])
                                            @foreach ($mc->gallery->images ?? [] as $image)
                                                @include('translate.field-partial', ['label' => __('title').' · '.($image->alt ?: '#'.$image->id), 'originalHtml' => $image->alt, 'translationHtml' => $image->getTranslation('alt', 'en', false), 'inputName' => "translations[Image.{$image->id}.alt]", 'placeholder' => __('translate_placeholder_title')])
                                                @include('translate.field-partial', ['label' => __('gallery_image_description').' · '.($image->alt ?: '#'.$image->id), 'originalHtml' => $image->description, 'translationHtml' => $image->getTranslation('description', 'en', false), 'inputName' => "translations[Image.{$image->id}.description]", 'placeholder' => __('translate_placeholder_description'), 'multiline' => true])
                                            @endforeach
                                        </div>
                                    </section>
                                @elseif ($mcClass === 'Audiovisual')
                                    <section class="mb-8 ml-12 rounded-md border border-line-200 bg-paper-0">
                                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line-200 px-4 py-3">
                                            <span class="inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                                <x-icon name="{{ $mc->audiovisual->type === 'audio' ? 'volume-2' : 'play' }}" size="3"/>
                                                <span>{{ __('translate_group_block') }} · {{ $mcTitle }}</span>
                                            </span>
                                        </header>
                                        <div class="divide-y divide-line-200">
                                            @include('translate.field-partial', ['label' => __('transcript'), 'originalHtml' => $mc->audiovisual->transcript, 'translationHtml' => $mc->audiovisual->getTranslation('transcript', 'en', false), 'inputName' => "translations[Audiovisual.{$mc->audiovisual->id}.transcript]", 'placeholder' => __('translate_placeholder_text'), 'multiline' => true])
                                            @include('translate.field-partial', ['label' => __('copyright'), 'originalHtml' => $mc->audiovisual->copyright, 'translationHtml' => $mc->audiovisual->getTranslation('copyright', 'en', false), 'inputName' => "translations[Audiovisual.{$mc->audiovisual->id}.copyright]", 'placeholder' => __('translate_placeholder_copyright')])
                                            @include('translate.field-partial', ['label' => __('origin'), 'originalHtml' => $mc->audiovisual->source, 'translationHtml' => $mc->audiovisual->getTranslation('source', 'en', false), 'inputName' => "translations[Audiovisual.{$mc->audiovisual->id}.source]", 'placeholder' => __('translate_placeholder_source')])
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                        @endforeach
                    @endif
                @endforeach
            @endisset

            @isset($data['percentageOfTranslation'])
                <div class="sticky bottom-0 -mx-4 mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-line-200 bg-paper-0/95 px-6 py-3 shadow-medium backdrop-blur">
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <div class="h-2 w-full max-w-md overflow-hidden rounded-full bg-line-100">
                            <div data-progress-bar class="h-full bg-brand-bar" style="width: {{ $data['percentageOfTranslation'] }}%"></div>
                        </div>
                        <p data-progress-label class="text-caption text-ink-500">{{ $data['percentageOfTranslation'] }}%</p>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <x-icon name="save" size="4"/>
                        <span>{{ __('translate_save_all') }}</span>
                    </button>
                </div>
            @endisset
        </form>
    </div>

@endsection
