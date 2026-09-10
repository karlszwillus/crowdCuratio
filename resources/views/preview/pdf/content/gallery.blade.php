{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-2 (2026-09-10): PDF-Galerie. Einfaches Zwei-
Spalten-Grid über eine HTML-Tabelle (dompdf greift Tables besser
als Flexbox). Keine Anzahl-Regel, keine Sequenz-Bühne, keine
Lightbox — das PDF blättert am Papier.

Bildunterschrift plus Nachweis stehen unter jedem Bild, immer.
Der Web-Kontaktbogen darf Nachweise in die Lightbox schieben,
das PDF nicht.

Erwartet: $media (MediaContent mit ->gallery + ->gallery->images).
--}}
@if(isset($media->gallery))
    @php
        $gallery = $media->gallery;
        $imgs = $gallery->images ?? collect();
    @endphp

    @if(! empty($gallery->title) || ! empty($gallery->subtitle) || ! empty($gallery->description))
        <div style="margin: 4mm 0 3mm;">
            @if(! empty($gallery->title))<h4 style="color:#23201c; font-size:11pt; margin-bottom:1mm;">{{ strip_tags((string) $gallery->title) }}</h4>@endif
            @if(! empty($gallery->subtitle))<p style="color:#55504a; margin:0 0 2mm;">{{ strip_tags((string) $gallery->subtitle) }}</p>@endif
            @if(! empty($gallery->description))<p style="margin:0 0 3mm;">{{ strip_tags((string) $gallery->description) }}</p>@endif
        </div>
    @endif

    @if($imgs->isNotEmpty())
        <table style="width:100%; border-collapse:separate; border-spacing:4mm 6mm; margin: 3mm 0;">
            @foreach($imgs->chunk(2) as $row)
                <tr>
                    @foreach($row as $img)
                        @php
                            $creditParts = collect([
                                optional($img->copyrightImage)->name,
                                optional($img->originImage)->name,
                            ])->filter()->implode(' · ');
                        @endphp
                        <td style="width:50%; vertical-align:top; padding:0;">
                            <img src="{{ public_path('storage/uploads/images/'.$img->image) }}"
                                 alt="{{ $img->alt }}"
                                 style="width:100%; max-height:80mm; display:block;">
                            @if(! empty(trim(strip_tags((string) $img->alt))))
                                <div style="font-size:8.5pt; color:#23201c; margin-top:1.5mm; line-height:1.4;">
                                    {{ strip_tags((string) $img->alt) }}
                                </div>
                            @endif
                            @if($creditParts !== '')
                                <div style="font-family:'DejaVu Sans Mono','Courier',monospace; font-size:7.5pt; color:#736c62; margin-top:0.5mm;">
                                    {{ $creditParts }}
                                </div>
                            @endif
                        </td>
                    @endforeach
                    @if($row->count() === 1)
                        <td style="width:50%;"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif
@endif
