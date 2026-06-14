import { type Page, expect } from '@playwright/test';
import { BasePage } from '../BasePage.ts';

export class LoginPage extends BasePage {
  constructor(page: Page, private baseUrl: string) {
    super(page);
  }

  async login(email: string, password: string) {
    await this.page.goto(`${this.baseUrl}/login`, { waitUntil: 'networkidle' });
    await this.page.fill('#email', email);
    await this.page.fill('#password', password);
    // device_id is auto-populated by a React useEffect (UUID v4 hidden field).
    await expect(this.page.locator('[name=device_id]')).not.toHaveValue('', { timeout: 5000 });
    await this.page.click('[type=submit]');
    // Inertia navigates via XHR, so networkidle can resolve before the URL
    // updates. Wait for the SPA to actually leave /login (success) — or settle
    // on /login with an error (caller asserts). Tolerate either outcome.
    await this.page
      .waitForURL((u) => !/\/login(\/|$|\?)/.test(u.toString()), { timeout: 15000 })
      .catch(() => {});
    await this.page.waitForLoadState('networkidle').catch(() => {});
  }

  async logout(baseUrl = this.baseUrl) {
    // POST /logout with Laravel's Inertia CSRF (X-XSRF-TOKEN = urldecoded
    // XSRF-TOKEN cookie). page.request shares the context's session cookie.
    const cookies = await this.page.context().cookies();
    const xsrf = cookies.find((c) => c.name === 'XSRF-TOKEN')?.value ?? '';
    await this.page.request.post(`${baseUrl}/logout`, {
      headers: {
        'X-XSRF-TOKEN': decodeURIComponent(xsrf),
        'X-Requested-With': 'XMLHttpRequest',
      },
      failOnStatusCode: false,
    });
  }

  async expectLoginError() {
    await expect(
      this.page.getByText(/incorrect|credentials|invalid|do not match/i).first()
    ).toBeVisible({ timeout: 8000 });
  }
}
