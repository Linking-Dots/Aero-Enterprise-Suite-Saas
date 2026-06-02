import { config } from 'dotenv';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const here = dirname(fileURLToPath(import.meta.url));
config({ path: resolve(here, '../.env') });

function req(name: string): string {
  const v = process.env[name];
  if (!v) throw new Error(`Missing required env var ${name} (copy e2e/.env.example to e2e/.env)`);
  return v;
}

export const ENV = {
  saasPlatformUrl: req('SAAS_PLATFORM_URL'),
  saasAdminUrl: req('SAAS_ADMIN_URL'),
  saasTenantSubdomain: req('SAAS_TENANT_SUBDOMAIN'),
  saasTenantUrl: req('SAAS_TENANT_URL'),
  standaloneUrl: req('STANDALONE_URL'),
  saasHostPath: req('SAAS_HOST_PATH'),
  standaloneHostPath: req('STANDALONE_HOST_PATH'),
  password: req('UAT_PASSWORD'),
  superAdminEmail: req('UAT_SUPERADMIN_EMAIL'),
  hrEmail: req('UAT_HR_EMAIL'),
  employeeEmail: req('UAT_EMPLOYEE_EMAIL'),
  landlordEmail: req('UAT_LANDLORD_EMAIL'),
  stripeKey: process.env.STRIPE_KEY ?? '',
  stripeSecret: process.env.STRIPE_SECRET ?? '',
  runDestructive: process.env.RUN_DESTRUCTIVE === '1',
  skipGlobalSetup: process.env.SKIP_GLOBAL_SETUP === '1',
  hasStripe(): boolean { return !!this.stripeSecret && !!this.stripeKey; },
};
