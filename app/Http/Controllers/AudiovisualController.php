<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022 - berlinHistory e.V.

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

namespace App\Http\Controllers;

use App\Http\Requests\StoreAudiovisualRequest;
use App\Models\Audiovisual;
use App\Models\MediaContent;
use App\Services\CommentService;
use App\Traits\UploadTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AudiovisualController extends Controller
{
    use UploadTrait;

    /**
     * Instantiate a new AudioVisualController instance.
     */
    public function __construct(
        private readonly CommentService $comments,
    ) {
        $this->middleware('auth');
    }

    public function index() {}

    /**
     * Store or update audiovisual
     *
     * NF-SEC-201: Signatur auf `StoreAudiovisualRequest`. MIME-
     * Whitelist (audio/mpeg, audio/mp4, audio/wav, audio/ogg,
     * audio/x-m4a) und 20-MB-Limit greifen vor dem Methoden-Body.
     *
     * @return RedirectResponse
     */
    public function store(StoreAudiovisualRequest $request)
    {

        if ($request->has('audio')) {
            $request['link'] = $this->uploadAudio($request);
        } else {
            $request['link'] = ($this->youtubeID($request['link'])) ? 'https://www.youtube.com/embed/'.$this->youtubeID($request['link']) : $request['link'];
        }

        if (isset($request['audiovisualId']) && $request['audiovisualId'] != '') {
            $model = Audiovisual::findOrFail($request['audiovisualId']);

            if ($request['translationMode']) {
                if (! is_null($request['link'])) {
                    $model->setTranslation('link', 'en', $request['link']);
                }
                $model->setTranslation('copyright', 'en', $request['copyright']);
                $model->setTranslation('source', 'en', $request['source']);
                $model->is_translated = isset($request['isTranslated']) ? 1 : 0;

            } else {

                if (! is_null($request['link'])) {
                    $model->link = $request['link'];
                }
                if (! is_null($request['type'])) {
                    $model->type = $request['type'];
                }
                if (! is_null($request['copyright'])) {
                    $model->copyright = $request['copyright'];
                }
                if (! is_null($request['source'])) {
                    $model->source = $request['source'];
                }
            }

            $model->save();

            return redirect()->back()->with('success', __('message_update_success'));
        }

        $item = Audiovisual::create($this->mapData($request));
        $this->attachMedia($item->id, $request['entryId'], 'App\Models\Audiovisual');

        return redirect()->back()->with('success', __('message_add_success'));
    }

    /**
     * Delete audiovisual
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, $id)
    {

        // Detach from media content
        MediaContent::where('media_content_id', $id)->where('media_contentable_type', 'App\Models\Audiovisual')->delete();

        // delete content
        Audiovisual::where('id', $id)->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_success'));

    }

    /**
     * Map incoming data
     *
     * @return array
     */
    protected function mapData($request)
    {

        $data = [];

        if (isset($request['link'])) {
            $data['link'] = $request['link'];
        }
        if (isset($request['source'])) {
            $data['source'] = $request['source'];
        }
        if (isset($request['copyright'])) {
            $data['copyright'] = $request['copyright'];
        }
        if (isset($request['type'])) {
            $data['type'] = $request['type'];
        }

        return $data;

    }

    /**
     * Attach audiovisual to media content
     *
     * @return mixed
     */
    public function attachMedia($id, $entry, $type)
    {
        // get last position
        $position = MediaContent::where('media_contentable_id', $entry)->orderBy('position', 'desc')->first();

        $pos = 0;
        if (! empty($position->position)) {
            $pos = $position->position;
        }

        return MediaContent::create(
            [
                'position' => $pos + 1,
                'media_content_id' => $id,
                'media_contentable_id' => $entry,
                'media_contentable_type' => $type,
            ]
        );
    }

    /**
     * Upload audio
     *
     * NF-SEC-201: Filename ist ab sofort durchgängig Server-generiert
     * (`Str::random(10)`). Vorher lief der Pfad zuerst über
     * `getClientOriginalName()` und überschrieb den Namen erst am
     * Ende — der Zwischenwert war ein Path-Traversal-Vektor (analog
     * NF-SEC-007 für Logo-Uploads). Kein Client-Input mehr im
     * Dateinamen, keine Original-Extension (Browser-MIME-Routing
     * läuft über den Content-Type-Header des Storage-Disks).
     *
     * @return string
     */
    protected function uploadAudio($request)
    {
        $folder = '/uploads/audio/';
        $audio = null;

        if ($request->has('audio')) {
            $audio = $request->file('audio');
        } elseif ($request->has('newImage')) {
            $audio = $request->file('newImage');
        }

        if ($audio === null) {
            return '';
        }

        $name = Str::random(10);
        $this->uploadOne($audio, $folder, 'public', $name);

        return $name;
    }

    /**
     * Routet eine save-Submission auf einem Audiovisual
     * (Edit/Delete/Reply).
     */
    public function saveCommentAudiovisual(Request $request, Audiovisual $audiovisual): RedirectResponse
    {
        $commentable = isset($request['question'])
            ? (Audiovisual::find($request['question']) ?? $audiovisual)
            : $audiovisual;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Neuer Top-Level-Kommentar auf einem Audiovisual.
     *
     * Route hat kein {audiovisual} in der URL, deshalb laden wir
     * das Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentAudiovisual(Request $request): RedirectResponse
    {
        $request->validate(['comment' => 'required']);

        $audiovisual = Audiovisual::findOrFail($request->id);
        $this->comments->addComment($audiovisual, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Get youtube video ID
     *
     * @return false|mixed
     */
    protected function youtubeID($url)
    {
        if (strlen($url) > 11) {
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
                return $match[1];
            } else {
                return false;
            }
        }

        return $url;
    }
}
