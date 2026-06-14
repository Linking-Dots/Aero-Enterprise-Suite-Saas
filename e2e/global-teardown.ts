/**
 * Single-env mode: nothing to restore (no env swapping). The seeded UAT data
 * persists in each host DB for inspection and fast re-runs (SKIP_GLOBAL_SETUP=1).
 */
export default async function globalTeardown() {
  console.log('[uat] done (single-env: no .env restore needed)');
}
