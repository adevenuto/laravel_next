<?php

namespace Database\Factories;

use App\Models\Level;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'level_id' => Level::factory(),
            'slug' => $slug,
            'title' => $this->faker->words(3, true),
            'tagline' => $this->faker->sentence(),
            'order' => 1,
            'reward_badge_key' => 'badge_'.str_replace('-', '_', $slug),
            'reward_feature_key' => null,
        ];
    }
}
