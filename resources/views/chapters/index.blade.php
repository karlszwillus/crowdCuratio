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
                <li class="chapter group" data-chapter="{{$chapter->id}}" data-project="{{$project->id}}" id="{{$chapter->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                    {{-- Kapitel = Zone, keine Karte (Handoff v4 § P1.2).
                         Kein Background, kein Border. Titel + Untertitel
                         + Description sitzen offen auf dem Canvas; nur
                         Entry und Block-Cards tragen Rahmen. Ein
                         horizontaler Trenner unter der Kapitel-
                         Beschreibung markiert den Uebergang zu den
                         Entries. --}}
                    <div id="{{$chapter->id}}" class="mb-10">
                        <header class="mb-4 flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1" id="anchor_Chapter_{{$chapter->id}}">
                                @can('update', $project)
                                    <livewire:inline-editor
                                        :model="$chapter"
                                        field="name"
                                        rules="nullable|string|max:255"
                                        :label="__('chapter_title')"
                                        :variant="'title'"
                                        :key="'chapter-name-'.$chapter->id"
                                    />
                                    <livewire:inline-editor
                                        :model="$chapter"
                                        field="subtitle"
                                        rules="nullable|string|max:255"
                                        :label="__('chapter_subtitle')"
                                        :variant="'subtitle'"
                                        :key="'chapter-subtitle-'.$chapter->id"
                                    />
                                @else
                                    <h2 class="text-title font-semibold text-ink-900">{!! $chapter->name !!}</h2>
                                    <p class="mt-1 text-body text-ink-500">{!! $chapter->subtitle !!}</p>
                                @endcan
                            </div>

                            <form action="{{ route('chapters.destroy',$chapter->id) }}" method="POST"
                                  class="flex shrink-0 items-center gap-1 text-ink-500">
                                @csrf
                                <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                @method('DELETE')

                                <a href="{{route('projects.edit',['project'=> $project, 'log'=> $chapter->id, 'model' => 'Chapter'])}}"
                                   title="{{ __('older_versions') }}"
                                   class="inline-flex size-11 items-center justify-center rounded-md hover:bg-line-100 hover:text-ink-900">
                                    <x-icon name="rotate-ccw" size="4"/>
                                </a>

                                @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                    <x-comment.trigger
                                        commentableType="App\Models\Chapter"
                                        :commentableId="$chapter->id"
                                        :count="isset($chapter->comments) ? count($chapter->comments) : 0"
                                    />
                                @endif

                                @if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                    <button type="submit"
                                            onclick="return confirm('{{__('message_delete_confirm')}}')"
                                            title="{{ __('delete_chapter') }}"
                                            class="inline-flex size-11 items-center justify-center rounded-md hover:bg-danger-bg hover:text-danger">
                                        <x-icon name="trash-2" size="4"/>
                                    </button>
                                @endif

                                {{-- Chevron-Toggle entfaellt seit 5-D.6b: Kapitel
                                     ist eine offene Zone, das Auf-/Zuklappen
                                     laeuft ueber den Sidebar-Tree. --}}
                            </form>
                        </header>

                        {{-- Kapitel-Beschreibung als Rich-Text-Editor,
                             direkt unter dem Section-Header. --}}
                        @can('update', $project)
                            <livewire:rich-text-editor
                                :model="$chapter"
                                field="description"
                                rules="nullable|string"
                                :label="__('chapter_description')"
                                :key="'chapter-description-'.$chapter->id"
                            />
                        @else
                            <p class="text-body text-ink-700">{!! $chapter->description !!}</p>
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
                                        <li class="entry group" data-chapter="{{$chapter->id}}" data-entry="{{$entry->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                            {{-- Entry als Karte mit Mono-Caps-Label
                                                 (Handoff v4 Screen 02: „EINTRAG · KAPITEL 2").
                                                 Bezug zum umschließenden Kapitel steht
                                                 explizit im Kopf, nicht ueber CSS-Einrueckung. --}}
                                            <div id="P-{{$project->id}}-C-{{$chapter->id}}-entry-{{$entry->id}}"
                                                 class="mb-6 rounded-lg border border-line-200 bg-paper-0 p-6 shadow-subtle">
                                                <p class="mb-2 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                                                    {{ __('entry') }} · {{ __('chapter') }} {{ $key + 1 }}
                                                </p>
                                                <header class="mb-3 flex items-start justify-between gap-4">
                                                    <div class="min-w-0 flex-1" id="anchor_Entry_{{$entry->id}}">
                                                        @can('update', $project)
                                                            <livewire:inline-editor
                                                                :model="$entry"
                                                                field="name"
                                                                rules="nullable|string|max:255"
                                                                :label="__('entry_title')"
                                                                :variant="'heading'"
                                                                :key="'entry-name-'.$entry->id"
                                                            />
                                                            <livewire:inline-editor
                                                                :model="$entry"
                                                                field="subtitle"
                                                                rules="nullable|string|max:255"
                                                                :label="__('entry_subtitle')"
                                                                :variant="'subtitle'"
                                                                :key="'entry-subtitle-'.$entry->id"
                                                            />
                                                        @else
                                                            <h3 class="text-heading font-semibold text-ink-900">{!! $entry->name !!}</h3>
                                                            <p class="mt-1 text-body text-ink-500">{!! $entry->subtitle !!}</p>
                                                        @endcan
                                                    </div>

                                                    <form action="{{ route('entries.destroy',$entry->id) }}"
                                                          method="POST"
                                                          class="flex shrink-0 items-center gap-1 text-ink-500">
                                                        @csrf
                                                        <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                        @method('DELETE')

                                                        <a href="{{route('projects.edit',['project'=> $project, 'log'=> $entry->id, 'model' => 'Entry'])}}"
                                                           title="{{ __('older_versions') }}"
                                                           class="inline-flex size-11 items-center justify-center rounded-md hover:bg-line-100 hover:text-ink-900">
                                                            <x-icon name="rotate-ccw" size="4"/>
                                                        </a>

                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                            <x-comment.trigger
                                                                commentableType="App\Models\Entry"
                                                                :commentableId="$entry->id"
                                                                :count="isset($entry->comments) ? count($entry->comments) : 0"
                                                            />
                                                        @endif

                                                        @if(in_array('edit', $listPermissions) || Auth::user()->can('delete', $project))
                                                            <button type="submit"
                                                                    onclick="return confirm('{{__('message_delete_confirm')}}')"
                                                                    title="{{ __('delete_entry') }}"
                                                                    class="inline-flex size-11 items-center justify-center rounded-md hover:bg-danger-bg hover:text-danger">
                                                                <x-icon name="trash-2" size="4"/>
                                                            </button>
                                                        @endif

                                                        {{-- Chevron-Toggle entfaellt seit 5-D.6b: Auf-/
                                                             Zuklappen laeuft ueber den Sidebar-Tree. --}}
                                                    </form>
                                                </header>

                                                @can('update', $project)
                                                    <livewire:rich-text-editor
                                                        :model="$entry"
                                                        field="description"
                                                        rules="nullable|string"
                                                        :label="__('entry_description')"
                                                        :key="'entry-description-'.$entry->id"
                                                    />
                                                @else
                                                    <p class="text-body text-ink-700">{!! $entry->description !!}</p>
                                                @endcan
                                            </div>
                                                    @if(isset($entry->mediaContent) && count($entry->mediaContent) > 0)
                                                        <div id="entry_{{$entry->id}}">
                                                            <ul class="list-group  ui-sortable-content sortable_list_content connectedSortableContent" data-entry="{{$entry->id}}" id="{{$entry->id}}" data-reorder-element="content" data-reorder-url="{{ route('chapter.drag') }}">
                                                                @foreach($entry->mediaContent as $item)
                                                                    @if($item->content_type == 'App\Models\Text')
                                                                        @isset($item->text->text)
                                                                            <li class="item text content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <x-ui.block-card type="text" id="anchor_MediaContent_{{$item->id}}" class="mb-4" :save-slot="'Text-'.$item->text->id">
                                                                                    <div>
                                                                                        <div class="text-scrollbar overflow-auto">
                                                                                            @can('update', $project)
                                                                                                <livewire:rich-text-editor
                                                                                                    :model="$item->text"
                                                                                                    field="text"
                                                                                                    rules="nullable|string"
                                                                                                    :label="__('text_content')"
                                                                                                    :key="'text-content-'.$item->text->id" />
                                                                                            @else
                                                                                                <p>{!! html_entity_decode($item->text->text) !!}</p>
                                                                                            @endcan
                                                                                        </div>
                                                                                    </div>
                                                                                    <div class="mt-3 flex items-center justify-end gap-1">
                                                                                        <form action="{{ route('text.delete',$item->text->id) }}"
                                                                                              method="POST" class="mb-5">
                                                                                            @csrf
                                                                                            <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                            @method('DELETE')
                                                                                            <a href="{{route('projects.edit',['project'=> $project, 'log'=> $item->text->id, 'model' => 'Text'])}}" title="{{ __('older_versions') }}" class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900"><x-icon name="rotate-ccw" size="4"/></a>
                                                                                            @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                <x-comment.trigger
                                                                                                    commentableType="App\Models\Text"
                                                                                                    :commentableId="$item->text->id"
                                                                                                    :count="isset($item->text->comments) ? count($item->text->comments) : 0"
                                                                                                />
                                                                                            @endif

 																								@if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                <button type="submit" onclick="return confirm('{{__('message_delete_confirm')}}')" title="{{__('delete_text')}}" class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger"><x-icon name="trash-2" size="4"/></button>
                                                                                            @endif

                                                                                            {{-- Edit-Button für Text entfällt seit Phase 5c.6.c.4:
                                                                                                 Text-Content wird per rich-text-editor-Volt-Komponente
                                                                                                 direkt im Content-Card editiert. Add-Text-Modal
                                                                                                 (contentModal) bleibt für „Text hinzufügen". --}}
                                                                                        </form>
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
                                                                                    </div>
                                                                                </x-ui.block-card>
                                                                            </li>
                                                                        @endisset
                                                                    @endif
                                                                    @if($item->content_type == 'App\Models\Audiovisual')
                                                                        @isset($item->audiovisual->link)
                                                                            <li class="item audiovisual content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
                                                                                <x-ui.block-card :type="$item->audiovisual->type === 'audio' ? 'audio' : 'video'" id="anchor_MediaContent_{{$item->id}}" class="mb-4" :save-slot="'Audiovisual-'.$item->audiovisual->id">
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
                                                                                                {{-- Link/Uploader rendert jetzt der
                                                                                                     audiovisual-player selbst
                                                                                                     (Phase 5c.6.c.3-Fix); der
                                                                                                     Type-Wechsel triggert dort
                                                                                                     den kompletten Re-Render. --}}
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->audiovisual"
                                                                                                    field="type"
                                                                                                    rules="required|in:audio,video"
                                                                                                    :options="['audio' => __('audio'), 'video' => __('video')]"
                                                                                                    :label="__('type')"
                                                                                                    :key="'av-type-'.$item->audiovisual->id" />

                                                                                                {{-- Copyright und Quelle sind
                                                                                                     Metadaten, keine Player-Konfig
                                                                                                     — hinter einem <details>-Toggle
                                                                                                     kollabiert, damit die Editor-
                                                                                                     Sicht ruhig bleibt. Nativ,
                                                                                                     ohne JS, WCAG-freundlich. --}}
                                                                                                <details class="mt-1 rounded-md border border-ink-300/60 bg-canvas-dim/40 px-3 py-2">
                                                                                                    <summary class="cursor-pointer select-none text-caption text-chrome-on-dim focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                        {{ __('metadata') }}
                                                                                                    </summary>
                                                                                                    <div class="mt-2 space-y-2">
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="copyright"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('copyright')"
                                                                                                            :key="'av-copyright-'.$item->audiovisual->id" />
                                                                                                        <livewire:inline-editor
                                                                                                            :model="$item->audiovisual"
                                                                                                            field="source"
                                                                                                            rules="nullable|string|max:255"
                                                                                                            :label="__('origin')"
                                                                                                            :key="'av-source-'.$item->audiovisual->id" />
                                                                                                    </div>
                                                                                                </details>
                                                                                            </div>
                                                                                        @else
                                                                                            <p class="metadata mt-2">
                                                                                                Copyright {!! $item->audiovisual->copyright !!}<br>
                                                                                                Origin {!! $item->audiovisual->source !!}
                                                                                            </p>
                                                                                        @endcan
                                                                                    </div>
                                                                                    <div class="mt-3 flex items-center justify-end gap-1">
                                                                                        <form action="{{ route('audiovisual.delete',$item->audiovisual->id) }}"
                                                                                              method="POST" class="mb-5">
                                                                                            @csrf
                                                                                            <input type="hidden" name="project" value="{!! $project->id !!}"/>
                                                                                            @method('DELETE')
                                                                                           <a href="{{route('projects.edit',['project'=> $project, 'log'=> $item->audiovisual->id, 'model' => 'Audiovisual'])}}" title="{{ __('older_versions') }}" class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900"><x-icon name="rotate-ccw" size="4"/></a>

                                                                                            @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                                <x-comment.trigger
                                                                                                    commentableType="App\Models\Audiovisual"
                                                                                                    :commentableId="$item->audiovisual->id"
                                                                                                    :count="isset($item->audiovisual->comments) ? count($item->audiovisual->comments) : 0"
                                                                                                />
                                                                                            @endif

 																							@if(in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                                                                                                <button type="submit" onclick="return confirm('{{__('message_delete_confirm')}}')" title="{{__('delete_text')}}" class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger"><x-icon name="trash-2" size="4"/></button>
                                                                                            @endif

                                                                                            {{-- Modify-Button für Audiovisual entfällt seit
                                                                                                 Phase 5c.6.c.3: link/type/copyright/source
                                                                                                 werden per inline-editor-Volt-Komponente
                                                                                                 direkt unter dem Player editiert. Add-
                                                                                                 Audiovisual-Modal (audiovisualModal) bleibt
                                                                                                 für neu Anlegen. --}}
                                                                                        </form>
                                                                                        <p class="metadata">
                                                                                            {{ __('saved') }} · {!! date('d.m.Y', strtotime($item->audiovisual->created_at)) !!}
                                                                                        </p>
                                                                                    </div>
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
                                                                                        <a href="{{route('projects.edit',['project'=> $project, 'log'=> $item->gallery->id, 'model' => 'Gallery'])}}"
                                                                                           title="{{ __('older_versions') }}"
                                                                                           class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                                                                                            <x-icon name="rotate-ccw" size="4"/>
                                                                                        </a>

                                                                                        @if(in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                                                                                            <x-comment.trigger
                                                                                                commentableType="App\Models\Gallery"
                                                                                                :commentableId="$item->gallery->id"
                                                                                                :count="isset($item->gallery->comments) ? count($item->gallery->comments) : 0"
                                                                                            />
                                                                                        @endif

                                                                                        @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                                                            {{-- „+ Bilder"-Zugang bleibt in der Aktions-Zeile;
                                                                                                 die vom Briefing empfohlene „+ Bilder"-Zeile
                                                                                                 am Rasterkopf kommt in 5y.4 dazu. --}}
                                                                                            <button type="button"
                                                                                                    class="addImage inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900"
                                                                                                    data-chapter="{{$chapter->name}}"
                                                                                                    data-entry="{{$entry->name}}"
                                                                                                    data-id="{{$item->gallery->id}}"
                                                                                                    data-entryId="{{$entry->id}}"
                                                                                                    data-toggle="modal"
                                                                                                    data-target="#imageModal"
                                                                                                    title="{{__('add_content')}}">
                                                                                                <x-icon name="plus-circle" size="4"/>
                                                                                            </button>
                                                                                        @endif

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
                                                                                        <div class="mt-4">
                                                                                            <x-ui.media-placeholder type="gallery"/>
                                                                                        </div>
                                                                                    @else
                                                                                    {{-- Phase 5y.2 + 5y.3 + 5y.5 + 5y.7: Kachel-Raster ODER Detailzeile.
                                                                                         Alpine-State `editingImageId` schaltet die beiden Ansichten um; die
                                                                                         Detailzeile ersetzt das Raster im selben Block, ohne Modal (Briefing
                                                                                         § 5). Papierkorb-Kaskade aus der alten Meta-Reihe unter der Kachel
                                                                                         entfaellt komplett — Bild entfernen sitzt jetzt als Overlay unten
                                                                                         auf der Kachel (permanent Touch, hover/focus Desktop). --}}
                                                                                    <div x-data="{
                                                                                        editingImageId: null,
                                                                                        pickedId: null,
                                                                                        originalOrder: [],
                                                                                        announcement: '',
                                                                                        reorderUrl: '{{ route("gallery.images.reorder", $item->gallery->id) }}',
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
                                                                                                onEnd: () => this.persistOrder(),
                                                                                            });
                                                                                        },
                                                                                        persistOrder() {
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
                                                                                    }" x-init="initSortable()">
                                                                                    <div
                                                                                        role="status"
                                                                                        aria-live="polite"
                                                                                        class="sr-only"
                                                                                        x-text="announcement"
                                                                                    ></div>

                                                                                        {{-- Raster --}}
                                                                                        <div x-show="editingImageId === null"
                                                                                             x-ref="grid"
                                                                                             class="mt-4 grid gap-[14px]"
                                                                                             style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
                                                                                            @foreach($item->gallery->images as $image)
                                                                                                <div class="gallery_item group relative" id="gallery_items_{{$item->gallery->id}}" data-image-id="{{ $image->id }}">
                                                                                                    <div id="anchor_MediaContent_{{$item->id}}"
                                                                                                         class="relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
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
                                                                                                            <span>{{ $loop->iteration }}</span>
                                                                                                        </span>

                                                                                                        @can('update', $project)
                                                                                                            {{-- Overlay-Aktionen unten: Angaben bearbeiten + Entfernen.
                                                                                                                 Erscheint bei hover ODER focus-within — auf Touch-Geraeten
                                                                                                                 per :focus-within immer, sobald man die Kachel antippt. --}}
                                                                                                            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-gradient-to-t from-ink-900/80 to-transparent px-2 py-1.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100 group-focus-within:opacity-100">
                                                                                                                <button
                                                                                                                    type="button"
                                                                                                                    @click.stop="editingImageId = {{ $image->id }}"
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
                                                                                        </div>

                                                                                        {{-- Detailzeile: ersetzt das Raster fuer eine einzelne Kachel.
                                                                                             Jedes Bild rendert seine eigene, per x-show gefiltert. --}}
                                                                                        @can('update', $project)
                                                                                            @foreach($item->gallery->images as $image)
                                                                                                <div
                                                                                                    x-show="editingImageId === {{ $image->id }}"
                                                                                                    x-cloak
                                                                                                    @keydown.escape.window="editingImageId = null"
                                                                                                    class="mt-4 rounded-md border border-line-200 bg-paper-50 p-4"
                                                                                                >
                                                                                                    <header class="mb-4 flex items-center justify-between gap-3">
                                                                                                        <button type="button"
                                                                                                                @click="editingImageId = null"
                                                                                                                class="inline-flex items-center gap-1 text-body text-primary hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                                                                                            <x-icon name="chevron-left" size="4"/>
                                                                                                            <span>{{ __('gallery_back_to_grid') }}</span>
                                                                                                        </button>
                                                                                                        <span class="text-caption text-ink-500">
                                                                                                            {{ __('gallery_image_n_of_m', ['n' => $loop->iteration, 'm' => $item->gallery->images->count()]) }}
                                                                                                        </span>
                                                                                                        <button type="button"
                                                                                                                @click="editingImageId = null"
                                                                                                                title="{{ __('close') }}"
                                                                                                                class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900">
                                                                                                            <x-icon name="x" size="4"/>
                                                                                                        </button>
                                                                                                    </header>

                                                                                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                                                                                        {{-- Vorschau --}}
                                                                                                        <div>
                                                                                                            <div class="relative flex aspect-video items-center justify-center overflow-hidden rounded-md bg-line-100">
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
                                <ul class="list-group ui-sortable-entry sortable_list_entry connectedSortableEntry" id="{{$chapter->id}}" data-reorder-element="entry" data-reorder-url="{{ route('chapter.drag') }}">
                                    <li>&nbsp;</li>
                                </ul>
                            @endif
                        </div>
                    </div>
                    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                        <div class="mb-4">
                        <button type="button"
                                title="{{__('add_entry')}}"
                                data-chapter="{{$chapter->name}}"
                                data-id="{{$chapter->id}}"
                                data-toggle="modal"
                                data-target="#entryModal"
                                class="addEntry add_entry inline-flex w-full items-center justify-center gap-2 rounded-md
                                       border-2 border-dashed border-line-200 bg-transparent
                                       px-4 py-3 text-body text-ink-500
                                       hover:border-ink-400 hover:bg-line-100/40 hover:text-ink-700">
                            <x-icon name="plus" size="4"/> <span>{{__('new_entry')}}</span>
                        </button>
                        </div>
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

    <x-ui.modal id="previewModal" :title="__('create_html_output')">
        <div class="row m-2">
            <div id="headerComment"></div>
            <div id="listComment"></div>
            <form id="" action="{{route('preview')}}" method="get">
                @csrf
                <input name="project" type="hidden" value="{{$project->id}}">
                <div class="form-check">
                    <input type="color" value="#EDBA0E" class="form-check-input color-element" name="colorAccent">
                    <label class="form-check-label">{{__('color_accent')}}</label>
                </div>
                <div class="form-check">
                    <input type="color" value="#EDBA0E" class="form-check-input color-element" name="colorChapter">
                    <label class="form-check-label" >{{__('color_chapter')}}</label>
                </div>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="backgroundSecond">
                    <label class="form-check-label" >{{__('background_second')}}</label>
                </div>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="collapse">
                    <label class="form-check-label" >{{__('collapse')}}</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="pdf">
                    <label class="form-check-label" >{{__('pdf')}}</label>
                </div>
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary" >{{__('html')}}</button>
                </div>
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
