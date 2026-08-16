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

    // Projekt aus commentable aufloesen + Panel-Query fuer Deep-Link.
    // Phase 5x.9-Follow-up: der Link uebergibt jetzt `model` und
    // `comment` als Query-Params, damit das Kommentar-Panel im Editor
    // beim Load automatisch fuer den richtigen Block oeffnet
    // (URL-Trigger, siehe <x-layout.comment-panel>).
    $target = $comment->commentable;
    $project = null;
    $breadcrumb = '—';
    $anchor = null;
    $panelType = null;
    $panelId = null;

    if ($target instanceof \App\Models\Project) {
        $project = $target;
        $breadcrumb = $target->name;
        $panelType = \App\Models\Project::class;
        $panelId = $target->id;
    } elseif ($target instanceof \App\Models\Chapter) {
        $project = $target->project ?? null;
        $breadcrumb = trim(($project?->name ?? '').' › '.$target->name, ' ›');
        $anchor = 'anchor_Chapter_'.$target->id;
        $panelType = \App\Models\Chapter::class;
        $panelId = $target->id;
    } elseif ($target instanceof \App\Models\Entry) {
        $chapter = $target->chapter ?? null;
        $project = $chapter?->project ?? null;
        $parts = array_filter([$project?->name, $chapter?->name, $target->name]);
        $breadcrumb = implode(' › ', $parts);
        $anchor = 'anchor_Entry_'.$target->id;
        $panelType = \App\Models\Entry::class;
        $panelId = $target->id;
    } elseif ($target !== null && method_exists($target, 'project')) {
        // Text/Image/Gallery/Audiovisual: Projekt via project()-Kette.
        $project = $target->project();
        $breadcrumb = $project?->name ?? '—';
        $panelType = $comment->commentable_type;
        $panelId = $comment->commentable_id;
    }

    $link = $project
        ? route('projects.edit', ['project' => $project->id, 'model' => $panelType, 'comment' => $panelId])
            .($anchor ? '#'.$anchor : '')
        : '#';

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
                {{-- Comment nutzt $timestamps = false — created_at
                     kommt als String aus der DB und wird nicht als
                     Carbon gecastet. Defensiv per Carbon::parse
                     wrappen. --}}
                @php
                    $createdAt = $comment->created_at;
                    if (is_string($createdAt) && $createdAt !== '') {
                        try {
                            $createdAt = \Illuminate\Support\Carbon::parse($createdAt);
                        } catch (\Throwable $e) {
                            $createdAt = null;
                        }
                    }
                @endphp
                {{ $createdAt?->diffForHumans() }}
            </span>
        </div>
        <p class="mt-0.5 text-body text-ink-600"
           style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
            {{ $text }}
        </p>
    </div>
</a>
