# AEOS365 — Monorepo Guide

**Stack:** Laravel 12 + React 18 + Inertia.js v2 | Dual-mode: SaaS + Standalone

| | Path |
|--|--|
| Monorepo | `c:\laragon\www\Aero-Enterprise-Suite-Saas` |
| SaaS host | `c:\laragon\www\aeos365` (`http://{tenant}.aeos365.test`) |
| Standalone | `c:\laragon\www\aeos365-standalone` (`http://aeos365-standalone.test`) |

## Status
Phase 0 ✅ Done (Auth, Install, Tenant Shell) · Phase 1 ⬜ Starting (HRM — H-1 Employees next) · Full roadmap: `docs/master-plan.md`

## Rules

| Rule | Detail |
|------|--------|
| Package-first | ALL code in `packages/aero-*`. Host apps are dumb wrappers — zero business logic. |
| HRMAC | Route: `hrmac:module.sub.component.action` · React: `useHRMAC()` · Hierarchy: `config/module.php` |
| Audit | `AuditService::log()` on every business action · `AuditService::logAccess()` on PII exposure |
| Encryption | `EncryptedField` on `account_number`, `routing_number`, `tax_id`, `national_id`, `medical_notes`, `byoc_db_*` |
| Models | Extend `TenantModel` or `CentralModel` — never bare `Model` |
| Immutability | Payslip/JournalEntry/Invoice/PerformanceReview locked after finalization via `ImmutableRecordObserver` |
| Transactions | All writes in `DB::transaction()` |
| Frontend | All components from `@aero/ui` · No inline `style={}` · `useForm()` + `router.*` from Inertia v2 |
| Dual-mode | Every feature works in SaaS + Standalone — no hardcoded central DB config in feature packages |

## Standards
`docs/standards/`: `inertia-standard.md` · `hrmac-convention.md` · `done-definition.md` · `test-standard.md` · `security-architecture.md` · `audit-standard.md`

## Agents
| Task | Agent |
|------|-------|
| Architecture, migrations, `config/module.php`, orchestration | Lead Architect |
| Controllers, services, models, routes, Form Requests | Backend Engineer |
| React pages, components, forms, tables, hooks | Frontend Engineer |
| PHPUnit tests, code review, UAT | Quality Control |
| Module gap analysis | Audit Prompt Generator |
| Public landing pages, marketing copy | Marketing Content Creator |
