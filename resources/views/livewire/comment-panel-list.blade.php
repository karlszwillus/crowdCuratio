<?php

/**
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
 */

use App\Models\Project;
use App\Services\CommentRetrieve;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

/**
 * Phase 5x.9 (vorgezogen): Livewire-Container fuer das Kommentar-Panel.
 *
 * Ersetzt den frueheren Full-Page-Reload-Flow, bei dem der Klick auf ein
 * Kommentar-Icon zur URL `?comment={id}&model={type}` navigierte. Der
 * Panel-Content war dann Ergebnis des serverseitigen Rendering von
 * `projects/description.blade.php`.
 *
 * Neu: die Kommentar-Icons feuern nur noch ein Alpine-Event
 * `panel:load-and-open`, der Panel-Wrapper dispatched darauf hin
 * Livewires `comment-panel:load` mit dem commentable-Typ und der -Id.
 * Diese Komponente hoert auf das Event, laedt die Kommentare via
 * `CommentRetrieve` und rendert Karten + Composer.
 */
new class extends Component
{
    public int $projectId;

    public ?string $commentableType = null;

    public ?int $commentableId = null;

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;
    }

    /**
     * Wird vom Panel-Wrapper (oder direkt von Livewire.dispatch aus JS)
     * mit den commentable-Koordinaten aufgerufen. Setzt den State — die
     * eigentliche Datenladung passiert im Blade-Render via `with()`.
     */
    #[On('comment-panel:load')]
    public function load(string $commentableType, int $commentableId): void
    {
        $this->commentableType = $commentableType;
        $this->commentableId = $commentableId;
    }

    /**
     * Der Composer feuert nach dem Speichern `comment-added`. Wir hoeren
     * einfach zu und triggern einen Re-Render — der `with()`-Helper laedt
     * die Kommentare frisch nach. Kein Full-Reload noetig.
     */
    #[On('comment-added')]
    public function reloadOnAdded(): void
    {
        // No-op: reine Trigger-Methode fuer Livewires Re-Render.
    }

    /**
     * Der Status-Switcher aktualisiert eine Karte inline — die Liste
     * muss aber trotzdem neu rendern, weil der „Erledigte ausblenden"-
     * Filter jetzt andere Karten trifft. Ein No-Op-Handler triggert
     * den Re-Query in `with()`.
     */
    #[On('comment-status-changed')]
    public function reloadOnStatusChanged(): void
    {
        // No-op.
    }

    public function with(): array
    {
        $comments = [
            'comment' => [],
            'pathComment' => '',
            'commentableType' => $this->commentableType,
            'id' => $this->commentableId,
        ];

        if ($this->commentableType !== null && $this->commentableId !== null) {
            $comments = (new CommentRetrieve)->getComments(
                $this->commentableType,
                $this->commentableId,
            );
        }

        return [
            'comments' => $comments,
            'project' => Project::findOrFail($this->projectId),
        ];
    }
}; ?>

<div>
    @php
        use App\Support\CommentStatus;

        // Sichtbare List nur wenn Panel etwas zum Anzeigen hat.
        $hasSelection = $commentableType !== null && $commentableId !== null;
        $hasComments = $hasSelection
            && isset($comments['comment'])
            && count($comments['comment']) > 0;

        // Phase 5x.6: erledigt + abgelehnt zaehlen wir separat, damit
        // der „Erledigte anzeigen"-Toggle einen Badge mit der aktuellen
        // Zahl zeigen kann.
        $hiddenCount = 0;
        if ($hasComments) {
            foreach ($comments['comment'] as $c) {
                $status = $c['model']->status ?? null;
                if ($status instanceof CommentStatus && $status->isHiddenByDefault()) {
                    $hiddenCount++;
                }
            }
        }
    @endphp

    @if (! $hasSelection)
        {{-- Leerzustand: das Panel wurde ueber „Panel oeffnen" (keyboard
             shortcut, o. a.) ohne Kontext angezeigt. Weist auf Klick auf
             Block-Icon hin. --}}
        <p
            role="status"
            aria-live="polite"
            class="mt-2 border-l-2 border-line-200 py-2 pl-3 text-caption text-ink-500"
        >
            {{ __('comment_panel_empty_hint') }}
        </p>
    @else
        {{-- Phase 5x.6: Erledigte-Filter. showResolved wird aus dem
             sessionStorage geladen (Default: false), Aenderungen persistieren
             sich per x-effect. Kein Neuladen der Livewire-Komponente
             noetig — Alpine steuert die Sichtbarkeit auf DOM-Ebene. --}}
        <div
            x-data="{
                showResolved: JSON.parse(sessionStorage.getItem('showResolvedComments') || 'false'),
            }"
            x-effect="sessionStorage.setItem('showResolvedComments', JSON.stringify(showResolved))"
        >
            @if ($hiddenCount > 0)
                <div class="mb-3 flex items-center justify-end">
                    <button
                        type="button"
                        @click="showResolved = !showResolved"
                        :aria-pressed="showResolved"
                        :class="showResolved
                            ? 'bg-ink-900 text-paper-0 border-ink-900'
                            : 'bg-paper-0 text-ink-600 border-line-200 hover:border-ink-400'"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-caption transition-colors
                               focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <x-icon name="eye" size="3" />
                        <span x-text="showResolved ? @js(__('comment_hide_resolved')) : @js(__('comment_show_resolved'))"></span>
                        <span class="text-ink-500" :class="showResolved ? 'text-paper-0/70' : ''">
                            · {{ $hiddenCount }}
                        </span>
                    </button>
                </div>
            @endif

            @if ($hasComments)
                @foreach ($comments['comment'] as $comment)
                    @php
                        $isHiddenByDefault = ($comment['model']->status ?? null) instanceof CommentStatus
                            && $comment['model']->status->isHiddenByDefault();
                    @endphp
                    <div x-show="showResolved || {{ $isHiddenByDefault ? 'false' : 'true' }}">
                        <x-comment.card
                            :comment="$comment['model']"
                            :project="$project"
                            :replyPath="$comment['path']"
                        >
                            @if (! empty($comment['replies']))
                                @foreach ($comment['replies'] as $reply)
                                    <x-comment.card
                                        :comment="$reply['model']"
                                        :project="$project"
                                        :replyPath="$comment['path']"
                                        :isReply="true"
                                    />
                                @endforeach
                            @endif

                            <livewire:comment-composer
                                :commentableType="$comments['commentableType']"
                                :commentableId="(int) $comment['commentable_id']"
                                :projectId="$projectId"
                                :parentId="(int) $comment['id']"
                                variant="reply"
                                :key="'reply-composer-'.$comment['id']"
                            />
                        </x-comment.card>
                    </div>
                @endforeach
            @endif

            <livewire:comment-composer
                :commentableType="$commentableType"
                :commentableId="$commentableId"
                :projectId="$projectId"
                variant="full"
                :key="'root-composer-'.$commentableType.'-'.$commentableId"
            />
        </div>
    @endif
</div>
