{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-2 (2026-09-10): PDF-Zitat. Blockquote sw mit
Akzent-Kante, Nachweiszeile als kleiner Chip darunter. Optionaler
Originaltext (bei Übersetzungen) in einer eigenen kleinen Zeile.

Erwartet: $media (MediaContent mit ->quoteBlock) im Kontext.
--}}
@if(isset($media->quoteBlock))
    @php
        /** @var \App\Models\QuoteBlock $quote */
        $quote = $media->quoteBlock;
        $bodyText = strip_tags((string) $quote->text);
        $original = trim(strip_tags((string) ($quote->original_text ?? '')));
        $source = $quote->source;
    @endphp
    @if(! empty($bodyText))
        <blockquote style="margin: 5mm 0; padding: 1mm 0 1mm 6mm; border-left: 1.5pt solid #a8392f;
                           font-size: 11pt; line-height: 1.55; color: #23201c;">
            <p style="margin: 0 0 2mm;">{{ $bodyText }}</p>
            @if($source)
                <div style="font-family: 'DejaVu Sans Mono','Courier',monospace; font-size: 8pt;
                            letter-spacing: 0.5pt; color: #736c62; margin-top: 2mm;">
                    {{ $source->name }}
                    @if(! empty(trim((string) $source->title))) · {{ $source->title }} @endif
                    @if(! empty(trim((string) $source->holding))) · {{ $source->holding }} @endif
                </div>
            @endif
            @if($original !== '')
                <div style="font-family: 'DejaVu Sans','Helvetica',sans-serif; font-size: 8.5pt;
                            color: #55504a; margin-top: 2mm; font-style: italic;">
                    {{ $original }}
                </div>
            @endif
        </blockquote>
    @endif
@endif
