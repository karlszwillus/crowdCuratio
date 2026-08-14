{{--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('projects.layout')

@section('content')

    @php
        // Statuszählung für die Filter-Chips (Handoff v4 § Screen 01).
        // Wir mappen Status-Strings aus der DB auf drei sichtbare
        // Buckets: veröffentlicht / entwurf / in review. Andere Werte
        // landen im „Alle"-Sammelbecken.
        $data ??= collect();
        $counts = [
            'all' => $data->count(),
            'published' => $data->where('status', 'Published')->count(),
            'draft' => $data->where('status', 'Draft')->count(),
            'review' => $data->where('status', 'In Review')->count(),
        ];

        $statusMeta = [
            'Published' => ['label' => __('status_published'), 'variant' => 'success'],
            'Draft'     => ['label' => __('status_draft'),     'variant' => 'warning'],
            'In Review' => ['label' => __('status_in_review'), 'variant' => 'info'],
        ];
    @endphp

    <div x-data="{ filter: 'all', search: '' }">

        {{-- Screen-Kopf: Titel + Suche + „Neues Projekt". --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('projects') }}
            </h1>

            <div class="flex items-center gap-3">
                <div class="relative w-64">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-500">
                        <x-icon name="search" size="4" />
                    </span>
                    <input
                        type="search"
                        x-model="search"
                        placeholder="{{ __('search_projects') }}"
                        class="block w-full rounded-md border border-line-200 bg-paper-50 py-2 pl-9 pr-3
                               text-body text-ink-900 placeholder:text-ink-500
                               focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                    />
                </div>

                @can('create', App\Models\Project::class)
                    <a
                        href="{{ route('projects.create') }}"
                        class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3.5 py-2
                               text-body font-medium text-primary-on hover:opacity-90
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                    >
                        <x-icon name="plus" size="4" />
                        {{ __('new_project') }}
                    </a>
                @endcan
            </div>
        </div>

        {{-- Filter-Chips mit Zählern. Aktiv: ink-900/paper-0. --}}
        <div class="mb-4 flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __('filter_by_status') }}">
            @foreach ([
                'all'       => __('filter_all'),
                'published' => __('status_published'),
                'draft'     => __('status_draft'),
                'review'    => __('status_in_review'),
            ] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    @click="filter = '{{ $key }}'"
                    :aria-selected="filter === '{{ $key }}'"
                    :class="filter === '{{ $key }}'
                        ? 'bg-ink-900 text-paper-0 border-ink-900'
                        : 'bg-paper-0 text-ink-500 border-line-200 hover:border-ink-400'"
                    class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-body transition-colors
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                >
                    <span>{{ $label }}</span>
                    <span class="text-ink-500" :class="filter === '{{ $key }}' ? 'text-paper-0/70' : ''">
                        · {{ $counts[$key] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Projekt-Tabelle als CSS-Grid.
             Spalten: Projekt (2.4fr) · Status (1fr) · Autor:in (1.3fr)
             · Zuletzt geändert (1.2fr) · Aktionen (0.7fr). --}}
        <div class="overflow-hidden rounded-lg border border-line-200 bg-paper-0">
            {{-- Kopf --}}
            <div class="grid grid-cols-[2.4fr_1fr_1.3fr_1.2fr_0.7fr] gap-4 border-b border-line-200 bg-paper-50 px-5 py-3
                        text-mono-caps font-mono uppercase tracking-widest text-ink-500"
                 role="row"
            >
                <div>{{ __('project') }}</div>
                <div>{{ __('status') }}</div>
                <div>{{ __('author') }}</div>
                <div>{{ __('last_modified') }}</div>
                <div class="sr-only">{{ __('actions') }}</div>
            </div>

            {{-- Zeilen --}}
            @if ($data->isEmpty())
                <div class="px-5 py-8 text-center text-body text-ink-500">
                    {{ __('no_projects_yet') }}
                </div>
            @else
                <ul>
                    @foreach ($data as $project)
                        @php
                            $meta = $statusMeta[$project->status] ?? ['label' => $project->status, 'variant' => 'info'];
                            $badgeClass = [
                                'success' => 'bg-success-bg text-success',
                                'warning' => 'bg-warning-bg text-warning',
                                'info'    => 'bg-info-bg text-info',
                                'danger'  => 'bg-danger-bg text-danger',
                            ][$meta['variant']] ?? 'bg-info-bg text-info';

                            $initials = collect(explode(' ', trim((string) $project->user_name)))
                                ->filter()
                                ->take(2)
                                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                                ->implode('');

                            // Match für Filter (data-status = alle | published | draft | review).
                            $dataStatus = match ($project->status) {
                                'Published' => 'published',
                                'Draft'     => 'draft',
                                'In Review' => 'review',
                                default     => 'other',
                            };

                            $chapterCount = $project->chapters_count ?? 0;
                        @endphp

                        <li
                            x-show="(filter === 'all' || filter === '{{ $dataStatus }}')
                                    && (search === '' || '{{ addslashes($project->name) }}'.toLowerCase().includes(search.toLowerCase()))"
                            class="grid grid-cols-[2.4fr_1fr_1.3fr_1.2fr_0.7fr] items-center gap-4 border-b border-line-100 px-5 py-3.5
                                   last:border-b-0 hover:bg-paper-50"
                            role="row"
                        >
                            {{-- Projekt (Thumbnail + Titel + Untertitel) --}}
                            <a href="{{ route('projects.edit', $project->id) }}"
                               class="flex items-center gap-3 text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar rounded-md"
                            >
                                {{-- Projekt-Thumbnail: der Bestand liefert Bilder
                                     ueber die `image`-Route (Image-Controller-
                                     Endpoint), nicht direkt aus dem storage-Path. --}}
                                <span
                                    class="h-8 w-11 shrink-0 rounded bg-line-100 bg-cover bg-center"
                                    @if ($project->logo)
                                        style="background-image: url('{{ route('image', $project->logo) }}')"
                                    @endif
                                    aria-hidden="true"
                                ></span>
                                <span class="min-w-0">
                                    <span class="block truncate text-body font-medium text-ink-900">
                                        {{ $project->name }}
                                    </span>
                                    <span class="block truncate text-caption text-ink-500">
                                        {{ trans_choice('n_chapters', $chapterCount, ['count' => $chapterCount]) }}
                                    </span>
                                </span>
                            </a>

                            {{-- Status-Badge --}}
                            <div>
                                <span class="{{ $badgeClass }} inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-caption font-medium">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $meta['label'] }}
                                </span>
                            </div>

                            {{-- Autor:in --}}
                            <div class="flex items-center gap-2 text-body text-ink-700">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-line-200 text-caption font-semibold text-ink-700">
                                    {{ $initials }}
                                </span>
                                <span class="truncate">{{ $project->user_name }}</span>
                            </div>

                            {{-- Zuletzt geändert --}}
                            <div class="text-body text-ink-600">
                                {{ $project->updated_at ? $project->updated_at->diffForHumans() : '—' }}
                            </div>

                            {{-- Aktionen --}}
                            <form action="{{ route('projects.destroy', $project->id) }}" method="POST"
                                  class="flex items-center justify-end gap-2"
                                  onsubmit="return confirm('{{ __('message_delete_confirm') }}')"
                            >
                                @csrf
                                @if(Auth::user()->can('publish', $project) || Auth::user()->can('preview'))
                                    <a
                                        href="#"
                                        data-project="{{ $project->id }}"
                                        data-toggle="modal"
                                        data-target="#previewModal"
                                        class="preview inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900
                                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                                        title="{{ __('preview') }}"
                                    >
                                        <x-icon name="globe" size="4" :decorative="false" :label="__('preview')"/>
                                    </a>
                                @endif
                                <a
                                    href="{{ route('projects.edit', $project->id) }}"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-line-100 hover:text-ink-900
                                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                                    title="{{ __('edit_project') }}"
                                >
                                    <x-icon name="pencil" size="4" :decorative="false" :label="__('edit_project')"/>
                                </a>
                                @if(Auth::user()->can('update', $project) || Auth::user()->can('edit'))
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex size-8 items-center justify-center rounded-md text-ink-500 hover:bg-danger-bg hover:text-danger
                                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                                        title="{{ __('delete_project') }}"
                                    >
                                        <x-icon name="trash-2" size="4" :decorative="false" :label="__('delete_project')"/>
                                    </button>
                                @endif
                            </form>
                        </li>
                    @endforeach
                </ul>

                {{-- Fußzeile --}}
                <div class="flex items-center justify-between border-t border-line-200 bg-paper-50 px-5 py-3
                            text-caption text-ink-500"
                >
                    <span>{{ trans_choice('n_projects_total', $data->count(), ['count' => $data->count()]) }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Preview-Modal bleibt bestehen — Preview-Rendering ist noch nicht Teil der Design-Refaktorierung. --}}
    <x-ui.modal id="previewModal" :title="__('add_new_element_comment')">
        <div class="row m-2">
            <div id="headerComment"></div>
            <hr style="width:100%;text-align:left;margin-left:0">
            <div id="listComment"></div>
            <form id="frm_preview" action="{{route('preview')}}" method="get">
                @csrf
                <input type="hidden" name="project" id="project">
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
@endsection

@push('scripts')
    <script type="text/javascript">
        // Preview-Modal-Trigger: setzt die Project-ID im Formular.
        $('.preview').click(function () {
            $('#project').val($(this).attr('data-project'));
        });
    </script>
@endpush
