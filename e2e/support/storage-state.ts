import { chromium } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { ENV } from './env.ts';
import { LoginPage } from '../pages/auth/LoginPage.ts';
import { ROLE_EMAIL, statePath, type Mode, type Role } from '../fixtures/roles.ts';

/** Log in once via UI and persist the session for reuse across specs. */
export async function mintState(role: Role, mode: Mode, baseUrl: string): Promise<void> {
  mkdirSync('.auth', { recursive: true });
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await ctx.newPage();
  try {
    const login = new LoginPage(page, baseUrl);
    await login.login(ROLE_EMAIL[role], ENV.password);
    if (/\/login(\/|$|\?)/.test(page.url())) {
      const bodyText = (await page.locator('body').innerText().catch(() => '')).slice(0, 600);
      console.log(`[uat][mint ${role}/${mode}] url=${page.url()}\n--- visible text ---\n${bodyText}\n---`);
      throw new Error(
        `storageState mint failed for ${role}/${mode}: still on /login after submit. ` +
          `Check seeded credentials (${ROLE_EMAIL[role]} / ${ENV.password}) and that ${baseUrl} serves the UAT DB.`
      );
    }
    await ctx.storageState({ path: statePath(role, mode) });
  } finally {
    await browser.close();
  }
}
