# Plan P-3 — Platform Dashboard & Analytics Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the platform admin command center: live MRR/ARR dashboard with system health, per-tenant quota management with overrides and enforcement settings, deep platform analytics (revenue, tenants, usage, performance), and product analytics (feature usage, cohorts, funnels, adoption).

**Architecture:** All code in `packages/aero-platform/src/{Models,Http,Services}/`. Models extend `Aero\Contracts\Models\CentralModel` (`protected $connection = 'central'`). Auth guard is `landlord`. Routes live in `packages/aero-platform/routes/admin.php` under the existing `landlord` middleware group. Inertia pages live in `packages/aero-ui/resources/js/Pages/Platform/Admin/`. HRMAC paths follow the 3-level format `{submodule-code}.{component-code}.{action-code}` taken from `packages/aero-platform/config/module.php`. All writes wrap in `DB::transaction()` and audit via `AuditServiceInterface`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11.

---

> **ARCH NOTE (locked):** MRR/ARR throughout this plan = plan subscription revenue + product subscription revenue. The `Plan` model defines pricing tier and resource limits ONLY; it does NOT grant module access. Module access is gated by `ProductSubscription` (canonical) — `SubscriptionModule` is deprecated. All revenue analytics must query both `subscriptions` (plan) and `product_subscriptions` (product) tables.

## Task 1 — Migrations

- [ ] Create migrations under `packages/aero-platform/database/migrations/`:
  - `2026_06_01_000001_create_platform_metrics_daily_table.php`
  - `2026_06_01_000002_create_tenant_quota_overrides_table.php`
  - `2026_06_01_000003_create_quota_enforcement_settings_table.php`
  - `2026_06_01_000004_create_feature_usage_events_table.php`
  - `2026_06_01_000005_create_funnel_definitions_table.php`

```php
// 2026_06_01_000001_create_platform_metrics_daily_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            // ARCH NOTE: mrr/arr are TOTALS = plan_mrr + product_mrr. Per locked architecture,
            // MRR/ARR = sum(subscriptions revenue) + sum(product_subscriptions revenue).
            $table->decimal('mrr', 14, 2)->default(0);
            $table->decimal('arr', 14, 2)->default(0);
            $table->decimal('plan_mrr', 14, 2)->default(0);
            $table->decimal('product_mrr', 14, 2)->default(0);
            $table->decimal('plan_arr', 14, 2)->default(0);
            $table->decimal('product_arr', 14, 2)->default(0);
            $table->unsignedInteger('new_tenants')->default(0);
            $table->unsignedInteger('churned_tenants')->default(0);
            $table->unsignedInteger('active_tenants')->default(0);
            $table->unsignedInteger('trial_tenants')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_metrics_daily');
    }
};
```

```php
// 2026_06_01_000002_create_tenant_quota_overrides_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_quota_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('resource', 64); // storage_gb, api_calls, users, modules
            $table->bigInteger('limit_value');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('set_by')->constrained('landlord_users');
            $table->timestamps();
            $table->unique(['tenant_id', 'resource']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_quota_overrides');
    }
};
```

```php
// 2026_06_01_000003_create_quota_enforcement_settings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('quota_enforcement_settings', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 64)->unique();
            $table->bigInteger('default_limit');
            $table->unsignedTinyInteger('warning_threshold_pct')->default(80);
            $table->unsignedTinyInteger('hard_limit_pct')->default(100);
            $table->string('action', 16)->default('warn'); // warn | throttle | block
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('quota_enforcement_settings');
    }
};
```

```php
// 2026_06_01_000004_create_feature_usage_events_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('feature_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('feature_code', 128);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['feature_code', 'occurred_at']);
            $table->index(['tenant_id', 'feature_code']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('feature_usage_events');
    }
};
```

```php
// 2026_06_01_000005_create_funnel_definitions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('funnel_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->json('steps'); // [{event, label}]
            $table->foreignId('created_by')->constrained('landlord_users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('funnel_definitions');
    }
};
```

## Task 2 — Models

- [ ] `packages/aero-platform/src/Models/PlatformMetricDaily.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlatformMetricDaily extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'platform_metrics_daily';

    protected $fillable = [
        'date', 'mrr', 'arr', 'plan_mrr', 'product_mrr', 'plan_arr', 'product_arr',
        'new_tenants', 'churned_tenants',
        'active_tenants', 'trial_tenants', 'total_revenue',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mrr' => 'decimal:2',
            'arr' => 'decimal:2',
            'plan_mrr' => 'decimal:2',
            'product_mrr' => 'decimal:2',
            'plan_arr' => 'decimal:2',
            'product_arr' => 'decimal:2',
            'total_revenue' => 'decimal:2',
        ];
    }

    public static function forDate(string $date): ?self
    {
        return static::where('date', $date)->first();
    }

    public static function range(string $from, string $to)
    {
        return static::whereBetween('date', [$from, $to])->orderBy('date')->get();
    }
}
```

- [ ] `packages/aero-platform/src/Models/TenantQuotaOverride.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantQuotaOverride extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'tenant_quota_overrides';

    protected $fillable = [
        'tenant_id', 'resource', 'limit_value',
        'reason', 'expires_at', 'set_by',
    ];

    protected function casts(): array
    {
        return [
            'limit_value' => 'integer',
            'expires_at'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'set_by');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
```

- [ ] `packages/aero-platform/src/Models/QuotaEnforcementSetting.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuotaEnforcementSetting extends CentralModel
{
    use HasFactory;

    public const ACTION_WARN = 'warn';
    public const ACTION_THROTTLE = 'throttle';
    public const ACTION_BLOCK = 'block';

    protected $connection = 'central';
    protected $table = 'quota_enforcement_settings';

    protected $fillable = [
        'resource', 'default_limit',
        'warning_threshold_pct', 'hard_limit_pct', 'action',
    ];

    protected function casts(): array
    {
        return [
            'default_limit' => 'integer',
            'warning_threshold_pct' => 'integer',
            'hard_limit_pct' => 'integer',
        ];
    }
}
```

- [ ] `packages/aero-platform/src/Models/FeatureUsageEvent.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageEvent extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'feature_usage_events';

    protected $fillable = ['tenant_id', 'feature_code', 'user_id', 'occurred_at'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

- [ ] `packages/aero-platform/src/Models/FunnelDefinition.php`

```php
<?php

namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunnelDefinition extends CentralModel
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'funnel_definitions';

    protected $fillable = ['name', 'steps', 'created_by'];

    protected function casts(): array
    {
        return ['steps' => 'array'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(LandlordUser::class, 'created_by');
    }
}
```

## Task 3 — HRMAC entries

- [ ] Add submodules to `packages/aero-platform/config/module.php` (under `'submodules'` array) — keep priority increasing from existing entries.

```php
// platform-dashboard already exists; ensure component "dashboard-overview" has 'view' action.

// Quota Management
[
    'code' => 'quota-management',
    'name' => 'Quota Management',
    'description' => 'Per-tenant quota overrides and enforcement settings',
    'icon' => 'AdjustmentsHorizontalIcon',
    'route' => '/quotas',
    'priority' => 30,
    'components' => [
        [
            'code' => 'quota-dashboard',
            'name' => 'Quota Dashboard',
            'route' => '/quotas',
            'actions' => [
                ['code' => 'view', 'name' => 'View Quotas'],
                ['code' => 'override', 'name' => 'Override Quota'],
                ['code' => 'dismiss-warnings', 'name' => 'Dismiss Warnings'],
            ],
        ],
        [
            'code' => 'quota-settings',
            'name' => 'Enforcement Settings',
            'route' => '/quotas/settings',
            'actions' => [
                ['code' => 'view', 'name' => 'View Settings'],
                ['code' => 'edit', 'name' => 'Edit Settings'],
            ],
        ],
        [
            'code' => 'quota-analytics',
            'name' => 'Quota Analytics',
            'route' => '/quotas/analytics',
            'actions' => [
                ['code' => 'view', 'name' => 'View Analytics'],
                ['code' => 'export', 'name' => 'Export Analytics'],
            ],
        ],
    ],
],

// Platform Analytics
[
    'code' => 'platform-analytics',
    'name' => 'Platform Analytics',
    'description' => 'Revenue, tenant and usage analytics',
    'icon' => 'ChartBarIcon',
    'route' => '/analytics',
    'priority' => 31,
    'components' => [
        [
            'code' => 'analytics-dashboard',
            'name' => 'Analytics Dashboard',
            'route' => '/analytics',
            'actions' => [['code' => 'view', 'name' => 'View Dashboard']],
        ],
        [
            'code' => 'revenue-reports',
            'name' => 'Revenue Reports',
            'route' => '/analytics/revenue',
            'actions' => [
                ['code' => 'view', 'name' => 'View Revenue'],
                ['code' => 'export', 'name' => 'Export Revenue'],
            ],
        ],
        [
            'code' => 'tenant-analytics',
            'name' => 'Tenant Analytics',
            'route' => '/analytics/tenants',
            'actions' => [['code' => 'view', 'name' => 'View Tenants']],
        ],
    ],
],

// Product Analytics
[
    'code' => 'product-analytics',
    'name' => 'Product Analytics',
    'description' => 'Feature usage, cohorts, funnels',
    'icon' => 'PresentationChartLineIcon',
    'route' => '/product-analytics',
    'priority' => 32,
    'components' => [
        [
            'code' => 'feature-usage',
            'name' => 'Feature Usage',
            'route' => '/product-analytics/features',
            'actions' => [
                ['code' => 'view', 'name' => 'View Feature Usage'],
                ['code' => 'export', 'name' => 'Export'],
            ],
        ],
        [
            'code' => 'cohort-analysis',
            'name' => 'Cohort Analysis',
            'route' => '/product-analytics/cohorts',
            'actions' => [
                ['code' => 'view', 'name' => 'View Cohorts'],
                ['code' => 'export', 'name' => 'Export'],
            ],
        ],
        [
            'code' => 'funnel-analysis',
            'name' => 'Funnel Analysis',
            'route' => '/product-analytics/funnels',
            'actions' => [
                ['code' => 'view', 'name' => 'View Funnels'],
                ['code' => 'manage', 'name' => 'Manage Funnels'],
            ],
        ],
        [
            'code' => 'adoption-metrics',
            'name' => 'Adoption Metrics',
            'route' => '/product-analytics/adoption',
            'actions' => [['code' => 'view', 'name' => 'View Adoption']],
        ],
    ],
],
```

## Task 4 — Services

- [ ] `packages/aero-platform/src/Services/PlatformDashboardService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformDashboardService
{
    public function stats(): array
    {
        return Cache::remember('platform:dashboard:stats', 60, function () {
            $today = Carbon::today()->toDateString();
            $latest = PlatformMetricDaily::latest('date')->first();
            $prev = PlatformMetricDaily::where('date', '<', $latest?->date ?? $today)
                ->orderByDesc('date')->first();

            $churnRate = $this->churnRate(30);

            // ARCH NOTE: MRR/ARR = plan_mrr + product_mrr per locked architecture.
            // Plan subscriptions and product subscriptions are independent revenue streams.
            return [
                'mrr'              => (float) ($latest->mrr ?? 0),
                'mrr_growth'       => $this->growth($latest?->mrr, $prev?->mrr),
                'plan_mrr'         => (float) ($latest->plan_mrr ?? 0),
                'product_mrr'      => (float) ($latest->product_mrr ?? 0),
                'arr'              => (float) ($latest->arr ?? 0),
                'plan_arr'         => (float) ($latest->plan_arr ?? 0),
                'product_arr'      => (float) ($latest->product_arr ?? 0),
                'active_tenants'   => (int) ($latest->active_tenants ?? Tenant::where('status', Tenant::STATUS_ACTIVE)->count()),
                'trial_tenants'    => (int) ($latest->trial_tenants ?? 0),
                'churned_tenants'  => (int) ($latest->churned_tenants ?? 0),
                'churn_rate_pct'   => $churnRate,
                'new_tenants_7d'   => Tenant::where('created_at', '>=', now()->subDays(7))->count(),
                'total_revenue'    => (float) ($latest->total_revenue ?? 0),
            ];
        });
    }

    public function recentTenants(int $limit = 10): array
    {
        return Tenant::query()
            ->select(['id', 'name', 'subdomain', 'status', 'plan_id', 'created_at'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function systemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'queue'    => $this->checkQueue(),
            'storage'  => $this->checkStorage(),
        ];
    }

    private function growth(?float $current, ?float $previous): float
    {
        if (! $previous || $previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function churnRate(int $days): float
    {
        $start = now()->subDays($days)->toDateString();
        $churned = (int) PlatformMetricDaily::where('date', '>=', $start)->sum('churned_tenants');
        $active  = max(1, (int) (PlatformMetricDaily::latest('date')->value('active_tenants') ?? 1));
        return round(($churned / $active) * 100, 2);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection('central')->select('select 1');
            return ['status' => 'ok', 'latency_ms' => round((microtime(true) - $start) * 1000, 2)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('platform:health:check', 1, 5);
            return ['status' => Cache::get('platform:health:check') === 1 ? 'ok' : 'error'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::connection('central')->table('jobs')->count();
            $failed  = DB::connection('central')->table('failed_jobs')->count();
            return ['status' => $failed > 50 ? 'warn' : 'ok', 'pending' => $pending, 'failed' => $failed];
        } catch (\Throwable $e) {
            return ['status' => 'unknown'];
        }
    }

    private function checkStorage(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (! $free || ! $total) {
            return ['status' => 'unknown'];
        }
        $usedPct = round((($total - $free) / $total) * 100, 1);
        return [
            'status'   => $usedPct > 90 ? 'warn' : 'ok',
            'used_pct' => $usedPct,
            'free_gb'  => round($free / 1024 / 1024 / 1024, 2),
        ];
    }
}
```

- [ ] `packages/aero-platform/src/Services/QuotaAdminService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\QuotaEnforcementSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantQuotaOverride;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotaAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function listOverrides(array $filters = [])
    {
        return TenantQuotaOverride::query()
            ->with(['tenant:id,name,subdomain', 'setter:id,name,email'])
            ->when($filters['resource'] ?? null, fn ($q, $r) => $q->where('resource', $r))
            ->when($filters['tenant_id'] ?? null, fn ($q, $t) => $q->where('tenant_id', $t))
            ->latest('id')->paginate(25)->withQueryString();
    }

    public function setOverride(Tenant $tenant, string $resource, int $limit, ?string $reason, ?string $expiresAt): TenantQuotaOverride
    {
        return DB::transaction(function () use ($tenant, $resource, $limit, $reason, $expiresAt) {
            $current = $this->currentUsage($tenant, $resource);
            if ($limit < $current) {
                abort(422, "Limit ($limit) cannot be below current usage ($current).");
            }

            $override = TenantQuotaOverride::updateOrCreate(
                ['tenant_id' => $tenant->id, 'resource' => $resource],
                [
                    'limit_value' => $limit,
                    'reason'      => $reason,
                    'expires_at'  => $expiresAt,
                    'set_by'      => Auth::guard('landlord')->id(),
                ]
            );

            $this->audit->log(
                event: 'platform.quota.override.set',
                action: 'override',
                subject: $override,
                description: "Quota override for tenant {$tenant->name} resource={$resource} limit={$limit}",
            );

            return $override;
        });
    }

    public function removeOverride(Tenant $tenant, string $resource): void
    {
        DB::transaction(function () use ($tenant, $resource) {
            $override = TenantQuotaOverride::where('tenant_id', $tenant->id)
                ->where('resource', $resource)
                ->firstOrFail();

            $this->audit->log(
                event: 'platform.quota.override.removed',
                action: 'override',
                subject: $override,
                description: "Removed quota override for tenant {$tenant->name} resource={$resource}",
            );

            $override->delete();
        });
    }

    public function updateSettings(string $resource, array $data): QuotaEnforcementSetting
    {
        return DB::transaction(function () use ($resource, $data) {
            $setting = QuotaEnforcementSetting::updateOrCreate(
                ['resource' => $resource],
                [
                    'default_limit'         => $data['default_limit'],
                    'warning_threshold_pct' => $data['warning_threshold_pct'] ?? 80,
                    'hard_limit_pct'        => $data['hard_limit_pct'] ?? 100,
                    'action'                => $data['action'] ?? QuotaEnforcementSetting::ACTION_WARN,
                ]
            );

            $this->audit->log(
                event: 'platform.quota.settings.updated',
                action: 'edit',
                subject: $setting,
                description: "Quota enforcement settings updated for resource={$resource}",
            );

            return $setting;
        });
    }

    public function analytics(): array
    {
        return [
            'overrides_count' => TenantQuotaOverride::count(),
            'by_resource' => TenantQuotaOverride::query()
                ->select('resource', DB::raw('count(*) as count'))
                ->groupBy('resource')->pluck('count', 'resource')->all(),
            'expiring_soon' => TenantQuotaOverride::where('expires_at', '<=', now()->addDays(7))
                ->where('expires_at', '>=', now())->count(),
        ];
    }

    private function currentUsage(Tenant $tenant, string $resource): int
    {
        return (int) DB::connection('central')->table('tenant_stats')
            ->where('tenant_id', $tenant->id)
            ->latest('date')
            ->value(match ($resource) {
                'storage_gb' => 'storage_mb',
                'api_calls'  => 'api_requests',
                'users'      => 'active_users',
                default      => 'storage_mb',
            }) ?? 0;
    }
}
```

- [ ] `packages/aero-platform/src/Services/PlatformAnalyticsService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    public function revenue(string $from, string $to, string $bucket = 'day'): array
    {
        $rows = PlatformMetricDaily::range($from, $to);

        $trend = $rows->groupBy(fn ($r) => $this->bucketKey($r->date, $bucket))
            ->map(fn ($group, $key) => [
                'period'  => $key,
                'mrr'     => (float) $group->avg('mrr'),
                'revenue' => (float) $group->sum('total_revenue'),
            ])->values()->all();

        // ARCH NOTE: Plan MRR (plan subscription revenue only).
        $byPlan = DB::connection('central')->table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->select('plans.name', DB::raw('count(*) as tenants'), DB::raw('sum(plans.price_monthly) as mrr'))
            ->groupBy('plans.name')->get()->toArray();

        // ARCH NOTE: Product MRR is independent — sum product_subscriptions.price_monthly per product.
        $byProduct = DB::connection('central')->table('product_subscriptions')
            ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
            ->where('product_subscriptions.status', 'active')
            ->select('products.name', DB::raw('count(*) as tenants'), DB::raw('sum(product_subscriptions.price_monthly) as mrr'))
            ->groupBy('products.name')->get()->toArray();

        return [
            'trend'      => $trend,
            'by_plan'    => $byPlan,
            'by_product' => $byProduct,
            'churn'   => [
                'churned' => (int) $rows->sum('churned_tenants'),
                'new'     => (int) $rows->sum('new_tenants'),
            ],
        ];
    }

    public function tenantAnalytics(string $from, string $to): array
    {
        $rows = PlatformMetricDaily::range($from, $to);

        return [
            'signup_trend' => $rows->map(fn ($r) => [
                'date'         => $r->date->toDateString(),
                'new_tenants'  => $r->new_tenants,
                'churned'      => $r->churned_tenants,
            ])->all(),
            'plan_distribution' => Tenant::query()
                ->select('plan_id', DB::raw('count(*) as count'))
                ->groupBy('plan_id')->with('plan:id,name')->get()->toArray(),
            'retention' => $this->retentionMatrix(),
        ];
    }

    public function usageAnalytics(): array
    {
        return [
            'top_tenants_by_storage' => DB::connection('central')->table('tenant_stats')
                ->select('tenant_id', DB::raw('max(storage_mb) as storage'))
                ->groupBy('tenant_id')
                ->orderByDesc('storage')->limit(10)->get()->toArray(),
            'top_tenants_by_api' => DB::connection('central')->table('tenant_stats')
                ->select('tenant_id', DB::raw('sum(api_requests) as api'))
                ->where('date', '>=', now()->subDays(30)->toDateString())
                ->groupBy('tenant_id')
                ->orderByDesc('api')->limit(10)->get()->toArray(),
        ];
    }

    private function bucketKey(Carbon $date, string $bucket): string
    {
        return match ($bucket) {
            'week'  => $date->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    private function retentionMatrix(): array
    {
        // Simplified cohort retention by signup month over 6 months
        $cohorts = DB::connection('central')->table('tenants')
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as cohort"), DB::raw('count(*) as size'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('cohort')->orderBy('cohort')->get();

        return $cohorts->map(fn ($c) => [
            'cohort' => $c->cohort,
            'size'   => (int) $c->size,
        ])->all();
    }
}
```

- [ ] `packages/aero-platform/src/Services/ProductAnalyticsService.php`

```php
<?php

namespace Aero\Platform\Services;

use Aero\Platform\Models\FeatureUsageEvent;
use Aero\Platform\Models\FunnelDefinition;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ProductAnalyticsService
{
    public function featureUsage(int $days = 30): array
    {
        $since = now()->subDays($days);

        $rows = FeatureUsageEvent::query()
            ->where('occurred_at', '>=', $since)
            ->select('feature_code',
                DB::raw('count(*) as events'),
                DB::raw('count(distinct tenant_id) as tenants'))
            ->groupBy('feature_code')->get();

        $activeTenants = max(1, Tenant::where('status', Tenant::STATUS_ACTIVE)->count());

        return $rows->map(fn ($r) => [
            'feature_code' => $r->feature_code,
            'events'       => (int) $r->events,
            'tenants'      => (int) $r->tenants,
            'adoption_pct' => round(($r->tenants / $activeTenants) * 100, 2),
        ])->sortByDesc('adoption_pct')->values()->all();
    }

    public function cohortRetention(int $months = 6): array
    {
        $cohorts = DB::connection('central')->table('tenants')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as cohort"),
                DB::raw('count(*) as size')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('cohort')->orderBy('cohort')->get();

        $matrix = [];
        foreach ($cohorts as $c) {
            $row = ['cohort' => $c->cohort, 'size' => (int) $c->size, 'months' => []];
            for ($m = 0; $m < $months; $m++) {
                $end = \Carbon\Carbon::parse($c->cohort.'-01')->endOfMonth()->addMonths($m);
                $retained = DB::connection('central')->table('tenants')
                    ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$c->cohort])
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->where(function ($q) use ($end) {
                        $q->whereNull('deleted_at')->orWhere('deleted_at', '>', $end);
                    })
                    ->count();
                $row['months'][] = [
                    'month'    => $m,
                    'retained' => $retained,
                    'pct'      => $c->size > 0 ? round(($retained / $c->size) * 100, 2) : 0,
                ];
            }
            $matrix[] = $row;
        }
        return $matrix;
    }

    public function funnelAnalysis(FunnelDefinition $funnel, int $days = 30): array
    {
        $since = now()->subDays($days);
        $steps = [];
        $prevTenants = null;

        foreach ($funnel->steps as $idx => $step) {
            $event = $step['event'];
            $tenants = FeatureUsageEvent::where('feature_code', $event)
                ->where('occurred_at', '>=', $since)
                ->distinct('tenant_id')->count('tenant_id');

            $conversion = $prevTenants ? round(($tenants / max(1, $prevTenants)) * 100, 2) : 100.0;
            $steps[] = [
                'order'        => $idx,
                'label'        => $step['label'] ?? $event,
                'event'        => $event,
                'tenants'      => $tenants,
                'conversion_pct' => $conversion,
            ];
            $prevTenants = $tenants;
        }

        return ['funnel' => $funnel->name, 'steps' => $steps];
    }

    public function adoptionMetrics(): array
    {
        $activeTenants = max(1, Tenant::where('status', Tenant::STATUS_ACTIVE)->count());
        $usingAny30d = FeatureUsageEvent::where('occurred_at', '>=', now()->subDays(30))
            ->distinct('tenant_id')->count('tenant_id');

        return [
            'active_tenants'     => $activeTenants,
            'dau_30d'            => $usingAny30d,
            'adoption_pct'       => round(($usingAny30d / $activeTenants) * 100, 2),
            'top_features'       => $this->featureUsage(30),
        ];
    }
}
```

## Task 5 — Controllers

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/DashboardController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformDashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private PlatformDashboardService $svc) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Dashboard/Index', [
            'stats'         => $this->svc->stats(),
            'recentTenants' => $this->svc->recentTenants(),
            'systemHealth'  => $this->svc->systemHealth(),
        ]);
    }

    public function stats()
    {
        return response()->json($this->svc->stats());
    }

    public function systemHealth()
    {
        return response()->json($this->svc->systemHealth());
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/QuotaController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\QuotaEnforcementSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\QuotaAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotaController extends Controller
{
    public function __construct(private QuotaAdminService $svc) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Quotas/Index', [
            'overrides' => $this->svc->listOverrides($request->only(['resource', 'tenant_id'])),
            'analytics' => $this->svc->analytics(),
            'filters'   => $request->only(['resource', 'tenant_id']),
        ]);
    }

    public function override(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'resource'    => ['required', 'string', 'max:64'],
            'limit_value' => ['required', 'integer', 'min:0'],
            'reason'      => ['nullable', 'string', 'max:1000'],
            'expires_at'  => ['nullable', 'date', 'after:now'],
        ]);

        $override = $this->svc->setOverride(
            $tenant, $data['resource'], $data['limit_value'],
            $data['reason'] ?? null, $data['expires_at'] ?? null
        );

        return back()->with('success', 'Override saved.');
    }

    public function removeOverride(Tenant $tenant, string $resource)
    {
        $this->svc->removeOverride($tenant, $resource);
        return back()->with('success', 'Override removed.');
    }

    public function settings()
    {
        return Inertia::render('Platform/Admin/Quotas/Settings', [
            'settings' => QuotaEnforcementSetting::orderBy('resource')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'resource'              => ['required', 'string', 'max:64'],
            'default_limit'         => ['required', 'integer', 'min:0'],
            'warning_threshold_pct' => ['nullable', 'integer', 'between:1,100'],
            'hard_limit_pct'        => ['nullable', 'integer', 'between:1,200'],
            'action'                => ['required', 'in:warn,throttle,block'],
        ]);

        $this->svc->updateSettings($data['resource'], $data);
        return back()->with('success', 'Settings updated.');
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/AnalyticsController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(private PlatformAnalyticsService $svc) {}

    public function dashboard(Request $request)
    {
        [$from, $to] = $this->range($request);
        return Inertia::render('Platform/Admin/Analytics/Index', [
            'revenue' => $this->svc->revenue($from, $to),
            'tenants' => $this->svc->tenantAnalytics($from, $to),
            'range'   => compact('from', 'to'),
        ]);
    }

    public function revenue(Request $request)
    {
        [$from, $to] = $this->range($request);
        $bucket = $request->input('bucket', 'day');
        return Inertia::render('Platform/Admin/Analytics/Revenue', [
            'data'   => $this->svc->revenue($from, $to, $bucket),
            'range'  => compact('from', 'to'),
            'bucket' => $bucket,
        ]);
    }

    public function tenants(Request $request)
    {
        [$from, $to] = $this->range($request);
        return Inertia::render('Platform/Admin/Analytics/Tenants', [
            'data'  => $this->svc->tenantAnalytics($from, $to),
            'range' => compact('from', 'to'),
        ]);
    }

    public function usage()
    {
        return Inertia::render('Platform/Admin/Analytics/Usage', [
            'data' => $this->svc->usageAnalytics(),
        ]);
    }

    private function range(Request $request): array
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to   = $request->input('to', now()->toDateString());
        return [$from, $to];
    }
}
```

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/ProductAnalyticsController.php`

```php
<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\FunnelDefinition;
use Aero\Platform\Services\ProductAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductAnalyticsController extends Controller
{
    public function __construct(
        private ProductAnalyticsService $svc,
        private AuditServiceInterface $audit,
    ) {}

    public function featureUsage(Request $request)
    {
        $days = (int) $request->input('days', 30);
        return Inertia::render('Platform/Admin/ProductAnalytics/Features', [
            'rows' => $this->svc->featureUsage($days),
            'days' => $days,
        ]);
    }

    public function cohorts(Request $request)
    {
        $months = (int) $request->input('months', 6);
        return Inertia::render('Platform/Admin/ProductAnalytics/Cohorts', [
            'matrix' => $this->svc->cohortRetention($months),
            'months' => $months,
        ]);
    }

    public function funnels(Request $request)
    {
        $funnels = FunnelDefinition::orderByDesc('id')->get();
        $selected = $request->input('funnel_id')
            ? $funnels->firstWhere('id', (int) $request->input('funnel_id'))
            : $funnels->first();

        return Inertia::render('Platform/Admin/ProductAnalytics/Funnels', [
            'funnels'  => $funnels,
            'selected' => $selected,
            'analysis' => $selected ? $this->svc->funnelAnalysis($selected) : null,
        ]);
    }

    public function storeFunnel(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:160'],
            'steps'          => ['required', 'array', 'min:2'],
            'steps.*.event'  => ['required', 'string', 'max:128'],
            'steps.*.label'  => ['nullable', 'string', 'max:160'],
        ]);

        return DB::transaction(function () use ($data) {
            $funnel = FunnelDefinition::create([
                'name'       => $data['name'],
                'steps'      => $data['steps'],
                'created_by' => Auth::guard('landlord')->id(),
            ]);

            $this->audit->log(
                event: 'platform.funnel.created',
                action: 'manage',
                subject: $funnel,
                description: "Funnel created: {$funnel->name}",
            );

            return back()->with('success', 'Funnel saved.');
        });
    }

    public function adoption()
    {
        return Inertia::render('Platform/Admin/ProductAnalytics/Adoption', [
            'data' => $this->svc->adoptionMetrics(),
        ]);
    }
}
```

## Task 6 — Routes

- [ ] Append to `packages/aero-platform/routes/admin.php` inside the existing landlord-guarded group:

```php
use Aero\Platform\Http\Controllers\Admin\AnalyticsController;
use Aero\Platform\Http\Controllers\Admin\DashboardController;
use Aero\Platform\Http\Controllers\Admin\ProductAnalyticsController;
use Aero\Platform\Http\Controllers\Admin\QuotaController;

// Dashboard
Route::middleware('hrmac:platform-dashboard.dashboard-overview.view')->group(function () {
    Route::get('/dashboard',         [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/stats',   [DashboardController::class, 'stats'])->name('admin.dashboard.stats');
    Route::get('/dashboard/health',  [DashboardController::class, 'systemHealth'])->name('admin.dashboard.health');
});

// Quota Management
Route::prefix('quotas')->name('admin.quotas.')->group(function () {
    Route::middleware('hrmac:quota-management.quota-dashboard.view')
        ->get('/', [QuotaController::class, 'index'])->name('index');

    Route::middleware('hrmac:quota-management.quota-dashboard.override')->group(function () {
        Route::post('{tenant}/override', [QuotaController::class, 'override'])->name('override');
        Route::delete('{tenant}/override/{resource}', [QuotaController::class, 'removeOverride'])->name('override.remove');
    });

    Route::middleware('hrmac:quota-management.quota-settings.view')
        ->get('/settings', [QuotaController::class, 'settings'])->name('settings');
    Route::middleware('hrmac:quota-management.quota-settings.edit')
        ->put('/settings', [QuotaController::class, 'updateSettings'])->name('settings.update');
});

// Platform Analytics
Route::prefix('analytics')->name('admin.analytics.')->group(function () {
    Route::middleware('hrmac:platform-analytics.analytics-dashboard.view')
        ->get('/', [AnalyticsController::class, 'dashboard'])->name('index');
    Route::middleware('hrmac:platform-analytics.revenue-reports.view')
        ->get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
    Route::middleware('hrmac:platform-analytics.tenant-analytics.view')
        ->get('/tenants', [AnalyticsController::class, 'tenants'])->name('tenants');
});

// Product Analytics
Route::prefix('product-analytics')->name('admin.product-analytics.')->group(function () {
    Route::middleware('hrmac:product-analytics.feature-usage.view')
        ->get('/features', [ProductAnalyticsController::class, 'featureUsage'])->name('features');
    Route::middleware('hrmac:product-analytics.cohort-analysis.view')
        ->get('/cohorts', [ProductAnalyticsController::class, 'cohorts'])->name('cohorts');
    Route::middleware('hrmac:product-analytics.funnel-analysis.view')
        ->get('/funnels', [ProductAnalyticsController::class, 'funnels'])->name('funnels');
    Route::middleware('hrmac:product-analytics.funnel-analysis.manage')
        ->post('/funnels', [ProductAnalyticsController::class, 'storeFunnel'])->name('funnels.store');
    Route::middleware('hrmac:product-analytics.adoption-metrics.view')
        ->get('/adoption', [ProductAnalyticsController::class, 'adoption'])->name('adoption');
});
```

## Task 7 — React pages

> Inertia pages live at `packages/aero-ui/resources/js/Pages/Platform/Admin/{Feature}/Page.jsx`. Depth = 4 segments. Imports:
> - App: `'../../../App.jsx'`
> - useHRMAC: `'../../../../hooks/useHRMAC.js'`
> - All components from `@aero/ui` — never `@heroui/react`. No inline `style={}`, no `<style>`, no `window.confirm`.

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Dashboard/Index.jsx`

```jsx
import App from '../../../App.jsx';
import { Head } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Chip, Progress, Table, TableHeader,
    TableColumn, TableBody, TableRow, TableCell, Button,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Index({ stats, recentTenants, systemHealth }) {
    const { hasAccess } = useHRMAC();

    return (
        <>
            <Head title="Platform Dashboard" />
            <div className="space-y-6 p-6">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <StatCard label="MRR" value={`$${stats.mrr.toLocaleString()}`} growth={stats.mrr_growth} />
                    <StatCard label="ARR" value={`$${stats.arr.toLocaleString()}`} />
                    <StatCard label="Active Tenants" value={stats.active_tenants} />
                    <StatCard label="Churn (30d)" value={`${stats.churn_rate_pct}%`} />
                </div>

                <Card>
                    <CardHeader>System Health</CardHeader>
                    <CardBody className="grid grid-cols-1 gap-4 md:grid-cols-4">
                        {Object.entries(systemHealth).map(([key, h]) => (
                            <div key={key} className="flex items-center justify-between">
                                <span className="capitalize">{key}</span>
                                <Chip color={h.status === 'ok' ? 'success' : h.status === 'warn' ? 'warning' : 'danger'}>
                                    {h.status}
                                </Chip>
                            </div>
                        ))}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>Recent Tenants</CardHeader>
                    <CardBody>
                        <Table aria-label="Recent tenants">
                            <TableHeader>
                                <TableColumn>Name</TableColumn>
                                <TableColumn>Subdomain</TableColumn>
                                <TableColumn>Status</TableColumn>
                                <TableColumn>Created</TableColumn>
                            </TableHeader>
                            <TableBody items={recentTenants}>
                                {(t) => (
                                    <TableRow key={t.id}>
                                        <TableCell>{t.name}</TableCell>
                                        <TableCell>{t.subdomain}</TableCell>
                                        <TableCell><Chip>{t.status}</Chip></TableCell>
                                        <TableCell>{new Date(t.created_at).toLocaleDateString()}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

function StatCard({ label, value, growth }) {
    return (
        <Card>
            <CardBody>
                <div className="text-sm text-default-500">{label}</div>
                <div className="text-2xl font-semibold">{value}</div>
                {growth !== undefined && (
                    <Chip color={growth >= 0 ? 'success' : 'danger'} size="sm">
                        {growth >= 0 ? '+' : ''}{growth}%
                    </Chip>
                )}
            </CardBody>
        </Card>
    );
}

Index.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Quotas/Index.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Modal, ModalContent, ModalHeader,
    ModalBody, ModalFooter, Input, Textarea, Select, SelectItem, Chip,
    Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
    useDisclosure,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { useState } from 'react';

export default function Index({ overrides, analytics, filters }) {
    const { hasAccess } = useHRMAC();
    const canOverride = hasAccess('quota-management.quota-dashboard.override');
    const { isOpen, onOpen, onClose } = useDisclosure();
    const [target, setTarget] = useState(null);
    const form = useForm({ resource: 'storage_gb', limit_value: 0, reason: '', expires_at: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(`/admin/quotas/${target.id}/override`, { onSuccess: () => { onClose(); form.reset(); } });
    };

    return (
        <>
            <Head title="Quota Management" />
            <div className="space-y-6 p-6">
                <div className="grid grid-cols-3 gap-4">
                    <Card><CardBody><div>Overrides</div><div className="text-2xl font-bold">{analytics.overrides_count}</div></CardBody></Card>
                    <Card><CardBody><div>Expiring (7d)</div><div className="text-2xl font-bold">{analytics.expiring_soon}</div></CardBody></Card>
                    <Card><CardBody><div>By resource</div>{Object.entries(analytics.by_resource).map(([k, v]) => (<Chip key={k} className="mr-1">{k}: {v}</Chip>))}</CardBody></Card>
                </div>

                <Card>
                    <CardHeader>Tenant Quota Overrides</CardHeader>
                    <CardBody>
                        <Table aria-label="Overrides">
                            <TableHeader>
                                <TableColumn>Tenant</TableColumn>
                                <TableColumn>Resource</TableColumn>
                                <TableColumn>Limit</TableColumn>
                                <TableColumn>Expires</TableColumn>
                                <TableColumn>Set By</TableColumn>
                                <TableColumn>Actions</TableColumn>
                            </TableHeader>
                            <TableBody items={overrides.data}>
                                {(o) => (
                                    <TableRow key={o.id}>
                                        <TableCell>{o.tenant?.name}</TableCell>
                                        <TableCell>{o.resource}</TableCell>
                                        <TableCell>{o.limit_value}</TableCell>
                                        <TableCell>{o.expires_at ?? '—'}</TableCell>
                                        <TableCell>{o.setter?.name}</TableCell>
                                        <TableCell>
                                            {canOverride && (
                                                <Button size="sm" color="danger" variant="flat"
                                                    onPress={() => router.delete(`/admin/quotas/${o.tenant_id}/override/${o.resource}`)}>
                                                    Remove
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>
            </div>

            <Modal isOpen={isOpen} onClose={onClose}>
                <ModalContent>
                    <form onSubmit={submit}>
                        <ModalHeader>Set Override for {target?.name}</ModalHeader>
                        <ModalBody className="space-y-3">
                            <Select label="Resource" selectedKeys={[form.data.resource]}
                                onChange={(e) => form.setData('resource', e.target.value)}>
                                <SelectItem key="storage_gb">storage_gb</SelectItem>
                                <SelectItem key="api_calls">api_calls</SelectItem>
                                <SelectItem key="users">users</SelectItem>
                            </Select>
                            <Input type="number" label="Limit" value={String(form.data.limit_value)}
                                onValueChange={(v) => form.setData('limit_value', Number(v))} />
                            <Textarea label="Reason" value={form.data.reason}
                                onValueChange={(v) => form.setData('reason', v)} />
                            <Input type="datetime-local" label="Expires At" value={form.data.expires_at}
                                onValueChange={(v) => form.setData('expires_at', v)} />
                        </ModalBody>
                        <ModalFooter>
                            <Button variant="flat" onPress={onClose}>Cancel</Button>
                            <Button color="primary" type="submit" isLoading={form.processing}>Save</Button>
                        </ModalFooter>
                    </form>
                </ModalContent>
            </Modal>
        </>
    );
}

Index.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Quotas/Settings.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, useForm } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Input, Select, SelectItem, Chip,
} from '@aero/ui';

export default function Settings({ settings }) {
    return (
        <>
            <Head title="Quota Enforcement Settings" />
            <div className="space-y-4 p-6">
                {settings.map((s) => <Row key={s.id} setting={s} />)}
                <NewRow />
            </div>
        </>
    );
}

function Row({ setting }) {
    const form = useForm({
        resource: setting.resource,
        default_limit: setting.default_limit,
        warning_threshold_pct: setting.warning_threshold_pct,
        hard_limit_pct: setting.hard_limit_pct,
        action: setting.action,
    });
    const submit = (e) => { e.preventDefault(); form.put('/admin/quotas/settings'); };
    return (
        <Card>
            <CardHeader>{setting.resource}</CardHeader>
            <CardBody>
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <Input label="Default Limit" type="number" value={String(form.data.default_limit)}
                        onValueChange={(v) => form.setData('default_limit', Number(v))} />
                    <Input label="Warn %" type="number" value={String(form.data.warning_threshold_pct)}
                        onValueChange={(v) => form.setData('warning_threshold_pct', Number(v))} />
                    <Input label="Hard %" type="number" value={String(form.data.hard_limit_pct)}
                        onValueChange={(v) => form.setData('hard_limit_pct', Number(v))} />
                    <Select label="Action" selectedKeys={[form.data.action]}
                        onChange={(e) => form.setData('action', e.target.value)}>
                        <SelectItem key="warn">warn</SelectItem>
                        <SelectItem key="throttle">throttle</SelectItem>
                        <SelectItem key="block">block</SelectItem>
                    </Select>
                    <Button type="submit" color="primary" isLoading={form.processing}>Save</Button>
                </form>
            </CardBody>
        </Card>
    );
}

function NewRow() {
    const form = useForm({ resource: '', default_limit: 0, warning_threshold_pct: 80, hard_limit_pct: 100, action: 'warn' });
    const submit = (e) => { e.preventDefault(); form.put('/admin/quotas/settings', { onSuccess: () => form.reset() }); };
    return (
        <Card>
            <CardHeader>Add Resource</CardHeader>
            <CardBody>
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <Input label="Resource" value={form.data.resource}
                        onValueChange={(v) => form.setData('resource', v)} />
                    <Input label="Default Limit" type="number" value={String(form.data.default_limit)}
                        onValueChange={(v) => form.setData('default_limit', Number(v))} />
                    <Input label="Warn %" type="number" value={String(form.data.warning_threshold_pct)}
                        onValueChange={(v) => form.setData('warning_threshold_pct', Number(v))} />
                    <Select label="Action" selectedKeys={[form.data.action]}
                        onChange={(e) => form.setData('action', e.target.value)}>
                        <SelectItem key="warn">warn</SelectItem>
                        <SelectItem key="throttle">throttle</SelectItem>
                        <SelectItem key="block">block</SelectItem>
                    </Select>
                    <Button type="submit" color="primary" isLoading={form.processing}>Add</Button>
                </form>
            </CardBody>
        </Card>
    );
}

Settings.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Analytics/Revenue.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, router } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Select, SelectItem,
    Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
} from '@aero/ui';

export default function Revenue({ data, range, bucket }) {
    const setBucket = (b) => router.get('/admin/analytics/revenue', { ...range, bucket: b }, { preserveState: true });

    return (
        <>
            <Head title="Revenue Reports" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <span>Revenue Trend</span>
                        <Select size="sm" className="max-w-[160px]" selectedKeys={[bucket]}
                            onChange={(e) => setBucket(e.target.value)}>
                            <SelectItem key="day">Daily</SelectItem>
                            <SelectItem key="week">Weekly</SelectItem>
                            <SelectItem key="month">Monthly</SelectItem>
                        </Select>
                    </CardHeader>
                    <CardBody>
                        <Table aria-label="Revenue trend">
                            <TableHeader>
                                <TableColumn>Period</TableColumn>
                                <TableColumn>MRR</TableColumn>
                                <TableColumn>Revenue</TableColumn>
                            </TableHeader>
                            <TableBody items={data.trend}>
                                {(r) => (
                                    <TableRow key={r.period}>
                                        <TableCell>{r.period}</TableCell>
                                        <TableCell>${r.mrr.toLocaleString()}</TableCell>
                                        <TableCell>${r.revenue.toLocaleString()}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>Revenue by Plan</CardHeader>
                    <CardBody>
                        <Table aria-label="By plan">
                            <TableHeader>
                                <TableColumn>Plan</TableColumn>
                                <TableColumn>Tenants</TableColumn>
                                <TableColumn>MRR</TableColumn>
                            </TableHeader>
                            <TableBody items={data.by_plan}>
                                {(r) => (
                                    <TableRow key={r.name}>
                                        <TableCell>{r.name}</TableCell>
                                        <TableCell>{r.tenants}</TableCell>
                                        <TableCell>${Number(r.mrr).toLocaleString()}</TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Revenue.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Analytics/Tenants.jsx`

```jsx
import App from '../../../App.jsx';
import { Head } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Chip,
    Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
} from '@aero/ui';

export default function Tenants({ data, range }) {
    return (
        <>
            <Head title="Tenant Analytics" />
            <div className="space-y-6 p-6">
                <Card>
                    <CardHeader>Signup Trend ({range.from} → {range.to})</CardHeader>
                    <CardBody>
                        <Table aria-label="Signup trend">
                            <TableHeader>
                                <TableColumn>Date</TableColumn>
                                <TableColumn>New</TableColumn>
                                <TableColumn>Churned</TableColumn>
                            </TableHeader>
                            <TableBody items={data.signup_trend}>
                                {(r) => (
                                    <TableRow key={r.date}>
                                        <TableCell>{r.date}</TableCell>
                                        <TableCell><Chip color="success">{r.new_tenants}</Chip></TableCell>
                                        <TableCell><Chip color="danger">{r.churned}</Chip></TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>Plan Distribution</CardHeader>
                    <CardBody>
                        {data.plan_distribution.map((p) => (
                            <Chip key={p.plan_id} className="mr-2">
                                {p.plan?.name ?? `Plan #${p.plan_id}`}: {p.count}
                            </Chip>
                        ))}
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Tenants.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/ProductAnalytics/Features.jsx`

```jsx
import App from '../../../App.jsx';
import { Head, router } from '@inertiajs/react';
import {
    Card, CardBody, CardHeader, Button, Select, SelectItem,
    Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Progress,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Features({ rows, days }) {
    const { hasAccess } = useHRMAC();
    const canExport = hasAccess('product-analytics.feature-usage.export');

    return (
        <>
            <Head title="Feature Usage" />
            <div className="space-y-4 p-6">
                <Card>
                    <CardHeader className="flex items-center justify-between">
                        <span>Feature Usage (last {days} days)</span>
                        <div className="flex gap-2">
                            <Select size="sm" className="max-w-[120px]" selectedKeys={[String(days)]}
                                onChange={(e) => router.get('/admin/product-analytics/features', { days: e.target.value })}>
                                <SelectItem key="7">7d</SelectItem>
                                <SelectItem key="30">30d</SelectItem>
                                <SelectItem key="90">90d</SelectItem>
                            </Select>
                            {canExport && (
                                <Button color="primary" onPress={() => window.open(`/admin/product-analytics/features/export?days=${days}`)}>
                                    Export
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardBody>
                        <Table aria-label="Feature usage">
                            <TableHeader>
                                <TableColumn>Feature</TableColumn>
                                <TableColumn>Events</TableColumn>
                                <TableColumn>Tenants</TableColumn>
                                <TableColumn>Adoption</TableColumn>
                            </TableHeader>
                            <TableBody items={rows}>
                                {(r) => (
                                    <TableRow key={r.feature_code}>
                                        <TableCell>{r.feature_code}</TableCell>
                                        <TableCell>{r.events.toLocaleString()}</TableCell>
                                        <TableCell>{r.tenants}</TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Progress value={r.adoption_pct} className="max-w-[120px]" />
                                                <span>{r.adoption_pct}%</span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Features.layout = (page) => <App children={page} />;
```

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/ProductAnalytics/Cohorts.jsx`

```jsx
import App from '../../../App.jsx';
import { Head } from '@inertiajs/react';
import { Card, CardBody, CardHeader, Chip } from '@aero/ui';

export default function Cohorts({ matrix, months }) {
    const heat = (pct) =>
        pct >= 80 ? 'success' : pct >= 60 ? 'primary' : pct >= 40 ? 'warning' : 'danger';

    return (
        <>
            <Head title="Cohort Retention" />
            <div className="space-y-4 p-6">
                <Card>
                    <CardHeader>Cohort Retention ({months} months)</CardHeader>
                    <CardBody className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th className="px-3 py-2 text-left">Cohort</th>
                                    <th className="px-3 py-2 text-left">Size</th>
                                    {Array.from({ length: months }, (_, i) => (
                                        <th key={i} className="px-3 py-2 text-left">M{i}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {matrix.map((row) => (
                                    <tr key={row.cohort}>
                                        <td className="px-3 py-2">{row.cohort}</td>
                                        <td className="px-3 py-2">{row.size}</td>
                                        {row.months.map((m) => (
                                            <td key={m.month} className="px-3 py-2">
                                                <Chip color={heat(m.pct)} size="sm">{m.pct}%</Chip>
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

Cohorts.layout = (page) => <App children={page} />;
```

## Task 8 — Tests

- [ ] `packages/aero-platform/tests/Feature/Admin/DashboardTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_dashboard_returns_mrr_arr_correctly(): void
    {
        PlatformMetricDaily::factory()->create([
            'date' => today()->toDateString(),
            'mrr'  => 12500,
            'arr'  => 150000,
        ]);

        $user = LandlordUser::factory()->create();

        $this->actingAs($user, 'landlord')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/Dashboard/Index')
                ->where('stats.mrr', 12500.0)
                ->where('stats.arr', 150000.0)
            );
    }
}
```

- [ ] `packages/aero-platform/tests/Feature/Admin/QuotaTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantQuotaOverride;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class QuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_can_set_quota_override(): void
    {
        $admin = LandlordUser::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($admin, 'landlord')
            ->post("/admin/quotas/{$tenant->id}/override", [
                'resource'    => 'storage_gb',
                'limit_value' => 200,
                'reason'      => 'Customer requested upgrade',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_quota_overrides', [
            'tenant_id'   => $tenant->id,
            'resource'    => 'storage_gb',
            'limit_value' => 200,
        ]);
    }

    public function test_cannot_set_override_below_current_usage(): void
    {
        $admin = LandlordUser::factory()->create();
        $tenant = Tenant::factory()->create();

        \DB::connection('central')->table('tenant_stats')->insert([
            'tenant_id'    => $tenant->id,
            'date'         => today(),
            'storage_mb'   => 500,
            'api_requests' => 0,
            'active_users' => 0,
        ]);

        $this->actingAs($admin, 'landlord')
            ->post("/admin/quotas/{$tenant->id}/override", [
                'resource'    => 'storage_gb',
                'limit_value' => 100,
            ])
            ->assertStatus(422);
    }
}
```

- [ ] `packages/aero-platform/tests/Feature/Admin/AnalyticsTest.php`

```php
<?php

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformMetricDaily;
use Aero\Platform\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_revenue_report_groups_by_period(): void
    {
        for ($i = 0; $i < 14; $i++) {
            PlatformMetricDaily::factory()->create([
                'date' => now()->subDays($i)->toDateString(),
                'mrr'  => 1000 + $i,
                'total_revenue' => 100,
            ]);
        }

        $admin = LandlordUser::factory()->create();

        $this->actingAs($admin, 'landlord')
            ->get('/admin/analytics/revenue?bucket=week')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Platform/Admin/Analytics/Revenue'));
    }

    public function test_cohort_analysis_returns_retention_rates(): void
    {
        $admin = LandlordUser::factory()->create();

        $this->actingAs($admin, 'landlord')
            ->get('/admin/product-analytics/cohorts?months=3')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/ProductAnalytics/Cohorts')
                ->has('matrix')
            );
    }
}
```

## Task 9 — Done definition

- [ ] All migrations run on `central` connection.
- [ ] Models extend `CentralModel` and declare `protected $connection = 'central'`.
- [ ] HRMAC entries added in `module.php` for: `quota-management`, `platform-analytics`, `product-analytics`.
- [ ] All routes guarded with `hrmac:{submodule}.{component}.{action}` middleware.
- [ ] All writes wrapped in `DB::transaction()`.
- [ ] Audit log written via `AuditServiceInterface` on override set/remove, settings update, funnel create.
- [ ] React pages live under `Pages/Platform/Admin/{Feature}/` using `@aero/ui` only (no `@heroui/react`, no inline `style={}`, no `window.confirm`).
- [ ] Import depths: App=`'../../../App.jsx'`, useHRMAC=`'../../../../hooks/useHRMAC.js'`.
- [ ] All listed tests pass with `Gate::before(fn () => true)`.
