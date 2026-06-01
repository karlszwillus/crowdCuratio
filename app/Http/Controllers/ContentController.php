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

use App\Http\Requests\StoreImageBlockRequest;
use App\Models\Audiovisual;
use App\Models\Comment;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Traits\UploadTrait;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    use UploadTrait;

    /**
     * Instantiate a new ContentController instance.
     */
    public function __construct(
        private readonly CommentService $comments,
    ) {
        $this->middleware('auth');
    }

    /**
     * Delete Text
     *
     * @return RedirectResponse
     */
    public function destroyText(Request $request, $id)
    {
        // Detach media
        $this->detachMedia($id, 'App\Models\Text');

        $text = Text::find($id);
        $text->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_text_success'));
    }

    /**
     * Detach media from entry
     *
     *
     * @return mixed
     */
    public function detachMedia($id, $type)
    {

        Comment::where('commentable_id', $id)->where('commentable_type', $type)->update(['deleted_at' => now()]);

        return MediaContent::where('media_contentable_id', $id)
            ->where('media_contentable_type', $type)
            ->update(['deleted_at' => now()]);
    }

    /**
     * Delete Image
     *
     * @return RedirectResponse
     */
    public function destroyImage(Request $request, $id)
    {
        $image = Image::find($id);
        $image->delete();

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_image_success'));
    }

    /**
     * Ajax autocomplete
     *
     * @return array
     */
    public function autocomplete(Request $request)
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

    /**
     * Save or update image
     *
     * NF-SEC-201: Signatur auf `StoreImageBlockRequest` umgestellt.
     * MIME-Whitelist (jpeg/jpg/png/gif/webp) und 4-MB-Limit für
     * `image` und `newImage` greifen jetzt schon vor dem Methoden-
     * Body. Inhaltliche Logik (Translation-/Create-/Update-Modus)
     * bleibt — Zerlegung in dedizierte Endpoints ist Phase-4-Material.
     *
     * @return RedirectResponse
     */
    public function saveImage(StoreImageBlockRequest $request)
    {

        if (isset($request['translationMode'])) {
            if (isset($request['originId'])) {
                $this->translateField($request['originId'], $request['originField'], $request['isTranslated']);
            }

            if (isset($request['copyrightId'])) {
                $this->translateField($request['copyrightId'], $request['copyrightField'], $request['isTranslated']);
            }

            if (isset($request['altField'])) {
                $image = Image::findOrFail($request['imageId']);
                $image->setTranslation('alt', 'en', $request['altField']);
                $image->save();
            }

            return redirect()->back()->with('success', __('message_edit_image_success'));
        }

        $request->validate(
            [
                'copyrightImage' => 'required',
                'originImage' => 'required',
            ]
        );

        if (isset($request['imageId']) && $request['imageId'] != '') {
            $this->updateImage($request);

            return redirect()->back()->with('success', __('message_edit_image_success'));
        } else {
            $request->validate(
                [
                    'image' => 'required',
                ]
            );

            $name = '';
            // Check if an image should be uploaded
            $name = $this->setImage($request);
            $position = Image::where('gallery_id', $request['galleryId'])->orderBy('position', 'desc')->pluck('position')->first();

            // Set copyright value
            $copyright = $this->getSource($request['copyrightImage'], 'Copyright');

            // Set origin value
            $origin = $this->getSource($request['originImage'], 'Origin');

            $im = Image::firstOrCreate(
                [
                    'gallery_id' => $request['galleryId'],
                    'image' => $name,
                    'position' => $position + 1,
                    'origin' => $origin,
                    'copyright' => $copyright,
                    'url' => Storage::path($name),
                    'alt' => $request['altText'],
                ]
            );

            // Attach image to gallery
            // $this->attachMedia($im->id, $request['entryId'], 'App\Models\Image');

            // Attach gallery to entry
            // $this->attachMedia($im->id, $request['entryId'], 'App\Models\Image');

            return redirect()->back()->with('success', __('message_add_image_success'));
        }
    }

    /**
     * Update image
     *
     * @return $this
     */
    public function updateImage(Request $request)
    {
        $image = Image::find($request['imageId']);

        // Set copyright value
        $copyright = $this->getSource($request['copyrightImage'], 'Copyright');

        // Set origin value
        $origin = $this->getSource($request['originImage'], 'Origin');

        if (isset($request['newImage']) && ! is_null($request['newImage'])) {
            // Check if an image should be uploaded
            $name = $this->setImage($request);
            $image->image = $name;
            $image->url = Storage::path($name);
        }

        $image->origin = $origin;
        $image->copyright = $copyright;
        $image->updated_at = now();

        if (isset($request['altText'])) {
            $image->alt = $request['altText'];
        }

        $image->save();

        return $this;
    }

    /**
     * Translate metadata
     *
     * @param  $request
     * @return $this
     */
    public function translateField($id, $field, $translated)
    {

        $source = Source::findOrFail($id);
        $source->setTranslation('name', 'en', $field);
        $source->is_translated = isset($translated) ? 1 : 0;

        $source->save();

        return $this;

    }

    /**
     * Get or create a Source row of the given type for the given value.
     *
     * Returns the id of a matching Source if one exists, otherwise
     * inserts a new row (with the translated `name` payload) and
     * returns the new id.
     *
     * Duplicate of ProjectController::getSource — both will be lifted
     * into a Source service in the Phase-4 refactor.
     *
     * @return int
     */
    protected function getSource($value, $type)
    {
        $sources = Source::where('type', $type)->get();

        foreach ($sources as $source) {
            if ($source->name == $value) {
                return $source->id;
            }
        }

        return Source::insertGetId(['name' => json_encode([app()->getLocale() => $value]), 'type' => $type, 'created_at' => now()]);
    }

    /**
     * Set Image
     *
     * @return string
     */
    protected function setImage(Request $request)
    {
        $name = '';
        $image = '';

        // Define folder path
        $folder = '/uploads/images/';

        if ($request->has('image')) {
            // Get image file
            $image = $request->file('image');

            // Make a image name based on user name and current timestamp
            $name = date('Ymd').'_'.time().'.'.$request->file('image')->extension();
        }

        if ($request->has('newImage')) {
            // Get image file
            $image = $request->file('newImage');

            // Make a image name based on user name and current timestamp
            $name = date('Ymd').'_'.time().'.'.$request->file('newImage')->extension();
        }

        if ($name != '' && $image != '') {
            $this->uploadOne($image, $folder, 'public', $name);
        }

        return $name;
    }

    /**
     * Attach media to entry
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
     * Get selected Image to be modified
     *
     * @return JsonResponse
     */
    public function editImage($id)
    {
        $image = Image::findOrFail($id);
        $data = ['id' => $image->id, 'image' => $image->image, 'url' => $image->url, 'alt' => $image->alt, 'origin' => $image->originImage->name, 'copyright' => $image->copyrightImage->name];

        return response()->json($data);
    }

    /**
     * Save or update text
     *
     * @return RedirectResponse
     */
    public function saveText(Request $request)
    {

        if (isset($request['translationMode'])) {
            if (isset($request['textId'])) {
                $this->saveTranslatedText($request);
            }
            if (isset($request['originId'])) {
                $this->translateField($request['originId'], $request['originField'], $request['isTranslated']);
            }

            if (isset($request['copyrightId'])) {
                $this->translateField($request['copyrightId'], $request['copyrightField'], $request['isTranslated']);
            }

            return redirect()->back()->with('success', __('message_edit_text_success'));
        }

        $request->validate(
            [
                'contentText' => 'required',
                'copyrightText' => 'required',
                'originText' => 'required',
            ]
        );

        if (isset($request['textId']) && $request['textId'] != '') {
            $this->updateText($request);

            return redirect()->back()->with('success', __('message_edit_text_success'));
        } else {
            // Set copyright value
            $copyright = $this->getSource($request['copyrightText'], 'Copyright');

            // Set origin value
            $origin = $this->getSource($request['originText'], 'Origin');

            // filter text before saving
            $strClean = str_replace(['<script>', '</script>'], ['', ''], $request['contentText']);

            $id = Text::insertGetId(
                [
                    'text' => json_encode([app()->getLocale() => $strClean]),
                    'origin' => $origin,
                    'copyright' => $copyright,
                    'created_at' => now(),
                ]

            );

            // Attach text to entry
            $this->attachMedia($id, $request['entryId'], 'App\Models\Text');

            return redirect()->back()->with('success', __('message_add_text_success'));
        }
    }

    /**
     * Update text
     *
     * @return $this
     */
    public function updateText(Request $request)
    {
        // Set copyright value
        $copyright = $this->getSource($request['copyrightText'], 'Copyright');

        // Set origin value
        $origin = $this->getSource($request['originText'], 'Origin');

        // filter text before saving
        $strClean = str_replace(['<script>', '</script>'], ['', ''], $request['contentText']);

        $text = Text::find($request['textId']);
        $text->text = $strClean;
        $text->origin = $origin;
        $text->copyright = $copyright;
        $text->updated_at = now();
        $text->is_translated = isset($request['isTranslatedText']) ? 1 : 0;
        $text->save();

        return $this;
    }

    /**
     * get selected text to be modified
     *
     * @return JsonResponse
     */
    public function editText($id)
    {
        $text = Text::findOrFail($id);
        $data = ['id' => $text->id, 'text' => $text->text, 'origin' => $text->originText->name, 'copyright' => $text->copyrightText->name];

        return response()->json($data);
    }

    /**
     * Comment Text — neuer Top-Level-Kommentar.
     *
     * Route hat kein {text} in der URL, deshalb laden wir das
     * Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentText(Request $request): RedirectResponse
    {
        $request->validate(['comment' => 'required']);

        $text = Text::findOrFail($request->id);
        $this->comments->addComment($text, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Retrieve all comment of current text
     *
     * @return JsonResponse
     */
    public function getTextComment($id)
    {
        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\MediaContent', $id);
    }

    /**
     * Routet eine save-Submission auf einem Text (Edit/Delete/Reply).
     */
    public function saveCommentText(Request $request, Text $text): RedirectResponse
    {
        // Reply hängt sich an das Text-Modell, das `question` referenziert.
        // Bei Edit und Delete ist das egal, der Helper greift nur bei Reply
        // auf das commentable-Modell zu.
        $commentable = isset($request['question'])
            ? (Text::find($request['question']) ?? $text)
            : $text;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Comment Image — neuer Top-Level-Kommentar.
     *
     * Route hat kein {image} in der URL, deshalb laden wir das
     * Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentImage(Request $request): RedirectResponse
    {
        $request->validate(['comment' => 'required']);

        $image = Image::findOrFail($request->id);
        $this->comments->addComment($image, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Retrieve all comment of current image
     *
     * @return JsonResponse
     */
    public function getImageComment($id)
    {
        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\MediaContent', $id);
    }

    /**
     * Routet eine save-Submission auf einem Image (Edit/Delete/Reply).
     */
    public function saveCommentImage(Request $request, Image $image): RedirectResponse
    {
        if (isset($request['name']) && $request['name'] === 'edit') {
            $this->comments->editComment((int) $request['pk'], (string) $request['value']);

            return redirect()->back()->with('success', 'Comment edited successfully');
        }

        $commentable = isset($request['question'])
            ? (Image::find($request['question']) ?? $image)
            : $image;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments auf einem Text.
     */
    public function setCommentStatusText(Request $request, Text $text): JsonResponse
    {
        $this->comments->setCommentStatus((int) $request['id'], (int) $request['status']);

        return response()->json(['success' => true]);
    }

    /**
     * Setzt den Status eines Comments auf einem Image.
     */
    public function setCommentStatusImage(Request $request, Image $image): JsonResponse
    {
        $this->comments->setCommentStatus((int) $request['id'], (int) $request['status']);

        return response()->json(['success' => true]);
    }

    /**
     * Reset text
     *
     * @return RedirectResponse
     */
    public function resetText(Request $request)
    {
        $model = Text::findOrFail($request['idReset']);
        $model->text = $request['valueReset'];

        $model->save();

        return redirect()->back()->with('success', 'Text reset successfully');
    }

    /**
     * List all comments
     *
     * @return Application|Factory|View
     */
    public function listComments()
    {

        if (Auth::user()->isAdmin()) {
            // Larastan-v2 / Laravel-9-Sprung: Relation-Name ist lowercase
            // (Eloquent-Konvention), das große 'User' war ein silenter
            // Eager-Load-Bug — Spatie/Eloquent hat den Aufruf still
            // ignoriert, ohne dass jemand das gemerkt hätte. Korrekter
            // Pfad ist `user()`, definiert in Comment.php.
            $comments = Comment::with('user')->whereNotNull('project_id')->get();

            return view('contents.comment', compact('comments'));
        }

        $projects = Project::query()
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->leftJoin('invitations', 'invitations.project_id', '=', 'projects.id')
            ->distinct()
            ->Where(function ($query) {
                $query->where('invitations.guest_id', Auth::user()->id)
                    ->orWhere('projects.user_id', Auth::user()->id);
            })
            ->whereNull('projects.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNotNull('project_id')
            ->pluck('projects.id')->toArray();

        $comments = Comment::whereIn('project_id', $projects)->whereNotNull('project_id')->get();

        return view('contents.comment', compact('comments'));
    }

    /**
     * Save translation text
     *
     * @return $this
     */
    public function saveTranslatedText(Request $request)
    {

        $text = Text::findOrFail($request['textId']);

        // filter text before saving
        if ($request['text'] != 'undefined') {
            $strClean = str_replace(['<script>', '</script>'], ['', ''], $request['text']);
            $text->setTranslation('text', 'en', $strClean);
        }
        $text->is_translated = isset($request['isTranslated']) ? 1 : 0;
        $text->save();

        return $this;
    }

    /**
     * Setzt einen Comment-Status direkt aus der URL. Funktional
     * identisch zu den setCommentStatus*-POST-Endpunkten der
     * anderen Controller, nur dass das Frontend hier per
     * Link-Klick statt Form arbeitet.
     */
    public function updateCommentStatus($id, $status): RedirectResponse
    {
        $this->comments->setCommentStatus((int) $id, (int) $status);

        return redirect()->back()->with('success', __('message_status_success'));
    }

    public function saveGallery(Request $request)
    {

        if (isset($request['galleryId']) && $request['galleryId'] != '') {
            $gallery = Gallery::findOrFail($request['galleryId']);

            if (isset($request['translationGallery'])) {
                $gallery->setTranslation('title', 'en', $request['galleryTitle']);
                $gallery->setTranslation('subtitle', 'en', $request['gallerySubtitle']);
                $gallery->setTranslation('description', 'en', $request['galleryDescription']);
            } else {

                $gallery->title = $request['title'];
                $gallery->subtitle = $request['subtitle'];
                $gallery->description = $request['description'];

            }

            $gallery->is_translated = isset($request['isTranslated']) ? 1 : 0;
            $gallery->save();

            return redirect()->back()->with('success', __('message_update_success'));
        }

        $gallery = Gallery::create($this->mapData($request));
        $this->attachMedia($gallery->id, $request['entryId'], 'App\Models\Image');

        return redirect()->back()->with('success', __('message_gallery_success'));

    }

    /**
     * Mapping request
     *
     * @return array
     */
    protected function mapData($data)
    {

        $result = [];

        if (isset($data['entryId']) && $data['entryId'] != '') {

            $result['entryId'] = $data['entryId'];

        }

        if (isset($data['title'])) {
            $result['title'] = $data['title'];
        }
        if (isset($data['subtitle'])) {
            $result['subtitle'] = $data['subtitle'];
        }
        if (isset($data['description'])) {
            $result['description'] = $data['description'];
        }

        return $result;
    }

    /**
     * Get gallery
     *
     * @return JsonResponse
     */
    public function editGallery($id)
    {

        $gallery = Gallery::where('id', $id)->first();

        return \response()->json($gallery);
    }

    /**
     * Destroy gallery
     *
     * @return RedirectResponse
     */
    public function destroyGallery(Request $request, $id)
    {

        // Detach media
        $this->detachMedia($id, 'App\Models\Gallery');

        // delete from image
        DB::table('images')->where('gallery_id', '=', $id)->update(['deleted_at' => now()]);

        DB::table('galleries')->where('id', '=', $id)->update(['deleted_at' => now()]);

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_text_success'));
    }

    /**
     * Routet eine save-Submission auf einer Gallery (Edit/Delete/Reply).
     */
    public function saveCommentGallery(Request $request, Gallery $gallery): RedirectResponse
    {
        $commentable = isset($request['question'])
            ? (Gallery::find($request['question']) ?? $gallery)
            : $gallery;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Neuer Top-Level-Kommentar auf einer Gallery.
     *
     * Route hat kein {gallery} in der URL, deshalb laden wir das
     * Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentGallery(Request $request): RedirectResponse
    {
        $request->validate(['comment' => 'required']);

        $gallery = Gallery::findOrFail($request->id);
        $this->comments->addComment($gallery, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }
}
