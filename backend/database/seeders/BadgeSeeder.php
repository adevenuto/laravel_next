<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'key' => 'the_decoder',
                'title' => 'The Decoder',
                'description' => 'Cracked the Spanish sound system. You can read and pronounce anything.',
                'icon' => 'badge-decoder',
            ],
            [
                'key' => 'first_contact',
                'title' => 'First Contact',
                'description' => 'You can greet, introduce yourself, and be polite — anywhere in Latin America.',
                'icon' => 'badge-first-contact',
            ],
            [
                'key' => 'unsinkable',
                'title' => 'Unsinkable',
                'description' => 'You will never get stuck. You always have a way to ask for help.',
                'icon' => 'badge-unsinkable',
            ],
            [
                'key' => 'the_alchemist',
                'title' => 'The Alchemist',
                'description' => 'You turn English words into Spanish ones at will. +400 to your vocabulary.',
                'icon' => 'badge-alchemist',
            ],
            [
                'key' => 'the_navigator',
                'title' => 'The Navigator',
                'description' => 'Numbers, days, and time — you can set up plans in Spanish.',
                'icon' => 'badge-navigator',
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['key' => $badge['key']], $badge);
        }
    }
}
