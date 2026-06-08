import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class OnboardingPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  async skip() {
    const skipBtn = this.page
      .locator('button')
      .filter({ hasText: /Skip|Complete|Finish/ })
      .first();
    if (await skipBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await skipBtn.click();
      await this.page.waitForLoadState('networkidle');
    }
    return this;
  }

  async expectOnboardingDone() {
    await expect(this.page).not.toHaveURL(/\/onboarding/);
  }
}
