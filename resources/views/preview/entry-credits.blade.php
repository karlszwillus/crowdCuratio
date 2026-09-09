{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G-Fund-5 (2026-09-09): Credit-Zeile am Abschnittskopf.
Aggregation der Namen pro Rolle, ergänzt um die zwei abgeleiteten
Zähler (n Abbildungen · n Quellen).

Erwartet: $entry (Entry) im Kontext.
--}}
@php
    /** @var \App\Models\Entry $entry */
    $credits = $entry->credits ?? collect();
    $researchers = $credits->where('role', 'recherche')->pluck('name')->all();
    $editors = $credits->where('role', 'redaktion')->pluck('name')->all();
    $latestDate = $credits->pluck('date')->filter()->max();

    // Zähler ableiten: Abbildungen aus allen Gallery-Blöcken, Quellen
    // aus den referenzierten Sources (analog zu entry-sources.blade).
    $imageCount = 0;
    $sourceIds = [];
    foreach ($entry->mediaContent ?? [] as $mc) {
        if ($mc->content_type === 'App\\Models\\Text' && isset($mc->text)) {
            foreach ([$mc->text->copyrightText, $mc->text->originText] as $s) { if ($s) $sourceIds[] = $s->id; }
        } elseif ($mc->content_type === 'App\\Models\\Gallery' && isset($mc->gallery)) {
            $imageCount += ($mc->gallery->images ?? collect())->count();
            foreach ($mc->gallery->images ?? [] as $img) {
                foreach ([$img->copyrightImage ?? null, $img->originImage ?? null] as $s) { if ($s) $sourceIds[] = $s->id; }
            }
        } elseif ($mc->content_type === 'App\\Models\\Audiovisual' && isset($mc->audiovisual)) {
            foreach ([$mc->audiovisual->copyrightSource ?? null, $mc->audiovisual->originSource ?? null] as $s) { if ($s) $sourceIds[] = $s->id; }
        } elseif ($mc->content_type === 'App\\Models\\QuoteBlock' && isset($mc->quoteBlock)) {
            if ($mc->quoteBlock->source) $sourceIds[] = $mc->quoteBlock->source->id;
        }
    }
    $sourceCount = count(array_unique($sourceIds));

    $parts = [];
    if (! empty($researchers)) {
        $parts[] = __('reader_credit_recherche').': '.implode(', ', $researchers);
    }
    if (! empty($editors)) {
        $parts[] = __('reader_credit_redaktion').': '.implode(', ', $editors);
    }
    if ($latestDate) {
        $parts[] = __('reader_credit_stand').' '.\Illuminate\Support\Carbon::parse($latestDate)->format('m/Y');
    }
    if ($imageCount > 0) {
        $parts[] = trans_choice('reader_credit_images', $imageCount, ['count' => $imageCount]);
    }
    if ($sourceCount > 0) {
        $parts[] = trans_choice('reader_credit_sources', $sourceCount, ['count' => $sourceCount]);
    }
@endphp
@if(! empty($parts))
    <p class="cc-entry-credit">
        {{ implode(' · ', $parts) }}
    </p>
@endif
