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

return [

    'status' => [
        'default' => 'Draft',
    ],

    // Phase 5x.4: Kommentar-Status lebt jetzt als Backed-Enum in
    // App\Support\CommentStatus. Die alte int-Map (open=1, accepted=2,
    // decline=3, cancel=4, done=5) ist Migration-Historie und wird
    // von CommentStatus::fromLegacyInt() gemappt.

    'mail' => [
        'default' => 'Du erhältst diese Email, da für Dich soeben ein Account erstellt worden ist, mit dem Du Zugang zum CMS hast.',
    ],
];
