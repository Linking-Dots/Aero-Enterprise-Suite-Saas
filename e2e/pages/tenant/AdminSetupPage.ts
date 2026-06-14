import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class AdminSetupPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  async fillForm(data: {
    name: string;
    userName: string;
    email: string;
    password: string;
    phone?: string;
  }) {
    await this.page.fill('#name', data.name);
    await this.page.fill('#user_name', data.userName);
    await this.page.fill('#email', data.email);
    if (data.phone) await this.page.fill('#phone', data.phone);
    await this.page.fill('#password', data.password);
    await this.page.fill(
      '#password_confirmation',
      data.password,
    );
    return this;
  }

  async submit() {
    await this.page.click('button:has-text("Complete Setup")');
    await this.page.waitForLoadState('networkidle');
    return this;
  }

  async expectAdminSetupComplete() {
    await expect(this.page).not.toHaveURL(/\/admin-setup/);
  }
}
