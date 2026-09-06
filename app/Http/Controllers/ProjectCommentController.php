<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2022, 2026 - berlinHistory e.V.

See LICENSE.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Project;
use App\Services\CommentRetrieve;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Q4-Etappe 2 / I6 (2026-08-27): Kommentar-Endpunkte fuer Projekte,
 * extrahiert aus dem `ProjectController`. Vier Methoden nach dem
 * gleichen Muster wie ChapterCommentController / EntryCommentController
 * — ein spaeterer polymorpher `ContentCommentController` (I7) kann
 * diese Klasse mit ersetzen.
 *
 * Alle vier Endpunkte gaten gegen `comment` auf dem Projekt. Der
 * Security-Sweep-III (2026-06-22) hat die alten Route-Model-Binding-
 * Fallen (Route hat kein `{project}`, nur `{id}`) hier geschlossen.
 */
class ProjectCommentController extends Controller
{
    public function __construct(
        private readonly CommentService $comments,
        // I3 (2026-08-21): Constructor-Injection.
        private readonly CommentRetrieve $commentRetrieve,
    ) {
        $this->middleware('auth');
    }

    /**
     * Neuer Top-Level-Kommentar am Projekt.
     *
     * Route hat kein {project} in der URL, deshalb resolved Laravel
     * das Project-Argument nicht — wir laden es explizit aus
     * $request->id, wie der alte CommentTrait das auch tat.
     */
    public function commentProject(StoreCommentRequest $request): RedirectResponse
    {
        $project = Project::findOrFail($request->validated('id'));
        $this->authorize('comment', $project);
        $this->comments->addComment($project, $request);

        return redirect()->back()->with('success', 'Reply to comment added successfully');
    }

    /**
     * Alle Kommentare am aktuellen Projekt.
     */
    public function getProjectComment(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $this->authorize('comment', $project);

        return $this->commentRetrieve->getComments(Project::class, $id);
    }

    /**
     * Routet eine save-Submission (Edit/Delete/Reply).
     *
     * Security-Sweep-III (2026-06-22): vorher toter Route-Model-Binding
     * (Route hat kein {project}, nur {id}). Jetzt: Project via
     * $request->route('id') laden, authorize('comment').
     */
    public function saveCommentProject(Request $request): RedirectResponse
    {
        $project = Project::findOrFail($request->route('id'));
        $this->authorize('comment', $project);

        $this->comments->dispatchSaveAction($project, $request);

        return redirect()->back()->with('success', 'Comment-Aktion ausgeführt');
    }

    /**
     * Setzt den Status eines Comments auf einem Projekt.
     *
     * Security-Sweep-III (2026-06-22): vorher toter Route-Model-Binding.
     * Comment via Request-id laden, Project via
     * CommentService::resolveProjectForComment aufloesen, authorize
     * gegen das Project — analog zu setCommentStatus{Chapter,Entry,
     * Text,Image}.
     */
    public function setCommentStatusProject(Request $request): JsonResponse
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
