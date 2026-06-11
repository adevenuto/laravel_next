<?php

namespace Database\Seeders;

use App\Models\CognateWord;
use Illuminate\Database\Seeder;

class CognateWordSeeder extends Seeder
{
    public function run(): void
    {
        $clean = [
            ['en' => 'information', 'es' => 'información'],
            ['en' => 'notification', 'es' => 'notificación'],
            ['en' => 'identification', 'es' => 'identificación'],
            ['en' => 'verification', 'es' => 'verificación'],
            ['en' => 'confirmation', 'es' => 'confirmación'],
            ['en' => 'observation', 'es' => 'observación'],
            ['en' => 'administration', 'es' => 'administración'],
            ['en' => 'situation', 'es' => 'situación'],
            ['en' => 'conversation', 'es' => 'conversación'],
            ['en' => 'celebration', 'es' => 'celebración'],
            ['en' => 'imagination', 'es' => 'imaginación'],
            ['en' => 'decoration', 'es' => 'decoración'],
            ['en' => 'reservation', 'es' => 'reservación'],
            ['en' => 'education', 'es' => 'educación'],
            ['en' => 'operation', 'es' => 'operación'],
            ['en' => 'condition', 'es' => 'condición'],
            ['en' => 'nation', 'es' => 'nación'],
            ['en' => 'invention', 'es' => 'invención'],
            ['en' => 'intention', 'es' => 'intención'],
            ['en' => 'invitation', 'es' => 'invitación'],
            ['en' => 'liberation', 'es' => 'liberación'],
            ['en' => 'limitation', 'es' => 'limitación'],
            ['en' => 'migration', 'es' => 'migración'],
            ['en' => 'mention', 'es' => 'mención'],
            ['en' => 'preparation', 'es' => 'preparación'],
            ['en' => 'promotion', 'es' => 'promoción'],
            ['en' => 'proposition', 'es' => 'proposición'],
            ['en' => 'publication', 'es' => 'publicación'],
            ['en' => 'reputation', 'es' => 'reputación'],
            ['en' => 'resolution', 'es' => 'resolución'],
            ['en' => 'revolution', 'es' => 'revolución'],
            ['en' => 'sensation', 'es' => 'sensación'],
            ['en' => 'separation', 'es' => 'separación'],
            ['en' => 'solution', 'es' => 'solución'],
            ['en' => 'tradition', 'es' => 'tradición'],
            ['en' => 'transition', 'es' => 'transición'],
            ['en' => 'vibration', 'es' => 'vibración'],
            ['en' => 'ambition', 'es' => 'ambición'],
            ['en' => 'creation', 'es' => 'creación'],
            ['en' => 'evolution', 'es' => 'evolución'],
            ['en' => 'function', 'es' => 'función'],
            ['en' => 'motivation', 'es' => 'motivación'],
            ['en' => 'meditation', 'es' => 'meditación'],
            ['en' => 'inflation', 'es' => 'inflación'],
            ['en' => 'donation', 'es' => 'donación'],
            ['en' => 'distribution', 'es' => 'distribución'],
            ['en' => 'declaration', 'es' => 'declaración'],
            ['en' => 'imitation', 'es' => 'imitación'],
            ['en' => 'discrimination', 'es' => 'discriminación'],
            ['en' => 'organization', 'es' => 'organización'],
        ];

        // Soft tweaks — still follow the rule, with a small note.
        $tweaks = [
            ['en' => 'station', 'es' => 'estación', 'note' => 'Spanish adds e- before st-.'],
            ['en' => 'attention', 'es' => 'atención', 'note' => 'Double tt collapses to one t.'],
            ['en' => 'communication', 'es' => 'comunicación', 'note' => 'Double mm collapses to one m.'],
            ['en' => 'recommendation', 'es' => 'recomendación', 'note' => 'Double mm collapses to one m.'],
            ['en' => 'authorization', 'es' => 'autorización', 'note' => 'The th drops to a t.'],
            ['en' => 'action', 'es' => 'acción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'reaction', 'es' => 'reacción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'selection', 'es' => 'selección', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'instruction', 'es' => 'instrucción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'introduction', 'es' => 'introducción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'destruction', 'es' => 'destrucción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'direction', 'es' => 'dirección', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'production', 'es' => 'producción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'protection', 'es' => 'protección', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'reduction', 'es' => 'reducción', 'note' => 'English -ction becomes Spanish -cción.'],
            ['en' => 'commission', 'es' => 'comisión', 'note' => '-ssion becomes -sión, and double mm collapses.'],
            ['en' => 'navigation', 'es' => 'navegación', 'note' => 'Stem changes: navig- becomes naveg-.'],
            ['en' => 'occupation', 'es' => 'ocupación', 'note' => 'Double cc collapses to one c.'],
            ['en' => 'population', 'es' => 'población', 'note' => 'Stem changes: pop- becomes pob-.'],
            ['en' => 'illustration', 'es' => 'ilustración', 'note' => 'Double ll collapses to one l.'],
        ];

        // True exceptions — the pattern does NOT produce the right word.
        $exceptions = [
            [
                'en' => 'translation',
                'es' => 'traducción',
                'note' => 'From traducir (to translate). Naïve translación is wrong.',
            ],
            [
                'en' => 'explanation',
                'es' => 'explicación',
                'note' => 'From explicar (to explain). Naïve explanación is wrong.',
            ],
            [
                'en' => 'vacation',
                'es' => 'vacaciones',
                'note' => 'Always plural in Spanish — las vacaciones.',
            ],
        ];

        foreach ($clean as $row) {
            $this->upsert($row['en'], $row['es'], false, null);
        }
        foreach ($tweaks as $row) {
            $this->upsert($row['en'], $row['es'], false, $row['note']);
        }
        foreach ($exceptions as $row) {
            $this->upsert($row['en'], $row['es'], true, $row['note']);
        }
    }

    private function upsert(string $en, string $es, bool $isException, ?string $note): void
    {
        CognateWord::updateOrCreate(
            ['rule_key' => 'tion_cion', 'en' => $en],
            [
                'es' => $es,
                'is_exception' => $isException,
                'exception_note' => $note,
                'audio_key' => 'tts_cognate_'.self::slug($es),
            ]
        );
    }

    private static function slug(string $es): string
    {
        $s = mb_strtolower($es);
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
    }
}
