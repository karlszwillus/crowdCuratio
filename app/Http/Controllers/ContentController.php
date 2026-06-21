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

use App\Data\GalleryData;
use App\Data\ImageData;
use App\Data\TextData;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreImageBlockRequest;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\Project;
use App\Models\Source;
use App\Models\Text;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\TextService;
use App\Support\PermissionName;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentController extends Controller
{
    /**
     * Instantiate a new ContentController instance.
     */
    public function __construct(
        private readonly CommentService $comments,
        private readonly TextService $texts,
        private readonly ImageService $images,
        private readonly GalleryService $galleries,
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
        $text = Text::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): TextPolicy::delete.
        $this->authorize('delete', $text);
        $this->texts->destroy($text);

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_text_success'));
    }

    /**
     * Delete Image
     *
     * @return RedirectResponse
     */
    public function destroyImage(Request $request, $id)
    {
        $image = Image::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): ImagePolicy::delete.
        $this->authorize('delete', $image);
        $this->images->destroy($image);

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
        // E.7b 4a-Hotfix-II.b: Defense-in-Depth — Reader (ohne
        // globale 'edit'-Permission) kommen hier nicht durch.
        // Modell-spezifisches authorize() folgt weiter unten, wenn
        // imageId/galleryId aufgelöst sind.
        if (! $request->user()->hasPermissionTo(PermissionName::EDIT->value)) {
            abort(403);
        }

        // Translation-Pfad: setzt alt-Übersetzung + delegiert
        // Source-Übersetzungen via translateField. Bleibt vorerst
        // inline (Translation-Refactor späterer Block).
        //
        // Stakeholder-Fix Juni 2026: Härtung gegen
        // `ConvertEmptyStringsToNull` (Laravel-11-Default). `isset()`
        // auf den Request-Bag-Keys ist post-Middleware unzuverlässig
        // — Keys können present sein mit Wert `null`. `filled()`
        // testet idiomatisch "vorhanden und nicht leer/null".
        if ($request->filled('translationMode')) {
            if ($request->filled('originId')) {
                $this->translateField($request['originId'], $request['originField'], $request['isTranslated']);
            }
            if ($request->filled('copyrightId')) {
                $this->translateField($request['copyrightId'], $request['copyrightField'], $request['isTranslated']);
            }
            if ($request->filled('altField')) {
                $image = Image::findOrFail($request['imageId']);
                // E.7b 4a-Hotfix-II.b: project-scoped Gate für Image.
                $this->authorize('update', $image);
                $image->setTranslation('alt', 'en', $request['altField']);
                $image->save();
            }

            return redirect()->back()->with('success', __('message_edit_image_success'));
        }

        $request->validate([
            'copyrightImage' => 'required',
            'originImage' => 'required',
        ]);

        $data = ImageData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: siehe saveGallery — gleiches
        // Pattern, gleicher Fix. `ConvertEmptyStringsToNull` macht
        // `imageId=""` zu `null`; ohne `filled()` lief die alte
        // Bedingung in `Image::findOrFail(null)` → 404.
        if ($request->filled('imageId')) {
            $image = Image::findOrFail($request['imageId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $image);
            $newFile = $request->hasFile('newImage') ? $request->file('newImage') : null;
            $this->images->update($image, $data, $newFile);

            return redirect()->back()->with('success', __('message_edit_image_success'));
        }

        $request->validate(['image' => 'required']);

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Gallery laden + gaten,
        // weil Image dort hineingehängt wird.
        $gallery = Gallery::findOrFail((int) $request['galleryId']);
        $this->authorize('update', $gallery);

        $this->images->create($data, $request->file('image'), $gallery->id);

        return redirect()->back()->with('success', __('message_add_image_success'));
    }

    /**
     * Translate metadata
     *
     * E.7b 4a-Hotfix-II.b (2026-06-21): auf `private` reduziert.
     * Methode wurde nur intern aus saveText/saveImage aufgerufen,
     * hat keine eigene Route, aber war public — was bedeutete dass
     * sie indirekt via Reflection oder Vererbung erreichbar wäre.
     * Auth läuft jetzt vorgelagert über die Aufrufer (saveText/
     * saveImage prüfen hasPermissionTo('edit')).
     * Sources sind global geteilte Origin-/Copyright-Quellen ohne
     * Project-Bezug — ein project-scoped Gate gibt es hier nicht.
     *
     * @param  $request
     * @return $this
     */
    private function translateField($id, $field, $translated)
    {

        $source = Source::findOrFail($id);
        $source->setTranslation('name', 'en', $field);
        $source->is_translated = isset($translated) ? 1 : 0;

        $source->save();

        return $this;

    }

    /**
     * Get selected Image to be modified
     *
     * @return JsonResponse
     */
    public function editImage($id)
    {
        $image = Image::findOrFail($id);
        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Image-Daten
        // ausliefern.
        $this->authorize('view', $image);
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
        // E.7b 4a-Hotfix-II.b: Defense-in-Depth — Reader (ohne
        // globale 'edit'-Permission) kommen hier nicht durch.
        // Modell-spezifisches authorize() folgt weiter unten, wenn
        // textId/entryId aufgelöst sind.
        if (! $request->user()->hasPermissionTo(PermissionName::EDIT->value)) {
            abort(403);
        }

        // Translation-Pfad: schreibt Übersetzungen in den Body und
        // die Source-Namen, kein Body-Update via TextService. Bleibt
        // bis zur Translation-Refaktorierung (späterer Block) auf
        // den Inline-Aufrufen.
        //
        // Stakeholder-Fix Juni 2026: Härtung gegen
        // `ConvertEmptyStringsToNull` (analog saveImage), damit
        // `null`-Werte nicht in `saveTranslatedText`/`translateField`
        // mit `findOrFail(null)` enden.
        if ($request->filled('translationMode')) {
            if ($request->filled('textId')) {
                // E.7b 4a-Hotfix-II.b: project-scoped Gate.
                $text = Text::findOrFail($request['textId']);
                $this->authorize('update', $text);
                $this->saveTranslatedText($request);
            }
            if ($request->filled('originId')) {
                $this->translateField($request['originId'], $request['originField'], $request['isTranslated']);
            }
            if ($request->filled('copyrightId')) {
                $this->translateField($request['copyrightId'], $request['copyrightField'], $request['isTranslated']);
            }

            return redirect()->back()->with('success', __('message_edit_text_success'));
        }

        $request->validate([
            'contentText' => 'required',
            'copyrightText' => 'required',
            'originText' => 'required',
        ]);

        $data = TextData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: siehe saveGallery — gleiches
        // Pattern, gleicher Fix. `ConvertEmptyStringsToNull` macht
        // `textId=""` zu `null`; ohne `filled()` läuft die alte
        // Bedingung in `Text::findOrFail(null)` → 404.
        if ($request->filled('textId')) {
            $text = Text::findOrFail($request['textId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $text);
            $this->texts->update($text, $data);

            return redirect()->back()->with('success', __('message_edit_text_success'));
        }

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Entry laden + gaten,
        // weil Text dort angefügt wird.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->texts->create($data, $entry->id);

        return redirect()->back()->with('success', __('message_add_text_success'));
    }

    /**
     * get selected text to be modified
     *
     * @return JsonResponse
     */
    public function editText($id)
    {
        $text = Text::findOrFail($id);
        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Text-Daten
        // ausliefern.
        $this->authorize('view', $text);
        $data = ['id' => $text->id, 'text' => $text->text, 'origin' => $text->originText->name, 'copyright' => $text->copyrightText->name];

        return response()->json($data);
    }

    /**
     * Comment Text — neuer Top-Level-Kommentar.
     *
     * Route hat kein {text} in der URL, deshalb laden wir das
     * Modell explizit aus $request->id (siehe ProjectController).
     */
    public function commentText(StoreCommentRequest $request): RedirectResponse
    {
        $text = Text::findOrFail($request->validated('id'));
        // E.7b 4a-Hotfix-II.b: project-scoped Gate nachgereicht.
        $this->authorize('comment', $text);

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
        // E.7b 4a-Hotfix-II.b: Text laden + authorize. $id ist die
        // Text-Modell-Id (siehe Aufrufer in chapters/index.blade.php).
        $text = Text::findOrFail($id);
        $this->authorize('view', $text);

        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\MediaContent', $id);
    }

    /**
     * Routet eine save-Submission auf einem Text (Edit/Delete/Reply).
     */
    public function saveCommentText(Request $request, Text $text): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b: project-scoped Gate via Text.
        $this->authorize('comment', $text);

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
    public function commentImage(StoreCommentRequest $request): RedirectResponse
    {
        $image = Image::findOrFail($request->validated('id'));
        // E.7b 4a-Hotfix-II.b: project-scoped Gate nachgereicht.
        $this->authorize('comment', $image);

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
        // E.7b 4a-Hotfix-II.b: Image laden + authorize.
        $image = Image::findOrFail($id);
        $this->authorize('view', $image);

        $comment = new CommentRetrieve;

        return $comment->getComments('App\Models\MediaContent', $id);
    }

    /**
     * Routet eine save-Submission auf einem Image (Edit/Delete/Reply).
     */
    public function saveCommentImage(Request $request, Image $image): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b: project-scoped Gate via Image.
        $this->authorize('comment', $image);

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
    public function setCommentStatusText(Request $request): JsonResponse
    {
        // E.7b 4a-Hotfix-II.b: das `Text $text`-Argument war ohne
        // {text}-Route-Parameter ein toter Auth-Hook. Comment via
        // Request['id'] laden, Project auflösen, authorize.
        $commentId = (int) $request['id'];
        $project = $this->comments->resolveProjectForComment($commentId);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus($commentId, (int) $request['status']);

        return response()->json(['success' => true]);
    }

    /**
     * Setzt den Status eines Comments auf einem Image.
     */
    public function setCommentStatusImage(Request $request): JsonResponse
    {
        // E.7b 4a-Hotfix-II.b: analog setCommentStatusText.
        $commentId = (int) $request['id'];
        $project = $this->comments->resolveProjectForComment($commentId);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus($commentId, (int) $request['status']);

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
        // E.7b 4a-Hotfix-II.b: project-scoped Gate.
        $this->authorize('update', $model);

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
            //
            // Strict-Mode: project, user und content müssen eager
            // geladen sein, weil contents.comment.blade.php auf
            // $comment->project->name, $comment->user->name und
            // $comment->content->content_type zugreift (E.7b 4a, ADR-0022).
            $comments = Comment::with(['user', 'project', 'content'])
                ->whereNotNull('project_id')
                ->get();

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

        $comments = Comment::with(['user', 'project', 'content'])
            ->whereIn('project_id', $projects)
            ->whereNotNull('project_id')
            ->get();

        return view('contents.comment', compact('comments'));
    }

    /**
     * Save translation text
     *
     * @return $this
     */
    private function saveTranslatedText(Request $request)
    {
        // E.7b 4a-Hotfix-II.b: auf `private` reduziert. Wird nur
        // intern aus saveText() aufgerufen; saveText prüft vorab
        // hasPermissionTo('edit') + project-scoped Gate auf $text.

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
        // E.7b 4a-Hotfix-II.b: Comment laden, Project auflösen,
        // authorize, bevor Status auf fremdem Comment geändert wird.
        $project = $this->comments->resolveProjectForComment((int) $id);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus((int) $id, (int) $status);

        return redirect()->back()->with('success', __('message_status_success'));
    }

    public function saveGallery(Request $request)
    {
        // E.7b 4a-Hotfix-II.b: Defense-in-Depth — Reader-Gate.
        if (! $request->user()->hasPermissionTo(PermissionName::EDIT->value)) {
            abort(403);
        }

        $data = GalleryData::fromRequest($request);

        // Stakeholder-Fix Juni 2026: vorher
        // `isset($request['galleryId']) && $request['galleryId'] !== ''`.
        // Seit dem Laravel-11-Sprung (Phase 3 / Block F) ist die
        // Middleware `ConvertEmptyStringsToNull` Default-Bestandteil
        // der `web`-Gruppe — sie schreibt leere Hidden-Inputs (Neu-
        // anlage: `galleryId=""`) zu `null` um. Die alte Bedingung
        // war dafür blind (`null !== ''` ist `true`), lief in den
        // Update-Pfad und liess `Gallery::findOrFail(null)` als 404
        // rendern. `$request->filled('galleryId')` ist die idiomatische
        // Laravel-Form: true, wenn Input present UND nicht leer/null.
        if ($request->filled('galleryId')) {
            $gallery = Gallery::findOrFail($request['galleryId']);
            // E.7b 4a-Hotfix-II.b: project-scoped Gate.
            $this->authorize('update', $gallery);
            $this->galleries->update($gallery, $data);

            return redirect()->back()->with('success', __('message_update_success'));
        }

        // E.7b 4a-Hotfix-II.b: Create-Pfad — Entry laden + gaten.
        $entry = Entry::findOrFail((int) $request['entryId']);
        $this->authorize('update', $entry);

        $this->galleries->create($data, $entry->id);

        return redirect()->back()->with('success', __('message_gallery_success'));
    }

    /**
     * Get gallery
     *
     * @return JsonResponse
     */
    public function editGallery($id)
    {

        $gallery = Gallery::where('id', $id)->first();

        if ($gallery === null) {
            abort(404);
        }

        // E.7b 4a-Hotfix-II.b: JSON-API darf keine fremden Gallery-
        // Daten ausliefern.
        $this->authorize('view', $gallery);

        return \response()->json($gallery);
    }

    /**
     * Destroy gallery
     *
     * @return RedirectResponse
     */
    public function destroyGallery(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        // Block E.7b Sub-Welle 3 (ADR-0022): GalleryPolicy::delete.
        $this->authorize('delete', $gallery);
        $this->galleries->destroy($gallery);

        return redirect('projects/'.$request->project.'/edit')->with('success', __('message_delete_text_success'));
    }

    /**
     * Routet eine save-Submission auf einer Gallery (Edit/Delete/Reply).
     */
    public function saveCommentGallery(Request $request, Gallery $gallery): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b: project-scoped Gate via Gallery.
        $this->authorize('comment', $gallery);

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
    public function commentGallery(StoreCommentRequest $request): RedirectResponse
    {
        $gallery = Gallery::findOrFail($request->validated('id'));
        // E.7b 4a-Hotfix-II.b: project-scoped Gate nachgereicht.
        $this->authorize('comment', $gallery);

        $this->comments->addComment($gallery, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }
}
