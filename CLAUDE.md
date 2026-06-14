# AEOS365 — Monorepo Guide

**Stack:** Laravel 12 + React 18 + Inertia.js v2 | Dual-mode: SaaS + Standalone

| | Path |
|--|--|
| Monorepo | `c:\laragon\www\Aero-Enterprise-Suite-Saas` |
| SaaS host | `c:\laragon\www\aeos365` (`http://{tenant}.aeos365.test`) |
| Standalone | `c:\laragon\www\aeos365-standalone` (`http://aeos365-standalone.test`) |

## Status
Phase 0 ✅ Done · Phase 1 ✅ Done (HRM H-1–H-18) · Phase 2 ✅ Done (P-1–P-11) · Phase 3 ⬜ Next (Finance) · Full roadmap: `docs/master-plan.md`

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
| Doc currency | NEVER write version-sensitive framework code from memory. Resolve current API via **Context7 MCP** (`.mcp.json`), else `WebFetch` the canonical URL in `docs/standards/tech-versions.md`, else state the uncertainty. Pinned: Laravel **12.x** (NOT 13), React **18** (NOT 19), Inertia **v2** (`router.*`, not v1 `Inertia.*`), HeroUI (NOT NextUI), Tailwind **v4**, Zod **4**. |

## Standards
`docs/standards/`: `tech-versions.md` · `inertia-standard.md` · `hrmac-convention.md` · `done-definition.md` · `test-standard.md` · `security-architecture.md` · `audit-standard.md`

## Agents
| Task | Agent |
|------|-------|
| Architecture, migrations, `config/module.php`, orchestration | Lead Architect |
| Controllers, services, models, routes, Form Requests | Backend Engineer |
| React pages, components, forms, tables, hooks | Frontend Engineer |
| PHPUnit tests, code review, UAT | Quality Control |
| Module gap analysis | Audit Prompt Generator |
| Public landing pages, marketing copy | Marketing Content Creator |
