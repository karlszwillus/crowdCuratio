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
        @php
            // Q4-Etappe 6 · G7 (2026-09-10): Bilder werden als
            // data-URI eingebettet — public_path()/setChroot()
            // sind mit Symlinks und Docker-Bind-Mounts unzuverlässig
            // (Karl-Befund: „Platzhalter statt Bild"). Base64 ist
            // langsam, aber deterministisch.
            $imageToDataUri = function ($filename) {
                if (empty($filename)) {
                    return null;
                }
                $path = storage_path('app/public/uploads/images/'.$filename);
                if (! is_file($path)) {
                    return null;
                }
                $mime = match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };

                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            };
        @endphp
        <table style="width:100%; border-collapse:separate; border-spacing:4mm 6mm; margin: 3mm 0;">
            @foreach($imgs->chunk(2) as $row)
                <tr>
                    @foreach($row as $img)
                        @php
                            $creditParts = collect([
                                optional($img->copyrightImage)->name,
                                optional($img->originImage)->name,
                            ])->filter()->implode(' · ');
                            $dataUri = $imageToDataUri($img->image);
                        @endphp
                        <td style="width:50%; vertical-align:top; padding:0;">
                            @if($dataUri !== null)
                                {{-- Feste max-Dimensionen in mm; dompdf respektiert
                                     Prozent-Breiten in Table-Cells bei grossen
                                     Original-Bildern nicht zuverlaessig und laesst
                                     die Zelle nach rechts rauslaufen. --}}
                                <img src="{{ $dataUri }}"
                                     alt="{{ $img->alt }}"
                                     style="max-width:80mm; max-height:55mm; width:auto; height:auto; display:block;">
                            @endif
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
