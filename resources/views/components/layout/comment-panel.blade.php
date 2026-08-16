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

{{-- Phase 5x.1: Kommentar-Slide-out-Panel rechts (Screen 10).

Öffnet automatisch, wenn die URL das `?comment=`-Query trägt (der Legacy-
Trigger aus den Block-Icons bleibt bestehen). Zusaetzlich hoert das Panel
auf das globale Event `panel:open` — spaeter kann so aus einem Header-
Button ohne URL-Wechsel geoeffnet werden.

Animation:
  Das Panel bleibt permanent im DOM und wechselt zwischen `translate-x-full`
  (rechts ausserhalb des Viewports) und `translate-x-0` per :class-Toggle.
  Das umgeht das bekannte Alpine-Problem, dass x-show + x-transition mit
  initial-open beim ersten Paint keinen Enter-Uebergang durchlaeuft.
  Timing: 350 ms mit `cubic-bezier(0.16, 1, 0.3, 1)` (ease-out expo) —
  startet schnell, laeuft am Ende weich aus (Drawer-Landeanflug).

Props:
- `title`   — Titel im Panel-Header (Default: „Kommentare").
--}}

@props([
    'title' => null,
])

@php
    $panelTitle = $title ?? __('comment');
    $urlOpen = request()->has('comment');
    // Phase 5x.9-Follow-up: bei URL-basiertem Auto-Open (Deep-Link
    // aus Dashboard oder /allComments) reichen wir die commentable-
    // Koordinaten mit an die Livewire-Liste weiter — sonst bleibt das
    // Panel offen, aber leer.
    $urlCommentableType = request()->query('model');
    $urlCommentableId = request()->query('comment');
@endphp

<div
    x-cloak
    x-data="{
        open: false,
        openPanel() { this.open = true; },
        closePanel() {
            this.open = false;
            const url = new URL(window.location.href);
            if (url.searchParams.has('comment')) {
                url.searchParams.delete('comment');
                url.searchParams.delete('model');
                url.searchParams.delete('type');
                window.history.replaceState({}, '', url.toString());
            }
        },
    }"
    @keydown.escape.window="if (open) closePanel()"
    @panel:open.window="openPanel()"
    @panel:close.window="closePanel()"
    {{-- Phase 5x.9: die Kommentar-Icons feuern `panel:load-and-open`
         mit `{commentableType, commentableId}`. Wir reichen es weiter an
         die Livewire-Liste (Event `comment-panel:load`) und oeffnen das
         Panel im gleichen Schritt. Kein Full-Page-Reload mehr. --}}
    @panel:load-and-open.window="
        Livewire.dispatch('comment-panel:load', {
            commentableType: $event.detail.commentableType,
            commentableId: $event.detail.commentableId,
        });
        openPanel();
    "
    {{-- Kein Reload mehr — die Livewire-Komponente `comment-panel-list`
         hoert selbst auf `comment-added` und rendert sich neu. --}}
    {{-- Doppeltes requestAnimationFrame stellt sicher, dass der Browser
         den initialen closed-Zustand (opacity-0 + translate-x-full)
         mindestens einmal gepaintet hat, bevor open=true die Transition
         zum offenen Zustand ausloest. Ohne diesen Umweg fasst Chrome
         beide State-Wechsel im selben Frame zusammen und ueberspringt
         die Animation. Wenn ein Deep-Link (mit `?model=…&comment=…`)
         geoeffnet wird, feuern wir zusaetzlich `comment-panel:load`,
         damit die Livewire-Liste die richtigen Kommentare zieht. --}}
    x-init="
        @if ($urlOpen)
            requestAnimationFrame(() => requestAnimationFrame(() => {
                openPanel();
            }));
            @if ($urlCommentableType && $urlCommentableId)
                {{-- Livewire braucht ein paar Ticks nach Alpine, um
                     seine Globale bereitzustellen. Wir warten auf das
                     `livewire:init`-Event; ist es schon gefeuert
                     worden, macht der setTimeout-Fallback den Job. --}}
                document.addEventListener('livewire:init', () => {
                    Livewire.dispatch('comment-panel:load', {
                        commentableType: @js($urlCommentableType),
                        commentableId: {{ (int) $urlCommentableId }},
                    });
                }, { once: true });
                setTimeout(() => {
                    if (window.Livewire) {
                        Livewire.dispatch('comment-panel:load', {
                            commentableType: @js($urlCommentableType),
                            commentableId: {{ (int) $urlCommentableId }},
                        });
                    }
                }, 300);
            @endif
        @endif
    "
>
    {{-- Backdrop: closed-Zustand ist die statische Basis-Klasse. Alpine
         setzt via :class-Object-Form additive „open"-Klassen — die
         !-Praefixe zwingen Tailwind, sie ueber die Basis zu stellen,
         damit es beim Uebergang zu einem echten opacity-Animation kommt
         (statt einer Praezedenz-Verwirrung zwischen opacity-0 und
         opacity-100 im gleichen class-Attribut). --}}
    <div
        :class="open ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
        @click="closePanel()"
        class="fixed inset-0 z-40 bg-ink-900/40"
        style="transition: opacity 300ms ease-out;"
        aria-hidden="true"
    ></div>

    {{-- Panel: gleiches Muster. translate-x-full als statische Basis,
         Alpine schaltet !translate-x-0 additiv dazu. Das inline-Style
         gibt der Transition das ease-out-expo-Easing (0.16, 1, 0.3, 1). --}}
    <aside
        :class="open ? 'translate-x-0' : 'translate-x-full'"
        role="dialog"
        aria-modal="true"
        :aria-hidden="open ? 'false' : 'true'"
        aria-label="{{ $panelTitle }}"
        class="fixed inset-y-0 right-0 z-50 flex w-[26rem] max-w-full flex-col border-l border-line-200 bg-paper-0 shadow-floating"
        style="transition: transform 350ms cubic-bezier(0.16, 1, 0.3, 1);"
    >
        <header class="flex items-center justify-between border-b border-line-200 px-4 py-3">
            <h2 class="text-heading font-semibold text-ink-900">{{ $panelTitle }}</h2>
            <button
                type="button"
                @click="closePanel()"
                class="rounded-md p-2 text-ink-600 hover:bg-line-100 hover:text-ink-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                aria-label="{{ __('close') }}"
            >
                <x-icon name="x" size="5" />
            </button>
        </header>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            {{ $slot }}
        </div>
    </aside>
</div>
