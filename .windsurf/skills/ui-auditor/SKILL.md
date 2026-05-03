---
name: ui-auditor
description: "Audit AEOS365 JSX and PHP files for pattern violations. Detects vanilla HTML leakage, legacy imports (HeroUI, framer-motion, heroicons), inline validation, env() misuse, missing HRMAC middleware, and design system inconsistencies."
---

# UI Auditor Skill

## Purpose

Scan changed or new files for violations against the AEOS365 `@aero/ui` design system and Laravel backend patterns. Report drift with severity and actionable fix suggestions.

## Audit Matrix

### Frontend Violations

| ID | Violation | Pattern | Severity | Fix |
|----|-----------|---------|----------|-----|
| FE-01 | HeroUI import | `import ... from '@heroui/react'` | **MEDIUM** | Replace with `@aero/ui` equivalent |
| FE-02 | Heroicons import | `import ... from '@heroicons/react'` | **MEDIUM** | Replace with `Icon` component |
| FE-03 | Framer Motion import | `import { motion } from 'framer-motion'` | **LOW** | Replace with CSS transitions |
| FE-04 | Legacy toast | `showToast.promise(` | **MEDIUM** | Replace with `useToast()` hook |
| FE-05 | Vanilla button | `<button` not from `@aero/ui` | **HIGH** | Use `Button` or `IconButton` |
| FE-06 | Vanilla link | `<a href=` (internal) | **HIGH** | Use `Link` or `as={Link}` |
| FE-07 | Vanilla table | `<table>`, `<tr>`, `<td>` | **HIGH** | Use `DataTable` |
| FE-08 | Vanilla input | `<input`, `<select`, `<textarea` | **HIGH** | Use `Input`, `Select`, `Textarea` |
| FE-09 | Manual flex div | `className="flex"` on `<div>` | **MEDIUM** | Use `HStack`, `VStack`, `Box` |
| FE-10 | Manual responsive | `window.innerWidth` checks | **MEDIUM** | Use `useBreakpoint()` |
| FE-11 | Direct AppShell | `<AppShell` in page file | **MEDIUM** | Use `App.jsx` via `.layout` |
| FE-12 | Missing layout | No `Page.layout = ` export | **HIGH** | Add `.layout` property |
| FE-13 | Hardcoded color | `#00E5FF`, `rgb(...)`, `#333` | **MEDIUM** | Use `var(--aeos-primary)` |
| FE-14 | Manual body classes | `document.body.classList.add` | **MEDIUM** | Use `ThemeProvider` |

### Backend Violations

| ID | Violation | Pattern | Severity | Fix |
|----|-----------|---------|----------|-----|
| BE-01 | Inline validation | `$request->validate([` | **CRITICAL** | Extract to Form Request |
| BE-02 | Static validation | `Validator::make(` | **CRITICAL** | Extract to Form Request |
| BE-03 | env() misuse | `env(` in Controller/Provider | **CRITICAL** | Replace with `config()` |
| BE-04 | Missing HRMAC | Route without `hrmac:` | **HIGH** | Add `hrmac:{path}` |
| BE-05 | Wrong middleware order | `hrmac:` before `auth` | **MEDIUM** | Order: web→auth→verified→hrmac |
| BE-06 | Missing auth | Tenant route w/o `auth` | **HIGH** | Add `auth` middleware |
| BE-07 | Fat controller | Action >30 lines | **MEDIUM** | Extract to Service class |
| BE-08 | Missing eager loading | `Model::get()` w/o `with()` | **MEDIUM** | Add `->with(['rel'])` |
| BE-09 | DB facade over Eloquent | `DB::table('...')` | **LOW** | Use `Model::query()` |
| BE-10 | Missing return type | `public function index()` | **LOW** | Add `: Response` / `: JsonResponse` |

### Monorepo Violations

| ID | Violation | Pattern | Severity | Fix |
|----|-----------|---------|----------|-----|
| MO-01 | Host app code | File in `aeos365/app/` | **CRITICAL** | Move to `packages/aero-*/` |
| MO-02 | Host resources | File in `aeos365/resources/` | **CRITICAL** | Move to `packages/aero-*/` |
| MO-03 | Wrong provider name | Provider not `Aero*ServiceProvider` | **MEDIUM** | Rename to standard |
| MO-04 | Missing extra.aero | No `extra.aero` in composer.json | **MEDIUM** | Add metadata block |
| MO-05 | Core cross-import | Core imports HRM/Finance class | **HIGH** | Use contract/interface |
| MO-06 | Missing module config | No `config/module.php` | **HIGH** | Create HRMAC definition |

## Audit Execution

When reviewing a pull request or new file, check in this order:

1. **Monorepo boundaries** — Is the file in the correct package? (MO-01, MO-02)
2. **Backend patterns** — Form Request? No env()? HRMAC middleware? (BE-01..BE-06)
3. **Frontend patterns** — @aero/ui imports? Layout export? No vanilla HTML? (FE-01..FE-14)
4. **Architecture** — Thin controllers? Service classes? Contracts? (BE-07, MO-05)

Report format:
```
[SEVERITY] ID: Description in file.ext:line
  Fix: Action to take
```

Example:
```
[CRITICAL] BE-01: Inline validation found in EmployeeController.php:47
  Fix: Create StoreEmployeeRequest in packages/aero-hrm/src/Http/Requests/

[HIGH] FE-05: Vanilla <button> in Dashboard.jsx:89
  Fix: Replace with <Button intent="primary"> from @aero/ui
```

## Reference Files

- Frontend gold standard: `packages/aero-ui/resources/js/Pages/Tenant/Dashboard.jsx`
- App shell: `packages/aero-ui/resources/js/Pages/App.jsx`
- Backend gold standard: `packages/aero-hrm/src/Http/Controllers/Controller.php`
- Service provider: `packages/aero-core/src/Providers/AbstractModuleProvider.php`
