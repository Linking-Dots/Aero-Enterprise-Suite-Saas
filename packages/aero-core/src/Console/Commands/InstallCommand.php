<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Models\SystemSetting;
use Aero\Core\Models\User;
use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\HRMAC\Models\Module;
use Aero\HRMAC\Models\Role;
use Aero\Platform\AeroPlatformServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aero:install 
                            {--force : Force installation even if already installed}
                            {--skip-modules : Skip module synchronization}
                            {--admin-name= : Admin user name}
                            {--admin-email= : Admin user email}
                            {--admin-password= : Admin user password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Aero Enterprise Suite in standalone mode';

    /**
     * Installation lock file
     */
    private const LOCK_FILE = 'storage/framework/aero-installation.lock';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('==============================================');
        $this->info('  Aero Enterprise Suite - Standalone Installation');
        $this->info('==============================================');
        $this->newLine();

        // Check if already installed
        if ($this->isInstalled() && ! $this->option('force')) {
            $this->error('❌ Aero is already installed!');
            $this->info('💡 Use --force to reinstall (WARNING: This will delete all data)');

            return self::FAILURE;
        }

        if ($this->option('force')) {
            if (! $this->confirm('⚠️  Force installation will delete ALL existing data. Continue?', false)) {
                $this->info('Installation cancelled.');

                return self::FAILURE;
            }
        }

        // Pre-installation checks
        if (! $this->preInstallationChecks()) {
            return self::FAILURE;
        }

        // Create installation lock
        $this->createLock();

        try {
            // Step 1: Database Setup
            $this->info('📦 Step 1/5: Setting up database...');
            if (! $this->setupDatabase()) {
                throw new \Exception('Database setup failed');
            }

            // Step 2: Run Migrations
            $this->info('🔄 Step 2/5: Running migrations...');
            if (! $this->runMigrations()) {
                throw new \Exception('Migration failed');
            }

            // Step 3: Seed Core Data
            $this->info('🌱 Step 3/5: Seeding core data...');
            if (! $this->seedCoreData()) {
                throw new \Exception('Seeding failed');
            }

            // Step 4: Create Admin User
            $this->info('👤 Step 4/5: Creating admin user...');
            if (! $this->createAdminUser()) {
                throw new \Exception('Admin user creation failed');
            }

            // Step 5: Sync Modules
            if (! $this->option('skip-modules')) {
                $this->info('🔗 Step 5/5: Synchronizing modules...');
                if (! $this->syncModules()) {
                    throw new \Exception('Module synchronization failed');
                }
            } else {
                $this->info('⏩ Step 5/5: Skipped module synchronization');
            }

            // Mark as installed
            $this->markAsInstalled();

            $this->newLine();
            $this->info('✅ Installation completed successfully!');
            $this->newLine();
            $this->line('🌐 You can now access your application at: '.config('app.url'));
            $this->line('📧 Admin Email: '.$this->getAdminEmail());
            $this->newLine();

            // Remove lock
            $this->removeLock();

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->removeLock();
            $this->error('❌ Installation failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Pre-installation checks
     */
    private function preInstallationChecks(): bool
    {
        $this->info('🔍 Running pre-installation checks...');

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->error('❌ PHP 8.2 or higher is required. Current: '.PHP_VERSION);

            return false;
        }

        // Check required extensions
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        foreach ($requiredExtensions as $ext) {
            if (! extension_loaded($ext)) {
                $this->error("❌ Required PHP extension '{$ext}' is not loaded");

                return false;
            }
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: '.$e->getMessage());

            return false;
        }

        // Check writable directories
        $writableDirs = [
            storage_path(),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($writableDirs as $dir) {
            if (! is_writable($dir)) {
                $this->error("❌ Directory not writable: {$dir}");

                return false;
            }
        }

        $this->info('✅ All pre-installation checks passed');

        return true;
    }

    /**
     * Setup database
     */
    private function setupDatabase(): bool
    {
        try {
            // Test connection
            $pdo = DB::connection()->getPdo();
            $database = DB::connection()->getDatabaseName();

            $this->line("   Connected to database: {$database}");

            if ($this->option('force')) {
                $this->warn('   Dropping all tables...');
                Artisan::call('db:wipe', ['--force' => true]);
            }

            return true;
        } catch (\Exception $e) {
            $this->error('   Database setup failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Run migrations
     */
    private function runMigrations(): bool
    {
        try {
            $this->line('   Running migrations...');

            Artisan::call('migrate', [
                '--force' => true,
                '--step' => false,
            ]);

            $this->line('   ✅ Migrations completed');

            return true;
        } catch (\Exception $e) {
            $this->error('   Migration failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Seed core data (roles, permissions, system settings)
     */
    private function seedCoreData(): bool
    {
        try {
            DB::beginTransaction();

            // Create roles
            $this->line('   Creating roles...');
            $roles = [
                'Super Admin' => 'Full system access',
                'Admin' => 'Administrative access',
                'Manager' => 'Management access',
                'Employee' => 'Basic employee access',
            ];

            foreach ($roles as $name => $description) {
                Role::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['description' => $description]
                );
            }

            // Create system settings
            $this->line('   Creating system settings...');
            $settings = [
                ['key' => 'app_name', 'value' => config('app.name', 'Aero ERP'), 'type' => 'general'],
                ['key' => 'timezone', 'value' => config('app.timezone', 'UTC'), 'type' => 'general'],
                ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'general'],
                ['key' => 'time_format', 'value' => 'H:i:s', 'type' => 'general'],
                ['key' => 'currency', 'value' => 'USD', 'type' => 'general'],
                ['key' => 'installation_date', 'value' => now()->toDateTimeString(), 'type' => 'system'],
                ['key' => 'mode', 'value' => 'standalone', 'type' => 'system'],
            ];

            foreach ($settings as $setting) {
                SystemSetting::firstOrCreate(
                    ['key' => $setting['key']],
                    $setting
                );
            }

            DB::commit();
            $this->line('   ✅ Core data seeded');

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('   Seeding failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Create admin user
     */
    private function createAdminUser(): bool
    {
        try {
            DB::beginTransaction();

            // Get admin details
            $name = $this->option('admin-name') ?? $this->ask('Admin Name', 'Super Admin');
            $email = $this->option('admin-email') ?? $this->ask('Admin Email', 'admin@example.com');

            // Validate email
            $validator = Validator::make(['email' => $email], [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                throw new \Exception('Invalid email address');
            }

            // Get password
            $password = $this->option('admin-password');
            if (! $password) {
                $password = $this->secret('Admin Password (min 8 characters)');
                $confirmPassword = $this->secret('Confirm Password');

                if ($password !== $confirmPassword) {
                    throw new \Exception('Passwords do not match');
                }

                if (strlen($password) < 8) {
                    throw new \Exception('Password must be at least 8 characters');
                }
            }

            // Create user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            // Assign Super Admin role
            $superAdminRole = Role::where('name', 'Super Admin')->first();
            if ($superAdminRole) {
                $user->assignRole($superAdminRole);
            }

            DB::commit();
            $this->line("   ✅ Admin user created: {$email}");

            // Store email for later display
            $this->adminEmail = $email;

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('   Admin user creation failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sync modules from packages
     */
    private function syncModules(): bool
    {
        try {
            $this->line('   Discovering and syncing modules...');

            // Check if HRMAC package is available (preferred)
            if (class_exists(SyncModuleHierarchy::class)) {
                // Determine scope based on mode
                // In standalone mode (no Platform package), sync ALL modules
                // In SaaS mode with Platform, sync platform-scoped modules for central database
                $scope = class_exists(AeroPlatformServiceProvider::class)
                    ? 'platform'  // SaaS mode - platform modules for central database
                    : 'all';      // Standalone mode - all modules

                $this->line("   Using HRMAC sync (scope: {$scope})...");

                Artisan::call('hrmac:sync-modules', [
                    '--scope' => $scope,
                    '--prune' => true,
                ]);

                $moduleCount = Module::count();
                $this->line("   ✅ {$moduleCount} modules synchronized");
            } else {
                // Fallback to legacy sync command
                $this->line('   Using legacy aero:sync-module...');
                Artisan::call('aero:sync-module', [
                    '--prune' => true,
                ]);

                $moduleCount = \Aero\Core\Models\Module::count();
                $this->line("   ✅ {$moduleCount} modules synchronized (legacy)");
            }

            return true;
        } catch (\Exception $e) {
            $this->error('   Module sync failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Check if already installed
     */
    private function isInstalled(): bool
    {
        try {
            // Check if we can connect to database
            DB::connection()->getPdo();

            // Check if migrations table exists and has data
            if (! DB::getSchemaBuilder()->hasTable('migrations')) {
                return false;
            }

            // Check if key tables exist
            $requiredTables = ['users', 'roles', 'system_settings', 'modules'];
            foreach ($requiredTables as $table) {
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    return false;
                }
            }

            // Check if we have at least one user
            if (User::count() === 0) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mark installation as complete
     */
    private function markAsInstalled(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'installation_completed'],
            [
                'value' => now()->toDateTimeString(),
                'type' => 'system',
            ]
        );
    }

    /**
     * Create installation lock
     */
    private function createLock(): void
    {
        $lockPath = base_path(self::LOCK_FILE);
        File::ensureDirectoryExists(dirname($lockPath));
        File::put($lockPath, json_encode([
            'started_at' => now()->toDateTimeString(),
            'pid' => getmypid(),
        ]));
    }

    /**
     * Remove installation lock
     */
    private function removeLock(): void
    {
        $lockPath = base_path(self::LOCK_FILE);
        if (File::exists($lockPath)) {
            File::delete($lockPath);
        }
    }

    /**
     * Get admin email
     */
    private function getAdminEmail(): string
    {
        return $this->adminEmail ?? $this->option('admin-email') ?? 'admin@example.com';
    }

    private ?string $adminEmail = null;
}
