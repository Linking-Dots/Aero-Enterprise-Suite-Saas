<?php

namespace Aero\Platform\Models;

use Aero\Platform\Database\Factories\TenantFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * EOS365 Tenant Model
 *
 * Represents a tenant (customer organization) in the multi-tenant SaaS platform.
 * Uses UUID primary key to prevent enumeration attacks.
 *
 * This model extends Stancl\Tenancy's base Tenant and implements:
 * - TenantWithDatabase: Enables automatic database creation/deletion
 * - HasDomains trait: Provides domain management functionality
 * - Billable trait: Enables Laravel Cashier for Stripe subscriptions
 *
 * @property string $id UUID primary key
 * @property \ArrayObject $data Flexible metadata storage (owner_name, address, etc.)
 * @property string $status Tenant status: pending, provisioning, active, failed, cancelled, suspended, archived
 * @property bool $maintenance_mode Whether tenant is in maintenance mode
 * @property Carbon|null $trial_ends_at (via subscription relation) Trial period end date
 * @property string|null $plan_id (via subscription relation) Foreign key to plans table
 * @property string|null $stripe_id Stripe Customer ID
 * @property string|null $pm_type Payment method type (card, etc.)
 * @property string|null $pm_last_four Last 4 digits of payment method
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable, HasDatabase, HasDomains, HasFactory, SoftDeletes;

    /**
     * Tenant status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROVISIONING = 'provisioning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * Provisioning step constants.
     */
    public const STEP_CREATING_DB = 'creating_db';

    public const STEP_MIGRATING = 'migrating';

    public const STEP_SEEDING = 'seeding';

    public const STEP_CREATING_ADMIN = 'creating_admin';

    /**
     * Registration step constants for tracking incomplete registrations.
     */
    public const REG_STEP_ACCOUNT_TYPE = 'account_type';

    public const REG_STEP_DETAILS = 'details';

    public const REG_STEP_ADMIN = 'admin';

    public const REG_STEP_VERIFY_EMAIL = 'verify_email';

    public const REG_STEP_VERIFY_PHONE = 'verify_phone';

    public const REG_STEP_PLAN = 'plan';

    public const REG_STEP_TRIAL = 'trial';

    public const REG_STEP_PAYMENT = 'payment';

    public const REG_STEP_PROVISIONING = 'provisioning';

    /**
     * Custom columns that are stored directly on the tenants table
     * (not in the JSON 'data' column).
     *
     * IMPORTANT: Any attribute listed here will be stored in its own
     * database column instead of being serialized into the 'data' JSON column.
     * This is crucial for:
     * - Indexing and query performance
     * - Foreign key relationships (plan_id)
     * - Filtering in database queries
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'type',
            'subdomain',
            'email',
            'phone',
            // Billing state is intentionally REMOVED from tenants table.
            // Plan, modules, trial, and subscription lifecycle now live in:
            //   - subscriptions          (base plan subscription)
            //   - subscription_modules     (module add-ons)
            //   - tenant_module pivot    (feature access registry)
            'status',
            'provisioning_step', // Async provisioning: creating_db, migrating, seeding, creating_admin
            'admin_data',        // Temporary admin credentials during provisioning
            'maintenance_mode',
            // Admin verification columns (legacy - kept for backward compatibility)
            'admin_email_verified_at',
            'admin_phone_verified_at',
            'admin_email_verification_code',
            'admin_email_verification_sent_at',
            'admin_phone_verification_code',
            'admin_phone_verification_sent_at',
            // Company verification columns (new - for verifying company contact info)
            'company_email_verified_at',
            'company_phone_verified_at',
            'company_email_verification_code',
            'company_email_verification_sent_at',
            'company_phone_verification_code',
            'company_phone_verification_sent_at',
            'registration_step',  // Track which step user left from for resume functionality
            // Stripe Cashier columns
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'stripe_trial_ends_at',
        ];
    }

    /**
     * The attributes that should be cast.
     *
     * Using AsArrayObject for 'data' allows partial updates without
     * overwriting the entire JSON structure.
     */
    protected function casts(): array
    {
        return [
            'data' => AsArrayObject::class,
            'admin_data' => AsArrayObject::class,
            'stripe_trial_ends_at' => 'datetime',
            'maintenance_mode' => 'boolean',
            'admin_email_verified_at' => 'datetime',
            'admin_phone_verified_at' => 'datetime',
            'admin_email_verification_sent_at' => 'datetime',
            'admin_phone_verification_sent_at' => 'datetime',
            'company_email_verified_at' => 'datetime',
            'company_phone_verified_at' => 'datetime',
            'company_email_verification_sent_at' => 'datetime',
            'company_phone_verification_sent_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get all domains associated with this tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get the primary domain for this tenant.
     */
    public function primaryDomain(): ?Domain
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    /**
     * Get the subscription plan for this tenant via the active subscription.
     */
    public function getPlanAttribute(): ?Plan
    {
        return $this->currentSubscription?->plan;
    }

    /**
     * Get the current (most recent active) subscription.
     *
     * Uses the polymorphic billable relation so Cashier and lifecycle
     * services both query the same unified subscription model.
     */
    public function currentSubscription(): MorphOne
    {
        return $this->morphOne(Subscription::class, 'billable')
            ->ofMany(
                ['created_at' => 'max'],
                fn ($query) => $query->where('status', 'active')
            );
    }

    /**
     * Get all module add-on subscriptions for this tenant.
     */
    public function moduleSubscriptions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(SubscriptionModule::class, 'billable');
    }

    /**
     * Get all invoices for this tenant (polymorphic billable).
     */
    public function invoices(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable')
            ->orderByDesc('created_at');
    }

    /**
     * Get all modules subscribed by this tenant (feature access registry).
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_module')
            ->withPivot('is_active', 'subscribed_at', 'unsubscribed_at')
            ->wherePivot('is_active', true)
            ->withTimestamps();
    }

    /**
     * Get active module codes for this tenant.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveModules(): Collection
    {
        return $this->modules()->pluck('code');
    }

    /**
     * Alias for currentSubscription() for backward compatibility.
     */
    public function activeSubscription(): MorphOne
    {
        return $this->currentSubscription();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter only active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter tenants currently in trial.
     */
    public function scopeOnTrial($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->where('status', Subscription::STATUS_TRIALING)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now());
        });
    }

    /**
     * Scope to filter suspended tenants.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope to filter tenants currently provisioning.
     */
    public function scopeProvisioning($query)
    {
        return $query->where('status', self::STATUS_PROVISIONING);
    }

    /**
     * Scope to filter failed tenants.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter cancelled tenants.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the tenant is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the tenant is currently provisioning.
     */
    public function isProvisioning(): bool
    {
        return $this->status === self::STATUS_PROVISIONING;
    }

    /**
     * Check if the tenant provisioning has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the tenant has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the tenant is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->subscription('default')?->trial_ends_at?->isFuture() ?? false;
    }

    /**
     * Check if the tenant's trial has expired.
     */
    public function hasTrialExpired(): bool
    {
        return $this->subscription('default')?->trial_ends_at?->isPast() ?? false;
    }

    /**
     * Check if the tenant is in maintenance mode.
     */
    public function isInMaintenance(): bool
    {
        return $this->maintenance_mode === true;
    }

    /**
     * Get the owner information from the data column.
     *
     * Owner info is stored in data JSON to avoid schema changes
     * when adding new owner fields.
     *
     * @return array{name?: string, email?: string, phone?: string}
     */
    public function getOwnerAttribute(): array
    {
        return [
            'name' => $this->data['owner_name'] ?? null,
            'email' => $this->data['owner_email'] ?? null,
            'phone' => $this->data['owner_phone'] ?? null,
        ];
    }

    /**
     * Activate the tenant (change status from pending/provisioning to active).
     * Clears admin_data after successful activation.
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'provisioning_step' => null,
            'admin_data' => null,
        ]);
    }

    /**
     * Start the provisioning process.
     *
     * @param  string  $step  Initial provisioning step
     */
    public function startProvisioning(string $step = self::STEP_CREATING_DB): bool
    {
        return $this->update([
            'status' => self::STATUS_PROVISIONING,
            'provisioning_step' => $step,
        ]);
    }

    /**
     * Update the current provisioning step.
     */
    public function updateProvisioningStep(string $step): bool
    {
        return $this->update(['provisioning_step' => $step]);
    }

    /**
     * Mark provisioning as failed.
     *
     * @param  string|null  $reason  Failure reason to store in data column
     */
    public function markProvisioningFailed(?string $reason = null): bool
    {
        // Use update() with explicit data array to avoid ArrayObject mutation-detection issues.
        // Mutating data in-place and then re-assigning can silently fail to dirty the attribute.
        $updateData = ['status' => self::STATUS_FAILED];

        if ($reason) {
            $currentData = $this->data ? $this->data->getArrayCopy() : [];
            $currentData['provisioning_error'] = $reason;
            $currentData['provisioning_failed_at'] = now()->toIso8601String();
            $updateData['data'] = $currentData;
        }

        return $this->update($updateData);
    }

    /**
     * Clear admin data after admin user has been created.
     * Important for security - credentials should not persist.
     */
    public function clearAdminData(): bool
    {
        return $this->update(['admin_data' => null]);
    }

    /**
     * Check if the tenant has an active module subscription for a specific module.
     *
     * This is the core gating method used by CheckModuleAccess middleware.
     * Plans and products/modules are separate concerns:
     * - Plan subscription controls limits (users, storage).
     * - Module access is determined by the tenant_module pivot and
     *   independent module subscriptions (subscription_modules table).
     *
     * @param  string  $moduleName  Module code e.g., 'hrm', 'crm'
     */
    public function hasActiveSubscription(string $moduleName): bool
    {
        // Check 1: Active module in tenant_module pivot (covers manual grants + synced modules)
        if ($this->modules()->where('code', $moduleName)->exists()) {
            return true;
        }

        // Check 2: Active module subscription via subscription_modules table
        $hasModuleSub = $this->moduleSubscriptions()
            ->where('module_code', $moduleName)
            ->where(function ($q) {
                $q->where('status', SubscriptionModule::STATUS_ACTIVE)
                    ->orWhere('status', SubscriptionModule::STATUS_TRIALING);
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->exists();

        return $hasModuleSub;
    }

    /**
     * Suspend the tenant.
     */
    public function suspend(?string $reason = null): bool
    {
        $data = $this->data ?? new \ArrayObject;
        $data['suspension_reason'] = $reason;
        $data['suspended_at'] = now()->toIso8601String();
        $this->data = $data;
        $this->status = self::STATUS_SUSPENDED;

        return $this->save();
    }

    /**
     * Enable maintenance mode for this tenant.
     */
    public function enableMaintenance(): bool
    {
        return $this->update(['maintenance_mode' => true]);
    }

    /**
     * Disable maintenance mode for this tenant.
     */
    public function disableMaintenance(): bool
    {
        return $this->update(['maintenance_mode' => false]);
    }

    // =========================================================================
    // STRIPE CASHIER OVERRIDES
    // =========================================================================

    /**
     * Get the email address used to create the Stripe customer.
     *
     * This returns the tenant owner's email from the data column,
     * which is the primary contact for billing communications.
     */
    public function stripeEmail(): ?string
    {
        return $this->data['owner_email'] ?? $this->email;
    }

    /**
     * Get the name for the Stripe customer.
     *
     * Uses the company name for business tenants, or owner name for individuals.
     */
    public function stripeName(): ?string
    {
        if ($this->type === 'company') {
            return $this->name;
        }

        return $this->data['owner_name'] ?? $this->name;
    }

    /**
     * Get the phone number for the Stripe customer.
     */
    public function stripePhone(): ?string
    {
        return $this->data['owner_phone'] ?? $this->phone;
    }

    /**
     * Get the address for the Stripe customer.
     *
     * @return array<string, string|null>
     */
    public function stripeAddress(): ?array
    {
        $billingAddress = $this->billingAddress;

        if (! $billingAddress) {
            return null;
        }

        return [
            'line1' => $billingAddress->address_line1,
            'line2' => $billingAddress->address_line2,
            'city' => $billingAddress->city,
            'state' => $billingAddress->state,
            'postal_code' => $billingAddress->postal_code,
            'country' => $billingAddress->country,
        ];
    }

    /**
     * Get metadata to store on the Stripe customer.
     *
     * @return array<string, string>
     */
    public function stripeMetadata(): array
    {
        return [
            'tenant_id' => $this->id,
            'subdomain' => $this->subdomain,
            'type' => $this->type,
        ];
    }

    // =========================================================================
    // BILLING RELATIONSHIPS
    // =========================================================================

    /**
     * Get the tenant's billing address.
     */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(TenantBillingAddress::class, 'tenant_id');
    }

    // =========================================================================
    // BILLING HELPER METHODS
    // =========================================================================

    /**
     * Check if tenant has an active Stripe subscription.
     */
    public function hasActiveStripeSubscription(): bool
    {
        return $this->subscribed('default');
    }

    /**
     * Get the current Stripe subscription price.
     */
    public function currentStripePlan(): ?string
    {
        $subscription = $this->subscription('default');

        return $subscription?->stripe_price;
    }

    /**
     * Check if tenant is on a specific plan by Stripe price ID.
     */
    public function isOnStripePlan(string $priceId): bool
    {
        return $this->subscribedToPrice($priceId, 'default');
    }

    // =========================================================================
    // ADMIN SETUP HELPER METHODS
    // =========================================================================

    /**
     * Check if admin setup has been completed for this tenant.
     *
     * This checks the admin_setup_completed flag in the data column.
     * This flag is set when the first admin user is created via AdminSetupController.
     */
    public function isAdminSetupComplete(): bool
    {
        $data = $this->data instanceof \ArrayObject
            ? $this->data->getArrayCopy()
            : (array) ($this->data ?? []);

        return ! empty($data['admin_setup_completed']);
    }

    /**
     * Get the timestamp when admin setup was completed.
     */
    public function getAdminSetupCompletedAt(): ?string
    {
        $data = $this->data instanceof \ArrayObject
            ? $this->data->getArrayCopy()
            : (array) ($this->data ?? []);

        return $data['admin_setup_completed_at'] ?? null;
    }

    /**
     * Get the database size in bytes for this tenant.
     *
     * Uses information_schema to calculate total size of all tables.
     * Returns null if unable to determine (database not accessible).
     *
     * @return array{size_bytes: int|null, size_formatted: string|null, table_count: int|null}|null
     */
    public function getDatabaseSize(): ?array
    {
        try {
            $dbName = $this->database()?->getName();

            if (! $dbName) {
                return null;
            }

            $result = DB::select(
                'SELECT
                    SUM(data_length + index_length) AS size_bytes,
                    COUNT(*) AS table_count
                FROM information_schema.tables
                WHERE table_schema = ?',
                [$dbName]
            );

            if (empty($result)) {
                return null;
            }

            $sizeBytes = (int) ($result[0]->size_bytes ?? 0);
            $tableCount = (int) ($result[0]->table_count ?? 0);

            return [
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'table_count' => $tableCount,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format bytes into human-readable size.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
