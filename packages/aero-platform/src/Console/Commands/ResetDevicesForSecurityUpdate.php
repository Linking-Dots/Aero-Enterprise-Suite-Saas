<?php

namespace Aero\Platform\Console\Commands;

use Aero\Auth\Models\UserDevice;
use Aero\Contracts\AeroMode;
use Illuminate\Console\Command;

class ResetDevicesForSecurityUpdate extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'devices:reset-for-security-update
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Reset all user devices for security update (fixes cross-account device fingerprinting vulnerability)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will clear ALL user devices and force users to re-login. Continue?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        $this->info('Starting device reset for security update...');

        // Auth-identity unification (2D-step3): UserDevice now lives in aero-auth and
        // carries the EnforcesTenantContext guard. Landlord devices are in the CENTRAL
        // user_devices table, so in SaaS this command targets the 'central' connection
        // via ::on('central'). That both points the count()/distinct() queries at the
        // right table AND satisfies the guard's central escape for them; truncate()
        // bypasses Eloquent global scopes entirely, so it relies on the connection only.
        // In standalone there is a single DB and the guard is a no-op (default connection).
        $query = fn () => AeroMode::isSaas()
            ? UserDevice::on('central')
            : UserDevice::query();

        // Get count before deletion
        $deviceCount = $query()->count();
        $userCount = $query()->distinct('user_id')->count('user_id');

        // Delete all devices
        $query()->truncate();

        $this->info("✓ Cleared {$deviceCount} devices for {$userCount} users");
        $this->info('✓ All users will be required to re-login from their devices');
        $this->info('✓ New secure device fingerprinting will be applied on next login');

        $this->newLine();
        $this->warn('IMPORTANT: Users will be logged out on their next request and must re-authenticate.');

        return Command::SUCCESS;
    }
}
