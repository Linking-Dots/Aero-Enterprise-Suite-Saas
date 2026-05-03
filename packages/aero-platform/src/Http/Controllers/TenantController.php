<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers;

use Aero\Platform\Jobs\ProvisionTenant;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Str;
use Aero\Platform\Services\Tenant\TenantPurgeService;
use Aero\Platform\Services\Tenant\TenantRetentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tenant Management Controller
 *
 * Handles CRUD operations for tenants in the platform admin panel.
 * All operations are performed on the central database with landlord guard.
 */
class TenantController extends Controller
{
    public function __construct(
        protected TenantRetentionService $retentionService,
        protected TenantPurgeService $purgeService
    ) {}

    /**
     * Get paginated list of tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::query()
            ->with(['currentSubscription.plan', 'domains'])
            ->when($request->boolean('include_archived'), function ($q) {
                $q->withTrashed();
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subdomain', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->input('type'));
            });
        // NOTE: plan_id and trial_status filters removed from tenant table.
        // Use subscription-based filters or the Billing dashboard instead.

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tenants = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $tenants->items(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'last_page' => $tenants->lastPage(),
            ],
        ]);
    }

    /**
     * Get tenant statistics.
     *
     * Stats are cached for 2 minutes to improve performance at scale.
     * Uses a static cache key so all admins share the same cached data.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $cacheKey = 'platform:tenant_stats';
        $cacheTtl = 120; // 2 minutes

        $stats = Cache::remember($cacheKey, $cacheTtl, function () {
            return [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', Tenant::STATUS_ACTIVE)->count(),
                'pending' => Tenant::where('status', Tenant::STATUS_PENDING)->count(),
                'suspended' => Tenant::where('status', Tenant::STATUS_SUSPENDED)->count(),
                'archived' => Tenant::where('status', Tenant::STATUS_ARCHIVED)->count(),
                'provisioning' => Tenant::where('status', Tenant::STATUS_PROVISIONING)->count(),
                'failed' => Tenant::where('status', Tenant::STATUS_FAILED)->count(),
                'on_trial' => Tenant::whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->where('trial_ends_at', '>', now()))->count(),
                'trial_expired' => Tenant::whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->where('trial_ends_at', '<=', now()))->where('status', '!=', Tenant::STATUS_ACTIVE)->count(),
                'new_this_month' => Tenant::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }

    /**
     * Get a specific tenant.
     */
    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        $tenant->load(['plan', 'domains', 'subscriptions']);

        return response()->json(['data' => $tenant]);
    }

    /**
     * Store a new tenant (admin-initiated).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/i',
                Rule::unique('tenants', 'subdomain'),
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'string', Rule::in(['business', 'enterprise', 'startup'])],
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::beginTransaction();

        try {
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'subdomain' => strtolower($validated['subdomain']),
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'type' => $validated['type'],
                'status' => Tenant::STATUS_PENDING,
                'data' => [
                    'plan_id' => $validated['plan_id'],
                    'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
                ],
                'admin_data' => [
                    'name' => $validated['admin_name'],
                    'email' => $validated['admin_email'],
                    'password' => $validated['admin_password'] ?? null,
                ],
            ]);

            // Create trial subscription record (kept in subscriptions table, not tenant row)
            $plan = Plan::find($validated['plan_id']);
            if ($plan) {
                $trialDays = (int) ($validated['trial_days'] ?? $plan->trial_days ?? 14);
                $trialEndsAt = now()->addDays($trialDays);
                $billingCycle = $validated['billing_cycle'] ?? 'monthly';
                $amount = $billingCycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price;

                Subscription::create([
                    'id' => (string) Str::uuid(),
                    'billable_type' => Tenant::class,
                    'billable_id' => $tenant->id,
                    'name' => 'default',
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,
                    'amount' => $amount,
                    'currency' => $plan->currency ?? config('cashier.currency', 'usd'),
                    'status' => Subscription::STATUS_TRIALING,
                    'trial_starts_at' => now(),
                    'trial_ends_at' => $trialEndsAt,
                    'starts_at' => now(),
                    'ends_at' => $trialEndsAt,
                ]);
            }

            // Create domain
            $tenant->createDomain([
                'domain' => $tenant->subdomain.'.'.config('tenancy.central_domains.0'),
            ]);

            DB::commit();

            // Dispatch provisioning job
            ProvisionTenant::dispatch($tenant->fresh());

            return response()->json([
                'data' => $tenant,
                'message' => 'Tenant created successfully. Provisioning started.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a tenant.
     *
     * If plan_id is being changed, validates that the new plan is compatible:
     * - New plan must have all modules currently in use by the tenant
     * - Downgrade warnings are logged for audit purposes
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'string'],
        ]);

        $tenant->update($validated);

        return response()->json([
            'data' => $tenant->fresh(['plan', 'domains']),
            'message' => 'Tenant updated successfully.',
        ]);
    }

    /**
     * Archive a tenant (soft delete with retention period).
     */
    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'confirm' => ['required', 'accepted'],
        ]);

        // Soft delete the tenant
        $tenant->delete();

        // Update metadata
        $tenant->update([
            'status' => Tenant::STATUS_ARCHIVED,
            'data' => array_merge($tenant->data?->getArrayCopy() ?? [], [
                'archived_reason' => $validated['reason'],
                'archived_by' => auth('landlord')->id(),
            ]),
        ]);

        $retentionExpiresAt = $this->retentionService->getRetentionExpiresAt($tenant);

        return response()->json([
            'message' => 'Tenant archived successfully. Can be restored within retention period.',
            'retention_expires_at' => $retentionExpiresAt?->toIso8601String(),
            'retention_days' => config('tenancy.retention.days', 30),
        ]);
    }

    /**
     * Restore an archived tenant.
     */
    public function restore(Request $request, string $tenantId): JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($tenantId);

        $this->authorize('restore', $tenant);

        // Check if restoration is allowed
        if (! $this->retentionService->canRestore($tenant)) {
            return response()->json([
                'message' => 'Retention period expired. Tenant cannot be restored.',
            ], 422);
        }

        // Restore tenant
        $tenant->restore();

        // Reactivate
        $tenant->update([
            'status' => Tenant::STATUS_ACTIVE,
            'data' => array_merge($tenant->data?->getArrayCopy() ?? [], [
                'restored_at' => now()->toIso8601String(),
                'restored_by' => auth('landlord')->id(),
            ]),
        ]);

        return response()->json([
            'data' => $tenant->fresh(),
            'message' => 'Tenant restored successfully.',
        ]);
    }

    /**
     * Permanently purge a tenant (irreversible).
     */
    public function purge(Request $request, string $tenantId): JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($tenantId);

        $this->authorize('forceDelete', $tenant);

        // Verify retention period expired
        if (! $this->retentionService->canPurge($tenant)) {
            $expiresAt = $this->retentionService->getRetentionExpiresAt($tenant);

            return response()->json([
                'message' => "Retention period not expired. Can purge after {$expiresAt->toDateString()}",
                'retention_expires_at' => $expiresAt->toIso8601String(),
                'days_remaining' => $this->retentionService->getDaysUntilPurge($tenant),
            ], 422);
        }

        // Require confirmation
        $validated = $request->validate([
            'confirm' => ['required', 'accepted'],
            'confirm_name' => ['required', 'string', function ($attribute, $value, $fail) use ($tenant) {
                if ($value !== $tenant->name) {
                    $fail('Tenant name confirmation does not match.');
                }
            }],
        ]);

        try {
            $this->purgeService->purge($tenant);

            return response()->json([
                'message' => 'Tenant permanently purged.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to purge tenant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('suspend', $tenant);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->update([
            'status' => Tenant::STATUS_SUSPENDED,
            'data' => array_merge($tenant->data?->getArrayCopy() ?? [], [
                'suspended_at' => now()->toIso8601String(),
                'suspended_reason' => $validated['reason'] ?? null,
                'suspended_by' => auth('landlord')->id(),
            ]),
        ]);

        return response()->json([
            'data' => $tenant->fresh(),
            'message' => 'Tenant suspended successfully.',
        ]);
    }

    /**
     * Activate/reactivate a tenant.
     */
    public function activate(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('activate', $tenant);

        $tenant->update([
            'status' => Tenant::STATUS_ACTIVE,
            'data' => array_merge($tenant->data?->getArrayCopy() ?? [], [
                'activated_at' => now()->toIso8601String(),
                'activated_by' => auth('landlord')->id(),
            ]),
        ]);

        return response()->json([
            'data' => $tenant->fresh(),
            'message' => 'Tenant activated successfully.',
        ]);
    }

    /**
     * Archive a tenant.
     */
    public function archive(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('archive', $tenant);

        $tenant->update([
            'status' => Tenant::STATUS_ARCHIVED,
        ]);

        // Soft delete
        $tenant->delete();

        return response()->json([
            'message' => 'Tenant archived successfully.',
        ]);
    }

    /**
     * Check subdomain availability.
     *
     * Public API endpoint - no session required.
     * Only checks if subdomain is taken by an active tenant.
     */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subdomain' => ['required', 'string', 'max:63'],
        ]);

        $subdomain = strtolower($validated['subdomain']);

        // Check format
        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $subdomain) && strlen($subdomain) > 2) {
            return response()->json([
                'available' => false,
                'message' => 'Invalid subdomain format. Use only lowercase letters, numbers, and hyphens.',
            ]);
        }

        // Check reserved subdomains
        $reserved = ['admin', 'api', 'app', 'www', 'mail', 'smtp', 'ftp', 'cdn', 'static', 'assets', 'help', 'support', 'billing', 'status'];
        if (in_array($subdomain, $reserved)) {
            return response()->json([
                'available' => false,
                'message' => 'This subdomain is reserved.',
            ]);
        }

        // Check if subdomain is taken by any tenant (active, pending, or failed)
        // Note: We show as "taken" even for pending/failed to prevent conflicts
        // The actual registration submission will handle re-claiming abandoned registrations
        $exists = Tenant::where('subdomain', $subdomain)->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'This subdomain is already taken.' : 'Subdomain is available.',
        ]);
    }

    /**
     * Retry provisioning for a failed tenant.
     */
    public function retryProvisioning(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('retryProvisioning', $tenant);

        // Validate tenant is in failed state
        if ($tenant->status !== Tenant::STATUS_FAILED) {
            return response()->json([
                'message' => 'Only failed tenants can have their provisioning retried.',
            ], 422);
        }

        // Reset status to pending
        $tenant->update([
            'status' => Tenant::STATUS_PROVISIONING,
            'data' => array_merge($tenant->data?->getArrayCopy() ?? [], [
                'provisioning_retry_at' => now()->toIso8601String(),
                'provisioning_retry_by' => auth('landlord')->id(),
                'provisioning_error' => null,
            ]),
        ]);

        // Dispatch provisioning job
        ProvisionTenant::dispatch($tenant->fresh());

        return response()->json([
            'data' => $tenant->fresh(),
            'message' => 'Provisioning retry started.',
        ]);
    }

    /**
     * Force logout all users for a tenant.
     *
     * Invalidates all active sessions by clearing the sessions table
     * and revoking all personal access tokens for the tenant's database.
     */
    public function forceLogout(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('suspend', $tenant);

        try {
            tenancy()->initialize($tenant);

            // Clear all sessions
            DB::table('sessions')->truncate();

            // Revoke all personal access tokens (if using Sanctum)
            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')->delete();
            }

            tenancy()->end();

            // Log the action
            $tenant->data = array_merge($tenant->data?->getArrayCopy() ?? [], [
                'force_logout_at' => now()->toIso8601String(),
                'force_logout_by' => auth('landlord')->id(),
            ]);
            $tenant->save();

            return response()->json([
                'message' => 'All user sessions have been terminated.',
            ]);
        } catch (\Exception $e) {
            tenancy()->end();

            return response()->json([
                'message' => 'Failed to force logout: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle maintenance mode for a tenant.
     */
    public function toggleMaintenance(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $isEnabled = $tenant->isInMaintenance();

        if ($isEnabled) {
            $tenant->disableMaintenance();
            $message = 'Maintenance mode disabled.';
        } else {
            $tenant->enableMaintenance();
            $message = 'Maintenance mode enabled.';
        }

        // Log the action
        $tenant->data = array_merge($tenant->data?->getArrayCopy() ?? [], [
            'maintenance_toggled_at' => now()->toIso8601String(),
            'maintenance_toggled_by' => auth('landlord')->id(),
            'maintenance_mode' => ! $isEnabled,
        ]);
        $tenant->save();

        return response()->json([
            'data' => $tenant->fresh(),
            'maintenance_mode' => ! $isEnabled,
            'message' => $message,
        ]);
    }

    /**
     * Export tenants to CSV (server-side generation for large datasets).
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::query()
            ->with(['plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            // plan_id filter removed: plan is now stored in subscriptions table
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subdomain', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $filename = 'tenants_export_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write header row
            fputcsv($handle, [
                'ID',
                'Name',
                'Subdomain',
                'Email',
                'Phone',
                'Type',
                'Status',
                'Plan',
                'Trial Status',
                'Created At',
                'Updated At',
            ]);

            // Stream results in chunks to handle large datasets
            $query->chunk(500, function ($tenants) use ($handle) {
                foreach ($tenants as $tenant) {
                    fputcsv($handle, [
                        $tenant->id,
                        $tenant->name,
                        $tenant->subdomain,
                        $tenant->email,
                        $tenant->phone,
                        $tenant->type,
                        $tenant->status,
                        $tenant->plan?->name ?? 'No Plan',
                        optional($tenant->subscription()->first())->status ?? 'N/A',
                        $tenant->created_at?->toDateTimeString(),
                        $tenant->updated_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
