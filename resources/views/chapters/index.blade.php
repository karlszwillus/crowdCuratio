<!--
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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
@once
    @push('scripts')

    @endpush
@endonce
@section('log')
    <livewire:sidebar-tree :project="$project" :key="'sidebar-tree-'.$project->id" />
@endsection

@section('sidebar')

    @include('projects.description')

@endsection

@section('main')
    {{-- Q3-Politur G4 (2026-08-20) / UX-06: Rollen-Hinweis fuer Nutzer:innen,
         die den Editor lesen aber nicht editieren koennen. Steht als
         eigene Zeile unter dem Editor-Chrome, damit Reader/Reviewer
         wissen, warum die Save-Aktionen fehlen. --}}
    @php
        $viewerCanUpdate = Auth::user()?->can('update', $project) ?? false;
        $viewerCanPreview = Auth::user()?->can('preview') ?? false;
        $roleHint = null;
        if (! $viewerCanUpdate) {
            $roleHint = $viewerCanPreview ? __('role_hint_reviewer') : __('role_hint_reader');
        }
    @endphp

    {{-- Editor-Chrome (Handoff v4 Screen 02, Phase 5-D.5):
         Brotkrumen links, Segmented Control mittig, Publish-Button
         und ⋮-Menü rechts. Sticky an der Canvas-Oberkante, damit
         Kontext und Publish beim Scrollen im Blick bleiben. --}}
    <div class="sticky top-0 z-20 -mx-6 -mt-6 mb-6 flex flex-wrap items-center justify-between gap-4
                border-b border-line-200 bg-canvas-bg/95 px-6 py-3
                backdrop-blur supports-[backdrop-filter]:bg-canvas-bg/80">
        <div class="min-w-0 flex-1">
            <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($data)" />
        </div>

        <div class="flex items-center gap-3">
            @can('update', $project)
                <x-projects.tabs :project="$project" active="edit"/>
            @endcan

            @if (Auth::user()->can('publish', $project) || Auth::user()->can('preview'))
                <button
                    type="button"
                    data-toggle="modal"
                    data-target="#previewModal"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2
                           text-body font-medium text-primary-on hover:opacity-90
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                >
                    {{ __('publish') }}
                </button>
            @endif

            @can('update', $project)
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        @click="open = !open"
                        @click.outside="open = false"
                        aria-haspopup="true"
                        :aria-expanded="open"
                        class="inline-flex size-9 items-center justify-center rounded-md text-ink-500
                               hover:bg-line-100 hover:text-ink-900
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                        title="{{ __('more_actions') }}"
                        :aria-label="'{{ __('more_actions') }}'"
                    >
                        <x-icon name="ellipsis-vertical" size="5"/>
                    </button>
                    <div
                        x-show="open"
                        x-transition
                        x-cloak
                        class="absolute right-0 z-30 mt-1 min-w-[14rem]
                               rounded-md border border-line-200 bg-paper-0 py-1 shadow-popover"
                    >
                        @if (Auth::user()->can('publish', $project) || Auth::user()->can('preview'))
                            <button type="button"
                                    data-toggle="modal"
                                    data-target="#previewModal"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                                <x-icon name="file-text" size="4"/>
                                <span>{{ __('pdf') }}</span>
                            </button>
                            <button type="button"
                                    data-toggle="modal"
                                    data-target="#previewModal"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                                <x-icon name="globe" size="4"/>
                                <span>{{ __('preview') }}</span>
                            </button>
                            <a href="https://app.crowdcurat.io/downloads/html.zip"
                               target="_blank" rel="noopener"
                               class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-900 hover:bg-line-100/60">
                                <x-icon name="download" size="4"/>
                                <span>{{ __('download') }}</span>
                            </a>
                            <div class="my-1 border-t border-line-100"></div>
                        @endif
                        <form action="{{ route('projects.destroy', $project->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                onclick="return confirm('{{ __('message_delete_confirm') }}')"
                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-danger hover:bg-danger-bg"
                            >
                                <x-icon name="trash-2" size="4"/>
                                <span>{{ __('delete_project') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    @if ($roleHint)
        <p class="mb-4 rounded-md border border-line-200 bg-paper-0 px-4 py-2 text-caption text-ink-700"
           role="note">
            {{ $roleHint }}
        </p>
    @endif

    @if ($message = Session::get('success'))
        <x-ui.banner type="success" class="mb-4" dismissible>
            {{ $message }}
        </x-ui.banner>
    @endif
    @if ($errors->any())
        <x-ui.banner type="danger" class="mb-4" :title="__('whoops')">
            {{ __('message_problem_input') }}
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.banner>
    @endif
    @if(isset($data) /**&& count($data) > 0*/)
        <div class="row project mb-4">
            <div class="col-sm-2">
                @if($project->logo) <img src="{{route('image', $project->logo)}}" alt="{{$project->logo}}" class="logo"> @endif
            </div>
            <div class="col-sm-9">
                <h1>{{$project->name}}</h1>
                <p>{!! $project->description !!}</p>
            </div>
        </div>
        <ul class="list-group ui-sortable-chapter sortable_list_chapter connectedSortableChapter" id="groupsList" data-reorder-element="chapter" data-reorder-url="{{ route('chapter.drag') }}" data-reorder-project="{{ $project->id }}">
            @foreach($data->chapters as $key => $chapter)
                @php
                    // Design v6 § 2 (uebersetzt auf 5e-Vokabular): Kapitel als Klammer.
                    // Alle Kapitel ruhen in line-200; nur das aktuell bearbeitete
                    // wechselt auf brand-bar. „Aktuell" heisst hier: irgendwo im
                    // Kapitel liegt der Tastatur-/Maus-Fokus (focus-within).
                    $chapterEntryCount = isset($chapter->entries) ? count($chapter->entries) : 0;
                @endphp
                <li class="chapter group border-l-[3px] border-line-200 focus-within:border-brand-bar pl-4 transition-colors" data-chapter="{{$chapter->id}}" data-project="{{$project->id}}" data-history-subject="Chapter:{{$chapter->id}}" id="{{$chapter->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                    {{-- Kapitel = Klammer (Design v6 § 2, in 5e-Vokabular).
                         Rail links über die ganze Gruppe; Titel + Untertitel
                         + Description sitzen offen auf dem Canvas. Der
                         Kapitel-Chip nennt Nummer und Abschnittsanzahl,
                         Aktionen sitzen im ⋯-Menü der Titelzeile. --}}
                    <div id="{{$chapter->id}}" class="mb-10">
                        <header class="mb-4 flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1" id="anchor_Chapter_{{$chapter->id}}">
                                {{-- Kapitel-Chip: Mono-Caps Nummer + Abschnitts-Zaehler. --}}
                                <div class="mb-2 inline-flex items-center gap-2 rounded-md bg-line-100 px-2 py-0.5 text-caption font-semibold uppercase tracking-wider text-ink-700">
                                    <x-icon name="square" size="3"/>
                                    <span>{{ __('chapter_chip_label') }} {{ $loop->iteration }}</span>
                                    <span class="text-ink-500">·</span>
                                    <span class="text-ink-500">{{ trans_choice('chapter_chip_entries', $chapterEntryCount, ['count' => $chapterEntryCount]) }}</span>
                                </div>
                                @can('update', $project)
                                    {{-- Phase 5ab.4: data-history-field-Wrapper, damit der
                                         Diff-Modus die Diff-HTML pro Feld einhaengen kann.
                                         Wrapper liegen um die Inline-Editors, weil die
                                         eigentliche Feld-DOM ins Livewire-Snapshot laeuft. --}}
                                    <div data-history-field="name">
                                        <livewire:inline-editor
                                            :model="$chapter"
                                            field="name"
                                            rules="nullable|string|max:255"
                                            :label="__('chapter_title')"
                                            :variant="'title'"
                                            :key="'chapter-name-'.$chapter->id"
                                        />
                                    </div>
                                    <div data-history-field="subtitle">
                                        <livewire:inline-editor
                                            :model="$chapter"
                                            field="subtitle"
                                            rules="nullable|string|max:255"
                                            :label="__('chapter_subtitle')"
                                            :variant="'subtitle'"
                                            :key="'chapter-subtitle-'.$chapter->id"
                                        />
                                    </div>
                                @else
                                    @if (! empty(trim((string) $chapter->name)))
                                        <h2 data-history-field="name" class="text-title font-semibold text-ink-900">{!! $chapter->name !!}</h2>
                                    @endif
                                    @if (! empty(trim((string) $chapter->subtitle)))
                                        <p data-history-field="subtitle" class="mt-1 text-body text-ink-500">{!! $chapter->subtitle !!}</p>
                                    @endif
                                @endcan
                            </div>

                            <div class="flex shrink-0 items-center gap-1 text-ink-500">
                                {{-- Phase 5ab.3: Verlauf-Trigger — oeffnet das Panel
                                     rechts statt Full-Page-Reload auf ?log=. --}}
                                <x-ui.history-trigger
                                    subjectType="Chapter"
                                    :subjectId="$chapter->id"
                                />

                                @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                    <x-comment.trigger
                                        commentableType="App\Models\Chapter"
                                        :commentableId="$chapter->id"
                                        :count="isset($chapter->comments) ? count($chapter->comments) : 0"
                                    />
                                @endif

                                {{-- ⋯-Menue: Löschen liegt hier statt in der Titelzeile.
                                     Duplizieren und Verschieben sind Design-Ziel aus v6,
                                     werden aber erst gebaut, wenn Backend da ist —
                                     aktuell disabled mit Tooltip auf Backlog. --}}
                                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button"
                                                @click="open = !open"
                                                @click.outside="open = false"
                                                :aria-expanded="open"
                                                aria-haspopup="menu"
                                                aria-label="{{ __('more_actions') }}"
                                                title="{{ __('more_actions') }}"
                                                class="inline-flex size-11 items-center justify-center rounded-md hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                            <x-icon name="ellipsis-vertical" size="4"/>
                                        </button>
                                        <div x-show="open"
                                             x-transition
                                             x-cloak
                                             role="menu"
                                             class="absolute right-0 z-30 mt-1 min-w-[14rem] rounded-md border border-line-200 bg-paper-0 py-1 shadow-popover">
                                            <button type="button"
                                                    disabled
                                                    aria-disabled="true"
                                                    title="{{ __('feature_not_yet') }}"
                                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-400 opacity-60">
                                                <x-icon name="copy" size="4"/>
                                                <span>{{ __('chapter_menu_duplicate') }}</span>
                                            </button>
                                            <button type="button"
                                                    disabled
                                                    aria-disabled="true"
                                                    title="{{ __('feature_not_yet') }}"
                                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-400 opacity-60">
                                                <x-icon name="move" size="4"/>
                                                <span>{{ __('chapter_menu_move') }}</span>
                                            </button>
                                            <div class="my-1 border-t border-line-100"></div>
                                            <form action="{{ route('chapters.destroy',$chapter->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-danger hover:bg-danger-bg">
                                                    <x-icon name="trash-2" size="4"/>
                                                    <span>{{ __('delete_chapter') }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </header>

                        {{-- Kapitel-Beschreibung als Rich-Text-Editor,
                             direkt unter dem Section-Header. --}}
                        @can('update', $project)
                            <div data-history-field="description">
                                <livewire:rich-text-editor
                                    :model="$chapter"
                                    field="description"
                                    rules="nullable|string"
                                    :label="__('chapter_description')"
                                    :key="'chapter-description-'.$chapter->id"
                                />
                            </div>
                        @else
                            @if (! empty(trim(strip_tags((string) $chapter->description))))
                                <p data-history-field="description" class="text-body text-ink-700">{!! $chapter->description !!}</p>
                            @endif
                        @endcan

                        {{-- Grosser Vertikalspace zwischen Kapitel-Zone
                             und den enthaltenen Entry-Karten, damit die
                             Ebenen visuell nicht in einen 'Kapitel-
                             Kasten' verschmelzen. Der Space macht klar:
                             die Karten sitzen IN der Zone. --}}
                        <div class="h-16" aria-hidden="true"></div>
                        <div class="collapse in" id="chapter_{{$chapter->id}}" aria-expanded="false">
                            @if(isset($chapter->entries) && count($chapter->entries) >0)
                                <ul class="list-group ui-sortable-entry sortable_list_entry connectedSortableEntry" id="{{$chapter->id}}" data-reorder-element="entry" data-reorder-url="{{ route('chapter.drag') }}">
                                    @foreach($chapter->entries as $entry)
                                        <li class="entry group" data-chapter="{{$chapter->id}}" data-entry="{{$entry->id}}" data-history-subject="Entry:{{$entry->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                            {{-- Entry als Karte mit Mono-Caps-Label
                                                 (Handoff v4 Screen 02: „EINTRAG · KAPITEL 2").
                                                 Bezug zum umschließenden Kapitel steht
                                                 explizit im Kopf, nicht ueber CSS-Einrueckung. --}}
                                            <div id="P-{{$project->id}}-C-{{$chapter->id}}-entry-{{$entry->id}}"
                                                 class="mb-6 rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                                                {{-- Design v6 § 3 (in 5e-Vokabular): Chip nennt eigene Nummer + Kapitelnamen,
                                                     nicht nur die Elternnummer. Löschen wandert in ⋯-Menü unten. --}}
                                                <p class="mb-2 inline-flex items-center gap-2 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                                                    <span>{{ __('entry_chip_label') }} {{ $loop->iteration }}</span>
                                                    <span>·</span>
                                                    <span>{{ __('entry_chip_in') }} „{{ $chapter->name }}"</span>
                                                </p>
                                                <header class="mb-3 flex items-start justify-between gap-4">
                                                    <div class="min-w-0 flex-1" id="anchor_Entry_{{$entry->id}}">
                                                        @can('update', $project)
                                                            <div data-history-field="name">
                                                                <livewire:inline-editor
                                                                    :model="$entry"
                                                                    field="name"
                                                                    rules="nullable|string|max:255"
                                                                    :label="__('entry_title')"
                                                                    :variant="'heading'"
                                                                    :key="'entry-name-'.$entry->id"
                                                                />
                                                            </div>
                                                            <div data-history-field="subtitle">
                                                                <livewire:inline-editor
                                                                    :model="$entry"
                                                                    field="subtitle"
                                                                    rules="nullable|string|max:255"
                                                                    :label="__('entry_subtitle')"
                                                                    :variant="'subtitle'"
                                                                    :key="'entry-subtitle-'.$entry->id"
                                                                />
                                                            </div>
                                                        @else
                                                            @if (! empty(trim((string) $entry->name)))
                                                                <h3 data-history-field="name" class="text-heading font-semibold text-ink-900">{!! $entry->name !!}</h3>
                                                            @endif
                                                            @if (! empty(trim((string) $entry->subtitle)))
                                                                <p data-history-field="subtitle" class="mt-1 text-body text-ink-500">{!! $entry->subtitle !!}</p>
                                                            @endif
                                                        @endcan
                                                    </div>

                                                    <div class="flex shrink-0 items-center gap-1 text-ink-500">
                                                        <x-ui.history-trigger
                                                            subjectType="Entry"
                                                            :subjectId="$entry->id"
                                                        />

                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                            <x-comment.trigger
                                                                commentableType="App\Models\Entry"
                                                                :commentableId="$entry->id"
                                                                :count="isset($entry->comments) ? count($entry->comments) : 0"
                                                            />
                                                        @endif

                                                        @if(in_array('edit', $listPermissions) || Auth::user()->can('delete', $project))
                                                            <div x-data="{ open: false }" class="relative">
                                                                <button type="button"
                                                                        @click="open = !open"
                                                                        @click.outside="open = false"
                                                                        :aria-expanded="open"
                                                                        aria-haspopup="menu"
                                                                        aria-label="{{ __('more_actions') }}"
                                                                        title="{{ __('more_actions') }}"
                                                                        class="inline-flex size-11 items-center justify-center rounded-md hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                    <x-icon name="ellipsis-vertical" size="4"/>
                                                                </button>
                                                                <div x-show="open"
                                                                     x-transition
                                                                     x-cloak
                                                                     role="menu"
                                                                     class="absolute right-0 z-30 mt-1 min-w-[14rem] rounded-md border border-line-200 bg-paper-0 py-1 shadow-popover">
                                                                    <button type="button"
                                                                            disabled
                                                                            aria-disabled="true"
                                                                            title="{{ __('feature_not_yet') }}"
                                                                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-400 opacity-60">
                                                                        <x-icon name="copy" size="4"/>
                                                                        <span>{{ __('entry_menu_duplicate') }}</span>
                                                                    </button>
                                                                    <button type="button"
                                                                            disabled
                                                                            aria-disabled="true"
                                                                            title="{{ __('feature_not_yet') }}"
                                                                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-ink-400 opacity-60">
                                                                        <x-icon name="move" size="4"/>
                                                                        <span>{{ __('entry_menu_move') }}</span>
                                                                    </button>
                                                                    <div class="my-1 border-t border-line-100"></div>
                                                                    <form action="{{ route('entries.destroy',$entry->id) }}" method="POST">
                                                                        @csrf
                                                                        <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                        @method('DELETE')
                                                                        <button type="submit"
                                                                                onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                                class="flex w-full items-center gap-2 px-4 py-2 text-left text-body text-danger hover:bg-danger-bg">
                                                                            <x-icon name="trash-2" size="4"/>
                                                                            <span>{{ __('delete_entry') }}</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </header>

                                                @can('update', $project)
                                                    <div data-history-field="description">
                                                        <livewire:rich-text-editor
                                                            :model="$entry"
                                                            field="description"
                                                            rules="nullable|string"
                                                            :label="__('entry_description')"
                                                            :key="'entry-description-'.$entry->id"
                                                        />
                                                    </div>
                                                @else
                                                    @if (! empty(trim(strip_tags((string) $entry->description))))
                                                        <p data-history-field="description" class="text-body text-ink-700">{!! $entry->description !!}</p>
                                                    @endif
                                                @endcan
                                            </div>
                                                    @if(isset($entry->mediaContent) && count($entry->mediaContent) > 0)
                                                        <div id="entry_{{$entry->id}}">
                                                            <ul class="list-group  ui-sortable-content sortable_list_content connectedSortableContent" data-entry="{{$entry->id}}" id="{{$entry->id}}" data-reorder-element="content" data-reorder-url="{{ route('chapter.drag') }}">
                                                                @foreach($entry->mediaContent as $item)
                                                                    @if($item->content_type == 'App\Models\Text')
                                                                        @isset($item->text->text)
                                                                            <li class="item text content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" data-history-subject="Text:{{$item->text->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <x-ui.block-card type="text" id="anchor_MediaContent_{{$item->id}}" class="mb-4" :save-slot="'Text-'.$item->text->id">
                                                                                    <x-slot:actions>
                                                                                        {{-- Design v6 § 4 (in 5e-Vokabular): Text-Block-Aktionen
                                                                                             wandern aus der Fußzeile in den Blockkopf, analog zu
                                                                                             Galerie und Audio/Video. --}}
                                                                                        <x-ui.history-trigger
                                                                                            subjectType="Text"
                                                                                            :subjectId="$item->text->id"
                                                                                        />
                                                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                            <x-comment.trigger
                                                                                                commentableType="App\Models\Text"
                                                                                                :commentableId="$item->text->id"
                                                                                                :count="isset($item->text->comments) ? count($item->text->comments) : 0"
                                                                                            />
                                                                                        @endif
                                                                                        @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                            <form action="{{ route('text.delete',$item->text->id) }}"
                                                                                                  method="POST" class="inline-flex">
                                                                                                @csrf
                                                                                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                                @method('DELETE')
                                                                                                <button type="submit"
                                                                                                        onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                                                        title="{{__('delete_block')}}"
                                                                                                        class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                                                                                                    <x-icon name="trash-2" size="4"/>
                                                                                                </button>
                                                                                            </form>
                                                                                        @endif
                                                                                    </x-slot:actions>
                                                                                    <div>
                                                                                        <div class="text-scrollbar overflow-auto">
                                                                                            @can('update', $project)
                                                                                                <div data-history-field="text">
                                                                                                    <livewire:rich-text-editor
                                                                                                        :model="$item->text"
                                                                                                        field="text"
                                                                                                        rules="nullable|string"
                                                                                                        :label="__('text_content')"
                                                                                                        :key="'text-content-'.$item->text->id" />
                                                                                                </div>
                                                                                                {{-- 5z.5: Absatz-Legende beantwortet die Review-Frage
                                                                                                     „Hier nur ein BR?" im Editor statt in der Vorschau. --}}
                                                                                                <p class="mt-2 text-caption text-ink-500">
                                                                                                    {{ __('text_paragraph_legend') }}
                                                                                                </p>
                                                                                            @else
                                                                                                <p data-history-field="text">{!! html_entity_decode($item->text->text) !!}</p>
                                                                                            @endcan
                                                                                        </div>
                                                                                    </div>
                                                                                        {{-- Copyright + Quelle sind Pflichtfelder und
                                                                                             sitzen sichtbar am Fuss der Block-Card
                                                                                             (P1.4 aus Designer-Review: eingeklappt
                                                                                             darf nur Optionales sein). Sternchen
                                                                                             und aria-required kommen ueber das
                                                                                             rules-Prop des source-picker. --}}
                                                                                        <div class="mt-4 border-t border-line-100 pt-3">
                                                                                            @can('update', $project)
                                                                                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                                                                    <div>
                                                                                                        <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                            {{ __('copyright') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                        </label>
                                                                                                        <livewire:source-picker
                                                                                                            :model="$item->text"
                                                                                                            field="copyright"
                                                                                                            relation="copyrightText"
                                                                                                            source-type="Copyright"
                                                                                                            :label="__('copyright')"
                                                                                                            :key="'text-copyright-'.$item->text->id" />
                                                                                                    </div>
                                                                                                    <div>
                                                                                                        <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                            {{ __('origin') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                        </label>
                                                                                                        <livewire:source-picker
                                                                                                            :model="$item->text"
                                                                                                            field="origin"
                                                                                                            relation="originText"
                                                                                                            source-type="Origin"
                                                                                                            :label="__('origin')"
                                                                                                            :key="'text-origin-'.$item->text->id" />
                                                                                                    </div>
                                                                                                </div>
                                                                                            @else
                                                                                                <div class="text-caption text-ink-500">
                                                                                                    Copyright: {!! $item->text->copyrightText?->name !!} · {{ __('origin') }}: {!! $item->text->originText?->name !!}
                                                                                                </div>
                                                                                            @endcan
                                                                                        </div>

                                                                                        {{-- 5z.10 § 8.3: Einheitliche Fußzeile — Vollständigkeit links,
                                                                                             Speicherstand mit Datum + Uhrzeit rechts. --}}
                                                                                        @can('update', $project)
                                                                                            @php
                                                                                                $textMissing = collect([
                                                                                                    $item->text->copyrightText ? null : __('copyright'),
                                                                                                    $item->text->originText ? null : __('origin'),
                                                                                                ])->filter()->values();
                                                                                            @endphp
                                                                                            <div class="mt-3 flex items-center justify-between gap-3">
                                                                                                @if ($textMissing->isEmpty())
                                                                                                    <p class="text-caption text-success">✓ {{ __('gallery_status_complete') }}</p>
                                                                                                @else
                                                                                                    <p class="text-caption text-warning">⚠ {{ __('gallery_status_missing', ['fields' => $textMissing->implode(', ')]) }}</p>
                                                                                                @endif
                                                                                                <p class="text-caption text-ink-500">
                                                                                                    {{ __('saved') }} · {{ optional($item->text->updated_at ?? $item->text->created_at)->format('d.m.Y, H:i') }}
                                                                                                </p>
                                                                                            </div>
                                                                                        @endcan
                                                                                </x-ui.block-card>
                                                                            </li>
                                                                        @endisset
                                                                    @endif
                                                                    @if($item->content_type == 'App\Models\Audiovisual')
                                                                        @isset($item->audiovisual->link)
                                                                            <li class="item audiovisual content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" data-history-subject="Audiovisual:{{$item->audiovisual->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <x-ui.block-card :type="$item->audiovisual->type === 'audio' ? 'audio' : 'video'" id="anchor_MediaContent_{{$item->id}}" class="mb-4" :save-slot="'Audiovisual-'.$item->audiovisual->id">
                                                                                    <x-slot:actions>
                                                                                        <x-ui.history-trigger
                                                                                            subjectType="Audiovisual"
                                                                                            :subjectId="$item->audiovisual->id"
                                                                                        />
                                                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                            <x-comment.trigger
                                                                                                commentableType="App\Models\Audiovisual"
                                                                                                :commentableId="$item->audiovisual->id"
                                                                                                :count="isset($item->audiovisual->comments) ? count($item->audiovisual->comments) : 0"
                                                                                            />
                                                                                        @endif
                                                                                        @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                            <form action="{{ route('audiovisual.delete',$item->audiovisual->id) }}"
                                                                                                  method="POST" class="inline-flex">
                                                                                                @csrf
                                                                                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                                @method('DELETE')
                                                                                                <button type="submit"
                                                                                                        onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                                                        title="{{__('delete_block')}}"
                                                                                                        class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                                                                                                    <x-icon name="trash-2" size="4"/>
                                                                                                </button>
                                                                                            </form>
                                                                                        @endif
                                                                                    </x-slot:actions>
                                                                                    <div>
                                                                                        {{-- Player als eigenständige Volt-Komponente
                                                                                             (Phase 5c.6.c.3). Rendert audio/iframe
                                                                                             und aktualisiert sich beim Speichern
                                                                                             eines Inline-Editor-Felds via `saved`
                                                                                             Event. --}}
                                                                                        <livewire:audiovisual-player
                                                                                            :audiovisual="$item->audiovisual"
                                                                                            :key="'av-player-'.$item->audiovisual->id" />

                                                                                        @can('update', $project)
                                                                                            <div class="mt-3 space-y-2">
                                                                                                {{-- Design v6 § 8.1: Blocktyp-Select aus dem Body raus —
                                                                                                     der Typ steht schon im Chip. Umwandeln kommt später
                                                                                                     als eigener Menü-Eintrag im ⋯-Menü zurück; bis dahin
                                                                                                     ist der Typ nach Anlage fest. --}}

                                                                                                {{-- 5z.9: Transkript-Feld für Audio + Video, weiche Pflicht
                                                                                                     analog zur Bildbeschreibung in der Galerie. --}}
                                                                                                <div class="mt-2" data-history-field="transcript">
                                                                                                    <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                        {{ __('transcript') }}
                                                                                                    </label>
                                                                                                    <livewire:inline-editor
                                                                                                        :model="$item->audiovisual"
                                                                                                        field="transcript"
                                                                                                        :multiline="true"
                                                                                                        rules="nullable|string|max:20000"
                                                                                                        :label="__('transcript')"
                                                                                                        :key="'av-transcript-'.$item->audiovisual->id" />
                                                                                                    <p class="mt-1 text-caption text-ink-500">{{ __('transcript_hint') }}</p>
                                                                                                </div>

                                                                                                {{-- 5z.9/§ 8: Copyright und Quelle stehen offen, kein <details>
                                                                                                     mehr — gleiche Stelle wie beim Text- und Galerie-Block. --}}
                                                                                                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                                                                                                    <div>
                                                                                                        <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                            {{ __('copyright') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                        </label>
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="copyright"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('copyright')"
                                                                                                            :key="'av-copyright-'.$item->audiovisual->id" />
                                                                                                    </div>
                                                                                                    <div>
                                                                                                        <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                            {{ __('origin') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                        </label>
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="source"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('origin')"
                                                                                                            :key="'av-source-'.$item->audiovisual->id" />
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @else
                                                                                            <p class="metadata mt-2">
                                                                                                Copyright {!! $item->audiovisual->copyright !!}<br>
                                                                                                Origin {!! $item->audiovisual->source !!}
                                                                                            </p>
                                                                                        @endcan
                                                                                    </div>
                                                                                    {{-- 5y.11: Angaben-Status analog Gallery — Copyright und Quelle als weiche Pflichtfelder. --}}
                                                                                    @can('update', $project)
                                                                                        @php
                                                                                            $avMissing = collect([
                                                                                                empty(trim(strip_tags((string) $item->audiovisual->copyright))) ? __('copyright') : null,
                                                                                                empty(trim(strip_tags((string) $item->audiovisual->source))) ? __('origin') : null,
                                                                                                empty(trim(strip_tags((string) $item->audiovisual->transcript))) ? __('transcript') : null,
                                                                                            ])->filter()->values();
                                                                                        @endphp
                                                                                        <div class="mt-3 flex items-center justify-between gap-3">
                                                                                            @if ($avMissing->isEmpty())
                                                                                                <p class="text-caption text-success">✓ {{ __('gallery_status_complete') }}</p>
                                                                                            @else
                                                                                                <p class="text-caption text-warning">⚠ {{ __('gallery_status_missing', ['fields' => $avMissing->implode(', ')]) }}</p>
                                                                                            @endif
                                                                                            <p class="text-caption text-ink-500">
                                                                                                {{-- 5z.10 § 8.3: Speicherstand mit Datum UND Uhrzeit. --}}
                                                                                                {{ __('saved') }} · {{ optional($item->audiovisual->updated_at ?? $item->audiovisual->created_at)->format('d.m.Y, H:i') }}
                                                                                            </p>
                                                                                        </div>
                                                                                    @endcan
                                                                                </x-ui.block-card>
                                                                            </li>
                                                                        @endisset
                                                                    @endif
                                                                    {{-- Phase 4 / E.7b 4a: alte Spalte hatte historisch
                                                                         'App\Models\Image' für Galleries; neue content_type
                                                                         hat 'App\Models\Gallery' (ADR-0022). --}}
                                                                    @if(isset($item) && $item->content_type == 'App\Models\Gallery')
                                                                        @if(isset($item->gallery))
                                                                            <li class="item gallery content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <x-ui.block-card type="gallery" class="mb-4" :save-slot="'Gallery-'.$item->gallery->id">
                                                                                    {{-- Phase 5y.1: Block-Aktionen wandern in den
                                                                                         `actions`-Slot der Block-Card. Vorher standen
                                                                                         Rueckgaengig/Kommentare/Bild-hinzufuegen/Loeschen
                                                                                         als eigene Row unter dem Beschreibungs-Editor —
                                                                                         daraus entstand die 250-px-Leerflaeche zwischen
                                                                                         Beschreibung und Bild-Raster (Briefing § 2). --}}
                                                                                    <x-slot:actions>
                                                                                        <x-ui.history-trigger
                                                                                            subjectType="Gallery"
                                                                                            :subjectId="$item->gallery->id"
                                                                                        />

                                                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                            <x-comment.trigger
                                                                                                commentableType="App\Models\Gallery"
                                                                                                :commentableId="$item->gallery->id"
                                                                                                :count="isset($item->gallery->comments) ? count($item->gallery->comments) : 0"
                                                                                            />
                                                                                        @endif

                                                                                        {{-- 5y.4: „+ Bilder"-Zugang lebt jetzt im Rasterkopf und als Drop-Zone im Raster. --}}

                                                                                        @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                            <form action="{{ route('gallery.delete',$item->gallery->id) }}"
                                                                                                  method="POST"
                                                                                                  class="inline-flex">
                                                                                                @csrf
                                                                                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                                @method('DELETE')
                                                                                                <button type="submit"
                                                                                                        onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                                                        title="{{__('delete_block')}}"
                                                                                                        class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                                                                                                    <x-icon name="trash-2" size="4"/>
                                                                                                </button>
                                                                                            </form>
                                                                                        @endif
                                                                                    </x-slot:actions>

                                                                                    <div>
                                                                                        @can('update', $project)
                                                                                            <livewire:inline-editor
                                                                                                :model="$item->gallery"
                                                                                                field="title"
                                                                                                rules="nullable|string|max:255"
                                                                                                :label="__('title')"
                                                                                                :variant="'heading'"
                                                                                                :key="'gallery-title-'.$item->gallery->id"
                                                                                            />
                                                                                            <livewire:inline-editor
                                                                                                :model="$item->gallery"
                                                                                                field="subtitle"
                                                                                                rules="nullable|string|max:255"
                                                                                                :variant="'subtitle'"
                                                                                                :key="'gallery-subtitle-'.$item->gallery->id"
                                                                                            />
                                                                                            {{-- Phase 5y.1: der Rich-Text-Editor bleibt
                                                                                                 fuer Editor:innen dauerhaft anzeigbar
                                                                                                 (er ist die Bearbeitungsflaeche); dort
                                                                                                 verursacht seine Mindesthoehe keine
                                                                                                 Leerflaeche mehr, weil die Aktionen jetzt
                                                                                                 im Kopf liegen und nicht mehr darunter. --}}
                                                                                            <livewire:rich-text-editor
                                                                                                :model="$item->gallery"
                                                                                                field="description"
                                                                                                rules="nullable|string"
                                                                                                :label="__('gallery_description')"
                                                                                                :key="'gallery-description-'.$item->gallery->id"
                                                                                            />
                                                                                        @else
                                                                                            <h4 class="text-heading font-semibold text-ink-900">{{$item->gallery->title}}</h4>
                                                                                            @if (! empty(trim($item->gallery->subtitle ?? '')))
                                                                                                <p class="text-body text-ink-600">{{$item->gallery->subtitle}}</p>
                                                                                            @endif
                                                                                            {{-- Phase 5y.1: leere Beschreibung darf keine
                                                                                                 Zeile reservieren (Briefing § 2 Punkt 4). --}}
                                                                                            @if (! empty(trim(strip_tags((string) $item->gallery->description))))
                                                                                                <div class="text-body text-ink-800">
                                                                                                    {!! $item->gallery->description !!}
                                                                                                </div>
                                                                                            @endif
                                                                                        @endcan
                                                                                    </div>
                                                                                    {{-- public/css/crowdcuratio.css wird seit dem
                                                                                         Vite-Umbau nicht mehr geladen — die alten
                                                                                         .gallery_container-Grid-Regeln greifen nicht.
                                                                                         Wir setzen das Grid komplett per Tailwind-
                                                                                         Utilities direkt am Element. --}}
                                                                                    @if ($item->gallery->images->isEmpty())
                                                                                        @can('update', $project)
                                                                                            {{-- 5y.4 § 7 leer: Drop-Zone ueber volle Blockbreite, kein leeres Raster. --}}
                                                                                            <button type="button"
                                                                                                    class="addImage mt-4 flex w-full flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-line-200 bg-transparent px-4 py-8 text-body text-ink-500 hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                                                                                    data-chapter="{{ $chapter->name }}"
                                                                                                    data-entry="{{ $entry->name }}"
                                                                                                    data-id="{{ $item->gallery->id }}"
                                                                                                    data-entryId="{{ $entry->id }}"
                                                                                                    data-toggle="modal"
                                                                                                    data-target="#imageModal">
                                                                                                <x-icon name="image-plus" size="5"/>
                                                                                                <span class="text-body font-medium text-ink-700">{{ __('gallery_dropzone_title') }}</span>
                                                                                                <span class="text-caption text-ink-500">{{ __('gallery_dropzone_hint') }}</span>
                                                                                                <span class="mt-1 inline-flex items-center gap-1 rounded-md border border-line-200 bg-paper-0 px-3 py-1 text-caption font-medium text-ink-900">
                                                                                                    {{ __('gallery_dropzone_button') }}
                                                                                                </span>
                                                                                            </button>
                                                                                        @else
                                                                                            <p class="mt-4 rounded-md border border-line-200 bg-paper-50 px-4 py-3 text-body text-ink-500">
                                                                                                {{ __('placeholder_gallery_hint') }}
                                                                                            </p>
                                                                                        @endcan
                                                                                    @else
                                                                                    {{-- Phase 5y.2 + 5y.3 + 5y.5 + 5y.7: Kachel-Raster ODER Detailzeile.
                                                                                         Alpine-State `editingImageId` schaltet die beiden Ansichten um; die
                                                                                         Detailzeile ersetzt das Raster im selben Block, ohne Modal (Briefing
                                                                                         § 5). Papierkorb-Kaskade aus der alten Meta-Reihe unter der Kachel
                                                                                         entfaellt komplett — Bild entfernen sitzt jetzt als Overlay unten
                                                                                         auf der Kachel (permanent Touch, hover/focus Desktop). --}}
                                                                                    <div x-ref="body" x-data="{
                                                                                        editingImageId: null,
                                                                                        pickedId: null,
                                                                                        originalOrder: [],
                                                                                        announcement: '',
                                                                                        hadEdits: false,
                                                                                        uploads: [],
                                                                                        rejected: [],
                                                                                        dropUrl: '{{ route('gallery.images.drop', $item->gallery->id) }}',
                                                                                        handleFiles(fileList) {
                                                                                            if (!fileList || !fileList.length) return;
                                                                                            const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                                                                                            const maxBytes = 4 * 1024 * 1024;
                                                                                            const queue = [];
                                                                                            for (const file of fileList) {
                                                                                                if (!allowed.includes(file.type)) {
                                                                                                    this.rejected.push({ name: file.name, reason: '{{ __('gallery_rejected_reason_type') }}' });
                                                                                                    continue;
                                                                                                }
                                                                                                if (file.size > maxBytes) {
                                                                                                    this.rejected.push({ name: file.name, reason: '{{ __('gallery_rejected_reason_size') }}' });
                                                                                                    continue;
                                                                                                }
                                                                                                queue.push(file);
                                                                                            }
                                                                                            if (!queue.length) return;
                                                                                            let pending = queue.length;
                                                                                            const totalAtStart = queue.length;
                                                                                            const uploadedIds = [];
                                                                                            for (const file of queue) {
                                                                                                const ghostId = 'ghost-' + Math.random().toString(36).slice(2, 9);
                                                                                                const previewUrl = URL.createObjectURL(file);
                                                                                                const entry = { id: ghostId, name: file.name, previewUrl, progress: 0, status: 'uploading', xhr: null };
                                                                                                this.uploads.push(entry);
                                                                                                this.uploadOne(entry, file, (newId) => {
                                                                                                    if (newId) uploadedIds.push(newId);
                                                                                                    pending -= 1;
                                                                                                    if (pending <= 0) {
                                                                                                        const url = new URL(window.location.href);
                                                                                                        // 5y.9: bei genau einer erfolgreich hochgeladenen Datei
                                                                                                        // die Detailzeile direkt oeffnen, damit der Nutzer
                                                                                                        // Copyright/Quelle in einem Rutsch nachpflegt.
                                                                                                        if (totalAtStart === 1 && uploadedIds.length === 1) {
                                                                                                            url.searchParams.set('editImage', uploadedIds[0]);
                                                                                                        }
                                                                                                        setTimeout(() => { window.location.href = url.toString(); }, 600);
                                                                                                    }
                                                                                                });
                                                                                            }
                                                                                        },
                                                                                        uploadOne(entry, file, done) {
                                                                                            const token = document.querySelector('meta[name=csrf-token]')?.content;
                                                                                            const xhr = new XMLHttpRequest();
                                                                                            entry.xhr = xhr;
                                                                                            xhr.open('POST', this.dropUrl);
                                                                                            xhr.setRequestHeader('X-CSRF-TOKEN', token);
                                                                                            xhr.setRequestHeader('Accept', 'application/json');
                                                                                            xhr.upload.onprogress = (e) => {
                                                                                                if (e.lengthComputable) {
                                                                                                    entry.progress = Math.round((e.loaded / e.total) * 100);
                                                                                                }
                                                                                            };
                                                                                            xhr.onload = () => {
                                                                                                if (xhr.status >= 200 && xhr.status < 300) {
                                                                                                    entry.status = 'done';
                                                                                                    entry.progress = 100;
                                                                                                    let newId = null;
                                                                                                    try {
                                                                                                        const payload = JSON.parse(xhr.responseText);
                                                                                                        newId = payload && payload.image && payload.image.id ? payload.image.id : null;
                                                                                                    } catch (e) { /* ignore */ }
                                                                                                    done(newId);
                                                                                                } else {
                                                                                                    entry.status = 'error';
                                                                                                    this.rejected.push({ name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}' });
                                                                                                    this.uploads = this.uploads.filter(u => u.id !== entry.id);
                                                                                                    done(null);
                                                                                                }
                                                                                            };
                                                                                            xhr.onerror = () => {
                                                                                                entry.status = 'error';
                                                                                                this.rejected.push({ name: entry.name, reason: '{{ __('gallery_rejected_reason_server') }}' });
                                                                                                this.uploads = this.uploads.filter(u => u.id !== entry.id);
                                                                                                done(null);
                                                                                            };
                                                                                            const fd = new FormData();
                                                                                            fd.append('file', file);
                                                                                            xhr.send(fd);
                                                                                        },
                                                                                        cancelUpload(id) {
                                                                                            const entry = this.uploads.find(u => u.id === id);
                                                                                            if (entry && entry.xhr) entry.xhr.abort();
                                                                                            this.uploads = this.uploads.filter(u => u.id !== id);
                                                                                        },
                                                                                        prefersReducedMotion() {
                                                                                            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                                                                                        },
                                                                                        interpolateHeight(fromH, toH, duration) {
                                                                                            const body = this.$refs.body;
                                                                                            if (!body) return;
                                                                                            body.style.overflow = 'hidden';
                                                                                            body.style.height = fromH + 'px';
                                                                                            body.style.transition = 'height ' + duration + 'ms ease-out';
                                                                                            requestAnimationFrame(() => {
                                                                                                body.style.height = toH + 'px';
                                                                                            });
                                                                                            setTimeout(() => {
                                                                                                body.style.height = '';
                                                                                                body.style.overflow = '';
                                                                                                body.style.transition = '';
                                                                                            }, duration + 30);
                                                                                        },
                                                                                        flipElement(el, from, to, duration, delay) {
                                                                                            if (!el) return;
                                                                                            const dx = from.left - to.left;
                                                                                            const dy = from.top - to.top;
                                                                                            const sx = to.width === 0 ? 1 : from.width / to.width;
                                                                                            const sy = to.height === 0 ? 1 : from.height / to.height;
                                                                                            el.style.transformOrigin = 'top left';
                                                                                            el.style.transition = 'none';
                                                                                            el.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(' + sx + ', ' + sy + ')';
                                                                                            requestAnimationFrame(() => {
                                                                                                requestAnimationFrame(() => {
                                                                                                    el.style.transition = 'transform ' + duration + 'ms cubic-bezier(0.2, 0.7, 0.3, 1) ' + delay + 'ms';
                                                                                                    el.style.transform = '';
                                                                                                });
                                                                                            });
                                                                                            setTimeout(() => {
                                                                                                el.style.transition = '';
                                                                                                el.style.transform = '';
                                                                                                el.style.transformOrigin = '';
                                                                                            }, duration + delay + 50);
                                                                                        },
                                                                                        focusFirstField(imageId) {
                                                                                            const row = Array.from(this.$refs.body.querySelectorAll('.gallery-detail-row')).find(el => String(el.dataset.imageId) === String(imageId));
                                                                                            if (!row) return;
                                                                                            const field = row.querySelector('input:not([type=hidden]), textarea, [contenteditable], button:not([data-flip-skip])');
                                                                                            if (field) field.focus({ preventScroll: true });
                                                                                        },
                                                                                        enterDetail(imageId) {
                                                                                            if (this.prefersReducedMotion()) {
                                                                                                this.editingImageId = imageId;
                                                                                                this.$nextTick(() => this.focusFirstField(imageId));
                                                                                                return;
                                                                                            }
                                                                                            const body = this.$refs.body;
                                                                                            const tileFrame = Array.from(body.querySelectorAll('.gallery-tile-frame')).find(el => String(el.dataset.imageId) === String(imageId));
                                                                                            const fromRect = tileFrame ? tileFrame.getBoundingClientRect() : null;
                                                                                            const fromHeight = body.getBoundingClientRect().height;
                                                                                            this.editingImageId = imageId;
                                                                                            this.$nextTick(() => {
                                                                                                const toHeight = body.getBoundingClientRect().height;
                                                                                                this.interpolateHeight(fromHeight, toHeight, 200);
                                                                                                const detailPreview = Array.from(body.querySelectorAll('.gallery-detail-preview')).find(el => String(el.dataset.imageId) === String(imageId));
                                                                                                if (detailPreview && fromRect) {
                                                                                                    const toRect = detailPreview.getBoundingClientRect();
                                                                                                    this.flipElement(detailPreview, fromRect, toRect, 180, 0);
                                                                                                }
                                                                                                setTimeout(() => this.focusFirstField(imageId), 240);
                                                                                            });
                                                                                        },
                                                                                        exitDetail() {
                                                                                            if (this.hadEdits) { window.location.reload(); return; }
                                                                                            if (this.prefersReducedMotion()) {
                                                                                                this.editingImageId = null;
                                                                                                return;
                                                                                            }
                                                                                            const body = this.$refs.body;
                                                                                            const imageId = this.editingImageId;
                                                                                            const detailPreview = Array.from(body.querySelectorAll('.gallery-detail-preview')).find(el => String(el.dataset.imageId) === String(imageId));
                                                                                            const fromRect = detailPreview ? detailPreview.getBoundingClientRect() : null;
                                                                                            const fromHeight = body.getBoundingClientRect().height;
                                                                                            this.editingImageId = null;
                                                                                            this.$nextTick(() => {
                                                                                                const toHeight = body.getBoundingClientRect().height;
                                                                                                this.interpolateHeight(fromHeight, toHeight, 200);
                                                                                                const tileFrame = Array.from(body.querySelectorAll('.gallery-tile-frame')).find(el => String(el.dataset.imageId) === String(imageId));
                                                                                                if (tileFrame && fromRect) {
                                                                                                    const toRect = tileFrame.getBoundingClientRect();
                                                                                                    this.flipElement(tileFrame, fromRect, toRect, 180, 0);
                                                                                                }
                                                                                            });
                                                                                        },
                                                                                        reorderUrl: '{{ route('gallery.images.reorder', $item->gallery->id) }}',
                                                                                        announce(text) {
                                                                                            this.announcement = '';
                                                                                            this.$nextTick(() => { this.announcement = text; });
                                                                                        },
                                                                                        snapshotOrder() {
                                                                                            this.originalOrder = Array.from(this.$refs.grid.children)
                                                                                                .map(el => el.dataset.imageId)
                                                                                                .filter(Boolean);
                                                                                        },
                                                                                        currentIndex(id) {
                                                                                            const items = Array.from(this.$refs.grid.children);
                                                                                            return items.findIndex(el => el.dataset.imageId === String(id));
                                                                                        },
                                                                                        pick(id) {
                                                                                            this.pickedId = id;
                                                                                            this.snapshotOrder();
                                                                                            const pos = this.currentIndex(id) + 1;
                                                                                            const total = this.$refs.grid.children.length;
                                                                                            this.announce(`Bild ${pos} von ${total} aufgenommen. Pfeiltasten verschieben, Leertaste ablegen, Esc abbrechen.`);
                                                                                        },
                                                                                        drop() {
                                                                                            const pos = this.currentIndex(this.pickedId) + 1;
                                                                                            const total = this.$refs.grid.children.length;
                                                                                            this.announce(`Bild an Position ${pos} von ${total} abgelegt.`);
                                                                                            this.pickedId = null;
                                                                                            this.persistOrder();
                                                                                        },
                                                                                        cancel() {
                                                                                            const grid = this.$refs.grid;
                                                                                            const byId = {};
                                                                                            Array.from(grid.children).forEach(el => { if (el.dataset.imageId) byId[el.dataset.imageId] = el; });
                                                                                            this.originalOrder.forEach(id => { if (byId[id]) grid.appendChild(byId[id]); });
                                                                                            this.pickedId = null;
                                                                                            this.announce('Sortierung abgebrochen.');
                                                                                        },
                                                                                        moveBy(id, delta) {
                                                                                            const grid = this.$refs.grid;
                                                                                            const items = Array.from(grid.children);
                                                                                            const from = this.currentIndex(id);
                                                                                            const to = from + delta;
                                                                                            if (from < 0 || to < 0 || to >= items.length) return;
                                                                                            const target = items[to];
                                                                                            if (delta > 0) {
                                                                                                grid.insertBefore(items[from], target.nextSibling);
                                                                                            } else {
                                                                                                grid.insertBefore(items[from], target);
                                                                                            }
                                                                                            const pos = this.currentIndex(id) + 1;
                                                                                            this.announce(`Bild an Position ${pos} von ${items.length} verschoben.`);
                                                                                        },
                                                                                        initSortable() {
                                                                                            const grid = this.$refs.grid;
                                                                                            if (!grid || typeof Sortable === 'undefined') return;
                                                                                            Sortable.create(grid, {
                                                                                                handle: '.gallery-drag-handle',
                                                                                                animation: 150,
                                                                                                ghostClass: 'opacity-40',
                                                                                                group: { name: 'gallery-images-' + this.reorderUrl, pull: false, put: false },
                                                                                                onEnd: () => this.persistOrder(),
                                                                                            });
                                                                                        },
                                                                                        renumberPositions() {
                                                                                            const grid = this.$refs.grid;
                                                                                            if (!grid) return;
                                                                                            Array.from(grid.children).forEach((el, index) => {
                                                                                                if (!el.dataset || !el.dataset.imageId) return;
                                                                                                const num = el.querySelector('[data-image-position]');
                                                                                                if (num) num.textContent = index + 1;
                                                                                            });
                                                                                        },
                                                                                        persistOrder() {
                                                                                            this.renumberPositions();
                                                                                            const ids = Array.from(this.$refs.grid.children)
                                                                                                .map(el => el.dataset.imageId)
                                                                                                .filter(Boolean);
                                                                                            const token = document.querySelector('meta[name=csrf-token]')?.content;
                                                                                            Alpine.store('saveStatus')?.set?.('saving');
                                                                                            fetch(this.reorderUrl, {
                                                                                                method: 'POST',
                                                                                                headers: {
                                                                                                    'X-CSRF-TOKEN': token,
                                                                                                    'Content-Type': 'application/json',
                                                                                                    'Accept': 'application/json',
                                                                                                },
                                                                                                body: JSON.stringify({ ids }),
                                                                                            })
                                                                                                .then(r => r.ok
                                                                                                    ? Alpine.store('saveStatus')?.set?.('saved')
                                                                                                    : Alpine.store('saveStatus')?.set?.('error'))
                                                                                                .catch(() => Alpine.store('saveStatus')?.set?.('error'));
                                                                                        },
                                                                                    }" x-init="initSortable(); window.addEventListener('saved', (e) => { if (e && e.detail && e.detail.model === 'Image') { hadEdits = true; } }); { const params = new URLSearchParams(window.location.search); const editParam = params.get('editImage'); if (editParam) { const parsed = parseInt(editParam, 10); if (!isNaN(parsed)) { $nextTick(() => { editingImageId = parsed; setTimeout(() => focusFirstField(parsed), 100); }); } params.delete('editImage'); const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : ''); window.history.replaceState({}, '', clean); } }">
                                                                                    <div
                                                                                        role="status"
                                                                                        aria-live="polite"
                                                                                        class="sr-only"
                                                                                        x-text="announcement"
                                                                                    ></div>

                                                                                        {{-- Raster --}}
                                                                                        {{-- 5y.4: Rasterkopf mit Anzahl, Sammelwarnung, Reihenfolge-Hinweis und Add-Button. --}}
                                                                                        <div x-show="editingImageId === null"
                                                                                             class="mt-5 flex flex-wrap items-center justify-between gap-3 border-b border-line-200 pb-2">
                                                                                            <div class="flex flex-wrap items-center gap-3">
                                                                                                <span class="text-caption font-semibold uppercase tracking-wider text-ink-500">{{ __('gallery_header_label') }}</span>
                                                                                                <span class="text-body text-ink-700">{{ trans_choice('gallery_header_count', $item->gallery->images->count(), ['count' => $item->gallery->images->count()]) }}</span>
                                                                                                @php
                                                                                                    $missingCount = $item->gallery->images->filter(fn($img) =>
                                                                                                        empty(trim(strip_tags((string) $img->description)))
                                                                                                        || ! $img->copyrightImage
                                                                                                        || ! $img->originImage
                                                                                                    )->count();
                                                                                                @endphp
                                                                                                @if ($missingCount > 0)
                                                                                                    <span class="inline-flex items-center gap-1 rounded-md bg-warning-bg px-2 py-0.5 text-caption text-warning">
                                                                                                        {{ trans_choice('gallery_header_missing', $missingCount, ['count' => $missingCount]) }}
                                                                                                    </span>
                                                                                                @endif
                                                                                            </div>
                                                                                            <div class="flex flex-wrap items-center gap-3">
                                                                                                @can('update', $project)
                                                                                                    <span class="text-caption text-ink-500">{{ __('gallery_header_order_hint') }}</span>
                                                                                                    <button type="button"
                                                                                                            class="addImage inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-caption font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                                                                                            data-chapter="{{ $chapter->name }}"
                                                                                                            data-entry="{{ $entry->name }}"
                                                                                                            data-id="{{ $item->gallery->id }}"
                                                                                                            data-entryId="{{ $entry->id }}"
                                                                                                            data-toggle="modal"
                                                                                                            data-target="#imageModal">
                                                                                                        <x-icon name="plus" size="3"/>
                                                                                                        <span>{{ __('gallery_header_add') }}</span>
                                                                                                    </button>
                                                                                                @endcan
                                                                                            </div>
                                                                                        </div>

                                                                                        @cannot('update', $project)
                                                                                            <p class="mt-2 rounded-md bg-info-bg px-3 py-2 text-caption text-info">
                                                                                                {{ __('gallery_reader_hint') }}
                                                                                            </p>
                                                                                        @endcannot

                                                                                        {{-- 5y.9: Banner mit abgewiesenen Dateien. --}}
                                                                                        <div x-show="rejected.length > 0" x-cloak
                                                                                             class="mt-3 rounded-md border border-warning-bg bg-warning-bg/40 px-3 py-2 text-caption text-ink-700">
                                                                                            <div class="flex items-center justify-between">
                                                                                                <span class="font-semibold text-warning">⚠ {{ __('gallery_rejected_title') }}</span>
                                                                                                <button type="button" @click="rejected = []" class="text-caption underline decoration-dotted underline-offset-2">
                                                                                                    {{ __('gallery_rejected_dismiss') }}
                                                                                                </button>
                                                                                            </div>
                                                                                            <ul class="mt-1 space-y-0.5">
                                                                                                <template x-for="(r, i) in rejected" :key="i">
                                                                                                    <li><span class="font-medium text-ink-900" x-text="r.name"></span> — <span x-text="r.reason"></span></li>
                                                                                                </template>
                                                                                            </ul>
                                                                                        </div>

                                                                                        <div x-show="editingImageId === null"
                                                                                             x-ref="grid"
                                                                                             class="mt-4 grid gap-[14px]"
                                                                                             style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
                                                                                            @foreach($item->gallery->images as $image)
                                                                                                <div class="gallery_item group relative" id="gallery_items_{{$item->gallery->id}}" data-image-id="{{ $image->id }}">
                                                                                                    <div id="anchor_MediaContent_{{$item->id}}"
                                                                                                         data-image-id="{{ $image->id }}"
                                                                                                         class="gallery-tile-frame relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
                                                                                                        <img
                                                                                                            src="{{ route('image', $image->image) }}"
                                                                                                            alt="{{ $image->alt }}"
                                                                                                            class="max-h-full max-w-full object-contain"
                                                                                                            loading="lazy"
                                                                                                        />
                                                                                                        <span
                                                                                                            @can('update', $project)
                                                                                                                role="button"
                                                                                                                tabindex="0"
                                                                                                                :aria-pressed="pickedId === {{ $image->id }} ? 'true' : 'false'"
                                                                                                                aria-keyshortcuts="Space ArrowUp ArrowDown ArrowLeft ArrowRight Escape"
                                                                                                                @keydown.space.prevent="pickedId === {{ $image->id }} ? drop() : (pickedId === null && pick({{ $image->id }}))"
                                                                                                                @keydown.enter.prevent="pickedId === {{ $image->id }} ? drop() : (pickedId === null && pick({{ $image->id }}))"
                                                                                                                @keydown.escape.prevent="pickedId === {{ $image->id }} && cancel()"
                                                                                                                @keydown.arrow-right.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, 1)"
                                                                                                                @keydown.arrow-down.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, 1)"
                                                                                                                @keydown.arrow-left.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, -1)"
                                                                                                                @keydown.arrow-up.prevent="pickedId === {{ $image->id }} && moveBy({{ $image->id }}, -1)"
                                                                                                                :class="pickedId === {{ $image->id }} ? 'ring-2 ring-brand-bar ring-offset-1' : ''"
                                                                                                            @endcan
                                                                                                            class="gallery-drag-handle absolute left-1.5 top-1.5 inline-flex cursor-grab items-center gap-1 rounded px-1.5 py-0.5 text-caption font-semibold text-white active:cursor-grabbing focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                                                                                                            style="background-color: rgba(27,35,48,.78);"
                                                                                                            aria-label="{{ __('gallery_position') }} {{ $loop->iteration }}"
                                                                                                        >
                                                                                                            @can('update', $project)
                                                                                                                <x-icon name="grip-vertical" size="3"/>
                                                                                                            @endcan
                                                                                                            <span data-image-position>{{ $loop->iteration }}</span>
                                                                                                        </span>

                                                                                                        @can('update', $project)
                                                                                                            {{-- Overlay-Aktionen unten: Angaben bearbeiten + Entfernen.
                                                                                                                 Erscheint bei hover ODER focus-within — auf Touch-Geraeten
                                                                                                                 per :focus-within immer, sobald man die Kachel antippt. --}}
                                                                                                            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-ink-900/80 to-transparent px-2 py-1.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100">
                                                                                                                <button
                                                                                                                    type="button"
                                                                                                                    @click.stop="enterDetail({{ $image->id }})"
                                                                                                                    class="pointer-events-auto inline-flex items-center gap-1 rounded bg-white/90 px-2 py-1 text-caption font-medium text-ink-900 hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                                                                                                    title="{{ __('gallery_actions_edit') }}"
                                                                                                                >
                                                                                                                    <x-icon name="pencil" size="3"/>
                                                                                                                    <span>{{ __('gallery_actions_edit') }}</span>
                                                                                                                </button>
                                                                                                                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                                    <form action="{{ route('image.delete', $image->id) }}" method="POST" class="pointer-events-auto">
                                                                                                                        @csrf
                                                                                                                        @method('DELETE')
                                                                                                                        <button type="submit"
                                                                                                                                onclick="return confirm('{{ __('message_delete_confirm') }}')"
                                                                                                                                title="{{ __('delete_image') }}"
                                                                                                                                class="inline-flex size-8 items-center justify-center rounded bg-white/90 text-danger hover:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                                            <x-icon name="trash-2" size="3"/>
                                                                                                                        </button>
                                                                                                                    </form>
                                                                                                                @endif
                                                                                                            </div>
                                                                                                        @endcan
                                                                                                    </div>

                                                                                                    <div class="mt-1.5 truncate text-body">
                                                                                                        @if (! empty(trim($image->alt ?? '')))
                                                                                                            <span class="text-ink-900">{{ $image->alt }}</span>
                                                                                                        @else
                                                                                                            <span class="italic text-ink-500">{{ __('gallery_image_untitled') }}</span>
                                                                                                        @endif
                                                                                                    </div>

                                                                                                    @can('update', $project)
                                                                                                        {{-- Angaben-Status: weiches Pflichtfeld pro Bild (Briefing § 5).
                                                                                                             Zeigt „Angaben vollstaendig" oder eine namentliche Warnung. --}}
                                                                                                        @php
                                                                                                            $missing = collect([
                                                                                                                ! empty(trim(strip_tags((string) $image->description))) ? null : __('gallery_image_description'),
                                                                                                                $image->copyrightImage ? null : __('copyright'),
                                                                                                                $image->originImage ? null : __('origin'),
                                                                                                            ])->filter()->values();
                                                                                                        @endphp
                                                                                                        @if ($missing->isEmpty())
                                                                                                            <p class="mt-1 text-caption text-success">✓ {{ __('gallery_status_complete') }}</p>
                                                                                                        @else
                                                                                                            <p class="mt-1 text-caption text-warning">⚠ {{ __('gallery_status_missing', ['fields' => $missing->implode(', ')]) }}</p>
                                                                                                        @endif
                                                                                                    @endcan
                                                                                                </div>
                                                                                            @endforeach

                                                                                            @can('update', $project)
                                                                                                {{-- 5y.9: Optimistische Ghost-Kacheln waehrend Upload. --}}
                                                                                                <template x-for="entry in uploads" :key="entry.id">
                                                                                                    <div class="relative">
                                                                                                        <div class="relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
                                                                                                            <img :src="entry.previewUrl" alt="" class="max-h-full max-w-full object-contain opacity-70"/>
                                                                                                            <div class="absolute inset-x-0 bottom-0 flex flex-col gap-1 bg-ink-900/70 px-2 py-1.5 text-caption text-white">
                                                                                                                <div class="flex items-center justify-between">
                                                                                                                    <span x-text="entry.status === 'done' ? '{{ __('gallery_upload_done') }}' : '{{ __('gallery_upload_progress') }}'"></span>
                                                                                                                    <button type="button"
                                                                                                                            x-show="entry.status !== 'done'"
                                                                                                                            @click="cancelUpload(entry.id)"
                                                                                                                            class="text-caption underline decoration-dotted underline-offset-2">
                                                                                                                        {{ __('gallery_upload_cancel') }}
                                                                                                                    </button>
                                                                                                                </div>
                                                                                                                <div class="h-1 w-full overflow-hidden rounded bg-white/20">
                                                                                                                    <div class="h-full bg-info" :style="'width: ' + entry.progress + '%'"></div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        <div class="mt-1.5 truncate text-body text-ink-500" x-text="entry.name"></div>
                                                                                                    </div>
                                                                                                </template>
                                                                                                {{-- 5y.4 + 5y.9: Drop-Zone als letzte Kachel — Klick UND echtes File-Drop.
                                                                                                     Beim Drop landen die Dateien im imageModal-Input, der Klick oeffnet
                                                                                                     das Modal ueber den bestehenden .addImage-Handler. --}}
                                                                                                {{-- 5y.9: Drop-Zone mit optimistischem Upload.
                                                                                                     Klick oeffnet den nativen File-Picker,
                                                                                                     Drop laesst die Dateien direkt hochladen. --}}
                                                                                                <div x-data="{ dragging: false }"
                                                                                                     @dragover.prevent="dragging = true"
                                                                                                     @dragenter.prevent="dragging = true"
                                                                                                     @dragleave.prevent="dragging = false"
                                                                                                     @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
                                                                                                     :class="dragging ? 'border-primary bg-primary/10 text-primary' : 'border-line-200'"
                                                                                                     class="group relative flex aspect-video w-full flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed bg-transparent text-caption text-ink-500 hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                                                                                                    <label class="flex h-full w-full cursor-pointer flex-col items-center justify-center gap-1" aria-label="{{ __('gallery_header_add') }}">
                                                                                                        <x-icon name="image-plus" size="5"/>
                                                                                                        <span class="text-caption font-medium">{{ __('gallery_dropzone_title') }}</span>
                                                                                                        <span class="text-caption text-ink-500">{{ __('gallery_dropzone_hint') }}</span>
                                                                                                        <input type="file" class="sr-only" multiple accept="image/jpeg,image/png,image/gif,image/webp"
                                                                                                               @change="handleFiles($event.target.files); $event.target.value = ''"/>
                                                                                                    </label>
                                                                                                </div>
                                                                                            @endcan
                                                                                        </div>

                                                                                        {{-- Detailzeile: ersetzt das Raster fuer eine einzelne Kachel.
                                                                                             Jedes Bild rendert seine eigene, per x-show gefiltert. --}}
                                                                                        @can('update', $project)
                                                                                            @foreach($item->gallery->images as $image)
                                                                                                <div
                                                                                                    x-show="editingImageId === {{ $image->id }}"
                                                                                                    x-cloak
                                                                                                    data-image-id="{{ $image->id }}"
                                                                                                    @keydown.escape.window="exitDetail()"
                                                                                                    class="gallery-detail-row mt-4 rounded-md border border-line-200 bg-paper-50 p-4"
                                                                                                >
                                                                                                    <header class="mb-4 flex items-center justify-between gap-3">
                                                                                                        <button type="button"
                                                                                                                @click="exitDetail()"
                                                                                                                class="inline-flex items-center gap-1 text-body text-primary hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                            <x-icon name="chevron-left" size="4"/>
                                                                                                            <span>{{ __('gallery_back_to_grid') }}</span>
                                                                                                        </button>
                                                                                                        <span class="text-caption text-ink-500">
                                                                                                            {{ __('gallery_image_n_of_m', ['n' => $loop->iteration, 'm' => $item->gallery->images->count()]) }}
                                                                                                        </span>
                                                                                                        <button type="button"
                                                                                                                @click="exitDetail()"
                                                                                                                title="{{ __('close') }}"
                                                                                                                class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                                                                                                            <x-icon name="x" size="4"/>
                                                                                                        </button>
                                                                                                    </header>

                                                                                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                                                                                        {{-- Vorschau --}}
                                                                                                        <div>
                                                                                                            <div class="gallery-detail-preview relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100" data-image-id="{{ $image->id }}">
                                                                                                                <img src="{{ route('image', $image->image) }}"
                                                                                                                     alt="{{ $image->alt }}"
                                                                                                                     class="max-h-full max-w-full object-contain"/>
                                                                                                            </div>
                                                                                                        </div>

                                                                                                        {{-- Vier Felder: Titel · Bildbeschreibung · Urheberrecht · Quelle. --}}
                                                                                                        <div class="grid grid-cols-1 gap-4">
                                                                                                            <div>
                                                                                                                <label class="mb-1 block text-caption font-medium text-ink-700">{{ __('title') }}</label>
                                                                                                                <livewire:inline-editor
                                                                                                                    :model="$image"
                                                                                                                    field="alt"
                                                                                                                    rules="nullable|string|max:255"
                                                                                                                    :key="'image-detail-alt-'.$image->id"
                                                                                                                />
                                                                                                                <p class="mt-1 text-caption text-ink-500">{{ __('gallery_field_title_hint') }}</p>
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                                    {{ __('gallery_image_description') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                                </label>
                                                                                                                <livewire:inline-editor
                                                                                                                    :model="$image"
                                                                                                                    field="description"
                                                                                                                    rules="nullable|string|max:2000"
                                                                                                                    :key="'image-detail-description-'.$image->id"
                                                                                                                />
                                                                                                                <p class="mt-1 text-caption text-ink-500">{{ __('gallery_field_description_hint') }}</p>
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                                    {{ __('copyright') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                                </label>
                                                                                                                <livewire:source-picker
                                                                                                                    :model="$image"
                                                                                                                    field="copyright"
                                                                                                                    relation="copyrightImage"
                                                                                                                    source-type="Copyright"
                                                                                                                    :label="__('copyright')"
                                                                                                                    :key="'image-detail-copyright-'.$image->id" />
                                                                                                            </div>
                                                                                                            <div>
                                                                                                                <label class="mb-1 block text-caption font-medium text-ink-700">
                                                                                                                    {{ __('origin') }} <span class="text-danger" aria-hidden="true">*</span>
                                                                                                                </label>
                                                                                                                <livewire:source-picker
                                                                                                                    :model="$image"
                                                                                                                    field="origin"
                                                                                                                    relation="originImage"
                                                                                                                    source-type="Origin"
                                                                                                                    :label="__('origin')"
                                                                                                                    :key="'image-detail-origin-'.$image->id" />
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        @endcan
                                                                                    </div>
                                                                                    @endif
                                                                                </x-ui.block-card>
                                                                            </li>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                            @else
                                                <ul class="list-group  ui-sortable-content sortable_list_content connectedSortableContent" data-entry="{{$entry->id}}" id="{{$entry->id}}" data-reorder-element="content" data-reorder-url="{{ route('chapter.drag') }}">
                                                   {{-- <li class="" data-content="" data-entry="{{$entry->id}}">
                                                    </li> --}}
                                                </ul>
                                            @endif
                                            <div class="mb-4">
                                                @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                    <span data-toggle="tooltip"
                                                          data-placement="top"
                                                          title="{{__('add_content')}}"> <button
                                                                type="button"
                                                                class="addContent btn btn-secondary add_item"
                                                                data-chapter="{{$chapter->name}}"
                                                                data-entry="{{$entry->name}}"
                                                                data-id="{{$entry->id}}"
                                                                data-toggle="modal"
                                                                data-target="#contentModal"
                                                                class="addContent inline-flex w-full items-center justify-center gap-2 rounded-md
                                                                       border-2 border-dashed border-line-200 bg-transparent
                                                                       px-4 py-3 text-body text-ink-500
                                                                       hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700"
                                                                ><x-icon name="plus" size="4"/> <span>{{__('new_element')}}</span></button></span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                @can('update', $project)
                                    {{-- 5z.2 § 2 (in 5e-Vokabular): Leeres Kapitel als Zustand,
                                         nicht als 500-px-Weiß. Info-Banner + primäre Aktion. --}}
                                    <div class="mb-4 rounded-md border border-info-bg bg-info-bg/40 px-4 py-3 text-body text-ink-700">
                                        {{ __('chapter_empty_banner') }}
                                    </div>
                                    <div class="mb-4">
                                        <button type="button"
                                                title="{{__('add_entry')}}"
                                                data-chapter="{{$chapter->name}}"
                                                data-id="{{$chapter->id}}"
                                                data-toggle="modal"
                                                data-target="#entryModal"
                                                class="addEntry inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                            <x-icon name="plus" size="4"/>
                                            <span>{{ __('chapter_empty_action') }}</span>
                                        </button>
                                    </div>
                                @endcan
                            @endif
                        </div>
                    </div>
                    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                        @if(isset($chapter->entries) && count($chapter->entries) > 0)
                            {{-- 5z.2: „+ Neuer Abschnitt" INNERHALB der Klammer — eingerückt,
                                 paper-50, sekundär (Design v6 § 2 „Zwei Einfüge-Zonen unterscheiden"). --}}
                            <div class="mb-6 ml-4">
                                <button type="button"
                                        title="{{__('add_entry')}}"
                                        data-chapter="{{$chapter->name}}"
                                        data-id="{{$chapter->id}}"
                                        data-toggle="modal"
                                        data-target="#entryModal"
                                        class="addEntry add_entry inline-flex w-full items-center justify-center gap-2 rounded-md
                                               border border-dashed border-line-200 bg-paper-50
                                               px-4 py-2.5 text-body text-ink-500
                                               hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700
                                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                    <x-icon name="plus" size="4"/> <span>{{__('new_entry')}}</span>
                                </button>
                            </div>
                        @endif
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
        <a class="add_chapter mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md
                  border-2 border-dashed border-line-200 bg-transparent
                  px-4 py-4 text-body text-ink-500
                  hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700 cursor-pointer"
           data-toggle="modal" data-target="#myModal">
            <x-icon name="plus" size="5"/> <span>{{__('new_chapter')}}</span>
        </a>
    @endif
    <hr class="mt-5 mb-5">
    <!-- Modal Chapter -->

    <x-ui.modal id="myModal" :title="__('add_new_element')">
        <div class="row">
            <div id="infoMsg" class=""></div>
            <div class="writeinfo"></div>
            <div class="col-xs-12">
                <form id="chapter_frm" name="projectForm"
                      action="{{ route('chapters.store') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    {{-- Phase 2 / D.12: _method-Override für die PATCH-Variante --}}
                    <input name="_method" type="hidden" value="">
                    <div class="col-xs-6">
                        <input name="projectId" id="projectId" type="hidden" class="form-control mb-3"
                               value="{{$project->id}}">
                        <input name="chapterId" type="hidden" class="form-control mb-3"
                               value="">
                        {{__('chapter_title')}}
                        <input name="chapterTitle" id="chapterTitle" type="text"
                               class="form-control mb-3 title-change"
                               placeholder="{{__('chapter_title')}}">
                    </div>
                    <div class="col-xs-12">
                        {{__('chapter_subtitle')}}
                        <input id="chapterSubtitle" name="chapterSubtitle" type="text"
                               class="form-control mb-3" placeholder="{{__('chapter_subtitle')}}">
                    </div>
                    <div class="col-xs-12">
                        {{__('chapter_description')}}
                        <div id="chapterDescription"></div>
                    </div>
                    <div class="col-xs-12 mt-4">
                        <button id="submit_chapter" type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </x-ui.modal>

    <x-ui.modal id="commentModal" :title="__('add_new_element_comment')" size="lg">
        <div class="row">
            <div id="headerComment"></div>

            <div id="listComment"></div>
            <form id="frmComment" action="" method="post">
                @csrf
                <input name="id" type="hidden" id="commentId">
                <input name="IdProjectComment" type="hidden" id="IdProjectComment">
                <div class="col-xs-12 mt-7">
                    <textarea id="commentProjectId" name="comment" class="form-control mb-3"
                              placeholder="{{__('leave_comment')}}" onkeyup="enableButton()"></textarea>
                </div>
                <div class="col-xs-12">
                    <button id="commentButton" type="submit" class="btn btn-primary float-right reply-comment" disabled>{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    @php
        // 5aa.4 Design v6 § 5: Umfangszeile — projektweite Zählung von Kapitel,
        // Eintrag und Block, damit der Modal-Titel eine Größe hat.
        $exportChapterCount = 0;
        $exportEntryCount = 0;
        $exportBlockCount = 0;
        if (isset($data) && isset($data->chapters)) {
            $exportChapterCount = count($data->chapters);
            foreach ($data->chapters as $exportChapter) {
                $exportEntryCount += count($exportChapter->entries ?? []);
                foreach ($exportChapter->entries ?? [] as $exportEntry) {
                    $exportBlockCount += count($exportEntry->mediaContent ?? []);
                }
            }
        }
    @endphp
    <x-ui.modal id="previewModal" :title="__('export_modal_title')" size="lg">
        <div x-data="{
                 format: 'html',
                 accent: '#c23934',
                 accents: [
                     { hex: '#c23934', name: 'Rot', ratio: '5.4 : 1' },
                     { hex: '#1b2330', name: 'Anthrazit', ratio: '15.6 : 1' },
                     { hex: '#0f766e', name: 'Teal', ratio: '5.9 : 1' },
                     { hex: '#7c2d12', name: 'Braun', ratio: '9.5 : 1' },
                 ],
                 language: 'de',
                 collapse: false,
                 background_second: false,
             }">
            <p class="mb-4 text-caption text-ink-500">
                {{ __('export_scope', ['chapters' => $exportChapterCount, 'entries' => $exportEntryCount, 'blocks' => $exportBlockCount]) }}
            </p>

            <form id="exportForm" action="{{route('preview')}}" method="get">
                @csrf
                <input name="project" type="hidden" value="{{$project->id}}">
                <input name="colorAccent" type="hidden" :value="accent">
                <input name="colorChapter" type="hidden" :value="accent">

                {{-- Format als zwei Radio-Karten. --}}
                <fieldset class="mb-6">
                    <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                        {{ __('export_format_label') }}
                    </legend>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label
                            :class="format === 'html' ? 'border-primary bg-primary/5' : 'border-line-200 bg-canvas-bg'"
                            class="flex cursor-pointer flex-col gap-1 rounded-md border-2 p-3 hover:border-ink-300"
                        >
                            <div class="flex items-center gap-2">
                                <input type="radio" name="format_ui" value="html" x-model="format" class="size-4 accent-primary"/>
                                <span class="text-body font-semibold text-ink-900">{{ __('export_format_html_title') }}</span>
                            </div>
                            <p class="text-caption text-ink-500">{{ __('export_format_html_desc') }}</p>
                        </label>
                        <label
                            :class="format === 'pdf' ? 'border-primary bg-primary/5' : 'border-line-200 bg-canvas-bg'"
                            class="flex cursor-pointer flex-col gap-1 rounded-md border-2 p-3 hover:border-ink-300"
                        >
                            <div class="flex items-center gap-2">
                                <input type="radio" name="format_ui" value="pdf" x-model="format" class="size-4 accent-primary"/>
                                <span class="text-body font-semibold text-ink-900">{{ __('export_format_pdf_title') }}</span>
                            </div>
                            <p class="text-caption text-ink-500">{{ __('export_format_pdf_desc') }}</p>
                        </label>
                    </div>
                    <input type="checkbox" name="pdf" value="1" x-show="false" :checked="format === 'pdf'" class="hidden"/>
                </fieldset>

                {{-- Prüfung vor dem Export: Bild-, AV- und Transkript-Lücken namentlich. --}}
                @php
                    // 5y.10: Prüft Bild-Angaben projektweit und listet fehlende Felder namentlich auf.
                    // Veröffentlichen bleibt möglich — die Lücken erscheinen im Preview, hier wird
                    // vor dem Export darauf hingewiesen.
                    $publishCheckMissing = collect();
                    if (isset($data) && isset($data->chapters)) {
                        foreach ($data->chapters as $publishCheckChapter) {
                            foreach ($publishCheckChapter->entries as $publishCheckEntry) {
                                foreach ($publishCheckEntry->mediaContent as $publishCheckMc) {
                                    // MediaContent::gallery/audiovisual sind polymorph ueber content_id
                                    // aufgeloest — ohne content_type-Filter greift die Relation auch
                                    // in fremde Projekte. Bug in 5y.10, gefixt in 5y.10-Followup.
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
                    }
                @endphp

                <fieldset class="mb-6">
                    <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                        {{ __('export_check_label') }}
                    </legend>
                    @if ($publishCheckMissing->isNotEmpty())
                        <ul class="space-y-1 rounded-md border border-warning-bg bg-warning-bg/40 px-4 py-3 text-caption text-ink-700">
                            @foreach ($publishCheckMissing as $publishCheckRow)
                                <li class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span>
                                        <span class="text-warning">⚠</span>
                                        <span class="font-medium text-ink-900">{{ $publishCheckRow['title'] }}</span>
                                        — {{ $publishCheckRow['fields'] }}
                                    </span>
                                    <a href="{{ $publishCheckRow['anchor'] }}"
                                       data-dismiss="modal"
                                       class="text-caption text-primary hover:underline">
                                        {{ __('export_check_view') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="rounded-md bg-success-bg px-3 py-2 text-caption text-success">
                            ✓ {{ __('publish_check_all_clear') }}
                        </p>
                    @endif
                    <p class="mt-2 text-caption text-ink-500">{{ __('export_check_footer_hint') }}</p>
                </fieldset>

                {{-- Sprache. --}}
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
                            @isset($data['percentageOfTranslation'])
                                <span class="text-caption text-ink-500">· {{ $data['percentageOfTranslation'] }} %</span>
                            @endisset
                        </label>
                    </div>
                    <p class="mt-2 text-caption text-ink-500">{{ __('export_language_consequence') }}</p>
                </fieldset>

                {{-- Akzentfarbe: vier kuratierte Farben statt Farb-Picker. --}}
                <fieldset class="mb-6">
                    <legend class="mb-2 text-caption font-semibold uppercase tracking-wider text-ink-500">
                        {{ __('export_accent_label') }}
                    </legend>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="c in accents" :key="c.hex">
                            <button type="button" @click="accent = c.hex"
                                    :aria-pressed="accent === c.hex"
                                    class="flex items-center gap-2 rounded-md border-2 px-3 py-1.5 text-caption text-ink-900 hover:border-ink-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                    :class="accent === c.hex ? 'border-ink-900 bg-canvas-bg' : 'border-line-200 bg-canvas-bg'">
                                <span class="inline-block size-4 rounded-sm" :style="'background-color: ' + c.hex"></span>
                                <span x-text="c.name"></span>
                                <span class="text-ink-500" x-text="'· ' + c.ratio + ' ✓'"></span>
                            </button>
                        </template>
                    </div>
                    <p class="mt-2 text-caption text-ink-500">{{ __('export_accent_hint') }}</p>
                </fieldset>

                {{-- Darstellung als Toggles mit Wirkung. --}}
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
                        <label class="inline-flex items-start gap-3">
                            <input type="checkbox" name="backgroundSecond" x-model="background_second" class="mt-1 size-4 accent-primary"/>
                            <span>
                                <span class="block text-body text-ink-900">{{ __('export_toggle_alternate_title') }}</span>
                                <span class="block text-caption text-ink-500">{{ __('export_toggle_alternate_desc') }}</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <footer class="flex items-center justify-end gap-2 border-t border-line-200 pt-3">
                    <button type="button" data-dismiss="modal"
                            class="inline-flex items-center rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        {{ __('cancel') }}
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <x-icon name="download" size="4"/>
                        <span x-text="format === 'pdf' ? '{{ __('export_button_pdf') }}' : '{{ __('export_button_html') }}'"></span>
                    </button>
                </footer>
            </form>
        </div>
    </x-ui.modal>
    @include('Entry.index')
    @include('contents.index')
    {{-- Stakeholder-Fix Juni 2026: `@include('contents.gallery')` war
         hier ein Doppel-Include — `contents.index` (Z. 86) lädt das
         Gallery-Modal bereits transitiv. Folge: galleryModal + Form +
         alle Hidden Inputs (galleryId, title, …) waren DOM-doppelt,
         was den Submit unzuverlässig machte (POST /save-gallery → 404).
         Image-/Audiovisual-Modal kommen weiter direkt rein — die
         haben keine eigenen Sub-Includes. --}}
    @include('contents.image')
    @include('contents.audiovisual')
@endsection
{{-- Export-Buttons (PDF/Preview/Download) wandern seit Phase
     5-D.6b-P3.15 ins ⋮-Menue neben 'Veroeffentlichen' in der
     Editor-Chrome-Bar oben. Kein Footer-Block mehr am Seitenende. --}}
@push('scripts')
    <script>
        $(".rotate").click(function() {
            $(this).toggleClass("down");
        })

        function activeComment(id) {
            $('#edit_' + id).show();
            $('#original_' + id).hide();
        }

        function activeReply(id) {
            $('#reply_' + id).show();
            $('#original_' + id).hide();
        }

        function cancelComment(id) {
            $('#edit_' + id).hide();
            $('#original_' + id).show();

        }

        function cancelReply(id) {
            $('#reply_' + id).hide();
            $('#original_' + id).show();

        }

        //Set status for comment
        $(document).on('change', '.status-list', function (el) {
            //var conf = confirm('Are you sure want to change status ?');

            let url;

            var statusName = $(el.target).val();
            var id = $(el.target).attr("data-id");
            var model = $(el.target).attr("data-model");

            switch (model) {
                case "chapter":
                    url = "{{ route('comment.chapter.status') }}";
                    break;
                case "entry":
                    url = "{{ route('comment.entry.status') }}";
                    break;
                case "text":
                    url = "{{ route('comment.text.status') }}";
                    break;
                case "image":
                    url = "{{ route('comment.image.status') }}";
                    break;
                case "project":
                    url = "{{ route('comment.project.status') }}";
                    break;
            }

            $.ajax({
                type: 'POST',
                url: url,
                data: {id: id, model: model, status: statusName},
                success: function (data) {
                    //console.log(data);
                }
            });

        });

        //$(document).ready(function () {
        $('#btn_text').click(function () {
            let hvalue = quill.root.innerHTML;
            $(this).append("<textarea name='contentText' style='display:none'>" + hvalue + "</textarea>");
        });

        //Submit chapter
        $('#submit_chapter').click(function () {
            let chapterDescription = quillChapter.root.innerHTML;
            $(this).append("<textarea name='chapterDescription' style='display:none'>" + chapterDescription + "</textarea>");
        });

        //Submit entry
        $('#submit_entry').click(function () {
            let entryDescription = quillEntry.root.innerHTML;
            $(this).append("<textarea name='entryDescription' style='display:none'>" + entryDescription + "</textarea>");
        });

        //tooltip initialize
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })

        function initialize() {
            quill.container.firstChild.innerHTML = '';
            $('#copyrightText').val('');
            $('#originText').val('');
            $('#textId').val('');
            $('#url').text('');
            $('#altText').val('');
            $('#copyrightImage').val('');
            $('#originImage').val('');
            $('#imageId').val('');
            $('#image').val('');
            $('#newImage').val('');
        }

        let Font = Quill.import('formats/font');
        Font.whitelist = ['times-new-roman', 'arial', 'Sans Serif'];
        Quill.register(Font, true);

        let toolbarOptions = [
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],
            ['bold', 'italic', 'underline', 'strike'], // toggled buttons
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            [{
                'indent': '-1'
            }, {
                'indent': '+1'
            }], // outdent/indent
            [{
                'direction': 'rtl'
            }], // text direction
            [{
                'size': ['small', false, 'large', 'huge']
            }], // custom dropdown

            [{
                'color': []
            }, {
                'background': []
            }], // dropdown with defaults from theme
            [{
                'font': ['', 'times-new-roman', 'arial']
            }],
            [{align: ''}, {align: 'center'}, {align: 'right'}, {align: 'justify'}],
            ['link'],
            ['clean'] // remove formatting button
        ];
        let quill = new Quill('#contentText', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillEntry = new Quill('#entryDescription', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillChapter = new Quill('#chapterDescription', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        /**
         * Add protocol to link if it is missing. Considers the current selection in Quill.
         */
        function updateLink() {
            var selection = quill.getSelection(),
                selectionChanged = false;
            if (selection === null) {
                var tooltip = quill.theme.tooltip;
                if (tooltip.hasOwnProperty('linkRange')) {
                    // user started to edit a link
                    lastLinkRange = tooltip.linkRange;
                    return;
                } else {
                    // user finished editing a link
                    var format = quill.getFormat(lastLinkRange),
                        link = format.link;
                    quill.setSelection(lastLinkRange.index, lastLinkRange.length, 'silent');
                    selectionChanged = true;
                }
            } else {
                var format = quill.getFormat();
                if (!format.hasOwnProperty('link')) {
                    return; // not a link after all
                }
                var link = format.link;
            }
            // add protocol if not there yet
            if (!/^https?:/.test(link)) {
                link = 'http:' + link;
                quill.format('link', link);
                // reset selection if we changed it
                if (selectionChanged) {
                    if (selection === null) {
                        quill.setSelection(selection, 0, 'silent');
                    } else {
                        quill.setSelection(selection.index, selection.length, 'silent');
                    }
                }
            }
        }


        // Add Entry — Event-Delegation, damit auch der Trigger aus
        // dem Sidebar-Tree (Livewire-gerendert, nicht beim Page-Load
        // im DOM) greift. Ohne Delegation feuert der Handler nur
        // fuer den Trigger im Chapter-Card, der Sidebar-Tree-Trigger
        // oeffnet den Modal ohne gesetzte chapterId und der Request
        // faellt in der Policy mit 403 durch.
        //
        // Persona-Smoke 2026-08-15: Selektoren auf #entry_frm bzw.
        // #chapter_frm pinnen, sonst kreuzen sich Chapter- und
        // Entry-Modal (beide haben ein hidden input[name=chapterId]).
        // Wenn Bootstrap beim Oeffnen des Entry-Modals ein anderes
        // Modal schliesst, feuert dessen hidden.bs.modal-Handler
        // resetChapterForm — und das globale
        // $('input[name=chapterId]').val('') leert auch das gerade
        // per .addEntry gesetzte Feld im Entry-Modal → 403.
        $(document).on('click', '.addEntry', function () {
            $('#entryTitle').val('');
            $('#entrySubtitle').val('');
            let id = $(this).attr('data-id');
            let chapter = $(this).attr('data-chapter');
            $('#entry_frm input[name="chapterId"]').val(id);
            $('#lblChapter').text(chapter);
        });

        // Phase 2 / D.12: zentrale Helper für Chapter-Form-Mode.
        // Phase-1-Verzweigung im Controller ist weg — wir müssen jetzt
        // im Client zwischen POST /chapters und PATCH /chapters/{id}
        // umschalten.
        const chapterStoreUrl = "{{ route('chapters.store') }}";
        const chapterUpdateUrlTpl = "{{ route('chapters.update', ':id') }}";

        function resetChapterForm() {
            $('#chapter_frm').attr('action', chapterStoreUrl);
            $('#chapter_frm input[name="_method"]').val('');
            // Persona-Smoke 2026-08-15: Selector auf #chapter_frm
            // pinnen — sonst leert der Reset auch den chapterId-
            // Input im entryModal und der naechste Entry-Add faellt
            // in der StoreEntryRequest::authorize() mit 403 durch.
            $('#chapter_frm input[name="chapterId"]').val('');
        }

        function setChapterFormUpdate(id) {
            $('#chapter_frm').attr('action', chapterUpdateUrlTpl.replace(':id', id));
            $('#chapter_frm input[name="_method"]').val('PATCH');
        }

        // Chapter-Modal kann sowohl für Add (Default) als auch für
        // Modify geöffnet werden; ein hidden-Reset stellt sicher, dass
        // der nächste Add nicht aus dem letzten Update-Mode lebt.
        $('#myModal').on('hidden.bs.modal', resetChapterForm);

        // Modify-Chapter-Handler entfällt seit Phase 5c.6.a — Kapitel-
        // Titel, Subtitel und Beschreibung werden direkt im
        // Kapitel-Card editiert, das Chapter-Edit-Modal ist raus.
        // Add-Chapter-Modal (myModal) bleibt unangetastet,
        // resetChapterForm/setChapterFormUpdate werden dort weiter
        // genutzt.

        //Modify entry
        // Phase 2 / D.13: zentrale Helper für Entry-Form-Mode (analog
        // zu Chapter, siehe oben). Add ist Default, Modify schaltet auf
        // PATCH um.
        const entryStoreUrl = "{{ route('entries.store') }}";
        const entryUpdateUrlTpl = "{{ route('entries.update', ':id') }}";

        function resetEntryForm() {
            $('#entry_frm').attr('action', entryStoreUrl);
            $('#entry_frm input[name="_method"]').val('');
            $('input[name="entryId"]').val('');
        }

        function setEntryFormUpdate(id) {
            $('#entry_frm').attr('action', entryUpdateUrlTpl.replace(':id', id));
            $('#entry_frm input[name="_method"]').val('PATCH');
        }

        $('#entryModal').on('hidden.bs.modal', resetEntryForm);

        // Modify-Entry-Handler entfällt seit Phase 5c.6.b — Entry-
        // Titel, Subtitel und Beschreibung werden direkt im Entry-
        // Card editiert. Add-Entry-Modal bleibt unangetastet.

        // Modify-Text-Handler entfaellt seit Phase 5c.6.c.4 —
        // Text-Content wird ueber die rich-text-editor-Volt-
        // Komponente direkt im Content-Card editiert. Der
        // contentModal + der Quill (quill) bleiben fuer den
        // Add-Text-Fall bestehen (neuer Textblock hinzufuegen).

        // Modify-Image-Handler entfaellt seit Phase 5c.6.c.4-Followup —
        // Copyright/Quelle werden ueber die source-picker-Volt-Komponente
        // in den Bild-Details editiert, das Alt-Feld ueber inline-editor.
        // imageModal bleibt bestehen fuer den Add-Image-Fall.

        // Modify-Gallery-Handler entfaellt seit Phase 5c.6.c.1 —
        // Title, Subtitle und Description werden direkt im Gallery-
        // Card via der Volt-Komponente inline-editor editiert.
        // Add-Image-Modal (imageModal) bleibt fuer neue Bilder in
        // der Galerie.

        //Add Content
        $('.addContent').click(function () {
            let id = $(this).attr("data-id");
            let chapter = $(this).attr("data-chapter");
            let entry = $(this).attr("data-entry");
            $('input[name="entryId"]').val(id);
            $('#contentType').show();
            $('#addText').hide();
            $('#addImage').hide();
            $('#chapterLbl').text(chapter);
            $('#entryLbl').text(entry);
            initialize();
        })

        //Add Content
        $('.addImage').click(function () {
            let id = $(this).attr("data-id");
            let chapter = $(this).attr("data-chapter");
            let entry = $(this).attr("data-entry");
            let entryId = $(this).attr("data-entryId");
            let gallery = $(this).attr("data-gallery");
            $('input[name="galleryId"]').val(id);
            // Hotfix-Welle 0 (2026-06-23): entryId-Hidden-Input
            // setzen, sonst läuft saveImage auf Entry::findOrFail(0)
            // → 404 und Bild wird zwar gespeichert (über galleryId),
            // aber die Authorize-Kette schlägt fehl und die
            // Render-Seite zeigt das Bild nicht.
            $('input[name="entryId"]').val(entryId);
            $('#chapterLbl').text(chapter);
            $('#entryLbl').text(entry);
            $('#galleryLbl').text(gallery);
            // 5y.4/5y.9: gedroppte Dateien aus der Drop-Zone ins File-Input
            // uebernehmen, damit User im Modal nur noch Copyright/Origin
            // ausfuellen muss.
            if (window.__ccDroppedImageFiles && window.__ccDroppedImageFiles.length) {
                const imageInput = document.getElementById('image');
                if (imageInput) {
                    try {
                        const dt = new DataTransfer();
                        for (const f of window.__ccDroppedImageFiles) dt.items.add(f);
                        imageInput.files = dt.files;
                    } catch (e) { /* aeltere Browser: File-Input bleibt leer, User waehlt manuell */ }
                }
                window.__ccDroppedImageFiles = null;
            }
            initialize();
        })

        //Toggle Text Block
        $('.add-Text').click(function (e) {
            e.preventDefault();
            $('#addText').toggle();
            $('#contentType').toggle();
            $('#addImage').hide();
        })

        //Toggle Image Block
        $('.add-Image').click(function (e) {
            $('#galleryModal').modal('show');
            e.preventDefault();
        })

        //Toggle video Block
        $('.add-video').click(function (e) {
            resetValues();
            $('#savedAudio').hide();
            $('#link').show();
            $('#type').val('video');
            $('#audiovisualModal').modal('show');
            e.preventDefault();
        })

        //Toggle video Block
        $('.add-audio').click(function (e) {
            resetValues();
            $('#savedAudio').show();
            $('#link').hide();
            $('#type').val('audio');
            $('#audiovisualModal').modal('show');
            e.preventDefault();
        })

        // Autocomplete-Felder. In $(document).ready(...) gewrappt, weil
        // der jQuery-Shim fuer typeahead (resources/js/typeahead.js) als
        // Vite-Module-Script erst nach DOMContentLoaded verfuegbar ist;
        // ohne den Wrapper liefe der Aufruf direkt beim HTML-Parsing.
        var path = "{{ route('autocomplete') }}";
        $(function () {
            $('#copyrightText').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Copyright'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${item}`;
                },
                afterSelect: function (item) {
                    $('#copyrightText').val(item);
                },
                fitToElement: true
            });

            $('#originText').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Origin'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item)}`;
                },
                afterSelect: function (item) {
                    $('#originText').val(Object.values(item));
                },
                fitToElement: true
            });

            $('#copyrightImage').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Copyright'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item)}`;
                },
                afterSelect: function (item) {
                    $('#copyrightImage').val(Object.values(item));
                },
                fitToElement: true
            });

            $('#originImage').typeahead({
                source: function (query, process) {
                    return $.get(path, {query: query, type: 'Origin'}, function (data) {
                        return process(data);
                    });
                },
                displayText: function (item) {
                    return `${Object.values(item.name)}`;
                },
                afterSelect: function (item) {
                    $('#originImage').val(Object.values(item.name));
                },
                fitToElement: true
            });
        });

        //Add thumbnail
        $(document).on('change', '.btn-file :file', function () {
            let input = $(this),
                label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
            input.trigger('fileselect', [label]);
        });

        $('.btn-file :file').on('fileselect', function (event, label) {

            let input = $(this).parents('.input-group').find(':text'),
                log = label;

            if (input.length) {
                input.val(log);
            } else {

            }

        });

        function readURL(input) {
            if (input.files && input.files[0]) {
                let reader = new FileReader();

                reader.onload = function (e) {
                    $('#img-upload').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#imgInp").change(function () {
            readURL(this);
        });

        $("#upload").change(function () {
            readURL(this);
        });

        function confirmExit() {
            if (formmodified == 1) {
                return "Exit?";
            }
        }

        //Add Chapter through Ajax
        $.ajaxSetup({headers: {'X-CSRF-TOKEN': jQuery('meta[name="csrf-token"]').attr('content')}});

        $("#addNewChapter").click(function (e) {
            e.preventDefault();

            let chapterTitle = $("input[name=chapterTitle]").val();
            let chapterSubtitle = $("input[name=chapterSubtitle]").val();
            let chapterDescription = $("textarea[name=chapterDescription]").val();
            let projectId = {!! json_encode($project->id) !!};

            $.ajax({
                type: 'POST',
                url: "{{ route('chapters.store') }}",
                data: {
                    chapterTitle: chapterTitle,
                    chapterSubtitle: chapterSubtitle,
                    chapterDescription: chapterDescription,
                    projectId: projectId
                },
                success: function (data) {
                    //alert(data.data['id']);
                    $('#infoMsg').addClass('alert alert-success');
                    $('#infoMsg').html(data.success);

                }
            });
        });

        //})

        $('#userRole').change(function () {
            let userId = $('#userRole').val();
            let project = $(this).attr('data-project');
            getUserRights(userId, project);
        })

        $('.edit-user').click(function () {
            let id = $(this).attr('data-id');
            let project = $(this).attr('data-project');
            $("div.user-permission select").val(id).change();
            getUserRights(id, project);
        })

        function getUserRights(id, project) {
            $(".cb-element input:checkbox").prop("checked", false);
            let url = "{{ route('permission.project', ":id") }}";
            id = id + '_' + project;
            url = url.replace(':id', id);

            $.ajax({
                type: 'GET',
                url: url,
                success: function (data) {
                    if (data.length > 0) {
                        $("#list").find('[value=' + data.join('], [value=') + ']').prop("checked", true);
                    }
                    console.log(data);
                }
            });
        }

        //Logs
        $(".reset-version").click(function () {
            $('#oldActivity').html('');
            $('#currentActivity').html('');
            $('#valueReset').val('');
            $('#idReset').val('');
            $('#infoLog').html('');
            let id = $(this).attr('data-id');
            let userName = $(this).attr('data-user');
            let timestamp = $(this).attr('data-date');
            let oldActivity = $(this).attr('data-preview');
            let newActivity = $(this).attr('data-new');
            let oldValue = $(this).attr('data-old');
            let chapter = $(this).attr('data-chapterName');
            let entry = $(this).attr('data-entryName');

            $('#valueReset').val(oldValue);
            $('#idReset').val(id);
            $(oldActivity).appendTo('#oldActivity');
            $(newActivity).appendTo('#currentActivity');
            $('<p> Chapter: ' + chapter + '</p>').appendTo('#infoLog');
            $('<p> Entry: ' + entry + '</p>').appendTo('#infoLog');

        })

        //Onchange update project
        $('.update-project').on('focusin', function () {
            $(this).data('val', $(this).val());
        });

        $('.update-project').on('change', function () {
            var prev = $(this).data('val');
            var current = $(this).val();
            if (prev !== current) {
                // Persona-Smoke 2026-08-15: x-icon-Komponente darf
                // hier NICHT inline im JS-String stehen — Blade rendert
                // sie zu einem mehrzeiligen Inline-SVG mit doppelten
                // Quotes, das den einfach-quotierten JS-String
                // zerbricht (SyntaxError, gesamter Script-Push-Block
                // bricht ab, addEntry und add-Text-Handler tot).
                // Label-String ueber json_encode ausgeben.
                $('#updateProjectBtn').html(
                    '<button id="btn_save" class="btn btn-secondary btn-block text-left" '
                    + 'type="submit" name="btn_submit" value="Save">'
                    + @json(__('save'))
                    + '</button>'
                );
            }

        });

        $('.enable-textarea').keyup(function(e) {
            let input = e.target.id;
            let dInput = this.value;
            if (this.value === '') {
                $('#commentProjectId_'+e.target.id).prop('disabled', true);
            }else {
                $('#commentProjectId_'+e.target.id).prop('disabled', false);
            }

        });


        function enableButton(){
            $('#commentButton').prop('disabled', false);
        }



        $(document).ready(function () {

			/*$(".col-sm-3").append($(".row.versions"));*/

            //Set project comment id
            $('#IdProjectComment').val({!! json_encode($project->id) !!});

            //Check copyright and origin for Text
            $("#text_frm").submit(function (event) {
                let copyrightText = $("input[name='copyrightText']", this).val();
                let originText = $("input[name='originText']", this).val();

                if (originText.length === 0) {
                    event.preventDefault();
                    alert('Origin should not be empty');
                }

                if (copyrightText.length === 0) {
                    event.preventDefault();
                    alert('Copyright should not be empty');
                }
            });

            //Check copyright and origin for image
            $("#image_frm").submit(function (event) {
                let copyrightImage = $("input[name='copyrightImage']", this).val();
                let originImage = $("input[name='originImage']", this).val();

                if (originImage.length === 0) {
                    event.preventDefault();
                    alert('Origin should not be empty');
                }

                if (copyrightImage.length === 0) {
                    event.preventDefault();
                    alert('Copyright should not be empty');
                }
            });

        });

        // collapseExpand()- und collapseExpandEntry()-Handler
        // entfallen seit Phase 5-D.6b: Kapitel und Eintrag sind
        // offene Zonen, das Auf-/Zuklappen laeuft ueber den
        // Sidebar-Tree (Alpine-x-collapse in
        // sidebar-tree.blade.php).

        // Phase-5-Backlog-Sammler (2026-08-16, #52): frueher wurden hier
        // die Legacy-Invite-Modals per Session::get('error_code') 6/7
        // getriggert. Beide Modals sind mit dem Screen-3B-Umbau (5d.4)
        // und dem Register-Reader-Default (5d.7) abgeloest.

        $('.edit-user').click(function (event) {
            //event.preventDefault();
            $("#selectedUser").html('');
            let user = $(this).attr("data-id");
            let project = $(this).attr("data-project");
            let permission = $(this).attr("data-permission");
            let listPermissions = @json($permissions);
            jQuery.each(listPermissions, function (i, val) {
                let check = "";
                if (val.id in $.parseJSON(permission)) check = "checked";
                $("#selectedUser").append('<div class="form-check"><input name="permissions[]" class="form-check-input" type="checkbox" value="' + val.id + '" id="flexCheckChecked"' + check + ' > <label class="form-check-label" for="flexCheckChecked"> ' + val.name + ' </label></div>')
            });
            $('#selectedUserId').val(user);
            $('#editUserPermission').load('/user/' + user + '/project/' + project + '/info');
        })

        $('.add-user').click(function () {
            $("#detailUser").html('<form action="{{route('check.email')}}" method="POST" enctype="multipart/form-data" class="form-group form-inline" id="frmCheckEmail">@csrf<input name="project" @isset($project->id) value="{{$project->id}}" @endisset type="hidden"/><div class="form-group col-xs-8 mb-2"><input type="email" class="form-control" name="userEmail" placeholder="User email" style="width: 100% !important;"></div><button id="userEmailCheck" type="submit" class="btn btn-primary mb-2">Einladen</button></form>');
        })

        $('.comment-edit').click(function (){

        })


        // Inline-Edit der Kommentar-Texte laeuft jetzt ueber die
        // Volt-Komponente comment-text-editor in projects/description.
        // x-editable und seine Form-Buttons fallen damit.


        // Update Status: Comment-Status laeuft jetzt ueber die Volt-Komponente
        // (comment-status-switcher) in projects/description.blade.php.
        // Der fruehere $.ajax-POST-Pfad auf /comment/{id}/update/{status}
        // wird nicht mehr aus dem Frontend gerufen — die Route bleibt
        // vorerst stehen (Route-Cleanup folgt spaeter).

        $(document).ready(function (){
            $('.reply').hide();
        })

        $('.enable-reply').click(function (){
            $('.reply_'+this.id).toggle();
        })

        // Audiovisual-Modify-Handler entfaellt seit Phase 5c.6.c.3 —
        // link/type/copyright/source werden ueber die inline-editor-
        // Volt-Komponente direkt unter dem Player editiert. Die
        // resetValues()-Hilfsfunktion wird vom Add-Handler weiter
        // unten noch benoetigt.

        function resetValues(){
            $('#link').val('');
            $('#copyright').val('');
            $('#source').val('');
            $('#type').val('');
        }

        //Drag and drop
        // Reader-Frontend-Härtung Juni 2026: jQuery-Sortable-Inits
        // nur für User mit update-Recht. Backend (chapter.drag →
        // ChapterController::saveDragAndDrop) blockt seit Phase 4
        // via $this->authorize('update', $project); das Frontend
        // initialisierte die Sortables aber für alle User —
        // Reader konnten Content visuell verschieben, der POST
        // gegen die Route lief in 403, der UI-Zustand blieb
        // verschoben bis zum nächsten Refresh. Verwirrend, UX-
        // mäßig falsch und in der Konsole ein 403-Spuk.
        @can('update', $project)
        $(function() {
            $( ".sortable_list_chapter" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableChapter",

                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-chapter'});
                    data['element'] = 'chapter';
                    data['project'] = {{$project->id}};

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {
                            // setting a timeout
                            //console.log(JSON.stringify(data))
                        },
                        success: function (data) {
                            //location.reload();
                        },
                        error: function () {

                        }
                    })
                }
            });

            $( ".sortable_list_entry" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableEntry",
                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-entry'});
                    data['element'] = 'entry';
                    data['chapter'] = this.id;

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {

                        },
                        success: function (data) {

                        },
                        error: function () {

                        }
                    })
                }
            });

            $( ".sortable_list_content" ).sortable({
				placeholder:"placeholder",
                connectWith: ".connectedSortableContent",
                update: function(event, ui) {
                    let data = {};
                    data['data'] = $(this).sortable('toArray', {attribute:'data-content'});
                    data['element'] = 'content';
                    data['entry'] = this.id;

                    $.ajax({
                        data: {"data":data},
                        type: 'POST',
                        url: "{{route('chapter.drag')}}",
                        beforeSend: function() {

                        },
                        success: function (data) {

                        },
                        error: function () {

                        }
                    })
                }
            });


        });
        @endcan {{-- Reader-Frontend-Härtung Juni 2026 (Sortable-Init-Gate) --}}

    </script>
@endpush
