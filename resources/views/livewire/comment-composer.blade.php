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

use App\Contracts\HasComments;
use App\Models\Project;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

/**
 * Phase 5x.8: Composer fuer neue Kommentare und Antworten.
 *
 * Ersetzt zwei Legacy-Konstrukte in projects/description.blade.php:
 *  - den Top-Level-<form id="frmComment"> am Ende der Sidebar
 *  - den Reply-<form class="reply reply_{id}"> innerhalb jeder Karte
 *
 * Modi:
 *  - variant="full"  → immer offene Textarea (Top-Level-Composer)
 *  - variant="reply" → kollabiert, Toggle-Button "Antworten" oeffnet
 *                      die Textarea inline unter der Karte
 *
 * Berechtigung: Gate('comment', $project). Ohne Recht zeigt der
 * Composer die Leser-Hinweiszeile (aria-live), damit klar ist,
 * warum keine Eingabe erscheint.
 */
new class extends Component
{
    /** FQCN des commentable-Modells (z. B. App\Models\Chapter). */
    public string $commentableType;

    public int $commentableId;

    public int $projectId;

    public ?int $parentId = null;

    /** 'full' → offen, 'reply' → toggelbarer Antwort-Button. */
    public string $variant = 'full';

    #[Validate('required|string|min:1|max:5000')]
    public string $body = '';

    public bool $open = true;

    public bool $canComment = false;

    public function mount(): void
    {
        $project = Project::findOrFail($this->projectId);
        $this->canComment = auth()->user()?->can('comment', $project) ?? false;
        $this->open = $this->variant === 'full';
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        if (! $this->open) {
            $this->body = '';
            $this->resetErrorBag('body');
        }
    }

    public function save(CommentService $comments): void
    {
        $project = Project::findOrFail($this->projectId);
        Gate::authorize('comment', $project);

        $this->validate();

        /** @var HasComments $commentable */
        $commentable = $this->commentableType::findOrFail($this->commentableId);

        // CommentService erwartet die klassischen Request-Keys aus den
        // Legacy-Formularen. Wir bauen einen leichten Request nur mit
        // dem, was addComment/replyToComment lesen.
        $request = new Request;
        $request->setUserResolver(fn () => auth()->user());
        $request->merge([
            'comment' => $this->body,
            'reply' => $this->body,
            'projectId' => $this->projectId,
            'IdProjectComment' => $this->projectId,
            'commentId' => $this->parentId,
        ]);

        if ($this->parentId !== null) {
            $comments->replyToComment($commentable, $request);
        } else {
            $comments->addComment($commentable, $request);
        }

        $this->body = '';
        if ($this->variant === 'reply') {
            $this->open = false;
        }

        // Signal fuer die Sidebar / Zaehler-Badges (5x.10). Solange
        // die Sidebar noch serverseitig aus dem Controller kommt, muss
        // der User einmal navigieren, damit der neue Kommentar
        // erscheint — der Vollausbau der Live-Liste kommt in 5x.9.
        $this->dispatch('comment-added', parentId: $this->parentId);
    }
};

?>

<div>
    @if (! $canComment)
        {{-- Leser-Hinweiszeile: klar sagen, warum kein Feld erscheint. --}}
        <p
            role="status"
            aria-live="polite"
            class="mt-2 border-l-2 border-line-200 py-2 pl-3 text-xs text-ink-500"
        >
            {{ __('comment_reader_hint') }}
        </p>
    @elseif ($variant === 'reply' && ! $open)
        <button
            type="button"
            wire:click="toggle"
            class="mt-2 inline-flex items-center gap-1 text-caption text-primary hover:underline"
        >
            <x-icon name="corner-down-right" size="3" />
            {{ __('reply') }}
        </button>
    @else
        <form
            wire:submit="save"
            @class([
                // variant=full sitzt bereits im Panel-Chrome — kein
                // eigener Rahmen/Padding, damit es nicht wie „Panel
                // im Panel" wirkt. variant=reply sitzt innerhalb einer
                // Karte, dort trennt der Rahmen den Composer sichtbar
                // ab.
                'rounded-lg border border-line-200 bg-paper-0 p-3 mt-2' => $variant === 'reply',
                'mt-2' => $variant === 'full',
            ])
        >
            @php
                $composerId = 'composer-'.($parentId ?? 'root-'.$commentableId);
            @endphp

            <label class="sr-only" for="{{ $composerId }}">
                {{ $parentId ? __('reply') : __('add_comment') }}
            </label>
            <textarea
                id="{{ $composerId }}"
                wire:model="body"
                rows="3"
                class="w-full rounded-md border border-line-200 p-2 text-body text-ink-900 focus:border-primary focus:outline-none"
                placeholder="{{ __('leave_comment') }}"
                @error('body') aria-invalid="true" aria-describedby="{{ $composerId }}-error" @enderror
            ></textarea>

            @error('body')
                <p
                    id="{{ $composerId }}-error"
                    class="mt-1 text-caption text-danger"
                    role="alert"
                >
                    {{ $message }}
                </p>
            @enderror

            <div class="mt-2 flex items-center justify-end gap-2">
                @if ($variant === 'reply')
                    <button
                        type="button"
                        wire:click="toggle"
                        class="text-caption text-ink-600 hover:underline"
                    >
                        {{ __('cancel') }}
                    </button>
                @endif
                <x-ui.button
                    type="submit"
                    variant="primary"
                    size="sm"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">{{ __('save') }}</span>
                    <span wire:loading wire:target="save">{{ __('saving') }}</span>
                </x-ui.button>
            </div>
        </form>
    @endif
</div>
