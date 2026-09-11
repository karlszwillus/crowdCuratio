@props(['item', 'entry', 'project', 'listPermissions'])

{{--
    Q4-Etappe 4 / G3 (2026-09-08): Daten-und-Fakten-Block-Rendering.
    Analog zu quote-block. Titel + Untertitel via inline-editor
    (translatable), Zeilen über die data-facts-rows-editor-Volt-
    Komponente.

    Props:
    - $item             MediaContent-Row aus $entry->mediaContent
    - $entry            Entry-Model (parent)
    - $project          Project-Model (root; fuer @can-Gates)
    - $listPermissions  array<string> (Rechte des eingeloggten Users)
--}}

@isset($item->dataFactBlock)
    <li class="item data-facts content"
        data-content="{{ $item->id }}"
        data-entry="{{ $entry->id }}"
        data-history-subject="DataFactBlock:{{ $item->dataFactBlock->id }}"
        id="{{ $item->id }}"
        @can('update', $project)
            tabindex="0"
            aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
            title="{{ __('reorder_hint') }}"
        @endcan>
        <x-ui.block-card type="data-facts"
                         id="anchor_MediaContent_{{ $item->id }}"
                         class="mb-4">
            <x-slot:actions>
                <x-ui.history-trigger
                    subjectType="DataFactBlock"
                    :subjectId="$item->dataFactBlock->id"
                />
                @if (in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                    <x-comment.trigger
                        commentableType="App\Models\DataFactBlock"
                        :commentableId="$item->dataFactBlock->id"
                        :count="isset($item->dataFactBlock->comments) ? count($item->dataFactBlock->comments) : 0"
                    />
                @endif
                @if (in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('data-facts.delete', $item->dataFactBlock->id) }}"
                          method="POST" class="inline-flex">
                        @csrf
                        <input type="hidden" name="project" value="{!! $project->id !!}"/>
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('{{ __('message_delete_confirm') }}')"
                                title="{{ __('delete_block') }}"
                                class="inline-flex size-11 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger">
                            <x-icon name="trash-2" size="4"/>
                        </button>
                    </form>
                @endif
            </x-slot:actions>

            {{-- Titel + Untertitel (translatable). Placeholder-Pattern
                 analog Gallery/Kapitel — kein sichtbares Label. --}}
            <div class="space-y-1">
                <div data-history-field="title">
                    @can('update', $project)
                        <livewire:inline-editor
                            :model="$item->dataFactBlock"
                            field="title"
                            rules="nullable|string|max:255"
                            :label="__('data_facts_title')"
                            :placeholder="__('data_facts_title_placeholder')"
                            :variant="'heading'"
                            :key="'data-facts-title-'.$item->dataFactBlock->id" />
                    @else
                        @isset($item->dataFactBlock->title)
                            <h2 class="text-heading font-semibold text-ink-900">{{ $item->dataFactBlock->title }}</h2>
                        @endisset
                    @endcan
                </div>
                <div data-history-field="subtitle">
                    @can('update', $project)
                        <livewire:inline-editor
                            :model="$item->dataFactBlock"
                            field="subtitle"
                            rules="nullable|string|max:255"
                            :label="__('data_facts_subtitle')"
                            :placeholder="__('data_facts_subtitle_placeholder')"
                            :variant="'subtitle'"
                            :key="'data-facts-subtitle-'.$item->dataFactBlock->id" />
                    @else
                        @isset($item->dataFactBlock->subtitle)
                            <p class="text-body text-ink-500">{{ $item->dataFactBlock->subtitle }}</p>
                        @endisset
                    @endcan
                </div>
            </div>

            {{-- Optionale Einleitung (Rich-Text, translatable). Erscheint
                 im Reader über dem Steckbrief/der Tabelle. --}}
            <div class="mt-3" data-history-field="description">
                @can('update', $project)
                    <livewire:rich-text-editor
                        :model="$item->dataFactBlock"
                        field="description"
                        rules="nullable|string"
                        :label="__('data_facts_description')"
                        :placeholder="__('data_facts_description_placeholder')"
                        :key="'data-facts-description-'.$item->dataFactBlock->id" />
                @else
                    @if (! empty(trim(strip_tags((string) $item->dataFactBlock->description))))
                        <div class="text-body text-ink-700">{!! $item->dataFactBlock->description !!}</div>
                    @endif
                @endcan
            </div>

            {{-- Layout-Umschalter (Steckbrief vs. Tabelle). Steht
                 vor dem Zeilen-Editor, weil die Wahl das darunter
                 sichtbare Formular bestimmt. --}}
            @can('update', $project)
                <div class="mt-4 border-t border-line-100 pt-3">
                    <livewire:data-facts-layout-selector
                        :block-id="$item->dataFactBlock->id"
                        :value="$item->dataFactBlock->layout ?? 'steckbrief'"
                        :key="'data-facts-layout-'.$item->dataFactBlock->id" />
                </div>
            @endcan

            {{-- Zeilen — Editor je Layout, Read-Only DL/Table je Layout.
                 Im Edit-Modus sind BEIDE Editor-Volts im DOM; Alpine
                 x-show entscheidet, welcher sichtbar ist. Der Layout-
                 Selector oben dispatched `data-facts-layout-changed`,
                 das Alpine hier fängt — Umschalten passiert instant. --}}
            <div class="mt-4"
                 data-history-field="rows"
                 x-data="{ layout: '{{ $item->dataFactBlock->layout ?? 'steckbrief' }}' }"
                 @data-facts-layout-changed.window="if ($event.detail.blockId === {{ $item->dataFactBlock->id }}) { layout = $event.detail.layout }">
                @can('update', $project)
                    <div x-show="layout === 'tabelle'" x-cloak>
                        <livewire:data-facts-table-editor
                            :block-id="$item->dataFactBlock->id"
                            :key="'data-facts-table-'.$item->dataFactBlock->id" />
                    </div>
                    <div x-show="layout === 'steckbrief'" x-cloak>
                        <livewire:data-facts-rows-editor
                            :block-id="$item->dataFactBlock->id"
                            :key="'data-facts-rows-'.$item->dataFactBlock->id" />
                    </div>
                @else
                    @php $locale = app()->getLocale(); @endphp
                    @if ($item->dataFactBlock->isTabellenLayout())
                        @php
                            $columns = $item->dataFactBlock->columns ?? [];
                            $rows = $item->dataFactBlock->rows ?? [];
                        @endphp
                        @if (! empty($columns) && ! empty($rows))
                            <table class="min-w-full divide-y divide-line-100">
                                <thead>
                                    <tr>
                                        @foreach ($columns as $col)
                                            <th class="px-2 py-1 text-left text-caption font-medium text-ink-700">
                                                {{ is_array($col['header'] ?? null) ? ($col['header'][$locale] ?? $col['header']['de'] ?? '') : (string) ($col['header'] ?? '') }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line-100">
                                    @foreach ($rows as $row)
                                        <tr>
                                            @php $cells = $row['cells'] ?? []; @endphp
                                            @foreach ($columns as $ci => $col)
                                                @php $cell = $cells[$ci] ?? ''; @endphp
                                                <td class="px-2 py-1 text-body text-ink-900">
                                                    {{ is_array($cell) ? ($cell[$locale] ?? $cell['de'] ?? '') : (string) $cell }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @else
                        @php $rows = $item->dataFactBlock->rows ?? []; @endphp
                        @if (! empty($rows))
                            <dl class="grid grid-cols-1 gap-y-1 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] md:gap-x-4">
                                @foreach ($rows as $row)
                                    @php
                                        $label = is_array($row['label'] ?? null) ? ($row['label'][$locale] ?? $row['label']['de'] ?? '') : (string) ($row['label'] ?? '');
                                        $value = is_array($row['value'] ?? null) ? ($row['value'][$locale] ?? $row['value']['de'] ?? '') : (string) ($row['value'] ?? '');
                                    @endphp
                                    <dt class="text-caption font-medium text-ink-700">{{ $label }}</dt>
                                    <dd class="text-body text-ink-900">{{ $value }}</dd>
                                @endforeach
                            </dl>
                        @endif
                    @endif
                @endcan
            </div>

            {{-- Karl 2026-09-11 (E7-6): kein pro-Block-Speicherstand
                 mehr — Chrome-Bar zentralisiert das. --}}
        </x-ui.block-card>
    </li>
@endisset
