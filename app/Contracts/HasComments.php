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

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Markiert ein Modell als commentable: es trägt eine
 * `comments()`-MorphMany-Relation auf das `Comment`-Modell. Wird
 * vom `CommentService` als Type-Hint verwendet, damit der Service
 * nicht generisch gegen `Model` arbeitet und Larastan die
 * `->comments()`-Aufrufe statisch verifizieren kann.
 *
 * Implementiert von Project, Chapter, Entry, MediaContent, Text,
 * Image, Gallery und Audiovisual — alle acht Modelle haben die
 * Relation bereits direkt im Body deklariert (vor der
 * CommentTrait-Auflösung lebte sie im Trait).
 */
interface HasComments
{
    public function comments(): MorphMany;
}
