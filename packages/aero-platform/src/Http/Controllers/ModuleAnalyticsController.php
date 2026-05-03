<?php

namespace Aero\Platform\Http\Controllers;

use Aero\Core\Support\TenantCache;
use Aero\Platform\Models\Module;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleAnalyticsController extends Controller
{
    /**
     * Get comprehensive module analytics dashboard data.
     */
    public function index()
    {
        $analytics = TenantCache::remember('module_analytics_dashboard', 300, function () {
            return [
                'overview' => $this->getOverviewStats(),
                'module_adoption' => $this->getModuleAdoption(),
                'plan_distribution' => $this->getPlanDistribution(),
                'trending_modules' => $this->getTrendingModules(),
            ];
        });

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get overview statistics.
     */
    protected function getOverviewStats(): array
    {
        $totalModules = Module::where('is_active', true)->count();
        $totalTenants = Tenant::whereNotNull('id')->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();

        // Calculate average modules per tenant
        $avgModulesPerTenant = DB::table('subscriptions')
            ->join('plan_module', 'subscriptions.plan_id', '=', 'plan_module.plan_id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billable_type', Tenant::class)
            ->where('plan_module.is_enabled', true)
            ->select('subscriptions.billable_id')
            ->distinct()
            ->count();

        return [
            'total_modules' => $totalModules,
            'total_tenants' => $totalTenants,
            'active_subscriptions' => $activeSubscriptions,
            'avg_modules_per_tenant' => $totalTenants > 0 ? round($avgModulesPerTenant / $totalTenants, 2) : 0,
        ];
    }

    /**
     * Get module adoption rates.
     */
    protected function getModuleAdoption(): array
    {
        $modules = Module::with(['plans.subscriptions' => function ($query) {
            $query->where('status', 'active');
        }])->get();

        $totalActiveTenants = Subscription::where('status', 'active')
            ->where('billable_type', Tenant::class)
            ->distinct('billable_id')
            ->count();

        return $modules->map(function ($module) use ($totalActiveTenants) {
            $activeTenants = $module->plans()
                ->whereHas('subscriptions', function ($query) {
                    $query->where('status', 'active');
                })
                ->with(['subscriptions' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->get()
                ->pluck('subscriptions')
                ->flatten()
                ->unique('billable_id')
                ->count();

            return [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'is_core' => $module->is_core,
                'active_tenants' => $activeTenants,
                'adoption_rate' => $totalActiveTenants > 0
                    ? round(($activeTenants / $totalActiveTenants) * 100, 2)
                    : 0,
                'total_plans' => $module->plans()->count(),
            ];
        })->sortByDesc('adoption_rate')->values()->all();
    }

    /**
     * Get plan distribution by modules.
     */
    protected function getPlanDistribution(): array
    {
        $plans = Plan::with('modules')->get();

        return $plans->map(function ($plan) {
            $activeSubscriptions = Subscription::where('plan_id', $plan->id)
                ->where('status', 'active')
                ->count();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'price' => $plan->price,
                'module_count' => $plan->modules()->count(),
                'modules' => $plan->modules->pluck('code')->toArray(),
                'active_subscriptions' => $activeSubscriptions,
                'revenue' => $activeSubscriptions * ($plan->price ?? 0),
            ];
        })->sortByDesc('active_subscriptions')->values()->all();
    }

    /**
     * Get trending modules (most recently adopted).
     */
    protected function getTrendingModules(): array
    {
        // Get modules with recent subscriptions
        $trendingData = DB::table('subscriptions')
            ->join('plan_module', 'subscriptions.plan_id', '=', 'plan_module.plan_id')
            ->join('modules', 'plan_module.module_id', '=', 'modules.id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billable_type', Tenant::class)
            ->where('subscriptions.created_at', '>=', now()->subDays(30))
            ->select(
                'modules.id',
                'modules.code',
                'modules.name',
                DB::raw('COUNT(DISTINCT subscriptions.billable_id) as new_adoptions')
            )
            ->groupBy('modules.id', 'modules.code', 'modules.name')
            ->orderByDesc('new_adoptions')
            ->limit(10)
            ->get();

        return $trendingData->map(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'new_adoptions' => $item->new_adoptions,
            ];
        })->toArray();
    }

    /**
     * Get detailed analytics for a specific module.
     */
    public function show(Module $module)
    {
        $analytics = TenantCache::remember("module_analytics_{$module->id}", 300, function () use ($module) {
            $module->load(['subModules.components.actions', 'plans.subscriptions']);

            $activeTenants = $module->plans()
                ->whereHas('subscriptions', function ($query) {
                    $query->where('status', 'active');
                })
                ->with(['subscriptions' => function ($query) {
                    $query->where('status', 'active');
                }])
                ->get()
                ->pluck('subscriptions')
                ->flatten()
                ->unique('billable_id')
                ->count();

            return [
                'module' => [
                    'id' => $module->id,
                    'code' => $module->code,
                    'name' => $module->name,
                    'description' => $module->description,
                    'is_core' => $module->is_core,
                    'is_active' => $module->is_active,
                ],
                'hierarchy' => [
                    'submodules' => $module->subModules->count(),
                    'components' => $module->subModules->sum(function ($sub) {
                        return $sub->components->count();
                    }),
                    'actions' => $module->subModules->sum(function ($sub) {
                        return $sub->components->sum(function ($comp) {
                            return $comp->actions->count();
                        });
                    }),
                ],
                'usage' => [
                    'active_tenants' => $activeTenants,
                    'total_plans' => $module->plans()->count(),
                    'plans' => $module->plans->map(fn ($plan) => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'active_subscriptions' => $plan->subscriptions()->where('status', 'active')->count(),
                    ]),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get module usage trends over time.
     */
    public function trends(Request $request)
    {
        $days = $request->input('days', 30);

        $trends = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $activeSubs = Subscription::where('status', 'active')
                ->whereDate('created_at', '<=', $date)
                ->whereDate(function ($query) use ($date) {
                    $query->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $date);
                })
                ->count();

            $trends[] = [
                'date' => $date,
                'active_subscriptions' => $activeSubs,
            ];
        }

        return response()->json([
            'success' => true,
            'trends' => $trends,
        ]);
    }
}
