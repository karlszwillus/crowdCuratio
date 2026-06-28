{{--
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

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

{{-- Breadcrumb / „Krümelpfad" oberhalb des Content-Canvas.
 - Zwei Modi:
   (a) statisch via `:items`-Prop — Liste von [label, href]-Paaren,
       letzter Eintrag wird ohne Link gerendert (aria-current="page").
   (b) live via `:tree`-Prop — Alpine-Komponente watcht
       window.location.hash und leitet den Pfad aus dem Tree-Daten-
       Objekt ab. Klick im Sidebar-Tree ändert den Hash, Breadcrumb
       folgt automatisch. Auch für Deep-Links beim Page-Load.
 - Tree-Struktur (`:tree`):
     ['root'     => ['label' => 'Projekt-Name', 'href' => '#main-content'],
      'chapters' => [
          chapterId => [
              'label'   => 'Kapitel-Name',
              'href'    => '#anchor_Chapter_5',
              'entries' => [
                  entryId => ['label' => 'Abschnitt', 'href' => '#anchor_Entry_12'],
              ],
          ],
      ]]
--}}

@props([
    'items' => [],
    'tree' => null,
])

@if ($tree !== null)
    <div
        x-data="ccBreadcrumb({{ json_encode($tree) }})"
        x-init="syncFromHash()"
        @hashchange.window="syncFromHash()"
    >
        <nav aria-label="Breadcrumb" class="mb-3 text-body">
            <ol class="flex flex-wrap items-center gap-1 text-ink-700">
                <template x-for="(item, idx) in path" :key="idx">
                    <li class="flex items-center gap-1">
                        <template x-if="idx === path.length - 1">
                            <span aria-current="page" class="font-medium text-ink-900" x-text="item.label"></span>
                        </template>
                        <template x-if="idx !== path.length - 1">
                            <a
                                :href="item.href"
                                class="rounded-md hover:bg-chrome-active hover:text-ink-900 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary"
                                x-text="item.label"
                            ></a>
                        </template>
                        <template x-if="idx !== path.length - 1">
                            <span aria-hidden="true" class="text-ink-400">/</span>
                        </template>
                    </li>
                </template>
            </ol>
        </nav>
    </div>
@elseif (! empty($items))
    <nav aria-label="Breadcrumb" class="mb-3 text-body">
        <ol class="flex flex-wrap items-center gap-1 text-ink-700">
            @foreach ($items as $index => $item)
                @php
                    $isLast = $index === array_key_last($items);
                @endphp
                <li class="flex items-center gap-1">
                    @if ($isLast)
                        <span aria-current="page" class="font-medium text-ink-900">
                            {{ $item['label'] }}
                        </span>
                    @else
                        <a
                            href="{{ $item['href'] }}"
                            class="rounded-md hover:bg-chrome-active hover:text-ink-900 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-primary"
                        >
                            {{ $item['label'] }}
                        </a>
                        <span aria-hidden="true" class="text-ink-400">/</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
