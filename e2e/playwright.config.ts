import { defineConfig, devices } from '@playwright/test';
import { ENV } from './support/env.ts';

export default defineConfig({
  testDir: './specs',
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  // Retry on the live shared Laragon (Apache reloads / login latency under load).
  retries: 2,
  // One live Laragon serves both hosts; parallel full-login flows overwhelm it
  // (timeouts). Keep concurrency modest for stability.
  workers: 2,
  // Live single-server Laragon can be slow under parallel load (full login flows);
  // give each test headroom beyond Playwright's 30s default.
  timeout: 60_000,
  expect: { timeout: 10_000 },
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
