<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $level = Level::where('slug', 'level-1')->firstOrFail();

        $units = [
            [
                'slug' => 'unit-1-1-vowels',
                'title' => 'The Five Vowels',
                'tagline' => 'Lock in five sounds. Read anything aloud.',
                'order' => 1,
                'reward_badge_key' => 'the_decoder',
                'reward_feature_key' => 'pronunciation_hints',
            ],
            [
                'slug' => 'unit-1-2-greetings',
                'title' => 'Greetings & Courtesy Chunks',
                'tagline' => 'Walk into any conversation politely.',
                'order' => 2,
                'reward_badge_key' => 'first_contact',
                'reward_feature_key' => null,
            ],
            [
                'slug' => 'unit-1-3-repair-kit',
                'title' => 'Conversational Repair Kit',
                'tagline' => 'You will never get stuck again.',
                'order' => 3,
                'reward_badge_key' => 'unsinkable',
                'reward_feature_key' => 'como_se_dice_button',
            ],
            [
                'slug' => 'unit-1-4-cognates',
                'title' => 'Cognate Pattern: -tion → -ción',
                'tagline' => 'Steal 400 words in 20 minutes.',
                'order' => 4,
                'reward_badge_key' => 'the_alchemist',
                'reward_feature_key' => null,
            ],
            [
                'slug' => 'unit-1-5-numbers-time',
                'title' => 'Numbers, Days & Time',
                'tagline' => 'Make plans. Pay the bill.',
                'order' => 5,
                'reward_badge_key' => 'the_navigator',
                'reward_feature_key' => null,
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['slug' => $unit['slug']],
                array_merge($unit, ['level_id' => $level->id])
            );
        }
    }
}
