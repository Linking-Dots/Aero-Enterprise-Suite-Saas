import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';
import { cleanupTenant } from '../../support/cleanup.ts';

test.describe('@saas EP-002: Subdomain validation edge cases', () => {
  test.beforeEach(() => {
    cleanupTenant('valid-ep002-sub');
  });

  test.afterEach(() => {
    cleanupTenant('valid-ep002-sub');
  });

  const reserved = ['admin', 'www', 'api'];
  const tooShort = 'ab';
  const tooLong = 'a'.repeat(64);
  const taken = ENV.saasTenantSubdomain;

  for (const subdomain of [...reserved, tooShort, tooLong, taken]) {
    test(`rejects subdomain "${subdomain}"`, async ({ page }) => {
      const registration = new RegistrationPage(page);
      await registration.goto();
      await registration.expectLoaded();
      await registration.selectAccountType('company');
      await registration.fillDetails({
        name: 'Test Corp',
        email: `ep002-${Date.now()}@test.test`,
        phone: '+15551234567',
        subdomain,
      });
      await registration.expectDetailsError(/already in use|reserved|too short|too long|invalid/i);
    });
  }

  test('accepts a valid unused subdomain', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    await registration.selectAccountType('company');
    const uniqueSub = `ep002-${Date.now()}`;
    await registration.fillDetails({
      name: 'Valid Corp',
      email: `ep002-${Date.now()}@test.test`,
      phone: '+15551234567',
      subdomain: uniqueSub,
    });
    await expect(page).toHaveURL(/\/signup\/(verify|plan)/, { timeout: 10_000 });
  });
});
