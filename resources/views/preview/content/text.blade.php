{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Reader-Content-Partial · Text.
Extrahiert aus preview/index.blade.php. Wird auch von der neuen
Multi-Page-View wiederverwendet.

Erwartet: $media (MediaContent mit ->text) im Kontext.
--}}
@if(isset($media->text->text))
    <div class="einspaltig">
        <p>@rich($media->text->text)</p>
    </div>
@endif
