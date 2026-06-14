import { ENV } from './env.ts';
import { artisan } from './artisan.ts';

export function cleanupTenant(subdomain: string): void {
  const php = [
    "$t = \\Aero\\Platform\\Models\\Tenant::where('subdomain','" + subdomain + "')->first();",
    'if ($t) {',
    '    \\DB::statement("DROP DATABASE IF EXISTS `" . $t->database()->getName() . "`");',
    '    $t->forceDelete();',
    '    echo "cleanup_ok";',
    '} else {',
    '    echo "no_tenant";',
    '}',
  ].join(' ');
  try {
    artisan(ENV.saasHostPath, ['tinker', '--execute', php]);
  } catch (error) {
    // Swallow teardown errors so failing tests still surface the actual failure.
  }
}
