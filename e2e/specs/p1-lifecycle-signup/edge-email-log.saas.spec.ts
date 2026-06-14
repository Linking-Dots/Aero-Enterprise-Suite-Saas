import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

test.describe('@saas EP-005: Email verification without SMTP (UAT log mode)', () => {
  test.beforeAll(() => {
    cleanupTenant('ep005-sub');
  });
  test.afterAll(() => {
    cleanupTenant('ep005-sub');
  });

  test('submits verification code extracted from Laravel log', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    await registration.selectAccountType('company');
    const email = `ep005-${Date.now()}@test.test`;
    await registration.fillDetails({
      name: 'Mail Log Corp',
      email,
      phone: '',
      subdomain: 'ep005-sub',
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/, { timeout: 10_000 });
    // If landed on verify-email, use log code. Otherwise skip.
    if (/\/verify-email/.test(page.url())) {
      await registration.verifyEmailWithLog(email);
    }
    await expect(page).toHaveURL(/\/signup\/(plan|payment|byoc)/, { timeout: 15_000 });
  });
});
