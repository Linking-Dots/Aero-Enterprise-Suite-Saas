<?php

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Jobs\ProvisionTenant;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\OnboardingAdminService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin Onboarding Controller
 *
 * Manages the complete tenant onboarding lifecycle including:
 * - Dashboard with registration and trial statistics
 * - Pending registration approvals
 * - Provisioning queue monitoring
 * - Trial management and conversions
 * - Onboarding analytics
 * - Automation rules
 * - Onboarding settings
 *
 * @see Tenant
 * @see Subscription
 */
class AdminOnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingAdminService $svc
    ) {}

    /**
     * Onboarding command centre — the /onboarding landing (full lifecycle console).
     */
    public function overview(): Response
    {
        return Inertia::render('Platform/Admin/Onboarding/P2/Onboarding', [
            'overview' => fn () => $this->svc->overview(),
        ]);
    }

    /**
     * Per-tenant lifecycle detail for the drawer (JSON, fetched client-side).
     */
    public function detail(Tenant $tenant): JsonResponse
    {
        return response()->json($this->svc->detail((string) $tenant->id));
    }

    /**
     * Bulk lifecycle action over pending/failed/trial tenants. Each item is
     * processed independently so one failure cannot roll back the others.
     */
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:approve,reject,retry,archive',
            'ids' => 'required|array',
            'ids.*' => 'string',
            'reason' => 'nullable|string|max:500',
        ]);

        $ok = 0;
        $failed = 0;
        foreach ($data['ids'] as $id) {
            try {
                $tenant = Tenant::find($id);
                if ($tenant === null) {
                    $failed++;

                    continue;
                }
                match ($data['action']) {
                    'approve' => $this->approve($request, $tenant),
                    'reject'  => $this->reject($request->merge(['reason' => $data['reason'] ?? 'Bulk rejection']), $tenant),
                    'retry'   => $this->retryProvisioning($tenant),
                    'archive' => $this->archive($request->merge(['reason' => $data['reason'] ?? 'Bulk archive']), $tenant),
                };
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return response()->json(['success' => true, 'ok' => $ok, 'failed' => $failed]);
    }

    /**
     * Display the onboarding dashboard with key metrics.
     */
    public function dashboard(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display pending registrations awaiting approval.
     */
    public function pending(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display provisioning queue and status.
     */
    public function provisioning(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display trial management interface.
     */
    public function trials(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display onboarding analytics.
     */
    public function analytics(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display automation rules management.
     */
    public function automation(): RedirectResponse
    {
        return redirect('/onboarding');
    }

    /**
     * Display onboarding settings.
     */
    public function settings(): Response
    {
        return Inertia::render('Platform/Admin/Onboarding/P2/Settings', [
            'data' => fn () => $this->svc->settingsPayload(),
        ]);
    }

    // =========================================================================
    // API ACTIONS
    // =========================================================================

    /**
     * Approve a pending registration.
     */
    public function approve(Request $request, Tenant $tenant): JsonResponse
    {
        try {
            if ($tenant->status !== Tenant::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending tenants can be approved.',
                ], 422);
            }

            $tenant->update([
                'status' => Tenant::STATUS_PROVISIONING,
            ]);

            // Ensure the tenant has a domain before provisioning (may be missing if
            // registration was aborted before activateTrial() was called).
            if ($tenant->domains()->count() === 0 && ! empty($tenant->subdomain)) {
                $baseDomain = config('platform.central_domain', 'localhost');
                $tenant->domains()->create([
                    'domain' => $tenant->subdomain.'.'.$baseDomain,
                    'is_primary' => true,
                ]);
            }

            // Dispatch provisioning job
            ProvisionTenant::dispatch($tenant->fresh());

            // Clear stats cache
            $this->clearStatsCache();

            Log::info('Tenant approved for provisioning', [
                'tenant_id' => $tenant->id,
                'approved_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant approved and queued for provisioning.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve tenant.',
            ], 500);
        }
    }

    /**
     * Reject a pending registration.
     */
    public function reject(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $tenant->update([
                'status' => Tenant::STATUS_ARCHIVED,
                'data' => array_merge($tenant->data?->toArray() ?? [], [
                    'rejection_reason' => $request->reason,
                    'rejected_at' => Carbon::now()->toISOString(),
                    'rejected_by' => Auth::guard('landlord')->id(),
                ]),
            ]);

            // Clear stats cache
            $this->clearStatsCache();

            Log::info('Tenant registration rejected', [
                'tenant_id' => $tenant->id,
                'reason' => $request->reason,
                'rejected_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration has been rejected.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject registration.',
            ], 500);
        }
    }

    /**
     * Retry failed provisioning.
     */
    public function retryProvisioning(Tenant $tenant): JsonResponse
    {
        try {
            if ($tenant->status !== Tenant::STATUS_FAILED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed tenants can be retried.',
                ], 422);
            }

            $tenant->update([
                'status' => Tenant::STATUS_PROVISIONING,
                'provisioning_step' => null,
            ]);

            // Dispatch provisioning job
            ProvisionTenant::dispatch($tenant);

            Log::info('Provisioning retry initiated', [
                'tenant_id' => $tenant->id,
                'initiated_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Provisioning retry has been initiated.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retry provisioning', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Temporarily expose the actual error for debugging
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry provisioning.',
                'debug_error' => $e->getMessage(),
                'debug_class' => get_class($e),
            ], 500);
        }
    }

    /**
     * Extend a tenant's trial period.
     */
    public function extendTrial(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:90',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $subscription = $tenant->subscription('default');
            $currentEnd = $subscription?->trial_ends_at ?? Carbon::now();
            $newEnd = Carbon::parse($currentEnd)->addDays($request->days);

            if ($subscription) {
                $subscription->update([
                    'trial_ends_at' => $newEnd,
                    'ends_at' => $newEnd,
                ]);
            }

            Log::info('Trial extended', [
                'tenant_id' => $tenant->id,
                'days_added' => $request->days,
                'new_end_date' => $newEnd->toISOString(),
                'reason' => $request->reason,
                'extended_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Trial extended by {$request->days} days.",
                'new_end_date' => $newEnd->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to extend trial', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extend trial.',
            ], 500);
        }
    }

    /**
     * Convert trial to paid subscription.
     */
    public function convertToPaid(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        try {
            $plan = Plan::findOrFail($request->plan_id);

            // Create subscription
            $subscription = Subscription::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'billable_type' => Tenant::class,
                'billable_id' => $tenant->id,
                'name' => 'default',
                'plan_id' => $plan->id,
                'billing_cycle' => $request->billing_cycle,
                'amount' => $request->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price,
                'currency' => $plan->currency ?? config('cashier.currency', 'USD'),
                'status' => Subscription::STATUS_ACTIVE,
                'starts_at' => Carbon::now(),
                'ends_at' => $request->billing_cycle === 'yearly'
                    ? Carbon::now()->addYear()
                    : Carbon::now()->addMonth(),
            ]);

            // Clear trial subscription if present
            $trialSubscription = $tenant->subscription('default');
            if ($trialSubscription && $trialSubscription->id !== $subscription->id) {
                $trialSubscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            Log::info('Trial converted to paid', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'subscription_id' => $subscription->id,
                'converted_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trial successfully converted to paid subscription.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to convert trial', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to convert trial.',
            ], 500);
        }
    }

    /**
     * Update onboarding settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $settings = $request->input('settings', $request->all());

        if (! is_array($settings)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings payload.',
            ], 422);
        }

        if (array_key_exists('require_admin_approval', $settings) && ! array_key_exists('require_manual_approval', $settings)) {
            $settings['require_manual_approval'] = $settings['require_admin_approval'];
        }

        try {
            $setting = PlatformSetting::current();
            $prefs = (array) ($setting->admin_preferences ?? []);
            foreach ($settings as $key => $value) {
                data_set($prefs, "onboarding.{$key}", $value);
            }
            $setting->update(['admin_preferences' => $prefs]);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update onboarding settings', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings.',
            ], 500);
        }
    }

    /**
     * Toggle automation rule.
     */
    public function toggleAutomation(Request $request): JsonResponse
    {
        $request->validate([
            'rule_id' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        try {
            // Onboarding preferences live in the PlatformSetting singleton's
            // admin_preferences JSON bag (there is no key/value settings table).
            $setting = PlatformSetting::current();
            $prefs = (array) ($setting->admin_preferences ?? []);
            data_set($prefs, "onboarding.automation.{$request->rule_id}", (bool) $request->is_active);
            $setting->update(['admin_preferences' => $prefs]);

            return response()->json([
                'success' => true,
                'message' => $request->is_active ? 'Automation enabled.' : 'Automation disabled.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle automation', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle automation.',
            ], 500);
        }
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            if ($tenant->status === Tenant::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant is already suspended.',
                ], 422);
            }

            $previousStatus = $tenant->status;

            $tenant->update([
                'status' => Tenant::STATUS_SUSPENDED,
                'data' => array_merge($tenant->data?->toArray() ?? [], [
                    'suspension_reason' => $request->reason,
                    'suspended_at' => Carbon::now()->toISOString(),
                    'suspended_by' => Auth::guard('landlord')->id(),
                    'previous_status' => $previousStatus,
                ]),
            ]);

            // Clear stats cache
            $this->clearStatsCache();

            Log::info('Tenant suspended', [
                'tenant_id' => $tenant->id,
                'reason' => $request->reason,
                'suspended_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant has been suspended.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to suspend tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend tenant.',
            ], 500);
        }
    }

    /**
     * Reactivate a suspended tenant.
     */
    public function reactivate(Tenant $tenant): JsonResponse
    {
        try {
            if ($tenant->status !== Tenant::STATUS_SUSPENDED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only suspended tenants can be reactivated.',
                ], 422);
            }

            // Restore previous status or default to active
            $previousStatus = $tenant->data?->offsetExists('previous_status')
                ? $tenant->data['previous_status']
                : Tenant::STATUS_ACTIVE;

            $tenant->update([
                'status' => $previousStatus,
                'data' => array_merge($tenant->data?->toArray() ?? [], [
                    'reactivated_at' => Carbon::now()->toISOString(),
                    'reactivated_by' => Auth::guard('landlord')->id(),
                ]),
            ]);

            // Clear stats cache
            $this->clearStatsCache();

            Log::info('Tenant reactivated', [
                'tenant_id' => $tenant->id,
                'restored_status' => $previousStatus,
                'reactivated_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant has been reactivated.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reactivate tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate tenant.',
            ], 500);
        }
    }

    /**
     * Archive a tenant.
     */
    public function archive(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $tenant->update([
                'status' => Tenant::STATUS_ARCHIVED,
                'data' => array_merge($tenant->data?->toArray() ?? [], [
                    'archive_reason' => $request->reason ?? 'Manually archived by admin',
                    'archived_at' => Carbon::now()->toISOString(),
                    'archived_by' => Auth::guard('landlord')->id(),
                ]),
            ]);

            Log::info('Tenant archived', [
                'tenant_id' => $tenant->id,
                'reason' => $request->reason,
                'archived_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant has been archived.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to archive tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive tenant.',
            ], 500);
        }
    }

    /**
     * Cancel a tenant's trial.
     */
    public function cancelTrial(Request $request, Tenant $tenant): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $subscription = $tenant->subscription('default');
            if (! $subscription?->onTrial()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant does not have an active trial.',
                ], 422);
            }

            $subscription->update([
                'trial_ends_at' => null,
                'status' => Subscription::STATUS_CANCELLED,
            ]);

            $tenant->update([
                'status' => $tenant->subscriptions()
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->exists() ? $tenant->status : Tenant::STATUS_CANCELLED,
                'data' => array_merge($tenant->data?->toArray() ?? [], [
                    'trial_cancelled_at' => Carbon::now()->toISOString(),
                    'trial_cancelled_by' => Auth::guard('landlord')->id(),
                    'trial_cancellation_reason' => $request->reason ?? 'Cancelled by admin',
                ]),
            ]);

            $this->clearStatsCache();

            Log::info('Trial cancelled', [
                'tenant_id' => $tenant->id,
                'reason' => $request->reason,
                'cancelled_by' => Auth::guard('landlord')->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trial has been cancelled.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel trial', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel trial.',
            ], 500);
        }
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Get onboarding statistics.
     *
     * @return array<string, mixed>
     */
    private function getOnboardingStats(): array
    {
        return \Cache::remember('onboarding.dashboard.stats', 300, function () {
            $now = Carbon::now();

            return [
                'pendingRegistrations' => Tenant::where('status', Tenant::STATUS_PENDING)->count(),
                'activeTrials' => Tenant::whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->where('trial_ends_at', '>', $now))
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->count(),
                'cancelledTenants' => Tenant::where('status', Tenant::STATUS_CANCELLED)->count(),
                'conversionRate' => $this->calculateConversionRate(),
                'provisioningQueue' => Tenant::whereIn('status', [Tenant::STATUS_PROVISIONING, Tenant::STATUS_FAILED])->count(),
                'funnelStarted' => Tenant::whereMonth('created_at', $now->month)->count(),
                'funnelVerified' => Tenant::whereMonth('created_at', $now->month)
                    ->whereNotNull('company_email_verified_at')
                    ->count(),
                'funnelProvisioned' => Tenant::whereMonth('created_at', $now->month)
                    ->whereIn('status', [Tenant::STATUS_ACTIVE, Tenant::STATUS_PROVISIONING])
                    ->count(),
                'funnelActive' => Tenant::whereMonth('created_at', $now->month)
                    ->where('status', Tenant::STATUS_ACTIVE)
                    ->count(),
            ];
        });
    }

    /**
     * Clear onboarding stats cache.
     */
    private function clearStatsCache(): void
    {
        \Cache::forget('onboarding.dashboard.stats');
    }

    /**
     * Get recent registrations.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRecentRegistrations(): array
    {
        return Tenant::where('status', Tenant::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'companyName' => $tenant->name,
                'email' => $tenant->email,
                'status' => $this->getRegistrationStatus($tenant),
                'registeredAt' => $tenant->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Get expiring trials.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getExpiringTrials(): array
    {
        return Tenant::whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->whereNotNull('trial_ends_at')->whereBetween('trial_ends_at', [Carbon::now(), Carbon::now()->addDays(14)]))
            ->where('status', Tenant::STATUS_ACTIVE)
            ->limit(5)
            ->with('plan')
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'companyName' => $tenant->name,
                'plan' => $tenant->plan?->name ?? 'No Plan',
                'daysRemaining' => Carbon::now()->diffInDays($tenant->subscription('default')?->trial_ends_at),
                'expiresAt' => $tenant->subscription('default')?->trial_ends_at?->format('M d, Y'),
            ])
            ->toArray();
    }

    /**
     * Get provisioning queue.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getProvisioningQueue(): array
    {
        return Tenant::whereIn('status', [Tenant::STATUS_PROVISIONING, Tenant::STATUS_FAILED])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'tenantName' => $tenant->name,
                'status' => $tenant->status === Tenant::STATUS_FAILED ? 'failed' : ($tenant->provisioning_step ?? 'queued'),
                'database' => $tenant->subdomain,
                'startedAt' => $tenant->updated_at->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Get registration status label.
     */
    private function getRegistrationStatus(Tenant $tenant): string
    {
        if (! $tenant->company_email_verified_at) {
            return 'pending';
        }

        if ($tenant->registration_step !== Tenant::REG_STEP_PAYMENT) {
            return 'verified';
        }

        return 'approved';
    }

    /**
     * Calculate conversion rate.
     */
    private function calculateConversionRate(?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $query = Tenant::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totalTrials = (clone $query)->whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->whereNotNull('trial_ends_at'))->count();

        if ($totalTrials === 0) {
            return 0;
        }

        $converted = (clone $query)
            ->whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->whereNotNull('trial_ends_at'))
            ->whereHas('subscriptions', fn ($q) => $q->where('status', Subscription::STATUS_ACTIVE))
            ->count();

        return round(($converted / $totalTrials) * 100, 1);
    }

    /**
     * Get start date for period.
     */
    private function getStartDateForPeriod(string $period): Carbon
    {
        return match ($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'quarter' => Carbon::now()->subQuarter(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }

    /**
     * Get registration trend data.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getRegistrationTrend(Carbon $startDate, Carbon $endDate): array
    {
        $registrations = Tenant::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $registrations->map(fn ($item) => [
            'date' => $item->date,
            'count' => $item->count,
        ])->toArray();
    }

    /**
     * Get conversion funnel data.
     *
     * @return array<string, int>
     */
    private function getConversionFunnel(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'started' => Tenant::whereBetween('created_at', [$startDate, $endDate])->count(),
            'verified' => Tenant::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('company_email_verified_at')
                ->count(),
            'provisioned' => Tenant::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Tenant::STATUS_ACTIVE)
                ->count(),
            'subscribed' => Tenant::whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('subscriptions', fn ($q) => $q->where('status', Subscription::STATUS_ACTIVE))
                ->count(),
        ];
    }

    /**
     * Get plan distribution.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPlanDistribution(): array
    {
        return Subscription::selectRaw('plan_id, COUNT(DISTINCT billable_id) as count')
            ->where('billable_type', Tenant::class)
            ->whereNotNull('plan_id')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->groupBy('plan_id')
            ->with('plan:id,name')
            ->get()
            ->map(fn ($item) => [
                'plan' => $item->plan?->name ?? 'Unknown',
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get geographic distribution.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getGeographicDistribution(): array
    {
        // Using JSON data column for country info
        return DB::table('tenants')
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.country')) as country, COUNT(*) as count")
            ->where('status', Tenant::STATUS_ACTIVE)
            ->whereRaw("JSON_EXTRACT(data, '$.country') IS NOT NULL")
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'country' => $item->country ?? 'Unknown',
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Calculate average onboarding time.
     */
    private function calculateAverageOnboardingTime(Carbon $startDate, Carbon $endDate): array
    {
        $avgHours = Tenant::where('status', Tenant::STATUS_ACTIVE)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('updated_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
            ->first()
            ?->avg_hours ?? 0;

        return [
            'hours' => round($avgHours, 1),
            'days' => round($avgHours / 24, 1),
        ];
    }

    /**
     * Calculate average trial days.
     */
    private function calculateAverageTrialDays(Carbon $startDate, Carbon $endDate): float
    {
        $converted = Tenant::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->whereNotNull('trial_ends_at'))
            ->whereHas('subscriptions', fn ($q) => $q->where('status', Subscription::STATUS_ACTIVE))
            ->with('subscriptions')
            ->get();

        if ($converted->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        foreach ($converted as $tenant) {
            $subscription = $tenant->subscriptions->first();
            if ($subscription && $tenant->created_at && $subscription->starts_at) {
                $totalDays += $tenant->created_at->diffInDays($subscription->starts_at);
            }
        }

        return round($totalDays / $converted->count(), 1);
    }

    /**
     * Get automation rules.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAutomationRules(): array
    {
        return [
            [
                'id' => 'auto_approve_verified',
                'name' => 'Auto-approve Verified Registrations',
                'description' => 'Automatically approve registrations that have verified their email and completed all steps',
                'trigger' => 'On email verification complete',
                'is_active' => PlatformSetting::getValue('onboarding.automation.auto_approve_verified.is_active', false),
            ],
            [
                'id' => 'trial_expiry_reminder',
                'name' => 'Trial Expiry Reminder',
                'description' => 'Send reminder emails 7, 3, and 1 days before trial expires',
                'trigger' => 'Daily at 9:00 AM',
                'is_active' => PlatformSetting::getValue('onboarding.automation.trial_expiry_reminder.is_active', true),
            ],
            [
                'id' => 'cleanup_abandoned',
                'name' => 'Cleanup Abandoned Registrations',
                'description' => 'Archive registrations that have been pending for more than 30 days without verification',
                'trigger' => 'Weekly on Sunday',
                'is_active' => PlatformSetting::getValue('onboarding.automation.cleanup_abandoned.is_active', false),
            ],
            [
                'id' => 'welcome_sequence',
                'name' => 'Welcome Email Sequence',
                'description' => 'Send onboarding tips and guides over the first 7 days',
                'trigger' => 'On tenant activation',
                'is_active' => PlatformSetting::getValue('onboarding.automation.welcome_sequence.is_active', true),
            ],
            [
                'id' => 'failed_provisioning_alert',
                'name' => 'Failed Provisioning Alert',
                'description' => 'Send alert to admin when provisioning fails',
                'trigger' => 'On provisioning failure',
                'is_active' => PlatformSetting::getValue('onboarding.automation.failed_provisioning_alert.is_active', true),
            ],
        ];
    }

    /**
     * Get automation execution log.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAutomationExecutionLog(): array
    {
        // In a real implementation, this would come from a dedicated table
        return [];
    }

    /**
     * Get executions count.
     */
    private function getExecutionsCount(Carbon $date): int
    {
        // In a real implementation, this would query execution log
        return 0;
    }

    /**
     * Get automation success rate.
     */
    private function getAutomationSuccessRate(): float
    {
        // In a real implementation, this would calculate from execution log
        return 100.0;
    }

    /**
     * Get onboarding settings.
     *
     * @return array<string, mixed>
     */
    private function getOnboardingSettings(): array
    {
        return [
            'default_trial_days' => PlatformSetting::getValue('onboarding.default_trial_days', 14),
            'require_email_verification' => PlatformSetting::getValue('onboarding.require_email_verification', true),
            'require_phone_verification' => PlatformSetting::getValue('onboarding.require_phone_verification', false),
            'require_manual_approval' => PlatformSetting::getValue('onboarding.require_manual_approval', false),
            'allowed_domains' => PlatformSetting::getValue('onboarding.allowed_domains', ''),
            'blocked_domains' => PlatformSetting::getValue('onboarding.blocked_domains', 'tempmail.com,throwaway.com'),
            'max_registrations_per_ip' => PlatformSetting::getValue('onboarding.max_registrations_per_ip', 5),
            'enable_captcha' => PlatformSetting::getValue('onboarding.enable_captcha', true),
            'default_plan_id' => PlatformSetting::getValue('onboarding.default_plan_id', null),
        ];
    }

    /**
     * Get email templates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEmailTemplates(): array
    {
        return [
            [
                'id' => 'welcome',
                'name' => 'Welcome Email',
                'subject' => 'Welcome to EOS365!',
                'description' => 'Sent immediately after successful registration',
            ],
            [
                'id' => 'email_verification',
                'name' => 'Email Verification',
                'subject' => 'Verify your email address',
                'description' => 'Sent when user registers to verify email',
            ],
            [
                'id' => 'trial_started',
                'name' => 'Trial Started',
                'subject' => 'Your trial has started!',
                'description' => 'Sent when trial period begins',
            ],
            [
                'id' => 'trial_expiring',
                'name' => 'Trial Expiring',
                'subject' => 'Your trial expires soon',
                'description' => 'Sent 7, 3, and 1 days before trial ends',
            ],
            [
                'id' => 'trial_expired',
                'name' => 'Trial Expired',
                'subject' => 'Your trial has expired',
                'description' => 'Sent when trial period ends',
            ],
            [
                'id' => 'provisioning_complete',
                'name' => 'Setup Complete',
                'subject' => 'Your workspace is ready!',
                'description' => 'Sent when tenant provisioning completes',
            ],
        ];
    }
}
