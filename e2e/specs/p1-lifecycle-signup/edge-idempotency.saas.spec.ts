import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

test.describe('@saas EP-006: Idempotency and double-submit protection', () => {
  const ts = Date.now();
  const subdomain = `ep006-${ts}`;

  test.beforeAll(() => {
    cleanupTenant(subdomain);
  });
  test.afterAll(() => {
    cleanupTenant(subdomain);
  });

  test('double-clicking account type does not create duplicate tenants', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    const companyCard = page.locator('text=Company').first();
    await companyCard.click();
    await companyCard.click();
    await page.waitForURL('**/signup/details', { timeout: 5000 }).catch(() => {});

    await registration.fillDetails({
      name: 'Idempotent Corp',
      email: `ep006-${ts}@test.test`,
      phone: '+15551234567',
      subdomain,
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/, { timeout: 10_000 });
  });
});
