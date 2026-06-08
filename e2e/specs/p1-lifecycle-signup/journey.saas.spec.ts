import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { AdminSetupPage } from '../../pages/tenant/AdminSetupPage.ts';
import { OnboardingPage } from '../../pages/tenant/OnboardingPage.ts';
import { TenantDashboardPage } from '../../pages/tenant/TenantDashboardPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

test.describe('@saas EP-001: Happy path — signup to tenant dashboard', () => {
  const ts = Date.now();
  const SUBDOMAIN = `ep001-${ts}`;

  test.beforeAll(() => {
    cleanupTenant(SUBDOMAIN);
  });
  test.afterAll(() => {
    cleanupTenant(SUBDOMAIN);
  });

  test('Landing → Signup → Wizard BYOC Trial → AdminSetup → Onboarding → Dashboard', async ({
    page,
  }) => {
    page.on('response', async response => {
      if (response.status() >= 400) {
        console.log(`[http ${response.status()}] ${response.url()}`);
      }
    });
    page.on('console', msg => {
      if (msg.type() === 'error') console.log(`[browser error] ${msg.text()}`);
    });
    page.on('pageerror', err => console.log(`[pageerror] ${err.message}`));

    const registration = new RegistrationPage(page);

    // Stage A: Landing
    await page.goto(ENV.saasPlatformUrl, { waitUntil: 'networkidle' });
    await expect(page.locator('body')).not.toContainText(
      /Server Error|Whoops|SQLSTATE/i,
    );
    await expect(page.locator('h1, [class*="hero"], main').first()).toBeVisible({
      timeout: 15_000,
    });

    // Stage B: Signup entry
    await registration.goto();
    await registration.expectLoaded();

    // Stage C1-C2: Account type + details
    await registration.selectAccountType('company');
    await registration.fillDetails({
      name: 'UAT Demo Corp',
      email: `ep001-${ts}@test.test`,
      phone: '',
      subdomain: SUBDOMAIN,
    });
    await registration.expectDetailsSubmitted();

    // Stage C3: Email verification (log mode)
    if (/\/verify-email/.test(page.url())) {
      await registration.verifyEmailWithLog(`ep001-${ts}@test.test`);
    }
    await expect(page).toHaveURL(/\/signup\/(plan|payment|byoc)/, { timeout: 20_000 });

    // Stage C5: Plan selection
    await registration.selectPlan('Free');
    await registration.selectModule('Human Resources');
    await registration.submitPlan();

    // Stage C6: BYOC
    await registration.skipByoc();

    // Stage C7: Trial activation
    await registration.startTrial();
    await expect(page).toHaveURL(/\/signup\/provisioning\//, { timeout: 15_000 });

    // Stage D: Provisioning
    await registration.waitForProvisioning(90_000);
    await expect(page).toHaveURL(new RegExp(`${SUBDOMAIN}\\.aeos365\\.test`), {
      timeout: 10_000,
    });

    // Stage E: Admin Setup
    const adminSetup = new AdminSetupPage(page);
    await adminSetup.fillForm({
      name: 'Demo Admin',
      userName: `admin-${ts}`,
      email: `admin-${ts}@test.test`,
      password: ENV.password,
    });
    await adminSetup.submit();
    await adminSetup.expectAdminSetupComplete();

    // Stage F: Onboarding
    const onboarding = new OnboardingPage(page);
    await onboarding.expectOnboardingDone();

    // Stage G: Dashboard
    await expect(page).toHaveURL(new RegExp(`${SUBDOMAIN}\\.aeos365\\.test`), {
      timeout: 10_000,
    });
    const dashboard = new TenantDashboardPage(page);
    await dashboard.expectDashboardLoaded();
    await dashboard.expectWelcomeBanner();
    await dashboard.expectQuickActions();
  });
});
