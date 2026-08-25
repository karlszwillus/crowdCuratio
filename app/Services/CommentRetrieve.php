<?php

/**
crowdCuratio - Curating together virtually
Copyright (C)2022, 2026 - berlinHistory e.V.

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

use App\Support\CommentableRoutes;
use App\Support\CommentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentRetrieve
{
    /**
     * Retrieve comments
     *
     * @return JsonResponse
     */
    public function getComments($class, $id)
    {

        $data = [];
        $status = [];
        $data['pathComment'] = '';
        // Phase 5x.8: der Composer (<livewire:comment-composer>) braucht
        // den commentable-Typ als FQCN, um in seinem save() das Modell
        // aufzuloesen. Der Aufrufer uebergibt $class ohnehin schon —
        // wir reichen ihn im Response weiter, damit die Blade-Seite
        // keinen zweiten Ableitungspfad braucht.
        $data['commentableType'] = $class;
        // Defensiv initialisieren, damit der foreach-Loop unten
        // auch dann nicht crasht, wenn der switch keinen Case für
        // $class trifft (z. B. MediaContent — wird heute von
        // ContentController::getTextComment / getImageComment als
        // class durchgereicht).
        $pathReply = '';

        // I2 (2026-08-21): Route-Mapping ueber CommentableRoutes-
        // Registry statt lokaler switch-Kaskade. MediaContent oder
        // andere nicht-registrierte Klassen fallen weiterhin auf den
        // Default (leerer pathReply/pathComment) — der foreach-Loop
        // unten ist darauf vorbereitet.
        $routes = CommentableRoutes::for($class);
        if ($routes !== null) {
            $pathReply = $routes['save'];
            $data['pathComment'] = $routes['base'];
            $data['id'] = $id;
        }

        // F-DB-014: alle hier möglichen Klassen (Project/Chapter/Entry/
        // MediaContent/Text/Image/Gallery/Audiovisual) nutzen SoftDeletes —
        // der Scope schließt trashed bereits implizit aus.
        //
        // Block-C-Folge: preventLazyLoading (Phase 2 / C.1) wirft bei
        // $model->comments, $value->replies und ->user. Eager-Loading
        // jetzt mit-anzieht. Das Pattern gilt für alle commentable
        // Klassen; jede hat eine `comments`-MorphMany via CommentTrait.
        $model = $class::with([
            'comments.user',
            'comments.replies.user',
        ])->findOrFail($id);

        // Phase 5x.4: Status-Map aus dem Enum statt aus der alten
        // config('project.comment'). Key ist der DB-Wert (String),
        // Wert der Label-String — Frontend verlangt weiterhin
        // ein Array-Format.
        $status = [];
        foreach (CommentStatus::cases() as $case) {
            $status[$case->value] = $case->value;
        }

        foreach ($model->comments as $key => $value) {
            $replies = [];

            if (count($value->replies) > 0) {
                foreach ($value->replies as $k => $v) {
                    $ownerReply = (Auth::user()->id == $v->user_id);
                    $name = isset($v->user->name) || isset($v->user->last_name) ? $v->user->name : 'gelöschte Benutzer';
                    $replies[] = [
                        'id' => $v->id,
                        'user' => $name,
                        'comment' => $v->comment,
                        'ownerReply' => $ownerReply,
                        'created' => date('d.m.Y', strtotime($v->created_at)),
                        // Eloquent-Instanz für die Volt-Komponente
                        // <livewire:comment-text-editor>. Existierende
                        // Array-Konsumenten bleiben unberührt.
                        'model' => $v,
                    ];
                }
            }
            $userName = isset($value->user->name) || isset($value->user->last_name) ? $value->user->name.' '.$value->user->last_name : 'gelöschte Benutzer';
            $owner = (Auth::user()->id == $value->user_id);
            $data['comment'][] = [
                'id' => $value->id,
                'commentable_id' => $value->commentable_id,
                'commentable_type' => $value->commentable_type,
                'user' => $userName,
                'owner' => $owner,
                'comment' => $value->comment,
                'stat' => $value->status,
                'status' => $status,
                'replies' => $replies,
                'created' => date('d.m.Y', strtotime($value->created_at)),
                'path' => $pathReply,
                // 5a.II: Eloquent-Instanz für die Volt-Komponente
                // <livewire:comment-status-switcher>. Existierende
                // Array-Konsumenten bleiben unberührt.
                'model' => $value,
            ];
        }

        return $data;
    }
}
