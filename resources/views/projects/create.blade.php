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
    <form id="frm_project" name="projectForm"
          action="@if(isset($project->id)) {{ route('projects.update',$project->id) }} @else {{ route('projects.store') }} @endif"
          method="POST"
          enctype="multipart/form-data">
    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-sm-11">
                    @csrf
                    @if(isset($project->id))
                        @method('PUT')
                    @endif
                    <div class="form-group mx-sm-3 mb-2 col-sm-5">
                        <label for="inputProject">{{__('project_name')}} <span
                                    style="color: red">{{__('label_mandatory')}}</span></label>
                        <input name="name" type="text" class="form-control" placeholder="Add name"
                               value="@if(isset($project->name)) {{$project->name}} @else {{old('name')}} @endif" autocomplete="off">
                    </div>
            </div>

        </div>
    </div>
    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-sm-9">
                <div class="form-group mx-sm-3 mb-2 col-sm-10">
                    <p for="inputProject">{{__('project_thumbnail')}} {{__('label_optional')}}</p>
                    <p for="thumbnail">{{__('add_project_thumbnail')}}</p>
                    <div class="form-group">
                        <label>200px x 200px</label>
                        <div class="input-group">
                    <span class="input-group-btn">
                        <span class="btn btn-default btn-file">
                            <x-icon name="folder" class="m-2" />{{__('browse')}} <input value="{{old('project_image')}}"
                                                                                    name="project_image" type="file"
                                                                                    id="imgInp">
                        </span>
                    </span>
                            <input name="logo" value="@if(isset($project->logo)) {{$project->logo}} @else {{old('logo')}} @endif" type="text" class="form-control border-0"
                                   style="background-color: white"
                                   readonly>

                        </div>

                    </div>
                </div>
            </div>
            <div class="col-sm-2">
                </br>

                <img id='img-upload' src="@if(isset($project->logo)){{route('image', $project->logo)}} @endif"/>

            </div>

        </div>
    </div>

    <hr class="mt-5 mb-5">

    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-sm-11">
                <div class="form-group mx-sm-12 mb-2 col-sm-12">
                    <label >{{__('description')}} </label>
                    <div id="descriptionId"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-sm-11">
                <div class="form-group mx-sm-12 mb-2 col-sm-12">
                    <label for="inputProject">{{__('project_imprint')}} <span
                                style="color: red">{{__('label_mandatory')}}</span></label>
                    <div id="imprintId"></div>
                </div>
            </div>

        </div>
    </div>

    <div class="card p-4 mb-4">
        <div class="row">
            <div class="col-sm-11">
                <div class="form-group mx-sm-12 mb-2 col-sm-12">
                    <label for="inputProject">{{__('project_terms')}} {{__('label_optional')}}</label>
                    <div id="termsId"></div>
                </div>
            </div>
        </div>
    </div>
    </form>
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
    <button id="btn_save" class="btn btn-secondary btn-lg btn-block text-left" type="submit" name="btn_submit"
            value="Save"><x-icon name="file-earmark" class="m-2" />@if(isset($project->id)) {{__('save')}} @else {{__('save')}} @endif
    </button>
    @if(isset($project->id))
    <a class="btn btn-secondary btn-lg btn-block text-left" href="{{ route('projects.edit', $project->id) }}"> {{__('content')}}
    </a>
    @endif
    @if(isset($project->id))
        @if(Auth::user()->id == $project->user_id || Auth::user()->isAdmin() || in_array('invite', $listPermissions))
            {{-- Phase 5d.4: die alte Permission-Card und alle drei
                 Legacy-Modals sind in die Sicht
                 /projects/{id}/permissions gewandert (Screen 3B,
                 Handoff v4). --}}
            <a href="{{ route('projects.permissions', $project->id) }}"
               class="btn btn-secondary btn-lg btn-block text-left mt-4">
                <x-icon name="users" class="m-2"/>
                {{ __('permissions') }}
            </a>
        @endif

    {{-- Phase-5-Backlog-Sammler (2026-08-16, #52):
         Die Legacy-Invite-Modals `newUserInvitation` und `newUser`
         sind endgueltig raus. Der Session-basierte Zwei-Schritt-Flow
         (Session::get('error_code') 6/7) ist mit der Volt-Component
         `<livewire:project-permissions>` (Screen 3B, 5d.4) und dem
         Register-Reader-Default (5d.7) abgeloest. Der check.email-
         Endpoint selbst bleibt vorerst als Route stehen und wird in
         einem Route-Cleanup-Ticket entfernt. --}}

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


            /*imprintModify.root.addEventListener('keydown', evt => {
                $('#updateProjectBtn').html('<button id="btn_save" class="btn btn-secondary btn-block text-left" type="submit" name="btn_submit" value="Save"><x-icon name="file-earmark" class="m-2" />{{__('save')}}</button>');
            });

            termsModify.root.addEventListener('keydown', evt => {
                $('#updateProjectBtn').html('<button id="btn_save" class="btn btn-secondary btn-block text-left" type="submit" name="btn_submit" value="Save"><x-icon name="file-earmark" class="m-2" />{{__('save')}}</button>');
            });*/

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
        // Legacy-Modals raus. Der Invite-Flow lebt in der
        // <livewire:project-permissions>-Sicht (Screen 3B).
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
