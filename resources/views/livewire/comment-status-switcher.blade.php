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

    public int $currentStatus;

    /** @var array<int|string,string> */
    public array $statuses = [];

    public function mount(Comment $comment, Project $project): void
    {
        $this->comment = $comment;
        $this->project = $project;
        $this->currentStatus = (int) $comment->status;

        /** @var array<int|string,string> $statuses */
        $statuses = (array) config('project.comment', []);
        $this->statuses = $statuses;
    }

    public function updateStatus(int $newStatus, CommentService $comments): void
    {
        Gate::authorize('comment', $this->project);

        if (! array_key_exists((string) $newStatus, $this->statuses)) {
            return;
        }

        $comments->setCommentStatus($this->comment->id, $newStatus);
        $this->currentStatus = $newStatus;
    }
}; ?>

<div>
    <select
        wire:change="updateStatus($event.target.value)"
        wire:loading.attr="disabled"
        class="form-control"
        style="width:auto;"
        aria-label="{{ __('status') }}"
    >
        @foreach ($statuses as $key => $label)
            <option value="{{ $key }}" @selected((int) $key === $currentStatus)>
                {{ __($label) }}
            </option>
        @endforeach
    </select>
</div>
