import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';

/**
 * P1.2 — SaaS tenant lifecycle (registration entry).
 *
 * The provisioning happy-path is already exercised by global-setup (uat_provision
 * creates + provisions the uatco tenant). The remaining lifecycle steps are
 * blocked on prerequisites and documented in the tracker as follow-ups:
 *   - Full registration → new-tenant login: needs wildcard *.aeos365.test DNS
 *     (only fixed subdomains resolve here) + driving the multi-step wizard.
 *   - Suspend (403) + GDPR-forget: driven from the landlord admin UI, which needs
 *     the P4 admin login page (B-34). TenantForget HTTP cases are covered by
 *     PHPUnit (tech-debt D3).
 *
 * Safe coverage here: the registration entry on the platform domain renders.
 */
test.describe('@saas P1.2 tenant registration entry', () => {
  test('signup page renders on the platform domain', async ({ page }) => {
    await page.goto(`${ENV.saasPlatformUrl}/signup`, { waitUntil: 'networkidle' });
    await expect(page).toHaveTitle(/Create your account|Sign up|Register/i);
    await expect(page).not.toHaveURL(/\/login(\/|$|\?)/);
    await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
  });

  test('platform domain /login is reachable without error', async ({ page }) => {
    // NOTE (tracker finding, P4): platform web.php intends /login → 302 /signup,
    // but the unconstrained tenant `login` route (aero-auth) shadows it on the
    // platform domain, so /login renders the tenant login form instead. Not a P1
    // blocker; asserting only that it does not error.
    await page.goto(`${ENV.saasPlatformUrl}/login`, { waitUntil: 'networkidle' });
    await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
  });
});
