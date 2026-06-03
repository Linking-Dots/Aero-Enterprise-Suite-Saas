import { defineConfig, devices } from '@playwright/test';
import { ENV } from './support/env.ts';

export default defineConfig({
  testDir: './specs',
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  // 1 retry even locally: tests drive a live shared Laragon, which can briefly
  // hiccup (Apache reload) during the long two-mode run.
  retries: process.env.CI ? 2 : 1,
  // Modes use separate DBs; workers parallelize within a mode-project.
  workers: process.env.CI ? 2 : 4,
  reporter: [['html', { open: 'never' }], ['list']],
  grepInvert: ENV.runDestructive ? undefined : /@destructive/,
  use: {
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    viewport: { width: 1366, height: 800 },
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'standalone',
      grep: /@standalone/,
      use: { ...devices['Desktop Chrome'], baseURL: ENV.standaloneUrl },
    },
    {
      name: 'saas',
      grep: /@saas/,
      use: { ...devices['Desktop Chrome'], baseURL: ENV.saasTenantUrl },
    },
  ],
});
