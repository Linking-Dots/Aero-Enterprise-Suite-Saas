<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\AeonTenantUsage;
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
     * Fleet KPIs — a cheap aggregate over the roll-up summary (aeon:rollup),
     * not a per-request fan-out. Falls back to zeros before the first roll-up.
     *
     * @return array<string,mixed>
     */
    private function stats(): array
    {
        $period = now()->format('Y-m');
        $agg = AeonTenantUsage::where('period', $period)
            ->selectRaw('SUM(enabled) as with_ai, SUM(messages_used) as used, SUM(feedback_up) as up, SUM(feedback_down) as down, MAX(synced_at) as synced')
            ->first();

        $used = (int) ($agg->used ?? 0);
        $up = (int) ($agg->up ?? 0);
        $down = (int) ($agg->down ?? 0);
        $rated = $up + $down;

        return [
            'tenants_total' => Tenant::whereNull('deleted_at')->count(),
            'tenants_with_ai' => (int) ($agg->with_ai ?? 0),
            'messages_this_month' => $used,
            'est_cost' => round($used * 0.0015, 2),
            'feedback_up' => $up,
            'feedback_down' => $down,
            'satisfaction' => $rated > 0 ? (int) round($up / $rated * 100) : null,
            'synced_at' => $agg->synced ?? null,
        ];
    }

    /**
     * One server-paginated page of tenants from the roll-up summary (cheap,
     * correct — the tenant-scoped counter is only readable inside the tenant,
     * which the job did), joined to tenants for the current name + search.
     *
     * @return array<string,mixed>
     */
    private function tenantPage(Request $request): array
    {
        $q = $request->string('q')->toString();
        $period = now()->format('Y-m');

        $paginator = AeonTenantUsage::query()
            ->where('aeon_tenant_usage.period', $period)
            ->join('tenants', 'tenants.id', '=', 'aeon_tenant_usage.tenant_id')
            ->when($q !== '', fn ($qb) => $qb->where('tenants.name', 'like', "%{$q}%"))
            ->orderByDesc('aeon_tenant_usage.enabled')
            ->orderBy('tenants.name')
            ->select('aeon_tenant_usage.*', 'tenants.name as tenant_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $rows = collect($paginator->items())->map(function (AeonTenantUsage $u) {
            $limit = (int) $u->message_limit;
            $unlimited = $limit === -1;

            return [
                'id' => (string) $u->tenant_id,
                'name' => $u->tenant_name,
                'plan' => $u->plan_name,
                'enabled' => (bool) $u->enabled,
                'model' => $u->model,
                'used' => (int) $u->messages_used,
                'limit' => $limit,
                'remaining' => $unlimited ? -1 : max(0, $limit - (int) $u->messages_used),
                'unlimited' => $unlimited,
                'feedback_up' => (int) $u->feedback_up,
                'feedback_down' => (int) $u->feedback_down,
            ];
        })->all();

        return [
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'synced_at' => optional($paginator->items()[0] ?? null)->synced_at,
        ];
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
