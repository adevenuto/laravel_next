# CI/CD Flow

How the GitHub Actions pipeline ships code from `main` to the live EC2 box.

---

## High-level picture

```
                       ┌─────────────────────────────┐
                       │ git push origin <branch>    │
                       └──────────────┬──────────────┘
                                      │
                ┌─────────────────────┴─────────────────────┐
                │                                           │
        push to main                              pull_request → main
                │                                           │
                ▼                                           ▼
       Run test + deploy                          Run test only (no deploy)
                │
       ┌────────┴────────┐
       │                 │
       ▼                 ▼
  backend/** changed?  client/** changed?
       │                 │
       ▼                 ▼
 backend.yml fires   client.yml fires
       │                 │
       │  GitHub Actions runner (Ubuntu)
       │                 │
       │  - tests          - lint / type-check
       │  - rsync to EC2   - build standalone bundle
       │  - SSH post-deploy- rsync to EC2
       │                   - SSH: pm2 reload
       ▼                 ▼
   EC2 t2.micro (one box, two app dirs):
   ├─ /var/www/laravel_next/backend  ← PHP-FPM 8.5 serves /api, /sanctum
   └─ /var/www/laravel_next/client   ← Next.js standalone via pm2 on :3000
                                         ↑
                            nginx :80 reverse-proxies above
```

---

## Workflows

Two independent workflows live in `.github/workflows/`:

| File | When it runs | What it does |
|---|---|---|
| `backend.yml` | Push or PR to `main` touching `backend/**`, or manual dispatch | Run PHPUnit → if push to main, rsync backend → SSH: composer install, migrate, cache, reload php-fpm |
| `client.yml` | Push or PR to `main` touching `client/**`, or manual dispatch | Lint + type-check → build standalone bundle → if push to main, rsync to EC2 → pm2 reload |

Both use the **same four GitHub secrets** for SSH access:

| Secret | Value |
|---|---|
| `EC2_HOST` | EC2 Elastic IP |
| `EC2_USER` | `ubuntu` |
| `EC2_SSH_KEY` | Private SSH key contents |
| `NEXT_PUBLIC_API_URL` | `http://<elastic-ip>` (baked into client bundle at build time) |

---

## Triggers — when does the pipeline fire?

Three event types trigger each workflow:

### 1. `push` to `main`

Normal flow. Both workflows are path-filtered, so:

| Push touches… | Backend fires? | Client fires? |
|---|---|---|
| only `backend/**` | ✅ | ❌ |
| only `client/**` | ❌ | ✅ |
| both | ✅ | ✅ (parallel) |
| only `docs/**` or `deploy/**` or `README.md` | ❌ | ❌ |
| `.github/workflows/backend.yml` | ✅ | ❌ |
| `.github/workflows/client.yml` | ❌ | ✅ |

A push touching only docs/deploy/etc. **won't redeploy anything** — intentional, since nothing the app serves changed.

### 2. `pull_request` against `main`

Same path filters apply, but **only the test/build job runs**. The deploy job is gated by:

```yaml
if: (github.event_name == 'push' && github.ref == 'refs/heads/main') || github.event_name == 'workflow_dispatch'
```

So PR runs verify the code builds + tests pass but never touch the server.

### 3. `workflow_dispatch` (manual)

Both workflows expose a **Run workflow** button in the GitHub Actions UI. Use this for:
- Re-running a deploy after fixing something on the server (e.g. perms, env)
- Rolling forward without a code change
- Bootstrapping the very first deploy (commits that introduce the workflow files sometimes don't fire automatically)

Manual dispatch deploys to whichever branch you select in the UI (default: `main`).

---

## What happens during a deploy

### Backend deploy (~30–45s)

1. **Test** — sets up PHP 8.5 on the runner, installs Composer deps with cache, runs `composer test` (PHPUnit).
2. **rsync `backend/` to EC2** — excludes `.env`, `vendor/`, `storage/logs`, `storage/framework/{cache,sessions,views}`, `.git/`. Files arrive owned by `ubuntu` (the SSH user).
3. **SSH post-deploy** on the box:
   - `sudo chown -R ubuntu:www-data .` — restores the ownership model
   - `sudo chmod -R g+ws storage bootstrap/cache` — setgid so PHP-FPM's new files inherit `www-data` group
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan migrate --force` — applies any new migrations
   - `php artisan config:cache && php artisan route:cache` — production caches
   - `sudo systemctl reload php8.5-fpm` — graceful reload, no downtime

### Client deploy (~1–2 min)

1. **Lint + Build** on the runner:
   - `npm ci` (cached)
   - `npm run lint` + `npm run type-check`
   - `NEXT_PUBLIC_API_URL=<secret> npm run build` — produces `.next/standalone/`
2. **Stage** — copies `public/` and `.next/static/` into `deploy_out/` (Next.js standalone doesn't auto-include them)
3. **Upload artifact** — `deploy_out/` is shipped to GitHub Actions storage (1-day retention)
4. **Deploy job**:
   - Downloads the artifact
   - rsync to `/var/www/laravel_next/client/.next/standalone/` on EC2
   - SSH: `sudo chown -R ubuntu:ubuntu /var/www/laravel_next/client`
   - `pm2 reload laravel_next_client` (or `pm2 start` if the process doesn't exist yet) — graceful, no downtime

---

## What about features that touch both?

Common scenario: new Laravel model + migration + controller + route, paired with a new Next.js page and API call.

**Both workflows fire in parallel.** They don't coordinate. Typical timeline:

```
t=0      Push lands
t=0      Both runners pick up the job
t=15s    Backend tests pass; rsync starts
t=30s    Backend rsync done; SSH post-deploy runs
t=45s    Backend deploy complete → new routes + migration live
t=60s    Client lint + type-check done; build starts
t=2:00   Client build done; rsync + pm2 reload
t=2:15   Client deploy complete → new UI live
```

For ~90s, the new backend is live but the old client is still being served. That's almost always **safe**:
- New backend routes the old client doesn't know about → unused, no impact
- New model + new column → fully additive, old client ignores it

**When this gets dangerous:**
- You **rename** an existing API endpoint → old client breaks against new backend for ~90s. Use a transitional approach: add the new endpoint, deploy both, remove the old endpoint in a later deploy.
- You **drop a column** the existing client still reads → old client crashes for ~90s. Same fix: two-step.
- You change a **request/response shape** the existing client depends on → same risk pattern.

For pure additive changes (new feature, new endpoint, new page), shipping both at once is fine.

---

## Concurrency

Each workflow has a concurrency lock:

```yaml
concurrency:
  group: deploy-backend     # or deploy-client
  cancel-in-progress: false
```

If you push twice in quick succession, the second deploy **queues** behind the first (rather than running in parallel or canceling the first). This prevents two deploys from racing to rsync into the same directory.

---

## Path filters — gotchas

A push that **only modifies `.github/workflows/backend.yml`** triggers Backend (because the workflow path matches itself). Same for client. This is intentional — when you edit a workflow file, you usually want it to run once to validate.

A push that modifies `docs/`, `deploy/`, `README.md`, `.editorconfig`, etc. **doesn't trigger anything**. If you want to test a `deploy/bootstrap.sh` change end-to-end, you need to apply it manually on EC2 (the workflow doesn't bootstrap; it just deploys app code).

---

## Manual rollback (no automation yet)

Currently there's **no built-in rollback button**. If a deploy breaks production, options are:

1. **Push a revert commit** to `main` — `git revert <bad-sha>`, push → CI redeploys the previous state. Cleanest, ~5 min recovery.
2. **SSH in and roll back manually** — restore from git: `cd /var/www/laravel_next/backend && sudo -u ubuntu git checkout <prev-sha> -- .` then restart php-fpm. Faster but doesn't update what `main` looks like.
3. **Re-run a previous successful workflow** — Actions tab → old successful run → "Re-run all jobs". Deploys the artifact / sha of that run.

---

## Known gaps (future polish)

- **No HTTPS yet** — `http://<elastic-ip>` only. Adding a custom domain + Let's Encrypt (via certbot) or ALB + ACM is a follow-up.
- **No environment separation** — there's only one environment (production). A `staging` branch + duplicate EC2 box would let you test changes before they hit users.
- **No real mail** — `MAIL_MAILER=log`. Emails accumulate in `storage/logs/laravel.log`. AWS SES is the natural next step.
- **No build OOM mitigation** — `npm run build` runs on the GitHub runner (plenty of RAM), so the t2.micro's 1 GB doesn't constrain it. But if you ever need to build on EC2 (e.g. some weird hotfix), add swap first.
- **Sessions in DB, not Redis** — fine for small traffic; if it ever matters, add ElastiCache.
- **SSH key auth, not OIDC** — modern AWS-side auth would use OpenID Connect federated credentials so GitHub Actions could call AWS APIs directly without long-lived secrets. SSH is simpler and works.

---

## Common failures + first fix

| Symptom | Likely cause | First thing to check |
|---|---|---|
| rsync: `Permission denied (13)` / `Operation not permitted` | EC2 dir owned by wrong user | `ls -ld /var/www/laravel_next/backend` — should be `ubuntu:www-data` |
| SSH: `invalid format` / `Permission denied (publickey)` | `EC2_SSH_KEY` secret missing trailing newline | Re-paste the key including the final blank line |
| Workflow doesn't fire on push | Path filter didn't match | `git diff --name-only HEAD~1 HEAD` — confirm files match `backend/**` or `client/**` |
| Workflow doesn't fire on new branch | `push` event needs the workflow file already on the target branch | Use `workflow_dispatch` for the first run after merging workflows to a new branch |
| Backend deploy succeeds but `/api/*` returns 500 | `php artisan config:cache` cached old/wrong `.env` | SSH in: `cd /var/www/laravel_next/backend && sudo -u ubuntu php artisan config:clear && sudo -u ubuntu php artisan config:cache` |
| Client deploy succeeds but page doesn't update | pm2 has stale module cache | SSH in: `pm2 restart laravel_next_client` (full restart, not reload) |
| `JavaScript heap out of memory` | Build OOM on the runner (unlikely; rare on standard runners) | Set `NODE_OPTIONS=--max-old-space-size=4096` in the build step |

---

## Day-to-day workflow

1. Branch off `development`: `git checkout -b feature/whatever`
2. Make changes; commit
3. PR into `main` (or push to `development` first and merge later)
4. **Tests run on the PR** — fix any failures before merging
5. **Merge PR → `main`** — appropriate workflow(s) deploy automatically
6. Watch the Actions tab; refresh the live URL when green

That's the loop.
