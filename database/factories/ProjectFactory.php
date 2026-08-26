<?php

/**
crowdCuratio - Curating together virtually
Copyright (C) 2026 - berlinHistory e.V.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
 */

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * Default-State fuer Tests.
     *
     * `name`, `imprint`, `terms`, `description` sind translatable —
     * ein einfacher String landet ueber HasTranslations automatisch
     * in der aktuellen App-Locale. `status` ist NOT NULL.
     * `user_id` verweist auf einen frisch angelegten User.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Projekt '.$this->faker->unique()->words(2, true),
            // imprint ist NOT NULL (2021_04_14_add_logo_imprint_terms
            // _to_project_table). terms/description/logo sind nullable
            // und muessen hier nicht gesetzt werden.
            'imprint' => 'Testimpressum',
            'status' => 'active',
        ];
    }
}
