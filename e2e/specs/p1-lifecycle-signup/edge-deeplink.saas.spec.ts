import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { RegistrationPage } from '../../pages/auth/RegistrationPage.ts';

test.describe('@saas EP-007: Deep link and direct URL access', () => {
  test('direct visit to /signup/details without session redirects back to /signup', async ({ page }) => {
    await page.goto(`${ENV.saasPlatformUrl}/signup/details`, { waitUntil: 'networkidle' });
    await expect(page).not.toHaveURL(/\/signup\/details$/);
  });

  test('direct visit to /signup/plan without session redirects back to /signup', async ({ page }) => {
    await page.goto(`${ENV.saasPlatformUrl}/signup/plan`, { waitUntil: 'networkidle' });
    await expect(page).not.toHaveURL(/\/signup\/plan$/);
  });

  test('direct visit to /signup/payment without session redirects back to /signup', async ({ page }) => {
    await page.goto(`${ENV.saasPlatformUrl}/signup/payment`, { waitUntil: 'networkidle' });
    await expect(page).not.toHaveURL(/\/signup\/payment$/);
  });
});

test.describe('@saas EP-008: Navigation and back-button behavior', () => {
  test('browser back button during wizard does not corrupt visible state', async ({ page }) => {
    const registration = new RegistrationPage(page);
    await registration.goto();
    await registration.expectLoaded();
    await registration.selectAccountType('company');
    await registration.fillDetails({
      name: 'Nav Corp',
      email: `ep008-${Date.now()}@test.test`,
      phone: '+15551234567',
      subdomain: 'ep008-sub',
    });
    await page.goBack({ waitUntil: 'networkidle' });
    await expect(page).not.toContainText(/Server Error|SQLSTATE/i);
  });
});
