# AEOS365 · cPanel Production Deploy Runbook (aeos365.com)

**Target:** cPanel shared hosting · Cloudflare DNS · SaaS host only (standalone stays local).
**Outcome:** the same demoable state as local — public site → signup → provisioning, `admin.aeos365.com`, and the seeded **democorp** tenant at `demo.aeos365.com` (250 employees, leave, payroll, attendance through Jul 17).

The host repo ships **vendor/ and public/build committed**, so the server needs **no Composer and no Node** — deploy is: get code → env → import DBs → wire domains → cron.

---

## 0. Prepare locally (already done by prep, listed for completeness)

- [x] `packages/aero-platform/config/tenancy.php` — DB prefix env-driven (`TENANCY_DB_PREFIX`).
- [x] `.env.production` — completed (cPanel drivers, `SESSION_DOMAIN=.aeos365.com`, `TENANCY_DB_PREFIX=aeos365_t`).
- [x] Fresh `npm run build` in `aeos365`.
- [x] DB dumps in `aeos365/deploy/`: `central_aeos365.sql`, `tenant_democorp.sql`, `post_import_central.sql` (deploy/ is gitignored — upload dumps manually, never commit them).
- [ ] Commit + push the host repo (includes vendor + build):
  ```bash
  cd c:/laragon/www/aeos365
  git add -A && git commit -m "chore(deploy): production build + deploy prep"
  git push origin main
  ```
  (Also push the monorepo for source-of-truth history; the server only needs the host repo.)

## 1. Cloudflare DNS (aeos365.com)

| Record | Type | Content | Proxy |
|--------|------|---------|-------|
| `aeos365.com` | A | server IP | Proxied ☁️ |
| `*` (wildcard) | A | server IP | Proxied ☁️ |
| `mail` | A/CNAME | per cPanel mail settings | **DNS only** (grey — SMTP is not proxied) |

- SSL/TLS mode: **Full** (use a **Cloudflare Origin Certificate** installed in cPanel, or cPanel AutoSSL for the apex — Universal SSL covers `*.aeos365.com` at the edge for free).
- Cloudflare Universal SSL covers only FIRST-level wildcards — `demo.aeos365.com` ✅, `x.y.aeos365.com` ❌ (we don't use those).

## 2. cPanel setup

1. **Domains:** point `aeos365.com` docroot to `~/aeos365/public`. Add a **wildcard subdomain** `*.aeos365.com` with the **same docroot** (`~/aeos365/public`). Add `admin.aeos365.com` only if the wildcard doesn't already cover it.
2. **Code:** cPanel → *Git™ Version Control* → clone `https://github.com/emamhosen1999/aeos365.git` to `~/aeos365` (or upload a zip via File Manager). Branch: `main`.
3. **PHP:** select PHP **8.3+** (MultiPHP Manager) with extensions: `pdo_mysql, mbstring, intl, gd, zip, bcmath, redis(no), opcache`.
4. **Env file:** copy `.env.production` → `~/aeos365/.env`, then edit on the server:
   - `APP_KEY` — **generate a fresh one** (`php artisan key:generate --force` via Terminal, or paste a new `base64:` key). Do NOT keep the committed key.
   - `DB_PASSWORD` / `MAIL_PASSWORD` — set the real values; **rotate them** (they were committed to the repo at some point — treat the old ones as burned).
5. **Mode file:** create `~/aeos365/storage/app/aeos.mode` containing exactly `saas`.
6. **Permissions:** `storage/` and `bootstrap/cache/` writable (cPanel default user-ownership normally suffices).
7. **Storage link:** Terminal → `cd ~/aeos365 && php artisan storage:link`.

## 3. Databases (phpMyAdmin / cPanel MySQL)

1. Create DBs: `aeos365_platform` and `aeos365_tdemocorp`. Assign user `aeos365_emamhosen` with **ALL PRIVILEGES** to both.
2. Import `central_aeos365.sql` → **aeos365_platform**.
3. Import `tenant_democorp.sql` → **aeos365_tdemocorp**.
4. Run `post_import_central.sql` against **aeos365_platform** (SQL tab). This: keeps only democorp, sets its domain to `demo.aeos365.com`, points it at `aeos365_tdemocorp`, refreshes the trial window, clears cache/sessions.
5. **Signup-provisioning privilege check** (decides if live signup works on this host): SQL tab →
   ```sql
   CREATE DATABASE aeos365_tprivtest; DROP DATABASE aeos365_tprivtest;
   ```
   - ✅ Works → live signups will provision (`TENANCY_DB_PREFIX=aeos365_t` keeps names inside your prefix grant).
   - ❌ Denied → the DB user cannot create databases; demo live-signup on prod is off the table (demo signup locally instead) — everything else still works. (Optional fix: ask the host to grant `CREATE` on ``aeos365\_%``.*)

## 4. Finalize app (cPanel Terminal)

```bash
cd ~/aeos365
php artisan migrate --force          # no-op if dumps are current; safe
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan config:cache && php artisan route:cache
php artisan permission:cache-reset || true
```

## 5. Cron (cPanel → Cron Jobs) — every minute

```
* * * * * cd ~/aeos365 && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd ~/aeos365 && php artisan queue:work database --stop-when-empty --max-time=50 >> /dev/null 2>&1
```
The queue worker matters: **provisioning + emails are queued in production** (locally they ran sync).

## 6. Post-deploy verification (I run this once you say it's live)

1. `https://aeos365.com` — landing renders, pricing shows $29/$79/$149, 0 console errors.
2. `https://demo.aeos365.com/login` → `admin@democorp.com` / `Aeos365!Admin` → Dashboard shows **Starter · Trial · 10 GB**.
3. HRM: Employees (250), Attendance (data on weekdays through Jul 17), Leave, Payroll (3 runs · Net 36.1M KPI).
4. Signup flow: register a test tenant end-to-end (only if the §3.5 privilege check passed) — confirm password-set email arrives (SMTP), tenant provisions, HRM nav appears.
5. `https://admin.aeos365.com` — platform admin loads (landlord: `landlord@aeos365.test` / `Password123!` — change it).

## Security notes (do before the demo)

- Rotate `DB_PASSWORD` + `MAIL_PASSWORD` (committed to git history) and set a fresh `APP_KEY` (a committed APP_KEY means committed encrypted data is decryptable).
- Change the landlord admin password; `APP_DEBUG=false` (already set).
- `deploy/*.sql` dumps contain PII (250 fake employees) — upload, import, then **delete from the server**.

## Rollback

Code: cPanel Git → checkout previous commit (vendor+build are committed, so any commit is a complete runnable snapshot). DB: re-import the dumps.
