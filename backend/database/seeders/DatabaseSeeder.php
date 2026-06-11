<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Content first — these seeders only touch reference tables and are idempotent
        // (updateOrCreate keyed by slug/key), so they're safe to re-run on existing data.
        $this->call([
            BadgeSeeder::class,
            LevelSeeder::class,
            UnitSeeder::class,
            LessonSeeder::class,
            ExerciseSeeder::class,
            ChunkSeeder::class,
            CognateWordSeeder::class,
            BossScenarioSeeder::class,
            DialogueLineSeeder::class,
        ]);

        // Dev convenience: a deterministic test user with the new fillable columns.
        // Only created if it doesn't already exist.
        User::firstOrCreate(
            ['email' => 'anthonydevenuto@gmail.com'],
            [
                'first_name' => 'Anthony',
                'last_name' => 'DeVenuto',
                'password' => bcrypt('Adev312!'),
                'display_name' => 'Tester',
                'hometown' => 'Brooklyn',
            ]
        );
    }
}
