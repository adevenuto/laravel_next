# BUILD INSTRUCTIONS — "Pattern Spanish" Learning App (Level 1 MVP)

> **Audience:** This document is a complete build directive for an AI coding session (or developer) to implement Level 1 of a pattern-based Spanish learning application. All architectural decisions below are FINAL unless technically impossible — do not re-litigate them. Where this document is silent, choose the simplest option consistent with the conventions herein and note the decision in `DECISIONS.md` at the repo root.

> **STARTING POINT:** This is NOT a greenfield build. An application skeleton already exists: a **Vue 3 SPA frontend** and a **Laravel backend**, with **basic authentication already working** — a user can register/log in and navigate to a dashboard. Phase 0 below requires auditing this skeleton and adapting to it. **Do not replace or rebuild the existing authentication system.** Extend what exists.

---

## 1. PRODUCT OVERVIEW

A gamified, progressive Spanish course (Babbel-style) built on a "pattern method": users learn derivation patterns (e.g., English *-tion* → Spanish *-ción*), fixed chunks (greetings, repair phrases), and sentence formulas — rather than rote vocabulary. Production is taught before listening comprehension, and the app explicitly rewards "asking for help" behaviors.

**This build covers Level 1 only** ("Survival & Sound System"): 5 units, 18 lessons, and a branching Boss Level conversation ("El Café"). The architecture must anticipate Levels 2–6 (formulas, conjugation, subjunctive triggers) without building them.

**North-star UX moments to protect (allocate polish budget here):**
1. The **WordForge** transformation exercise (Unit 1.4) — typing an English -tion word's Spanish form with live validation and a satisfying "forge" success animation.
2. The **+400 vocabulary counter jump** when Unit 1.4 completes (number-roll animation + confetti).
3. The **Boss Level "curveball"** — an NPC speaks too fast; the only winning move is a repair chunk, which *restores* a heart.
4. The **boss win screen** — replays the full conversation transcript the user just held in Spanish.

---

## 1.5 DESIGN & UX QUALITY BAR (applies to every screen and component)

> **If a frontend-design skill/plugin is available in this environment, activate and apply it for ALL UI work in every phase.** The directives below are the project-specific design brief that skill should execute against.

- **Committed aesthetic direction (FINAL — do not pick a different one per component):** warm, playful, game-like but refined — closer to a well-crafted indie game or premium mobile game than corporate edtech. Energetic without being childish. Explicitly avoid generic AI aesthetics: no default system font stacks, no purple-gradient-on-white card grids, no cookie-cutter component-library look.
- **Typography:** a distinctive display face for headings, numbers, and reward moments paired with a highly legible reading face for Spanish content. Spanish learning text is always rendered larger than surrounding UI chrome, and accent marks must be clearly distinguishable at every size (they are curriculum content, not decoration).
- **Design tokens, not magic numbers:** all colors, spacing, radii, shadows, and the type scale defined as Tailwind config values / CSS custom properties. Structure tokens dark-mode-ready even though dark mode is not in scope.
- **Motion has meaning:** answer feedback (correct/incorrect) is snappy — under ~300ms. Long, juicy, choreographed animation is reserved for the four north-star moments (§1) and unit rewards. Nothing animates just to animate. `prefers-reduced-motion` always respected.
- **Designed states, not defaults:** every interactive element ships with hover, focus-visible, active/pressed, disabled, and loading states. Tiles and answer buttons need tactile pressed feedback on touch. Keyboard focus must be visibly styled, not browser-default.
- **Feedback personality:** wrong answers coach, they never punish — no harsh red-X energy. Incorrect feedback always pairs the "not quite" signal with the relevant pattern hint. Correct feedback varies (avoid the same "Correct!" string 500 times).
- **Every view has designed empty, loading, and error states.** Loading uses skeletons or themed indicators, never raw spinners on white.
- **Signature element:** the WordForge interaction is the app's visual identity anchor — its letter-by-letter validation and forge-success moment should be the single most memorable piece of UI in the product. Budget design iteration there first.
- **Consistency check per phase:** before closing any phase, review new screens side-by-side for token drift (one-off colors/spacing) and fix.

---

## 2. TECH STACK & ARCHITECTURE (FINAL)

**Architecture:** Vue 3 SPA frontend ↔ Laravel JSON API backend. The frontend owns all routing and rendering; Laravel serves the API (and the SPA entry point). All app data flows through JSON endpoints defined in §7.

| Layer | Choice |
|---|---|
| Backend | Laravel (use the version already in the skeleton; do not downgrade), MySQL 8 (SQLite acceptable for local dev) |
| API | JSON REST endpoints under `/api`, consumed via axios |
| Auth | **Use the existing authentication as-is.** In Phase 0, identify whether it is session/cookie-based (Sanctum SPA mode or web guard) or token-based, and wire the new API routes into the same guard. Do not introduce a second auth system. |
| Frontend | Vue 3, Composition API, `<script setup>`, **TypeScript** (if the skeleton is plain JS, add TS support and write all NEW code in TS; converting existing skeleton files is optional) |
| Routing | **Vue Router** — extend the existing router; add route-level auth guards consistent with current behavior |
| Build | Vite (assumed present in skeleton; if not, migrate from Mix in Phase 0 and log it) |
| State | Pinia (add if not present) |
| Styling | Tailwind CSS (add if not present; if the skeleton uses another system, layer Tailwind in for new components and log the coexistence approach) |
| HTTP | axios with a single configured instance (base URL, CSRF/credentials per the existing auth mode, 401 → redirect-to-login interceptor) |
| Drag & drop | `@formkit/drag-and-drop` (fallback: `vuedraggable` if blockers) |
| Audio | `howler.js` with audio sprites per lesson |
| Animation | `@vueuse/motion` for micro-interactions; **GSAP** for reward moments (forge success, counter roll, unlocks); `canvas-confetti` for celebrations |
| Utilities | `@vueuse/core` |
| PWA | `vite-plugin-pwa` (basic offline shell + lesson asset caching; do this LAST) |
| Testing | Pest (PHP) — or PHPUnit if the skeleton already uses it; Vitest (components); one happy-path E2E optional |

**Speech features:** `ShadowRecord` uses the MediaRecorder API (record + playback self-comparison only — no scoring). Speech *recognition* (Web Speech API) is used ONLY for the optional Boss Level voice mode and must be feature-detected and fully skippable. Never block progress on microphone availability.

**Audio content:** Build an artisan command (suggested name: `audio:generate`) that reads all `chunks`, `cognate_words`, `numbers`, and `dialogue_lines` content and generates MP3s via a TTS driver. Implement a driver interface with two drivers: (a) `NullTtsDriver` that writes silent placeholder files (default for dev, so the app runs with zero API keys), and (b) `GoogleTtsDriver` (Cloud Text-to-Speech, voice `es-US-Neural2` family) wired but inert without credentials. After generation, a second step packs per-lesson Howler sprite JSON + concatenated audio, served from `public/audio/`. The frontend must gracefully no-op if an audio key is missing.

---

## 3. DOMAIN MODEL & MIGRATIONS

Create these tables (singular model names, standard Laravel conventions, timestamps on everything). The `users` table already exists — ADD the new columns via a new migration; do not recreate it:

```text
levels        id, number, slug, title, promise_text, order
units         id, level_id, slug, title, tagline, order,
              reward_badge_key, reward_feature_key(nullable)
lessons       id, unit_id, slug, title, order, skill_key(string),
              teach_screens(json)
exercises     id, lesson_id, component(string enum), payload(json),
              order, xp(int, default 10)
chunks        id, es, en, literal_en(nullable), audio_key,
              situation_tag(nullable), grammar_unlock_key(nullable),
              unit_id(introduced in)
cognate_words id, en, es, rule_key(string e.g. 'tion_cion'),
              is_exception(bool), exception_note(nullable), audio_key
boss_scenarios id, level_id, slug, title, scene(json)   // full dialogue graph
dialogue_lines id, audio_key, es, en   // boss/NPC audio inventory
badges        id, key, title, description, icon
users (ALTER) + display_name(nullable), hometown(nullable),
              xp(int default 0), vocab_counter(int default 0),
              streak_count(int default 0), streak_last_active_date(date nullable)
user_lesson_progress  user_id, lesson_id, status(enum: locked|available|completed),
                      best_score(int nullable), completed_at
user_skill_mastery    user_id, skill_key(string),
                      tier(enum: recognize|transform|produce|combine|spontaneous),
                      srs_due_at(datetime nullable), srs_interval_days(int default 1),
                      srs_ease(float default 2.5)
user_collections      user_id, collectible_type(enum: badge|rebel_word|chunk),
                      collectible_key, earned_at
user_boss_attempts    user_id, boss_scenario_id, stars(0-3),
                      hearts_remaining, transcript(json), completed_at
```

**Notes:**
- `teach_screens` json = ordered array of teaching cards shown before exercises: `[{type: 'concept'|'example'|'callout'|'audio_demo', title, body_md, audio_key?, visual_key?}]`.
- `exercises.component` must match a frontend registry name from §5 exactly.
- `skill_key` examples: `vowels`, `stress_rules`, `greetings`, `repair_kit`, `cognate_tion`, `numbers_0_15`, `numbers_16_100`, `time_telling`. Each lesson maps to exactly one skill_key.
- SRS: implement simple SM-2-lite on `user_skill_mastery` (correct review → interval × ease; failed → reset to 1 day). Do not over-engineer; FSRS is a future upgrade.

---

## 4. SEED CONTENT (MUST BE FULLY AUTHORED)

Seeders must populate Level 1 **completely** — once migrations and seeders have run, the app should be playable end-to-end. Author the actual Spanish content per the curriculum below. Verify all Spanish for correctness (accents included) — this is user-facing content, not lorem ipsum.

### Unit 1.1 — The Five Vowels (3 lessons)
1. **Vowel Lock-In** — teach a/e/i/o/u, one sound each. Exercises: EarTraining (hear vowel → tap letter, escalating speed), MinimalPairs (peso/piso-style audio discrimination — vowel pairs only here), ShadowRecord (la mesa, el niño…).
2. **Consonant Essentials** — ñ, ll, silent h, j, rr (reassure: single r acceptable), v≈b. Exercises: SoundMatch, SilentLetterTap (tap silent letters in hospital/hablar/ahora/hola), ShadowRecord.
3. **Stress & the Accent Mark** — Rule 1 (ends vowel/n/s → penultimate stress), Rule 2 (other consonant → final stress), Rule 3 (written accent overrides). Exercises: StressTap (tap stressed syllable pill), AccentDetective (papa vs papá audio), RuleSort (drag words into rule buckets).
- **Unit reward:** badge `the_decoder` + feature flag `pronunciation_hints` (enables tap-any-Spanish-word-to-hear-it app-wide; implement as a global `<EsWord>` wrapper component activated by the flag).

### Unit 1.2 — Greetings & Courtesy Chunks (4 lessons)
Chunks to seed (es / en / situation_tag): hola; buenos días; buenas tardes; buenas noches; adiós; hasta luego; hasta mañana; ¿cómo estás?; estoy bien, gracias; muy bien; más o menos; ¿y tú?; me llamo ___; ¿cómo te llamas?; mucho gusto; igualmente; soy de ___; ¿de dónde eres?; por favor; gracias; muchas gracias; de nada; perdón (tag: minor_offense); disculpe (tag: get_attention); con permiso (tag: passing_through); lo siento (tag: sympathy).
- Lessons: (1) Hello & Goodbye — TimeOfDayPicker, ChunkAssembly, AudioFirst. (2) How Are You? — DialogueVolley (mini ping-pong), ChunkRecall (typed, fuzzy/accent-forgiving), ShadowRecord. (3) Names & Meeting People — SlotFill using the user's real `display_name`/`hometown` (collect via a one-time onboarding modal if null), DialogueVolley, ScrambleBuild. (4) Courtesy Power Pack — SituationMatch (the perdón/disculpe/con permiso distinction is the priority drill), DialogueVolley (café micro-scene), SpeedChunks (60s timed matching).
- Mark gender-agreement chunks (buenas tardes) with `grammar_unlock_key = 'gender_agreement'` for future Level 2 callbacks.
- **Unit reward:** badge `first_contact` + Toolbox shelf "Social Chunks" (Toolbox = a screen listing earned chunk collections; simple v1).

### Unit 1.3 — Conversational Repair Kit (3 lessons)
Chunks: no entiendo; no sé; no hablo mucho español; ¿habla inglés?; más despacio, por favor; repita, por favor; otra vez; un momento; ¿puede escribirlo?; ¿cómo se dice ___?; ¿qué significa ___?
- Lessons: (1) I Don't Understand, (2) Slow Down & Say Again, (3) The Two Magic Questions.
- Exercises across unit: **PanicButton** (NPC plays a deliberately fast/above-level audio line; correct answer = choosing an appropriate repair chunk; NPC then repeats slowly — the exercise rewards asking for help), RepairRoulette (situation card → pick the right tool), DialogueVolley (a conversation that derails on purpose).
- **Unit reward:** badge `unsinkable` + feature flag `como_se_dice_button` (inside future DialogueVolley/Boss dialogues, a help button with limited uses that reveals a needed word).

### Unit 1.4 — Cognate Pattern: -tion → -ción (4 lessons)
Seed ≥60 cognate_words with rule_key `tion_cion`, e.g.: information→información, notification→notificación, identification→identificación, verification→verificación, confirmation→confirmación, observation→observación, administration→administración, situation→situación, conversation→conversación, celebration→celebración, imagination→imaginación, decoration→decoración, reservation→reservación, education→educación, operation→operación, attention→atención, direction→dirección, condition→condición, action→acción, nation→nación, station→estación (note e- prefix — mark as soft exception with note), authorization→autorización (th→t), organization→organización, communication→comunicación (double-letter collapse), commission→comisión… plus true exceptions: translation→traducción, explanation→explicación, vacation→vacaciones (mark `is_exception=true` with notes).
- Lessons: (1) The Transformation Rule — WordForge (core: type the Spanish; live per-letter validation; highlight the T→C moment; success = forge animation + audio playback), AccentPlacer, ChooseTheReal (distractors like "notificatión"). (2) Tweaks & Traps — WordForge mixed set, TrapOrTreat (clean conversion vs. exception; exceptions earned into "Rebel Words" collection). (3) Say Them Like a Local + La — EarTraining (native-speed audio → pick English cognate), ShadowRecord, ArticleAttach (drag *la* onto -ción nouns mixed with masculine decoys like *el doctor*; reinforce "all -ción = la, no exceptions"). (4) **Guess Mode** — WordForge with never-before-seen words; each cold success increments the user's visible `vocab_counter`; end screen messaging: "You just produced N Spanish words nobody taught you."
- **Unit reward:** badge `the_alchemist` + `vocab_counter += 400` fired exactly once with the number-roll + confetti moment (idempotent — guard against double-award).

### Unit 1.5 — Numbers, Days & Time (4 lessons)
1. Numbers 0–15 (memorized): NumberRush (timed audio→tap), ScrambleBuild, EarTraining.
2. Numbers 16–100 (patterns: dieciséis compression, veinti- compression, treinta y uno style, -enta tens): WordForge-for-numbers (see 17 → type "diecisiete"), PriceTag (hear price → type digits), PatternSort.
3. Days & Months (months as near-cognates = "free words"; el lunes = "on Monday" chunk): CalendarTap, SequenceOrder, AudioFirst.
4. What Time Is It? (chunks only: ¿qué hora es?; es la una; son las dos…; y media; y cuarto; de la mañana/tarde/noche — do NOT explain es/son, label it "part of the chunk"): ClockBuilder (analog clock → assemble time from tiles), DialogueVolley (ask a stranger the time; must open with *disculpe* — cross-unit callback).
- **Unit reward:** badge `the_navigator` + Toolbox shelf "Numbers & Time".

### Boss Level — "El Café" (boss_scenarios seed)
Author the full branching scene JSON per the schema in §6 implementing this flow: (1) time-appropriate greeting (wall clock visible in scene art — greeting must match it); (2) ¿cómo estás? volley (+bonus for ¿y tú?); (3) names & origins (uses user's real name/hometown; NPC Sofía is from Guadalajara); (4) **curveball**: Sofía says a fast above-level line — only repair chunks succeed and restore a heart; (5) cognate-by-ear: "Trabajo en administración. ¿Y tú?"; (6) courtesy gauntlet: 3 rapid micro-situations testing disculpe/perdón/con permiso; (7) the bill: "Son ocho cincuenta" → type 8.50; (8) goodbye (hasta luego + mucho gusto = style bonus).
- Mechanics: 3 hearts; wrong choices cost one; repair-chunk moments can restore one. Input escalation: phase 1 tiles → phase 2 free typing (fuzzy, accent-forgiving) → phase 3 optional voice (3rd star). Stars: 1 = complete, 2 = no hearts lost, 3 = voice mode used. Persist transcript to `user_boss_attempts` and show the replay on the win screen. Completing with ≥1 star marks Level 1 complete and shows a Level 2 teaser screen (static).

---

## 5. FRONTEND COMPONENT MANIFEST

All exercise components live in `src` alongside the existing skeleton's component directory convention (e.g., `resources/js/components/exercises/` — match whatever path style the skeleton uses), are payload-driven (props: `payload: object`, emits: `complete(result: {correct: boolean, score: number, meta?: object})`), and are registered in a single `exerciseRegistry.ts` keyed by the `exercises.component` string. The lesson player resolves components dynamically from the registry.

**Build these components** (shared look: card-based, large touch targets, mobile-first 380px-min design, desktop scales up):

`EarTraining`, `MinimalPairs`, `ShadowRecord`, `SoundMatch`, `SilentLetterTap`, `StressTap`, `AccentDetective`, `AccentPlacer`, `RuleSort`, `ChunkAssembly`, `ScrambleBuild`, `AudioFirst`, `DialogueVolley`, `ChunkRecall`, `SlotFill`, `SituationMatch`, `SpeedChunks`, `PanicButton`, `RepairRoulette`, `WordForge`, `TrapOrTreat`, `ChooseTheReal`, `ArticleAttach`, `NumberRush`, `PriceTag`, `PatternSort`, `CalendarTap`, `SequenceOrder`, `ClockBuilder`, `TimeOfDayPicker`.

> Consolidate where sensible: `ChooseTheReal`, `AudioFirst`, `SoundMatch`, `AccentDetective`, `RepairRoulette`, `SituationMatch`, `TimeOfDayPicker` can all be skins of one internal `<MultipleChoiceBase>`; `ChunkAssembly`/`ScrambleBuild`/`ClockBuilder` share a `<TileAssemblyBase>`; `WordForge`/`ChunkRecall`/`PriceTag` share a `<TypedAnswerBase>` with the fuzzy matcher. Aim for ~8 base interaction primitives skinned into the named components. Keep the public registry names as listed so seed payloads stay readable.

**Shared composables** (in the skeleton's composables directory):
- `useAudio()` — Howler sprite loading per lesson, `play(key)`, graceful missing-key no-op.
- `useRecorder()` — MediaRecorder wrapper for ShadowRecord; permission-denied = exercise auto-passes with a notice.
- `useSpeech()` — Web Speech API recognition, feature-detected; boss voice mode only.
- `useFuzzyMatch(expected, given)` — case-insensitive, trims, treats missing accents as correct-with-coaching (return `{correct: true, coaching: "Don't forget the stress arrow: información"}`), Levenshtein ≤1 on words ≥6 chars = "almost — check spelling" retry.
- `useHearts()`, `useXp()` (optimistic UI + POST sync), `useStreak()`.
- `useApi()` — thin wrapper over the configured axios instance.

**Pinia stores:** `useUserStore` (profile, xp, vocab_counter, streak, flags/badges — hydrated from `GET /api/me`), `useLessonStore` (current lesson, exercise queue, results), `useBossStore` (scene graph state machine, hearts, transcript).

**Vue Router routes (add to the existing router, behind the existing auth guard):**

```text
/dashboard        → DashboardView (extend existing dashboard): level map — units as
                    nodes on a path with lock states, vocab counter hero stat, streak flame
/lesson/:slug     → LessonPlayView: teach screens → exercise sequence → results screen
                    (XP earned + SRS scheduling notice)
/boss/:slug       → BossPlayView: full-screen BossScene
/toolbox          → ToolboxView: chunk shelves + Rebel Words + badges
/review           → ReviewView: SRS due-skills burst (5–8 exercises drawn from due
                    skill_keys' existing exercise pool)
```

Use lazy-loaded route components. Unknown/locked content routes redirect to `/dashboard` with a toast.

---

## 6. DIALOGUE / BOSS SCENE JSON SCHEMA

Deterministic branching graph (NO LLM calls in v1; design so an `llm` node type could be added later):

```jsonc
{
  "npc": {"name": "Sofía", "avatar": "sofia", "origin": "Guadalajara"},
  "scene": {"background": "cafe_afternoon", "ambient_audio": "cafe_ambience",
            "props": {"wall_clock": "15:00"}},
  "config": {"hearts": 3, "phases": {"1": "tiles", "2": "typed", "3": "voice_optional"}},
  "nodes": [
    {
      "id": "greeting",
      "npc_line": {"es": "¡Hola! Buenas tardes.", "audio_key": "boss1_greet"},
      "input": {"mode": "tiles",
        "tiles": ["buenos","buenas","días","tardes","noches","hola"],
        "answers": [
          {"match": ["buenas tardes"], "result": "correct", "next": "how_are_you"},
          {"match": ["hola"], "result": "partial", "feedback_es": "¡Hola! Pero, ¿qué hora es?", "retry": true},
          {"match": ["buenos días","buenas noches"], "result": "wrong",
           "heart_delta": -1, "coaching": "Check the wall clock — it's 3pm.", "retry": true}
        ]},
      "skill_keys": ["greetings","time_telling"]
    },
    {
      "id": "curveball",
      "npc_line": {"es": "(rápido) ¿Sabes? El otro día estaba pensando en cambiar de trabajo porque...",
                   "audio_key": "boss1_fast", "playback_rate": 1.35},
      "input": {"mode": "tiles",
        "tiles": ["más despacio, por favor","sí","no","no entiendo","gracias","¿qué significa?"],
        "answers": [
          {"match": ["más despacio, por favor","no entiendo","¿qué significa?"],
           "result": "correct", "heart_delta": 1,
           "feedback_es": "¡Ay, perdón! Más despacio...", "next": "curveball_slow"},
          {"match": ["sí","no","gracias"], "result": "wrong", "heart_delta": -1,
           "coaching": "When you're lost, say so — that's the skill.", "retry": true}
        ]},
      "skill_keys": ["repair_kit"]
    }
    // ... remaining nodes follow the same shape; typed-phase nodes use
    // "input": {"mode": "typed", "expected": ["me llamo {user.display_name}"], ...}
    // with {user.*} template interpolation and useFuzzyMatch validation.
  ],
  "scoring": {"stars": {"1": "complete", "2": "no_hearts_lost", "3": "voice_mode_used"},
              "style_bonuses": [{"node": "goodbye", "if_includes": ["mucho gusto"], "xp": 15}]}
}
```

`BossScene.vue` is a state machine over this graph: render npc_line (audio + chat bubble), render input mode, evaluate, apply heart/xp deltas, append both sides to transcript, advance. Heart at 0 = fail screen with "Practice these:" linking to weak skill lessons, retry allowed immediately.

---

## 7. BACKEND API

All endpoints are JSON, under `/api`, protected by the **existing auth guard** (match its mechanism — session/cookie or token — exactly; configure axios accordingly with CSRF/credentials as required). Use API Resources for response shaping.

**Read endpoints (frontend hydration):**
```text
GET  /api/me                          → profile, xp, vocab_counter, streak, badges, feature flags
GET  /api/levels/1/map                → units → lessons with per-user lock/complete state
GET  /api/lessons/{slug}              → teach_screens + ordered exercises (with payloads) — only if unlocked
GET  /api/boss/{slug}                 → scene JSON (with {user.*} values interpolated server-side or
                                        shipped alongside) — only if unlocked
GET  /api/toolbox                     → earned chunk shelves, rebel words, badges
GET  /api/review/queue                → due skills + selected exercise payloads
```

**Write endpoints:**
```text
POST /api/exercises/{exercise}/attempt   {correct, score, meta} → updates xp, mastery tier, SRS
POST /api/lessons/{lesson}/complete      → progress row, unlock next lesson, fire unit-reward
                                           if last lesson (idempotent)
POST /api/boss/{scenario}/attempt        {stars, hearts_remaining, transcript} → persist,
                                           mark level complete
POST /api/streak/ping                    → daily streak maintenance
POST /api/profile/onboarding             {display_name, hometown}
```

Business rules in dedicated service classes (`app/Services/ProgressService`, `RewardService`, `SrsService`). Unit reward grants (badge, feature flag, vocab_counter bump) must be idempotent and transactional. Server is the source of truth for unlock state — never trust the client's claim that a lesson is unlocked.

---

## 8. BUILD PHASES (do in order; each phase ends runnable & committed)

**Phase 0 — Skeleton audit & toolchain.** Confirm any available frontend-design skill is active and note it in `DECISIONS.md` (the §1.5 quality bar applies regardless). Read the existing codebase first. In `DECISIONS.md`, document: Laravel & Vue versions, auth mechanism (session vs. token), router setup, existing dashboard structure, directory/naming conventions, build tooling. Then add missing dependencies only (Pinia, Tailwind, TypeScript support, axios instance, Howler, GSAP, @vueuse/*, drag-and-drop, confetti). Configure the shared axios instance against the existing auth (verify with a `GET /api/me` smoke endpoint). Do not modify working auth flows. Lint/format tooling (Pint, ESLint, Prettier) if absent.

**Phase 1 — Domain & seed skeleton.** All migrations (including the `users` ALTER), models, relationships, factories. Seeders with full Level 1 *structure* (levels/units/lessons/exercises rows) and Unit 1.1 + 1.4 content fully authored. Pest/PHPUnit tests for ProgressService/RewardService/SrsService rules.

**Phase 2 — Lesson player + first primitives.** Extend the dashboard into the level map (consume `GET /api/levels/1/map`), LessonPlayView flow (teach screens → exercises → results), exercise registry, base primitives (MultipleChoiceBase, TypedAnswerBase, TileAssemblyBase), useAudio with NullTts placeholder sprites, useFuzzyMatch with unit tests. **Checkpoint: Unit 1.1 playable end-to-end through the real API.**

**Phase 3 — WordForge & Unit 1.4.** WordForge with live validation + GSAP forge animation, AccentPlacer, TrapOrTreat, ArticleAttach, Guess Mode flow, vocab counter + the +400 reward moment. **Checkpoint: the north-star moment works.**

**Phase 4 — Remaining content units.** Author + wire Units 1.2, 1.3, 1.5 content and their remaining components (DialogueVolley, PanicButton, SpeedChunks, NumberRush, ClockBuilder, etc.). Onboarding modal (name/hometown). Toolbox view. SRS review view + queue endpoint.

**Phase 5 — Boss Level.** Scene schema parser, BossScene state machine, hearts/stars, typed phase + fuzzy matching, transcript persistence + win-screen replay, fail/retry loop, level-complete + Level 2 teaser. Voice mode last, behind feature detection.

**Phase 6 — Polish & PWA.** Streak system + dashboard flame, the audio generation command + Google TTS driver (inert without creds), reward animations pass (GSAP/confetti), responsive/mobile QA at 380px, vite-plugin-pwa, Lighthouse pass, README updates covering new setup steps.

---

## 9. CONVENTIONS & GUARDRAILS

- **Respect the skeleton.** Match its existing directory structure, naming conventions, and component style for all new code. Extend the existing dashboard rather than replacing it. Never break the working login → dashboard flow; verify it after every phase.
- TypeScript strict for new code; exercise payloads typed via interfaces in a `types/exercises.ts` module mirroring seeder payloads.
- All user-visible Spanish must be accurate, accented correctly, and use Latin American conventions (ustedes not vosotros; "reservación" acceptable). Informal *tú* throughout per the method.
- Accessibility: all audio interactions need visible text equivalents; exercises completable by keyboard; respect `prefers-reduced-motion` (disable GSAP juice, keep function).
- Never hard-block on mic/speech/audio availability — every media feature degrades to a passable alternative.
- Mobile-first: every exercise must be fully usable at 380px width with thumb-reach targets ≥44px.
- No browser localStorage for progress — server is the source of truth; Pinia holds session state only.
- Commit per phase minimum; meaningful messages; keep seeders re-runnable (`updateOrCreate` by slug/key).
- If a library named here is unavailable or broken, substitute the closest equivalent and log it in `DECISIONS.md`.

## 10. DEFINITION OF DONE (Level 1 MVP)

1. Starting from the existing skeleton, after standard dependency installation, database migration, and seeding, the app is fully playable with zero external API keys, and the pre-existing login flow remains intact.
2. A logged-in user can: onboard (name/hometown) → complete all 18 lessons → see all 4 unit rewards fire (badges, +400 counter, feature flags) → beat the boss → view transcript replay → see Level 2 teaser.
3. The curveball mechanic works: repair chunk restores a heart; the win screen replays the transcript.
4. SRS review queue serves due skills daily; streak increments correctly across day boundaries.
5. Fuzzy matching accepts accent-less answers with coaching; rejects genuinely wrong answers.
6. All API endpoints enforce auth and server-side unlock state (a locked lesson's payload is never retrievable).
7. PHP + Vitest suites green; app usable at 380px and desktop; reduced-motion respected.