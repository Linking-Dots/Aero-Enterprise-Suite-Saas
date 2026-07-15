# Fleet Migration Runner — Design

**Date:** 2026-07-15
**Package:** `aero-platform`
**Status:** Approved (design), pending implementation plan
**Hosting assumption:** VPS / dedicated (a supervisor-managed queue worker is available)

## Problem

AEOS365 uses **database-per-tenant** isolation (stancl/tenancy, `DatabaseTenancyBootstrapper`).
Every deploy that ships a new tenant migration must apply it across **every** tenant database
(~150+ tables per tenant today). The existing `tenant:migrate` command
(`packages/aero-platform/src/Console/Commands/TenantMigrate.php`) is the naive form:

- Failures are collected in an in-memory `$failed[]` array and printed, never persisted.
- A single-threaded `foreach` over all tenants — O(tenants) wall-clock, no parallelism.
- Not resumable: a crash or killed SSH session mid-run loses all progress; the next run
  starts blind over the whole fleet.
- No per-tenant schema-version record, so "which tenants are on the new schema?" is unanswerable.
- A permanently-broken ("poison") tenant fails on every run forever, with no isolation.

This is a data-safety and operability risk the day AEOS365 has paying tenants a failed
migration could silently drift.

## Goal

A **ledgered, resumable, concurrent** fleet migration runner that:

1. Records durable per-tenant migration state so re-runs resume instead of restarting blind.
2. Runs tenants in parallel via a queue, with automatic retry/backoff for transient failures.
3. Quarantines a poison tenant after N attempts so the fleet keeps moving.
4. Exposes fleet migration status (progress + quarantined list) to platform admins.
5. Integrates with deploy as a non-blocking, additive step.

## Decisions (locked)

| Fork | Decision |
|------|----------|
| Execution model | **Queued jobs** — one job per tenant, workers give parallelism + retry/quarantine. |
| Deploy relationship | **Async + status dashboard** — deploy ships code, fleet migration drains after; assumes additive/back-compat migrations. |
| Poison-tenant policy | **Quarantine after N attempts** (default 3, configurable); excluded from auto-runs until `--retry`. |
| Home package | `aero-platform` (owns `Tenant`, central DB, `TenantMigrate`, `TenantMigrationPaths`). |
| Run primitive | Laravel `Bus::batch()` — the batch *is* the run. |
| Reuse | `MigrateTenantJob` wraps the existing `migrateTenant()` logic verbatim; do not rewrite it. |

## Architecture

A fleet run *is* a `Bus::batch()` of one queued `MigrateTenantJob` per pending tenant. A
central-DB ledger records durable per-tenant state (resumability) and append-only per-attempt
forensics. A thin `tenant:migrate` command and a platform status page are the two faces on top
of a single `FleetMigrationService`.

### Data model (all in the CENTRAL database — must survive tenant-DB wipes)

**`tenant_migration_runs`** — one row per fleet run (the manifest):

| Column | Notes |
|--------|-------|
| `id` (uuid, pk) | |
| `batch_id` | Laravel job-batch id ↔ this run |
| `trigger` | `deploy` \| `manual` \| `scheduled` |
| `release` | git sha / version being migrated to (nullable) |
| `total`, `pending`, `migrated`, `failed`, `quarantined` | denormalized counters for cheap dashboard reads |
| `status` | `running` \| `completed` \| `completed_with_failures` \| `cancelled` |
| `started_at`, `finished_at` | |

**`tenant_migration_status`** — durable current state per tenant (the resumability source of truth):

| Column | Notes |
|--------|-------|
| `tenant_id` (pk) | |
| `status` | `up_to_date` \| `pending` \| `running` \| `migrated` \| `failed` \| `quarantined` |
| `schema_fingerprint` | hash of the sorted migration filenames last applied |
| `attempts_since_success` | drives quarantine threshold |
| `last_error` | last failure message (truncated) |
| `last_run_id` | fk → `tenant_migration_runs.id` |
| `migrated_at` | |

**`tenant_migration_attempts`** — append-only ledger (forensics):

| Column | Notes |
|--------|-------|
| `run_id`, `tenant_id` | |
| `status` | outcome of this single attempt |
| `error` | full error for this attempt (nullable) |
| `duration_ms` | |
| `attempted_at` | |

### Pending detection (cheap — no tenant boot)

Each tenant stores a `schema_fingerprint` = hash of the sorted migration filenames from
`TenantMigrationPaths->forTenant($tenant)`. `plan()` computes the code's expected fingerprint
and compares to the stored one; a mismatch means pending. The dashboard and planner therefore
never initialize N tenant connections just to learn who is behind.

### Components

1. **`FleetMigrationService`** — orchestration brain:
   - `plan(): Collection<Tenant>` — pending tenants via fingerprint (excludes quarantined).
   - `dispatchRun(trigger, release): Run` — acquires a run-lock (reject overlap), creates the
     run row, builds a `Bus::batch()` with `allowFailures()` (one poison tenant must not cancel
     the fleet), `finally()` closes the run row to `completed` / `completed_with_failures`.
   - `status(runId = active): array` — counters + quarantined list.
   - `retry(Tenant): void` — clears quarantine, resets `attempts_since_success`, re-dispatches.

2. **`MigrateTenantJob`** (queued, dedicated `migrations` queue):
   - Wraps the **existing** `migrateTenant()` logic verbatim.
   - `WithoutOverlapping($tenantId)` middleware — never two migrations on one tenant at once.
   - `$tries = 3` + exponential `backoff()` — transient failures (deadlock, connection drop).
   - On success → `status=migrated`, fingerprint updated, `attempts_since_success=0`,
     `migrated_at=now`, write success attempt row, increment run `migrated`.
   - `failed()` hook → increment `attempts_since_success`; if `>= threshold` (config, default 3)
     → `quarantined`, else `failed`; write failed attempt row; increment run `failed`/`quarantined`.

3. **`tenant:migrate`** — refactored to a thin front-end over the service:
   - `--run` — queued fleet run (resumable).
   - `{tenant?}` — sync single-tenant migrate (keeps dev ergonomics).
   - No argument and no flag → **error with guidance** (must pass `--run` or `--sync`); we never
     silently change the old no-arg all-tenant behavior into a queued run.
   - `--status[=run_id]` — CLI view of ledger/progress.
   - `--plan` / `--pretend` — dry-run: show pending count without executing.
   - `--retry {tenant}` — clear quarantine and re-dispatch.
   - `--sync` — old blocking all-tenant behavior, emergency escape hatch.
   - Existing `--fresh` / `--seed` / `--rollback` / `--step` / `--path` / `--force` retained on
     the single-tenant path.

4. **Platform status surface** (minimal v1):
   - `tenant:migrate --status` CLI view.
   - A lean HRMAC-gated platform page + JSON endpoint: active-run %, per-status counts, and the
     quarantined list with `last_error` + a Retry action. Reuses existing command-center UI patterns.

## Data flow (async deploy run)

```
deploy → code live (old schema tolerated by additive migrations)
       → tenant:migrate --run --trigger=deploy --release=<sha>   (dispatches, returns immediately)
       → workers drain the `migrations` queue in parallel
           each job: WithoutOverlapping → tenancy()->initialize
                     → curated-path migrate --force (existing logic)
                     → update ledger row + atomically increment run counters
                     → tenancy()->end
       → batch finally() → run.status = completed | completed_with_failures
       → dashboard shows 942/1000, quarantined flagged
```

## Error handling / safety

- **Concurrency:** cache run-lock rejects overlapping fleet runs; `WithoutOverlapping($tenantId)`
  prevents double-migrating a single tenant.
- **Idempotent:** fingerprint means re-running a green fleet = 0 pending, a no-op.
- **Transient failures:** job `$tries` + `backoff` retry within a single dispatch.
- **Persistent failures:** quarantine after threshold; excluded from `plan()` and future
  auto-runs; only `--retry` re-admits.
- **Partial-deploy invariant (COST OF THE ASYNC CHOICE):** migrations MUST be additive /
  backward-compatible so old code tolerates not-yet-migrated tenants during drain. This is a
  hard requirement of the async model and must be enforced in review, not just documented.

## The one infra addition

A dedicated **`migrations` queue** plus a supervisor-managed worker on prod, so migration jobs
get parallelism and never starve behind application jobs. This is what the VPS/dedicated
hosting assumption buys.

## Testing (per `docs/standards/test-standard.md`)

- **Unit:** fingerprint pending-detection (pending vs up_to_date); quarantine threshold
  transition (`attempts_since_success` → `quarantined`); run-lock overlap rejection.
- **Feature:** `Bus::fake()` / `Queue::fake()` → one job per pending tenant + correct ledger
  rows; job success/failure updates status + run counters; `--retry` clears quarantine and
  re-dispatches. Heeds the known phpunit tenant-env harness gotcha (per-tenant sqlite schema).

## Out of scope (YAGNI, v1)

- Cross-host shard routing (the future horizontal-scale escape hatch via
  `template_tenant_connection` overrides on the tenant record).
- Fleet-wide rollback orchestration (single-tenant rollback stays; fleet rollback is not v1).
- Email / Slack alerting (dashboard + log only; a hook point is left for later).
- Worker autoscaling.
