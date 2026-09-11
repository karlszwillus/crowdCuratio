{{--
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

Q4-Etappe 5 / G2 (2026-09-08): Reader-Content-Partial · Daten/Fakten.
Extrahiert aus preview/index.blade.php.

Layout-spezifisches Rendering:
  - `steckbrief` → <dl> (Label · Wert)
  - `tabelle`    → <table> (Spalten-Header + N-Cell-Zeilen)

Erwartet: $media (MediaContent mit ->dataFactBlock) im Kontext.
--}}
@if(isset($media->dataFactBlock))
    @php
        $b = $media->dataFactBlock;
        $locale = app()->getLocale();
        $factRows = $b->rows ?? [];
        $factColumns = $b->columns ?? [];
    @endphp
    <div class="einspaltig daten-fakten daten-fakten--{{ $b->layout ?? 'steckbrief' }}">
        @if(! empty($b->title))
            <h3>{{ $b->title }}</h3>
        @endif
        @if(! empty($b->subtitle))
            <p class="subtitle">{{ $b->subtitle }}</p>
        @endif
        @if(! empty(trim(strip_tags((string) $b->description))))
            <div class="daten-fakten__intro">@rich($b->description)</div>
        @endif
        @if($b->isTabellenLayout())
            @if(! empty($factColumns) && ! empty($factRows))
                <table>
                    <thead>
                        <tr>
                            @foreach($factColumns as $col)
                                <th>{{ is_array($col['header'] ?? null) ? ($col['header'][$locale] ?? $col['header']['de'] ?? '') : (string) ($col['header'] ?? '') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($factRows as $row)
                            <tr>
                                @php $cells = $row['cells'] ?? []; @endphp
                                @foreach($factColumns as $ci => $col)
                                    @php $cell = $cells[$ci] ?? ''; @endphp
                                    <td>{{ is_array($cell) ? ($cell[$locale] ?? $cell['de'] ?? '') : (string) $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @else
            @if(! empty($factRows))
                <dl>
                    @foreach($factRows as $row)
                        @php
                            $label = is_array($row['label'] ?? null) ? ($row['label'][$locale] ?? $row['label']['de'] ?? '') : (string) ($row['label'] ?? '');
                            $value = is_array($row['value'] ?? null) ? ($row['value'][$locale] ?? $row['value']['de'] ?? '') : (string) ($row['value'] ?? '');
                        @endphp
                        @if($label !== '' || $value !== '')
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        @endif
                    @endforeach
                </dl>
            @endif
        @endif
    </div>
@endif
