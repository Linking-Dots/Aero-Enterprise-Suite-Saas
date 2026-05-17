import { test, expect } from '@playwright/test';

/**
 * HRM Employees smoke tests.
 * Requires a running tenant at http://testco.aeos365.test (authenticated session).
 */
test.describe('HRM Employees', () => {
  test('list page loads without error', async ({ page }) => {
    await page.goto('http://testco.aeos365.test/hrm/employees');
    await expect(page).not.toHaveURL(/error|500/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('create form is accessible via /hrm/employees/create', async ({ page }) => {
    await page.goto('http://testco.aeos365.test/hrm/employees/create');
    await expect(page).not.toHaveURL(/error|500/);
    await expect(page.locator('body')).toBeVisible();
  });
});
