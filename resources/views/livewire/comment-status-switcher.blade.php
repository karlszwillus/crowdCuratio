<?php

use App\Models\Comment;
use App\Models\Project;
use App\Services\CommentService;
use App\Support\CommentStatus;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

/**
 * Phase 5x.4: Status-Werte kommen aus dem CommentStatus-Enum
 * (open/in_progress/resolved/rejected). Alte int-Werte aus
 * `comments.status` sind per Migration umgezogen; das Volt
 * arbeitet ausschliesslich mit den Enum-String-Werten.
 *
 * Phase 5x.3: die UI ist jetzt eine Chip-Gruppe (aria-pressed
 * pro Chip), kein <select> mehr. Farb-Token pro Status kommen
 * aus CommentStatus::tokenVariant().
 */
new class extends Component
{
    public Comment $comment;

    public Project $project;

    public string $currentStatus;

    /** @var array<string,string> DB-Wert → Label-Key */
    public array $statuses = [];

    public function mount(Comment $comment, Project $project): void
    {
        $this->comment = $comment;
        $this->project = $project;
        $this->currentStatus = $comment->status instanceof CommentStatus
            ? $comment->status->value
            : (string) $comment->status;

        $map = [];
        foreach (CommentStatus::cases() as $case) {
            $map[$case->value] = $case->value;
        }
        $this->statuses = $map;
    }

    public function updateStatus(string $newStatus, CommentService $comments): void
    {
        // Autorisierung ueber die CommentPolicy statt einer projektweiten
        // comment-Permission — der Owner darf jederzeit, die Autor:in
        // darf auf ihre eigenen Kommentare (Phase 5x-Followup).
        Gate::authorize('changeStatus', $this->comment);

        $target = CommentStatus::tryFrom($newStatus);
        if ($target === null) {
            return;
        }

        $comments->setCommentStatus($this->comment->id, $target);
        $this->currentStatus = $target->value;

        // Signal an die Panel-Liste, damit sie den frischen Status
        // rendert (inkl. Hidden-Filter + Chip-Farbe). Ohne dieses
        // Event wuerde die UI erst nach einem Reload aktualisieren.
        $this->dispatch('comment-status-changed', commentId: $this->comment->id);
    }
}; ?>

<div
    role="group"
    aria-label="{{ __('status') }}"
    class="inline-flex flex-wrap items-center gap-1"
    wire:loading.class="opacity-60"
>
    @foreach (\App\Support\CommentStatus::cases() as $case)
        @php
            $active = $currentStatus === $case->value;
            $variant = $case->tokenVariant();
        @endphp
        <button
            type="button"
            wire:click="updateStatus(@js($case->value))"
            wire:loading.attr="disabled"
            aria-pressed="{{ $active ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center rounded-full border px-2 py-0.5 text-caption font-medium transition',
                // Aktive Chip-Farben pro Variant (Design-Tokens).
                'border-info bg-info-bg text-info' => $active && $variant === 'info',
                'border-warning bg-warning-bg text-warning' => $active && $variant === 'warning',
                'border-success bg-success-bg text-success' => $active && $variant === 'success',
                'border-line-200 bg-line-100 text-ink-700' => $active && $variant === 'neutral',
                // Inaktive Chips: Umriss + Hover-Feedback.
                'border-line-200 bg-paper-0 text-ink-500 hover:bg-paper-50 hover:text-ink-700' => ! $active,
            ])
        >
            {{ __('comment_status_'.$case->value) }}
        </button>
    @endforeach
</div>
