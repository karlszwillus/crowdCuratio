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
        // Defensiv initialisieren, damit der foreach-Loop unten
        // auch dann nicht crasht, wenn der switch keinen Case für
        // $class trifft (z. B. MediaContent — wird heute von
        // ContentController::getTextComment / getImageComment als
        // class durchgereicht).
        $pathReply = '';

        switch ($class) {
            case 'App\Models\Project':
                $pathReply = 'comment.project.save';
                $data['id'] = $id;
                $data['pathComment'] = '';
                break;
            case 'App\Models\Chapter':
                $pathReply = 'comment.save';
                $data['pathComment'] = 'comment.chapter';
                $data['id'] = $id;
                break;
            case 'App\Models\Entry':
                $pathReply = 'comment.entry.save';
                $data['pathComment'] = 'comment.entry';
                $data['id'] = $id;
                break;
            case 'App\Models\Gallery':
                $pathReply = 'comment.gallery.save';
                $data['pathComment'] = 'comment.gallery';
                $data['id'] = $id;
                break;
            case 'App\Models\Audiovisual':
                $pathReply = 'comment.audiovisual.save';
                $data['pathComment'] = 'comment.audiovisual';
                $data['id'] = $id;
                break;
            case 'App\Models\Image':
                $pathReply = 'comment.image.save';
                $data['pathComment'] = 'comment.image';
                $data['id'] = $id;
                break;
            case 'App\Models\Text':
                $pathReply = 'comment.text.save';
                $data['pathComment'] = 'comment.text';
                $data['id'] = $id;
                break;
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

        foreach (config('project.comment') as $v => $k) {
            $status[$v] = $k;
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
