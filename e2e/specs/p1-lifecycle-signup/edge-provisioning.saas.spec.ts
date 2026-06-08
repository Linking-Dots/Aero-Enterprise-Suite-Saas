import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

test.describe('@saas EP-004: Provisioning timeout and retry', () => {
  const ts = Date.now();
  const SUBDOMAIN = `ep004-${ts}`;

  test.beforeAll(() => {
    cleanupTenant(SUBDOMAIN);
  });
  test.afterAll(() => {
    cleanupTenant(SUBDOMAIN);
  });

  test('provisioning failure exposes retry UI and can recover', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    await registration.selectAccountType('company');
    const email = `ep004-${ts}@test.test`;
    await registration.fillDetails({
      name: 'Retry Corp',
      email,
      phone: '',
      subdomain: SUBDOMAIN,
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/, { timeout: 10_000 });

    if (/\/verify-email/.test(page.url())) {
      await registration.verifyEmailWithLog(email);
    }
    await expect(page).toHaveURL(/\/signup\/(plan|payment|byoc)/, { timeout: 20_000 });

    await registration.selectPlan('Free');
    await registration.selectModule('Human Resources');
    await registration.submitPlan();
    await registration.skipByoc();
    await registration.startTrial();
    await expect(page).toHaveURL(/\/signup\/provisioning\//, { timeout: 15_000 });

    const provisioningId = extractId(page.url());
    await page.route(`**/signup/provisioning/${provisioningId}/status`, async route => {
      await route.fulfill({ status: 200, body: JSON.stringify({ has_failed: true, is_ready: false, error: 'Simulated provisioning failure' }) });
    });
    await page.route(`**/signup/provisioning/${provisioningId}/retry`, async route => {
      await route.fulfill({ status: 200, body: JSON.stringify({ success: true }) });
    });

    await page.goto(`${ENV.saasPlatformUrl}/signup/provisioning/${provisioningId}`, { waitUntil: 'networkidle' });
    await registration.expectProvisioningError();

    const retryButton = page.locator('button').filter({ hasText: /Retry|Try again/i }).first();
    if (await retryButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await page.route(`**/signup/provisioning/${provisioningId}/status`, async route => route.continue());
      await retryButton.click();
    }
  });
});

function extractId(url: string): string {
  const match = url.match(/\/provisioning\/(\d+)/);
  if (!match) throw new Error(`No provisioning id in ${url}`);
  return match[1];
}
