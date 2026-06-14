import { artisan } from './artisan.ts';
import { ENV } from './env.ts';

/** Create + synchronously provision the known UAT SaaS tenant, then seed its DB. */
export function provisionUatTenant(): void {
  const out = artisan(ENV.saasHostPath, [
    'tinker',
    '--execute',
    "require database_path('seeders/uat_provision.php');",
  ]);
  if (!/UAT tenant provisioned/.test(out)) {
    throw new Error(`Tenant provisioning did not confirm success:\n${out}`);
  }
}
