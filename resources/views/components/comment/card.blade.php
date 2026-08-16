@props([
    'comment',
    'project',
    'isReply' => false,
    // Reply-Formular-Route-Name (z. B. 'comment.save', 'comment.entry.save', ...).
    // Optional. Ohne Route wird der Reply-Toggle ausgeblendet.
    'replyPath' => null,
])

@php
    /** @var \App\Models\Comment $comment */
    /** @var \App\Models\Project $project */

    $status = $comment->status instanceof \App\Support\CommentStatus
        ? $comment->status
        : \App\Support\CommentStatus::OPEN;

    $author = trim(($comment->user->name ?? '').' '.($comment->user->last_name ?? ''));
    if ($author === '') {
        $author = __('comment_author_deleted');
    }

    $user = auth()->user();
    // Alle Berechtigungs-Checks laufen durch CommentPolicy (Phase 5x.7).
    // Die Karte ist ein reines Praesentations-Bauteil — die Policy bleibt
    // Autoritaet, wir spiegeln nur die UI.
    $canEditText    = $user?->can('update', $comment) ?? false;
    $canChangeState = ! $isReply && ($user?->can('changeStatus', $comment) ?? false);
    $canDelete      = $user?->can('delete', $comment) ?? false;
    $canReply       = ! $isReply
        && $replyPath !== null
        && ($user?->can('reply', $comment) ?? false);

    // Erledigte + Abgelehnte werden per Default zurueckgenommen (5x.6 kommt
    // spaeter), hier schon visuell markiert.
    $muted = $status->isHiddenByDefault();
@endphp

<article
    aria-label="{{ __('comment') }}"
    data-comment-id="{{ $comment->id }}"
    data-comment-status="{{ $status->value }}"
    @class([
        'rounded-lg border p-4',
        'mb-3' => ! $isReply,
        // Reply-Karten: kein linker Margin mehr — die Einrueckung + Linie
        // kommen aus dem Threading-Container im Slot des Root-Kommentars
        // (Phase 5x.5).
        'border-line-100 bg-paper-50' => $isReply,
        'border-line-200 bg-paper-0' => ! $isReply,
        'opacity-70' => $muted,
    ])
>
    <header class="flex items-baseline justify-between gap-3 mb-2">
        <div class="flex items-baseline gap-2 min-w-0">
            <span class="text-sm font-medium text-ink-900 truncate">{{ $author }}</span>
            <time class="text-xs text-ink-500" datetime="{{ $comment->created_at?->toIso8601String() }}">
                {{ optional($comment->created_at)->format('d.m.Y H:i') }}
            </time>
        </div>

        @if ($canChangeState)
            <livewire:comment-status-switcher
                :comment="$comment"
                :project="$project"
                :key="'comment-status-'.$comment->id"
            />
        @elseif (! $isReply)
            {{-- Leser sehen den Status als reinen Chip, ohne Steuerung. --}}
            <span @class([
                'inline-flex items-center rounded-full px-2 py-0.5 text-caption font-medium',
                'bg-info-bg text-info' => $status->tokenVariant() === 'info',
                'bg-warning-bg text-warning' => $status->tokenVariant() === 'warning',
                'bg-success-bg text-success' => $status->tokenVariant() === 'success',
                'bg-line-100 text-ink-600' => $status->tokenVariant() === 'neutral',
            ])>
                {{ __('comment_status_'.$status->value) }}
            </span>
        @endif
    </header>

    <div class="text-sm text-ink-800">
        @if ($canEditText)
            <livewire:comment-text-editor
                :comment="$comment"
                :project="$project"
                :key="'comment-text-'.$comment->id"
            />
        @else
            {{ $comment->comment }}
        @endif
    </div>

    {{-- Phase 5x.8: der frühere enable-reply-Anchor ist entfallen —
         der Composer (<livewire:comment-composer variant="reply">) im
         Slot bringt seinen eigenen Toggle-Button mit. Der Footer traegt
         jetzt nur noch aktions-fremde Kommentar-Signale (aktuell: Loeschen). --}}
    @if ($canDelete && $replyPath !== null)
        <footer class="mt-3 flex items-center gap-4 text-caption">
            <form
                action="{{ route($replyPath, $comment->id) }}"
                method="POST"
                class="inline"
                onsubmit="return confirm(@js(__('comment_delete_confirm')))"
            >
                @csrf
                <input type="hidden" name="btn_submit" value="delete">
                <input type="hidden" name="id" value="{{ $comment->id }}">
                <button
                    type="submit"
                    class="inline-flex items-center gap-1 text-danger hover:underline"
                >
                    <x-icon name="trash-2" size="3" />
                    {{ __('delete') }}
                </button>
            </form>
        </footer>
    @endif

    {{-- Threading-Container (Phase 5x.5): Antwort-Karten UND der Reply-
         Composer sitzen im Slot. Wir wickeln sie in einen Container mit
         linkem Border, damit visuell klar ist, dass alles hier zur Antwort-
         Kette dieses Kommentars gehoert. Nur Root-Karten (nicht isReply)
         haben diesen Container, Reply-Karten reichen ihn nicht weiter. --}}
    @if (! $isReply && trim($slot) !== '')
        <div class="mt-3 ml-4 border-l-2 border-line-200 pl-4">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</article>
