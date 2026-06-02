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

use App\Models\Audiovisual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Audiovisual>
 */
class AudiovisualFactory extends Factory
{
    protected $model = Audiovisual::class;

    public function definition(): array
    {
        return [
            'link' => 'https://www.youtube.com/embed/'.$this->faker->regexify('[A-Za-z0-9_-]{11}'),
            'source' => $this->faker->sentence(3),
            'copyright' => $this->faker->name(),
            'type' => 'video',
        ];
    }

    public function audio(): self
    {
        return $this->state([
            'link' => 'test-audio-'.$this->faker->uuid().'.mp3',
            'type' => 'audio',
        ]);
    }
}
