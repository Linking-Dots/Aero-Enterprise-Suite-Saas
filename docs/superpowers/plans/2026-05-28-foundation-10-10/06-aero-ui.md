# aero-ui — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 6.5/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 6–9 engineer-days

**Goal:** Bring the frontend design system into full compliance with CLAUDE.md rules — eliminate 346 inline-style violations, add a test suite (currently zero tests on 422 JSX files), tighten HRMAC frontend enforcement, and verify HeroUI/Tailwind discipline.

**Architecture:** No structural change. The package stays as a shared React/HeroUI/Inertia v2 design system with `Pages/`, `components/`, `hooks/`, `layouts/`, `shells/`, `templates/`, `theme/`. Add `tests/` for the first time. Add a single ESLint config enforcing the style rules so regressions cannot land.

**Tech Stack:** React 18, Inertia v2, HeroUI, Tailwind CSS, Vite. Tests: Vitest + React Testing Library + Playwright (for E2E flows).

**Prerequisite:** None for design-system work. E2E tests need a running app (Phase 0 wiring should be done by then for Redis cache, etc.).

---

## Reference

- 12 PHP files (mostly ServiceProvider + a few console scripts)
- 422 JSX files
  - `Pages/Auth/` × 11
  - `Pages/Core/` × 116
  - `Pages/Platform/` × 115
  - `Pages/HRM/` × 136 (deferred — HRM has its own plan slot later)
  - `Pages/Installation/` × 11
  - `Pages/Addons/`, `Admin/`, `I18n/`, `Shared/`, `Tenant/` × ~7 total
- 219 files use `useHRMAC` (52% adoption — needs gap closure)
- 305 files use Inertia v2 `router.*` (good)
- 190 files use `useForm` (good)
- **346 inline `style={...}` uses** (CLAUDE.md violation)
- **0 tests**
- 1 file imports `@heroui` directly (the rest go through `@aero/ui` re-exports — verify)

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-ui/.eslintrc.json` (new) | Rule: forbid inline `style=` outside whitelist |
| `packages/aero-ui/vitest.config.ts` (new) | Vitest config |
| `packages/aero-ui/playwright.config.ts` (new) | Playwright E2E config |
| `packages/aero-ui/resources/js/hooks/useHRMAC.test.js` (new) | Unit tests for hook |
| `packages/aero-ui/resources/js/components/__tests__/*.test.jsx` (new) | RTL tests for primitives |
| `packages/aero-ui/resources/js/Pages/__tests__/*.test.jsx` (new — critical pages) | Inertia page smoke tests |
| `packages/aero-ui/tests/e2e/*.spec.ts` (new) | Playwright flows for login, MFA, registration, tenant signup |
| `packages/aero-ui/resources/js/Pages/**/*.jsx` (346 files) | Replace inline `style=` with Tailwind classes |
| `packages/aero-ui/resources/js/config/page-registry.js` (new) | Declarative page-to-route mapping (for HRMAC frontend audit) |
| `packages/aero-ui/resources/js/components/Hrmac/HrmacGate.jsx` (new) | Higher-order pattern to gate UI elements |
| `packages/aero-ui/package.json` | Add scripts: `test`, `test:e2e`, `lint`, `coverage` |

---

## Task 1: ESLint rule — forbid inline `style={...}` in `packages/aero-ui` outside whitelist

**Severity:** High. 346 violations exist. Without a CI guard, this number grows.

**Files:**
- Create: `packages/aero-ui/.eslintrc.json`

- [ ] **Step 1: Write ESLint config**

```json
{
  "extends": ["react-app", "react-app/jest"],
  "plugins": ["react"],
  "rules": {
    "react/forbid-component-props": [
      "error",
      {
        "forbid": [
          {
            "propName": "style",
            "allowedFor": [],
            "message": "Inline style is forbidden in aero-ui (CLAUDE.md). Use Tailwind utility classes or HeroUI theme tokens. Exceptions for dynamic values must use the `style-dynamic-allow` ESLint disable comment with justification."
          }
        ]
      }
    ],
    "react/forbid-dom-props": [
      "error",
      {
        "forbid": [
          {
            "propName": "style",
            "message": "Use Tailwind classes instead of inline style on DOM elements."
          }
        ]
      }
    ]
  }
}
```

- [ ] **Step 2: Run ESLint, capture baseline**

```bash
cd packages/aero-ui && npx eslint resources/js --ext .jsx,.js 2>&1 | tee .eslint-baseline.log
```

Expected: ~346 errors.

- [ ] **Step 3: Commit baseline + rule**

```bash
git commit -am "ci(ui): ESLint rule forbidding inline style (baseline = 346 violations)"
```

---

## Task 2: Migrate the 346 inline `style={}` uses to Tailwind

Per the audit, top offenders are barrel files in `components/` (Actions.jsx, Data.jsx, Display.jsx, etc.) plus pages.

**Strategy:** Triage in 4 waves:
1. **Wave A:** Static colors / fonts / spacing → direct Tailwind class substitution (`style={{padding: 12}}` → `className="p-3"`)
2. **Wave B:** Theme tokens → use HeroUI's theme system or Tailwind CSS variables
3. **Wave C:** Truly dynamic values (computed positions, percentages from data) → use Tailwind arbitrary value syntax `className={`w-[${pct}%]`}` or CSS variables: `style={{ '--bar-w': `${pct}%` }} className="w-[var(--bar-w)]"`
4. **Wave D:** Animations / transitions → move to Tailwind / Framer Motion

- [ ] **Step 1: Grep inventory by file**

```bash
grep -rln "style={" packages/aero-ui/resources/js --include="*.jsx" > .style-inventory.txt
```

- [ ] **Step 2: For each file in waves A→D, edit and run ESLint locally to confirm zero violations**

- [ ] **Step 3: After each batch (~50 files), commit**

```bash
git commit -m "refactor(ui): migrate inline styles to Tailwind (batch N/M)"
```

- [ ] **Step 4: After full migration, ESLint passes**

```bash
npx eslint resources/js --ext .jsx,.js
# 0 problems
```

- [ ] **Step 5: Final commit**

```bash
git commit -am "refactor(ui): zero inline styles — CLAUDE.md compliance achieved"
```

---

## Task 3: Wire ESLint into CI

**Files:**
- Modify: `.github/workflows/wiring-guards.yml` (created in Phase 0 Task 12)

- [ ] **Step 1: Add lint job**

```yaml
  ui-lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '20' }
      - run: npm ci
      - run: cd packages/aero-ui && npx eslint resources/js --ext .jsx,.js
```

- [ ] **Step 2: Commit**

```bash
git commit -am "ci(ui): block inline style regressions"
```

---

## Task 4: Vitest setup + unit tests for hooks

**Files:**
- Create: `packages/aero-ui/vitest.config.ts`
- Create: `packages/aero-ui/resources/js/hooks/useHRMAC.test.js`
- Create: `packages/aero-ui/resources/js/hooks/useSavedViews.test.js`

- [ ] **Step 1: Install Vitest + RTL**

```bash
cd packages/aero-ui && npm i -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom
```

- [ ] **Step 2: vitest.config.ts**

```ts
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./resources/js/test-setup.js'],
    coverage: { provider: 'v8', reporter: ['text', 'html'], lines: 70 },
  },
});
```

- [ ] **Step 3: useHRMAC tests**

```js
import { describe, it, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useHRMAC, useHRMACMany } from './useHRMAC';

// Mock @inertiajs/react usePage
vi.mock('@inertiajs/react', () => ({
  usePage: () => ({ props: { auth: { user: { permissions_map: { 'hrm.employees.view': true, '*': false } } } } }),
}));

describe('useHRMAC', () => {
  it('returns true for granted permission', () => {
    const { result } = renderHook(() => useHRMAC('hrm.employees.view'));
    expect(result.current).toBe(true);
  });

  it('returns false for missing permission', () => {
    const { result } = renderHook(() => useHRMAC('hrm.payroll.view'));
    expect(result.current).toBe(false);
  });

  it('super admin wildcard grants everything', () => {
    // re-mock with *
    // ... test
  });

  it('returns false when auth or user is missing', () => {
    // ... test
  });
});
```

- [ ] **Step 4: Run vitest**

```bash
npx vitest run
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git commit -am "test(ui): Vitest + useHRMAC + useSavedViews coverage"
```

---

## Task 5: Component test pattern — primitives + critical compounds

**Files:**
- Create: `packages/aero-ui/resources/js/components/__tests__/Primitives.test.jsx`
- Create: `packages/aero-ui/resources/js/components/__tests__/Forms.test.jsx`
- Create: `packages/aero-ui/resources/js/components/__tests__/Data.test.jsx`
- Create: `packages/aero-ui/resources/js/components/__tests__/Comments.test.jsx`

For each barrel module, render every exported component with sensible defaults and assert:
- Renders without throw
- Has no inline style (regression guard)
- Forwards `data-testid` if provided
- Respects `disabled` / `readOnly` / `required` props

- [ ] **Step 1: Write tests per barrel**

- [ ] **Step 2: Run + commit per file**

---

## Task 6: Page smoke tests for critical Inertia pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Auth/Login.test.jsx`
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Auth/ForgotPassword.test.jsx`
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Auth/TwoFactor.test.jsx`
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Platform/Tenants/Index.test.jsx`
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Core/Dashboard/Index.test.jsx`
- Create: `packages/aero-ui/resources/js/Pages/__tests__/Installation/Welcome.test.jsx`

Each test:
- Renders the page with mocked Inertia props
- Asserts critical elements exist (form fields, submit button, error states)
- Asserts no inline style

- [ ] **Step 1: Mock Inertia in `test-setup.js`**

```js
import '@testing-library/jest-dom';
import { vi } from 'vitest';
vi.mock('@inertiajs/react', () => ({
  usePage: () => ({ props: { auth: { user: { permissions_map: { '*': true } } } } }),
  useForm: (initial) => ({
    data: initial,
    setData: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    processing: false,
    errors: {},
    reset: vi.fn(),
  }),
  router: { visit: vi.fn(), get: vi.fn(), post: vi.fn() },
  Link: ({ children, ...p }) => <a {...p}>{children}</a>,
  Head: ({ children }) => <>{children}</>,
}));
```

- [ ] **Step 2: Write per-page smoke tests**

- [ ] **Step 3: Commit**

---

## Task 7: Playwright E2E suite

**Files:**
- Create: `packages/aero-ui/playwright.config.ts`
- Create: `packages/aero-ui/tests/e2e/login.spec.ts`
- Create: `packages/aero-ui/tests/e2e/registration.spec.ts`
- Create: `packages/aero-ui/tests/e2e/mfa-setup.spec.ts`
- Create: `packages/aero-ui/tests/e2e/tenant-signup.spec.ts`
- Create: `packages/aero-ui/tests/e2e/impersonation-banner.spec.ts`

- [ ] **Step 1: Install Playwright**

```bash
cd packages/aero-ui && npm i -D @playwright/test && npx playwright install
```

- [ ] **Step 2: Config**

```ts
import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  use: { baseURL: 'http://aeos365.test', headless: true, trace: 'on-first-retry' },
  webServer: { command: 'php artisan serve --host=aeos365.test --port=80', cwd: '../../', timeout: 60_000, reuseExistingServer: true },
});
```

- [ ] **Step 3: Per-flow spec**

```ts
import { test, expect } from '@playwright/test';

test('user can log in with valid credentials', async ({ page }) => {
  await page.goto('/login');
  await page.fill('[name=email]', 'test@example.com');
  await page.fill('[name=password]', 'secret123');
  await page.click('button[type=submit]');
  await expect(page).toHaveURL(/\/dashboard/);
});

test('rate-limited after 5 failed login attempts', async ({ page }) => {
  for (let i = 0; i < 5; i++) {
    await page.goto('/login');
    await page.fill('[name=email]', 'attacker@example.com');
    await page.fill('[name=password]', 'wrong');
    await page.click('button[type=submit]');
  }
  await page.goto('/login');
  await page.fill('[name=email]', 'attacker@example.com');
  await page.fill('[name=password]', 'wrong');
  await page.click('button[type=submit]');
  await expect(page.getByText(/too many login attempts/i)).toBeVisible();
});
```

- [ ] **Step 4: Run**

```bash
npx playwright test
```

- [ ] **Step 5: Commit**

```bash
git commit -am "test(ui): Playwright E2E flows — login, register, MFA, signup, impersonation"
```

---

## Task 8: Verify HeroUI/`@aero/ui` re-export discipline

**Files:**
- Audit: each `components/*.jsx` barrel file
- Document in `README.md` the import convention

- [ ] **Step 1: Confirm whether `@aero/ui` re-exports HeroUI**

```bash
grep -rn "export.*from '@heroui" packages/aero-ui/resources/js
```

Expected: barrel files re-export HeroUI components under `@aero/ui` namespace.

- [ ] **Step 2: If gap — add re-exports + update consumer imports**

- [ ] **Step 3: Add `eslint-plugin-import` rule forbidding direct `@heroui` import in feature packages (only `aero-ui` may import it)**

- [ ] **Step 4: Commit**

```bash
git commit -am "feat(ui): enforce @aero/ui import discipline (no direct @heroui in feature packages)"
```

---

## Task 9: Build `<HrmacGate>` HOC and page-registry

**Files:**
- Create: `packages/aero-ui/resources/js/components/Hrmac/HrmacGate.jsx`
- Create: `packages/aero-ui/resources/js/config/page-registry.js`

`<HrmacGate>` wraps any UI element to declaratively gate by permission. Reduces boilerplate `if (useHRMAC(...))` checks.

```jsx
export function HrmacGate({ permission, fallback = null, children }) {
  const allowed = useHRMAC(permission);
  return allowed ? children : fallback;
}
```

Use it everywhere a button or section is conditionally rendered by permission. Reduces inconsistency.

`page-registry.js` declares which pages exist + what permission each needs. Used for HRMAC frontend audit:

```js
export const PAGE_REGISTRY = {
  'Core/Users/Index': { permission: 'core.user_management.users.view' },
  'Core/Roles/Index': { permission: 'core.roles_permissions.roles.view' },
  // ...
};
```

- [ ] **Step 1: Write HOC + registry**

- [ ] **Step 2: Add audit test that every page in `Pages/` has an entry in `PAGE_REGISTRY`**

```js
test('every page file has a PAGE_REGISTRY entry', () => {
  const pageFiles = glob.sync('resources/js/Pages/**/*.jsx');
  for (const f of pageFiles) {
    const key = f.replace('resources/js/Pages/', '').replace('.jsx', '');
    expect(PAGE_REGISTRY).toHaveProperty(key);
  }
});
```

- [ ] **Step 3: Migrate selected pages to use `<HrmacGate>` (sample 20 pages, leave rest for follow-up)**

- [ ] **Step 4: Commit**

```bash
git commit -am "feat(ui): <HrmacGate> HOC + page registry"
```

---

## Task 10: HRMAC frontend adoption push — sensitive pages first

Per audit, only 52% of pages use `useHRMAC`. Focus on pages displaying:
- PII (user emails, phones, national IDs)
- Financial data (invoices, plans, billing)
- Configuration / settings
- User/role management
- Tenant management (platform)

**Files:**
- Modify: each sensitive page to use `useHRMAC` for action buttons (delete, edit, impersonate, etc.)

- [ ] **Step 1: Inventory pages without `useHRMAC`**

```bash
comm -23 <(find packages/aero-ui/resources/js/Pages -name "*.jsx" | sort) <(grep -rln useHRMAC packages/aero-ui/resources/js/Pages --include="*.jsx" | sort) > .pages-without-hrmac.txt
```

- [ ] **Step 2: Pick the ~50 most sensitive (Platform/Tenants, Core/Users, Core/Roles, Core/ApiKeys, etc.)**

- [ ] **Step 3: Add `useHRMAC` gating per file**

- [ ] **Step 4: Commit per module**

```bash
git commit -m "feat(ui): HRMAC gating on Platform tenant pages"
git commit -m "feat(ui): HRMAC gating on Core user/role pages"
# ...
```

---

## Task 11: Accessibility audit + fixes

**Files:**
- Modify: Pages with form inputs, modals, tables (priority on Auth pages, Tenant management, Settings)

- [ ] **Step 1: Install axe**

```bash
cd packages/aero-ui && npm i -D @axe-core/react vitest-axe
```

- [ ] **Step 2: Add axe checks to component tests**

```js
import { axe } from 'vitest-axe';

test('Login page has no a11y violations', async () => {
  const { container } = render(<Login />);
  expect(await axe(container)).toHaveNoViolations();
});
```

- [ ] **Step 3: Fix violations file by file (typical: missing labels, missing alt, low contrast)**

- [ ] **Step 4: Commit per batch**

---

## Task 12: i18n usage audit

If `aero-i18n` ships translation hooks (e.g., `useTranslation`), verify Inertia pages use them rather than hard-coded English strings.

- [ ] **Step 1: Survey hard-coded English strings in Pages/Platform and Pages/Core**

```bash
grep -rn ">[A-Z][a-z]*[a-z]<" packages/aero-ui/resources/js/Pages | wc -l
```

- [ ] **Step 2: Coordinate with aero-i18n plan (Tier 3)** to wire `t()` calls into critical user-facing strings

- [ ] **Step 3: This task likely deferred to aero-i18n plan; document the dependency here**

---

## Task 13: Storybook (optional — documentation)

**Files:**
- Add: `packages/aero-ui/.storybook/main.ts`
- Add: stories for primitives (Button, Input, Modal, Table)

Skip if time-constrained. Storybook accelerates designer/engineer alignment but isn't required for 10/10.

---

## Task 14: Final verification

- [ ] **Step 1: Run ESLint**

```bash
cd packages/aero-ui && npx eslint resources/js --ext .jsx,.js
```

Expected: 0 errors.

- [ ] **Step 2: Run unit + component tests**

```bash
npx vitest run
```

Expected: PASS, coverage ≥70%.

- [ ] **Step 3: Run E2E**

```bash
npx playwright test
```

Expected: PASS.

- [ ] **Step 4: Verify no direct `@heroui` imports outside aero-ui**

```bash
grep -rn "from '@heroui" packages/aero-* --include="*.jsx" | grep -v "packages/aero-ui"
```

Expected: empty.

- [ ] **Step 5: Score recheck**

| Dimension | Target |
|---|---|
| Inline style discipline (0 violations) | 10/10 |
| Test coverage (unit + component + E2E) | 9/10 |
| HRMAC frontend adoption on sensitive pages | 10/10 |
| Design system import discipline | 10/10 |
| Accessibility (zero axe violations on critical flows) | 9/10 |

- [ ] **Step 6: Tag**

```bash
git tag aero-ui-10-10
```

---

## Self-Review

- ✅ All 5 audit findings addressed
- ✅ ESLint guard + CI hookup prevents regression
- ✅ Test pyramid (unit → component → E2E) covers critical flows
- ✅ Cross-package coordination with aero-i18n (Task 12) and aero-auth (Task 7 E2E) called out

## Execution Handoff

**Order:**
1. Task 1 + Task 3 (ESLint rule + CI) — protect against new regressions
2. Tasks 4–7 (test infra + critical tests) — build safety net
3. Task 2 (the 346 inline style migration) — high-volume but mechanical, batched
4. Tasks 8–11 (discipline + HRMAC + a11y) — polish to 10/10
5. Task 12 (i18n) — coordinate with aero-i18n plan
6. Task 14 (verify)

Largest task is #2 (~346 file edits). Can be parallelized across multiple subagents — each handles a Pages/{module} subfolder.
