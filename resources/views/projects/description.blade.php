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

    {{-- Phase 5ab.3: Verlauf-Panel neben dem Kommentar-Panel. Beide sind
         permanent im DOM und schliessen sich per `panel:open`-Namens-
         Guard gegenseitig aus (§ 6). --}}
    <x-layout.history-panel>
        <livewire:history-panel-list :projectId="$project->id" />
    </x-layout.history-panel>

    {{-- Phase 5ab.5: Bestaetigungs-Dialog fuer Wiederherstellen. Hoert
         auf `history:restore-request`, ruft revisions.restore und laedt
         danach die Seite neu, damit der frische Zustand sichtbar wird. --}}
    <x-layout.history-restore-dialog />

    {{-- Phase 5ab.3: Der alte Version-Log-Block hier (Karten-Liste mit
         Namen und Uhrzeit unter dem Editor) ist durch das Verlauf-Panel
         oben abgeloest. Die `log.detail`-Route bleibt vorerst bestehen —
         eine tiefere Aufraeumung folgt in 5ab.6, sobald wir sicher sind,
         dass keine externe Verlinkung sie mehr braucht. --}}
@endsection
