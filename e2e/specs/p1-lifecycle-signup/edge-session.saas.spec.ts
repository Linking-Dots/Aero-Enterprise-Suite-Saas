import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

const ts = Date.now();
test.describe('@saas EP-003: Cross-domain session persistence', () => {
  test.beforeAll(() => {
    cleanupTenant('ep003-sub');
  });
  test.afterAll(() => {
    cleanupTenant('ep003-sub');
  });

  test('session cookie is present with .aeos365.test domain after provisioning redirects to tenant domain', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    await registration.selectAccountType('company');
    await registration.fillDetails({
      name: 'Session Corp',
      email: `ep003-${ts}@test.test`,
      phone: '+15551234567',
      subdomain: 'ep003-sub',
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/, { timeout: 10_000 });
    await registration.selectPlan('Free');
    await registration.selectModule('Human Resources');
    await registration.submitPlan();
    await registration.skipByoc();
    await registration.startTrial();
    await registration.waitForProvisioning(90_000);

    await expect(page).toHaveURL(/ep003-sub\.aeos365\.test/, { timeout: 10_000 });

    const cookies = await page.context().cookies();
    const sessionCookie = cookies.find(c => c.name === 'laravel_session');
    expect(sessionCookie).toBeDefined();
    expect(sessionCookie?.domain).toBe('.aeos365.test');
  });
});
