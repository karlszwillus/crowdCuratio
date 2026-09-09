{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / E1b (2026-09-09): Kapitel-Opener.
Handoff-Vorgabe: Mono „KAPITEL 02" 13/.16em in Akzent, Linie,
rechts „N Abschnitte", Titel 40 px, Lead 20 px im Lesemaß,
Unterkante 1px --rule.

Erwartet: $chapter (Chapter), $chapterIndex (int 0-basiert) im Kontext.
--}}
@php
    $entryCount = isset($chapter->entries) ? $chapter->entries->count() : 0;
@endphp
<header class="cc-chapter-opener">
    <div class="cc-chapter-opener__meta">
        <span>{{ __('reader_chapter_prefix') }} {{ str_pad((string) (($chapterIndex ?? 0) + 1), 2, '0', STR_PAD_LEFT) }}</span>
        <span>{{ trans_choice('reader_chapter_entries_count', $entryCount, ['count' => $entryCount]) }}</span>
    </div>
    <h2>{{ $chapter->name }}</h2>
    @if(! empty(trim(strip_tags((string) $chapter->description))))
        <p class="cc-chapter-opener__lead">{{ Str::limit(strip_tags((string) $chapter->description), 240) }}</p>
    @endif
</header>
