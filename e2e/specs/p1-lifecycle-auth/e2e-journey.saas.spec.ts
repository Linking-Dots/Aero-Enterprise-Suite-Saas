import { test, expect } from '@playwright/test';
import { ENV } from '../../support/env.ts';
import { artisan } from '../../support/artisan.ts';

test.describe('@saas E2E Platform & Tenant Journey', () => {
  test.beforeAll(() => {
    // Clean up any existing tenant with subdomain 'testcorp' to ensure a fresh registration path
    try {
      artisan(ENV.saasHostPath, [
        'tinker',
        '--execute',
        "try { $t = \\Aero\\Platform\\Models\\Tenant::where('subdomain', 'testcorp')->first(); if ($t) { \\DB::statement('DROP DATABASE IF EXISTS `' . $t->database()->getName() . '`'); $t->forceDelete(); } } catch(\\Throwable $e) {}"
      ]);
    } catch (e) {
      console.warn('Failed to clean up testcorp tenant:', (e as Error).message);
    }
  });

  test('full registration and login flow: home > signup > tenant dashboard > platform dashboard', async ({ page, context }) => {
    // Add console and pageerror listeners for debugging
    page.on('console', msg => console.log(`[browser console] ${msg.type()}: ${msg.text()}`));
    page.on('pageerror', err => console.log(`[browser pageerror] ${err.message}`));

    // 1. Visit the home page
    console.log('[journey] visiting landing page');
    await page.goto(ENV.saasPlatformUrl, { waitUntil: 'networkidle' });
    await expect(page).toHaveTitle(/Enterprise ERP Platform|aeos365/i);
    await expect(page.locator('h1.aeos-pub-h1')).toContainText('One platform.');

    // 2. Go to the signup page
    console.log('[journey] clicking start free trial');
    // Click the trial button in the hero
    await page.click('text=Start free trial');
    await page.waitForURL(/\/signup$/, { timeout: 10000 });

    // 3. Step 1: Account Type selection
    console.log('[journey] selecting Company account type');
    await page.getByRole('button', { name: /Company/ }).click();
    await page.waitForURL(/\/signup\/details$/, { timeout: 10000 });

    // 4. Step 2: Fill Details (verification is bypassed due to APP_DEBUG=true)
    console.log('[journey] filling company details');
    await page.fill('#name', 'UAT Journey Corp');
    await page.fill('#email', 'journey@uatco.test');
    await page.fill('#phone', '+15551234567');
    await page.fill('#subdomain', 'testcorp');
    await page.getByRole('button', { name: /Continue/i }).click();

    // 5. Step 3: Plan selection (since email verification is skipped, we land directly on plan)
    console.log('[journey] selecting plan and modules');
    await page.waitForURL(/\/signup\/plan$/, { timeout: 15000 });
    await page.getByRole('button', { name: /UAT HRM Plan/i }).click();
    
    // Check the hrm module check button
    console.log('[journey] selecting hrm module');
    await page.getByRole('button', { name: /Select Human Resources/i }).click();
    await page.getByRole('button', { name: /Continue to Payment/i }).click();

    // 6. Step 4: Start Free Trial (Review & Payment page)
    console.log('[journey] submitting trial activation');
    await page.waitForURL(/\/signup\/payment$/, { timeout: 10000 });
    await page.getByRole('button', { name: /Start Free Trial/i }).click();

    // 7. Step 5: Provisioning and redirect to Admin Setup on tenant domain
    console.log('[journey] waiting for provisioning and redirect to tenant admin-setup');
    // Wait for URL matching tenant subdomain admin-setup page
    await page.waitForURL(/https:\/\/testcorp\.aeos365\.test\/admin-setup/, { timeout: 45000 });
    await expect(page.locator('body')).toContainText('Complete your account setup');

    // 8. Step 6: Create Tenant Admin Account
    console.log('[journey] filling tenant admin account setup');
    await page.fill('#name', 'Journey Admin');
    await page.fill('#user_name', 'journey_admin');
    await page.fill('#email', 'admin@testcorp.test');
    await page.fill('#phone', '+15559876543');
    await page.fill('#password', 'Password123!');
    await page.fill('#password_confirmation', 'Password123!');
    await page.getByRole('button', { name: /Complete Setup/i }).click();

    // 9. Redirect to Tenant Dashboard
    console.log('[journey] verifying tenant dashboard loads');
    await page.waitForURL(/https:\/\/testcorp\.aeos365\.test\/dashboard/, { timeout: 20000 });
    await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
    await expect(page.locator('body')).toContainText(/Dashboard/i);

    // 10. Platform Landlord login & dashboard verification
    console.log('[journey] logging in to platform admin dashboard');
    const platformPage = await context.newPage();
    await platformPage.goto(ENV.saasAdminUrl + '/login', { waitUntil: 'networkidle' });
    await platformPage.fill('#email', ENV.landlordEmail);
    await platformPage.fill('#password', ENV.password);
    
    // Check device_id hidden field is populated
    await expect(platformPage.locator('[name=device_id]')).not.toHaveValue('', { timeout: 5000 });
    await platformPage.getByRole('button', { name: /Sign in|Log in/i }).first().click();
    
    await platformPage.waitForURL(new RegExp(ENV.saasAdminUrl + '/dashboard'), { timeout: 20000 });
    await expect(platformPage.locator('body')).not.toContainText(/Server Error|SQLSTATE/i);
    await expect(platformPage.locator('body')).toContainText(/Dashboard/i);
    console.log('[journey] platform and tenant E2E UAT journey completed successfully!');
  });
});
