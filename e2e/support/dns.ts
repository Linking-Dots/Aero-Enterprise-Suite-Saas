import { request } from '@playwright/test';

/**
 * Assert a URL resolves (DNS + server responding). Any HTTP status counts as
 * reachable — this runs BEFORE the UAT env-swap/migrate, so a 5xx from the
 * pre-setup dev state is expected and not a failure. Only a connection/DNS
 * error (no HTTP response at all) fails the preflight.
 */
export async function assertResolves(url: string): Promise<void> {
  const ctx = await request.newContext({ ignoreHTTPSErrors: true });
  let lastErr: unknown;
  try {
    // Retry: the live Laragon Apache can briefly drop connections during a reload.
    for (let attempt = 1; attempt <= 5; attempt++) {
      try {
        await ctx.get(url, { timeout: 12000, maxRedirects: 0 });
        return;
      } catch (e) {
        lastErr = e;
        await new Promise((r) => setTimeout(r, 2000));
      }
    }
    throw new Error(
      `Cannot reach ${url} after 5 attempts. Is Laragon running and the vhost/hosts entry configured?\n${lastErr}`
    );
  } finally {
    await ctx.dispose();
  }
}
