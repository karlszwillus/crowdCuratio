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

use App\Models\ProjectUserPermission;

/*
|--------------------------------------------------------------------------
| ProjectUserPermission-Modell
|--------------------------------------------------------------------------
|
| Block D PR 2 / D.8. Fixiert Tabellen-Bindung und Fillable-Set
| der neuen Modell-Klasse — vorher `App\Models\UserHasPermission`.
*/

it('Modell ist an Tabelle project_user_permissions gebunden', function () {
    $model = new ProjectUserPermission;

    expect($model->getTable())->toBe('project_user_permissions');
});

it('Modell hat die korrekten fillable-Felder', function () {
    $model = new ProjectUserPermission;

    expect($model->getFillable())->toBe(['project_id', 'permission_id', 'user_id']);
});
