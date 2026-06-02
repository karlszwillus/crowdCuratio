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

namespace Database\Factories;

use App\Models\Source;
use App\Models\Text;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Text>
 */
class TextFactory extends Factory
{
    protected $model = Text::class;

    public function definition(): array
    {
        return [
            'text' => '<p>'.$this->faker->paragraph().'</p>',
            // texts.origin und texts.copyright sind FKs auf sources;
            // wir legen pro Text zwei eigene Source-Zeilen an.
            'origin' => Source::factory()->origin(),
            'copyright' => Source::factory()->copyright(),
        ];
    }
}
