@props(['item', 'entry', 'project', 'listPermissions'])

{{--
    Q4-Etappe 2 / I5 (2026-08-27): Audiovisual-Block-Rendering aus
    resources/views/chapters/_canvas.blade.php extrahiert (~125 LoC).
--}}

@isset($item->audiovisual->link)
    <li class="item audiovisual content"
        data-content="{{ $item->id }}"
        data-entry="{{ $entry->id }}"
        data-history-subject="Audiovisual:{{ $item->audiovisual->id }}"
        id="{{ $item->id }}"
        @can('update', $project)
            tabindex="0"
            aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
            title="{{ __('reorder_hint') }}"
        @endcan>
        <x-ui.block-card :type="$item->audiovisual->type === 'audio' ? 'audio' : 'video'"
                         id="anchor_MediaContent_{{ $item->id }}"
                         class="mb-4"
                         :save-slot="'Audiovisual-'.$item->audiovisual->id">
            <x-slot:actions>
                <x-ui.history-trigger
                    subjectType="Audiovisual"
                    :subjectId="$item->audiovisual->id"
                />
                @if (in_array('comment', $listPermissions) || Auth::user()->can('update', $project))
                    <x-comment.trigger
                        commentableType="App\Models\Audiovisual"
                        :commentableId="$item->audiovisual->id"
                        :count="isset($item->audiovisual->comments) ? count($item->audiovisual->comments) : 0"
                    />
                @endif
                @if (in_array('delete', $listPermissions) || Auth::user()->can('delete', $project))
                    <form action="{{ route('audiovisual.delete', $item->audiovisual->id) }}"
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
                {{-- Player als eigenstaendige Volt-Komponente (Phase 5c.6.c.3).
                     Rendert audio/iframe und aktualisiert sich beim Speichern
                     eines Inline-Editor-Felds via `saved` Event. --}}
                <livewire:audiovisual-player
                    :audiovisual="$item->audiovisual"
                    :key="'av-player-'.$item->audiovisual->id" />

                @can('update', $project)
                    <div class="mt-3 space-y-2">
                        {{-- Design v6 § 8.1: Blocktyp-Select aus dem Body raus —
                             der Typ steht schon im Chip. Umwandeln kommt spaeter
                             als eigener Menu-Eintrag im ⋯-Menu zurueck; bis dahin
                             ist der Typ nach Anlage fest. --}}

                        {{-- 5z.9: Transkript-Feld fuer Audio + Video, weiche Pflicht
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

                        {{-- Q4-Etappe 3 / C0-8a Erweiterung (2026-09-07):
                             Copyright und Quelle laufen ab jetzt ueber
                             die projekt-scopeden Source-Rows, analog
                             Text- und Image-Block. Legacy-Strings
                             (`copyright`/`source`) werden bis zum
                             Backfill mitgeschrieben, Reader liest
                             bevorzugt aus copyrightSource/originSource. --}}
                        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div data-history-field="copyright">
                                <label class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('copyright') }} <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <livewire:source-picker
                                    :model="$item->audiovisual"
                                    field="copyright_id"
                                    relation="copyrightSource"
                                    source-type="Copyright"
                                    :label="__('copyright')"
                                    :key="'av-copyright-'.$item->audiovisual->id" />
                            </div>
                            <div data-history-field="source">
                                <label class="mb-1 block text-caption font-medium text-ink-700">
                                    {{ __('origin') }} <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <livewire:source-picker
                                    :model="$item->audiovisual"
                                    field="origin_id"
                                    relation="originSource"
                                    source-type="Origin"
                                    :label="__('origin')"
                                    :key="'av-origin-'.$item->audiovisual->id" />
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Q4-Etappe 3 / C0-8a Erweiterung: Reader liest
                         bevorzugt aus der FK-basierten Source-Relation,
                         faellt auf die Legacy-Strings zurueck, bis der
                         Backfill den Bestand nachgezogen hat. --}}
                    <p class="metadata mt-2">
                        Copyright {!! $item->audiovisual->copyrightSource?->name ?? $item->audiovisual->copyright !!}<br>
                        Origin {!! $item->audiovisual->originSource?->name ?? $item->audiovisual->source !!}
                    </p>
                @endcan
            </div>
            {{-- 5y.11: Angaben-Status analog Gallery — Copyright und Quelle als weiche Pflichtfelder. --}}
            @can('update', $project)
                @php
                    $avMissing = collect([
                        // Q4-Etappe 3 / C0-8a: fehlend, wenn weder
                        // FK noch Legacy-String gesetzt sind.
                        ($item->audiovisual->copyright_id === null
                            && empty(trim(strip_tags((string) $item->audiovisual->copyright)))) ? __('copyright') : null,
                        ($item->audiovisual->origin_id === null
                            && empty(trim(strip_tags((string) $item->audiovisual->source)))) ? __('origin') : null,
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
