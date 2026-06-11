<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    public function run(): void
    {
        Level::updateOrCreate(
            ['slug' => 'level-1'],
            [
                'number' => 1,
                'title' => 'Survival & Sound System',
                'promise_text' => 'By the end of Level 1, you will read every Spanish word aloud correctly, '
                    .'greet anyone in Latin America, ask for help when you are lost, and produce '
                    .'400 Spanish words you were never taught.',
                'order' => 1,
            ]
        );
    }
}
