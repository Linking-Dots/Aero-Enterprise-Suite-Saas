# Plan D — Standalone Host App & Add-on System

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the `aeos365-standalone` host app; build the in-admin add-on marketplace page (catalog, buy, install); implement the `AddonInstaller` that extracts a product ZIP into `modules/`, runs migrations, seeds permissions, and registers the module — all without touching Composer or vendor.

**Architecture:** The standalone host app is a Laravel skeleton in `aeos365-standalone/` within the monorepo, referencing `packages/` via path repository. It requires `aero/core` + an initial product only; `aero/platform` is never required. Add-ons are installed into `modules/{package-name}/` at runtime. The `RuntimeLoader` (already in `aero-core`) discovers and registers them. The `AddonInstaller` service handles: validate ZIP manifest → extract → run artisan migrate → seed permissions → enable in DB. The catalog is fetched from the SaaS license API with a bundled JSON fallback. Auto-download uses the signed URL from the license server; manual upload is the fallback.

**Tech Stack:** Laravel 11, `aero/core`, `aero/installation`, existing `RuntimeLoader`, Inertia.js + React.

**Prerequisites:** Plan A (bug fixes) and Plan C (license API) must be complete. Plans B and D can run in parallel.

---

## File Map

### New Files (host app)
- `aeos365-standalone/composer.json`
- `aeos365-standalone/config/product.php`
- `aeos365-standalone/config/license.php`
- `aeos365-standalone/.env.example`

### New Files (aero-core)
- `packages/aero-core/src/Services/AddonInstaller.php`
- `packages/aero-core/src/Services/AddonCatalogService.php`
- `packages/aero-core/src/Http/Controllers/Admin/AddonController.php`
- `packages/aero-core/database/migrations/XXXX_create_installed_addons_table.php`
- `packages/aero-core/src/Models/InstalledAddon.php`
- `packages/aero-core/tests/Feature/AddonInstallerTest.php`

### Modified Files
- `packages/aero-core/src/Services/RuntimeLoader.php` — ensure it loads from `modules/` correctly
- `packages/aero-core/routes/web.php` — add addon routes
- `packages/aero-core/src/AeroCoreServiceProvider.php` — register AddonInstaller, AddonCatalogService

---

## Task D1: Create the Standalone Host App

**Files:**
- Create: `aeos365-standalone/composer.json`
- Create: `aeos365-standalone/config/product.php`
- Create: `aeos365-standalone/config/license.php`
- Create: `aeos365-standalone/.env.example`

- [ ] **Step D1.1: Create the host app directory**

```bash
# From monorepo root: Aero-Enterprise-Suite-Saas/
mkdir aeos365-standalone
cd aeos365-standalone
```

- [ ] **Step D1.2: Write composer.json**

Create `aeos365-standalone/composer.json`:

```json
{
    "name": "aero/aeos365-standalone",
    "type": "project",
    "description": "AEOS365 Standalone Host Application",
    "license": "proprietary",
    "repositories": [
        {
            "type": "path",
            "url": "../packages/*",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10",
        "aero/core": "@dev",
        "aero/auth": "@dev",
        "aero/installation": "@dev",
        "aero/i18n": "@dev",
        "aero/notifications": "@dev",
        "aero/hrmac": "@dev",
        "aero/ui": "@dev",
        "aero/hrm": "@dev"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.24",
        "phpunit/phpunit": "^11.5"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": { "Tests\\": "tests/" }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Note: Initial bundle is HRM. To create a CRM-first standalone, replace `aero/hrm` with `aero/crm`. The initial product matches `config/product.php`.

- [ ] **Step D1.3: Write config/product.php**

Create `aeos365-standalone/config/product.php`:

```php
<?php

/*
|--------------------------------------------------------------------------
| Standalone Product Manifest
|--------------------------------------------------------------------------
|
| Identifies which product this standalone installation represents.
| The 'id' must match the product code registered on the license server.
|
| For a CRM-first bundle: set 'id' => 'crm', 'initial_module' => 'crm'.
|
*/

return [
    'id'             => env('PRODUCT_ID', 'hrm'),   // must match license server product code
    'name'           => env('PRODUCT_NAME', 'Aero HRM Suite'),
    'version'        => '1.0.0',
    'edition'        => 'standalone',
    'initial_module' => env('PRODUCT_ID', 'hrm'),   // the Composer-bundled module

    /*
     * All modules available to this installation.
     * Initial module is composer-installed. Add-ons come from modules/ at runtime.
     * This list is advisory — actual availability is determined by the licenses stored
     * in storage/app/aeos.license.* files.
     */
    'bundled_modules' => [env('PRODUCT_ID', 'hrm')],

    'license_server' => env('LICENSE_SERVER_URL', 'https://licenses.aerosuite.com'),
    'update_server'  => env('UPDATE_SERVER_URL',  'https://updates.aerosuite.com'),
];
```

- [ ] **Step D1.4: Write config/license.php**

Create `aeos365-standalone/config/license.php`:

```php
<?php

return [
    'server_url'             => env('LICENSE_SERVER_URL', 'https://licenses.aerosuite.com'),
    'check_ttl_seconds'      => env('LICENSE_CHECK_TTL', 86400),    // 24h
    'grace_period_seconds'   => env('LICENSE_GRACE_PERIOD', 259200), // 72h
    'checksum_salt'          => env('LICENSE_CHECKSUM_SALT', 'aero-license-salt'),
    'bypass'                 => env('LICENSE_BYPASS', false),
];
```

- [ ] **Step D1.5: Write .env.example**

Create `aeos365-standalone/.env.example`:

```
APP_NAME="Aero HRM Suite"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost
APP_VERSION=1.0.0

PRODUCT_ID=hrm
PRODUCT_NAME="Aero HRM Suite"

LICENSE_SERVER_URL=https://licenses.aerosuite.com
LICENSE_BYPASS=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aeos_standalone
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

- [ ] **Step D1.6: Bootstrap the host app (copy skeleton from aeos365)**

Copy from `aeos365/` the following directories/files into `aeos365-standalone/`:
- `app/` (Models, Http, Providers, Console)
- `bootstrap/`
- `public/`
- `resources/`
- `routes/` (keep web.php, api.php, console.php)
- `storage/` (empty, gitignored)
- `artisan`
- `phpunit.xml`

Remove from copied files:
- Any reference to `Aero\Platform` in `app/` or `routes/`
- Any `PlatformConfigurationStep` references in installation config

- [ ] **Step D1.7: Verify standalone boots**

```bash
cd aeos365-standalone
composer install
cp .env.example .env
php artisan key:generate
php artisan config:clear
php artisan tinker --execute="echo aero_mode();"
```

Expected output: `standalone`

- [ ] **Step D1.8: Commit**

```bash
git add aeos365-standalone/
git commit -m "feat(aeos365-standalone): create standalone host app — HRM initial bundle, no aero/platform"
```

---

## Task D2: InstalledAddon Model & Migration

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_07_000010_create_installed_addons_table.php`
- Create: `packages/aero-core/src/Models/InstalledAddon.php`

Tracks which add-ons are installed, their versions, and license keys.

- [ ] **Step D2.1: Write the migration**

```php
<?php
// packages/aero-core/database/migrations/2026_05_07_000010_create_installed_addons_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_addons', function (Blueprint $table) {
            $table->id();
            $table->string('module_code')->unique();    // e.g. 'crm'
            $table->string('product_code')->unique();   // e.g. 'crm' (matches license server)
            $table->string('name');                     // e.g. 'Aero CRM Suite'
            $table->string('version');                  // e.g. '1.0.0'
            $table->string('license_key');              // the license key used to install
            $table->string('install_path');             // modules/aero-crm
            $table->string('status')->default('active'); // active | disabled | needs_update
            $table->timestamp('installed_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_addons');
    }
};
```

- [ ] **Step D2.2: Write the InstalledAddon model**

```php
<?php
// packages/aero-core/src/Models/InstalledAddon.php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledAddon extends Model
{
    protected $fillable = [
        'module_code', 'product_code', 'name', 'version',
        'license_key', 'install_path', 'status',
        'installed_at', 'last_checked_at', 'metadata',
    ];

    protected $casts = [
        'installed_at'    => 'datetime',
        'last_checked_at' => 'datetime',
        'metadata'        => 'array',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function installFullPath(): string
    {
        return base_path($this->install_path);
    }
}
```

- [ ] **Step D2.3: Run migration in standalone**

```bash
cd aeos365-standalone
php artisan migrate --path=../packages/aero-core/database/migrations/2026_05_07_000010_create_installed_addons_table.php
```

- [ ] **Step D2.4: Commit**

```bash
git add packages/aero-core/database/migrations/2026_05_07_000010_create_installed_addons_table.php \
        packages/aero-core/src/Models/InstalledAddon.php
git commit -m "feat(aero-core): add installed_addons table — tracks runtime-installed product add-ons"
```

---

## Task D3: AddonCatalogService

**Files:**
- Create: `packages/aero-core/src/Services/AddonCatalogService.php`

Fetches the product catalog from the SaaS marketplace API with file-based cache fallback. Used by the add-on admin page to show what's available.

- [ ] **Step D3.1: Write the service**

```php
<?php
// packages/aero-core/src/Services/AddonCatalogService.php

namespace Aero\Core\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddonCatalogService
{
    private string $catalogCachePath;
    private int    $cacheTtlSeconds = 3600; // 1 hour

    public function __construct()
    {
        $this->catalogCachePath = storage_path('app/aeos.addon-catalog');
    }

    /**
     * Get the product catalog.
     * Tries live API first, falls back to file cache, then bundled fallback.
     */
    public function getCatalog(): array
    {
        // 1. Try live API
        $live = $this->fetchLive();
        if ($live !== null) {
            $this->writeCache($live);
            return $live;
        }

        // 2. File cache (stale but available)
        $cached = $this->readCache();
        if ($cached !== null) {
            return $cached;
        }

        // 3. Bundled fallback — always available, never stale-crashes
        return $this->bundledCatalog();
    }

    /**
     * Get only products NOT already installed (available to purchase/install).
     */
    public function getAvailableAddons(): array
    {
        $installed    = \Aero\Core\Models\InstalledAddon::pluck('product_code')->toArray();
        $initialModule = config('product.initial_module', config('product.id', 'hrm'));

        return array_filter(
            $this->getCatalog(),
            fn ($p) => ! in_array($p['code'], $installed, true)
                    && $p['code'] !== $initialModule // don't show the pre-installed product
        );
    }

    private function fetchLive(): ?array
    {
        $url = config('license.server_url') . '/api/marketplace/catalog';
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                return $response->json('products', []);
            }
        } catch (\Throwable $e) {
            Log::debug('AddonCatalog: live fetch failed', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function readCache(): ?array
    {
        if (! file_exists($this->catalogCachePath)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->catalogCachePath), true);
        if (! is_array($data) || ! isset($data['cached_at'])) {
            return null;
        }
        // Return stale cache even if expired — better than nothing
        return $data['products'] ?? null;
    }

    private function writeCache(array $products): void
    {
        file_put_contents($this->catalogCachePath, json_encode([
            'products'  => $products,
            'cached_at' => time(),
        ]));
    }

    /**
     * Minimal bundled catalog — always available even offline.
     * Update this array when adding new products to the platform.
     */
    private function bundledCatalog(): array
    {
        return [
            ['code' => 'hrm',     'module_code' => 'hrm',     'name' => 'HRM Suite',           'monthly_price' => 29.00, 'currency' => 'USD'],
            ['code' => 'crm',     'module_code' => 'crm',     'name' => 'CRM Suite',            'monthly_price' => 39.00, 'currency' => 'USD'],
            ['code' => 'project', 'module_code' => 'project', 'name' => 'Project Management',   'monthly_price' => 24.00, 'currency' => 'USD'],
            ['code' => 'finance', 'module_code' => 'finance', 'name' => 'Finance & Accounting',  'monthly_price' => 49.00, 'currency' => 'USD'],
        ];
    }
}
```

- [ ] **Step D3.2: Register in AeroCoreServiceProvider**

In `register()`:
```php
$this->app->singleton(\Aero\Core\Services\AddonCatalogService::class);
```

- [ ] **Step D3.3: Commit**

```bash
git add packages/aero-core/src/Services/AddonCatalogService.php \
        packages/aero-core/src/AeroCoreServiceProvider.php
git commit -m "feat(aero-core): AddonCatalogService — live catalog with file-cache + bundled fallback"
```

---

## Task D4: AddonInstaller Service

**Files:**
- Create: `packages/aero-core/src/Services/AddonInstaller.php`
- Create: `packages/aero-core/tests/Feature/AddonInstallerTest.php`

The core of the add-on system. Takes a validated ZIP (local path or downloaded), extracts it to `modules/`, validates the manifest, runs migrations, seeds permissions, records in `installed_addons`.

- [ ] **Step D4.1: Write failing tests**

Create `packages/aero-core/tests/Feature/AddonInstallerTest.php`:

```php
<?php
// packages/aero-core/tests/Feature/AddonInstallerTest.php

namespace Aero\Core\Tests\Feature;

use Aero\Core\Models\InstalledAddon;
use Aero\Core\Services\AddonInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class AddonInstallerTest extends TestCase
{
    use RefreshDatabase;

    private string $testZipPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testZipPath = storage_path('app/test-addon.zip');
        $this->buildTestAddonZip();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('modules/aero-test-addon'));
        if (file_exists($this->testZipPath)) {
            unlink($this->testZipPath);
        }
        parent::tearDown();
    }

    public function test_install_extracts_zip_to_modules_directory(): void
    {
        $installer = app(AddonInstaller::class);
        $installer->install($this->testZipPath, 'TEST-LICENSEKEY-00000000');

        $this->assertDirectoryExists(base_path('modules/aero-test-addon'));
        $this->assertFileExists(base_path('modules/aero-test-addon/config/module.php'));
    }

    public function test_install_records_in_installed_addons_table(): void
    {
        $installer = app(AddonInstaller::class);
        $installer->install($this->testZipPath, 'TEST-LICENSEKEY-00000000');

        $this->assertDatabaseHas('installed_addons', [
            'module_code'  => 'test-addon',
            'product_code' => 'test-addon',
            'status'       => 'active',
        ]);
    }

    public function test_install_fails_if_zip_has_no_module_php(): void
    {
        $badZip = storage_path('app/bad-addon.zip');
        $zip    = new ZipArchive();
        $zip->open($badZip, ZipArchive::CREATE);
        $zip->addFromString('readme.txt', 'no manifest here');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('module.php manifest not found');

        app(AddonInstaller::class)->install($badZip, 'SOMEKEY');
        unlink($badZip);
    }

    private function buildTestAddonZip(): void
    {
        $zip = new ZipArchive();
        $zip->open($this->testZipPath, ZipArchive::CREATE);

        $zip->addFromString('aero-test-addon/config/module.php', '<?php return [
            "code"     => "test-addon",
            "scope"    => "tenant",
            "name"     => "Test Addon",
            "version"  => "1.0.0",
            "priority" => 100,
            "submodules" => [],
        ];');
        $zip->addFromString('aero-test-addon/composer.json', json_encode([
            'name' => 'aero/test-addon', 'version' => '1.0.0',
        ]));

        $zip->close();
    }
}
```

- [ ] **Step D4.2: Run tests — expect failure**

```bash
php artisan test packages/aero-core/tests/Feature/AddonInstallerTest.php
```

Expected: `AddonInstaller class not found`

- [ ] **Step D4.3: Write AddonInstaller**

```php
<?php
// packages/aero-core/src/Services/AddonInstaller.php

namespace Aero\Core\Services;

use Aero\Core\Models\InstalledAddon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class AddonInstaller
{
    private string $modulesBasePath;

    public function __construct()
    {
        $this->modulesBasePath = base_path('modules');
    }

    /**
     * Install an add-on from a local ZIP file path.
     *
     * Steps:
     *   1. Validate ZIP has a module.php manifest
     *   2. Extract to modules/{package-dir}/
     *   3. Run package migrations
     *   4. Seed package permissions
     *   5. Record in installed_addons
     *
     * @param string $zipPath      Absolute path to the ZIP file
     * @param string $licenseKey   License key used for this installation
     * @throws \RuntimeException   If ZIP is invalid or install fails
     */
    public function install(string $zipPath, string $licenseKey): InstalledAddon
    {
        if (! file_exists($zipPath)) {
            throw new \RuntimeException("ZIP file not found: {$zipPath}");
        }

        // Step 1: Validate & read manifest from ZIP
        $manifest    = $this->readManifestFromZip($zipPath);
        $packageDir  = $this->detectPackageDirectory($zipPath);
        $installPath = "modules/{$packageDir}";
        $fullPath    = base_path($installPath);

        // Step 2: Guard against re-installing an existing addon
        if (InstalledAddon::where('module_code', $manifest['code'])->exists()) {
            throw new \RuntimeException("Add-on [{$manifest['code']}] is already installed. Use update instead.");
        }

        // Step 3: Extract to modules/
        $this->extract($zipPath, $this->modulesBasePath);
        Log::info("AddonInstaller: extracted {$packageDir} to modules/");

        // Step 4: Run migrations for the new module
        $migrationsPath = "{$fullPath}/database/migrations";
        if (is_dir($migrationsPath)) {
            Artisan::call('migrate', [
                '--path'  => $installPath . '/database/migrations',
                '--force' => true,
            ]);
            Log::info("AddonInstaller: ran migrations for {$packageDir}");
        }

        // Step 5: Seed permissions (if seeder exists)
        $seederClass = $this->detectSeederClass($fullPath, $manifest['code']);
        if ($seederClass !== null) {
            try {
                Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
                Log::info("AddonInstaller: ran seeder {$seederClass}");
            } catch (\Throwable $e) {
                Log::warning("AddonInstaller: seeder failed (non-fatal)", ['error' => $e->getMessage()]);
            }
        }

        // Step 6: Record in installed_addons
        $addon = InstalledAddon::create([
            'module_code'     => $manifest['code'],
            'product_code'    => $manifest['code'], // product code === module code for standalone
            'name'            => $manifest['name'],
            'version'         => $manifest['version'],
            'license_key'     => $licenseKey,
            'install_path'    => $installPath,
            'status'          => 'active',
            'installed_at'    => now(),
        ]);

        Log::info("AddonInstaller: add-on [{$manifest['code']}] installed successfully");

        return $addon;
    }

    /**
     * Read and validate the module.php manifest from inside the ZIP.
     * @throws \RuntimeException
     */
    private function readManifestFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot open ZIP file: {$zipPath}");
        }

        // Find module.php anywhere in the ZIP (typically at {package}/config/module.php)
        $manifestContent = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, 'config/module.php')) {
                $manifestContent = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($manifestContent === null) {
            throw new \RuntimeException('module.php manifest not found in ZIP. This does not appear to be a valid Aero add-on package.');
        }

        // Safely evaluate the PHP config (it's just a return array)
        $manifest = eval('?>' . $manifestContent);
        if (! is_array($manifest)) {
            throw new \RuntimeException('module.php manifest is not a valid PHP array.');
        }

        foreach (['code', 'name', 'version'] as $required) {
            if (empty($manifest[$required])) {
                throw new \RuntimeException("module.php is missing required key: [{$required}]");
            }
        }

        return $manifest;
    }

    /**
     * Detect the top-level package directory name inside the ZIP.
     */
    private function detectPackageDirectory(string $zipPath): string
    {
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $firstName = $zip->getNameIndex(0);
        $zip->close();

        // First entry is typically 'aero-crm/' — get the directory name
        return trim(explode('/', $firstName)[0]);
    }

    /**
     * Extract all files to the target directory.
     */
    private function extract(string $zipPath, string $targetDir): void
    {
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot extract ZIP: {$zipPath}");
        }

        $zip->extractTo($targetDir);
        $zip->close();
    }

    /**
     * Try to find a permission seeder class in the extracted package.
     */
    private function detectSeederClass(string $packagePath, string $moduleCode): ?string
    {
        $camelCode   = str_replace(' ', '', ucwords(str_replace('-', ' ', $moduleCode)));
        $candidates  = [
            "Aero\\{$camelCode}\\Database\\Seeders\\PermissionSeeder",
            "Aero\\{$camelCode}\\Database\\Seeders\\{$camelCode}PermissionSeeder",
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }
}
```

- [ ] **Step D4.4: Run tests — all should pass**

```bash
php artisan test packages/aero-core/tests/Feature/AddonInstallerTest.php
```

Expected: 3 tests PASS.

- [ ] **Step D4.5: Commit**

```bash
git add packages/aero-core/src/Services/AddonInstaller.php \
        packages/aero-core/tests/Feature/AddonInstallerTest.php
git commit -m "feat(aero-core): AddonInstaller — extract ZIP, run migrations, seed permissions, record installation"
```

---

## Task D5: AddonController & Routes

**Files:**
- Create: `packages/aero-core/src/Http/Controllers/Admin/AddonController.php`
- Modify: `packages/aero-core/routes/web.php`

The admin panel add-on page. Shows catalog, installed add-ons, handles license key entry and ZIP upload/auto-download.

- [ ] **Step D5.1: Write AddonController**

```php
<?php
// packages/aero-core/src/Http/Controllers/Admin/AddonController.php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Contracts\LicenseServiceInterface;
use Aero\Core\Models\InstalledAddon;
use Aero\Core\Services\AddonCatalogService;
use Aero\Core\Services\AddonInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AddonController extends Controller
{
    public function __construct(
        private readonly AddonCatalogService $catalog,
        private readonly AddonInstaller      $installer,
        private readonly LicenseServiceInterface $license,
    ) {}

    /**
     * GET /addons
     * Show installed add-ons and available catalog.
     */
    public function index(): Response
    {
        return Inertia::render('Addons/Index', [
            'installed' => InstalledAddon::orderBy('installed_at', 'desc')->get(),
            'available' => $this->catalog->getAvailableAddons(),
            'product'   => config('product'),
            'marketplace_url' => config('license.server_url') . '/marketplace',
        ]);
    }

    /**
     * POST /addons/install
     * Install an add-on. Accepts either a license key (triggers auto-download)
     * or a license key + uploaded ZIP file (manual install).
     */
    public function install(Request $request): RedirectResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_code'=> ['required', 'string'],
            'zip_file'    => ['nullable', 'file', 'mimes:zip', 'max:102400'], // 100MB max
        ]);

        $licenseKey  = strtoupper(trim($request->license_key));
        $productCode = $request->product_code;

        // Step 1: Validate license key against the license server
        $validation = $this->validateLicenseKey($licenseKey, $productCode);
        if (! $validation['valid']) {
            return back()->withErrors(['license_key' => $validation['message']]);
        }

        // Step 2: Obtain the ZIP — auto-download or manual upload
        $zipPath = null;
        try {
            if ($request->hasFile('zip_file')) {
                // Manual upload path
                $zipPath = $request->file('zip_file')->store('addon-uploads', 'local');
                $zipPath = storage_path("app/{$zipPath}");
            } else {
                // Auto-download path — request signed URL from license server
                $zipPath = $this->autoDownload($licenseKey, $productCode);
            }

            if ($zipPath === null) {
                return back()->withErrors([
                    'zip_file' => 'Auto-download failed. Please upload the ZIP file manually.',
                ]);
            }

            // Step 3: Install
            $addon = $this->installer->install($zipPath, $licenseKey);

            return redirect()->route('addons.index')
                ->with('success', "Add-on [{$addon->name}] installed successfully. Refresh to see it in the navigation.");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['zip_file' => $e->getMessage()]);
        } finally {
            // Clean up temp ZIP if it was auto-downloaded
            if ($zipPath && str_contains($zipPath, 'addon-downloads') && file_exists($zipPath)) {
                unlink($zipPath);
            }
        }
    }

    private function validateLicenseKey(string $key, string $productCode): array
    {
        $serverUrl  = config('license.server_url');
        $domainHash = hash('sha256', strtolower(request()->getHost()));

        try {
            $response = Http::timeout(10)->post("{$serverUrl}/api/license/validate", [
                'license_key' => $key,
                'product_id'  => $productCode,
                'domain_hash' => $domainHash,
            ]);

            if (! $response->successful()) {
                return ['valid' => false, 'message' => 'Could not reach license server. Try again shortly.'];
            }

            $status = $response->json('status');

            return match ($status) {
                'valid'   => ['valid' => true,  'message' => ''],
                'expired' => ['valid' => false, 'message' => 'This license key has expired. Please renew.'],
                default   => ['valid' => false, 'message' => 'Invalid license key. Please check your purchase email.'],
            };

        } catch (\Throwable $e) {
            Log::warning('AddonController: license validation network failure', ['error' => $e->getMessage()]);
            return ['valid' => false, 'message' => 'Could not reach license server. Please try manual ZIP install.'];
        }
    }

    private function autoDownload(string $licenseKey, string $productCode): ?string
    {
        $serverUrl = config('license.server_url');
        try {
            // Ask license server for a signed download URL
            $response = Http::timeout(15)->post("{$serverUrl}/api/license/download-url", [
                'license_key' => $licenseKey,
                'product_id'  => $productCode,
            ]);

            if (! $response->successful() || ! $response->json('download_url')) {
                return null;
            }

            $downloadUrl = $response->json('download_url');
            $zipPath     = storage_path('app/addon-downloads/' . $productCode . '-' . time() . '.zip');

            if (! is_dir(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            // Stream download to disk
            $fileResponse = Http::timeout(120)->sink($zipPath)->get($downloadUrl);

            return $fileResponse->successful() ? $zipPath : null;

        } catch (\Throwable $e) {
            Log::warning('AddonController: auto-download failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
```

- [ ] **Step D5.2: Register addon routes**

In `packages/aero-core/routes/web.php`, add inside the authenticated + installed route group:

```php
// Add-on management — standalone only
Route::middleware(['auth', 'module:core'])
    ->prefix('addons')
    ->name('addons.')
    ->group(function () {
        Route::get('/',       [\Aero\Core\Http\Controllers\Admin\AddonController::class, 'index'])->name('index');
        Route::post('/install', [\Aero\Core\Http\Controllers\Admin\AddonController::class, 'install'])->name('install');
    });
```

- [ ] **Step D5.3: Verify routes**

```bash
php artisan route:list | grep addons
```

Expected: `GET addons/` and `POST addons/install` registered.

- [ ] **Step D5.4: Commit**

```bash
git add packages/aero-core/src/Http/Controllers/Admin/AddonController.php \
        packages/aero-core/routes/web.php
git commit -m "feat(aero-core): AddonController — catalog index, license validation, auto-download + manual upload install flow"
```

---

## Task D6: Wire RuntimeLoader to Installed Add-ons

**Files:**
- Modify: `packages/aero-core/src/Services/RuntimeLoader.php`

Ensure RuntimeLoader discovers and registers modules from `modules/` correctly and that the `InstalledAddon` table is used to verify which modules are active.

- [ ] **Step D6.1: Verify RuntimeLoader already scans `modules/`**

```bash
grep -n "modules" packages/aero-core/src/Services/RuntimeLoader.php | head -20
```

Confirm it reads from `base_path('modules')`. If it does, no changes needed — move to D6.2.

If the `loadModules()` method does not check `InstalledAddon::where('status', 'active')`, add this guard: only load a module from `modules/` if it has a corresponding active record in `installed_addons` OR if `installed_addons` table doesn't exist (fresh install before migration).

- [ ] **Step D6.2: Add guard to RuntimeLoader**

In `packages/aero-core/src/Services/RuntimeLoader.php`, in the `loadModules()` method (or wherever modules are registered), add after discovering a module directory:

```php
// Only load if recorded as active in installed_addons
// (Skip check if table doesn't exist — pre-migration / test context)
if (\Illuminate\Support\Facades\Schema::hasTable('installed_addons')) {
    $isActive = \Aero\Core\Models\InstalledAddon::where('module_code', $moduleCode)
        ->where('status', 'active')
        ->exists();

    if (! $isActive) {
        continue; // Skip disabled or unregistered modules
    }
}
```

- [ ] **Step D6.3: Enable runtime loading in standalone app config**

In `aeos365-standalone/config/aero.php` (or wherever `aero.runtime_loading.enabled` is configured), ensure:

```php
'runtime_loading' => [
    'enabled'      => true,          // MUST be true for add-ons to load
    'modules_path' => base_path('modules'),
],
```

- [ ] **Step D6.4: Test end-to-end: install test addon, verify it loads**

```bash
cd aeos365-standalone

# 1. Create a test module directory manually
mkdir -p modules/aero-test/config
echo "<?php return ['code'=>'test','scope'=>'tenant','name'=>'Test','version'=>'1.0.0','priority'=>99,'submodules'=>[]];" \
    > modules/aero-test/config/module.php

# 2. Insert into installed_addons
php artisan tinker --execute="
\Aero\Core\Models\InstalledAddon::create([
    'module_code'  => 'test',
    'product_code' => 'test',
    'name'         => 'Test',
    'version'      => '1.0.0',
    'license_key'  => 'TEST-TESTTEST-TESTTEST-TESTTEST',
    'install_path' => 'modules/aero-test',
    'status'       => 'active',
    'installed_at' => now(),
]);
echo 'done';
"

# 3. Verify RuntimeLoader picks it up
php artisan tinker --execute="
\$loader = app(\Aero\Core\Services\RuntimeLoader::class);
\$modules = \$loader->loadModules();
var_dump(array_keys(\$modules));
"
```

Expected: `['test']` (or includes 'test' in the array).

- [ ] **Step D6.5: Commit**

```bash
git add packages/aero-core/src/Services/RuntimeLoader.php \
        aeos365-standalone/config/aero.php
git commit -m "feat(aero-core): RuntimeLoader guards against unregistered modules; standalone enables runtime loading"
```

---

## Task D7: Frontend — Add-ons Admin Page (React/Inertia)

**Files:**
- Create: `packages/aero-ui/resources/js/pages/Addons/Index.jsx`

The Inertia page shown at `/addons`. Lists installed add-ons with version/status, shows available catalog with "Buy" links and "Install" form.

- [ ] **Step D7.1: Write the Addons Index page**

Create `packages/aero-ui/resources/js/pages/Addons/Index.jsx`:

```jsx
import React, { useState } from 'react'
import { router, useForm } from '@inertiajs/react'
import AppLayout from '@/layouts/AppLayout'

export default function AddonsIndex({ installed, available, product, marketplace_url }) {
  const [showInstallForm, setShowInstallForm] = useState(null) // product code or null

  const { data, setData, post, processing, errors, reset } = useForm({
    license_key:  '',
    product_code: '',
    zip_file:     null,
  })

  function openInstallForm(productCode) {
    setData({ license_key: '', product_code: productCode, zip_file: null })
    setShowInstallForm(productCode)
  }

  function submitInstall(e) {
    e.preventDefault()
    post(route('addons.install'), {
      forceFormData: true,
      onSuccess: () => { reset(); setShowInstallForm(null) },
    })
  }

  return (
    <AppLayout title="Add-ons & Extensions">
      {/* Installed Add-ons */}
      <section className="mb-10">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Installed Add-ons
        </h2>
        {installed.length === 0 ? (
          <p className="text-gray-500">No add-ons installed yet. Browse available add-ons below.</p>
        ) : (
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {installed.map(addon => (
              <div key={addon.id}
                className="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                <div className="flex items-center justify-between">
                  <span className="font-medium text-gray-900 dark:text-white">{addon.name}</span>
                  <span className={`text-xs px-2 py-0.5 rounded-full ${
                    addon.status === 'active'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'
                  }`}>
                    {addon.status}
                  </span>
                </div>
                <p className="text-xs text-gray-500 mt-1">v{addon.version}</p>
              </div>
            ))}
          </div>
        )}
      </section>

      {/* Available Add-ons */}
      <section>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          Available Add-ons
        </h2>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {available.map(product => (
            <div key={product.code}
              className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
              <div className="p-4">
                <h3 className="font-semibold text-gray-900 dark:text-white">{product.name}</h3>
                <p className="text-sm text-gray-500 mt-1 line-clamp-2">{product.description}</p>
                <p className="text-sm font-medium text-blue-600 mt-2">
                  From ${product.monthly_price}/{product.currency}
                </p>
              </div>
              <div className="px-4 pb-4 flex gap-2">
                <a href={`${marketplace_url}/products/${product.code}`}
                  target="_blank"
                  rel="noreferrer"
                  className="flex-1 text-center text-sm px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                  View Details
                </a>
                <button
                  onClick={() => openInstallForm(product.code)}
                  className="flex-1 text-sm px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                  Install
                </button>
              </div>

              {/* Install form (inline, expands when "Install" is clicked) */}
              {showInstallForm === product.code && (
                <form onSubmit={submitInstall} className="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900">
                  <p className="text-xs text-gray-500 mb-3">
                    Enter your license key from your purchase email.
                    <a href={`${marketplace_url}/products/${product.code}`}
                      target="_blank" rel="noreferrer"
                      className="ml-1 text-blue-600 hover:underline">
                      Buy now →
                    </a>
                  </p>
                  <input
                    type="text"
                    placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX"
                    value={data.license_key}
                    onChange={e => setData('license_key', e.target.value.toUpperCase())}
                    className="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 mb-2 font-mono bg-white dark:bg-gray-800"
                    required
                  />
                  {errors.license_key && (
                    <p className="text-xs text-red-600 mb-2">{errors.license_key}</p>
                  )}
                  <details className="mb-3">
                    <summary className="text-xs text-gray-500 cursor-pointer hover:text-gray-700">
                      Upload ZIP manually (if auto-download fails)
                    </summary>
                    <input
                      type="file"
                      accept=".zip"
                      onChange={e => setData('zip_file', e.target.files[0])}
                      className="mt-2 text-sm w-full"
                    />
                    {errors.zip_file && (
                      <p className="text-xs text-red-600 mt-1">{errors.zip_file}</p>
                    )}
                  </details>
                  <div className="flex gap-2">
                    <button type="button"
                      onClick={() => setShowInstallForm(null)}
                      className="flex-1 text-sm px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 dark:border-gray-600 transition-colors">
                      Cancel
                    </button>
                    <button type="submit" disabled={processing}
                      className="flex-1 text-sm px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors">
                      {processing ? 'Installing…' : 'Install Add-on'}
                    </button>
                  </div>
                </form>
              )}
            </div>
          ))}
        </div>
      </section>
    </AppLayout>
  )
}
```

- [ ] **Step D7.2: Add the route to navigation**

In `packages/aero-core/config/module.php`, the `settings` submodule or a new `addons` submodule should include the addons link — but only shown in standalone mode. Add to the `settings` components array:

```php
[
    'code'   => 'addons',
    'name'   => 'Add-ons & Extensions',
    'type'   => 'page',
    'route'  => '/addons',
    'plan'   => 'standalone', // hint: only shown in standalone
    'actions'=> [
        ['code' => 'view',    'name' => 'View Add-ons'],
        ['code' => 'install', 'name' => 'Install Add-on'],
    ],
],
```

- [ ] **Step D7.3: Commit**

```bash
git add packages/aero-ui/resources/js/pages/Addons/Index.jsx \
        packages/aero-core/config/module.php
git commit -m "feat(aero-ui): Addons/Index page — catalog browsing, license key entry, auto-download + manual ZIP install"
```

---

## Task D8: Packaging Command for Standalone Distribution

**Files:**
- Modify: `packages/aero-core/src/Console/Commands/PackageProduct.php` (from Plan A, enhance for standalone host)

The packaging command runs from within `aeos365-standalone/` and produces the distributable ZIP.

- [ ] **Step D8.1: Verify the command works from standalone host**

```bash
cd aeos365-standalone
php artisan aero:package-product --output=../dist --no-verify
```

Expected: creates `../dist/hrm-v1.0.0-standalone.zip`

Verify ZIP contents:
- `vendor/` present, dev dependencies excluded
- `modules/` directory empty (add-ons not bundled — customers install via license key)
- `storage/app/` empty (no `aeos.mode`, `aeos.installed`, `aeos.license` — fresh install state)
- `config/product.php` present
- `.env` NOT present
- `packages/aero-platform/` NOT present

If any of these fail, update the `$excludeDirs` / `$excludeFiles` list in `PackageProduct.php`.

- [ ] **Step D8.2: Verify mode file is absent in the ZIP**

```bash
unzip -l ../dist/hrm-v1.0.0-standalone.zip | grep "aeos.mode"
```

Expected: no output (file should not be in the ZIP — customers get fresh install state).

Add `storage/app/aeos.*` to the exclude list in `PackageProduct::buildExcludes()` if needed.

- [ ] **Step D8.3: Commit**

```bash
git add packages/aero-core/src/Console/Commands/PackageProduct.php
git commit -m "feat(aero-core): PackageProduct excludes aeos.* runtime state files from standalone distribution"
```
