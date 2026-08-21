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
    @include('chapters._canvas')
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
    @include('entries._add-modal')
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

        // Submit entry — mit I1 (2026-08-21) entfaellt: der Alpine-
        // Modal in entries/_add-modal.blade.php extrahiert den Quill-
        // Content im eigenen @submit-Handler in ein hidden Feld.

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


        // Add Entry — I1 (2026-08-21): Der Trigger dispatched jetzt
        // ein `entry-modal:open`-Event mit `{chapterId, chapterName}`.
        // Der Modal ist eine Alpine-Komponente (entries/_add-modal),
        // die die Werte selbst uebernimmt. jQuery-Handler `.addEntry`,
        // `resetEntryForm`, `entry_frm` und `hidden.bs.modal` sind
        // damit weg — der frueher wiederholt aufgetretene 403 durch
        // chapterId-Reset-Konflikt zwischen Chapter- und Entry-Modal
        // ist strukturell nicht mehr moeglich.

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

        // Modify-Entry-Handler entfaellt seit Phase 5c.6.b — Entry-
        // Titel, Subtitel und Beschreibung werden direkt im Entry-
        // Card editiert. Add-Entry-Modal ist mit I1 (2026-08-21)
        // ganz auf Alpine umgezogen; die frueheren entryStoreUrl-,
        // resetEntryForm- und hidden.bs.modal-Handler sind hier
        // ersatzlos entfallen.

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
