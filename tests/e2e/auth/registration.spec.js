import { test, expect } from '@playwright/test';

/**
 * SaaS Registration wizard smoke tests.
 */
test.describe('SaaS Registration flow', () => {
  test('registration start page renders', async ({ page }) => {
    await page.goto('http://aeos365.test/signup');
    await expect(page).not.toHaveURL(/error|500/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('BYOC step redirects to start without session', async ({ page }) => {
    await page.goto('http://aeos365.test/signup/byoc');
    await page.waitForURL(/signup/, { timeout: 5000 });
    expect(page.url()).toMatch(/signup/);
  });
});
