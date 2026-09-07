@props(['item', 'entry', 'project', 'listPermissions'])

{{--
    Q4-Etappe 2 / I5 (2026-08-27): Text-Block-Rendering aus
    resources/views/chapters/_canvas.blade.php extrahiert (~120 LoC).
    Wire-/Livewire-Kette bleibt intakt, weil die Sub-Component im DOM
    des Editor-Root sitzt und Route-Namen unveraendert sind.

    Props:
    - $item             MediaContent-Row aus $entry->mediaContent
    - $entry            Entry-Model (parent)
    - $project          Project-Model (root; fuer @can-Gates)
    - $listPermissions  array<string> (Rechte des eingeloggten Users)
--}}

@isset($item->text->text)
    <li class="item text content"
        data-content="{{ $item->id }}"
        data-entry="{{ $entry->id }}"
        data-history-subject="Text:{{ $item->text->id }}"
        id="{{ $item->id }}"
        @can('update', $project)
            tabindex="0"
            aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
            title="{{ __('reorder_hint') }}"
        @endcan>
        <x-ui.block-card type="text"
                         id="anchor_MediaContent_{{ $item->id }}"
                         class="mb-4"
                         :save-slot="'Text-'.$item->text->id">
            <x-slot:actions>
                {{-- Design v6 § 4 (in 5e-Vokabular): Text-Block-Aktionen
                     wandern aus der Fusszeile in den Blockkopf, analog zu
                     Galerie und Audio/Video. --}}
                <x-ui.history-trigger
                    subjectType="Text"
                    :subjectId="$item->text->id"
                />
                @if (in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                    <x-comment.trigger
                        commentableType="App\Models\Text"
                        :commentableId="$item->text->id"
                        :count="isset($item->text->comments) ? count($item->text->comments) : 0"
                    />
                @endif
                @if (in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('text.delete', $item->text->id) }}"
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
            {{-- Copyright + Quelle sind Pflichtfelder und sitzen sichtbar
                 am Fuss der Block-Card (P1.4 aus Designer-Review:
                 eingeklappt darf nur Optionales sein). Sternchen und
                 aria-required kommen ueber das rules-Prop des
                 source-picker. --}}
            <div class="mt-4 border-t border-line-100 pt-3">
                @can('update', $project)
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div data-history-field="copyright">
                            <label class="mb-1 block text-caption font-medium text-ink-700">
                                {{ __('copyright') }} @if ($project->requiresSources())<span class="text-danger" aria-hidden="true">*</span>@endif
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
                                {{ __('origin') }} @if ($project->requiresSources())<span class="text-danger" aria-hidden="true">*</span>@endif
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

            {{-- 5z.10 § 8.3: Einheitliche Fusszeile — Vollstaendigkeit links,
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
