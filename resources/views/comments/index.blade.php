<!--
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

If not, see <https://www.gnu.org/licenses/>. -->

@extends('projects.layout')

@section('content')

    @php
        use App\Support\CommentStatus;

        // Kommentar-Sammlung aus dem Controller in ein Anzeige-Array
        // eindampfen. Wir wollen im Blade nicht mehr auf $comment->status
        // (Enum) casten und auch nicht auf commentable_type raten muessen.
        $items = collect($comments ?? [])->map(function ($comment) {
            // Phase 5x-Followup nach Karls Feedback: Antworten haben
            // keinen eigenen Status — wir zeigen konsequent den Root-
            // Status. Das eager-geladene `parent` liefert ihn.
            $isReply = $comment->parent_id !== null;
            $statusSource = $isReply && $comment->parent
                ? $comment->parent
                : $comment;
            $status = $statusSource->status instanceof CommentStatus
                ? $statusSource->status
                : CommentStatus::OPEN;

            $author = trim(($comment->user->name ?? '').' '.($comment->user->last_name ?? ''));
            if ($author === '') { $author = __('comment_author_deleted'); }

            $initials = collect(explode(' ', $author))
                ->filter()
                ->take(2)
                ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
                ->implode('');

            // Block-Label: aus commentable_type ableiten (Chapter/Entry/…).
            // Bei MediaContent liegt der wahre Typ am content-Objekt.
            $type = class_basename($comment->commentable_type ?? '');
            if ($comment->commentable_type === 'App\\Models\\MediaContent'
                && isset($comment->content->content_type)) {
                $type = class_basename($comment->content->content_type);
            }

            return [
                'id' => $comment->id,
                'author' => $author,
                'initials' => $initials ?: '?',
                'projectId' => $comment->project_id,
                'projectName' => $comment->project?->name ?? '—',
                'blockType' => $type,
                'commentableType' => $comment->commentable_type,
                'commentableId' => $comment->commentable_id,
                'snippet' => \Illuminate\Support\Str::limit(strip_tags((string) $comment->comment), 120),
                'status' => $status,
                'statusKey' => $status->value,
                'statusVariant' => $status->tokenVariant(),
                'createdAt' => $comment->created_at,
                'isReply' => $isReply,
            ];
        });

        // Status-Zaehler fuer die Filter-Chips.
        // Phase 5x-Followup: die Chips zaehlen konsequent Threads
        // (Root-Kommentare), damit die Zahl semantisch mit dem
        // Rail-Badge zusammenpasst. Antworten sind Diskussionsbeitrag
        // zum Root, kein eigener Zustand — sie tauchen in der Liste
        // mit dem geerbten Status auf, werden aber nicht mitgezaehlt.
        $roots = $items->where('isReply', false);
        $counts = [
            'all' => $roots->count(),
            'open' => $roots->where('statusKey', CommentStatus::OPEN->value)->count(),
            'in_progress' => $roots->where('statusKey', CommentStatus::IN_PROGRESS->value)->count(),
            'resolved' => $roots->where('statusKey', CommentStatus::RESOLVED->value)->count(),
            'rejected' => $roots->where('statusKey', CommentStatus::REJECTED->value)->count(),
        ];

        $statusChipClass = [
            'info' => 'bg-info-bg text-info',
            'warning' => 'bg-warning-bg text-warning',
            'success' => 'bg-success-bg text-success',
            'neutral' => 'bg-line-100 text-ink-600',
        ];
    @endphp

    <div x-data="{ filter: 'all', search: '' }">

        @if ($message = Session::get('success'))
            <div class="mb-4 rounded-md border border-success-bg bg-success-bg/50 px-4 py-2 text-body text-success"
                 role="status" aria-live="polite">
                {{ $message }}
            </div>
        @endif

        {{-- Screen-Kopf: Titel + Suche. --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-title font-semibold text-ink-900">
                {{ __('comment') }}
            </h1>

            <div class="flex items-center gap-3">
                <div class="relative w-72">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-500">
                        <x-icon name="search" size="4"/>
                    </span>
                    <label for="commentSearch" class="sr-only">{{ __('search') }}</label>
                    <input
                        id="commentSearch"
                        type="search"
                        x-model="search"
                        placeholder="{{ __('search_comments') }}"
                        class="block w-full rounded-md border border-line-200 bg-paper-50 py-2 pl-9 pr-3
                               text-body text-ink-900 placeholder:text-ink-500
                               focus:border-primary focus:outline focus:outline-2 focus:outline-offset-1 focus:outline-primary"
                    />
                </div>
            </div>
        </div>

        {{-- Filter-Chips nach Status. --}}
        <div class="mb-4 flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __('status') }}">
            @foreach ([
                'all' => __('filter_all'),
                'open' => __('comment_status_open'),
                'in_progress' => __('comment_status_in_progress'),
                'resolved' => __('comment_status_resolved'),
                'rejected' => __('comment_status_rejected'),
            ] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    @click="filter = '{{ $key }}'"
                    :aria-selected="filter === '{{ $key }}'"
                    :class="filter === '{{ $key }}'
                        ? 'bg-ink-900 text-paper-0 border-ink-900'
                        : 'bg-paper-0 text-ink-500 border-line-200 hover:border-ink-400'"
                    class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-body transition-colors
                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-bar"
                >
                    <span>{{ $label }}</span>
                    <span class="text-ink-500" :class="filter === '{{ $key }}' ? 'text-paper-0/70' : ''">
                        · {{ $counts[$key] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Kommentar-Tabelle als CSS-Grid.
             Spalten: Autor:in (1.6fr) · Ort (2.4fr) · Text (2.4fr)
             · Status (1fr) · Datum (1fr) · Aktion (0.5fr). --}}
        <div class="overflow-hidden rounded-lg border border-line-200 bg-paper-0">
            <div class="grid gap-4 border-b border-line-200 bg-paper-50 px-5 py-3
                        text-mono-caps font-mono uppercase tracking-widest text-ink-500"
                 style="grid-template-columns: 2fr 3fr 3fr 1fr 1fr 1fr;"
                 role="row">
                <div>{{ __('author') }}</div>
                <div>{{ __('project') }} · {{ __('content_type') }}</div>
                <div>{{ __('comment') }}</div>
                <div>{{ __('status') }}</div>
                <div>{{ __('created_at') }}</div>
                <div class="sr-only">{{ __('actions') }}</div>
            </div>

            @if ($items->isEmpty())
                <div class="px-5 py-8 text-center text-body text-ink-500">
                    {{ __('no_comments_yet') }}
                </div>
            @else
                <ul>
                    @foreach ($items as $c)
                        <li
                            x-show="(filter === 'all' || filter === '{{ $c['statusKey'] }}')
                                    && (search === ''
                                        || '{{ addslashes($c['author']) }}'.toLowerCase().includes(search.toLowerCase())
                                        || '{{ addslashes($c['projectName']) }}'.toLowerCase().includes(search.toLowerCase())
                                        || '{{ addslashes($c['snippet']) }}'.toLowerCase().includes(search.toLowerCase()))"
                            class="grid items-center gap-4 border-b border-line-100 px-5 py-3.5
                                   last:border-b-0 hover:bg-paper-50"
                            style="grid-template-columns: 2fr 3fr 3fr 1fr 1fr 1fr;"
                            role="row"
                        >
                            {{-- Autor:in --}}
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-line-200
                                             text-caption font-semibold text-ink-700"
                                      aria-hidden="true">
                                    {{ $c['initials'] }}
                                </span>
                                <span class="min-w-0 truncate text-body font-medium text-ink-900">
                                    {{ $c['author'] }}
                                </span>
                            </div>

                            {{-- Ort: Projekt + Block-Typ. --}}
                            <div class="min-w-0">
                                <div class="truncate text-body text-ink-900">
                                    {{ $c['projectName'] }}
                                </div>
                                <div class="text-caption text-ink-500">
                                    {{ $c['blockType'] }}
                                </div>
                            </div>

                            {{-- Text-Snippet. Antworten bekommen ein Corner-Icon
                                 als visuelle Vererbungs-Kennung (Phase 5x-Followup). --}}
                            <div class="flex items-center gap-1.5 min-w-0 text-body text-ink-700">
                                @if ($c['isReply'])
                                    <x-icon
                                        name="corner-down-right"
                                        size="3"
                                        class="shrink-0 text-ink-500"
                                        title="{{ __('comment_reply_label') }}"
                                    />
                                @endif
                                <span class="truncate">{{ $c['snippet'] }}</span>
                            </div>

                            {{-- Status-Chip. --}}
                            <div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-caption font-medium
                                             {{ $statusChipClass[$c['statusVariant']] ?? $statusChipClass['neutral'] }}">
                                    {{ __('comment_status_'.$c['statusKey']) }}
                                </span>
                            </div>

                            {{-- Datum. --}}
                            <div class="text-caption text-ink-500">
                                {{ optional($c['createdAt'])->format('d.m.Y') }}
                            </div>

                            {{-- Aktion: Deep-Link zum Editor + Panel oeffnet
                                 sich per URL-Query im Ziel-Editor
                                 (Legacy-Fallback fuer Cross-Page-Navigation). --}}
                            <div class="flex items-center justify-end">
                                <a
                                    href="{{ route('projects.edit', ['project' => $c['projectId'], 'model' => $c['commentableType'], 'comment' => $c['commentableId']]) }}"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-ink-600 hover:bg-line-100 hover:text-ink-900
                                           focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                                    aria-label="{{ __('open_in_editor') }}"
                                    title="{{ __('open_in_editor') }}"
                                >
                                    <x-icon name="arrow-up-right" size="4"/>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
