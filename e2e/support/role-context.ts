import { type Browser, type BrowserContext, type TestInfo } from '@playwright/test';
import { statePath, type Mode, type Role } from '../fixtures/roles.ts';

/**
 * Build a browser context authenticated as `role` for the CURRENT mode-project,
 * with that project's baseURL so relative page.goto() works. Lets one shared
 * spec run in both the `saas` and `standalone` projects with the right session.
 */
export async function roleContext(
  browser: Browser,
  role: Role,
  testInfo: TestInfo
): Promise<BrowserContext> {
  const mode = testInfo.project.name as Mode;
  return browser.newContext({
    storageState: statePath(role, mode),
    baseURL: (testInfo.project.use as { baseURL?: string }).baseURL,
    ignoreHTTPSErrors: true,
  });
}
