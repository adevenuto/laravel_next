<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'slug' => $this->faker->unique()->slug(3),
            'title' => $this->faker->words(3, true),
            'order' => 1,
            'skill_key' => 'skill_'.$this->faker->unique()->word(),
            'teach_screens' => [],
        ];
    }
}
