{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Content-Type-Dispatcher für den
Web-Reader. Wählt anhand von $media->content_type das passende
Partial. Ein einziger Aufrufer in preview/index.blade.php (und
später in preview/multi-page/chapter.blade.php).

Erwartet: $media (MediaContent) im Kontext.
--}}
@if(isset($media->content_type))
    @switch($media->content_type)
        @case('App\Models\Text')
            @include('preview.content.text', ['media' => $media])
            @break
        @case('App\Models\Gallery')
            @include('preview.content.gallery', ['media' => $media])
            @break
        @case('App\Models\Audiovisual')
            @include('preview.content.audiovisual', ['media' => $media])
            @break
        @case('App\Models\QuoteBlock')
            @include('preview.content.quote', ['media' => $media])
            @break
        @case('App\Models\DataFactBlock')
            @include('preview.content.data-facts', ['media' => $media])
            @break
    @endswitch
@endif
