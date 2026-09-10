{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-2 (2026-09-10): PDF-Audio/Video — bewusst KEIN
Player-Ersatz-Frame, sondern eine kompakte Hinweiszeile mit
Icon-Character und Link. Der Handoff will explizit „Video → URL",
nicht einen leeren Rahmen mit Play-Button auf Papier.

Erwartet: $media (MediaContent mit ->audiovisual) im Kontext.
--}}
@if(isset($media->audiovisual))
    @php
        $av = $media->audiovisual;
        $url = trim((string) ($av->url ?? ''));
        $isVideo = ! empty($av->type) && str_contains(strtolower($av->type), 'video');
        $glyph = $isVideo ? '▶' : '♪';
        $label = $isVideo ? __('audiovisual_pdf_video_hint') : __('audiovisual_pdf_audio_hint');
        $credit = collect([
            optional($av->copyrightSource)->name,
            optional($av->originSource)->name,
        ])->filter()->implode(' · ');
    @endphp
    <div style="margin: 3mm 0 5mm; padding: 3mm 4mm; border-left: 1.5pt solid #ddd6c9; background: #faf8f4;">
        <div style="font-family:'DejaVu Sans','Helvetica',sans-serif; font-size: 9pt; color: #55504a;">
            <span style="font-size: 12pt; color: #23201c;">{{ $glyph }}</span>
            <span style="margin-left: 2mm;">{{ $label }}</span>
        </div>
        @if(! empty(trim((string) $av->name)))
            <div style="margin-top: 1.5mm; font-weight: bold;">{{ $av->name }}</div>
        @endif
        @if($url !== '')
            <div style="font-family:'DejaVu Sans Mono','Courier',monospace; font-size: 8.5pt; color: #23201c; margin-top: 1mm; word-break: break-all;">
                {{ $url }}
            </div>
        @endif
        @if($credit !== '')
            <div style="font-family:'DejaVu Sans Mono','Courier',monospace; font-size: 7.5pt; color: #736c62; margin-top: 1mm;">
                {{ $credit }}
            </div>
        @endif
    </div>
@endif
