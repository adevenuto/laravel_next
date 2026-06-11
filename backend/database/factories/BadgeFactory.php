<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'title' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => 'badge-default',
        ];
    }
}
