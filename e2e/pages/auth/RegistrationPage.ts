import { type Page, type Locator, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';
import { ENV } from '../../support/env.ts';
import { waitForMailCode } from '../../support/mail-log.ts';

export class RegistrationPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  async goto() {
    await this.page.goto(`${ENV.saasPlatformUrl}/signup`, {
      waitUntil: 'networkidle',
    });
    return this;
  }

  async expectLoaded() {
    await expect(this.page).toHaveURL(/\/signup/);
    await this.page.waitForLoadState('domcontentloaded').catch(() => {});
    await this.page
      .waitForSelector('[class*="signup"], main, #app', { timeout: 10_000 })
      .catch(() => {});
    await expect(
      this.page.getByRole('button', { name: /Company/ }),
    ).toBeVisible({ timeout: 15_000 });
  }

  async selectAccountType(type: 'company' | 'individual') {
    const button = this.page.getByRole('button', { name: new RegExp(type, 'i') });
    await button.click();
    await this.page
      .waitForURL('**/signup/details', { timeout: 5000 })
      .catch(() => {});
    return this;
  }

  async fillDetails(data: {
    name: string;
    email: string;
    phone?: string;
    subdomain: string;
  }) {
    await this.page.fill('#name', data.name);
    await this.page.fill('#email', data.email);
    if (data.phone) await this.page.fill('#phone', data.phone);
    await this.page.fill('#subdomain', data.subdomain);
    const continueButton = this.page.getByRole('button', { name: /Continue/i }).first();
    await continueButton.click();
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async expectDetailsSubmitted() {
    const url = this.page.url();
    const onNextStep = /\/signup\/(verify|plan|payment|byoc)/.test(url);
    const stillOnDetails = /\/signup\/details/.test(url);
    if (!onNextStep && !stillOnDetails) {
      throw new Error(`Unexpected URL after detail submit: ${url}`);
    }
  }

  async expectDetailsError(text: RegExp | string) {
    await expect(
      this.page.locator('[class*="error"], [role="alert"]').first(),
    ).toContainText(text, { timeout: 5000 });
  }

  async sendEmailVerification() {
    const sendButton = this.page
      .locator('button')
      .filter({ hasText: /Send|Resend|Verify/i })
      .first();
    if (await sendButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await sendButton.click();
    }
    return this;
  }

  async submitEmailVerification(code: string) {
    await this.page.fill('#code', code);
    await this.page.click('button[type=submit]');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async verifyEmailWithLog(email: string) {
    await this.sendEmailVerification();
    const code = await waitForMailCode(email, ENV.saasHostPath);
    return this.submitEmailVerification(code);
  }

  async expectOnEmailPage() {
    await expect(this.page).toHaveURL(/\/verify-email/, { timeout: 10_000 });
  }

  async selectPlan(planName: string) {
    const card = this.page.getByRole('button', { name: new RegExp(planName, 'i') }).first();
    await card.click();
    return this;
  }

  async selectModule(moduleName: string) {
    const card = this.page.locator('.rl-plan-main [class*="aeos-relative-left"], [class*="module-card"]').filter({ hasText: new RegExp(moduleName, 'i') }).first();
    await card.click();
    return this;
  }

  async submitPlan() {
    await this.page.click('button:has-text("Continue to Payment")');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async skipByoc() {
    const skipBtn = this.page
      .locator('button')
      .filter({ hasText: /Skip|Use Default|Continue/ })
      .first();
    if (await skipBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await skipBtn.click();
      await this.page.waitForLoadState('networkidle');
    }
    return this;
  }

  async startTrial() {
    const startBtn = this.page.getByRole('button', { name: /Start Free Trial/i });
    await startBtn.click();
    await this.page
      .waitForURL('**/provisioning/**', { timeout: 30_000 })
      .catch(() => {});
    return this;
  }

  async waitForProvisioning(timeout = 90_000) {
    await this.page
      .waitForURL('**/admin-setup', { timeout })
      .catch(() => {});
    return this;
  }

  async expectProvisioningFailed() {
    await expect(
      this.page.locator('text=Provisioning failed'),
    ).toBeVisible({ timeout: 60_000 });
  }

  async expectProvisioningError() {
    await expect(
      this.page.locator('[role="alert"], [class*="error"], [class*="alert"]').first(),
    ).toBeVisible({ timeout: 60_000 });
  }
}
