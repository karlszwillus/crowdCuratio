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

@section('log')

@endsection
@section('sidebar')
    {{-- Phase 5x.1 + 5x.9: das Kommentar-Panel ist immer im DOM. Der
         Inhalt ist eine Livewire-Komponente, die auf `comment-panel:load`
         hoert und die Kommentare fuer den aktiven Block per
         `CommentRetrieve` nachlaedt. Die Kommentar-Icons in der Editor-
         Struktur feuern das Event `panel:load-and-open` — Panel und
         Livewire-Liste reagieren gleichzeitig. Der frueher noetige
         Full-Page-Reload zu `?comment=…` faellt damit weg. --}}
    <x-layout.comment-panel>
        <livewire:comment-panel-list :projectId="$project->id" />
    </x-layout.comment-panel>

    {{-- Version-Log bleibt bewusst ausserhalb des Panels — er ist keine
         Kommentar-UI, sondern ein separates Historien-Widget. Wird im
         5-D.5-Editor-Chrome-Refactor voraussichtlich zum History-Drawer. --}}
    <div class="card p-4 mb-4 mt-4">
        <div class="row versions">
           {{-- <span class="ml-3">{{__('version')}}</span> --}}
            @isset($textLog)

                @foreach($textLog as $log => $v)
                    <div class="mt-4">
                        <p class="ml-4 mb-4">{{date('d.m.Y',strtotime( $v['created_at']))}}</p>
                        <div class="col-sm-8 mb-4">
                            <a href="{{route('log.detail',[$project->id,$v['id']])}}">{{$v['userName']}}</a>
                        </div>
                        <div class="col-sm-4 text-right mb-4">
                            {{date('G:i',strtotime( $v['created_at']))}}
                        </div>

                    </div>
                @endforeach
            @endisset

        </div>
    </div>
@endsection
