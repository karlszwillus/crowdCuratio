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

@section('main')

    {{-- Phase 5d.4-Followup: einheitlicher Projekt-Tab-Balken auf
         allen vier Screens. Nur zeigen, wenn wir ein bestehendes
         Projekt bearbeiten — bei der Neuanlage (POST /projects
         ohne existierendes $project->id) gibt es noch keine Tabs. --}}
    @if (isset($project->id))
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4
                    border-b border-line-200 pb-3">
            <div class="min-w-0 flex-1">
                <x-ui.breadcrumb :tree="app(App\Services\ProjectTreeService::class)->breadcrumbTree($project)"/>
            </div>
            <x-projects.tabs :project="$project" active="meta"/>
        </div>
    @endif

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>{{__('whoops')}}</strong> {{__('message_problem_input')}}<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <p class="mb-4 text-caption text-ink-500">{{ __('metadata_page_hint') }}</p>

    <form id="frm_project" name="projectForm"
          action="@if(isset($project->id)) {{ route('projects.update',$project->id) }} @else {{ route('projects.store') }} @endif"
          method="POST"
          enctype="multipart/form-data"
          x-data="{ nameLen: @js(isset($project->name) ? mb_strlen((string) $project->name) : 0) }">
        @csrf
        @if(isset($project->id))
            @method('PUT')
        @endif

        {{-- 5aa.2 § 3: Projektname. Jedes Feld eine Karte, ein Label,
             Sternchen in danger direkt am Label, kein „* (Pflichtfeld)". --}}
        <div class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <label for="inputProjectName" class="mb-1 block text-caption font-semibold text-ink-700">
                {{ __('project_name') }} <span class="text-danger" aria-hidden="true">*</span>
            </label>
            <p class="mb-2 text-caption text-ink-500">{{ __('metadata_field_name_hint') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <input id="inputProjectName"
                       name="name"
                       type="text"
                       maxlength="80"
                       required
                       autocomplete="off"
                       @input="nameLen = $event.target.value.length"
                       value="@if(isset($project->name)){{$project->name}}@else{{old('name')}}@endif"
                       class="w-full max-w-lg rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"/>
                <span class="text-caption font-mono text-ink-500"
                      x-text="`${nameLen} / 80`">
                </span>
            </div>
        </div>

        {{-- Projektbild: eigene Vorschau + Datei-Meta, kein natives Browser-Label
             (Design v6 § 3: „DurchsuchenChoose File No file chosen" ist doppelt
             lokalisierte Browser-Zeichenkette und darf nicht im UI stehen). --}}
        <div class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5"
             x-data="{
                 file: null,
                 preview: @js(isset($project->logo) ? route('image', $project->logo) : null),
                 fileName: @js($project->logo ?? null),
                 removed: false,
                 pickFile(event) {
                     const file = event.target.files[0];
                     if (!file) return;
                     this.file = file;
                     this.fileName = file.name;
                     this.removed = false;
                     const reader = new FileReader();
                     reader.onload = (e) => { this.preview = e.target.result; };
                     reader.readAsDataURL(file);
                 },
                 remove() {
                     this.file = null;
                     this.preview = null;
                     this.fileName = null;
                     this.removed = true;
                     this.$refs.input.value = '';
                 },
             }">
            <label class="mb-1 block text-caption font-semibold text-ink-700">
                {{ __('project_thumbnail') }}
                <span class="text-caption font-normal text-ink-500">{{ __('label_optional') }}</span>
            </label>
            <p class="mb-3 text-caption text-ink-500">{{ __('metadata_field_thumbnail_hint') }}</p>

            <div class="flex flex-wrap items-start gap-4">
                <div class="relative flex shrink-0 items-center justify-center overflow-hidden rounded-md bg-line-100"
                     style="width: 120px; height: 120px;">
                    <template x-if="preview">
                        <img :src="preview" alt="" class="max-h-full max-w-full object-cover"/>
                    </template>
                    <template x-if="! preview">
                        <x-icon name="image" size="6"/>
                    </template>
                </div>

                <div class="flex min-w-0 flex-1 flex-col gap-2">
                    <p class="truncate text-body text-ink-900" x-text="fileName || '—'"></p>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="cursor-pointer rounded-md border border-line-200 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary">
                            <span x-text="fileName ? '{{ __('metadata_field_thumbnail_replace') }}' : '{{ __('metadata_field_thumbnail_choose') }}'"></span>
                            <input x-ref="input" type="file" name="project_image" accept="image/*"
                                   @change="pickFile($event)"
                                   class="sr-only"/>
                        </label>
                        <button type="button" x-show="fileName" @click="remove()"
                                class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-caption text-danger hover:bg-danger-bg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                            <x-icon name="trash-2" size="3"/>
                            <span>{{ __('metadata_field_thumbnail_remove') }}</span>
                        </button>
                    </div>
                    <input type="hidden" name="logo"
                           :value="removed ? '' : @js($project->logo ?? '')"/>
                </div>
            </div>
        </div>

        {{-- Beschreibung — Quill-Editor bleibt im bestehenden #descriptionId. --}}
        <div class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <label class="mb-1 block text-caption font-semibold text-ink-700">{{ __('description') }}</label>
            <div id="descriptionId" class="rounded-md border border-line-200 bg-canvas-bg"></div>
        </div>

        {{-- Impressum — Systemtext-Übernahme kopiert den aktuellen /settings-Text
             ins Projekt-Feld. Danach entkoppelt. Bleibt das Feld später wieder
             leer, greift der Systemtext beim Publish automatisch. --}}
        <div class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <label class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('project_imprint') }} <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <p class="mb-3 text-caption text-ink-500">{{ __('metadata_imprint_intro') }}</p>
                </div>
                @isset($project->id)
                    <button type="submit"
                            form="adoptImprintForm"
                            class="inline-flex items-center gap-1 rounded-md border border-line-200 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <x-icon name="corner-down-left" size="3"/>
                        <span>{{ __('metadata_use_system_text') }}</span>
                    </button>
                @endisset
            </div>
            <div id="imprintId" class="rounded-md border border-line-200 bg-canvas-bg"></div>
        </div>

        {{-- Geschäftsbedingungen — analog zum Impressum. --}}
        <div class="mb-4 rounded-md border border-line-200 bg-paper-0 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <label class="mb-1 block text-caption font-semibold text-ink-700">
                        {{ __('project_terms') }}
                        <span class="text-caption font-normal text-ink-500">{{ __('label_optional') }}</span>
                    </label>
                    <p class="mb-3 text-caption text-ink-500">{{ __('metadata_terms_intro') }}</p>
                </div>
                @isset($project->id)
                    <button type="submit"
                            form="adoptTermsForm"
                            class="inline-flex items-center gap-1 rounded-md border border-line-200 bg-canvas-bg px-3 py-1.5 text-caption text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <x-icon name="corner-down-left" size="3"/>
                        <span>{{ __('metadata_use_system_text') }}</span>
                    </button>
                @endisset
            </div>
            <div id="termsId" class="rounded-md border border-line-200 bg-canvas-bg"></div>
        </div>

        {{-- Klebende Speicher-Fußzeile am Seitenende — Design v6 § 3.
             Ein Primär-Button, ein sekundärer, Speicherstand links. --}}
        <div class="sticky bottom-0 -mx-4 mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-line-200 bg-paper-0/95 px-6 py-3 shadow-medium backdrop-blur">
            <p class="text-caption text-ink-500">
                @isset($project->updated_at)
                    {{ __('metadata_footer_last_saved') }}
                    {{ $project->updated_at->format('d.m.Y, H:i') }}
                @else
                    {{ __('metadata_page_hint') }}
                @endisset
            </p>
            <div class="flex items-center gap-2">
                @isset($project->id)
                    <a href="{{ route('projects.edit', $project->id) }}"
                       class="inline-flex items-center gap-1 rounded-md border border-line-200 bg-canvas-bg px-3 py-2 text-body text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        {{ __('metadata_footer_discard') }}
                    </a>
                @endisset
                <button type="submit"
                        class="inline-flex items-center gap-1 rounded-md bg-primary px-4 py-2 text-body font-medium text-primary-on hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <x-icon name="save" size="4"/>
                    <span>{{ __('save') }}</span>
                </button>
            </div>
        </div>
    </form>

    @isset($project->id)
        {{-- 5aa.2 Design v6 § 3: „Systemtext übernehmen" — einmaliges Copy
             aus /settings ins Projekt-Feld. Danach entkoppelt. --}}
        <form id="adoptImprintForm"
              action="{{ route('projects.metadata.adopt_system_text', $project) }}"
              method="POST" class="hidden">
            @csrf
            <input type="hidden" name="field" value="imprint">
        </form>
        <form id="adoptTermsForm"
              action="{{ route('projects.metadata.adopt_system_text', $project) }}"
              method="POST" class="hidden">
            @csrf
            <input type="hidden" name="field" value="terms">
        </form>
    @endisset
@endsection
@section('action')
    <div class="col-sm-9">
        <button id="btn_save" class="btn btn-secondary btn-lg btn-block text-left" type="submit" name="btn_submit"
                value="Save"><x-icon name="file-earmark" class="m-2" />@if(isset($project->id)) {{__('save')}} @else Save @endif
        </button>
        <!--<button class="btn btn-secondary btn-lg btn-block text-left" type="submit" name="btn_submit" value="Preview"><x-icon name="eye" class="m-2" />Preview
         </button>
         <button class="btn btn-secondary btn-lg btn-block text-left" type="submit" name="btn_submit" value="Publish"><x-icon name="globe" class="m-2" />Publish
         </button>!-->
        @if(isset($project->id))
            <form action="{{ route('projects.destroy',$project->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button class="btn btn-secondary btn-lg btn-block text-left mt-2" type="submit"
                        onclick="return confirm('{{__('message_delete_confirm')}}')">
                    <x-icon name="trash" class="m-2" /> {{__('delete_project')}}
                </button>
            </form>
        @endif

    </div>
@endsection
@section('sidebar')
    {{-- 5aa.2 § 3: Rechte Spalte trägt Prüfung, Kennzahlen, Verlauf und
         Löschen. Speichern liegt in der klebenden Fußzeile, „Berechtigungen"
         und „Zurück zu Projektdetails" sind Tabs — hier daher nicht mehr. --}}
    @if(isset($project->id))
        <div class="flex flex-col gap-3">
            <a href="#publish-check"
               class="inline-flex items-center gap-2 rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="clipboard-check" size="4"/>
                <span>{{ __('metadata_sidebar_publish_check') }}</span>
            </a>
            <div class="rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-500">
                <p class="mb-1 text-caption font-semibold uppercase tracking-wider text-ink-700">
                    {{ __('metadata_sidebar_stats') }}
                </p>
                <p>{{ trans_choice('n_chapters', isset($project->chapters) ? count($project->chapters) : 0) }}</p>
            </div>
            <a href="{{ route('projects.edit', $project->id) }}#history"
               class="inline-flex items-center gap-2 rounded-md border border-line-200 bg-paper-0 px-3 py-2 text-body text-ink-900 hover:bg-chrome-active focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                <x-icon name="history" size="4"/>
                <span>{{ __('metadata_sidebar_history') }}</span>
            </a>
        </div>
        {{-- Löschen bewusst ausserhalb der Speicher-Fusszeile. --}}
        <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="mt-4">
            @csrf
            @method('DELETE')
            <button type="submit"
                    onclick="return confirm('{{__('message_delete_confirm')}}')"
                    class="inline-flex w-full items-center gap-2 rounded-md border border-danger-bg bg-transparent px-3 py-2 text-body text-danger hover:bg-danger-bg/40 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                <x-icon name="trash-2" size="4"/>
                <span>{{ __('delete_project') }}</span>
            </button>
        </form>
    @endif
@endsection
@push('scripts')
    <script>
        $(document).ready(function () {

            //Send form
            $('#btn_save').click(function (){
                $('#frm_project').submit();
            })

            //enable and disabled Entry click
            $('input[name=chapterId]').change(function () {
                var check = $('#chapterId').val();
                if (check != '') {
                    $('#addEntry').removeClass('btn disabled');
                    $('#addNewElement').removeClass('disabled');
                } else {
                    $('#addEntry').addClass('btn disabled');
                    $('#addNewElement').addClass('disabled');
                }
            });

            //toggle elements
            $('#addChapter').click(function () {
                $('#chapter').toggle();
            })

            $('#addEntry').click(function () {
                $('#entry').toggle();
            })

            //set content of new Element
            $('#addNewElement').click(
                function () {
                    var someText = $('#chapterId').val();
                    var action = $('');
                    var newDiv = $('<div class="card p-4 mb-4"><div class="row"><div class="col-sm-11"><p>Chapter</p><input name="chapter[]" type="text" class="form-control-plaintext border-0" value="' + someText + '" readonly></div></div></div>');
                    $('#newElement').append(newDiv);
                    // Direkt auf Vanilla-Modal-API (siehe Kommentar
                    // beim Invite-Flow oben) — nicht via jQuery-Shim.
                    window.crowdCuratioModal && window.crowdCuratioModal.close('#myModal');
                }
            )

            //Add thumbnail
            $(document).on('change', '.btn-file :file', function () {
                var input = $(this),
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [label]);
            });

            $('.btn-file :file').on('fileselect', function (event, label) {

                var input = $(this).parents('.input-group').find(':text'),
                    log = label;

                if (input.length) {
                    input.val(log);
                } else {
                    if (log) alert(log);
                }

            });

            function readURL(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        $('#img-upload').attr('src', e.target.result);
                    }

                    reader.readAsDataURL(input.files[0]);
                }
            }

            $("#imgInp").change(function () {
                readURL(this);
            });

            //Check for new changes before leaving the page
            /*let formmodified = 0;
            $('#frm_project').change(function () {
                formmodified = 1;
            });
            $('#btn_save').click(function () {
                formmodified = 0;
            });
            window.onbeforeunload = confirmExit;

            function confirmExit() {
                if (formmodified == 1) {
                    return "Exit?";
                }
            }*/
        })



        //Modify metadat imprint
        $(document).ready(function (){
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
            let quill = new Quill('#imprintId', {
                modules: {
                    toolbar: toolbarOptions,
                },
                theme: 'snow'
            });

            let quillTerms = new Quill('#termsId', {
                modules: {
                    toolbar: toolbarOptions,
                },
                theme: 'snow'
            });

            let quillDescription = new Quill('#descriptionId', {
                modules: {
                    toolbar: toolbarOptions,
                },
                theme: 'snow'
            });

            <?php if(isset($project)): ?>
                quillDescription.container.firstChild.innerHTML = '{!! !empty($project->description) ? $project->description : ''!!}';
                quill.root.innerHTML = '{!! !empty($project->imprint) ? $project->imprint : ''!!}';
                quillTerms.container.firstChild.innerHTML = '{!! !empty($project->terms) ? $project->terms : ''!!}';
            <?php endif; ?>


            // Phase-5-Backlog-Sammler (2026-08-16): toter Block
            // rausgeworfen. Zwei updateProjectBtn.html-Aufrufe
            // standen zwar in Block-Kommentaren, aber Blade rendert
            // die x-icon-Komponente im Text zu Inline-SVG, dessen
            // path- und viewBox-Attribute Sequenzen enthalten koennen,
            // die den Kommentar-Block frueh schliessen. Rest wird als
            // aktives JS geparst und wirft SyntaxError.

            //Add imprint and terms to forms
            $('#frm_project').submit(function() {
                let imprint = quill.root.innerHTML;
                let terms = quillTerms.root.innerHTML;
                let description = quillDescription.root.innerHTML;
                $(this).append("<textarea name='imprint' style='display:none'>" + imprint + "</textarea>");
                $(this).append("<textarea name='terms' style='display:none'>" + terms + "</textarea>");
                $(this).append("<textarea name='description' style='display:none'>" + description + "</textarea>");
                return true;
            });

        })

        // Phase-5-Backlog-Sammler (2026-08-16, #52): die frueheren
        // Session-Trigger fuer newUserInvitation/newUser sind mit den
        // Legacy-Modals raus. Der Invite-Flow lebt in der Volt-Sicht
        // project-permissions (Screen 3B).
        @isset($project->id)

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
        @endisset

    </script>
@endpush
