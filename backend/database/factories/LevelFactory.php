<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        $n = $this->faker->unique()->numberBetween(1, 6);

        return [
            'number' => $n,
            'slug' => 'level-'.$n,
            'title' => 'Level '.$n,
            'promise_text' => $this->faker->sentence(),
            'order' => $n,
        ];
    }
}
