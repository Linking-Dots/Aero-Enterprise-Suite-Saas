import { test, expect } from '@playwright/test';
import { statePath } from '../../fixtures/roles.ts';

test.describe('@standalone P0 smoke', () => {
  test.use({ storageState: statePath('superadmin', 'standalone') });

  test('authenticated dashboard loads (no redirect to /login)', async ({ page }) => {
    await page.goto('/');
    await expect(page).not.toHaveURL(/\/login(\/|$|\?)/);
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('@saas P0 smoke', () => {
  test.use({ storageState: statePath('superadmin', 'saas') });

  test('authenticated tenant dashboard loads (no redirect to /login)', async ({ page }) => {
    await page.goto('/');
    await expect(page).not.toHaveURL(/\/login(\/|$|\?)/);
    await expect(page.locator('body')).toBeVisible();
  });
});
