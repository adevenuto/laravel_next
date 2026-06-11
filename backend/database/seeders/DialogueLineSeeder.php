<?php

namespace Database\Seeders;

use App\Models\DialogueLine;
use Illuminate\Database\Seeder;

class DialogueLineSeeder extends Seeder
{
    public function run(): void
    {
        // Seed inventory for the boss's authored greeting node + the curveball line, so
        // Phase 5 has working audio keys to reference when expanding the scene graph.
        $lines = [
            ['audio_key' => 'boss1_greet', 'es' => '¡Hola! Buenas tardes.', 'en' => 'Hi! Good afternoon.'],
            ['audio_key' => 'boss1_fast', 'es' => '¿Sabes? El otro día estaba pensando en cambiar de trabajo porque…', 'en' => 'You know? The other day I was thinking about changing jobs because…'],
            ['audio_key' => 'boss1_slow_again', 'es' => '¡Ay, perdón! Más despacio…', 'en' => 'Oh, sorry! Slower…'],
        ];

        foreach ($lines as $line) {
            DialogueLine::updateOrCreate(['audio_key' => $line['audio_key']], $line);
        }
    }
}
