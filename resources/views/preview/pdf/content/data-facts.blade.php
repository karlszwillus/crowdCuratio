{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 6 · G7-2 (2026-09-10): PDF-Daten- und -Fakten-Block.
Zwei Layouts nach `layout`-Feld:
  - `steckbrief`: DL mit label:value-Zeilen (Rows als Array).
  - `tabelle`:    HTML-Table mit Kopfzeile aus columns[], Zellen
                  aus rows[].

Print-optimiert: kleinere Fonts, feste Rahmenlinien, keine
Radien/Schatten.

Erwartet: $media (MediaContent mit ->dataFactBlock) im Kontext.
--}}
@if(isset($media->dataFactBlock))
    @php
        /** @var \App\Models\DataFactBlock $block */
        $block = $media->dataFactBlock;
        $rows = is_array($block->rows) ? $block->rows : (json_decode((string) $block->rows, true) ?: []);
        $columns = is_array($block->columns) ? $block->columns : (json_decode((string) $block->columns, true) ?: []);
    @endphp
    <div style="margin: 4mm 0 6mm;">
        @if(! empty(trim((string) $block->title)))
            <h4 style="color:#23201c; font-size:11pt; margin-bottom:1mm;">{{ strip_tags((string) $block->title) }}</h4>
        @endif
        @if(! empty(trim((string) $block->subtitle)))
            <p style="color:#55504a; margin:0 0 3mm; font-size:9pt;">{{ strip_tags((string) $block->subtitle) }}</p>
        @endif

        @if($block->isTabellenLayout())
            <table style="width:100%; border-collapse: collapse; font-size: 9pt;">
                @if(! empty($columns))
                    <thead>
                        <tr>
                            @foreach($columns as $col)
                                <th style="text-align:left; border-bottom: 0.75pt solid #23201c; padding: 1.5mm 2mm;
                                           font-family: 'DejaVu Sans','Helvetica',sans-serif; font-size: 8pt;
                                           text-transform: uppercase; letter-spacing: 0.6pt; color: #55504a;">
                                    {{ $col['label'] ?? '' }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            @foreach($columns as $col)
                                <td style="border-bottom: 0.4pt solid #ddd6c9; padding: 1.5mm 2mm; color: #23201c;">
                                    {{ $row[$col['key'] ?? ''] ?? '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            {{-- Steckbrief: DL-Ähnliche Zwei-Spalten-Tabelle. --}}
            <table style="width:100%; border-collapse: collapse; font-size: 9.5pt;">
                @foreach($rows as $row)
                    <tr>
                        <td style="width: 32%; padding: 1mm 2mm 1mm 0;
                                   font-family: 'DejaVu Sans','Helvetica',sans-serif;
                                   font-weight: bold; color: #55504a; vertical-align: top;">
                            {{ $row['label'] ?? '' }}
                        </td>
                        <td style="padding: 1mm 0; color: #23201c; vertical-align: top;">
                            {{ $row['value'] ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endif
