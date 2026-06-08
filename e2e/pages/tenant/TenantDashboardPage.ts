import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class TenantDashboardPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  async goto() {
    await this.page.goto('/', { waitUntil: 'networkidle' });
    return this;
  }

  async expectDashboardLoaded() {
    await expect(this.page).not.toHaveURL(/\/login\b/);
    await expect(this.page).not.toHaveURL(/\/admin-setup/);
    await expect(this.page).not.toHaveURL(/\/onboarding/);
    await expect(this.page.locator('body')).not.toContainText(
      /Server Error|SQLSTATE|Whoops/i,
    );
  }

  async expectWelcomeBanner() {
    await expect(
      this.page.locator('text=Welcome').first(),
    ).toBeVisible();
  }

  async expectQuickActions() {
    await expect(
      this.page.locator('text=Manage Users').first(),
    ).toBeVisible();
  }
}
