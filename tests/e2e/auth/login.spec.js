import { test, expect } from '@playwright/test';

/**
 * Auth — Login smoke tests
 *
 * Tests the critical login journeys for both SaaS (tenant subdomain)
 * and Standalone modes. Requires the dev server to be running.
 */
test.describe('Auth — Login flow', () => {
  test('login page renders with email and password fields', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('[type=submit]')).toBeVisible();
  });

  test('login fails with wrong password and shows error', async ({ page }) => {
    await page.goto('/login');

    await page.fill('#email', 'admin@testcompany.com');
    await page.fill('#password', 'wrongpassword');
    await page.click('[type=submit]');

    await expect(
      page.locator('text=/incorrect|credentials|invalid/i').first()
    ).toBeVisible({ timeout: 5000 });
  });

  test('login page populates device_id hidden field with UUID v4', async ({ page }) => {
    await page.goto('/login');

    // Wait for React useEffect to run and populate device_id
    await page.waitForTimeout(500);

    const deviceIdValue = await page.inputValue('[name=device_id]');
    expect(deviceIdValue).toMatch(
      /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
    );
  });
});
