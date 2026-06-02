# AEOS365 Deploy Guide

Production deployment procedures for both SaaS and standalone hosts.

## Prerequisites

| Component | Version | Notes |
|---|---|---|
| PHP | 8.2+ | with extensions: pdo_mysql, redis, mbstring, openssl, bcmath, gd, intl |
| Redis | 7+ | cache, sessions, queues, Horizon |
| MySQL | 8.0+ | central + per-tenant databases (SaaS) or single DB (standalone) |
| Composer | 2+ | |
| Node.js | 20+ | for Vite asset build |
| supervisor | 4+ | process supervisor (Linux production) |
| nginx or Apache | latest | webserver |

## First-time install

### Both hosts

```bash
# Clone & install
git clone <your-fork> /var/www/aeos365
cd /var/www/aeos365
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Env
cp .env.example .env
# edit .env: APP_KEY=, APP_URL=, DB creds, REDIS, MAIL, SENTRY_LARAVEL_DSN
php artisan key:generate
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### SaaS host migration order

```bash
# 1. Central DB (platform + tenancy metadata)
php artisan migrate --database=central --force

# 2. Tenant DBs migrate on provisioning (Stancl handles this per-signup).
#    For existing tenants, run:
php artisan tenants:migrate --force

# 3. Seed platform admin + plans
php artisan db:seed --class=Database\\Seeders\\PlatformSeeder --force
```

### Standalone host migration order

```bash
php artisan migrate --force
php artisan db:seed --force
```

## Process supervision

The `supervisor/` directory ships three sample configs:

| File | Purpose |
|---|---|
| `aeos365-horizon.conf` | Horizon worker (SaaS, recommended) |
| `aeos365-scheduler.conf` | Laravel scheduler loop (cron replacement) |
| `aeos365-standalone-worker.conf` | Plain `queue:work` workers (standalone alternative to Horizon) |

```bash
sudo cp deploy/supervisor/aeos365-horizon.conf /etc/supervisor/conf.d/
sudo cp deploy/supervisor/aeos365-scheduler.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start aeos365-horizon aeos365-scheduler
```

Adjust paths and `user=` in each file to match your deployment.

## Health checks

| Endpoint | Purpose | Auth |
|---|---|---|
| `/health` | Liveness probe (LB-friendly, 200 OK if process responsive) | none |
| `/health/detailed` | Readiness probe (DB, Redis, queue, disk, memory) | none (rate-limited) |

LB / k8s probes should hit `/health`. Operator dashboards should hit `/health/detailed`.

## Rollback procedure

```bash
# 1. Stop workers
sudo supervisorctl stop aeos365-horizon aeos365-scheduler

# 2. Roll code back
cd /var/www/aeos365
git fetch --tags
git checkout <previous-tag>
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Roll migrations back (ONLY if previous deploy added schema changes)
php artisan migrate:rollback --database=central --step=1

# 4. Clear + warm caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart workers
sudo supervisorctl start aeos365-horizon aeos365-scheduler

# 6. Smoke test /health/detailed
curl -s https://aeos365.com/health/detailed | jq .status
```

## Zero-downtime deploy outline (production)

For sub-minute rollouts, use a symlinked-release pattern (Envoyer / Deployer / custom):

1. `releases/{timestamp}/` checkout + composer + npm build
2. `php artisan migrate --pretend --database=central` — confirm no destructive ops
3. `php artisan migrate --database=central --force`
4. Swap `/var/www/aeos365 → releases/{timestamp}` symlink
5. `php artisan horizon:terminate` — Horizon restarts gracefully on next supervisor tick
6. Hit `/health/detailed` to confirm green
7. Keep last 5 release dirs for fast rollback

## Tenant operations (SaaS)

```bash
# List tenants
php artisan tenants:list

# Migrate a single tenant
php artisan tenants:migrate --tenant=acme

# Run a command in tenant context
php artisan tenants:run cache:clear --tenant=acme
```

## Common errors

| Symptom | Likely cause | Fix |
|---|---|---|
| `Class "Aero\Platform\Http\Middleware\IdentifyTenant" not found` | Old code still references removed middleware (Phase 0 T3) | `composer dump-autoload`, deploy latest code |
| `Undefined array key 'local'` in Stancl FilesystemTenancyBootstrapper | `tenancy.filesystem` config block missing (Phase 0 T5) | Confirm `packages/aero-platform/config/tenancy.php` has the filesystem block |
| Cross-tenant cache hits | `CACHE_STORE` not `redis` | Set `CACHE_STORE=redis` in `.env`; restart workers |
| `composer install` fails on symlinked package | Path repo broken | Re-run from monorepo root, confirm `composer.json` `repositories[].url` is correct |

## Queue topology & throughput (Axis C C3)

Heavy/slow jobs run on dedicated queues so a surge on one cannot starve fast,
user-facing jobs:

| Queue | Jobs | Notes |
|---|---|---|
| `provisioning` | `ProvisionTenant` (timeout 600s) | One slow tenant create must not block the rest. |
| `maintenance` | `AggregateTenantStats`, `ReconcileOrphanedTenantDatabase` | Long batch / cleanup work. |
| `billing` | `ProcessSubscriptionRenewalsJob`, `RetryFailedPaymentsJob` | Stripe-bound; isolate from interactive work. |
| `security`, `notifications`, `emails`, `error-reporting` | auth mail, notifications, error reports | Fast, latency-sensitive. |
| `default` | everything else | |

Give each queue its own worker pool (Horizon `config/horizon.php` supervisors, or
separate `php artisan queue:work --queue=...` supervisor programs). Size pools from:

    workers_needed ≈ (peak jobs/sec for the queue) × (avg job duration in seconds)

e.g. notifications at 20 jobs/sec × 0.2s avg ≈ 4 workers; provisioning at 0.1
jobs/sec × 120s avg ≈ 12 concurrent slots at peak. Measure avg durations in
Horizon (or `failed_jobs`/telemetry) and re-size; never let `provisioning` and
`notifications` share a pool.

## Queue worker options — pick by hosting tier (incl. shared hosting)

The queue topology (provisioning/maintenance/billing/...) works with ANY driver.
Choose the worker by what your host supports. Jobs/failed_jobs/job_batches tables
ship in aero-core migrations, so the **database** driver needs no extra infra.

### A. Shared hosting (cPanel/Namecheap — no Redis, no pcntl, no supervisor)
`.env`: `QUEUE_CONNECTION=database`

Two cron entries in cPanel → Cron Jobs (every minute):
```
* * * * * cd /home/USER/APP && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/USER/APP && php artisan queue:work database --stop-when-empty --max-time=55 --tries=3 --queue=provisioning,maintenance,billing,security,notifications,emails,default >> /dev/null 2>&1
```
`--stop-when-empty` exits when the queue drains (no daemon, no pcntl needed);
`--max-time=55` guarantees it ends before the next minute's run. Priority order
in `--queue` keeps fast user-facing jobs ahead of heavy ones.

### B. VPS / dedicated / cloud (no Redis) — supervised worker
`.env`: `QUEUE_CONNECTION=database`. Run a persistent worker under supervisor
(`deploy/supervisor/aeos365-standalone-worker.conf`):
```
php artisan queue:work database --tries=3 --queue=provisioning,maintenance,billing,security,notifications,emails,default
```

### C. VPS / cloud WITH Redis — Horizon (dashboard + auto-balancing)
`.env`: `QUEUE_CONNECTION=redis`. Linux only (needs `ext-pcntl`):
```
composer require laravel/horizon && php artisan horizon:install
# run via deploy/supervisor/aeos365-horizon.conf
```
Horizon is an OPTIONAL upgrade — A and B cover every other host.

> Tiny / single-tenant trials can use `QUEUE_CONNECTION=sync` (runs jobs inline,
> no worker) — but provisioning/email then block the request, so prefer A.

## Error monitoring (any host)
Sentry is HTTP-only (no daemon/Redis) so it works on shared hosting too:
set `SENTRY_LARAVEL_DSN=` in `.env`, then `php artisan sentry:test`. The package
is already required by aero-core. (Self-host: Sentry-on-Docker or Spatie Flare.)
