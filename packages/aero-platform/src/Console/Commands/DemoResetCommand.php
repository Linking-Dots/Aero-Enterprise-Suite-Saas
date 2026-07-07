<?php

declare(strict_types=1);

namespace Aero\Platform\Console\Commands;

use Aero\HRM\Database\Seeders\HrmDemoSeeder;
use Aero\Platform\Database\Seeders\PlatformDemoSeeder;
use Aero\Platform\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * demo:reset — restore the live-demo tenant(s) + landlord demo data to a clean,
 * realistic seeded state so public visitors can freely click/edit and it heals.
 *
 * Strategy (per tenant), most-faithful first:
 *  1. If a pristine SQL snapshot exists (deploy/<subdomain>.sql), re-import it
 *     (restores EXACT state incl. employees/leave/payroll transactional data).
 *  2. Otherwise re-run the idempotent demo seeders (reference data + landlord).
 *
 * Scheduled nightly (Asia/Dhaka) — see AeroPlatformServiceProvider schedule().
 */
class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--tenant= : Limit to a single demo tenant subdomain}';

    protected $description = 'Reset live-demo tenant(s) + landlord demo data to seeded state';

    public function handle(): int
    {
        $this->info('🔄 demo:reset starting…');

        // 1. Landlord demo data (idempotent).
        try {
            Artisan::call('db:seed', ['--class' => PlatformDemoSeeder::class, '--force' => true]);
            $this->info('  ✓ landlord demo data reseeded');
        } catch (Throwable $e) {
            $this->error('  ✗ landlord reseed failed: '.$e->getMessage());
        }

        // 2. Each demo tenant.
        $query = Tenant::query()->where('is_demo', true);
        if ($sub = $this->option('tenant')) {
            $query->where('subdomain', $sub);
        }
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->warn('  No demo tenants (is_demo=1) found.');

            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->line("  → {$tenant->subdomain} ({$tenant->id})");
            $snapshot = base_path("deploy/{$tenant->subdomain}.sql");

            try {
                if (File::exists($snapshot)) {
                    $this->restoreSnapshot($tenant, $snapshot);
                    $this->info('    ✓ restored from snapshot');
                } else {
                    $tenant->run(function () {
                        Artisan::call('db:seed', ['--class' => HrmDemoSeeder::class, '--force' => true]);
                    });
                    $this->info('    ✓ reference data reseeded (no snapshot found)');
                }

                // Guardrail: always restore the public demo admin credentials so
                // the exposed login keeps working no matter what a visitor did.
                $this->ensureDemoAdmin($tenant);
                $this->info('    ✓ demo admin credentials enforced');
            } catch (Throwable $e) {
                $this->error("    ✗ {$tenant->subdomain} failed: ".$e->getMessage());
            }
        }

        $this->info('✅ demo:reset complete.');

        return self::SUCCESS;
    }

    /**
     * Restore the public demo admin's known credentials inside the tenant DB.
     */
    private function ensureDemoAdmin(Tenant $tenant): void
    {
        $email = config('aero.demo.email', 'admin@democorp.com');
        $password = config('aero.demo.password', 'Aeos365!Admin');

        $tenant->run(function () use ($email, $password) {
            if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
                return;
            }
            DB::table('users')->where('email', $email)->update([
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);
        });
    }

    /**
     * Re-import a tenant DB from a pristine SQL snapshot (mysql client).
     */
    private function restoreSnapshot(Tenant $tenant, string $snapshot): void
    {
        $tenant->run(function () use ($snapshot) {
            $conn = DB::connection();
            $db = $conn->getDatabaseName();
            $cfg = $conn->getConfig();

            $cmd = sprintf(
                'mysql -h%s -P%s -u%s %s %s < %s',
                escapeshellarg($cfg['host'] ?? '127.0.0.1'),
                escapeshellarg((string) ($cfg['port'] ?? 3306)),
                escapeshellarg($cfg['username'] ?? 'root'),
                ! empty($cfg['password']) ? '-p'.escapeshellarg($cfg['password']) : '',
                escapeshellarg($db),
                escapeshellarg($snapshot),
            );
            exec($cmd, $out, $code);
            if ($code !== 0) {
                throw new \RuntimeException("mysql import exited {$code}");
            }
        });
    }
}
