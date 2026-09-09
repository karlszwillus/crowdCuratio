{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Reader-Content-Partial · Audio/Video.
Extrahiert aus preview/index.blade.php.

Erwartet: $media (MediaContent mit ->audiovisual) im Kontext.
--}}
@if(isset($media->audiovisual))
    @if($media->audiovisual->type === 'audio')
        <audio controls class="embed-responsive-item" id="audio" src="{{ route('audio', $media->audiovisual->link) }}"></audio>
    @endif
    @if($media->audiovisual->type === 'video')
        <div class="variable-width">
            <div class="inhaltbildergalerie">
                <iframe width="960px" height="400px" src="@rich($media->audiovisual->link)" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
    @endif
@endif
