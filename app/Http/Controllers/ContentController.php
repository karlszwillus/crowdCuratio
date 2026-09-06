<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

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

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Der `ContentController` war ein
 * 784-LoC-Container fuer alles, was Content-Blocks (Text/Image/Gallery)
 * betraf — CRUD, Reset, sechs Kommentar-Methoden, Autocomplete, tote
 * Translation-Endpunkte. Der Split zerlegt ihn in vier neue Controller:
 *
 *  - `TextBlockController`       — saveText, editText, destroyText, resetText
 *  - `ImageBlockController`      — saveImage, editImage, destroyImage
 *  - `GalleryBlockController`    — reorderImages, dropImage, saveGallery,
 *                                   editGallery, destroyGallery
 *  - `ContentCommentController`  — polymorphe Kommentar-Endpunkte
 *                                   (comment / getComment / saveComment /
 *                                    setCommentStatus), listComments,
 *                                   updateCommentStatus
 *
 * Der `SourceTranslationService` haelt die frueher duplizierte
 * `translateField`-Logik zentral, damit beide Text- und Image-Save-
 * Pfade dieselbe Source-Translation-Logik nutzen.
 *
 * Was in dieser Klasse bleibt: die Source-Autocomplete-AJAX. Der Endpunkt
 * ist thematisch content-uebergreifend (liest die geteilte `sources`-
 * Tabelle), passt aber weder in einen Block-Controller noch in den
 * Kommentar-Controller. Kandidat fuer I11 (AJAX-Prefix bündeln) und
 * evtl. spaeter fuer einen dedizierten SourceController, wenn C0
 * (Quellen projekt-scopen) durch ist.
 */
class ContentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Ajax autocomplete fuer die Source-Suche im alten Modify-Modal.
     * Wird vom Live-Search-Feld in `contents/text.blade.php`,
     * `contents/image.blade.php` und `contents/gallery.blade.php`
     * gerufen. Neue Volt-Sicht `source-picker` nutzt einen eigenen
     * Suchpfad ueber das Model.
     *
     * @return array<int, string>
     */
    public function autocomplete(Request $request): array
    {
        $data = [];
        $res = Source::where('name', 'like', '%'.$request->input('query').'%')
            ->where('type', '=', $request->input('type'))
            ->get(['id', 'name']);

        foreach ($res as $key => $value) {
            $data[$key] = $value->name;
        }

        return $data;
    }
}
