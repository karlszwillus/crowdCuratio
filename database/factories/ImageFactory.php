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

use App\Models\Image;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'image' => 'test-image-'.$this->faker->uuid().'.jpg',
            'origin' => Source::factory()->origin(),
            'copyright' => Source::factory()->copyright(),
            'url' => null,
            'alt' => $this->faker->sentence(3),
            'position' => 0,
            // gallery_id bleibt null — Images können standalone sein
            // oder per State zu einer Gallery zugeordnet werden.
        ];
    }

    public function forGallery(int $galleryId): self
    {
        return $this->state(['gallery_id' => $galleryId]);
    }
}
