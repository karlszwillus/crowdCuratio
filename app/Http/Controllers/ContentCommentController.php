<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\HasComments;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\QuoteBlock;
use App\Models\Text;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use App\Support\ContentTypeRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Q4-Etappe 2 / I7 (2026-08-27): Polymorpher Kommentar-Controller
 * fuer die Content-Blocks (Text / Image / Gallery). Vereinigt die
 * frueheren acht praktisch identischen Methoden im `ContentController`
 * (comment{Text,Image,Gallery} / getComment / saveComment /
 * setCommentStatus) auf drei Kern-Methoden mit ContentType-Slug via
 * `App\Support\ContentTypeRegistry`.
 *
 * Die Route-Namen bleiben unveraendert — die oeffentlichen Methoden
 * pro Type (comment*, getComment*, saveComment*, setCommentStatus*)
 * sind duenne Wrapper, die auf die polymorphen Kern-Methoden zeigen.
 * So kann ein neuer commentable Content-Type in vier Zeilen dazu
 * kommen, ohne Copy-Paste im Body.
 *
 * `listComments` und `updateCommentStatus` sind projekt-uebergreifend
 * und leben ebenfalls hier, weil sie ins gleiche Comment-Handling
 * gehoeren.
 */
class ContentCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $comments,
        // I3 (2026-08-21): CommentRetrieve aus dem Container.
        private readonly CommentRetrieve $commentRetrieve,
    ) {
        $this->middleware('auth');
    }

    // ------------------------------------------------------------------
    // Text
    // ------------------------------------------------------------------

    public function commentText(StoreCommentRequest $request): RedirectResponse
    {
        return $this->storeComment('text', $request);
    }

    public function getTextComment(int $id): JsonResponse
    {
        return $this->getComments('text', $id);
    }

    public function saveCommentText(Request $request, Text $text): RedirectResponse
    {
        return $this->saveCommentAction($text, $request, Text::class);
    }

    public function setCommentStatusText(Request $request): JsonResponse
    {
        return $this->setStatus($request);
    }

    // ------------------------------------------------------------------
    // Image
    // ------------------------------------------------------------------

    public function commentImage(StoreCommentRequest $request): RedirectResponse
    {
        return $this->storeComment('image', $request);
    }

    public function getImageComment(int $id): JsonResponse
    {
        return $this->getComments('image', $id);
    }

    public function saveCommentImage(Request $request, Image $image): RedirectResponse
    {
        return $this->saveCommentAction($image, $request, Image::class);
    }

    public function setCommentStatusImage(Request $request): JsonResponse
    {
        return $this->setStatus($request);
    }

    // ------------------------------------------------------------------
    // Gallery
    // ------------------------------------------------------------------

    public function commentGallery(StoreCommentRequest $request): RedirectResponse
    {
        return $this->storeComment('gallery', $request);
    }

    public function saveCommentGallery(Request $request, Gallery $gallery): RedirectResponse
    {
        return $this->saveCommentAction($gallery, $request, Gallery::class);
    }

    // ------------------------------------------------------------------
    // Quote — Q4-Etappe 4 / F1 (2026-09-08)
    // ------------------------------------------------------------------

    public function commentQuote(StoreCommentRequest $request): RedirectResponse
    {
        return $this->storeComment('quote', $request);
    }

    public function getQuoteComment(int $id): JsonResponse
    {
        return $this->getComments('quote', $id);
    }

    public function saveCommentQuote(Request $request, QuoteBlock $quote): RedirectResponse
    {
        return $this->saveCommentAction($quote, $request, QuoteBlock::class);
    }

    public function setCommentStatusQuote(Request $request): JsonResponse
    {
        return $this->setStatus($request);
    }

    // ------------------------------------------------------------------
    // Uebergreifend
    // ------------------------------------------------------------------

    /**
     * Listet alle Kommentare, entweder projekt-uebergreifend fuer
     * Admins oder auf die Projekte des eingeloggten Users beschraenkt.
     *
     * @return Application|Factory|View
     */
    public function listComments()
    {
        if (Auth::user()->isAdmin()) {
            // Larastan-v2 / Laravel-9-Sprung: Relation-Name ist lowercase
            // (Eloquent-Konvention), das grosse 'User' war ein silenter
            // Eager-Load-Bug — Spatie/Eloquent hat den Aufruf still
            // ignoriert, ohne dass jemand das gemerkt haette. Korrekter
            // Pfad ist `user()`, definiert in Comment.php.
            //
            // Strict-Mode: project, user und content muessen eager
            // geladen sein, weil contents.comment.blade.php auf
            // $comment->project->name, $comment->user->name und
            // $comment->content->content_type zugreift (E.7b 4a, ADR-0022).
            // Phase 5x-Followup: Antworten bleiben in der Liste (sie
            // sind eigenstaendige Beitraege), aber der Status haengt am
            // Root — deshalb eager-load `parent`, damit die View den
            // Root-Status zeigen kann statt des Antwort-eigenen Werts.
            $comments = Comment::with(['user', 'project', 'content', 'parent'])
                ->whereNotNull('project_id')
                ->get();

            return view('comments.index', compact('comments'));
        }

        $projects = Project::query()
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->leftJoin('invitations', 'invitations.project_id', '=', 'projects.id')
            ->distinct()
            ->where(function ($query) {
                $query->where('invitations.guest_id', Auth::user()->id)
                    ->orWhere('projects.user_id', Auth::user()->id);
            })
            ->whereNull('projects.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNotNull('project_id')
            ->pluck('projects.id')->toArray();

        $comments = Comment::with(['user', 'project', 'content', 'parent'])
            ->whereIn('project_id', $projects)
            ->whereNotNull('project_id')
            ->get();

        return view('comments.index', compact('comments'));
    }

    /**
     * Setzt einen Comment-Status direkt aus der URL. Funktional
     * identisch zu den setCommentStatus*-POST-Endpunkten, nur dass
     * das Frontend hier per Link-Klick statt Form arbeitet.
     */
    public function updateCommentStatus(int $id, int $status): RedirectResponse
    {
        // E.7b 4a-Hotfix-II.b: Comment laden, Project aufloesen,
        // authorize, bevor Status auf fremdem Comment geaendert wird.
        $project = $this->comments->resolveProjectForComment($id);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus($id, $status);

        return redirect()->back()->with('success', __('message_status_success'));
    }

    // ------------------------------------------------------------------
    // Polymorphe Kern-Methoden
    // ------------------------------------------------------------------

    /**
     * Neuer Top-Level-Kommentar auf einem Content-Block. Der Model-FQCN
     * wird ueber `ContentTypeRegistry::model($slug)` aufgeloest — analog
     * zum Vorgehen im `LogService` und `CommentRetrieve`.
     */
    private function storeComment(string $slug, StoreCommentRequest $request): RedirectResponse
    {
        $modelClass = ContentTypeRegistry::model($slug);
        if ($modelClass === null) {
            abort(404);
        }

        /** @var Model $model */
        $model = $modelClass::findOrFail($request->validated('id'));
        // ContentTypeRegistry liefert nur commentable Content-Modelle;
        // sie implementieren alle `HasComments`. Assert sichert das
        // explizit ab, damit PHPStan die enge Signatur von
        // `CommentService::addComment` erkennt.
        assert($model instanceof HasComments);
        // E.7b 4a-Hotfix-II.b: project-scoped Gate nachgereicht.
        $this->authorize('comment', $model);

        $this->comments->addComment($model, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * JSON-Endpoint fuer die Kommentar-Liste eines Content-Blocks.
     * Historisch geht die Comment-Zuordnung ueber `MediaContent` —
     * die Content-Blocks (Text/Image) haengen dort ueber die Pivot-
     * Tabelle.
     */
    private function getComments(string $slug, int $id): JsonResponse
    {
        $modelClass = ContentTypeRegistry::model($slug);
        if ($modelClass === null) {
            abort(404);
        }

        /** @var Model $model */
        $model = $modelClass::findOrFail($id);
        // E.7b 4a-Hotfix-II.b: Model laden + authorize.
        $this->authorize('view', $model);

        // Historisch nutzt getComments die MediaContent-ID (siehe
        // Aufrufer in chapters/index.blade.php) — bleibt unveraendert.
        return $this->commentRetrieve->getComments(MediaContent::class, $id);
    }

    /**
     * Routet eine save-Submission auf einem Content-Block (Edit/Delete/
     * Reply). Der `$class`-Parameter wird als Fallback fuer den Reply-
     * Pfad genutzt, wenn die `question`-ID nicht auf ein bestehendes
     * Modell zeigt.
     *
     * @param  class-string  $class
     */
    private function saveCommentAction(
        Model $model,
        Request $request,
        string $class,
    ): RedirectResponse {
        // E.7b 4a-Hotfix-II.b: project-scoped Gate via Model.
        $this->authorize('comment', $model);

        // Image-Sonderfall: „edit"-Aktion aus dem alten
        // saveCommentImage-Pfad. Strikte Autor-Regel per
        // CommentPolicy::update (Phase 5x.7).
        if (isset($request['name']) && $request['name'] === 'edit') {
            $comment = Comment::findOrFail((int) $request['pk']);
            $this->authorize('update', $comment);

            $this->comments->editComment((int) $request['pk'], (string) $request['value']);

            return redirect()->back()->with('success', 'Comment edited successfully');
        }

        // Reply haengt sich an das Model, das `question` referenziert.
        // Bei Edit und Delete ist das egal, der Helper greift nur bei
        // Reply auf das commentable-Modell zu.
        $commentable = isset($request['question'])
            ? ($class::find($request['question']) ?? $model)
            : $model;

        $this->comments->dispatchSaveAction($commentable, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments. Der zugehoerige Project wird
     * ueber `CommentService::resolveProjectForComment` aufgeloest, das
     * Gate laeuft gegen Project::comment. Damit ist der Content-Type
     * fuer das Auth-Handling egal.
     */
    private function setStatus(Request $request): JsonResponse
    {
        $commentId = (int) $request['id'];
        $project = $this->comments->resolveProjectForComment($commentId);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('comment', $project);

        $this->comments->setCommentStatus($commentId, (int) $request['status']);

        return response()->json(['success' => true]);
    }
}
