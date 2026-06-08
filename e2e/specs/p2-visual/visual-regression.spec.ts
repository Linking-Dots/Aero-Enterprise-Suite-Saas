import { test, expect } from '@playwright/test';
import { statePath } from '../../fixtures/roles.ts';

test.describe('@standalone Visual Regression Tests', () => {
  test.use({ viewport: { width: 1920, height: 893 } });

  
  test('Login Page - Bitwise Pixel Perfect', async ({ page }) => {
    // Navigate to the login page without using saved state
    await page.goto('/login');
    // Ensure the page is fully loaded
    await page.waitForLoadState('networkidle');
    // We expect the login page to visually match 'login-page.png' exactly
    await expect(page).toHaveScreenshot('login-page.png', { maxDiffPixels: 0 });
  });

  test.describe('Authenticated Dashboard', () => {
    // Use an authenticated state to view the dashboard
    test.use({ storageState: statePath('superadmin', 'standalone') });

    test('Dashboard - Bitwise Pixel Perfect', async ({ page }) => {
      // Navigate to the dashboard
      await page.goto('/');
      await page.waitForLoadState('networkidle');
      // We expect the dashboard to visually match 'dashboard-aeos.png' exactly
      await expect(page).toHaveScreenshot('dashboard-aeos.png', { maxDiffPixels: 0 });
    });
  });

});
