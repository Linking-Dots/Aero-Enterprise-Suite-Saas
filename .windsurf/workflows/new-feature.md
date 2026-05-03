---
description: "Build a new feature end-to-end in an AEOS365 package."
---

# /new-feature Workflow

1. Identify target `aero-{module}` package. Verify `config/module.php` has the hierarchy.
2. Backend: migration → model → Form Request → service → thin controller → routes with `hrmac:*`.
3. Frontend: Inertia page in `packages/aero-ui/resources/js/Pages/{Module}/` using `@aero/ui`, `.layout = page => <App title="...">{page}</App>`, page templates.
4. Add navigation item to `config/module.php` if needed.
5. Audit: check inline validation, `env()`, vanilla HTML, missing HRMAC.
6. Test: PHPUnit feature test + Playwright E2E for critical path.

Backend rules: Form Request not inline validate. Service class ≤5 public methods. Controller ≤30 lines/action. Route middleware: `['web','auth','verified','hrmac:...']`.
Frontend rules: Import from `@aero/ui`. Use `useToast` not `showToast`. Use `useBreakpoint` not `window.innerWidth`. Export `.layout` to `App`.
