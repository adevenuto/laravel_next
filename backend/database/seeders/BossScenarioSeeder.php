<?php

namespace Database\Seeders;

use App\Models\BossScenario;
use App\Models\Level;
use Illuminate\Database\Seeder;

class BossScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $level = Level::where('slug', 'level-1')->firstOrFail();

        // Structural placeholder. Phase 5 authors the full branching scene per §6 schema:
        //   greeting → how_are_you → names_origins → curveball → cognate_by_ear
        //   → courtesy_gauntlet → the_bill → goodbye.
        BossScenario::updateOrCreate(
            ['slug' => 'el-cafe'],
            [
                'level_id' => $level->id,
                'title' => 'El Café',
                'scene' => [
                    'npc' => ['name' => 'Sofía', 'avatar' => 'sofia', 'origin' => 'Guadalajara'],
                    'scene' => [
                        'background' => 'cafe_afternoon',
                        'ambient_audio' => 'cafe_ambience',
                        'props' => ['wall_clock' => '15:00'],
                    ],
                    'config' => [
                        'hearts' => 3,
                        'phases' => ['1' => 'tiles', '2' => 'typed', '3' => 'voice_optional'],
                    ],
                    'nodes' => [],
                    'scoring' => [
                        'stars' => [
                            '1' => 'complete',
                            '2' => 'no_hearts_lost',
                            '3' => 'voice_mode_used',
                        ],
                        'style_bonuses' => [
                            ['node' => 'goodbye', 'if_includes' => ['mucho gusto'], 'xp' => 15],
                        ],
                    ],
                    'meta' => ['authored_in_phase' => 5],
                ],
            ]
        );
    }
}
