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

@section('content')
    @php
        // 5aa.1: Design v6 § 2 auf 5e-Vokabular. Vier gleichgrosse Kacheln
        // wandern in eine strukturierte Liste mit Titel, Status-Chip,
        // Auszug und Aenderungs-Datum. Zwei Gruppen: Rechtstexte und
        // Vorlagen.
        $excerpt = function ($html) {
            $plain = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)));
            if ($plain === '') { return null; }
            return mb_strimwidth($plain, 0, 140, '…', 'UTF-8');
        };
        $imprintFilled = isset($imprint->name['firstname']) || isset($imprint->address['address']);
        $imprintExcerpt = $imprintFilled
            ? trim(($imprint->name['firstname'] ?? '').' '.($imprint->name['lastname'] ?? '').' · '.($imprint->address['address'] ?? ''))
            : null;

        $rows = [
            [
                'title' => __('settings_imprint'),
                'excerpt' => $imprintExcerpt,
                'filled' => $imprintFilled,
                'updated' => optional($imprint?->updated_at)->format('d.m.Y'),
                'target' => '#imprintModal',
                'trigger' => null,
                'group' => 'legal',
            ],
            [
                'title' => __('settings_policy'),
                'excerpt' => $excerpt($privacy?->privacy_policy),
                'filled' => ! empty(trim(strip_tags((string) ($privacy?->privacy_policy ?? '')))),
                'updated' => optional($privacy?->updated_at)->format('d.m.Y'),
                'target' => '#privacyModal',
                'trigger' => 'addContentPrivacy',
                'group' => 'legal',
            ],
            [
                'title' => __('settings_terms'),
                'excerpt' => $excerpt($terms?->terms_conditions),
                'filled' => ! empty(trim(strip_tags((string) ($terms?->terms_conditions ?? '')))),
                'updated' => optional($terms?->updated_at)->format('d.m.Y'),
                'target' => '#termsConditionsModal',
                'trigger' => 'addContentTerms',
                'group' => 'legal',
            ],
            [
                'title' => __('settings_invitation'),
                'excerpt' => $excerpt($mail?->invitation),
                'filled' => ! empty(trim(strip_tags((string) ($mail?->invitation ?? '')))),
                'updated' => optional($mail?->updated_at)->format('d.m.Y'),
                'target' => '#invitationModal',
                'trigger' => 'addContentMail',
                'group' => 'templates',
                'placeholders' => ['{Name}', '{Projektname}', '{Einladender}', '{Link}'],
            ],
        ];
    @endphp

    @if ($message = Session::get('success'))
        <div class="mb-4 rounded-md border border-success-bg bg-success-bg/40 px-4 py-3 text-body text-success">
            {{ $message }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-md border border-danger-bg bg-danger-bg/40 px-4 py-3 text-body text-danger">
            <strong>{{__('whoops')}}</strong> {{__('message_problem_input')}}
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="mb-1 text-mono-caps font-mono uppercase tracking-widest text-ink-500">
                {{ __('settings_system_label') }}
            </p>
            <h1 class="text-title font-semibold text-ink-900">{{ __('settings_page_title') }}</h1>
        </div>
        <p class="max-w-sm text-body text-ink-500">{{ __('settings_scope_hint') }}</p>
    </header>

    @foreach (['legal', 'templates'] as $groupKey)
        @php
            $groupRows = collect($rows)->where('group', $groupKey)->values();
        @endphp
        <section class="mb-10">
            <header class="mb-3">
                <h2 class="text-heading font-semibold text-ink-900">
                    {{ __('settings_group_'.$groupKey) }}
                </h2>
                <p class="text-caption text-ink-500">
                    {{ __('settings_group_'.$groupKey.'_desc') }}
                </p>
            </header>

            <ul class="divide-y divide-line-200 rounded-md border border-line-200 bg-paper-0">
                @foreach ($groupRows as $row)
                    <li class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 {{ ! $row['filled'] ? 'bg-warning-bg/30' : '' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-body font-semibold text-ink-900">{{ $row['title'] }}</span>
                                @if ($row['filled'])
                                    <span class="rounded-md bg-success-bg px-2 py-0.5 text-caption text-success">{{ __('settings_status_filled') }}</span>
                                @else
                                    <span class="rounded-md bg-warning-bg px-2 py-0.5 text-caption text-warning">{{ __('settings_status_empty') }}</span>
                                @endif
                            </div>
                            @if ($row['excerpt'])
                                <p class="mt-1 truncate text-body text-ink-700">{{ $row['excerpt'] }}</p>
                            @else
                                <p class="mt-1 text-caption text-ink-500">{{ __('settings_status_empty_consequence') }}</p>
                            @endif
                            @if (! empty($row['placeholders']))
                                <p class="mt-1 font-mono text-caption text-ink-500">
                                    {{ __('settings_placeholders_hint', ['list' => implode(' · ', $row['placeholders'])]) }}
                                </p>
                            @endif
                            @if ($row['updated'])
                                <p class="mt-1 text-caption text-ink-500">{{ __('settings_changed_on') }} {{ $row['updated'] }}</p>
                            @endif
                        </div>

                        <button
                            type="button"
                            @if ($row['trigger']) id="{{ $row['trigger'] }}" @endif
                            data-toggle="modal"
                            data-target="{{ $row['target'] }}"
                            class="inline-flex items-center gap-1 rounded-md {{ $row['filled'] ? 'border border-line-200 bg-canvas-bg text-ink-900 hover:bg-chrome-active' : 'bg-primary text-primary-on hover:opacity-90' }} px-3 py-1.5 text-caption font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <x-icon name="{{ $row['filled'] ? 'pencil' : 'plus' }}" size="3"/>
                            <span>{{ $row['filled'] ? __('settings_edit') : __('settings_create_text') }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    {{-- Alte Cards nach 5aa.1 abgeschaltet; die Quelldaten werden von den
         Modals-JS-Hooks weiter benutzt. Wir behalten die verdeckten span-
         Container mit den Rohtexten, damit `#addContent*`-Handler den Quill
         mit dem aktuellen Inhalt fuellen koennen. --}}
    <div class="sr-only">
        @isset($terms->terms_conditions) <span id="contentTerms">{!! $terms->terms_conditions !!}</span> @endisset
        @isset($privacy->privacy_policy) <span id="contentPolicy">{!! $privacy->privacy_policy !!}</span> @endisset
        @isset($mail->invitation) <span id="contentMail">{!! $mail->invitation !!}</span> @endisset
    </div>

    <!-- Modale: Terms / Privacy / Imprint / Invitation -->
    <x-ui.modal id="termsConditionsModal" :title="__('add_new_terms')" size="lg" labelledby="Terms Conditions">
        <div class="row m-2">
            <form action="{{route('settings.store')}}" name="contentForm"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="idTerms" @isset($terms->id) value="{{ $terms->id }}" @endisset>
                <p class="mb-7">{{__('add_text')}}</p>
                <div id="termsConditionsEditor"></div>
                <div class="col-xs-12 mt-2">
                    <button id="btn_text" type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <x-ui.modal id="privacyModal" :title="__('add_new_policy')" size="lg" labelledby="Privacy Policy">
        <div class="row m-2">
            <form action="{{route('settings.store')}}" method="POST"
                  enctype="multipart/form-data">
                @csrf
                <div class="col-xs-12">
                    <input type="hidden" name="idPrivacy" @isset($privacy->id) value="{{$privacy->id}}" @endisset>
                    <p class="mb-7">{{__('add_text')}}</p>
                    <div id="privacyPolicy"></div>
                </div>
                <div class="col-xs-12 mt-2">
                    <button id="btn_privacy" type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <x-ui.modal id="imprintModal" :title="__('add_new_imprint')" labelledby="Imprint">
        <div class="row">
            <form action="{{route('settings.store')}}" method="post">
                @csrf
                <div class="col-xs-12">
                    <input type="hidden" name="IdImprint" @isset($imprint->id) value="{{$imprint->id}}" @endisset>
                    <input type="text" name="firstname" placeholder="Firstname" class="form-control mb-2" @isset($imprint->name['firstname']) value="{{$imprint->name['firstname']}}" @endisset/>
                    <input type="text" name="lastname" placeholder="Lastname" class="form-control mb-2" @isset($imprint->name['lastname']) value="{{$imprint->name['lastname']}}" @endisset/>
                    <input type="text" name="address" placeholder="Address" class="form-control mb-2" @isset($imprint->address['address']) value="{{$imprint->address['address']}}" @endisset/>
                    <input type="text" name="postcode" placeholder="Postcode" class="form-control mb-2" @isset($imprint->address['postcode']) value="{{$imprint->address['postcode']}}" @endisset/>
                    <input type="text" name="phone" placeholder="Phone" class="form-control mb-2" @isset($imprint->contact['phone']) value="{{$imprint->contact['phone']}}" @endisset/>
                    <input type="text" name="fax" placeholder="Fax" class="form-control mb-2" @isset($imprint->contact['fax']) value="{{$imprint->contact['fax']}}" @endisset/>
                    <input type="email" name="email" placeholder="E-mail" class="form-control mb-2" @isset($imprint->contact['email']) value="{{$imprint->contact['email']}}" @endisset/>
                </div>
                <div class="col-xs-12">
                    <button type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <x-ui.modal id="invitationModal" :title="__('add_new_invitation')" size="lg" labelledby="Invitation E-mail">
        <div class="row m-2">
            <form action="{{route('settings.store')}}" method="POST"
                  enctype="multipart/form-data">
                @csrf
                <div class="col-xs-12">
                    <input type="hidden" name="IdEmail" @isset($mail->id) value="{{ $mail->id }}" @endisset>
                    <div id="invitation"></div>
                </div>
                <div class="col-xs-12 mt-2">
                    <button id="btn_invitation" type="submit" class="btn btn-primary float-right">{{__('save')}}</button>
                </div>
            </form>
        </div>
    </x-ui.modal>
@endsection
@push('scripts')
    <script>
        let Font = Quill.import('formats/font');
        Font.whitelist = ['times-new-roman', 'arial', 'Sans Serif'];
        Quill.register(Font, true);

        let toolbarOptions = [
            [{
                'header': [1, 2, 3, 4, 5, 6, false]
            }],
            ['bold', 'italic', 'underline', 'strike'], // toggled buttons
            //['blockquote', 'code-block'],

            /*[{
                'header': 1
            }, {
                'header': 2
            }], // custom button values
            */[{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            /*[{
                'script': 'sub'
            }, {
                'script': 'super'
            }], // superscript/subscript
            */[{
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
        let quill = new Quill('#termsConditionsEditor', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillPrivacy = new Quill('#privacyPolicy', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        let quillMail = new Quill('#invitation', {
            modules: {
                toolbar: toolbarOptions,
            },
            theme: 'snow'
        });

        $('#btn_text').click(function () {
            let hvalue = $('.ql-editor').html();
            $(this).append("<textarea name='termsConditions' style='display:none'>" + hvalue + "</textarea>");
        });

        $('#btn_privacy').click(function () {
            let privacy = quillPrivacy.container.firstChild.innerHTML;
            $(this).append("<textarea name='privacyPolicy' style='display:none'>" + privacy + "</textarea>");
        });

        $('#btn_invitation').click(function () {
            let invitation = quillMail.container.firstChild.innerHTML;
            $(this).append("<textarea name='invitation' style='display:none'>" + invitation + "</textarea>");
        });

        //Add or Modify AGBs
        $('#addContentTerms').click(function () {
            if($('#contentTerms').length){
                quill.container.firstChild.innerHTML = $('#contentTerms').html();
            }
        });

        //Add or Modify Privacy and policy
        $('#addContentPrivacy').click(function () {
            if($('#contentPolicy').length){
                quillPrivacy.container.firstChild.innerHTML = $('#contentPolicy').html();
            }
        });

        //Add or Modify mail invitation
        $('#addContentMail').click(function () {
            if($('#contentMail').length){
                quillMail.container.firstChild.innerHTML = $('#contentMail').html();
            }
        });
    </script>
@endpush
