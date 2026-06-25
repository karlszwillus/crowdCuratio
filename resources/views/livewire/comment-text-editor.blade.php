<?php

use App\Models\Comment;
use App\Models\Project;
use App\Services\CommentService;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

new class extends Component
{
    public Comment $comment;

    public Project $project;

    public string $text = '';

    public bool $editing = false;

    public function mount(Comment $comment, Project $project): void
    {
        $this->comment = $comment;
        $this->project = $project;
        $this->text = $this->resolveDisplayText();
    }

    public function startEdit(): void
    {
        Gate::authorize('comment', $this->project);
        $this->text = $this->resolveDisplayText();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->text = $this->resolveDisplayText();
    }

    public function save(CommentService $comments): void
    {
        Gate::authorize('comment', $this->project);

        $value = trim($this->text);
        if ($value === '') {
            $this->editing = false;

            return;
        }

        $comments->editComment($this->comment->id, $value);
        $this->comment->refresh();
        $this->text = $this->resolveDisplayText();
        $this->editing = false;
    }

    /**
     * Comment-Text liegt im Bestand als JSON-Translatable. Wir zeigen
     * die aktuelle Locale; CommentService::editComment kapselt das
     * locale-richtige Schreiben.
     */
    private function resolveDisplayText(): string
    {
        $raw = $this->comment->comment;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $locale = app()->getLocale();

                return (string) ($decoded[$locale] ?? reset($decoded) ?? '');
            }

            return $raw;
        }

        if (is_array($raw)) {
            $locale = app()->getLocale();

            return (string) ($raw[$locale] ?? reset($raw) ?? '');
        }

        return '';
    }
}; ?>

<div class="comment-text-editor">
    @if ($editing)
        <div class="flex flex-col gap-2">
            <textarea
                wire:model="text"
                wire:keydown.enter.prevent="save"
                wire:keydown.escape="cancelEdit"
                rows="3"
                class="form-control w-full"
                aria-label="{{ __('comment') }}"
                autofocus
            ></textarea>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="btn btn-primary btn-sm"
                >
                    {{ __('save') }}
                </button>
                <button
                    type="button"
                    wire:click="cancelEdit"
                    class="btn btn-default btn-sm"
                >
                    {{ __('cancel') }}
                </button>
            </div>
        </div>
    @else
        <button
            type="button"
            wire:click="startEdit"
            class="block cursor-text rounded-md px-2 py-1 text-left text-body text-ink-900 hover:bg-ink-400/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            aria-label="{{ __('edit') }}"
        >
            {{ $text !== '' ? $text : __('comment') }}
        </button>
    @endif
</div>
