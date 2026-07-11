<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\Quotas\QuotaEnforcementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AI Assistant (Aeon) fleet control — the operator's control plane for the
 * tenant-facing assistant: central provider/model/key + global limits, fleet
 * usage, and a read-only mirror of each plan's AI allowance (edited in Plans).
 * Per-tenant overrides live on the Quotas page.
 *
 * The tenant table is SERVER-PAGINATED (AI usage computed only for the page's
 * rows); fleet KPIs are cached briefly so they never fan out per request.
 */
class AiAssistantController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private QuotaEnforcementService $quotas) {}

    public function index(Request $request): Response
    {
        $setting = PlatformSetting::current();

        return Inertia::render('Platform/Admin/AiAssistant/Index', [
            'settings' => $setting->getSanitizedAiSettings(),
            'stats' => $this->stats(),
            'planAllowances' => $this->planAllowances(),
            'tenants' => $this->tenantPage($request),
            'filters' => ['q' => $request->string('q')->toString()],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'enabled' => ['boolean'],
            'provider' => ['required', 'string', 'in:gemini,openai'],
            'fast_model' => ['required', 'string', 'max:100'],
            'premium_model' => ['required', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:400'],
            'base_url' => ['nullable', 'string', 'max:255'],
            'token_fuse_per_conversation' => ['required', 'integer', 'min:0'],
            'token_fuse_per_user_daily' => ['required', 'integer', 'min:0'],
            'max_tool_steps' => ['required', 'integer', 'min:1', 'max:10'],
        ])->validate();

        PlatformSetting::current()->saveAiSettings($data);
        Cache::forget('aeon:fleet_stats');

        return back()->with('success', 'AI settings saved.');
    }

    /**
     * Fleet KPIs. Cached (2 min) so the aggregate scan never runs per request;
     * the nightly roll-up (Phase 4) will replace this with a stored summary.
     *
     * @return array<string,mixed>
     */
    private function stats(): array
    {
        return Cache::remember('aeon:fleet_stats', 120, function () {
            $withAi = 0;
            $used = 0;
            Tenant::query()->whereNull('deleted_at')->select(['id', 'status'])
                ->chunk(200, function ($chunk) use (&$withAi, &$used) {
                    foreach ($chunk as $t) {
                        try {
                            $s = $this->quotas->getAiSummary($t);
                        } catch (\Throwable) {
                            continue;
                        }
                        if ($s['enabled']) {
                            $withAi++;
                        }
                        $used += $s['used'];
                    }
                });

            return [
                'tenants_total' => Tenant::whereNull('deleted_at')->count(),
                'tenants_with_ai' => $withAi,
                'messages_this_month' => $used,
                'est_cost' => round($used * 0.0015, 2),
            ];
        });
    }

    /**
     * One server-paginated page of tenants with their AI usage (computed only
     * for the ≤12 rows shown), optional name search.
     *
     * @return array<string,mixed>
     */
    private function tenantPage(Request $request): array
    {
        $q = $request->string('q')->toString();

        $paginator = Tenant::query()
            ->whereNull('deleted_at')
            ->when($q !== '', fn ($qb) => $qb->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $rows = collect($paginator->items())->map(function (Tenant $t) {
            try {
                $s = $this->quotas->getAiSummary($t);
                $plan = $this->safePlanName($t);
            } catch (\Throwable) {
                $s = ['enabled' => false, 'used' => 0, 'limit' => 0, 'remaining' => 0, 'model' => 'flash', 'unlimited' => false];
                $plan = null;
            }

            return [
                'id' => (string) $t->id,
                'name' => $t->name,
                'plan' => $plan,
                'enabled' => $s['enabled'],
                'model' => $s['model'],
                'used' => $s['used'],
                'limit' => $s['limit'],
                'remaining' => $s['remaining'],
                'unlimited' => $s['unlimited'],
            ];
        })->all();

        return [
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ];
    }

    /** Plan name for a tenant, tolerating plan-less tenants / accessor errors. */
    private function safePlanName(Tenant $t): ?string
    {
        try {
            return optional($t->plan)->name;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Read-only mirror of each plan's AI allowance (edit path lives in Plans).
     *
     * @return array<int,array<string,mixed>>
     */
    private function planAllowances(): array
    {
        return Plan::query()->orderBy('sort_order')->get()->map(function (Plan $p) {
            $limits = is_array($p->limits) ? $p->limits : [];
            $enabled = array_key_exists('max_ai_messages', $limits);

            return [
                'id' => (string) $p->id,
                'name' => $p->name,
                'tier' => $p->tier,
                'enabled' => $enabled,
                'model' => $enabled ? ($limits['ai_model'] ?? 'flash') : null,
                'messages' => $enabled ? (int) $limits['max_ai_messages'] : null,
            ];
        })->all();
    }
}
