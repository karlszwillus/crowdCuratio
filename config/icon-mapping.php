<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program in the file LICENSE.

If not, see <https://www.gnu.org/licenses/>.
 */

/*
|--------------------------------------------------------------------------
| Icon-Migrations-Shim: Bootstrap-Icons → Lucide (Phase 5-D.2)
|--------------------------------------------------------------------------
|
| Temporäres Mapping alter `bi-*`-Klassen auf ihre Lucide-Entsprechungen.
| Die `<x-icon>`-Blade-Komponente konsumiert dieses Mapping, damit alter
| Bestand ohne Sweep-Ganzhauswechsel weiterläuft.
|
| KEIN DAUER-ZUSTAND: Am Ende von 5-D.2 wandern alle `bi-*`-Aufrufe per
| Sweep auf `<x-icon>` mit dem semantischen Ziel-Namen, und diese Datei
| wird gelöscht. Zwei Namensräume für Icons dulden wir nicht.
|
| Konvention aus KONTEXT.md § 8 (Icon-System):
| - Größen 16/20/24 px (size-4/5/6), keine Zwischenwerte
| - Strichstärke 2 (Lucide-Default)
| - `currentColor`
| - Ein Konzept = ein Icon (Löschen ist immer `trash-2`)
|
| Quelle des Mappings: Designer-Handoff v4 § 9, ergänzt um die
| Bestands-Icons aus dem Sweep `grep -rEo 'bi-[a-z0-9-]+' resources/views/`.
*/

return [

    /*
     * Prototyp-Glyphen aus dem Handoff v4 § 9. Diese Einträge dienen
     * primär als Referenz für die Migration; wenn ein Bestandsview
     * `bi-arrow-right-circle` nutzt, das nicht 1:1 im Designer-
     * Mapping stand, wählen wir die semantisch nächstliegende
     * Lucide-Entsprechung.
     */
    'plus' => 'plus',
    'plus-circle' => 'plus-circle',
    'plus-circle-fill' => 'plus-circle',
    'grip-vertical' => 'grip-vertical',
    'pencil' => 'pencil',
    'pencil-fill' => 'pencil',
    'pencil-square' => 'square-pen',
    'three-dots' => 'ellipsis',
    'three-dots-vertical' => 'ellipsis-vertical',
    'clock-history' => 'rotate-ccw',
    'chat' => 'message-square',
    'chat-dots' => 'message-square-dots',
    'chat-dots-fill' => 'message-square-dots',
    'search' => 'search',
    'caret-down-fill' => 'chevron-down',
    'caret-right-fill' => 'chevron-right',
    'chevron-down' => 'chevron-down',
    'chevron-right' => 'chevron-right',
    'chevron-up' => 'chevron-up',
    'chevron-left' => 'chevron-left',
    'image' => 'image',
    'file-image' => 'image',
    'grid' => 'layout-grid',
    'file-earmark' => 'file-text',
    'file-earmark-pdf-fill' => 'file-text',
    'file-earmark-play' => 'play',
    'file-font' => 'type',
    'file-text' => 'file-text',
    'folder' => 'folder',
    'camera-video' => 'video',
    'play' => 'play',
    'play-circle' => 'play',
    'quote' => 'quote',
    'geo-alt' => 'map-pin',
    'lock' => 'lock',
    'unlock' => 'unlock',
    'upload' => 'upload',
    'download' => 'download',
    'check' => 'check',
    'check-circle' => 'circle-check',
    'check-circle-fill' => 'circle-check',
    'x' => 'x',
    'x-circle' => 'circle-x',
    'x-circle-fill' => 'circle-x',
    'exclamation-triangle' => 'triangle-alert',
    'exclamation-circle' => 'circle-alert',
    'info-circle' => 'info',
    'trash' => 'trash-2',
    'trash-fill' => 'trash-2',
    'save' => 'save',
    'globe' => 'globe',
    'eye' => 'eye',
    'eye-slash' => 'eye-off',
    'reply' => 'reply',
    'sun' => 'sun',
    'moon' => 'moon',

    /*
     * Handoff-v4-§9-Aliases: der Designer benennt Icons mit älteren
     * Lucide-Namen (Rename in Lucide 0.359, März 2024). Wir mappen
     * hier auf die aktuellen Namen im installierten Paket.
     */
    'check-circle-2' => 'circle-check',
    'alert-triangle' => 'triangle-alert',
    'alert-circle' => 'circle-alert',
];
