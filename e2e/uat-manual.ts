/**
 * Manual UAT Browser Test — Landing > Signup > Tenant Dashboard
 * 
 * Runs a headed Chromium browser through the full registration journey,
 * pausing at each step so the tester can observe and the console logs progress.
 * 
 * Usage: npx tsx uat-manual.ts
 */
import { chromium } from '@playwright/test';

const PLATFORM_URL = 'https://aeos365.test';
const ADMIN_URL = 'https://admin.aeos365.test';
const LANDLORD_EMAIL = 'landlord@aeos365.test';
const PASSWORD = 'Password123!';

async function sleep(ms: number) {
  return new Promise(r => setTimeout(r, ms));
}

async function screenshotAndLog(page: any, step: string) {
  console.log(`\n✅ STEP: ${step}`);
  console.log(`   URL: ${page.url()}`);
  console.log(`   Title: ${await page.title()}`);
  await sleep(1000); // brief pause so tester can observe
}

(async () => {
  console.log('🚀 Starting UAT Manual Browser Test');
  console.log('━'.repeat(60));

  // Clean up any existing testcorp tenant first
  console.log('\n🧹 Cleaning up existing testcorp tenant...');
  try {
    const { execFileSync } = await import('node:child_process');
    execFileSync('php', ['artisan', 'tinker', '--execute',
      "try { $t = \\Aero\\Platform\\Models\\Tenant::where('subdomain', 'testcorp')->first(); if ($t) { \\DB::statement('DROP DATABASE IF EXISTS `' . $t->database()->getName() . '`'); $t->forceDelete(); echo 'Cleaned up testcorp'; } else { echo 'No testcorp found'; } } catch(\\Throwable $e) { echo 'Cleanup: ' . $e->getMessage(); }"
    ], { cwd: 'c:/laragon/www/aeos365', encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
    console.log('   Done.');
  } catch (e) {
    console.warn('   Cleanup warning:', (e as Error).message);
  }

  const browser = await chromium.launch({
    headless: false,
    slowMo: 300, // slow down for visibility
    args: ['--start-maximized'],
  });

  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1366, height: 800 },
  });

  const page = await context.newPage();
  page.on('console', msg => {
    const text = msg.text();
    if (msg.type() === 'error') {
      console.log(`   [browser error] ${text}`);
    } else {
      console.log(`   [browser console] ${text}`);
    }
  });
  page.on('pageerror', err => console.log(`   [page error] ${err.message}`));
  page.on('response', response => {
    const status = response.status();
    if (status >= 400) {
      console.log(`   [HTTP ERROR] ${response.url()} returned status ${status}`);
    }
  });

  try {
    // ═══════════════════════════════════════════════════════════════
    // STEP 1: Landing Page
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 1: LANDING PAGE');
    console.log('═'.repeat(60));
    await page.goto(PLATFORM_URL, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.waitForLoadState('load', { timeout: 15000 }).catch(() => {});
    // Wait for React to render
    await page.waitForSelector('h1, [class*="hero"], main', { timeout: 15000 }).catch(() => {});
    await screenshotAndLog(page, 'Landing page loaded');

    // Check hero content
    const h1 = await page.locator('h1').first().textContent().catch(() => 'N/A');
    console.log(`   Hero H1: "${h1}"`);

    // Look for trial button
    const trialBtn = page.locator('text=Start free trial').first();
    const hasTrial = await trialBtn.isVisible().catch(() => false);
    console.log(`   "Start free trial" button visible: ${hasTrial}`);
    await sleep(2000);

    // ═══════════════════════════════════════════════════════════════
    // STEP 2: Navigate to Signup
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 2: NAVIGATE TO SIGNUP');
    console.log('═'.repeat(60));
    if (hasTrial) {
      await trialBtn.click();
    } else {
      await page.goto(`${PLATFORM_URL}/signup`, { waitUntil: 'domcontentloaded' });
    }
    await page.waitForURL(/\/signup/, { timeout: 10000 });
    await screenshotAndLog(page, 'Signup page loaded');
    await sleep(1000);

    // ═══════════════════════════════════════════════════════════════
    // STEP 3: Account Type Selection
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 3: ACCOUNT TYPE SELECTION');
    console.log('═'.repeat(60));
    const companyBtn = page.getByRole('button', { name: /Company/ });
    await companyBtn.waitFor({ timeout: 10000 });
    await companyBtn.click();
    await page.waitForURL(/\/signup\/details/, { timeout: 10000 });
    await screenshotAndLog(page, 'Selected Company account type');
    await sleep(1000);

    // ═══════════════════════════════════════════════════════════════
    // STEP 4: Fill Company Details
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 4: FILL COMPANY DETAILS');
    console.log('═'.repeat(60));
    await page.fill('#name', 'UAT Demo Corp');
    await page.fill('#email', 'demo@uatco.test');
    await page.fill('#phone', '+15551234567');
    await page.fill('#subdomain', 'testcorp');
    await screenshotAndLog(page, 'Company details filled');

    await page.getByRole('button', { name: /Continue/i }).click();
    
    // Wait for navigation to next step
    await page.waitForURL(/\/signup\/(verify|plan)/, { timeout: 15000 });
    await screenshotAndLog(page, 'Details submitted, moved to next step');
    await sleep(1000);

    // ═══════════════════════════════════════════════════════════════
    // STEP 5: Plan Selection (Modules & Plan page)
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 5: PLAN & MODULE SELECTION');
    console.log('═'.repeat(60));

    // If we landed on verification pages, they may auto-skip in debug mode
    // Wait until we reach the plan page
    if (!/\/signup\/plan/.test(page.url())) {
      console.log('   Waiting for plan page (verification may auto-skip)...');
      await page.waitForURL(/\/signup\/plan/, { timeout: 30000 });
    }
    await screenshotAndLog(page, 'Plan selection page loaded');

    // List available plans
    const planButtons = page.locator('[class*="plan"], button').filter({ hasText: /Free|Starter|Professional|Business|Enterprise/i });
    const planCount = await planButtons.count();
    console.log(`   Available plans: ${planCount}`);
    for (let i = 0; i < planCount; i++) {
      const text = await planButtons.nth(i).textContent();
      console.log(`     - ${text?.replace(/\s+/g, ' ').trim().substring(0, 80)}`);
    }

    // Select "Free" plan (first available)
    console.log('   Selecting "Free" plan...');
    await page.getByRole('button', { name: /Free/i }).first().click();
    await sleep(1000);

    // Select HRM module
    console.log('   Selecting Human Resources module...');
    const hrmBtn = page.getByRole('button', { name: /Select Human Resources/i });
    if (await hrmBtn.isVisible().catch(() => false)) {
      await hrmBtn.click();
      await sleep(500);
    } else {
      console.log('   ⚠️  HRM module button not found, checking alternatives...');
      const moduleButtons = page.locator('button').filter({ hasText: /Human Resources|HRM/i });
      const modCount = await moduleButtons.count();
      if (modCount > 0) {
        await moduleButtons.first().click();
      }
    }

    await screenshotAndLog(page, 'Plan and modules selected');

    // Click Continue to Payment
    const continueBtn = page.getByRole('button', { name: /Continue to Payment/i });
    await continueBtn.waitFor({ state: 'visible', timeout: 5000 });
    
    // Wait for button to be enabled
    await page.waitForFunction(() => {
      const btn = document.querySelector('button');
      return btn && !btn.disabled;
    }, { timeout: 5000 }).catch(() => {});
    
    await continueBtn.click();
    await sleep(2000);
    await screenshotAndLog(page, 'Continued to next step');

    // ═══════════════════════════════════════════════════════════════
    // STEP 6: Handle remaining steps (BYOC/Database, Review, Payment)
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 6: REMAINING SIGNUP STEPS');
    console.log('═'.repeat(60));
    
    // The wizard may have Database Setup, Review, Payment steps
    // Navigate through them
    const currentUrl = page.url();
    console.log(`   Current URL: ${currentUrl}`);

    // If on BYOC/Database setup — skip it if possible
    if (/\/signup\/byoc/.test(currentUrl)) {
      console.log('   On Database Setup page — looking for skip/continue...');
      const skipBtn = page.getByRole('button', { name: /Skip|Continue|Use Default/i }).first();
      if (await skipBtn.isVisible().catch(() => false)) {
        await skipBtn.click();
        await sleep(2000);
      }
    }

    // If on payment page — start trial
    if (/\/signup\/(payment|review)/.test(page.url())) {
      console.log('   On Payment/Review page — starting trial...');
      const startTrialBtn = page.getByRole('button', { name: /Start Free Trial|Activate|Submit/i }).first();
      if (await startTrialBtn.isVisible().catch(() => false)) {
        await startTrialBtn.click();
        console.log('   Trial activation submitted!');
      }
    }

    await screenshotAndLog(page, 'Post-payment/trial state');

    // ═══════════════════════════════════════════════════════════════
    // STEP 7: Provisioning & Redirect to Tenant
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 7: TENANT PROVISIONING');
    console.log('═'.repeat(60));

    // Wait for redirect to tenant domain (admin-setup or dashboard)
    console.log('   Waiting for provisioning (up to 60s)...');
    try {
      await page.waitForURL(/testcorp\.aeos365\.test/, { timeout: 60000 });
      await screenshotAndLog(page, 'Redirected to tenant domain!');
    } catch {
      console.log('   ⚠️  No redirect to tenant domain detected');
      console.log(`   Current URL: ${page.url()}`);
      try {
        await page.screenshot({ path: 'provisioning_failed.png' });
        console.log('   📸 Screenshot saved as provisioning_failed.png');
      } catch (err) {
        console.log('   Failed to take screenshot:', (err as Error).message);
      }
      const alerts = await page.locator('[role="alert"], [class*="alert"], [class*="error"]').allTextContents().catch(() => []);
      if (alerts.length > 0) {
        console.log('   Visible alerts/errors on page:', alerts);
      }
      const bodyText = await page.locator('body').textContent().catch(() => '');
      console.log(`   Page text (first 500 chars): ${bodyText?.substring(0, 500)}`);
    }

    // ═══════════════════════════════════════════════════════════════
    // STEP 8: Admin Setup (if redirected to admin-setup)
    // ═══════════════════════════════════════════════════════════════
    if (/admin-setup/.test(page.url())) {
      console.log('\n' + '═'.repeat(60));
      console.log('STEP 8: TENANT ADMIN SETUP');
      console.log('═'.repeat(60));

      await page.fill('#name', 'Demo Admin');
      await page.fill('#user_name', 'demo_admin');
      await page.fill('#email', 'admin@testcorp.test');
      await page.fill('#phone', '+15559876543');
      await page.fill('#password', 'Password123!');
      await page.fill('#password_confirmation', 'Password123!');

      await screenshotAndLog(page, 'Admin setup form filled');
      await page.getByRole('button', { name: /Complete Setup/i }).click();

      // Wait for dashboard redirect
      await page.waitForURL(/testcorp\.aeos365\.test\/dashboard/, { timeout: 20000 });
    }

    // ═══════════════════════════════════════════════════════════════
    // STEP 9: Tenant Dashboard Verification
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('STEP 9: TENANT DASHBOARD');
    console.log('═'.repeat(60));

    if (/dashboard/.test(page.url())) {
      await screenshotAndLog(page, 'Tenant dashboard loaded!');
      
      // Check for errors
      const bodyText = await page.locator('body').textContent().catch(() => '');
      const hasError = /Server Error|SQLSTATE/i.test(bodyText || '');
      console.log(`   Dashboard has errors: ${hasError}`);
      if (!hasError) {
        console.log('   ✅ TENANT DASHBOARD VERIFIED SUCCESSFULLY!');
      } else {
        console.log('   ❌ Dashboard has server errors');
      }
    } else {
      console.log(`   Current URL: ${page.url()}`);
      console.log('   ⚠️  Not on dashboard yet');
    }

    // ═══════════════════════════════════════════════════════════════
    // FINAL: Keep browser open for inspection
    // ═══════════════════════════════════════════════════════════════
    console.log('\n' + '═'.repeat(60));
    console.log('🎯 UAT TEST COMPLETE');
    console.log('═'.repeat(60));
    console.log('\nBrowser will stay open for 30 seconds for inspection...');
    console.log('Press Ctrl+C to close earlier.\n');
    await sleep(30000);

  } catch (error) {
    console.error('\n❌ UAT TEST FAILED:', (error as Error).message);
    console.log(`   Failed URL: ${page.url()}`);
    const bodyText = await page.locator('body').textContent().catch(() => '');
    console.log(`   Page content (first 500): ${bodyText?.substring(0, 500)}`);
    console.log('\nBrowser will stay open for 30 seconds for inspection...');
    await sleep(30000);
  } finally {
    await browser.close();
  }
})();
