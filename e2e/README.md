# AEOS365 — UAT End-to-End Suite

Exhaustive, repeatable Playwright E2E validation of the whole system (auth, tenant
lifecycle/installation, platform admin, billing, and every HRM submodule) in **both**
deployment modes — SaaS and Standalone — against the live Laragon servers.

Spec: `docs/superpowers/specs/2026-06-01-uat-e2e-design.md`
Plan: `docs/superpowers/plans/2026-06-02-uat-e2e-suite.md`

## How it works

- One Playwright project per mode: `standalone` (`http://aeos365-standalone.test`) and
  `saas` (`http://uatco.aeos365.test`). Tags (`@standalone`, `@saas`, `@billing`,
  `@destructive`) gate mode / Stripe / order.
- `global-setup.ts` swaps each host to a dedicated **UAT** `.env` (separate UAT databases —
  it never touches dev data), runs `migrate:fresh --seed`, provisions the SaaS test tenant
  synchronously, and captures a `storageState` per role via one real UI login each.
  `global-teardown.ts` restores the original `.env` files.
- **The env-swap overwrites each host's live `.env` for the duration of the run.** Do not run
  the suite while doing active dev work on those hosts; teardown restores them, but a crash
  mid-run leaves `.env.bak.uat` next to `.env` (copy it back to recover).

## One-time prerequisites

1. **Laragon** running (Apache + MySQL 8).
2. **Hosts entries** that resolve to `127.0.0.1`: `aeos365-standalone.test`, `aeos365.test`,
   `admin.aeos365.test`, `uatco.aeos365.test`. (Windows hosts file has no real wildcard;
   the random-subdomain registration spec in P1 needs either Acrylic DNS for
   `*.aeos365.test` or a small pre-added pool — see that spec.)
3. **PHP 8.3**, **Node 18+**.
4. **UAT databases**: `CREATE DATABASE aeos365_uat; CREATE DATABASE aeos365_standalone_uat;`
5. **UAT env files** (operator-local, not committed): copy each host's `.env` to `.env.uat`
   and change `APP_ENV=uat`, point `DB_DATABASE` at the matching UAT DB, `MAIL_MAILER=log`.
   Standalone also sets `APP_URL=http://aeos365-standalone.test` and `LICENSE_BYPASS=true`.
6. **Host seeders** (operator-local, in each host `database/seeders/`): `UatSeeder.php`
   (both hosts), plus `UatPlatformSeeder.php` + `uat_provision.php` (SaaS host only).
7. `cd e2e && npm install && npx playwright install chromium`
8. `cp .env.example .env` and adjust if paths/URLs differ.

## Running

```bash
cd e2e
npm run test:standalone     # standalone project only
npm run test:saas           # saas project only (requires UAT_SKIP_SAAS=0)
npm test                    # both
npm run report              # open the HTML report
```

Env flags (in `e2e/.env`):
- `UAT_SKIP_SAAS=1` — skip SaaS bring-up in global-setup (default while the aero-platform
  fresh-install path is being repaired). Set `0` to enable SaaS.
- `RUN_DESTRUCTIVE=1` — include `@destructive` specs (suspend / GDPR-forget / re-install).
- `SKIP_GLOBAL_SETUP=1` — reuse existing UAT DB state for fast re-runs.
- `STRIPE_KEY` / `STRIPE_SECRET` — enable `@billing` specs (skipped gracefully if unset).

## Seed dataset (`UatSeeder`)

Roles (Super Administrator, Administrator, HR Manager, Employee) with module-access grants
mirroring `ProvisionTenant`; one user per role (`superadmin@uatco.test`, `hr@uatco.test`,
`employee@uatco.test`, password `Password123!`); departments + designations (verified
seeders), leave types (seeded directly against current schema), and ~10 employees.
It intentionally does **not** call `HrmDemoSeeder` (schema-drifted — see tech-debt TD-15).
