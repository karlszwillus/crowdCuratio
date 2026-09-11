@props(['item', 'entry', 'project', 'listPermissions'])

{{--
    Q4-Etappe 4 / F3 (2026-09-08): Zitat-Block-Rendering. Analog zu
    text-block, aber mit Zitat-spezifischen Feldern (speaker,
    date_text, kind, locator, optional lang_original + text_original)
    und einer Quelle statt Copyright/Origin-Paar.

    Props:
    - $item             MediaContent-Row aus $entry->mediaContent
    - $entry            Entry-Model (parent)
    - $project          Project-Model (root; fuer @can-Gates)
    - $listPermissions  array<string> (Rechte des eingeloggten Users)
--}}

@isset($item->quoteBlock)
    <li class="item quote content"
        data-content="{{ $item->id }}"
        data-entry="{{ $entry->id }}"
        data-history-subject="QuoteBlock:{{ $item->quoteBlock->id }}"
        id="{{ $item->id }}"
        @can('update', $project)
            tabindex="0"
            aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
            title="{{ __('reorder_hint') }}"
        @endcan>
        <x-ui.block-card type="quote"
                         id="anchor_MediaContent_{{ $item->id }}"
                         class="mb-4">
            <x-slot:actions>
                <x-ui.history-trigger
                    subjectType="QuoteBlock"
                    :subjectId="$item->quoteBlock->id"
                />
                @if (in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                    <x-comment.trigger
                        commentableType="App\Models\QuoteBlock"
                        :commentableId="$item->quoteBlock->id"
                        :count="isset($item->quoteBlock->comments) ? count($item->quoteBlock->comments) : 0"
                    />
                @endif
                @if (in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('quote.delete', $item->quoteBlock->id) }}"
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

            {{-- Zitat-Text als Rich-Text (analog Text-Block). --}}
            <div class="text-scrollbar overflow-auto">
                @can('update', $project)
                    <div data-history-field="text">
                        <livewire:rich-text-editor
                            :model="$item->quoteBlock"
                            field="text"
                            rules="nullable|string"
                            :label="__('quote_text')"
                            :placeholder="__('quote_text_placeholder')"
                            :key="'quote-text-'.$item->quoteBlock->id" />
                    </div>
                @else
                    <blockquote data-history-field="text" class="border-l-2 border-primary/40 pl-3 italic">
                        {!! html_entity_decode((string) $item->quoteBlock->text) !!}
                    </blockquote>
                @endcan
            </div>

            {{-- Sprecher · Datum · Kind — in einer Zeile, jede
                 Zelle inline-editierbar. --}}
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div data-history-field="speaker">
                    <label class="mb-1 block text-caption font-medium text-ink-700">
                        {{ __('quote_speaker') }}
                    </label>
                    @can('update', $project)
                        <livewire:inline-editor
                            :model="$item->quoteBlock"
                            field="speaker"
                            rules="nullable|string|max:255"
                            :label="__('quote_speaker')"
                            :key="'quote-speaker-'.$item->quoteBlock->id" />
                    @else
                        <p class="text-body">{{ $item->quoteBlock->speaker }}</p>
                    @endcan
                </div>
                <div data-history-field="date_text">
                    <label class="mb-1 block text-caption font-medium text-ink-700">
                        {{ __('quote_date_text') }}
                    </label>
                    @can('update', $project)
                        <livewire:inline-editor
                            :model="$item->quoteBlock"
                            field="date_text"
                            rules="nullable|string|max:255"
                            :label="__('quote_date_text')"
                            :key="'quote-date-'.$item->quoteBlock->id" />
                    @else
                        <p class="text-body">{{ $item->quoteBlock->date_text }}</p>
                    @endcan
                </div>
                <div data-history-field="kind">
                    <label class="mb-1 block text-caption font-medium text-ink-700">
                        {{ __('quote_kind') }}
                    </label>
                    @can('update', $project)
                        <livewire:quote-kind-selector
                            :quote-id="$item->quoteBlock->id"
                            :value="$item->quoteBlock->kind"
                            :key="'quote-kind-'.$item->quoteBlock->id"
                        />
                    @else
                        <p class="text-body">{{ $item->quoteBlock->kind ? __('quote_kind_'.$item->quoteBlock->kind) : '—' }}</p>
                    @endcan
                </div>
            </div>

            {{-- Quelle + Fundstelle. --}}
            <div class="mt-4 border-t border-line-100 pt-3">
                @can('update', $project)
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-[2fr_1fr]">
                        <div data-history-field="source_id">
                            <label class="mb-1 block text-caption font-medium text-ink-700">
                                {{ __('quote_source') }} @if ($project->requiresSources())<span class="text-danger" aria-hidden="true">*</span>@endif
                            </label>
                            <livewire:source-picker
                                :model="$item->quoteBlock"
                                field="source_id"
                                relation="source"
                                source-type="Origin"
                                :label="__('quote_source')"
                                :key="'quote-source-'.$item->quoteBlock->id" />
                        </div>
                        <div data-history-field="locator">
                            <label class="mb-1 block text-caption font-medium text-ink-700">
                                {{ __('quote_locator') }}
                            </label>
                            <livewire:inline-editor
                                :model="$item->quoteBlock"
                                field="locator"
                                rules="nullable|string|max:255"
                                :label="__('quote_locator')"
                                :key="'quote-locator-'.$item->quoteBlock->id" />
                        </div>
                    </div>
                @else
                    <div class="text-caption text-ink-500">
                        {{ __('quote_source') }}: {!! $item->quoteBlock->source?->name !!}
                        @if (! empty($item->quoteBlock->locator))
                            · {{ $item->quoteBlock->locator }}
                        @endif
                    </div>
                @endcan
            </div>

            {{-- Original-Sprache + Original-Text, aufklappbar. Nur
                 gefüllt wenn das Zitat übersetzt wurde. --}}
            @can('update', $project)
                <details class="mt-4 border-t border-line-100 pt-3">
                    <summary class="cursor-pointer text-caption font-medium text-ink-700">
                        {{ __('quote_original_toggle') }}
                    </summary>
                    <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-[1fr_3fr]">
                        <div data-history-field="lang_original">
                            <label class="mb-1 block text-caption font-medium text-ink-700">
                                {{ __('quote_lang_original') }}
                            </label>
                            <livewire:inline-editor
                                :model="$item->quoteBlock"
                                field="lang_original"
                                rules="nullable|string|max:8"
                                :label="__('quote_lang_original')"
                                :key="'quote-lang-'.$item->quoteBlock->id" />
                        </div>
                        <div data-history-field="text_original">
                            <label class="mb-1 block text-caption font-medium text-ink-700">
                                {{ __('quote_text_original') }}
                            </label>
                            <livewire:inline-editor
                                :model="$item->quoteBlock"
                                field="text_original"
                                rules="nullable|string"
                                :label="__('quote_text_original')"
                                :key="'quote-text-original-'.$item->quoteBlock->id" />
                        </div>
                    </div>
                </details>
            @endcan

            {{-- Fußzeile: nur Vollstaendigkeit. Karl 2026-09-11 (E7-6):
                 Speicherstand pro Block entfaellt; die Chrome-Bar
                 zeigt den Save-Status zentral. --}}
            @can('update', $project)
                @php
                    $missing = collect([
                        empty(trim(strip_tags((string) $item->quoteBlock->text))) ? __('quote_text') : null,
                        empty($item->quoteBlock->speaker) ? __('quote_speaker') : null,
                        $project->requiresSources() && ! $item->quoteBlock->source ? __('quote_source') : null,
                    ])->filter()->values();
                @endphp
                <div class="mt-3">
                    @if ($missing->isEmpty())
                        <p class="text-caption text-success">✓ {{ __('gallery_status_complete') }}</p>
                    @else
                        <p class="text-caption text-ink-500">{{ __('gallery_status_missing', ['fields' => $missing->implode(', ')]) }}</p>
                    @endif
                </div>
            @endcan
        </x-ui.block-card>
    </li>
@endisset
