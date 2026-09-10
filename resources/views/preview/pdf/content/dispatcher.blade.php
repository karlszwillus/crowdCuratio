{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-1 (2026-09-10): Content-Type-Dispatcher für das
PDF. Wählt anhand von $media->content_type das passende Partial.
Parallel zum Web-Dispatcher (preview.content.dispatcher), aber
mit eigenen reduzierten Partials.

Erwartet: $media (MediaContent) im Kontext.
--}}
@if(isset($media->content_type))
    @switch($media->content_type)
        @case('App\Models\Text')
            @include('preview.pdf.content.text', ['media' => $media])
            @break
        @case('App\Models\Gallery')
            @include('preview.pdf.content.gallery', ['media' => $media])
            @break
        @case('App\Models\Audiovisual')
            @include('preview.pdf.content.audiovisual', ['media' => $media])
            @break
        @case('App\Models\QuoteBlock')
            @include('preview.pdf.content.quote', ['media' => $media])
            @break
        @case('App\Models\DataFactBlock')
            @include('preview.pdf.content.data-facts', ['media' => $media])
            @break
    @endswitch
@endif
