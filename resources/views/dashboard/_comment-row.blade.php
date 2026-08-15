@php
    /**
     * Kommentar-Vorschau-Zeile fuer das Dashboard (Screen 09).
     * Konsumiert: $comment — App\Models\Comment mit user, commentable
     *
     * Layout: Avatar 30px · Autor:in · Brotkrume · Zeit rechts,
     * darunter Kommentartext mit line-clamp: 2. Ohne Textauszug muss
     * man klicken, um zu wissen, ob einen der Kommentar angeht.
     */
    $author = $comment->user;
    $authorName = trim(($author->name ?? '').' '.($author->last_name ?? ''));
    $initials = collect(explode(' ', $authorName))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('') ?: '?';

    // Brotkrume aus commentable.
    $target = $comment->commentable;
    $breadcrumb = '—';
    $link = '#';

    if ($target instanceof \App\Models\Project) {
        $breadcrumb = $target->name;
        $link = route('projects.edit', $target->id);
    } elseif ($target instanceof \App\Models\Chapter && $target->project) {
        $breadcrumb = $target->project->name.' › '.$target->name;
        $link = route('projects.edit', $target->project->id).'#anchor_Chapter_'.$target->id;
    } elseif ($target instanceof \App\Models\Entry && $target->chapter) {
        $chapter = $target->chapter;
        $project = $chapter->project ?? null;
        if ($project) {
            $breadcrumb = $project->name.' › '.$chapter->name.' › '.$target->name;
            $link = route('projects.edit', $project->id).'#anchor_Entry_'.$target->id;
        }
    }

    $text = trim(strip_tags((string) ($comment->comment ?? $comment->body ?? '')));
@endphp

<a href="{{ $link }}"
   class="flex items-start gap-3 border-b border-[color:var(--color-line-100)] px-5 py-3 last:border-b-0
          hover:bg-paper-50
          focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar">

    <span class="mt-0.5 flex size-[30px] shrink-0 items-center justify-center rounded-full bg-line-200
                 text-caption font-semibold text-ink-700"
          aria-hidden="true">
        {{ $initials }}
    </span>

    <div class="min-w-0 flex-1">
        <div class="flex items-baseline justify-between gap-3">
            <div class="min-w-0 flex items-baseline gap-2">
                <span class="text-body font-medium text-ink-900">{{ $authorName }}</span>
                <span class="min-w-0 truncate text-caption text-ink-500">{{ $breadcrumb }}</span>
            </div>
            <span class="shrink-0 text-caption text-ink-500">
                {{ $comment->created_at?->diffForHumans() }}
            </span>
        </div>
        <p class="mt-0.5 text-body text-ink-600"
           style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
            {{ $text }}
        </p>
    </div>
</a>
