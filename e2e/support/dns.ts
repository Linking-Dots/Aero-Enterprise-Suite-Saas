import { request } from '@playwright/test';

/**
 * Assert a URL resolves (DNS + server responding). Any HTTP status counts as
 * reachable — this runs BEFORE the UAT env-swap/migrate, so a 5xx from the
 * pre-setup dev state is expected and not a failure. Only a connection/DNS
 * error (no HTTP response at all) fails the preflight.
 */
export async function assertResolves(url: string): Promise<void> {
  const ctx = await request.newContext({ ignoreHTTPSErrors: true });
  try {
    await ctx.get(url, { timeout: 10000, maxRedirects: 0 }).catch((e) => {
      throw new Error(
        `Cannot reach ${url}. Is Laragon running and the vhost/hosts entry configured?\n${e}`
      );
    });
  } finally {
    await ctx.dispose();
  }
}
