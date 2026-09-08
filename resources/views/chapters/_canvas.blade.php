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
                                                                {{-- Q4-Etappe 4 / C1b (2026-09-08): Add-Bar vor dem
                                                                     ersten Content-Block. Sortable ignoriert diese
                                                                     Slot-<li>s über die `items: '> li[data-content]'`-
                                                                     Restriktion in chapters/index.blade.php. --}}
                                                                @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                                    <li class="content-add-bar-slot">
                                                                        <livewire:content-add-bar
                                                                            :entry-id="$entry->id"
                                                                            :after-media-content-id="null"
                                                                            variant="between"
                                                                            :entry-name="$entry->name"
                                                                            :position="1"
                                                                            :key="'add-bar-'.$entry->id.'-top'"
                                                                        />
                                                                    </li>
                                                                @endif
                                                                @foreach($entry->mediaContent as $item)
                                                                    @if ($item->content_type == 'App\Models\Text')
                                                                        <x-content.text-block :item="$item" :entry="$entry" :project="$project" :list-permissions="$listPermissions"/>
                                                                    @endif
                                                                    @if ($item->content_type == 'App\Models\Audiovisual')
                                                                        <x-content.audiovisual-block :item="$item" :entry="$entry" :project="$project" :list-permissions="$listPermissions"/>
                                                                    @endif
                                                                    {{-- Phase 4 / E.7b 4a: alte Spalte hatte historisch
                                                                         'App\Models\Image' für Galleries; neue content_type
                                                                         hat 'App\Models\Gallery' (ADR-0022). --}}
                                                                    @if (isset($item) && $item->content_type == 'App\Models\Gallery')
                                                                        <x-content.gallery-block :item="$item" :entry="$entry" :chapter="$chapter" :project="$project" :list-permissions="$listPermissions"/>
                                                                    @endif
                                                                    {{-- Q4-Etappe 4 / F4 (2026-09-08): Zitat-Block. --}}
                                                                    @if (isset($item) && $item->content_type == 'App\Models\QuoteBlock')
                                                                        <x-content.quote-block :item="$item" :entry="$entry" :project="$project" :list-permissions="$listPermissions"/>
                                                                    @endif
                                                                    {{-- Q4-Etappe 4 / G1 (2026-09-08): Daten-und-Fakten-Block. --}}
                                                                    @if (isset($item) && $item->content_type == 'App\Models\DataFactBlock')
                                                                        <x-content.data-facts-block :item="$item" :entry="$entry" :project="$project" :list-permissions="$listPermissions"/>
                                                                    @endif
                                                                    @if(in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                                        <li class="content-add-bar-slot">
                                                                            <livewire:content-add-bar
                                                                                :entry-id="$entry->id"
                                                                                :after-media-content-id="$item->id"
                                                                                variant="between"
                                                                                :entry-name="$entry->name"
                                                                                :position="$loop->iteration + 1"
                                                                                :key="'add-bar-'.$entry->id.'-after-'.$item->id"
                                                                            />
                                                                        </li>
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
                                            {{-- Q4-Etappe 4 / C1d (2026-09-08): Bei nicht-leerem Entry
                                                 stehen die Zwischen-Trenner (variant="between") schon
                                                 nach jedem Block, inklusive nach dem letzten — die
                                                 empty-Bar wäre doppelt. Nur bei leerem Entry (kein
                                                 Content) rendert die empty-Bar als dominante
                                                 Anlege-Fläche. --}}
                                            @if((in_array('add', $listPermissions) || Auth::user()->can('update', $project))
                                                && (! isset($entry->mediaContent) || count($entry->mediaContent) === 0))
                                                <div class="mb-4">
                                                    <livewire:content-add-bar
                                                        :entry-id="$entry->id"
                                                        variant="empty"
                                                        :entry-name="$entry->name"
                                                        :position="1"
                                                        :key="'add-bar-empty-'.$entry->id"
                                                    />
                                                </div>
                                            @endif
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
