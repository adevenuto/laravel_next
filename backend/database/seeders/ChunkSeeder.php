<?php

namespace Database\Seeders;

use App\Models\Chunk;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ChunkSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all()->keyBy('slug');

        $chunks = [
            // ---------------- Unit 1.2 — Greetings & Courtesy ----------------
            ['es' => 'hola', 'en' => 'hello', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'buenos días', 'en' => 'good morning', 'unit_slug' => 'unit-1-2-greetings', 'grammar_unlock_key' => 'gender_agreement'],
            ['es' => 'buenas tardes', 'en' => 'good afternoon', 'unit_slug' => 'unit-1-2-greetings', 'grammar_unlock_key' => 'gender_agreement'],
            ['es' => 'buenas noches', 'en' => 'good evening / good night', 'unit_slug' => 'unit-1-2-greetings', 'grammar_unlock_key' => 'gender_agreement'],
            ['es' => 'adiós', 'en' => 'goodbye', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'hasta luego', 'en' => 'see you later', 'literal_en' => 'until later', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'hasta mañana', 'en' => 'see you tomorrow', 'literal_en' => 'until tomorrow', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => '¿cómo estás?', 'en' => 'how are you?', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'estoy bien, gracias', 'en' => "I'm well, thanks", 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'muy bien', 'en' => 'very well', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'más o menos', 'en' => 'so-so', 'literal_en' => 'more or less', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => '¿y tú?', 'en' => 'and you?', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'me llamo ___', 'en' => 'my name is ___', 'literal_en' => 'I call myself ___', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => '¿cómo te llamas?', 'en' => "what's your name?", 'literal_en' => 'how do you call yourself?', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'mucho gusto', 'en' => 'pleased to meet you', 'literal_en' => 'much pleasure', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'igualmente', 'en' => 'likewise', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'soy de ___', 'en' => "I'm from ___", 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => '¿de dónde eres?', 'en' => 'where are you from?', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'por favor', 'en' => 'please', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'gracias', 'en' => 'thanks', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'muchas gracias', 'en' => 'thank you very much', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'de nada', 'en' => "you're welcome", 'literal_en' => 'of nothing', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'perdón', 'en' => 'sorry', 'situation_tag' => 'minor_offense', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'disculpe', 'en' => 'excuse me', 'situation_tag' => 'get_attention', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'con permiso', 'en' => 'excuse me (passing through)', 'literal_en' => 'with permission', 'situation_tag' => 'passing_through', 'unit_slug' => 'unit-1-2-greetings'],
            ['es' => 'lo siento', 'en' => "I'm sorry", 'literal_en' => 'I feel it', 'situation_tag' => 'sympathy', 'unit_slug' => 'unit-1-2-greetings'],

            // ---------------- Unit 1.3 — Repair Kit ----------------
            ['es' => 'no entiendo', 'en' => "I don't understand", 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'no sé', 'en' => "I don't know", 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'no hablo mucho español', 'en' => "I don't speak much Spanish", 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => '¿habla inglés?', 'en' => 'do you speak English?', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'más despacio, por favor', 'en' => 'slower, please', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'repita, por favor', 'en' => 'repeat, please', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'otra vez', 'en' => 'one more time', 'literal_en' => 'another time', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => 'un momento', 'en' => 'one moment', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => '¿puede escribirlo?', 'en' => 'can you write it?', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => '¿cómo se dice ___?', 'en' => 'how do you say ___?', 'unit_slug' => 'unit-1-3-repair-kit'],
            ['es' => '¿qué significa ___?', 'en' => 'what does ___ mean?', 'unit_slug' => 'unit-1-3-repair-kit'],

            // ---------------- Unit 1.5 — Time-telling chunks ----------------
            ['es' => '¿qué hora es?', 'en' => 'what time is it?', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'es la una', 'en' => "it's one o'clock", 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'son las dos', 'en' => "it's two o'clock", 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'y media', 'en' => 'half past', 'literal_en' => 'and half', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'y cuarto', 'en' => 'quarter past', 'literal_en' => 'and quarter', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'de la mañana', 'en' => 'in the morning (AM)', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'de la tarde', 'en' => 'in the afternoon (PM)', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'de la noche', 'en' => 'at night (PM)', 'unit_slug' => 'unit-1-5-numbers-time'],
            ['es' => 'el lunes', 'en' => 'on Monday', 'literal_en' => 'the Monday', 'unit_slug' => 'unit-1-5-numbers-time'],
        ];

        foreach ($chunks as $chunk) {
            $unitSlug = $chunk['unit_slug'];
            unset($chunk['unit_slug']);
            $chunk['unit_id'] = $units[$unitSlug]->id;
            $chunk['audio_key'] = 'tts_chunk_'.self::slug($chunk['es']);

            Chunk::updateOrCreate(['audio_key' => $chunk['audio_key']], $chunk);
        }
    }

    private static function slug(string $es): string
    {
        $s = mb_strtolower($es);
        $s = strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            '¿' => '', '?' => '', '¡' => '', '!' => '', ',' => '', '.' => '',
        ]);
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';

        return trim($s, '_');
    }
}
