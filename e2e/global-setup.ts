import { writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { ENV } from './support/env.ts';
import { artisan } from './support/artisan.ts';
import { provisionUatTenant } from './support/provision-tenant.ts';
import { mintState } from './support/storage-state.ts';
import { assertResolves } from './support/dns.ts';

/**
 * Single-env setup: each host runs against its own real `.env` (production-
 * style). We migrate:fresh --seed the live DB, mark the install, and capture a
 * storageState per role via one real UI login. No env swapping.
 */
export default async function globalSetup() {
  if (ENV.skipGlobalSetup) {
    console.log('[uat] SKIP_GLOBAL_SETUP=1 — reusing existing DB state + storageState');
    return;
  }

  // ============ STANDALONE ============
  await assertResolves(ENV.standaloneUrl);
  console.log('[uat] standalone: migrate:fresh --seed');
  artisan(ENV.standaloneHostPath, [
    'migrate:fresh', '--seed', '--seeder=Database\\Seeders\\UatSeeder', '--force',
  ]);
  // Mark installed + standalone mode so BootstrapGuard lets requests through.
  const saStorage = join(ENV.standaloneHostPath, 'storage', 'app');
  writeFileSync(join(saStorage, 'aeos.mode'), 'standalone');
  writeFileSync(join(saStorage, 'aeos.installed'), new Date().toISOString());
  await mintState('superadmin', 'standalone', ENV.standaloneUrl);
  await mintState('hr', 'standalone', ENV.standaloneUrl);
  await mintState('employee', 'standalone', ENV.standaloneUrl);
  console.log('[uat] standalone ready');

  // ============ SAAS (gated until aero-platform fresh-install path is repaired) ============
  if (ENV.skipSaas) {
    console.log('[uat] SaaS setup skipped (UAT_SKIP_SAAS). Standalone-only run.');
    return;
  }

  await assertResolves(ENV.saasPlatformUrl);
  await assertResolves(ENV.saasTenantUrl);
  console.log('[uat] saas: central migrate:fresh + platform seed');
  artisan(ENV.saasHostPath, ['migrate:fresh', '--force']);
  artisan(ENV.saasHostPath, ['db:seed', '--class=Database\\Seeders\\UatPlatformSeeder', '--force']);
  writeFileSync(join(ENV.saasHostPath, 'storage', 'app', 'aeos.mode'), 'saas');

  console.log('[uat] saas: provision test tenant');
  provisionUatTenant();

  await mintState('superadmin', 'saas', ENV.saasTenantUrl);
  await mintState('hr', 'saas', ENV.saasTenantUrl);
  await mintState('employee', 'saas', ENV.saasTenantUrl);
  await mintState('landlord', 'saas', ENV.saasAdminUrl);
  console.log('[uat] saas ready');
}
