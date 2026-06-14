import { test, expect, type Page } from '@playwright/test';
import { roleContext } from '../../support/role-context.ts';

/**
 * P1.4 — HRMAC allow/deny (runs in both saas + standalone projects).
 *
 * HR Manager has full sub-module grants; Employee has self-service + dashboard
 * only. Web denials REDIRECT away from the target (CheckRoleModuleAccess) with a
 * flash warning; API denials return 403 JSON. We assert allow = lands on the
 * target page; deny = redirected off it (and never the raw page).
 */

// Pages an HR Manager can administer.
const HR_PAGES = ['/hrm/employees', '/hrm/leave', '/hrm/payroll'];
// Admin-only pages a self-service Employee must NOT reach. /hrm/leave is omitted
// on purpose: employees legitimately reach leave to apply for their own time off.
const ADMIN_ONLY_PAGES = ['/hrm/employees', '/hrm/payroll'];

async function landedOn(page: Page, path: string): Promise<boolean> {
  const url = new URL(page.url());
  return url.pathname === path || url.pathname.startsWith(path + '/');
}

test.describe('@saas @standalone P1.4 HRMAC allow/deny', () => {
  test('HR Manager CAN reach employee mgmt, leave, payroll', async ({ browser }, testInfo) => {
    const ctx = await roleContext(browser, 'hr', testInfo);
    const page = await ctx.newPage();
    try {
      for (const path of HR_PAGES) {
        await page.goto(path, { waitUntil: 'networkidle' });
        // Allow: stays on the target (poll to absorb client-side settle).
        await expect.poll(() => landedOn(page, path), {
          message: `HR should stay on ${path}, got ${page.url()}`,
          timeout: 8000,
        }).toBeTruthy();
        await expect(page).not.toHaveURL(/\/login(\/|$|\?)/);
        await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
      }
    } finally {
      await ctx.close();
    }
  });

  test('Employee CANNOT reach admin HRM pages (redirected away)', async ({ browser }, testInfo) => {
    const ctx = await roleContext(browser, 'employee', testInfo);
    const page = await ctx.newPage();
    try {
      for (const path of ADMIN_ONLY_PAGES) {
        await page.goto(path, { waitUntil: 'networkidle' });
        // Deny: HRMAC redirects off the target (server 302 → fallback dashboard).
        // Poll to absorb the redirect settle.
        await expect.poll(() => landedOn(page, path), {
          message: `Employee should be denied ${path} (redirected away), landed: ${page.url()}`,
          timeout: 8000,
        }).toBeFalsy();
      }
    } finally {
      await ctx.close();
    }
  });

  test('Employee CAN reach self-service', async ({ browser }, testInfo) => {
    const ctx = await roleContext(browser, 'employee', testInfo);
    const page = await ctx.newPage();
    try {
      await page.goto('/hrm/self-service', { waitUntil: 'networkidle' });
      await expect(page).not.toHaveURL(/\/login(\/|$|\?)/);
      await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
    } finally {
      await ctx.close();
    }
  });
});
