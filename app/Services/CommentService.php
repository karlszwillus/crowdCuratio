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

namespace App\Services;

use App\Contracts\HasComments;
use App\Models\Audiovisual;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Gallery;
use App\Models\Image;
use App\Models\MediaContent;
use App\Models\Project;
use App\Models\Text;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Kapselt die Schreibpfade auf Comments — Hinzufügen, Antworten,
 * Editieren, Löschen und Status-Setzen. Plus den Save-Switch-Helper,
 * der heute in sieben Controller-Methoden dupliziert über die fünf
 * Comment-tragenden Controller verteilt ist.
 *
 * Ersetzt die fünf Methoden aus dem alten CommentTrait, der auf
 * acht Modellen klebte. Die `comments()`-MorphMany-Relation lebt
 * bereits direkt in den Modellen, deshalb kann der Trait nach
 * dieser Welle ersatzlos entfallen.
 *
 * Read-Pfad liegt in `CommentRetrieve`-Service (bestand schon).
 */
class CommentService
{
    /**
     * Legt einen neuen Top-Level-Kommentar an einem commentable-
     * Modell an (Project, Chapter, Entry, Text, Image, Gallery,
     * Audiovisual, MediaContent).
     *
     * Erwartet aus dem Request:
     *  - comment            (Body)
     *  - IdProjectComment   (Project-FK für Filter-Pfade in der UI)
     */
    public function addComment(HasComments $commentable, Request $request): void
    {
        $comment = new Comment;
        $comment->comment = $request->comment;
        $comment->project_id = $request->IdProjectComment;
        $comment->status = 1;
        $comment->created_at = now();
        $comment->user()->associate($request->user());

        $commentable->comments()->save($comment);
    }

    /**
     * Hängt eine Antwort an einen bestehenden Comment.
     *
     * Erwartet aus dem Request:
     *  - reply       (Body)
     *  - projectId   (Project-FK)
     *  - commentId   (Parent)
     *  - question    (commentable-ID — `class::find($question)`)
     */
    public function replyToComment(HasComments $commentable, Request $request): void
    {
        $reply = new Comment;
        $reply->comment = $request->reply;
        $reply->project_id = $request->projectId;
        $reply->user()->associate($request->user());
        $reply->parent_id = $request->commentId;
        $reply->created_at = now();

        $commentable->comments()->save($reply);
    }

    /**
     * Aktualisiert den Body eines bestehenden Comments. Schreibt
     * den Body als de-Lokalisierung — das Frontend liefert ein
     * Plain-String, der Translation-Pfad serialisiert ihn nach
     * `{"de": "..."}`.
     */
    public function editComment(int $commentId, string $body): void
    {
        Comment::where('id', $commentId)
            ->update(['comment' => json_encode(['de' => $body])]);
    }

    /**
     * Löscht einen Comment (Soft-Delete, weil das Comment-Modell
     * SoftDeletes verwendet).
     */
    public function deleteComment(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if ($comment !== null) {
            $comment->delete();
        }
    }

    /**
     * Setzt den Status eines bestehenden Comments.
     */
    public function setCommentStatus(int $commentId, int $status): void
    {
        $comment = Comment::find($commentId);

        if ($comment !== null) {
            $comment->status = $status;
            $comment->save();
        }
    }

    /**
     * Löst zu einem Comment das zugehörige Project auf.
     *
     * E.7b 4a-Hotfix-II (2026-06-21): zentrale Helper-Methode für
     * die Comment-Pfade in ChapterController/EntryController/
     * ContentController/AudiovisualController/ProjectController.
     * Die `setCommentStatus*`- und `saveComment*`-Endpunkte haben
     * keinen Project-Bezug im Route-Param und können das Modell-
     * Argument im Methoden-Signature nicht via Route-Model-Binding
     * auflösen — wir navigieren stattdessen vom Comment via
     * `commentable_type`/`commentable_id` zum Project.
     */
    public function resolveProjectForComment(int $commentId): ?Project
    {
        $comment = Comment::find($commentId);

        if ($comment === null) {
            return null;
        }

        // commentable_type/_id sind als non-nullable im Comment-PHPDoc
        // typisiert — Larastan würde explizite null-Checks als always-
        // false-Smells melden. Pragmatisch: direkt im match() arbeiten.
        return match ($comment->commentable_type) {
            Project::class => Project::find($comment->commentable_id),
            Chapter::class => Chapter::find($comment->commentable_id)?->project,
            Entry::class => Entry::find($comment->commentable_id)?->chapter?->project,
            Text::class => Text::find($comment->commentable_id)?->project(),
            Audiovisual::class => Audiovisual::find($comment->commentable_id)?->project(),
            Gallery::class => Gallery::find($comment->commentable_id)?->project(),
            Image::class => Image::find($comment->commentable_id)?->project(),
            MediaContent::class => $this->resolveProjectViaMediaContent($comment->commentable_id),
            default => null,
        };
    }

    /**
     * Auflösung für Comments, die direkt am MediaContent-Pivot hängen
     * (selten — z.B. wenn vorhandene Comment-Daten den Pivot statt
     * den Content referenzieren).
     */
    private function resolveProjectViaMediaContent(int $mediaContentId): ?Project
    {
        $mc = MediaContent::find($mediaContentId);
        if ($mc === null) {
            return null;
        }
        /** @var Model|null $content */
        $content = $mc->content;
        if ($content === null) {
            return null;
        }
        if ($content instanceof Project) {
            return $content;
        }
        if (method_exists($content, 'project')) {
            $project = $content->project();
            if ($project instanceof Project) {
                return $project;
            }
        }

        return null;
    }

    /**
     * Routet eine `save-comment`-Form-Submission anhand des
     * `btn_submit`-Werts an die richtige Methode. Wird heute in
     * sieben Controller-Methoden (saveCommentProject, saveComment,
     * saveCommentEntry, saveCommentText, saveCommentImage,
     * commentGallery, commentAudiovisual) dupliziert.
     *
     * Erwartet aus dem Request:
     *  - btn_submit  (Edit | delete | <anderes — Reply>)
     *  - id, pk, value, reply, commentId, question, projectId
     *    je nach Pfad — siehe addComment/replyToComment/editComment
     *
     * Liefert `true`, wenn der Switch eine Aktion ausgelöst hat,
     * `false` bei unbekanntem oder fehlendem `btn_submit` — der
     * Aufrufer kann dann einen leeren Response zurückgeben (das
     * alte Verhalten der `saveComment*`-Methoden).
     */
    public function dispatchSaveAction(HasComments $commentable, Request $request): bool
    {
        if (! isset($request['btn_submit'])) {
            return false;
        }

        $action = $request['btn_submit'];

        if ($action === 'Edit') {
            $this->editComment((int) $request['pk'], (string) $request['value']);

            return true;
        }

        if ($action === 'delete') {
            $this->deleteComment((int) $request['id']);

            return true;
        }

        // Default: Reply-Pfad
        $this->replyToComment($commentable, $request);

        return true;
    }
}
