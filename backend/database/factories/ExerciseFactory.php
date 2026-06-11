<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    protected $model = Exercise::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'component' => 'ChooseTheReal',
            'payload' => ['stub' => true],
            'order' => 1,
            'xp' => 10,
        ];
    }
}
