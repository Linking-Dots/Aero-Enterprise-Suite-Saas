<?php

namespace Aero\Installation\Http\Controllers;

use Aero\Core\Models\Module;
use Aero\Core\Models\ModuleComponent;
use Aero\Core\Models\ModuleComponentAction;
use Aero\Core\Models\SubModule;
use Aero\Core\Services\InstallationState;
use Aero\Core\Services\LicenseValidationService;
use Aero\Core\Services\Module\ModuleDiscoveryService;
use Aero\Installation\Installation\InstallationOrchestrator;
use Aero\Installation\Installation\Steps\AdminUserStep;
use Aero\Installation\Installation\Steps\CacheStep;
use Aero\Installation\Installation\Steps\ConfigurationStep;
use Aero\Installation\Installation\Steps\DatabaseConnectionStep;
use Aero\Installation\Installation\Steps\FinalizeStep;
use Aero\Installation\Installation\Steps\LicenseStep;
use Aero\Installation\Installation\Steps\MigrationStep;
use Aero\Installation\Installation\Steps\ModuleDiscoveryStep;
use Aero\Installation\Installation\Steps\PlanSeedingStep;
use Aero\Installation\Installation\Steps\PlatformConfigurationStep;
use Aero\Installation\Installation\Steps\SeedingStep;
use Aero\Installation\Installation\Steps\SettingsStep;
use Dotenv\Dotenv;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Unified Installation Controller
 *
 * Handles installation for both SaaS (Platform) and Standalone (Core) modes.
 * Uses the unified UI components from aero-ui package.
 *
 * Mode detection:
 * - 'saas': When aero-platform package is installed and SAAS_MODE=true
 * - 'standalone': Default mode for single-tenant installations
 */
class UnifiedInstallationController extends Controller
{
    /**
     * Installation lock file (unified path for both core and platform)
     */
    private const LOCK_FILE = 'app/aeos.installed';

    /**
     * Progress file for tracking installation steps
     */
    private const PROGRESS_FILE = 'storage/framework/installation_progress.json';

    /**
     * Configuration persistence path
     */
    private const CONFIG_PATH = 'storage/framework/installation_config.json';

    /**
     * Installation timeout in minutes
     */
    private const INSTALLATION_TIMEOUT = 30;

    /**
     * License validation service (optional for SaaS mode)
     */
    protected ?LicenseValidationService $licenseService;

    public function __construct(?LicenseValidationService $licenseService = null)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Detect installation mode
     */
    protected function getMode(): string
    {
        // Check if Platform package is installed and SaaS mode is enabled
        if (class_exists('\Aero\Platform\AeroPlatformServiceProvider')
            && config('aero.mode', 'standalone') === 'saas') {
            return 'saas';
        }

        return 'standalone';
    }

    /**
     * Get step configuration based on mode
     */
    protected function getSteps(): array
    {
        $mode = $this->getMode();

        if ($mode === 'saas') {
            return [
                1 => 'welcome',
                2 => 'requirements',
                3 => 'database',
                4 => 'settings',
                5 => 'admin',
                6 => 'review',
                7 => 'processing',
                8 => 'complete',
            ];
        }

        return [
            1 => 'welcome',
            2 => 'license',
            3 => 'requirements',
            4 => 'database',
            5 => 'settings',
            6 => 'admin',
            7 => 'review',
            8 => 'processing',
            9 => 'complete',
        ];
    }

    /**
     * Check if application is already installed
     */
    protected function isInstalled(): bool
    {
        return InstallationState::isInstalled();
    }

    /**
     * Persist configuration to file (browser-refresh safe)
     */
    protected function persistConfig(string $key, array $data): void
    {
        $configPath = base_path(self::CONFIG_PATH);
        $config = [];

        if (File::exists($configPath)) {
            $config = json_decode(File::get($configPath), true) ?? [];
        }

        $config[$key] = $data;
        $config['_updated_at'] = now()->toDateTimeString();
        $config['_mode'] = $this->getMode();

        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Get persisted configuration
     */
    protected function getPersistedConfig(?string $key = null): mixed
    {
        $configPath = base_path(self::CONFIG_PATH);

        if (! File::exists($configPath)) {
            return $key ? null : [];
        }

        $config = json_decode(File::get($configPath), true) ?? [];

        return $key ? ($config[$key] ?? null) : $config;
    }

    /**
     * Clear persisted configuration
     */
    protected function clearPersistedConfig(): void
    {
        $configPath = base_path(self::CONFIG_PATH);

        if (File::exists($configPath)) {
            File::delete($configPath);
        }
    }

    /**
     * Show welcome page
     */
    public function welcome()
    {
        if ($this->isInstalled()) {
            return $this->alreadyInstalled();
        }

        $mode = $this->getMode();
        $steps = $this->getSteps();

        return Inertia::render('Installation/Welcome', [
            'title' => 'Welcome to aeos365',
            'mode' => $mode,
            'version' => config('app.version', '1.0.0'),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'steps' => array_values($steps),
            'installedModules' => $this->getInstalledModules(),
        ]);
    }

    /**
     * Show license validation page (Standalone only)
     */
    public function license()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        // Skip license for SaaS mode
        if ($this->getMode() === 'saas') {
            return redirect()->route('installation.requirements');
        }

        return Inertia::render('Installation/License', [
            'title' => 'License Validation',
            'mode' => 'standalone',
            'savedLicense' => $this->getPersistedConfig('license'),
        ]);
    }

    /**
     * Validate license key
     */
    public function validateLicense(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string|min:10',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Development mode bypass: automatically validate test licenses in local environment
        if (config('app.env') === 'local' && str_starts_with($request->license_key, 'AP-TEST-')) {
            $this->persistConfig('license', [
                'key' => $request->license_key,
                'email' => $request->email,
                'provider' => 'aero',
                'type' => 'extended',
                'valid_until' => now()->addYear()->toDateString(),
                'allowed_modules' => ['all'], // Allow all modules in dev mode
                'validated_at' => now()->toDateTimeString(),
                'is_dev_license' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Development license validated successfully',
                'data' => [
                    'provider' => 'aero',
                    'type' => 'extended',
                    'valid_until' => now()->addYear()->toDateString(),
                    'allowed_modules' => ['all'],
                    'is_dev_license' => true,
                ],
            ]);
        }

        if (! $this->licenseService) {
            return response()->json([
                'success' => false,
                'message' => 'License validation service not available. Use AP-TEST-* license key in development mode.',
            ], 500);
        }

        // Validate license against API
        $result = $this->licenseService->validate(
            $request->license_key,
            $request->email,
            $request->get('domain', request()->getHost())
        );

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        // Persist license data
        $this->persistConfig('license', [
            'key' => $request->license_key,
            'email' => $request->email,
            'provider' => $result['data']['provider'] ?? 'unknown',
            'type' => $result['data']['type'] ?? 'regular',
            'valid_until' => $result['data']['valid_until'] ?? null,
            'allowed_modules' => $result['data']['allowed_modules'] ?? [],
            'validated_at' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'License validated successfully',
            'data' => $result['data'],
        ]);
    }

    /**
     * Show requirements check page
     */
    public function requirements()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $checks = $this->performRequirementsCheck();

        return Inertia::render('Installation/Requirements', [
            'title' => 'System Requirements',
            'mode' => $this->getMode(),
            'checks' => $checks,
            'canProceed' => $checks['allPassed'],
        ]);
    }

    /**
     * Re-check requirements (AJAX)
     */
    public function recheckRequirements()
    {
        $checks = $this->performRequirementsCheck();

        return response()->json([
            'success' => true,
            'checks' => $checks,
            'canProceed' => $checks['allPassed'],
        ]);
    }

    /**
     * Show database configuration page
     */
    public function database()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        return Inertia::render('Installation/Database', [
            'title' => 'Database Configuration',
            'mode' => $this->getMode(),
            'savedDatabase' => $this->getPersistedConfig('database'),
            'connections' => ['mysql', 'pgsql', 'sqlite', 'sqlsrv'],
        ]);
    }

    /**
     * Test database server connection
     */
    public function testDatabaseServer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'connection' => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'host' => 'required_unless:connection,sqlite|string',
            'port' => 'required_unless:connection,sqlite|integer',
            'username' => 'required_unless:connection,sqlite|string',
            'password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $connection = $request->connection;

            // Configure temporary connection
            Config::set('database.connections.installation_test', array_merge(
                config("database.connections.{$connection}"),
                [
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'password' => $request->password ?? '',
                    'database' => null, // Don't specify database yet
                ]
            ));

            // Test connection
            DB::connection('installation_test')->getPdo();

            return response()->json([
                'success' => true,
                'message' => 'Server connection successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * List available databases
     */
    public function listDatabases(Request $request)
    {
        try {
            $connection = $request->connection ?? 'mysql';

            Config::set('database.connections.installation_test', array_merge(
                config("database.connections.{$connection}"),
                [
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'password' => $request->password ?? '',
                    'database' => null,
                ]
            ));

            $databases = DB::connection('installation_test')
                ->select('SHOW DATABASES');

            $systemDbs = ['information_schema', 'mysql', 'performance_schema', 'sys', 'phpmyadmin'];
            $filtered = array_filter(
                array_column($databases, 'Database'),
                fn ($db) => ! in_array($db, $systemDbs)
            );

            return response()->json([
                'success' => true,
                'databases' => array_values($filtered),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list databases: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create new database
     */
    public function createDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|alpha_dash|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid database name',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $connection = $request->connection ?? 'mysql';

            Config::set('database.connections.installation_test', array_merge(
                config("database.connections.{$connection}"),
                [
                    'host' => $request->host,
                    'port' => $request->port,
                    'username' => $request->username,
                    'password' => $request->password ?? '',
                    'database' => null,
                ]
            ));

            DB::connection('installation_test')
                ->statement("CREATE DATABASE IF NOT EXISTS `{$request->name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            return response()->json([
                'success' => true,
                'message' => "Database '{$request->name}' created successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create database: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Save database configuration
     */
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'connection' => 'required|string|in:mysql,pgsql,sqlite,sqlsrv',
            'host' => 'required_unless:connection,sqlite|string',
            'port' => 'required_unless:connection,sqlite|integer',
            'database' => 'required|string',
            'username' => 'required_unless:connection,sqlite|string',
            'password' => 'nullable|string',
        ]);

        // Test full connection (skip for SQLite)
        if ($request->connection !== 'sqlite') {
            try {
                Config::set('database.connections.installation_test', array_merge(
                    config("database.connections.{$request->connection}"),
                    [
                        'host' => $request->host,
                        'port' => $request->port,
                        'database' => $request->database,
                        'username' => $request->username,
                        'password' => $request->password ?? '',
                    ]
                ));

                DB::connection('installation_test')->getPdo();
            } catch (\Exception $e) {
                return back()->withErrors(['database' => 'Database connection failed: '.$e->getMessage()]);
            }
        }

        $this->persistConfig('database', $request->only([
            'connection', 'host', 'port', 'database', 'username', 'password',
        ]));

        // Inertia-standard response: this endpoint is called via Inertia's router.post,
        // so it must return an Inertia response (a redirect), NOT plain JSON — returning
        // JSON trips Inertia's "must receive a valid Inertia response" guard. back()
        // reloads the step with fresh props (savedDatabase) so onSuccess fires client-side.
        return back();
    }

    /**
     * Show settings page (mode-aware)
     */
    public function settings()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $mode = $this->getMode();
        $savedSettings = $this->getPersistedConfig('settings');

        return Inertia::render('Installation/Settings', [
            'title' => $mode === 'saas' ? 'Platform Settings' : 'System Settings',
            'mode' => $mode,
            'savedSettings' => $savedSettings,
            'timezones' => $this->getTimezones(),
        ]);
    }

    /**
     * Save settings (Platform or System)
     */
    public function saveSettings(Request $request)
    {
        $mode = $this->getMode();

        if ($mode === 'saas') {
            return $this->savePlatformSettings($request);
        }

        return $this->saveSystemSettings($request);
    }

    /**
     * Save Platform settings (SaaS mode)
     */
    protected function savePlatformSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:100',
            'support_email' => 'required|email',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
        ]);

        $this->persistConfig('settings', $request->all());

        // Inertia-standard response (called via Inertia router.post) — return a redirect,
        // not JSON, so Inertia's response guard is satisfied and onSuccess fires.
        return back();
    }

    /**
     * Save System settings (Standalone mode)
     */
    protected function saveSystemSettings(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:100',
            'support_email' => 'required|email',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
        ]);

        $this->persistConfig('settings', $request->all());

        // Inertia-standard response (called via Inertia router.post) — return a redirect,
        // not JSON, so Inertia's response guard is satisfied and onSuccess fires.
        return back();
    }

    /**
     * Show admin account page
     */
    public function admin()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        return Inertia::render('Installation/Admin', [
            'title' => 'Admin Account',
            'mode' => $this->getMode(),
            'savedAdmin' => $this->getPersistedConfig('admin'),
        ]);
    }

    /**
     * Save admin account
     */
    public function saveAdmin(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        // Security: store only the bcrypt hash — never persist plaintext passwords to disk.
        $this->persistConfig('admin', [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
        ]);

        // Inertia-standard response (called via Inertia useForm().post) — return a redirect,
        // not JSON, so Inertia's response guard is satisfied and onSuccess fires.
        return back();
    }

    /**
     * Show review page
     */
    public function review()
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $config = $this->getPersistedConfig();
        $mode = $this->getMode();

        // Build summary
        $summary = [
            'database' => $config['database'] ?? [],
            'settings' => $config['settings'] ?? [],
            'admin' => [
                'first_name' => $config['admin']['first_name'] ?? '',
                'last_name' => $config['admin']['last_name'] ?? '',
                'email' => $config['admin']['email'] ?? '',
            ],
        ];

        // Add license for standalone mode
        if ($mode === 'standalone' && isset($config['license'])) {
            $summary['license'] = [
                'key' => $config['license']['key'] ?? '',
                'provider' => $config['license']['provider'] ?? '',
                'type' => $config['license']['type'] ?? '',
                'valid_until' => $config['license']['valid_until'] ?? null,
            ];
            $summary['modules'] = $config['license']['allowed_modules'] ?? [];
        }

        return Inertia::render('Installation/Review', [
            'title' => 'Review Configuration',
            'mode' => $mode,
            'summary' => $summary,
        ]);
    }

    /**
     * Show processing page
     */
    public function processing()
    {
        if ($this->isInstalled()) {
            return redirect()->route('installation.complete');
        }

        return Inertia::render('Installation/Processing', [
            'title' => 'Installing',
            'mode' => $this->getMode(),
        ]);
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer',
            'mail_from_address' => 'required|email',
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Configure mailer dynamically
            Config::set('mail.mailers.smtp.host', $request->mail_host);
            Config::set('mail.mailers.smtp.port', $request->mail_port);
            Config::set('mail.mailers.smtp.username', $request->mail_username ?? '');
            Config::set('mail.mailers.smtp.password', $request->mail_password ?? '');
            Config::set('mail.mailers.smtp.encryption', $request->mail_encryption ?? 'tls');
            Config::set('mail.from.address', $request->mail_from_address);
            Config::set('mail.from.name', $request->mail_from_name ?? 'Aero Installation Test');

            // Send test email
            Mail::raw(
                'This is a test email from the aeos365 installation wizard. If you received this email, your SMTP configuration is working correctly.',
                function ($message) use ($request) {
                    $message->to($request->test_email)
                        ->subject('aeos365 - Test Email');
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute installation
     */
    public function execute()
    {
        if ($this->isInstalled()) {
            return redirect()->route('installation.complete');
        }

        try {
            Cache::put('installation_in_progress', true, now()->addMinutes(self::INSTALLATION_TIMEOUT));

            $progressFile = base_path(self::PROGRESS_FILE);
            if (File::exists($progressFile)) {
                File::delete($progressFile);
            }

            return redirect('/install/processing');
        } catch (\Exception $e) {
            Log::error('Installation execute failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to start installation: '.$e->getMessage());
        }
    }

    /**
     * Get installation progress for polling
     */
    public function progress()
    {
        Log::info('[Installation] progress() called');

        $progressData = $this->getProgressData();

        // Surface a tail of the real installation log lines for the "Show details"
        // panel on the Processing screen. Read once here so every getProgressData()
        // return path gets it.
        $progressData['log'] = $this->getInstallationLogTail();

        Log::info('[Installation] Progress data returned', [
            'percentage' => $progressData['percentage'] ?? 0,
            'current_step' => $progressData['currentStep'] ?? 'unknown',
            'status' => $progressData['status'] ?? 'unknown',
            'completed_steps' => $progressData['completedSteps'] ?? 0,
            'total_steps' => $progressData['totalSteps'] ?? 0,
        ]);

        return response()->json($progressData);
    }

    /**
     * Read the tail of the most recent Laravel log file and return only the
     * installation-related lines (those tagged "[Installation"), trimmed of the
     * leading timestamp/level prefix and trailing JSON context for readability.
     *
     * @return array<int, string>
     */
    protected function getInstallationLogTail(int $lines = 25): array
    {
        try {
            $dir = storage_path('logs');
            if (! is_dir($dir)) {
                return [];
            }

            $files = glob($dir.DIRECTORY_SEPARATOR.'laravel*.log') ?: [];
            if (empty($files)) {
                return [];
            }

            // Newest file first (handles the `daily` channel's rotated files).
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            $file = $files[0];

            // Only read the last chunk of the file to avoid loading huge logs.
            $size = filesize($file);
            $chunk = (int) min($size, 256 * 1024);
            $handle = fopen($file, 'rb');
            if ($handle === false) {
                return [];
            }
            if ($chunk < $size) {
                fseek($handle, -$chunk, SEEK_END);
            }
            $content = (string) fread($handle, $chunk);
            fclose($handle);

            $out = [];
            foreach (preg_split('/\r?\n/', $content) ?: [] as $line) {
                $pos = stripos($line, '[Installation');
                if ($pos === false) {
                    continue;
                }
                // Keep from the "[Installation…]" marker, drop a trailing JSON context blob.
                $msg = preg_replace('/\s+\{.*\}\s*$/', '', substr($line, $pos));
                $msg = trim((string) $msg);
                if ($msg !== '') {
                    $out[] = $msg;
                }
            }

            return array_slice($out, -$lines);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get installation progress
     */
    public function getProgressData()
    {
        $mode = $this->getMode();

        // Treat the install as finished ONLY when the authoritative lock file exists
        // (storage/app/aeos.installed, written by FinalizeStep at the very end).
        // Do NOT use InstallationState::isInstalled() here: its schema fallback
        // (hasTable('users') && hasTable('modules')) turns true the moment MigrationStep
        // creates those tables — i.e. MID-INSTALL — which would short-circuit this poll to
        // "completed" before the module-sync, admin-user, seeding and finalize steps ever
        // run, leaving a half-installed system with no admin user and no lock file.
        if (File::exists(storage_path(self::LOCK_FILE))) {
            Log::info('[Installation] Lock file present — installation already complete');

            return [
                'status' => 'completed',
                'percentage' => 100,
                'currentStep' => 'complete',
                'completedSteps' => 12,
                'totalSteps' => 12,
                'message' => 'Installation completed successfully',
                'steps' => $this->getProgressSteps($mode),
            ];
        }

        $orchestratorKey = 'installation_orchestrator_'.session()->getId();

        // First check if orchestrator exists in cache (installation in progress)
        if (Cache::has($orchestratorKey)) {
            Log::info('[Installation] Orchestrator found in cache, continuing installation');

            return $this->runInstallation();
        }

        try {
            // Try to read from database-based progress tracking
            if (\Schema::hasTable('installation_progress')) {
                $progress = \DB::table('installation_progress')
                    ->where('session_id', session()->getId())
                    ->first();

                if ($progress) {
                    // If installation is completed, return the stored progress
                    if ($progress->status === 'completed') {
                        return [
                            'percentage' => $progress->percentage ?? 100,
                            'currentStep' => $progress->step ?? 'complete',
                            'status' => 'completed',
                            'error' => $progress->error ?? null,
                            'message' => 'Installation completed successfully',
                            'completedSteps' => $this->calculateCompletedSteps($progress->percentage ?? 100),
                            'totalSteps' => 12,
                            'steps' => $this->getProgressSteps($mode),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but continue to fallback
            Log::warning('Database progress read failed: '.$e->getMessage());
        }

        // Fallback to file-based progress tracking
        try {
            $progressFile = storage_path('app/installation_progress.json');
            if (File::exists($progressFile)) {
                $progress = json_decode(File::get($progressFile), true);
                // Only return file progress if installation is completed
                if ($progress['status'] === 'completed') {
                    return $progress;
                }
            }
        } catch (\Exception $e) {
            Log::warning('File progress read failed: '.$e->getMessage());
        }

        // Try old progress file path for backwards compatibility
        try {
            $oldProgressFile = base_path(self::PROGRESS_FILE);
            if (File::exists($oldProgressFile)) {
                $progress = json_decode(File::get($oldProgressFile), true);
                // Only return old file progress if installation is completed
                if ($progress['status'] === 'completed') {
                    return $progress;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Old file progress read failed: '.$e->getMessage());
        }

        // No progress found and no orchestrator in cache, start installation
        Log::info('[Installation] No progress found, starting installation');

        return $this->runInstallation();
    }

    /**
     * Calculate completed steps from percentage
     */
    protected function calculateCompletedSteps(int $percentage): int
    {
        $totalSteps = 12;

        return (int) round(($percentage / 100) * $totalSteps);
    }

    /**
     * Run the actual installation (called via progress polling)
     *
     * Now uses the centralized InstallationOrchestrator for consistent step execution
     */
    protected function runInstallation()
    {
        $mode = $this->getMode();
        putenv("INSTALLATION_MODE={$mode}");

        Log::info("[Installation::{$mode}] runInstallation() called", [
            'mode' => $mode,
            'session_id' => session()->getId(),
        ]);

        try {
            // Get or create orchestrator in session
            $orchestratorKey = 'installation_orchestrator_'.session()->getId();

            Log::info("[Installation::{$mode}] Retrieving/creating orchestrator", [
                'orchestrator_key' => $orchestratorKey,
                'exists_in_cache' => Cache::has($orchestratorKey),
            ]);

            $orchestrator = Cache::remember($orchestratorKey,
                now()->addMinutes(30),
                function () use ($mode) {
                    Log::info("[Installation::{$mode}] Creating new orchestrator instance");
                    $orch = new InstallationOrchestrator($mode);

                    // Register all steps
                    $stepsToRegister = [
                        new ConfigurationStep,
                        new DatabaseConnectionStep,
                        new MigrationStep($mode),
                        new ModuleDiscoveryStep($mode),
                        new PlanSeedingStep($mode),
                        new PlatformConfigurationStep($mode),
                        new AdminUserStep($mode),
                        new SeedingStep($mode),
                        new SettingsStep,
                        new CacheStep,
                        new LicenseStep,
                        new FinalizeStep,
                    ];

                    Log::info("[Installation::{$mode}] Registering steps", [
                        'count' => count($stepsToRegister),
                        'steps' => array_map(fn ($s) => get_class($s), $stepsToRegister),
                    ]);

                    $orch->registerSteps($stepsToRegister);

                    Log::info("[Installation::{$mode}] Steps registered successfully");

                    return $orch;
                }
            );

            Log::info("[Installation::{$mode}] Orchestrator retrieved", [
                'completed_count' => count($orchestrator->completed ?? []),
                'failed_count' => count($orchestrator->failed ?? []),
            ]);

            // Execute next step
            Log::info("[Installation::{$mode}] Executing next step");
            $progress = $orchestrator->executeNextStep();

            Log::info("[Installation::{$mode}] Step execution result", [
                'status' => $progress['status'] ?? 'unknown',
                'current_step' => $progress['currentStep'] ?? 'unknown',
                'percentage' => $progress['percentage'] ?? 0,
                'completed_steps' => $progress['completedSteps'] ?? 0,
                'total_steps' => $progress['totalSteps'] ?? 0,
            ]);

            // Save orchestrator state back to cache
            Cache::put($orchestratorKey, $orchestrator, now()->addMinutes(30));
            Log::info("[Installation::{$mode}] Orchestrator state saved to cache");

            // Update progress file for polling
            $this->updateProgress(
                $progress['percentage'] ?? 0,
                $progress['currentStep'] ?? 'unknown',
                $progress['status'] ?? 'running',
                $progress['error'] ?? null
            );

            // If completed or failed, forget orchestrator
            if (in_array($progress['status'], ['completed', 'failed'])) {
                Cache::forget($orchestratorKey);

                // Only create lock file if truly completed (all steps executed, no failures)
                if ($progress['status'] === 'completed' && ($progress['completedSteps'] ?? 0) >= ($progress['totalSteps'] ?? 1)) {
                    $this->createLockFile();
                }
            }

            // Return progress for UI
            return $progress;

        } catch (\Throwable $e) {
            Log::error('Installation failed via orchestrator: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            // Return error response
            return [
                'percentage' => 0,
                'currentStep' => 'error',
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completedSteps' => 0,
                'totalSteps' => 12,
            ];
        }
    }

    /**
     * Execute a specific installation step
     */
    protected function executeStep(string $step, array $config, string $mode): void
    {
        match ($step) {
            'config' => $this->stepWriteConfig($config),
            'database' => $this->stepSetupDatabase($config),
            'migrations' => $this->stepRunMigrations($mode),
            'seeders' => $this->stepRunSeeders($mode),
            'roles' => $this->stepCreateRoles($mode),
            'admin' => $this->stepCreateAdmin($config, $mode),
            'settings' => $this->stepSaveSettings($config, $mode),
            'modules' => $this->stepInstallModules($config),
            'cache' => $this->stepWarmCache(),
            'finalize' => $this->stepFinalize(),
            default => throw new \Exception("Unknown installation step: {$step}"),
        };
    }

    /**
     * Step: Write configuration files
     */
    protected function stepWriteConfig(array $config): void
    {
        $envPath = base_path('.env');
        $dbConfig = $config['database'] ?? [];
        $settings = $config['settings'] ?? [];

        // Read current .env
        $envContent = File::exists($envPath) ? File::get($envPath) : '';

        // Update database settings
        $envContent = $this->updateEnvValue($envContent, 'DB_CONNECTION', $dbConfig['connection'] ?? 'mysql');
        $envContent = $this->updateEnvValue($envContent, 'DB_HOST', $dbConfig['host'] ?? '127.0.0.1');
        $envContent = $this->updateEnvValue($envContent, 'DB_PORT', $dbConfig['port'] ?? '3306');
        $envContent = $this->updateEnvValue($envContent, 'DB_DATABASE', $dbConfig['database'] ?? 'aero');
        $envContent = $this->updateEnvValue($envContent, 'DB_USERNAME', $dbConfig['username'] ?? 'root');
        $envContent = $this->updateEnvValue($envContent, 'DB_PASSWORD', $dbConfig['password'] ?? '');

        // Ensure APP_KEY exists (installer may run before a key is generated)
        if (! preg_match('/^APP_KEY\s*=\s*/m', $envContent)) {
            $generatedKey = 'base64:'.base64_encode(random_bytes(32));
            $envContent = rtrim($envContent)."\nAPP_KEY={$generatedKey}\n";
        }

        // Update app settings
        $envContent = $this->updateEnvValue($envContent, 'APP_URL', $settings['app_url'] ?? 'http://localhost');
        $envContent = $this->updateEnvValue($envContent, 'APP_TIMEZONE', $settings['timezone'] ?? 'UTC');

        // Persist Aero platform mode so it survives server restarts after installation.
        // Without this, AERO_MODE would revert to the .env value (often 'standalone')
        // after installation completes, breaking SaaS mode on the next request.
        $envContent = $this->updateEnvValue($envContent, 'AERO_MODE', $this->getMode());

        // Email settings
        if (! empty($settings['mail_host'])) {
            $envContent = $this->updateEnvValue($envContent, 'MAIL_MAILER', $settings['mail_driver'] ?? 'smtp');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_HOST', $settings['mail_host']);
            $envContent = $this->updateEnvValue($envContent, 'MAIL_PORT', $settings['mail_port'] ?? '587');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_USERNAME', $settings['mail_username'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_PASSWORD', $settings['mail_password'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_ENCRYPTION', $settings['mail_encryption'] ?? 'tls');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_ADDRESS', $settings['mail_from_address'] ?? '');
            $envContent = $this->updateEnvValue($envContent, 'MAIL_FROM_NAME', $settings['mail_from_name'] ?? '');
        }

        // Write atomically: write to temp file first, then rename (prevents corrupt state
        // if two concurrent requests race to write the .env file).
        $tmpPath = $envPath.'.install.tmp';
        File::put($tmpPath, $envContent);
        rename($tmpPath, $envPath);

        // Clear config cache by removing cached files directly (no Artisan commands)
        $this->clearConfigCache();

        // Force reload the APP_KEY from .env into the current process
        // This is critical because the installation process continues in the same request
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();

        // Update the running config with the new APP_KEY and rebind encrypter
        if ($appKey = env('APP_KEY')) {
            Config::set('app.key', $appKey);

            // Decode base64 keys to raw bytes as expected by Encrypter
            $normalizedKey = $appKey;
            if (str_starts_with($appKey, 'base64:')) {
                $normalizedKey = base64_decode(substr($appKey, 7));
            }

            app()->forgetInstance('encrypter');
            app()->singleton('encrypter', function ($app) use ($normalizedKey) {
                return new Encrypter($normalizedKey, $app['config']['app.cipher']);
            });
        }
    }

    /**
     * Step: Setup database connection
     */
    protected function stepSetupDatabase(array $config): void
    {
        $dbConfig = $config['database'] ?? [];
        $connection = $dbConfig['connection'] ?? 'mysql';

        // Re-configure database connection
        Config::set('database.default', $connection);
        Config::set("database.connections.{$connection}.host", $dbConfig['host'] ?? '127.0.0.1');
        Config::set("database.connections.{$connection}.port", $dbConfig['port'] ?? 3306);
        Config::set("database.connections.{$connection}.database", $dbConfig['database'] ?? 'aero');
        Config::set("database.connections.{$connection}.username", $dbConfig['username'] ?? 'root');
        Config::set("database.connections.{$connection}.password", $dbConfig['password'] ?? '');

        // Purge and reconnect
        DB::purge($connection);
        DB::reconnect($connection);

        // Test connection
        DB::connection()->getPdo();
    }

    /**
     * Step: Run migrations
     * Uses direct migration runner with package discovery (same mechanism as aero:sync-migrations)
     */
    protected function stepRunMigrations(string $mode): void
    {
        $migrator = app('migrator');
        $repository = $migrator->getRepository();

        // Ensure the migrations table exists first
        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        // Track all paths that have been explicitly run (realpath() for Junction/symlink-safe comparison)
        $processedPaths = [];

        // STEP 1: Run aero-core migrations FIRST.
        // Core creates the foundational tables (users, roles, permissions, cache, jobs, modules, etc.)
        // and MUST run before anything else.
        $corePath = base_path('vendor/aero/core/database/migrations');
        if (File::isDirectory($corePath)) {
            $migrator->run($corePath);
            $processedPaths[] = realpath($corePath) ?: $corePath;
            Log::info("Ran core migrations from: {$corePath}");
        }

        if ($mode === 'saas') {
            // STEP 2 (SaaS only): Run platform migrations second.
            // Platform creates the SaaS-specific tables: tenants, domains, plans, subscriptions,
            // landlord_users, platform_settings, etc.
            // NOTE: Some platform migrations duplicate core ones (cache, jobs, modules) — those
            // migrations must have Schema::hasTable() guards to skip if already created by core.
            $platformPath = base_path('vendor/aero/platform/database/migrations');
            if (File::isDirectory($platformPath)) {
                $migrator->run($platformPath);
                $processedPaths[] = realpath($platformPath) ?: $platformPath;
                Log::info("Ran platform migrations from: {$platformPath}");
            }

            // In SaaS mode, STOP HERE for the central database.
            // Module-specific packages (aero-compliance, aero-hrm, aero-crm, etc.) are for
            // TENANT databases only and run during tenant provisioning — NOT on the central DB.
            Log::info('SaaS mode: skipping module package migrations for central database (tenant-only tables)');
        } else {
            // STEP 3 (Standalone only): Run all other discovered package migrations.
            // In standalone mode, everything goes in one database so all packages migrate here.
            $migrationPaths = $this->discoverMigrationPaths();
            $priorityPackages = ['core', 'platform'];
            foreach ($migrationPaths as $packageName => $path) {
                if (in_array($packageName, $priorityPackages)) {
                    continue; // Already run above
                }
                if (File::isDirectory($path)) {
                    $migrator->run($path);
                    $processedPaths[] = realpath($path) ?: $path;
                    Log::info("Ran migrations for package: {$packageName}");
                }
            }

            // Fallback: run any registered paths not yet processed (realpath-safe comparison)
            $registeredPaths = $migrator->paths();
            foreach ($registeredPaths as $path) {
                $normalizedPath = realpath($path) ?: $path;
                if (in_array($normalizedPath, $processedPaths)) {
                    continue;
                }
                if (File::isDirectory($path)) {
                    $migrator->run($path);
                    $processedPaths[] = $normalizedPath;
                }
            }
        }
    }

    /**
     * Discover migration paths from all installed Aero packages
     * Uses the same mechanism as ModuleDiscoveryService and aero:sync-migrations command
     *
     * @return array<string, string> Package name => migration path
     */
    protected function discoverMigrationPaths(): array
    {
        $migrationPaths = [];
        $vendorPrefix = 'aero';

        // 1. Discover packages installed via Composer (vendor/aero/*)
        $vendorPath = base_path('vendor/'.$vendorPrefix);
        if (File::exists($vendorPath)) {
            foreach (File::directories($vendorPath) as $packagePath) {
                $migrationsPath = $packagePath.'/database/migrations';
                if (File::exists($migrationsPath) && File::isDirectory($migrationsPath)) {
                    $files = File::files($migrationsPath);
                    if (count($files) > 0) {
                        $packageName = basename($packagePath);
                        $migrationPaths[$packageName] = $migrationsPath;
                    }
                }
            }
        }

        // 2. Discover runtime modules (modules/*)
        $runtimePath = base_path('modules');
        if (File::exists($runtimePath)) {
            foreach (File::directories($runtimePath) as $modulePath) {
                $migrationsPath = $modulePath.'/database/migrations';
                if (File::exists($migrationsPath) && File::isDirectory($migrationsPath)) {
                    $files = File::files($migrationsPath);
                    if (count($files) > 0) {
                        $moduleName = basename($modulePath);
                        $migrationPaths[$moduleName] = $migrationsPath;
                    }
                }
            }
        }

        return $migrationPaths;
    }

    /**
     * Step: Run seeders
     * Uses direct seeder instantiation instead of Artisan commands
     */
    protected function stepRunSeeders(string $mode): void
    {
        if ($mode === 'saas') {
            $seederClass = 'Aero\\Platform\\Database\\Seeders\\PlatformDatabaseSeeder';
            if (class_exists($seederClass)) {
                $seeder = app($seederClass);
                $seeder->run();
            }
        } else {
            // Run default database seeder
            $seederClass = 'Database\\Seeders\\DatabaseSeeder';
            if (class_exists($seederClass)) {
                $seeder = app($seederClass);
                $seeder->run();
            }
        }
    }

    /**
     * Step: Create roles and permissions
     * Syncs module hierarchy directly without using Artisan commands
     */
    protected function stepCreateRoles(string $mode): void
    {
        // Determine scope based on installation mode
        // SaaS mode: sync platform-scoped modules for central database
        // Standalone mode: sync all modules (scope='all')
        $scope = ($mode === 'saas') ? 'platform' : 'all';

        // Sync modules directly using ModuleDiscoveryService
        $this->syncModuleHierarchy($scope);
    }

    /**
     * Step: Create admin user
     */
    protected function stepCreateAdmin(array $config, string $mode): void
    {
        $adminConfig = $config['admin'] ?? [];

        if ($mode === 'saas') {
            // Create User with HRMAC Role model
            $userClass = 'Aero\\Platform\\Models\\User';
            $roleClass = 'Aero\\HRMAC\\Models\\Role';
        } else {
            // Create regular User
            $userClass = config('auth.providers.users.model', 'App\\Models\\User');
            $roleClass = 'Spatie\\Permission\\Models\\Role';
        }

        $email = $adminConfig['email'] ?? 'admin@example.com';

        $passwordHash = $adminConfig['password_hash'] ?? null;
        if (!$passwordHash && env('ADMIN_PASSWORD')) {
            $passwordHash = Hash::make(env('ADMIN_PASSWORD'));
        }
        if (!$passwordHash) {
            throw new \Exception('Admin password/hash not configured.');
        }

        // Handle soft-deleted users: check with trashed first, restore or create
        $user = $userClass::withTrashed()->where('email', $email)->first();

        if ($user) {
            // User exists (possibly soft-deleted), restore if needed
            if ($user->trashed()) {
                $user->restore();
            }
            // Update password and name in case they changed
            $user->update([
                'name' => ($adminConfig['first_name'] ?? 'Admin').' '.($adminConfig['last_name'] ?? 'User'),
                'user_name' => strtolower(str_replace(' ', '_', ($adminConfig['first_name'] ?? 'admin').'_'.($adminConfig['last_name'] ?? 'user'))),
                'password' => $passwordHash,
                'email_verified_at' => now(),
            ]);
        } else {
            // Create new user
            $user = $userClass::create([
                'email' => $email,
                'name' => ($adminConfig['first_name'] ?? 'Admin').' '.($adminConfig['last_name'] ?? 'User'),
                'user_name' => strtolower(str_replace(' ', '_', ($adminConfig['first_name'] ?? 'admin').'_'.($adminConfig['last_name'] ?? 'user'))),
                'password' => $passwordHash,
                'email_verified_at' => now(),
            ]);
        }

        // Create Super Administrator role with protected=true and assign to admin
        $guardName = $mode === 'saas' ? 'landlord' : 'web';

        $role = $roleClass::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => $guardName],
            [
                'description' => 'Full system access with all permissions',
                'is_protected' => true,
            ]
        );

        // Ensure is_protected is set even if role already exists
        if (! $role->is_protected) {
            $role->update(['is_protected' => true]);
        }

        $user->assignRole($role);
    }

    /**
     * Step: Save settings to database
     */
    protected function stepSaveSettings(array $config, string $mode): void
    {
        $settings = $config['settings'] ?? [];

        if (empty($settings)) {
            return; // No settings to save
        }

        if ($mode === 'saas') {
            // Save Platform settings - PlatformSetting uses single row model
            $settingsClass = 'Aero\\Platform\\Models\\PlatformSetting';

            // Get or create the platform settings record
            $platformSettings = $settingsClass::firstOrCreate(
                ['slug' => 'platform'],
                ['site_name' => $settings['site_name'] ?? config('app.name')]
            );

            // Map settings to model attributes
            $attributesToUpdate = [];
            $settingsMapping = [
                'site_name' => 'site_name',
                'company_name' => 'legal_name',
                'tagline' => 'tagline',
                'support_email' => 'support_email',
                'support_phone' => 'support_phone',
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMapping[$key])) {
                    $attributesToUpdate[$settingsMapping[$key]] = $value;
                }
            }

            if (! empty($attributesToUpdate)) {
                $platformSettings->update($attributesToUpdate);
            }
        } else {
            // Save System settings for standalone mode
            // SystemSetting uses single-row model similar to PlatformSetting
            $settingsClass = 'Aero\\Core\\Models\\SystemSetting';

            // Get or create the system settings record
            $systemSettings = $settingsClass::firstOrCreate(
                ['slug' => 'default'],
                ['company_name' => $settings['company_name'] ?? config('app.name')]
            );

            // Map settings to model attributes (column-based, not key-value)
            $attributesToUpdate = [];
            $settingsMapping = [
                'company_name' => 'company_name',
                'legal_name' => 'legal_name',
                'tagline' => 'tagline',
                'app_url' => 'website_url',
                'timezone' => 'timezone',
                'support_email' => 'support_email',
                'support_phone' => 'support_phone',
                'address' => 'address_line1',
                'city' => 'city',
                'state' => 'state',
                'postal_code' => 'postal_code',
                'country' => 'country',
            ];

            foreach ($settings as $key => $value) {
                if (isset($settingsMapping[$key])) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $attributesToUpdate[$settingsMapping[$key]] = $value;
                }
            }

            // Handle email settings - pass array directly since model has 'array' cast
            if (isset($settings['mail_driver']) || isset($settings['mail_host'])) {
                $attributesToUpdate['email_settings'] = [
                    'driver' => $settings['mail_driver'] ?? 'smtp',
                    'host' => $settings['mail_host'] ?? '',
                    'port' => $settings['mail_port'] ?? 587,
                    'username' => $settings['mail_username'] ?? '',
                    'password' => $settings['mail_password'] ?? '',
                    'encryption' => $settings['mail_encryption'] ?? 'tls',
                    'from_address' => $settings['mail_from_address'] ?? '',
                    'from_name' => $settings['mail_from_name'] ?? '',
                ];
            }

            if (! empty($attributesToUpdate)) {
                $systemSettings->update($attributesToUpdate);
            }
        }
    }

    /**
     * Step: Sync and install modules
     * Syncs module hierarchy directly without using Artisan commands
     */
    protected function stepInstallModules(array $config): void
    {
        $mode = $this->getMode();

        // Sync modules with appropriate scope
        // platform = SaaS central database, tenant = standalone mode (tenant context)
        $scope = $mode === 'saas' ? 'platform' : 'all';

        // Sync module hierarchy directly
        $this->syncModuleHierarchy($scope);

        // For standalone mode, activate licensed modules
        if ($mode !== 'saas') {
            $license = $config['license'] ?? [];
            $modules = $license['allowed_modules'] ?? [];

            foreach ($modules as $moduleCode) {
                try {
                    // Activate the module in the database
                    Module::where('code', $moduleCode)->update(['is_active' => true]);
                    Log::info("Activated module: {$moduleCode}");
                } catch (\Exception $e) {
                    Log::warning("Failed to activate module: {$moduleCode}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Step: Warm cache
     * Clears caches directly without using Artisan commands
     */
    protected function stepWarmCache(): void
    {
        // Clear all caches directly
        $this->clearConfigCache();
        $this->clearRouteCache();
        $this->clearViewCache();

        // Note: We don't cache in production during installation
        // because the app should be fully functional first
    }

    /**
     * Step: Finalize installation
     */
    protected function stepFinalize(): void
    {
        // Generate installation key
        $installationKey = Str::uuid()->toString();

        $this->persistConfig('installation', [
            'key' => $installationKey,
            'installed_at' => now()->toDateTimeString(),
            'version' => config('app.version', '1.0.0'),
            'mode' => $this->getMode(),
        ]);
    }

    /**
     * Create installation lock file
     *
     * Creates the unified lock file at storage/app/aeos.installed
     * This path is used by both aero-core and aero-platform packages.
     */
    protected function createLockFile(): void
    {
        $lockFile = storage_path(self::LOCK_FILE);
        $config = $this->getPersistedConfig('installation') ?? [];
        $mode = $this->getMode();

        $lockData = [
            'installed_at' => now()->toDateTimeString(),
            'version' => config('app.version', '1.0.0'),
            'mode' => $mode,
            'key' => $config['key'] ?? Str::uuid()->toString(),
        ];

        File::ensureDirectoryExists(dirname($lockFile));
        File::put($lockFile, json_encode($lockData, JSON_PRETTY_PRINT));

        // Also create mode file for reference
        $modeFile = storage_path('app/aeos.mode');
        File::put($modeFile, $mode);
    }

    /**
     * Show completion page
     */
    public function complete()
    {
        if (! $this->isInstalled()) {
            return redirect('/install');
        }

        $config = $this->getPersistedConfig();
        $mode = $this->getMode();

        // Capture what we need before clearing
        $adminEmail = $config['admin']['email'] ?? '';
        $appUrl = $config['settings']['app_url'] ?? config('app.url');
        $licensedModules = $config['license']['allowed_modules'] ?? [];
        $installationKey = $config['installation']['key'] ?? null;

        // Security: immediately purge config file (contains DB creds + hashed password).
        // Only non-sensitive summary data is passed to the view.
        $this->clearPersistedConfig();

        return Inertia::render('Installation/Complete', [
            'title' => 'Installation Complete',
            'mode' => $mode,
            'appUrl' => $appUrl,
            'adminEmail' => $adminEmail,
            // Password is intentionally not passed — user already knows it; never echo it back.
            'licensedModules' => $licensedModules,
            'installationKey' => $installationKey,
            // Real, verified destination URLs for the Complete-page quick actions.
            // Each falls back to appUrl when the named route does not exist (or is
            // tenant-scoped and cannot be resolved without params), so links never 404.
            'actions' => $this->resolveCompleteActions($appUrl),
        ]);
    }

    /**
     * Resolve quick-action URLs for the Complete page from named routes,
     * guarding each lookup and falling back to the app URL.
     */
    protected function resolveCompleteActions(string $appUrl): array
    {
        $resolve = function (array $names) use ($appUrl) {
            foreach ($names as $name) {
                try {
                    if (Route::has($name)) {
                        return route($name);
                    }
                } catch (\Throwable $e) {
                    // Route exists but needs params we don't have (e.g. tenant scope) — try next.
                }
            }

            return $appUrl;
        };

        // Each chain prefers the most specific real route, then degrades to a
        // resolvable sign-in route, then the app URL — so a card always points
        // somewhere real (e.g. on a SaaS central domain the tenant-scoped
        // dashboard/settings routes aren't registered, but `login` is).
        return [
            'app' => $appUrl,
            'dashboard' => $resolve(['dashboard', 'admin.dashboard', 'login']),
            'login' => $resolve(['login']),
            'settings' => $resolve(['settings', 'admin.settings', 'dashboard', 'login']),
        ];
    }

    /**
     * Show already installed page.
     * If the application is NOT yet installed, redirect to the first install step
     * so that an unknown /install/* URL doesn't get stuck on this page.
     */
    public function alreadyInstalled()
    {
        if (! $this->isInstalled()) {
            return redirect()->route('installation.index');
        }

        $lockFile = storage_path(self::LOCK_FILE);
        $lockData = [];

        if (File::exists($lockFile)) {
            $lockData = json_decode(File::get($lockFile), true) ?? [];
        }

        return Inertia::render('Installation/AlreadyInstalled', [
            'title' => 'Already Installed',
            'mode' => $lockData['mode'] ?? $this->getMode(),
            'appUrl' => config('app.url'),
            'installedAt' => $lockData['installed_at'] ?? null,
            'version' => $lockData['version'] ?? config('app.version'),
        ]);
    }

    /**
     * Cleanup failed installation (POST endpoint)
     */
    public function cleanup()
    {
        try {
            // Remove lock file
            $lockFile = storage_path(self::LOCK_FILE);
            if (File::exists($lockFile)) {
                File::delete($lockFile);
            }

            // Remove progress file (both old and new paths)
            $progressFile = base_path(self::PROGRESS_FILE);
            if (File::exists($progressFile)) {
                File::delete($progressFile);
            }

            $newProgressFile = storage_path('app/installation_progress.json');
            if (File::exists($newProgressFile)) {
                File::delete($newProgressFile);
            }

            // Clear installation cache
            Cache::forget('installation_in_progress');
            Cache::forget('installation_orchestrator_'.session()->getId());

            Log::info('Installation cleanup completed');

            return response()->json([
                'success' => true,
                'message' => 'Installation cleanup completed',
            ]);
        } catch (\Exception $e) {
            Log::error('Installation cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed installation
     */
    public function retry()
    {
        // Do NOT clear progress file or cache - continue from where we left off
        // The orchestrator will be retrieved from cache and continue execution

        return response()->json([
            'success' => true,
            'message' => 'Retry started from last step',
        ]);
    }

    /**
     * Update installation progress
     */
    protected function updateProgress(float $percentage, string $currentStep, string $status, ?string $error = null): void
    {
        $progressFile = base_path(self::PROGRESS_FILE);
        $mode = $this->getMode();

        $progress = [
            'percentage' => round($percentage, 1),
            'currentStep' => $currentStep,
            'status' => $status,
            'error' => $error,
            'steps' => $this->getProgressSteps($mode),
            'updated_at' => now()->toDateTimeString(),
        ];

        File::ensureDirectoryExists(dirname($progressFile));
        File::put($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get progress steps with labels
     */
    protected function getProgressSteps(string $mode): array
    {
        if ($mode === 'saas') {
            return [
                ['key' => 'config', 'label' => 'Writing configuration files...'],
                ['key' => 'database', 'label' => 'Setting up central database...'],
                ['key' => 'migrations', 'label' => 'Running migrations...'],
                ['key' => 'admin', 'label' => 'Creating Super Administrator...'],
                ['key' => 'modules', 'label' => 'Syncing platform modules...'],
                ['key' => 'settings', 'label' => 'Saving platform settings...'],
                ['key' => 'cache', 'label' => 'Clearing and warming cache...'],
                ['key' => 'finalize', 'label' => 'Finalizing installation...'],
            ];
        }

        return [
            ['key' => 'config', 'label' => 'Writing configuration files...'],
            ['key' => 'database', 'label' => 'Setting up database...'],
            ['key' => 'migrations', 'label' => 'Running migrations...'],
            ['key' => 'seeders', 'label' => 'Seeding initial data...'],
            ['key' => 'roles', 'label' => 'Creating roles & permissions...'],
            ['key' => 'admin', 'label' => 'Creating Super Administrator...'],
            ['key' => 'settings', 'label' => 'Saving system settings...'],
            ['key' => 'modules', 'label' => 'Syncing modules...'],
            ['key' => 'cache', 'label' => 'Clearing and warming cache...'],
            ['key' => 'finalize', 'label' => 'Finalizing installation...'],
        ];
    }

    /**
     * Perform requirements check
     */
    protected function performRequirementsCheck(): array
    {
        $php = [
            'name' => 'PHP Version',
            'required' => '8.2.0',
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
        ];

        $extensions = [
            ['name' => 'BCMath', 'required' => true, 'installed' => extension_loaded('bcmath')],
            ['name' => 'Ctype', 'required' => true, 'installed' => extension_loaded('ctype')],
            ['name' => 'cURL', 'required' => true, 'installed' => extension_loaded('curl')],
            ['name' => 'DOM', 'required' => true, 'installed' => extension_loaded('dom')],
            ['name' => 'Fileinfo', 'required' => true, 'installed' => extension_loaded('fileinfo')],
            ['name' => 'JSON', 'required' => true, 'installed' => extension_loaded('json')],
            ['name' => 'Mbstring', 'required' => true, 'installed' => extension_loaded('mbstring')],
            ['name' => 'OpenSSL', 'required' => true, 'installed' => extension_loaded('openssl')],
            ['name' => 'PDO', 'required' => true, 'installed' => extension_loaded('pdo')],
            ['name' => 'PDO MySQL', 'required' => false, 'installed' => extension_loaded('pdo_mysql')],
            ['name' => 'Tokenizer', 'required' => true, 'installed' => extension_loaded('tokenizer')],
            ['name' => 'XML', 'required' => true, 'installed' => extension_loaded('xml')],
            ['name' => 'GD', 'required' => false, 'installed' => extension_loaded('gd')],
            ['name' => 'Imagick', 'required' => false, 'installed' => extension_loaded('imagick')],
            ['name' => 'Redis', 'required' => false, 'installed' => extension_loaded('redis')],
            ['name' => 'Zip', 'required' => false, 'installed' => extension_loaded('zip')],
        ];

        $directories = [
            ['path' => 'storage/app', 'writable' => is_writable(storage_path('app'))],
            ['path' => 'storage/framework', 'writable' => is_writable(storage_path('framework'))],
            ['path' => 'storage/logs', 'writable' => is_writable(storage_path('logs'))],
            ['path' => 'bootstrap/cache', 'writable' => is_writable(base_path('bootstrap/cache'))],
            ['path' => '.env', 'writable' => is_writable(base_path('.env'))],
        ];

        $allExtensionsPassed = collect($extensions)
            ->filter(fn ($e) => $e['required'])
            ->every(fn ($e) => $e['installed']);

        $allDirectoriesPassed = collect($directories)
            ->every(fn ($d) => $d['writable']);

        return [
            'php' => $php,
            'extensions' => $extensions,
            'directories' => $directories,
            'allPassed' => $php['passed'] && $allExtensionsPassed && $allDirectoriesPassed,
        ];
    }

    /**
     * Get installed product modules (from composer packages)
     *
     * Returns only product/business modules, excluding foundation packages.
     * Loads full names and descriptions from each module's config/module.php file.
     *
     * Foundation packages (hidden from display):
     * - core: Core framework functionality
     * - platform: Multi-tenant platform functionality (SaaS mode)
     * - ui: Shared UI components and themes
     * - hrmac: Human Resource Management Accounting (accounting foundation for HRM)
     *
     * Product modules (visible to users):
     * - hrm, crm, finance, dms, compliance, pos, scm, project, ims, quality, etc.
     */
    protected function getInstalledModules(): array
    {
        $modules = [];
        $packagesPath = base_path('vendor/aero');

        // Foundation packages that should NOT be shown as products
        $foundationPackages = ['core', 'platform', 'ui', 'hrmac'];

        if (File::isDirectory($packagesPath)) {
            $packages = File::directories($packagesPath);

            foreach ($packages as $package) {
                $name = basename($package);

                // Only include actual product modules, not foundation packages
                if (! in_array($name, $foundationPackages)) {
                    $configPath = $package.'/config/module.php';

                    // Try to load module config for proper name and description
                    if (File::exists($configPath)) {
                        $config = require $configPath;

                        // Handle different config structures (some have 'module' key, others don't)
                        $moduleConfig = $config['module'] ?? $config;

                        $modules[] = [
                            'code' => $name,
                            'name' => $moduleConfig['name'] ?? ucfirst(str_replace('-', ' ', $name)),
                            'description' => $moduleConfig['description'] ?? null,
                        ];
                    } else {
                        // Fallback if no config file exists
                        $modules[] = [
                            'code' => $name,
                            'name' => ucfirst(str_replace('-', ' ', $name)),
                            'description' => null,
                        ];
                    }
                }
            }
        }

        return $modules;
    }

    /**
     * Get available license providers
     */
    protected function getAvailableProviders(): array
    {
        return [
            [
                'code' => 'aero',
                'name' => 'Aero Platform',
                'prefix' => 'AP-',
                'description' => 'Official Aero Enterprise licenses',
            ],
            [
                'code' => 'codecanyon',
                'name' => 'CodeCanyon',
                'prefix' => 'CC-',
                'description' => 'Envato marketplace licenses',
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'prefix' => 'EP-',
                'description' => 'Enterprise offline licenses',
            ],
        ];
    }

    /**
     * Get list of timezones
     */
    protected function getTimezones(): array
    {
        return \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    /**
     * Update .env value
     */
    protected function updateEnvValue(string $content, string $key, string $value): string
    {
        // Escape special characters in value
        if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"')) {
            $value = '"'.addslashes($value).'"';
        }

        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, "{$key}={$value}", $content);
        }

        return $content."\n{$key}={$value}";
    }

    /**
     * Clear config cache by removing cached files directly
     */
    protected function clearConfigCache(): void
    {
        $cachedConfigPath = base_path('bootstrap/cache/config.php');
        if (File::exists($cachedConfigPath)) {
            File::delete($cachedConfigPath);
        }
    }

    /**
     * Clear route cache by removing cached files directly
     */
    protected function clearRouteCache(): void
    {
        $cachedRoutesPath = base_path('bootstrap/cache/routes-v7.php');
        if (File::exists($cachedRoutesPath)) {
            File::delete($cachedRoutesPath);
        }

        // Also try the older route cache filename
        $oldCachedRoutesPath = base_path('bootstrap/cache/routes.php');
        if (File::exists($oldCachedRoutesPath)) {
            File::delete($oldCachedRoutesPath);
        }
    }

    /**
     * Clear view cache by removing compiled view files
     */
    protected function clearViewCache(): void
    {
        $viewCachePath = storage_path('framework/views');
        if (File::isDirectory($viewCachePath)) {
            $files = File::glob($viewCachePath.'/*.php');
            foreach ($files as $file) {
                File::delete($file);
            }
        }
    }

    /**
     * Sync module hierarchy directly using ModuleDiscoveryService
     * This replaces the Artisan command call with direct logic
     */
    protected function syncModuleHierarchy(string $scope): void
    {
        try {
            // Validate schema first
            $requiredTables = ['modules', 'sub_modules', 'module_components', 'module_component_actions'];
            foreach ($requiredTables as $table) {
                if (! Schema::hasTable($table)) {
                    Log::warning("Module sync skipped: table '{$table}' does not exist");

                    return;
                }
            }

            $moduleDiscovery = app(ModuleDiscoveryService::class);
            $modules = $moduleDiscovery->getModuleDefinitions();

            if ($modules->isEmpty()) {
                Log::info('No module definitions found in packages');

                return;
            }

            DB::beginTransaction();

            foreach ($modules as $moduleDef) {
                // Filter by scope
                $moduleScope = $moduleDef['scope'] ?? 'tenant';
                if ($scope !== 'all' && $moduleScope !== $scope) {
                    continue;
                }

                $this->syncModule($moduleDef);
            }

            DB::commit();
            Log::info("Module hierarchy synced successfully for scope: {$scope}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Module sync failed: '.$e->getMessage());
        }
    }

    /**
     * Sync a single module and its hierarchy
     */
    protected function syncModule(array $moduleDef): void
    {
        $module = Module::updateOrCreate(
            ['code' => $moduleDef['code']],
            [
                'name' => $moduleDef['name'],
                'scope' => $moduleDef['scope'] ?? 'tenant',
                'description' => $moduleDef['description'] ?? null,
                'icon' => $moduleDef['icon'] ?? null,
                'route_prefix' => $moduleDef['route_prefix'] ?? null,
                'category' => $moduleDef['category'] ?? 'core_system',
                'priority' => $moduleDef['priority'] ?? 100,
                'is_active' => $moduleDef['is_active'] ?? true,
                'is_core' => $moduleDef['is_core'] ?? false,
                'settings' => $moduleDef['settings'] ?? null,
                'version' => $moduleDef['version'] ?? '1.0.0',
                'min_plan' => $moduleDef['min_plan'] ?? null,
                'license_type' => $moduleDef['license_type'] ?? null,
                'dependencies' => $moduleDef['dependencies'] ?? null,
                'release_date' => $moduleDef['release_date'] ?? null,
            ]
        );

        // Sync submodules
        if (isset($moduleDef['submodules']) && is_array($moduleDef['submodules'])) {
            foreach ($moduleDef['submodules'] as $subModuleDef) {
                $subModule = SubModule::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'code' => $subModuleDef['code'],
                    ],
                    [
                        'name' => $subModuleDef['name'],
                        'description' => $subModuleDef['description'] ?? null,
                        'icon' => $subModuleDef['icon'] ?? null,
                        'route' => $subModuleDef['route'] ?? null,
                        'priority' => $subModuleDef['priority'] ?? 100,
                        'is_active' => $subModuleDef['is_active'] ?? true,
                    ]
                );

                // Sync components
                if (isset($subModuleDef['components']) && is_array($subModuleDef['components'])) {
                    foreach ($subModuleDef['components'] as $componentDef) {
                        $component = ModuleComponent::updateOrCreate(
                            [
                                'module_id' => $module->id,
                                'sub_module_id' => $subModule->id,
                                'code' => $componentDef['code'],
                            ],
                            [
                                'name' => $componentDef['name'],
                                'description' => $componentDef['description'] ?? null,
                                'type' => $componentDef['type'] ?? 'page',
                                'route' => $componentDef['route'] ?? null,
                                'priority' => $componentDef['priority'] ?? 100,
                                'is_active' => $componentDef['is_active'] ?? true,
                            ]
                        );

                        // Sync actions
                        if (isset($componentDef['actions']) && is_array($componentDef['actions'])) {
                            foreach ($componentDef['actions'] as $actionDef) {
                                ModuleComponentAction::updateOrCreate(
                                    [
                                        'module_component_id' => $component->id,
                                        'code' => $actionDef['code'],
                                    ],
                                    [
                                        'name' => $actionDef['name'],
                                        'description' => $actionDef['description'] ?? null,
                                        'is_active' => $actionDef['is_active'] ?? true,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
