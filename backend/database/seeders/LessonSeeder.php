<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all()->keyBy('slug');

        $lessons = [
            // ---------------- Unit 1.1 — The Five Vowels ----------------
            [
                'unit_slug' => 'unit-1-1-vowels',
                'slug' => 'l-1-1-1-vowel-lock-in',
                'title' => 'Vowel Lock-In',
                'order' => 1,
                'skill_key' => 'vowels',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Five sounds. Forever.',
                        'body_md' => "Spanish has **five vowel sounds** and that is it. No matter what English does to them, Spanish vowels stay put.\n\n- **a** — like the *a* in *father*\n- **e** — like the *e* in *bed*\n- **i** — like the *ee* in *see*\n- **o** — like the *o* in *go* (short, no drift)\n- **u** — like the *oo* in *boot*",
                    ],
                    [
                        'type' => 'callout',
                        'title' => "What's different from English",
                        'body_md' => 'In English, vowels slide (*go* drifts to *gow*). In Spanish, vowels are **clean and short** — you stop where you started. This is the single biggest accent move you can make today.',
                    ],
                    [
                        'type' => 'audio_demo',
                        'title' => 'Hear the five',
                        'body_md' => 'Tap to hear each vowel by itself, then in a real word.',
                        'audio_key' => 'demo_vowels_aeiou',
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-1-vowels',
                'slug' => 'l-1-1-2-consonant-essentials',
                'title' => 'Consonant Essentials',
                'order' => 2,
                'skill_key' => 'consonants',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Five consonant moves',
                        'body_md' => "- **ñ** — *ny* as in *canyon* (mañana)\n- **ll** — *y* as in *yes* (llamo)\n- **h** — totally silent (hola = *o-la*)\n- **j** — back-of-throat *h*, like *Bach* (jamón)\n- **rr** — rolled. A single *r* will do for now.",
                    ],
                    [
                        'type' => 'callout',
                        'title' => 'v ≈ b',
                        'body_md' => "Don't try to make a real English *v*. Spanish *v* and *b* sound nearly identical — somewhere between the two. *Vamos* and *bamos* would sound the same.",
                    ],
                    [
                        'type' => 'audio_demo',
                        'title' => 'Hear the moves',
                        'body_md' => 'mañana · llamo · hola · jamón · perro',
                        'audio_key' => 'demo_consonants',
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-1-vowels',
                'slug' => 'l-1-1-3-stress-and-accents',
                'title' => 'Stress & the Accent Mark',
                'order' => 3,
                'skill_key' => 'stress_rules',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Three rules. That is it.',
                        'body_md' => "**Rule 1** — Word ends in a *vowel, n,* or *s* → stress the **second-to-last** syllable. (*ca-SA, jo-VEN, gra-CIAS*)\n\n**Rule 2** — Word ends in any other consonant → stress the **last** syllable. (*es-pa-ÑOL, ciu-DAD*)\n\n**Rule 3** — A **written accent** (´) overrides everything. It just says: *stress is here.* (*ca-FÉ, A-fri-ca, in-for-ma-CIÓN*)",
                    ],
                    [
                        'type' => 'example',
                        'title' => 'Compare',
                        'body_md' => "- **papa** = potato (rule 1: PA-pa)\n- **papá** = dad (rule 3: pa-PÁ)\n\nThe accent mark is the difference between *potato* and *dad*. Accents are content, not decoration.",
                    ],
                ],
            ],

            // ---------------- Unit 1.2 — Greetings ----------------
            [
                'unit_slug' => 'unit-1-2-greetings',
                'slug' => 'l-1-2-1-hello-goodbye',
                'title' => 'Hello & Goodbye',
                'order' => 1,
                'skill_key' => 'greetings',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Match the time of day',
                        'body_md' => "Spanish picks the greeting from the **clock**.\n\n- *buenos días* — morning (until ~noon)\n- *buenas tardes* — afternoon\n- *buenas noches* — evening / night\n\nAnd two universal: *hola* (any time) and *adiós* (any time).",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-2-greetings',
                'slug' => 'l-1-2-2-how-are-you',
                'title' => 'How Are You?',
                'order' => 2,
                'skill_key' => 'greetings',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'The ping-pong',
                        'body_md' => "*¿Cómo estás?* — How are you?\n*Estoy bien, gracias. ¿Y tú?* — I'm well, thanks. And you?\n\nAlways volley it back: **¿y tú?** This is the secret to sounding native.",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-2-greetings',
                'slug' => 'l-1-2-3-names-meeting',
                'title' => 'Names & Meeting People',
                'order' => 3,
                'skill_key' => 'names_intro',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Your turn',
                        'body_md' => "*Me llamo ___* — My name is ___.\n*Soy de ___* — I'm from ___.\n*Mucho gusto.* — Pleasure to meet you.\n*Igualmente.* — Likewise.\n\nWe will use your real name and hometown in the next drill.",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-2-greetings',
                'slug' => 'l-1-2-4-courtesy-power-pack',
                'title' => 'Courtesy Power Pack',
                'order' => 4,
                'skill_key' => 'courtesy',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Three sorrys. Pick the right one.',
                        'body_md' => "- *perdón* — I bumped you, I interrupted, I made a small mistake.\n- *disculpe* — Excuse me, can I get your attention?\n- *con permiso* — Coming through. (Bus, elevator, crowded café.)\n\nGetting these three right is what makes you sound *raised in Spanish*, not *taught in Spanish*.",
                    ],
                ],
            ],

            // ---------------- Unit 1.3 — Repair Kit ----------------
            [
                'unit_slug' => 'unit-1-3-repair-kit',
                'slug' => 'l-1-3-1-i-dont-understand',
                'title' => "I Don't Understand",
                'order' => 1,
                'skill_key' => 'repair_kit',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'When you are lost, say so',
                        'body_md' => "*No entiendo.* — I don't understand.\n*No sé.* — I don't know.\n*No hablo mucho español.* — I don't speak much Spanish.\n*¿Habla inglés?* — Do you speak English?\n\nAsking for help is a skill. This unit drills it until it is automatic.",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-3-repair-kit',
                'slug' => 'l-1-3-2-slow-down-say-again',
                'title' => 'Slow Down & Say Again',
                'order' => 2,
                'skill_key' => 'repair_kit',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Four ways to buy time',
                        'body_md' => "*Más despacio, por favor.* — Slower, please.\n*Repita, por favor.* — Repeat, please.\n*Otra vez.* — One more time.\n*Un momento.* — One moment.",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-3-repair-kit',
                'slug' => 'l-1-3-3-two-magic-questions',
                'title' => 'The Two Magic Questions',
                'order' => 3,
                'skill_key' => 'repair_kit',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'The vocabulary cheat codes',
                        'body_md' => "*¿Cómo se dice ___?* — How do you say ___?\n*¿Qué significa ___?* — What does ___ mean?\n\nMaster these two and you can keep any conversation going forever.",
                    ],
                ],
            ],

            // ---------------- Unit 1.4 — Cognates ----------------
            [
                'unit_slug' => 'unit-1-4-cognates',
                'slug' => 'l-1-4-1-transformation-rule',
                'title' => 'The Transformation Rule',
                'order' => 1,
                'skill_key' => 'cognate_tion',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'One swap. Hundreds of words.',
                        'body_md' => "Any English word ending in **-tion** has a Spanish twin ending in **-ción**.\n\n- information → **información**\n- celebration → **celebración**\n- imagination → **imaginación**\n\nThree moves:\n1. Swap **-tion** → **-ción**\n2. Put an **accent** on the **ó**\n3. (That's it.)",
                    ],
                    [
                        'type' => 'callout',
                        'title' => 'Why the accent?',
                        'body_md' => 'Words that end in *n* normally stress the second-to-last syllable. But the *-ción* sound is on the **last** syllable. The accent overrides — it says *stress lives here.*',
                    ],
                    [
                        'type' => 'audio_demo',
                        'title' => 'Hear it',
                        'body_md' => 'información · celebración · imaginación',
                        'audio_key' => 'demo_tion_cion',
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-4-cognates',
                'slug' => 'l-1-4-2-tweaks-and-traps',
                'title' => 'Tweaks & Traps',
                'order' => 2,
                'skill_key' => 'cognate_tion',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Small adjustments',
                        'body_md' => "Most -tion words convert cleanly. A few need a small tweak:\n\n- *station* → **estación** (Spanish adds an *e-* before *st-*: study → estudio, Spain → España)\n- *authorization* → **autorización** (the *th* drops to *t*)\n- *communication* → **comunicación** (double letters collapse to one: *mm* → *m*)",
                    ],
                    [
                        'type' => 'callout',
                        'title' => 'The Rebel Words',
                        'body_md' => "And three that refuse the pattern entirely. Catch them in this lesson and you earn them into your **Rebel Words** collection:\n\n- *translation* → **traducción** (not *translación*)\n- *explanation* → **explicación** (not *explanación*)\n- *vacation* → **vacaciones** (always plural in Spanish!)",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-4-cognates',
                'slug' => 'l-1-4-3-say-them-like-a-local',
                'title' => 'Say Them Like a Local + La',
                'order' => 3,
                'skill_key' => 'cognate_tion',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Every -ción is feminine',
                        'body_md' => "Every word that ends in *-ción* is grammatically **feminine**, so it always takes **la**.\n\n- **la** información\n- **la** estación\n- **la** conversación\n\nNo exceptions. Memorize the rule once and you are right hundreds of times.",
                    ],
                    [
                        'type' => 'audio_demo',
                        'title' => 'Native speed',
                        'body_md' => 'Listen for the *-ción* ending stamped on the end.',
                        'audio_key' => 'demo_cion_native_speed',
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-4-cognates',
                'slug' => 'l-1-4-4-guess-mode',
                'title' => 'Guess Mode',
                'order' => 4,
                'skill_key' => 'cognate_tion',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Words you were never taught',
                        'body_md' => "Now the trick. The words in this lesson are ones the app has **never shown you**. Apply the rule cold and watch your vocabulary counter climb.\n\nEach correct cold-guess = a word you produced yourself.",
                    ],
                ],
            ],

            // ---------------- Unit 1.5 — Numbers & Time ----------------
            [
                'unit_slug' => 'unit-1-5-numbers-time',
                'slug' => 'l-1-5-1-numbers-0-15',
                'title' => 'Numbers 0–15',
                'order' => 1,
                'skill_key' => 'numbers_0_15',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Memorize these. No shortcuts.',
                        'body_md' => "0 cero · 1 uno · 2 dos · 3 tres · 4 cuatro · 5 cinco\n\n6 seis · 7 siete · 8 ocho · 9 nueve · 10 diez\n\n11 once · 12 doce · 13 trece · 14 catorce · 15 quince",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-5-numbers-time',
                'slug' => 'l-1-5-2-numbers-16-100',
                'title' => 'Numbers 16–100',
                'order' => 2,
                'skill_key' => 'numbers_16_100',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'A handful of patterns',
                        'body_md' => "**16–19** — *diez y seis* compresses to **dieciséis**, then *diecisiete, dieciocho, diecinueve*.\n\n**20s** — *veinte y uno* compresses to **veintiuno**, then *veintidós, veintitrés…*\n\n**30+** — Stays separated: **treinta y uno, treinta y dos…**\n\n**Tens** — treinta (30) · cuarenta (40) · cincuenta (50) · sesenta (60) · setenta (70) · ochenta (80) · noventa (90) · cien (100).",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-5-numbers-time',
                'slug' => 'l-1-5-3-days-and-months',
                'title' => 'Days & Months',
                'order' => 3,
                'skill_key' => 'days_months',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'Free words',
                        'body_md' => "**Days** — lunes, martes, miércoles, jueves, viernes, sábado, domingo. *(Lowercase. Always.)*\n\n**Months** — enero, febrero, marzo, abril, mayo, junio, julio, agosto, septiembre, octubre, noviembre, diciembre. *(Lowercase. All near-cognates — basically free.)*\n\n**el lunes** = *on Monday*. Spanish uses *el* where English uses *on*.",
                    ],
                ],
            ],
            [
                'unit_slug' => 'unit-1-5-numbers-time',
                'slug' => 'l-1-5-4-what-time-is-it',
                'title' => 'What Time Is It?',
                'order' => 4,
                'skill_key' => 'time_telling',
                'teach_screens' => [
                    [
                        'type' => 'concept',
                        'title' => 'A chunk, not a rule',
                        'body_md' => "*¿Qué hora es?* — What time is it?\n\n*Es la una.* — It's one o'clock.\n*Son las dos.* — It's two o'clock.\n\nDon't worry about *es* vs *son* — treat them as part of the chunk. *La una* uses *es*. Every other hour uses *son*. Done.",
                    ],
                    [
                        'type' => 'callout',
                        'title' => 'Halves and quarters',
                        'body_md' => '*y media* — half past · *y cuarto* — quarter past · *de la mañana / tarde / noche* — AM/PM tag.',
                    ],
                ],
            ],
        ];

        foreach ($lessons as $lesson) {
            $unitSlug = $lesson['unit_slug'];
            unset($lesson['unit_slug']);

            Lesson::updateOrCreate(
                ['slug' => $lesson['slug']],
                array_merge($lesson, ['unit_id' => $units[$unitSlug]->id])
            );
        }
    }
}
