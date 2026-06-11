<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = Lesson::all()->keyBy('slug');

        // Unit 1.1 + 1.4 = fully authored payloads (Phase 1 brief).
        $exercises = array_merge(
            $this->unit11Exercises(),
            $this->unit12StubExercises(),
            $this->unit13StubExercises(),
            $this->unit14Exercises(),
            $this->unit15StubExercises(),
        );

        foreach ($exercises as $row) {
            $lessonSlug = $row['lesson_slug'];
            $component = $row['component'];
            $order = $row['order'];
            unset($row['lesson_slug']);

            Exercise::updateOrCreate(
                [
                    'lesson_id' => $lessons[$lessonSlug]->id,
                    'component' => $component,
                    'order' => $order,
                ],
                array_merge($row, ['lesson_id' => $lessons[$lessonSlug]->id])
            );
        }
    }

    // ====================================================================
    // UNIT 1.1 — The Five Vowels (fully authored)
    // ====================================================================

    private function unit11Exercises(): array
    {
        return [
            // -------- L1.1.1 Vowel Lock-In --------
            [
                'lesson_slug' => 'l-1-1-1-vowel-lock-in',
                'component' => 'EarTraining',
                'order' => 1,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Hear the vowel. Tap the letter.',
                    'options' => ['a', 'e', 'i', 'o', 'u'],
                    'rounds' => [
                        ['audio_key' => 'tts_vowel_a_1', 'answer' => 'a'],
                        ['audio_key' => 'tts_vowel_e_1', 'answer' => 'e'],
                        ['audio_key' => 'tts_vowel_i_1', 'answer' => 'i'],
                        ['audio_key' => 'tts_vowel_o_1', 'answer' => 'o'],
                        ['audio_key' => 'tts_vowel_u_1', 'answer' => 'u'],
                        ['audio_key' => 'tts_vowel_a_2', 'answer' => 'a'],
                        ['audio_key' => 'tts_vowel_e_2', 'answer' => 'e'],
                        ['audio_key' => 'tts_vowel_u_2', 'answer' => 'u'],
                        ['audio_key' => 'tts_vowel_o_2', 'answer' => 'o'],
                        ['audio_key' => 'tts_vowel_i_2', 'answer' => 'i'],
                    ],
                    'speed_escalation' => true,
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-1-vowel-lock-in',
                'component' => 'MinimalPairs',
                'order' => 2,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Which one did you hear?',
                    'pairs' => [
                        [
                            'a' => ['es' => 'paso', 'audio_key' => 'tts_word_paso', 'gloss' => 'step'],
                            'b' => ['es' => 'peso', 'audio_key' => 'tts_word_peso', 'gloss' => 'weight / peso (currency)'],
                            'prompt_audio' => 'tts_word_peso',
                            'answer' => 'b',
                        ],
                        [
                            'a' => ['es' => 'peso', 'audio_key' => 'tts_word_peso', 'gloss' => 'weight'],
                            'b' => ['es' => 'piso', 'audio_key' => 'tts_word_piso', 'gloss' => 'floor'],
                            'prompt_audio' => 'tts_word_piso',
                            'answer' => 'b',
                        ],
                        [
                            'a' => ['es' => 'misa', 'audio_key' => 'tts_word_misa', 'gloss' => 'mass (church)'],
                            'b' => ['es' => 'mesa', 'audio_key' => 'tts_word_mesa', 'gloss' => 'table'],
                            'prompt_audio' => 'tts_word_mesa',
                            'answer' => 'b',
                        ],
                        [
                            'a' => ['es' => 'pelo', 'audio_key' => 'tts_word_pelo', 'gloss' => 'hair'],
                            'b' => ['es' => 'polo', 'audio_key' => 'tts_word_polo', 'gloss' => 'pole / polo'],
                            'prompt_audio' => 'tts_word_pelo',
                            'answer' => 'a',
                        ],
                        [
                            'a' => ['es' => 'puso', 'audio_key' => 'tts_word_puso', 'gloss' => 'he/she put'],
                            'b' => ['es' => 'piso', 'audio_key' => 'tts_word_piso', 'gloss' => 'floor'],
                            'prompt_audio' => 'tts_word_puso',
                            'answer' => 'a',
                        ],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-1-vowel-lock-in',
                'component' => 'ShadowRecord',
                'order' => 3,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Listen, then record yourself. Compare.',
                    'phrases' => [
                        ['es' => 'la mesa', 'en' => 'the table', 'audio_key' => 'tts_shadow_la_mesa'],
                        ['es' => 'el niño', 'en' => 'the boy', 'audio_key' => 'tts_shadow_el_nino'],
                        ['es' => 'mi casa', 'en' => 'my house', 'audio_key' => 'tts_shadow_mi_casa'],
                        ['es' => 'tu libro', 'en' => 'your book', 'audio_key' => 'tts_shadow_tu_libro'],
                        ['es' => 'una taza', 'en' => 'a cup', 'audio_key' => 'tts_shadow_una_taza'],
                    ],
                ],
            ],

            // -------- L1.1.2 Consonant Essentials --------
            [
                'lesson_slug' => 'l-1-1-2-consonant-essentials',
                'component' => 'SoundMatch',
                'order' => 1,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Hear it. Pick the letter.',
                    'rounds' => [
                        ['audio_key' => 'tts_word_manana', 'es' => 'mañana', 'highlight' => 'ñ', 'options' => ['n', 'ñ', 'm'], 'answer' => 'ñ'],
                        ['audio_key' => 'tts_word_llamo', 'es' => 'llamo', 'highlight' => 'll', 'options' => ['l', 'll', 'y'], 'answer' => 'll'],
                        ['audio_key' => 'tts_word_jamon', 'es' => 'jamón', 'highlight' => 'j', 'options' => ['j', 'h', 'g'], 'answer' => 'j'],
                        ['audio_key' => 'tts_word_perro', 'es' => 'perro', 'highlight' => 'rr', 'options' => ['r', 'rr', 'l'], 'answer' => 'rr'],
                        ['audio_key' => 'tts_word_nino', 'es' => 'niño', 'highlight' => 'ñ', 'options' => ['n', 'ñ', 'gn'], 'answer' => 'ñ'],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-2-consonant-essentials',
                'component' => 'SilentLetterTap',
                'order' => 2,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Tap every silent letter.',
                    'words' => [
                        ['es' => 'hospital', 'audio_key' => 'tts_word_hospital', 'silent_indices' => [0]],
                        ['es' => 'hablar', 'audio_key' => 'tts_word_hablar', 'silent_indices' => [0]],
                        ['es' => 'ahora', 'audio_key' => 'tts_word_ahora', 'silent_indices' => [1]],
                        ['es' => 'hola', 'audio_key' => 'tts_word_hola', 'silent_indices' => [0]],
                        ['es' => 'hermano', 'audio_key' => 'tts_word_hermano', 'silent_indices' => [0]],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-2-consonant-essentials',
                'component' => 'ShadowRecord',
                'order' => 3,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Mind the silent h, roll the rr.',
                    'phrases' => [
                        ['es' => 'la mañana', 'en' => 'the morning', 'audio_key' => 'tts_shadow_la_manana'],
                        ['es' => 'me llamo', 'en' => 'my name is', 'audio_key' => 'tts_shadow_me_llamo'],
                        ['es' => 'hola, hermano', 'en' => 'hi, brother', 'audio_key' => 'tts_shadow_hola_hermano'],
                        ['es' => 'el perro corre', 'en' => 'the dog runs', 'audio_key' => 'tts_shadow_el_perro_corre'],
                    ],
                ],
            ],

            // -------- L1.1.3 Stress & Accents --------
            [
                'lesson_slug' => 'l-1-1-3-stress-and-accents',
                'component' => 'StressTap',
                'order' => 1,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Tap the stressed syllable.',
                    'words' => [
                        ['es' => 'casa', 'syllables' => ['ca', 'sa'], 'answer_index' => 0, 'audio_key' => 'tts_word_casa', 'rule' => 1],
                        ['es' => 'gracias', 'syllables' => ['gra', 'cias'], 'answer_index' => 0, 'audio_key' => 'tts_word_gracias', 'rule' => 1],
                        ['es' => 'joven', 'syllables' => ['jo', 'ven'], 'answer_index' => 0, 'audio_key' => 'tts_word_joven', 'rule' => 1],
                        ['es' => 'español', 'syllables' => ['es', 'pa', 'ñol'], 'answer_index' => 2, 'audio_key' => 'tts_word_espanol', 'rule' => 2],
                        ['es' => 'ciudad', 'syllables' => ['ciu', 'dad'], 'answer_index' => 1, 'audio_key' => 'tts_word_ciudad', 'rule' => 2],
                        ['es' => 'café', 'syllables' => ['ca', 'fé'], 'answer_index' => 1, 'audio_key' => 'tts_word_cafe', 'rule' => 3],
                        ['es' => 'información', 'syllables' => ['in', 'for', 'ma', 'ción'], 'answer_index' => 3, 'audio_key' => 'tts_word_informacion', 'rule' => 3],
                        ['es' => 'África', 'syllables' => ['Á', 'fri', 'ca'], 'answer_index' => 0, 'audio_key' => 'tts_word_africa', 'rule' => 3],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-3-stress-and-accents',
                'component' => 'AccentDetective',
                'order' => 2,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Listen. Which word did you hear?',
                    'rounds' => [
                        [
                            'audio_key' => 'tts_word_papa',
                            'options' => [
                                ['es' => 'papa', 'gloss' => 'potato'],
                                ['es' => 'papá', 'gloss' => 'dad'],
                            ],
                            'answer_index' => 0,
                        ],
                        [
                            'audio_key' => 'tts_word_papa_dad',
                            'options' => [
                                ['es' => 'papa', 'gloss' => 'potato'],
                                ['es' => 'papá', 'gloss' => 'dad'],
                            ],
                            'answer_index' => 1,
                        ],
                        [
                            'audio_key' => 'tts_word_si',
                            'options' => [
                                ['es' => 'si', 'gloss' => 'if'],
                                ['es' => 'sí', 'gloss' => 'yes'],
                            ],
                            'answer_index' => 1,
                        ],
                        [
                            'audio_key' => 'tts_word_tu',
                            'options' => [
                                ['es' => 'tu', 'gloss' => 'your'],
                                ['es' => 'tú', 'gloss' => 'you'],
                            ],
                            'answer_index' => 1,
                        ],
                        [
                            'audio_key' => 'tts_word_el',
                            'options' => [
                                ['es' => 'el', 'gloss' => 'the'],
                                ['es' => 'él', 'gloss' => 'he'],
                            ],
                            'answer_index' => 0,
                        ],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-1-3-stress-and-accents',
                'component' => 'RuleSort',
                'order' => 3,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Drag each word into the rule that explains its stress.',
                    'buckets' => [
                        ['key' => 'rule_1', 'label' => 'Rule 1 — ends in vowel / n / s'],
                        ['key' => 'rule_2', 'label' => 'Rule 2 — ends in any other consonant'],
                        ['key' => 'rule_3', 'label' => 'Rule 3 — written accent overrides'],
                    ],
                    'words' => [
                        ['es' => 'casa', 'bucket' => 'rule_1'],
                        ['es' => 'gracias', 'bucket' => 'rule_1'],
                        ['es' => 'joven', 'bucket' => 'rule_1'],
                        ['es' => 'español', 'bucket' => 'rule_2'],
                        ['es' => 'ciudad', 'bucket' => 'rule_2'],
                        ['es' => 'hablar', 'bucket' => 'rule_2'],
                        ['es' => 'café', 'bucket' => 'rule_3'],
                        ['es' => 'información', 'bucket' => 'rule_3'],
                        ['es' => 'África', 'bucket' => 'rule_3'],
                    ],
                ],
            ],
        ];
    }

    // ====================================================================
    // UNIT 1.4 — Cognate Pattern -tion → -ción (fully authored)
    // ====================================================================

    private function unit14Exercises(): array
    {
        return [
            // -------- L1.4.1 Transformation Rule --------
            [
                'lesson_slug' => 'l-1-4-1-transformation-rule',
                'component' => 'WordForge',
                'order' => 1,
                'xp' => 15,
                'payload' => [
                    'prompt' => 'Forge the Spanish twin.',
                    'rule_key' => 'tion_cion',
                    'transformation_hint' => 'tion → ción',
                    'words' => [
                        ['en' => 'information', 'es' => 'información', 'audio_key' => 'tts_cognate_informacion'],
                        ['en' => 'celebration', 'es' => 'celebración', 'audio_key' => 'tts_cognate_celebracion'],
                        ['en' => 'imagination', 'es' => 'imaginación', 'audio_key' => 'tts_cognate_imaginacion'],
                        ['en' => 'conversation', 'es' => 'conversación', 'audio_key' => 'tts_cognate_conversacion'],
                        ['en' => 'decoration', 'es' => 'decoración', 'audio_key' => 'tts_cognate_decoracion'],
                        ['en' => 'reservation', 'es' => 'reservación', 'audio_key' => 'tts_cognate_reservacion'],
                        ['en' => 'observation', 'es' => 'observación', 'audio_key' => 'tts_cognate_observacion'],
                        ['en' => 'situation', 'es' => 'situación', 'audio_key' => 'tts_cognate_situacion'],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-4-1-transformation-rule',
                'component' => 'AccentPlacer',
                'order' => 2,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Tap the vowel that needs the accent.',
                    'words' => [
                        ['base' => 'informacion', 'accented' => 'información', 'accent_letter_index' => 8],
                        ['base' => 'nacion', 'accented' => 'nación', 'accent_letter_index' => 2],
                        ['base' => 'cancion', 'accented' => 'canción', 'accent_letter_index' => 3],
                        ['base' => 'celebracion', 'accented' => 'celebración', 'accent_letter_index' => 8],
                        ['base' => 'situacion', 'accented' => 'situación', 'accent_letter_index' => 6],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-4-1-transformation-rule',
                'component' => 'ChooseTheReal',
                'order' => 3,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'One of these is real Spanish. The others are traps.',
                    'rounds' => [
                        [
                            'en' => 'notification',
                            'options' => ['notificacion', 'notificatión', 'notificación'],
                            'answer_index' => 2,
                            'why_wrong' => ['Missing accent.', 'Kept the English -t-. Swap it.', null],
                        ],
                        [
                            'en' => 'celebration',
                            'options' => ['celebración', 'celebrasión', 'célebración'],
                            'answer_index' => 0,
                            'why_wrong' => [null, 'No, it is -ción not -sión.', 'Accent is on -ción, not the first é.'],
                        ],
                        [
                            'en' => 'information',
                            'options' => ['informatión', 'información', 'informaciòn'],
                            'answer_index' => 1,
                            'why_wrong' => ['Kept the English -t-. Swap it.', null, 'Wrong accent — it is acute (´), not grave (`).'],
                        ],
                    ],
                ],
            ],

            // -------- L1.4.2 Tweaks & Traps --------
            [
                'lesson_slug' => 'l-1-4-2-tweaks-and-traps',
                'component' => 'WordForge',
                'order' => 1,
                'xp' => 15,
                'payload' => [
                    'prompt' => 'Mixed set — some tweaks, some clean.',
                    'rule_key' => 'tion_cion',
                    'transformation_hint' => 'tion → ción',
                    'words' => [
                        ['en' => 'station', 'es' => 'estación', 'audio_key' => 'tts_cognate_estacion', 'tweak_note' => 'Spanish adds e- before st-.'],
                        ['en' => 'authorization', 'es' => 'autorización', 'audio_key' => 'tts_cognate_autorizacion', 'tweak_note' => 'th → t.'],
                        ['en' => 'communication', 'es' => 'comunicación', 'audio_key' => 'tts_cognate_comunicacion', 'tweak_note' => 'Double mm collapses to one m.'],
                        ['en' => 'commission', 'es' => 'comisión', 'audio_key' => 'tts_cognate_comision', 'tweak_note' => '-ssion → -sión. Double m collapses too.'],
                        ['en' => 'organization', 'es' => 'organización', 'audio_key' => 'tts_cognate_organizacion'],
                        ['en' => 'attention', 'es' => 'atención', 'audio_key' => 'tts_cognate_atencion', 'tweak_note' => 'Double tt collapses to one t.'],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-4-2-tweaks-and-traps',
                'component' => 'TrapOrTreat',
                'order' => 2,
                'xp' => 15,
                'payload' => [
                    'prompt' => 'Clean conversion or rebel word? Pick wisely.',
                    'rounds' => [
                        [
                            'en' => 'translation',
                            'naive_es' => 'translación',
                            'real_es' => 'traducción',
                            'is_exception' => true,
                            'rebel_word_key' => 'traduccion',
                            'explanation' => 'The Spanish word comes from *traducir* (to translate). It refuses the pattern entirely.',
                        ],
                        [
                            'en' => 'explanation',
                            'naive_es' => 'explanación',
                            'real_es' => 'explicación',
                            'is_exception' => true,
                            'rebel_word_key' => 'explicacion',
                            'explanation' => 'From *explicar* (to explain). The pattern does not apply.',
                        ],
                        [
                            'en' => 'vacation',
                            'naive_es' => 'vacación',
                            'real_es' => 'vacaciones',
                            'is_exception' => true,
                            'rebel_word_key' => 'vacaciones',
                            'explanation' => 'Always plural in Spanish — *las vacaciones*.',
                        ],
                        [
                            'en' => 'celebration',
                            'naive_es' => 'celebración',
                            'real_es' => 'celebración',
                            'is_exception' => false,
                            'explanation' => 'Clean swap. The rule holds.',
                        ],
                        [
                            'en' => 'nation',
                            'naive_es' => 'nación',
                            'real_es' => 'nación',
                            'is_exception' => false,
                            'explanation' => 'Clean swap. The rule holds.',
                        ],
                        [
                            'en' => 'station',
                            'naive_es' => 'stación',
                            'real_es' => 'estación',
                            'is_exception' => false,
                            'explanation' => 'A small tweak (add e- before st-), not a rebel — the -ción pattern still wins.',
                        ],
                    ],
                ],
            ],

            // -------- L1.4.3 Say Them Like a Local + La --------
            [
                'lesson_slug' => 'l-1-4-3-say-them-like-a-local',
                'component' => 'EarTraining',
                'order' => 1,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Native speed. Tap the English word you hear.',
                    'options_pool' => true,
                    'rounds' => [
                        [
                            'audio_key' => 'tts_cognate_informacion_native',
                            'options' => ['information', 'formation', 'interaction'],
                            'answer' => 'information',
                        ],
                        [
                            'audio_key' => 'tts_cognate_situacion_native',
                            'options' => ['salutation', 'situation', 'station'],
                            'answer' => 'situation',
                        ],
                        [
                            'audio_key' => 'tts_cognate_celebracion_native',
                            'options' => ['celebration', 'declaration', 'separation'],
                            'answer' => 'celebration',
                        ],
                        [
                            'audio_key' => 'tts_cognate_atencion_native',
                            'options' => ['intention', 'attention', 'ascension'],
                            'answer' => 'attention',
                        ],
                        [
                            'audio_key' => 'tts_cognate_estacion_native',
                            'options' => ['station', 'evasion', 'estimation'],
                            'answer' => 'station',
                        ],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-4-3-say-them-like-a-local',
                'component' => 'ShadowRecord',
                'order' => 2,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Stamp the -ción on the end.',
                    'phrases' => [
                        ['es' => 'la información', 'en' => 'the information', 'audio_key' => 'tts_shadow_la_informacion'],
                        ['es' => 'la situación', 'en' => 'the situation', 'audio_key' => 'tts_shadow_la_situacion'],
                        ['es' => 'la conversación', 'en' => 'the conversation', 'audio_key' => 'tts_shadow_la_conversacion'],
                        ['es' => 'la estación', 'en' => 'the station', 'audio_key' => 'tts_shadow_la_estacion'],
                    ],
                ],
            ],
            [
                'lesson_slug' => 'l-1-4-3-say-them-like-a-local',
                'component' => 'ArticleAttach',
                'order' => 3,
                'xp' => 10,
                'payload' => [
                    'prompt' => 'Drag the right article onto each noun.',
                    'articles' => ['la', 'el'],
                    'nouns' => [
                        ['es' => 'información', 'article' => 'la', 'reason' => 'Every -ción noun is feminine.'],
                        ['es' => 'doctor', 'article' => 'el', 'reason' => 'Decoy — not a -ción noun.'],
                        ['es' => 'situación', 'article' => 'la'],
                        ['es' => 'profesor', 'article' => 'el', 'reason' => 'Decoy — not a -ción noun.'],
                        ['es' => 'celebración', 'article' => 'la'],
                        ['es' => 'estación', 'article' => 'la'],
                        ['es' => 'libro', 'article' => 'el', 'reason' => 'Decoy — not a -ción noun.'],
                        ['es' => 'conversación', 'article' => 'la'],
                    ],
                ],
            ],

            // -------- L1.4.4 Guess Mode --------
            [
                'lesson_slug' => 'l-1-4-4-guess-mode',
                'component' => 'WordForge',
                'order' => 1,
                'xp' => 20,
                'payload' => [
                    'prompt' => 'Cold guess. These words have never been shown to you.',
                    'rule_key' => 'tion_cion',
                    'transformation_hint' => 'tion → ción',
                    'is_guess_mode' => true,
                    'vocab_counter_per_correct' => 1,
                    'words' => [
                        ['en' => 'declaration', 'es' => 'declaración', 'audio_key' => 'tts_cognate_declaracion'],
                        ['en' => 'occupation', 'es' => 'ocupación', 'audio_key' => 'tts_cognate_ocupacion'],
                        ['en' => 'preparation', 'es' => 'preparación', 'audio_key' => 'tts_cognate_preparacion'],
                        ['en' => 'imitation', 'es' => 'imitación', 'audio_key' => 'tts_cognate_imitacion'],
                        ['en' => 'motivation', 'es' => 'motivación', 'audio_key' => 'tts_cognate_motivacion'],
                        ['en' => 'navigation', 'es' => 'navegación', 'audio_key' => 'tts_cognate_navegacion'],
                        ['en' => 'meditation', 'es' => 'meditación', 'audio_key' => 'tts_cognate_meditacion'],
                        ['en' => 'liberation', 'es' => 'liberación', 'audio_key' => 'tts_cognate_liberacion'],
                        ['en' => 'population', 'es' => 'población', 'audio_key' => 'tts_cognate_poblacion'],
                        ['en' => 'inflation', 'es' => 'inflación', 'audio_key' => 'tts_cognate_inflacion'],
                    ],
                    'closer_message' => 'You just produced {count} Spanish words nobody taught you.',
                ],
            ],
        ];
    }

    // ====================================================================
    // STUBBED EXERCISES (Phase 4 will author payloads)
    // ====================================================================

    private function unit12StubExercises(): array
    {
        return $this->stubsFor([
            'l-1-2-1-hello-goodbye' => [
                ['TimeOfDayPicker', 1],
                ['ChunkAssembly', 2],
                ['AudioFirst', 3],
            ],
            'l-1-2-2-how-are-you' => [
                ['DialogueVolley', 1],
                ['ChunkRecall', 2],
                ['ShadowRecord', 3],
            ],
            'l-1-2-3-names-meeting' => [
                ['SlotFill', 1],
                ['DialogueVolley', 2],
                ['ScrambleBuild', 3],
            ],
            'l-1-2-4-courtesy-power-pack' => [
                ['SituationMatch', 1],
                ['DialogueVolley', 2],
                ['SpeedChunks', 3],
            ],
        ]);
    }

    private function unit13StubExercises(): array
    {
        return $this->stubsFor([
            'l-1-3-1-i-dont-understand' => [
                ['PanicButton', 1],
                ['RepairRoulette', 2],
                ['DialogueVolley', 3],
            ],
            'l-1-3-2-slow-down-say-again' => [
                ['PanicButton', 1],
                ['RepairRoulette', 2],
                ['DialogueVolley', 3],
            ],
            'l-1-3-3-two-magic-questions' => [
                ['PanicButton', 1],
                ['RepairRoulette', 2],
                ['DialogueVolley', 3],
            ],
        ]);
    }

    private function unit15StubExercises(): array
    {
        return $this->stubsFor([
            'l-1-5-1-numbers-0-15' => [
                ['NumberRush', 1],
                ['ScrambleBuild', 2],
                ['EarTraining', 3],
            ],
            'l-1-5-2-numbers-16-100' => [
                ['WordForge', 1],
                ['PriceTag', 2],
                ['PatternSort', 3],
            ],
            'l-1-5-3-days-and-months' => [
                ['CalendarTap', 1],
                ['SequenceOrder', 2],
                ['AudioFirst', 3],
            ],
            'l-1-5-4-what-time-is-it' => [
                ['ClockBuilder', 1],
                ['DialogueVolley', 2],
            ],
        ]);
    }

    /**
     * @param  array<string, array<array{0: string, 1: int}>>  $map
     * @return list<array<string, mixed>>
     */
    private function stubsFor(array $map): array
    {
        $out = [];
        foreach ($map as $lessonSlug => $rows) {
            foreach ($rows as [$component, $order]) {
                $out[] = [
                    'lesson_slug' => $lessonSlug,
                    'component' => $component,
                    'order' => $order,
                    'xp' => 10,
                    'payload' => ['stub' => true, 'authored_in_phase' => 4],
                ];
            }
        }

        return $out;
    }
}
