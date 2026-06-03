import { test, expect } from '@playwright/test';

/**
 * P1.1 — Standalone installer (post-install guard).
 *
 * The full fresh wizard (DB step → admin → license → finalize) is @destructive:
 * it needs a scratch, non-installed database and removal of the aeos.installed
 * marker, which would disturb the working UAT install. That run is documented in
 * the tracker as needing a dedicated scratch-DB harness and is NOT executed here.
 *
 * What we assert safely on the installed host: the installer is guarded after
 * completion — /install renders the "Already Installed" notice, not the wizard.
 */
test.describe('@standalone P1.1 installer post-install guard', () => {
  test('/install shows "Already Installed" once the app is installed', async ({ page }) => {
    await page.goto('/install', { waitUntil: 'networkidle' });
    await expect(page).toHaveTitle(/Already Installed/i);
    await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
    // The DB-connection wizard step must NOT be presented post-install.
    await expect(page.locator('body')).not.toContainText(/Database Connection|Create Admin Account/i);
  });

  test('a deep installer step also refuses post-install', async ({ page }) => {
    await page.goto('/install/database', { waitUntil: 'networkidle' });
    // Either the already-installed notice, or redirected away from the wizard step.
    const onWizard = await page.locator('input[name="db_database"], #db_database').count();
    expect(onWizard, 'DB-step form must not be reachable post-install').toBe(0);
  });
});
