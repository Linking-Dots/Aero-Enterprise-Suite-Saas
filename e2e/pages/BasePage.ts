import { type Page, type Locator, expect } from '@playwright/test';

/** Shared navigation + assertion helpers for all page objects. */
export class BasePage {
  constructor(protected page: Page) {
    this.page.on('response', async response => {
      if (response.status() >= 400) {
        console.log(`[http ${response.status()}] ${response.url()}`);
      }
    });
  }

  async goto(path: string) {
    await this.page.goto(path, { waitUntil: 'networkidle' });
    await this.expectNoServerError();
  }

  async expectNoServerError() {
    await expect(this.page).not.toHaveURL(/\/(500|404|error)(\/|$|\?)/);
    await expect(this.page.locator('body')).not.toContainText(
      /Server Error|Whoops, something went wrong|SQLSTATE/i
    );
  }

  async expectToast(text: RegExp | string) {
    await expect(this.page.getByText(text).first()).toBeVisible({ timeout: 8000 });
  }

  row(text: string): Locator {
    return this.page.getByRole('row', { hasText: text });
  }
}
