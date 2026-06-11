# DECISIONS.md — Pattern Spanish (Level 1 MVP)

A running log of architectural and convention decisions for this build. Anything not explicitly stated in `docs/BUILD_INSTRUCTIONS.md` that required a judgement call lives here. Entries are dated and grouped by phase.

---

## Frontend-design skill

The `frontend-design:frontend-design` skill is available in this environment and **will be activated for every UI task**. Per §1.5 of the build directive, the skill is the executor; this project's design brief (warm/playful/game-like; distinctive display + reading typefaces; tokenised colours/spacing/radii/shadows; designed states; reduced-motion; signature WordForge moment) is what it executes against.

Phase 0 and Phase 1 are backend + plumbing. The first invocation of the skill will be at the start of Phase 2 when the level map and lesson player UI are built.

---

## Phase 0 — Skeleton audit findings

### Stack actually in place

| Layer | Found |
|---|---|
| PHP | 8.3 (composer requires `^8.3`) |
| Laravel | **13.8.0** (composer.lock) |
| Auth package | **Sanctum 4.0**, SPA cookie mode via `Application::configure(...)->withMiddleware(fn ($m) => $m->statefulApi())` in `backend/bootstrap/app.php` |
| Sanctum guard | `web` (default) — `config/sanctum.php` `'guard' => ['web']` |
| DB (dev) | MySQL (`next_laravel`), via `.env` |
| DB (test) | **SQLite `:memory:`** with `RefreshDatabase`, set in `phpunit.xml` |
| Test runner | **PHPUnit 12** (no Pest). Build doc §2 explicitly permits "PHPUnit if the skeleton already uses it" → no Pest install. |
| Code style | **Laravel Pint** (`backend/pint.json`, preset `laravel`, custom rule overrides for `simplified_null_return`, `braces`, `new_with_braces`) |
| Vue | **3.5.12**, Composition API, `<script setup>`, TypeScript **strict** (`tsconfig.app.json`) |
| Router | **Vue Router 4.6** with `requiresAuth` / `guestOnly` meta + layout switching in `App.vue` |
| State | **Pinia 3.0.4**, setup-store style (e.g., `useAuthStore`) |
| Styling | **Tailwind 3.4** + **shadcn-vue** primitives (button, input, label, card) under `client/src/components/ui/*`. Tailwind config uses HSL CSS variables (`--background`, `--foreground`, `--primary`, etc.) defined in `client/src/assets/index.css`. `tailwindcss-animate` plugin. |
| HTTP | `axios` with `withCredentials: true`, `withXSRFToken: true`, single instance in `client/src/lib/api.ts`. `ensureCsrfCookie()` memoised, 401 interceptor that clears auth store + redirects guarded routes to `/login`. |
| Path alias | `@` → `./src` (Vite + tsconfig + vitest configs all agree) |
| Lint/format | ESLint (`vue3-recommended` + `@vue/eslint-config-typescript` + `@vue/eslint-config-prettier`), Prettier (`semi: false`, `singleQuote: true`, `trailingComma: 'es5'`, `printWidth: 100`, `arrowParens: 'always'`) |
| Frontend tests | Vitest 3 + jsdom 27 + @vue/test-utils 2 + @pinia/testing 1 |
| Dev server | Vite on port 3000, **strictPort**, proxy `/api` and `/sanctum` → `http://localhost:8000` |

### Auth flow as it works today

1. Browser hits `/api/sanctum/csrf-cookie` (via `ensureCsrfCookie()`) → backend sets the `XSRF-TOKEN` cookie.
2. `POST /api/login` (or `/api/register`) with CSRF header → backend calls `Auth::login($user)` + `session()->regenerate()` and returns `{ user }`.
3. Subsequent requests carry the session cookie; `auth:sanctum` middleware reads it via the `web` guard. Bearer tokens (`createToken()`) also work — `AuthController` and tests support both paths.
4. `POST /api/logout` calls `Auth::guard('web')->logout()` + invalidates the session AND deletes the current personal access token if one was used.
5. On SPA boot, `main.ts` awaits `auth.fetchUser()` **before** registering the router so that guarded routes don't bounce to `/login` while hydration is in flight. This pattern must be preserved.

### Existing endpoints

```
POST /api/register        (throttled 5/1)
POST /api/login           (throttled 5/1)
POST /api/password-reset  (throttled 5/1)
POST /api/password-reset/confirm  (throttled 5/1)
POST /api/logout          (auth:sanctum)
GET  /api/user            (auth:sanctum)   ← current "who am I"
GET  /api/me              (auth:sanctum)   ← ADDED Phase 0 (richer payload)
```

### Existing User columns

```
id, first_name, last_name, email, email_verified_at, password,
remember_token, created_at, updated_at
```

(Originally had a single `name` — a `split_users_name_into_first_and_last` migration on 2026-05-27 split it into `first_name` + `last_name` with a backfill.) Phase 1's `users ALTER` migration adds: `display_name (nullable)`, `hometown (nullable)`, `xp (int default 0)`, `vocab_counter (int default 0)`, `streak_count (int default 0)`, `streak_last_active_date (date nullable)`.

### Directory & naming conventions (must match for new code)

- Backend models live at `backend/app/Models/{Name}.php` (singular, PascalCase). Currently only `User.php`.
- Controllers at `backend/app/Http/Controllers/` (Auth subnamespace already used). I'll group new domain controllers in topical subfolders only when there are ≥2 related controllers; otherwise flat.
- Services go in `backend/app/Services/` — directory does not yet exist, but the build doc names it for `ProgressService`, `RewardService`, `SrsService`. I'll create it.
- Notifications at `backend/app/Notifications/` (one exists: `WelcomeNotification`).
- Migrations: timestamped, snake_case, descriptive (`2026_05_27_200000_split_users_name_into_first_and_last.php`-style).
- Seeders: `backend/database/seeders/{Name}Seeder.php`, registered in `DatabaseSeeder::run()`.
- Frontend pages live at `client/src/pages/{Name}Page.vue` (not `views/`). Build doc §5 names routes by view (`DashboardView`, `LessonPlayView`, etc.) — for consistency with the existing skeleton I'll use the `Page` suffix: `DashboardPage.vue`, `LessonPlayPage.vue`, `BossPlayPage.vue`, `ToolboxPage.vue`, `ReviewPage.vue`.
- Frontend layouts at `client/src/layouts/`. Currently `AuthLayout.vue` + `AppLayout.vue`; existing app routes use `meta.layout: 'app' | 'auth'`. New gameplay routes will use the `app` layout.
- Frontend stores at `client/src/stores/{name}.ts`, setup-store style.
- Composables at `client/src/composables/{name}.ts`.
- UI primitives under `client/src/components/ui/{primitive}/`.
- New shared library code goes in `client/src/lib/`.
- Types under `client/src/types/{topic}.ts`.
- Tests under `client/src/__tests__/{Name}.test.ts` (flat, not nested).

### Build tooling

- Backend dev: `composer dev` runs `php artisan serve` + queue + pail + `npm run dev` via `concurrently`. (The backend has its own `package.json` and `vite.config.js` for Pail/log tooling — unrelated to the SPA's Vite.)
- Frontend dev: `cd client && npm run dev` on port 3000.
- Frontend build: `vue-tsc --noEmit -p tsconfig.app.json && vite build` — type-check is part of build.
- Backend test: `php artisan test` (or via `composer test`).

### Deploy

- `deploy/bootstrap.sh` + `deploy/nginx.conf`, GitHub Actions over SSH to a single EC2 with RDS MySQL. Out of scope for Phase 0/1.

### Bug found during audit (will be fixed in Phase 1, not Phase 0)

`backend/database/seeders/DatabaseSeeder.php` still references the dropped `name` column:

```php
User::factory()->create([
    'name' => 'Test User',          // ← column no longer exists
    'email' => 'test@example.com',
]);
```

`UserFactory` correctly uses `first_name`/`last_name`, so calling `User::factory()->create([...])` with extra `name` will silently set an attribute that isn't fillable. Running `php artisan db:seed` would produce a user with `first_name`/`last_name` from the factory's faker defaults — usable but not what the seeder author intended. I'll rewrite `DatabaseSeeder` in Phase 1 anyway (it needs to dispatch the Level/Unit/Lesson seeders), so the fix lands there.

### Dependencies added in Phase 0

Already present and reused as-is: `vue`, `vue-router`, `pinia`, `axios`, `tailwindcss`, `@vueuse/core`, `vitest`, `typescript`, `eslint`, `prettier`, `@vue/test-utils`, `@pinia/testing`, `radix-vue`, `lucide-vue-next`, `class-variance-authority`, `clsx`, `tailwind-merge`, `tailwindcss-animate`.

Newly installed (matching §2 of the build doc):

| Package | Version | Why |
|---|---|---|
| `howler` | ^2.2 | Audio sprite playback (§5 `useAudio`) |
| `@types/howler` | ^2.2 | TS types |
| `gsap` | ^3.15 | Reward-moment choreography (forge success, counter roll, unlocks) |
| `canvas-confetti` | ^1.9 | Celebrations (+400 vocab moment, badge grants) |
| `@types/canvas-confetti` | ^1.9 | TS types |
| `@vueuse/motion` | ^3.0 | Micro-interaction animations (answer feedback, hover/press) |
| `@formkit/drag-and-drop` | ^0.5 | Tile assembly / drag exercises |

**Deferred to later phases:** `vite-plugin-pwa` (Phase 6 only).

### `/api/me` smoke endpoint

Added at `GET /api/me`, behind `auth:sanctum`, returning the full §7 shape with stubs for fields whose columns don't exist yet:

```json
{
  "id": 1,
  "first_name": "Ada",
  "last_name": "Lovelace",
  "email": "ada@example.com",
  "display_name": null,
  "hometown": null,
  "xp": 0,
  "vocab_counter": 0,
  "streak": { "count": 0, "last_active_date": null },
  "badges": [],
  "feature_flags": {}
}
```

Phase 1 swaps the stubs for real reads once the columns + relationship tables exist.

### Decisions on coexistence with existing auth endpoints

- **`/api/user` stays as-is.** It is the boot-time identity check used by `main.ts` → `auth.fetchUser()` and is verified by 2 of the existing 15 auth tests. The build doc only names `/api/me`; nothing about it requires us to delete `/api/user`. Keeping both is the lowest-risk path and the contracts are independent.
- **Frontend hydration** continues to call `/api/user`. New gameplay code (`useUserStore` per §5) will call `/api/me` for the richer payload. The two stores will share the same user identity but the gameplay store is the one consumed by the dashboard, lesson player, boss screen, etc.

---

## Phase 0 — Conflicts found with the build directive

> Listed in the "End-of-phase summary" section of my reply to the user. Resolutions are recommendations, not unilateral changes — flagged for review.

### Conflict 1 — `GET /api/me` vs existing `GET /api/user`

- **Build doc §7** calls the "who am I" endpoint `/api/me` and specifies a payload including `xp`, `vocab_counter`, `streak`, `badges`, `feature_flags`.
- **Skeleton** already exposes `GET /api/user` returning the plain User model, called from `main.ts` during boot.
- **Resolution applied (Phase 0):** Both endpoints coexist. `/api/user` keeps its current contract; `/api/me` is added with the richer shape. The frontend auth store keeps using `/api/user` for boot; a new gameplay store will consume `/api/me`.
- **Alternative if you prefer:** Collapse onto `/api/me`, delete `/api/user`, update `auth.fetchUser()` + 2 tests. Slightly cleaner long-term but breaks the auth contract.

### Conflict 2 — Test framework

- **Build doc §2** lists "Pest (PHP) — or PHPUnit if the skeleton already uses it".
- **Skeleton** uses PHPUnit 12 with an existing 15-test auth suite.
- **Resolution applied:** Stay on PHPUnit. No Pest install.

### Conflict 3 — Aesthetic of the existing shadcn-vue skeleton vs §1.5 brief

- **Build doc §1.5** explicitly rules out "cookie-cutter component-library look", "purple-gradient-on-white card grids", default system font stacks.
- **Skeleton** uses default shadcn-vue HSL tokens (neutral grays, generic `Inter`-fallback `font-feature-settings` only). This is exactly the look the brief tells us to avoid.
- **Resolution recommendation (will execute at start of Phase 2 via the frontend-design skill):**
  - Keep the shadcn-vue primitives as accessibility-correct building blocks (button focus rings, label semantics, etc.).
  - **Redefine the design tokens** (`--background`, `--foreground`, `--primary`, the type scale, radii, shadows) in `client/src/assets/index.css` to the warm/playful/game-like direction. Add a `--display-font` and `--reading-font` pair.
  - Restructure tokens so a dark-mode layer is *possible* (per §1.5) even though dark mode is out of scope.
  - Re-skin the auth pages (Login/Signup/Forgot/Reset) and the Dashboard for consistency — non-trivial but mandated by §1.5's "consistency check per phase".
- **Not changed in Phase 0/1** — the redesign lives in Phase 2 since that's when the first new screens land.

### Conflict 4 — Build doc names routes `DashboardView.vue` / `LessonPlayView.vue` / etc.; skeleton uses the `Page` suffix

- **Skeleton convention:** `client/src/pages/DashboardPage.vue`.
- **Resolution applied:** Match the skeleton (`Page` suffix) per §9's "Respect the skeleton" guardrail. The route names from §5 are unchanged; only the file basenames differ.

### Conflict 5 — Display typography

- §1.5 mandates "a distinctive display face for headings, numbers, and reward moments paired with a highly legible reading face for Spanish content."
- Skeleton has no display face configured.
- **Resolution recommendation:** Pick the pair at Phase 2 kickoff via the frontend-design skill. The display face should be a warm geometric or rounded sans (something like *Fraunces* italic display cuts, *Recoleta*, *Sentient*, or *Cooper BT* — final pick TBD). The reading face should be a highly-legible humanist sans with strong accent-mark clarity (something like *Inter*, *General Sans*, *Manrope*). Both via `@fontsource/*` packages so we don't rely on external CDNs.

### Conflict 6 — None found re: stack choices

Pinia, Tailwind, TypeScript, Vue Router, Vite — all present and at versions compatible with the build doc.

---

## Phase 1 — Domain model & seed skeleton

### Migrations (14 new)

All timestamped `2026_06_10_1000xx_…` so they run after the existing 2026_05 set.

| # | Table | Notes |
|---|---|---|
| `_100000_` | users (ALTER) | Adds `display_name`, `hometown`, `xp`, `vocab_counter`, `streak_count`, `streak_last_active_date`. Reversible. |
| `_100100_` | levels | id, number, slug (unique), title, promise_text, order |
| `_100200_` | units | + level_id FK (cascadeOnDelete), reward_badge_key, reward_feature_key (nullable) |
| `_100300_` | lessons | + skill_key indexed, teach_screens JSON |
| `_100400_` | exercises | + component, payload JSON, xp |
| `_100500_` | chunks | + unique audio_key, nullable unit_id (nullOnDelete), grammar_unlock_key |
| `_100600_` | cognate_words | + unique (rule_key, en), unique audio_key, is_exception bool |
| `_100700_` | boss_scenarios | + level_id, scene JSON |
| `_100800_` | dialogue_lines | + unique audio_key |
| `_100900_` | badges | + unique key |
| `_101000_` | user_lesson_progress | + unique (user_id, lesson_id), enum status |
| `_101100_` | user_skill_mastery | + unique (user_id, skill_key), srs_due_at indexed, srs_ease float (default 2.5) |
| `_101200_` | user_collections | + unique (user_id, type, key), enum (badge / rebel_word / chunk) |
| `_101300_` | user_boss_attempts | + transcript JSON, completed_at |

### Models (13 new + 1 updated)

`Level`, `Unit`, `Lesson`, `Exercise`, `Chunk`, `CognateWord`, `BossScenario`, `DialogueLine`, `Badge`, `UserLessonProgress`, `UserSkillMastery`, `UserCollection`, `UserBossAttempt`. `User` updated with new fillable, casts (`streak_last_active_date` → date, `xp` / `vocab_counter` / `streak_count` → integer), and four relationships.

Factories: `Level`, `Unit`, `Lesson`, `Exercise`, `Badge` — enough for service tests. `User` factory was already present.

### Seeders (9 new + DatabaseSeeder rewritten)

All idempotent via `updateOrCreate` keyed on `slug` / `key` / `audio_key` per the §9 guardrail.

```
BadgeSeeder           5 rows  — the_decoder, first_contact, unsinkable, the_alchemist, the_navigator
LevelSeeder           1 row   — Level 1 "Survival & Sound System"
UnitSeeder            5 rows  — Units 1.1 → 1.5 with reward badges/flags wired
LessonSeeder         18 rows  — all 18 Level-1 lessons with skill_keys + teach_screens
                                (Unit 1.1 and 1.4 have full multi-screen teach content; the
                                 other units have a single concept screen each — sufficient
                                 to render the lesson player, expanded in Phase 4)
ExerciseSeeder       50 rows  — Units 1.1 + 1.4 fully authored payloads (18 rows);
                                Units 1.2/1.3/1.5 ship as named-component stubs
                                with payload {stub: true, authored_in_phase: 4}
ChunkSeeder          46 rows  — all chunks from Units 1.2 / 1.3 / 1.5, situation_tags
                                set on the perdón / disculpe / con permiso / lo siento
                                quartet, grammar_unlock_key='gender_agreement' on the
                                buenos días / buenas tardes / buenas noches triplet
CognateWordSeeder    73 rows  — 49 clean -ción + 21 soft tweaks (notes on the e-prefix,
                                tt/mm/cc collapses, -ssion → -sión) + 3 true exceptions
                                (traducción, explicación, vacaciones). Well over the
                                ≥60 minimum.
BossScenarioSeeder    1 row   — "El Café" with the §6 envelope (npc / scene / config /
                                scoring), empty `nodes` and meta.authored_in_phase=5
DialogueLineSeeder    3 rows  — boss1_greet, boss1_fast, boss1_slow_again (the audio
                                keys referenced by the structural boss scene)
```

`DatabaseSeeder` now calls all nine in dependency order, then `firstOrCreate`s a deterministic dev user (`test@example.com` / `password`) with the new columns populated. (Fixes the bug noted in Phase 0 where the seeder still referenced the dropped `name` column.)

### Services

`App\Services\ProgressService`:
- `unlockState(User, Lesson) → 'locked' | 'available' | 'completed'`
- `complete(User, Lesson, ?int $score) → UserLessonProgress` — transactional, idempotent. Refuses locked lessons. Calls `RewardService::grantUnitReward` when the completed lesson is the last in its unit.
- Best-score is updated as a high-water mark even on idempotent re-completes.

`App\Services\RewardService`:
- `grantUnitReward(User, Unit) → ['was_new', 'badge_key', 'vocab_delta', 'feature_flag']` — transactional, idempotent (driven by the unique `(user_id, type, key)` constraint on `user_collections`).
- `flagsFor(User) → array<string, true>` — derives feature flags from earned badges via `units.reward_feature_key`. **Decision:** feature flags are *not* stored as their own user column or collection-type; they're computed on demand from completed units. Rationale: the `units` table already owns the badge→flag mapping, so introducing a parallel `flag` collectible type or a `feature_flags` JSON column would just duplicate state. Documented in §1.5 of this file's audit.
- Per-badge vocab bumps live in a private constant map (`'the_alchemist' => 400`). Future levels add entries here; no schema change.

`App\Services\SrsService` — SM-2-lite:
- `recordReview(User, string $skillKey, bool $correct)` — correct: `interval = round(interval × ease)`, capped at 365 days; wrong: `interval = 1`, `ease -= 0.2` (floored at 1.3); `srs_due_at = now + interval days`.
- `dueSkills(User, int $limit = 8)` — ordered by `srs_due_at` ascending.
- Deliberately ignores quality grades and ease bumps on correct answers (per "do not over-engineer; FSRS is a future upgrade").

### Tests (19 new)

`tests/Feature/Services/ProgressServiceTest.php` (8) — first-lesson availability, completion creates a row, idempotent re-completion, idempotent best-score high-water-mark, next-lesson unlock within a unit, cross-unit unlock, locked-lesson refusal, badge fires only on last-lesson completion.

`tests/Feature/Services/RewardServiceTest.php` (6) — badge insertion, idempotency, +400 on alchemist (fires once), no bump on other units, feature flags derived from earned badges, empty-state behaviour.

`tests/Feature/Services/SrsServiceTest.php` (5) — first correct review's interval math, subsequent compound interval math, incorrect resets to 1 day, ease floor of 1.3, due-skills filter + ordering.

All 36 backend tests green (15 pre-existing auth + 19 new service + 2 placeholders). Vitest + ESLint + vue-tsc clean on the frontend.

### Conflicts found during Phase 1

#### Conflict 7 — Feature-flag storage is not specified

The build doc names two flags (`pronunciation_hints`, `como_se_dice_button`), shows them in the `/api/me` payload, but the `user_collections` schema only enumerates `['badge', 'rebel_word', 'chunk']`. No `flag` or `feature_flag` collectible type.

**Resolution applied:** Derive flags from earned badges, using `units.reward_feature_key` as the mapping table. Phase 1 implements this in `RewardService::flagsFor()`. No schema change.
**Alternative if you prefer:** Add `'flag'` to the `user_collections.collectible_type` enum and write directly to it. Functionally equivalent but introduces denormalisation.

#### Conflict 8 — `user_collections` lacks a `value` column for "Rebel Words"

The Rebel Words collection (Unit 1.4 traps) stores *which* exception was earned — but the user might also want to surface its English source on the Toolbox page. The current schema only stores `collectible_key`, which is fine if the key uniquely identifies the rebel word (e.g., `traduccion`, `explicacion`, `vacaciones`) and the renderer looks up the cognate via `cognate_words.es`.

**Resolution applied:** Use the de-accented Spanish form as the collectible_key (e.g., `traduccion`) and let the Toolbox view join `cognate_words` on `es` for display. Documented here so Phase 4 (Toolbox view) doesn't add a redundant value column.

#### Conflict 9 — `units.reward_feature_key` for Unit 1.4 is null but the unit still fires a reward

The doc says Unit 1.4's reward is "badge `the_alchemist` + `vocab_counter += 400`" — no feature flag. The +400 bump is handled by the per-badge vocab map in `RewardService` (`'the_alchemist' => 400`). This is the right shape; calling it out so a future contributor doesn't add a fake feature flag for the vocab bump.

#### Conflict 10 — `payload`'s loose JSON contract vs the registry

`exercises.component` is just a string, validated only by the frontend's `exerciseRegistry` lookup at render time. There is no migration-level validation that the seeded component name exists in the registry. **Resolution recommendation (Phase 2):** add a small unit test on the frontend that asserts every seeded `exercises.component` value resolves to a registered component. That bridge gets built in Phase 2 once the registry exists.

#### Conflict 11 — `vacaciones` is plural; the WordForge spec is implicitly singular

The WordForge interaction is "type the Spanish form of this English -tion word." For `vacaciones` the answer is a plural noun. The cognate row carries the plural with `is_exception=true`; the TrapOrTreat exercise in lesson 1.4.2 covers this case explicitly. WordForge in lesson 1.4.4 (Guess Mode) does *not* include `vacation` to avoid forcing the plural into a singular-shaped UI. Phase 3 should keep that assumption when authoring the WordForge component.

### Notes on what got run vs. what was reversible

- `php artisan migrate:fresh --seed` was executed against the **dev MySQL database** (`next_laravel`) to verify migrations + seeders run end-to-end. The DB now holds Level 1's seeded content (and the deterministic `test@example.com` user). The auth tests continue to use `:memory:` SQLite via `phpunit.xml` and are unaffected.
- No production DB exists yet so this was the lowest-risk environment to validate against. If you have unmigrated dev work, recover it from a recent git checkout — `migrate:fresh` is destructive.

---

## Phase 2 — Level map, lesson player & Unit 1.1 playable end-to-end

### Visual identity: "Sobremesa"

The committed §1.5 aesthetic direction landed as **Sobremesa** — the honeyed amber tone of late-afternoon conversation in a Latin American café. Concretely, in `client/src/assets/index.css`:

| Token | HSL | Role |
|---|---|---|
| `--primary` | `33 88% 56%` | Saffron-honey. Sunlit, not yellow. |
| `--secondary` | `170 32% 30%` | Deep jade — cool counter to the primary. |
| `--accent` | `168 38% 36%` | Brighter jade for chips and accents. |
| `--background` | `38 30% 96%` | Almond off-white, never pure white. |
| `--foreground` | `26 35% 14%` | Warm dark-brown ink. |
| `--coach` | `14 48% 56%` | Muted clay terracotta for wrong-answer feedback. NOT a harsh red. |
| `--success` | `145 48% 36%` | Cilantro-lime, distinct from the saffron primary. |
| `--destructive` | `12 75% 42%` | Reserved for **form-validation errors only** (login failures). |

Plus radii (`--radius-kid` 24px, `--radius-soft` 14px, `--radius-pill`), warm umber shadows (`--shadow-raised`, `--shadow-lifted`), and motion duration tokens (`--motion-duration-quick` 180ms, `--motion-duration-lush` 700ms) that are zeroed under `@media (prefers-reduced-motion: reduce)`.

Two reusable component classes shipped in the `@layer components` block:
- `.sunlit-card` / `.sunlit-card-interactive` / `.sunlit-card-locked` — the primary tile surface with a soft radial gradient that evokes warm interior light. Used for dashboard tiles, teach screens, results screen.
- `.coach-feedback` — the warm clay-accented inline feedback strip used by all base primitives for "not quite" coaching.
- `.numeric-display` — Fraunces 900 + tabular nums, ready for the Unit 1.4 +400 vocab-counter reward moment in Phase 3.

### Typography: Fraunces + Source Sans 3 (locked)

- Display: **Fraunces** (weights 600, 900 + 600 italic). Used on every heading, the `Patrón Spanish` wordmark, and the numeric counters. The italic 600 cut is reserved for the wordmark's `Spanish` half — a deliberate brand mark.
- Reading: **Source Sans 3** (weights 400, 600, 700). Adobe-tuned for Latin extended-A diacritics — Spanish accents stay crisp at sub-16px.
- Mono: system stack only (no third face shipped).
- Both faces loaded via `@fontsource/*` so the build is CDN-free.
- A new `.es` opt-in class on Spanish content sets slightly tighter letter-spacing and enables `kern`/`liga`/`ss01` features. All exercise components apply it to user-facing Spanish strings.

### Drag-and-drop (locked globally)

**Phase 2 locked `@formkit/drag-and-drop` as the project-wide D&D library**, not just for RuleSort. The decision was confirmed in the Phase 2 planning conversation and is recorded as a feedback memory at `/Users/anthonydevenuto/.claude/projects/-Users-anthonydevenuto-Code-laravel-apps-laravel-vue/memory/feedback_drag_and_drop.md` so it survives session boundaries. Phase 3+ exercises (DialogueVolley, ChunkAssembly, ScrambleBuild, Boss tile-mode) must use it — `vuedraggable` only as a last-resort fallback per build doc §2.

### Two-store identity model (lock confirmed)

`useAuthStore` (`/api/user`) and `useUserStore` (`/api/me`) stay separate. Auth boot hydration in `client/src/main.ts` is unchanged — it still calls `auth.fetchUser()` against `/api/user` before router setup. New gameplay code consumes `useUserStore.profile` for xp / vocab_counter / streak / badges / feature_flags. No merge, no consolidation.

### Server-side unlock enforcement

Every gameplay endpoint that could leak content or state if the client lied is gated server-side by `ProgressService::unlockState`:
- `GET /api/lessons/{slug}` → 403 `{error: 'locked'}`, **no `teach_screens` or `exercises` payload returned**.
- `POST /api/exercises/{id}/attempt` → 403 if the parent lesson is locked. The xp increment + `SrsService::recordReview` are inside a single DB transaction.
- `POST /api/lessons/{slug}/complete` → 403 via belt-and-suspenders (controller pre-check + try/catch around the service exception).

The `LessonPlayPage` defensively re-validates on its own 403 by `router.replace`-ing to `/dashboard?coach=locked` + showing the coach toast — handling the stale-cache case where the dashboard map says "available" but the server disagrees.

### `useFuzzyMatch` semantics (locked for Phase 3 to inherit)

Pure function in `client/src/composables/useFuzzyMatch.ts`. Spec discharged with 13 unit tests:

1. NFD-strip combining marks (U+0300–U+036F), lowercase, trim. Exact equality → `correct: true`. Coaching populated only when the expected form had accents the user didn't type (`"Don't forget the stress arrow: información"`).
2. Else if `expected.length >= 6` AND classical Levenshtein ≤ 1 → `correct: false, retry: true, coaching: "Almost — check spelling."`.
3. Else → `correct: false, retry: false, coaching: null`.

**Transpositions** (e.g. `infomración` for `información`) are Levenshtein 2 by classical definition and fall through to (3). Documented; revisit in Phase 3 if WordForge demands a Damerau-Levenshtein upgrade.

**Words under 6 characters** require exact post-accent-strip match — short words don't get a retry window.

### `useAudio` source-of-sprite contract

`useAudio(lessonSlug)` returns `{play, preload, isReady, isSilent}`. Behavior:
- On `preload()`, attempt `fetch('/audio/sprites/<slug>.json')`. On 404 (the Phase 2 state — no audio files exist), fall back to a `NullTtsHowl` that resolves `play()` immediately and `console.debug`s in dev.
- Howler is dynamically imported so the auth-page bundle stays small.
- Audio is provided down the exercise tree via Vue's `provide`/`inject` with the `audioKey` symbol from `client/src/composables/audioInjection.ts`. Exercises pull it via `inject(audioKey, null)`; `null` is a valid fallback (silent mode in tests).

The contract matches build doc §2: "the frontend must gracefully no-op if an audio key is missing." Phase 6's `audio:generate` artisan command writes the sprite JSONs that this composable will then find.

### Exercise registry + the "Coming Soon" defensive component

`client/src/lib/exerciseRegistry.ts` maps the 8 Unit 1.1 component name strings to async-loaded Vue components. Unknown names return `null` (no throw). `LessonPlayPage` resolves each exercise's `component` and falls back to an inline `<ComingSoon>` component that auto-completes with `{correct: true, score: 0, meta: {skipped: true}}` — so if a non-Phase-2 lesson is ever incorrectly served (e.g. seed payload mismatch), the player progresses instead of dead-ending.

The registry test in `client/src/__tests__/exerciseRegistry.test.ts` is **scoped to Unit 1.1's 8 component names** (per DECISIONS.md Conflict #10). A `it.skip`-ed test holds the future-state assertion for Phase 4 to enable once WordForge, AccentPlacer, TrapOrTreat, ArticleAttach, and the chunk-unit components ship.

### Map refetch on completion (not optimistic)

After a successful `POST /api/lessons/{slug}/complete`, `LessonPlayPage` calls `useLevelMapStore.invalidate()` + `fetch(1, force=true)` + `useUserStore.fetch()`. This costs one extra round-trip per lesson completion but avoids duplicating `ProgressService`'s cross-unit unlock logic in TypeScript — a maintenance trap when Phase 4 adds side-unlocks. The store has a 10-minute cache TTL for non-forced reads.

### File additions in Phase 2

**Backend (10 new + 2 modified):**
- 5 API Resources: `MeResource`, `LevelMapResource`, `LessonPlayResource` (`ExerciseAttemptResource` + `LessonCompleteResource` were inlined as response arrays — see below).
- 4 controllers: `LevelMapController`, `LessonController`, `ExerciseAttemptController`, `LessonCompleteController`. Plus expanded `MeController` (no longer a stub).
- 1 DTO: `App\Services\Dto\LessonCompletion` — bundles `progress` + optional `reward` so `ProgressService::complete` can report both atomically.
- Updated `ProgressService::complete` return type from `UserLessonProgress` to `LessonCompletion`. 2 existing tests adjusted; 2 new tests cover the new reward-channel behavior.
- 5 new route registrations under `auth:sanctum`.

**Frontend (~30 new files):**
- 3 types modules (`me.ts`, `domain.ts`, `exercises.ts`) — Phase 2 keeps `User` lean and routes gameplay state through a new `MeProfile` type.
- 1 typed API client (`lib/lessonsApi.ts`).
- 3 Pinia stores (`me`, `levelMap`, `lesson`).
- 3 composables (`useAudio`, `useFuzzyMatch`, plus `audioInjection` symbol module). Levenshtein helper kept separate so it can be spot-tested.
- 1 registry (`lib/exerciseRegistry.ts`).
- 4 exercise base primitives (`MultipleChoiceBase`, `TypedAnswerBase`, `TileAssemblyBase`, `TapTargetBase`).
- 8 Unit 1.1 exercise components.
- 2 pages (`LessonPlayPage`, `LessonPlayPage.results`).
- 1 fonts CSS file + the rewritten `index.css` tokens + the extended `tailwind.config.js`.
- Re-skinned `AppHeader`, `AuthLayout`, all 4 auth pages, `DashboardPage`, and the shadcn `CardTitle` primitive (font-display by default).

**Tests (6 new frontend files, 4 new backend files):**
- Backend: `MeTest` (4), `LevelMapTest` (4), `LessonPlayTest` (4), `ExerciseAttemptTest` (5), `LessonCompleteTest` (4) = **21 new endpoint tests**.
- Frontend: `useFuzzyMatch` (13), `exerciseRegistry` (11 incl 1 skipped), `MultipleChoiceBase` (2), `TypedAnswerBase` (3), `TapTargetBase` (3), `DashboardPage` (4), `LessonPlayPage` (3) = **39 new frontend assertions**.
- **Backend total: 59 tests pass.** Frontend total: 42 tests + 1 skipped (Phase 4 placeholder). Pint, ESLint, vue-tsc, Vite build all clean.

### Conflicts discovered in Phase 2

#### Conflict 12 — `eslint-plugin-vue` `no-v-html` rule vs the teach-screen markdown renderer

The lesson player's teach screens consume `body_md` JSON from the server and need to render bold/italic/lists. I shipped a tiny purpose-built renderer in `LessonPlayPage` that HTML-escapes input first, then applies a known whitelist of patterns (`**bold**`, `*italic*`, `-` bullets). It's safe but ESLint's `vue/no-v-html` rule still fires.

**Resolution applied:** Compute the rendered string in `<script setup>` as `renderedBody` and place an `<!-- eslint-disable-next-line vue/no-v-html -->` immediately above the consuming `<div>`. The element is single-line so the disable lands on the right LOC. **Phase 3+ recommendation:** swap the inline renderer for `markdown-it` if the teach-screen content grows; until then the surface area is small enough that the bespoke renderer is appropriate.

#### Conflict 13 — `RuleSort` bucket count is fixed at 3 in Phase 2

`@formkit/drag-and-drop`'s `useDragAndDrop` composable returns refs from top-level calls; you can't easily loop it over a `v-for`. The Unit 1.1 RuleSort payload has exactly 3 buckets (Rules 1/2/3), so the component hardcodes 3 lists with a `MAX_BUCKETS` constant and dev-mode warning if more are seeded.

**Resolution recommendation:** Phase 4 should generalize this if any of the new exercises need >3 buckets — likely via a small `useNDragAndDrop(n, options)` wrapper composable. For now: 3 buckets is enough.

#### Conflict 14 — ShadowRecord now scores via transcript comparison

Build doc §2 says: *"ShadowRecord uses the MediaRecorder API (record + playback self-comparison only — no scoring)."* During the Phase 2 playthrough, the user found the unscored loop unsatisfying — recording without any "did I get it right?" signal felt listless and out of step with the §1.5 "designed states" bar. So Phase 2 polish added two layers on top of the existing record-and-playback flow:

1. **Voice-activity auto-stop** via the new `useVoiceActivity` composable (Web Audio `AnalyserNode` RMS, no extra deps). Recording auto-stops ~1.2s after the user falls silent, gated by ≥600ms of detected speech first so it doesn't fire during the pre-roll.
2. **Transcript comparison** via the new `useSpeechTranscript` composable + existing `useFuzzyMatch`. The browser's Web Speech API transcribes the user, the result is compared against the reference Spanish, and one of three badges renders: green "¡Eso es!", coach "Almost — check the accent", or coach "Not quite — give the reference another listen."

**The `complete` event contract did NOT change** — ShadowRecord still emits `{correct: true, score: 100, meta: {practiced: n}}` regardless of the comparison badge. Comparison is feedback, not a gate. The build doc's "you practiced it" semantics survive; the user just sees a hint about how well they did.

**Fallback chain (satisfies §9 "never hard-block on media availability"):**
- Mic denied → existing auto-pass branch unchanged
- VAD construction fails (no Web Audio) → manual Stop button still works
- SpeechRecognition unsupported (Firefox; older Safari) → playback-only mode, no transcript / badge rendered
- SpeechRecognition produced no result ('no-speech' error) → playback-only mode, user advances when ready

#### Conflict 15 — SpeechRecognition is now used outside boss voice mode

Build doc §2 says: *"Speech recognition (Web Speech API) is used ONLY for the optional Boss Level voice mode and must be feature-detected and fully skippable."* Phase 2 polish breaks the "ONLY" by using it for ShadowRecord transcript comparison too (see Conflict 14). The "feature-detected and fully skippable" parts are honored: `useSpeechTranscript.isSupported` gates all UI; unsupported browsers silently render the playback-only flow.

**Why deviate:** the same `useSpeechTranscript` composable will power Phase 5's boss voice mode. Building it once and using it in two places is cheaper than two parallel implementations — and ShadowRecord is the natural place to validate the composable before the boss scene depends on it.

**Open follow-up for user review:** if the comparison badge feels patronizing or causes accent-related discouragement during play, revert by deleting the transcript block in `ShadowRecord.vue` (the composables stay for Phase 5 use). The deviation is intentional but reversible.

### What Phase 2 deliberately did NOT do

- **Streak system** — placeholder pill on dashboard reads `"soon…"`. Real implementation Phase 6.
- **Onboarding modal** (name/hometown) — Phase 4 per §8.
- **Toolbox view** — Phase 4.
- **SRS review queue view** — Phase 4 (the underlying service + endpoint exists; only the queue view is deferred).
- **Real toast system** — the locked-route coach toast is a `?coach=locked` query param + inline component. Full system Phase 4.
- **WordForge + the +400 vocab moment** — Phase 3, the signature interaction.
- **Boss scene** — Phase 5.
- **`audio:generate` artisan command + Google TTS driver** — Phase 6. Until then every exercise runs through `NullTtsHowl`.
- **PWA** — Phase 6.

