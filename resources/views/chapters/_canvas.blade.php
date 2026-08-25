{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

I1 (2026-08-21): Content-Canvas aus chapters/index.blade.php
extrahiert. Chapter-Loop mit Chapter-Kopf, Entry-Rendering und
Content-Block-Karten. Editor-Chrome (Breadcrumb, Tabs, Publish,
⋮-Menu) bleibt in `chapters/index.blade.php`, die Modals hängen
ebenfalls dort. `chapters/index` liefert per @include diese Sicht.

Erwartete Variablen (aus dem @section('main')-Kontext):
- $data          — das Projekt-Model mit Chapter-Beziehung
- $project       — Alias auf $data
- $listPermissions — Array-Liste der Berechtigungen des Users im Projekt
- $permissions   — Detail-Berechtigungen
- $roleHint      — nullable String, wird ausserhalb (vor dem @include) gerendert
--}}

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
                                                                                                    <div data-history-field="copyright">
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
                                                                                                    <div data-history-field="origin">
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
                                                                                                    <div data-history-field="copyright">
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
                                                                                                    <div data-history-field="source">
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
                                                                            <li class="item gallery content" data-content="{{$item->id}}" data-entry="{{$entry->id}}" data-history-subject="Gallery:{{$item->gallery->id}}" id="{{$item->id}}" @can('update', $project) tabindex="0" aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown" title="{{ __('reorder_hint') }}" @endcan>
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
                                                                                            {{-- A7 (2026-08-21): data-history-field-Wrapper
                                                                                                 damit history-diff.js die Wort-Diffs an
                                                                                                 die richtigen Gallery-Felder haengen kann. --}}
                                                                                            <div data-history-field="title">
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="title"
                                                                                                    rules="nullable|string|max:255"
                                                                                                    :label="__('title')"
                                                                                                    :variant="'heading'"
                                                                                                    :key="'gallery-title-'.$item->gallery->id"
                                                                                                />
                                                                                            </div>
                                                                                            <div data-history-field="subtitle">
                                                                                                <livewire:inline-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="subtitle"
                                                                                                    rules="nullable|string|max:255"
                                                                                                    :variant="'subtitle'"
                                                                                                    :key="'gallery-subtitle-'.$item->gallery->id"
                                                                                                />
                                                                                            </div>
                                                                                            {{-- Phase 5y.1: der Rich-Text-Editor bleibt
                                                                                                 fuer Editor:innen dauerhaft anzeigbar
                                                                                                 (er ist die Bearbeitungsflaeche); dort
                                                                                                 verursacht seine Mindesthoehe keine
                                                                                                 Leerflaeche mehr, weil die Aktionen jetzt
                                                                                                 im Kopf liegen und nicht mehr darunter. --}}
                                                                                            <div data-history-field="description">
                                                                                                <livewire:rich-text-editor
                                                                                                    :model="$item->gallery"
                                                                                                    field="description"
                                                                                                    rules="nullable|string"
                                                                                                    :label="__('gallery_description')"
                                                                                                    :key="'gallery-description-'.$item->gallery->id"
                                                                                                />
                                                                                            </div>
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
                                                                                                    data-history-subject="Image:{{ $image->id }}"
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
                                                                                                            <div data-history-field="alt">
                                                                                                                <label class="mb-1 block text-caption font-medium text-ink-700">{{ __('title') }}</label>
                                                                                                                <livewire:inline-editor
                                                                                                                    :model="$image"
                                                                                                                    field="alt"
                                                                                                                    rules="nullable|string|max:255"
                                                                                                                    :key="'image-detail-alt-'.$image->id"
                                                                                                                />
                                                                                                                <p class="mt-1 text-caption text-ink-500">{{ __('gallery_field_title_hint') }}</p>
                                                                                                            </div>
                                                                                                            <div data-history-field="description">
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
                                                                                                            <div data-history-field="copyright">
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
                                                                                                            <div data-history-field="origin">
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
                                                onclick="window.dispatchEvent(new CustomEvent('entry-modal:open', { detail: { chapterId: {{ (int) $chapter->id }}, chapterName: @js((string) $chapter->name) } }))"
                                                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
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
                                        onclick="window.dispatchEvent(new CustomEvent('entry-modal:open', { detail: { chapterId: {{ (int) $chapter->id }}, chapterName: @js((string) $chapter->name) } }))"
                                        class="add_entry inline-flex w-full items-center justify-center gap-2 rounded-md
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
