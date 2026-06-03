<?php

namespace Aero\Core\Models;

use Aero\Contracts\Searchable;
use Aero\Contracts\UserContract;
use Aero\Core\Database\Factories\UserFactory;
use Aero\Core\Models\Concerns\EnforcesTenantContext;
use Aero\Core\Services\UserRelationshipRegistry;
use Aero\Core\Traits\Searchable as SearchableTrait;
use Aero\Core\Traits\Taggable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * User Model - Core Authentication & Account Management
 *
 * This model is FULLY INDEPENDENT and handles ONLY core functionality:
 * - Login credentials (email, password, OAuth)
 * - Two-factor authentication (when Fortify installed)
 * - Device management
 * - Account status (active, locked)
 * - Roles & permissions
 * - Basic profile
 *
 * Implements UserContract interface to allow modules to depend on user
 * functionality without direct model coupling.
 *
 * Module-specific relationships (Employee, Attendance, Leaves, etc.) are
 * registered dynamically by their respective module providers via
 * UserRelationshipRegistry.
 *
 * Modules can extend User with dynamic relationships:
 *   $registry = app(UserRelationshipRegistry::class);
 *   $registry->registerRelationship('employee', fn($user) => $user->hasOne(Employee::class));
 *
 * Then use as normal: $user->employee
 *
 * Optional features (auto-enabled when packages installed):
 * - TwoFactorAuthenticatable: laravel/fortify
 * - InteractsWithMedia: spatie/laravel-medialibrary
 * - HasPushSubscriptions: laravel-notification-channels/webpush
 *
 * @property int $id
 * @property string $name
 * @property string|null $user_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $password
 * @property bool $active
 */
class User extends Authenticatable implements MustVerifyEmail, UserContract, Searchable
{
    use EnforcesTenantContext;
    use HasFactory;
    use Notifiable;
    use SearchableTrait;
    use SoftDeletes;
    use Taggable;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * Core authentication and profile fields only.
     * Module-specific fields should be on their respective module models.
     */
    protected $fillable = [
        // Core Identity
        'name',
        'user_name',
        'email',
        'phone',
        'password',

        // Account Status
        'active',
        'is_active',
        'account_locked_at',
        'locked_reason',
        'force_password_reset',

        // Profile basics
        'profile_image',
        'about',
        'locale',

        // Verification
        'email_verified_at',
        'phone_verified_at',
        'phone_verification_code',
        'phone_verification_sent_at',

        // OAuth / Social Login
        'oauth_provider',
        'oauth_provider_id',
        'oauth_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
        'avatar_url',

        // Device Management
        'single_device_login_enabled',
        'device_reset_at',
        'device_reset_reason',

        // Push Notifications
        'fcm_token',

        // Preferences
        'preferences',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'oauth_token',
        'oauth_refresh_token',
        'phone_verification_code',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'phone_verification_sent_at' => 'datetime',
        'password' => 'hashed',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'account_locked_at' => 'datetime',
        'single_device_login_enabled' => 'boolean',
        'device_reset_at' => 'datetime',
        'oauth_token_expires_at' => 'datetime',
        'preferences' => 'array',
        'notification_preferences' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'profile_image_url',
    ];

    // =========================================================================
    // DYNAMIC RELATIONSHIP RESOLUTION (Module Extension Point)
    // =========================================================================

    /**
     * Handle dynamic method calls for module-registered relationships.
     *
     * Modules register relationships via UserRelationshipRegistry:
     *   $registry->registerRelationship('employee', fn($user) => $user->hasOne(Employee::class));
     *
     * Then they can be used normally: $user->employee
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Check if this is a dynamically registered relationship
        $registry = app(UserRelationshipRegistry::class);

        if ($registry->hasRelationship($method)) {
            $callback = $registry->getRelationship($method);

            return $callback($this);
        }

        // Fall back to parent (handles scopes, etc.)
        return parent::__call($method, $parameters);
    }

    /**
     * Handle dynamic scope calls from modules.
     *
     * Usage: User::dynamic('withEmployeeRelations')->get()
     *
     * @param  Builder  $query
     * @param  mixed  ...$parameters
     * @return Builder
     */
    public function scopeDynamic($query, string $scopeName, ...$parameters)
    {
        $registry = app(UserRelationshipRegistry::class);

        if ($registry->hasScope($scopeName)) {
            $callback = $registry->getScope($scopeName);

            return $callback($query, ...$parameters);
        }

        return $query;
    }

    /**
     * Check if a dynamic relationship is available.
     */
    public function hasDynamicRelationship(string $name): bool
    {
        return app(UserRelationshipRegistry::class)->hasRelationship($name);
    }

    /**
     * Get available dynamic relationships.
     */
    public function getDynamicRelationships(): array
    {
        return app(UserRelationshipRegistry::class)->getRelationshipNames();
    }

    // =========================================================================
    // QUERY SCOPES (Core Only)
    // =========================================================================

    /**
     * Load core relations for user management.
     */
    public function scopeWithCoreRelations($query)
    {
        return $query->with([
            'roles:id,name',
            'currentDevice:id,user_id,device_name,device_type,last_used_at,is_active',
        ]);
    }

    /**
     * Load device information.
     */
    public function scopeWithDeviceInfo($query)
    {
        return $query->with([
            'currentDevice:id,user_id,device_name,device_type,last_used_at,is_active',
        ]);
    }

    /**
     * Active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Inactive users only.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    // =========================================================================
    // ACCOUNT STATUS MANAGEMENT
    // =========================================================================

    /**
     * Set active status (with soft delete handling).
     */
    public function setActiveStatus(bool $status): void
    {
        if ($status) {
            if ($this->trashed()) {
                $this->restore();
            }
            $this->active = true;
        } else {
            $this->active = false;
            $this->delete();
        }
        $this->save();
    }

    /**
     * Lock the user account.
     */
    public function lockAccount(?string $reason = null): bool
    {
        return $this->update([
            'account_locked_at' => now(),
            'locked_reason' => $reason,
        ]);
    }

    /**
     * Unlock the user account.
     */
    public function unlockAccount(): bool
    {
        return $this->update([
            'account_locked_at' => null,
            'locked_reason' => null,
        ]);
    }

    /**
     * Check if account is locked.
     */
    public function isLocked(): bool
    {
        return $this->account_locked_at !== null;
    }

    // =========================================================================
    // ROLES & PERMISSIONS (Uses HRMAC Package)
    // =========================================================================

    /**
     * Scope a query to only include users with a specific role.
     * Enables: User::role('Employee')->get()
     *
     * @param  Builder  $query
     * @param  string|array  $roles
     */
    public function scopeRole($query, $roles): Builder
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $query->whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('name', $roles);
        });
    }

    /**
     * Get the Role model class from config.
     */
    protected function getRoleModel(): string
    {
        return config('hrmac.models.role', \Aero\HRMAC\Models\Role::class);
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            $this->getRoleModel(),
            'model_has_roles',
            'model_id',
            'role_id'
        )->where('model_has_roles.model_type', static::class);
    }

    /**
     * Role names for this user (Spatie-compatible).
     *
     * Callers (UserProfileController, UserImpersonationService) expect a
     * Collection of role-name strings. User uses custom roles (not Spatie), so
     * this must be defined explicitly — otherwise /profile 500s with
     * "Call to undefined method getRoleNames()".
     */
    public function getRoleNames(): \Illuminate\Support\Collection
    {
        return $this->roles()->pluck('name');
    }

    /**
     * Human-readable label for audit trails.
     *
     * AuditService::log() calls $subject->getAuditLabel(). User extends
     * Authenticatable (not TenantModel/CentralModel, which provide this), so it
     * must define its own — otherwise every audited auth event throws
     * "undefined method getAuditLabel" (swallowed, but spams the log).
     */
    public function getAuditLabel(): ?string
    {
        return $this->name ?? $this->email ?? "User #{$this->getKey()}";
    }

    /**
     * Check if user has a specific role.
     *
     * @param  string|array|object  $role  Role name, Role object, or array of role names
     */
    public function hasRole($role): bool
    {
        // Handle array of role names - return true if user has ANY of them.
        // Flatten first: callers sometimes pass the guard-scoped super_admin_roles
        // config (['web' => [...], 'landlord' => [...]]) — a nested array would
        // make whereIn throw "Nested arrays may not be passed to whereIn".
        if (is_array($role)) {
            $names = [];
            array_walk_recursive($role, function ($r) use (&$names) {
                if (is_string($r)) {
                    $names[] = $r;
                }
            });

            return ! empty($names) && $this->roles()->whereIn('name', $names)->exists();
        }

        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }

        if (is_object($role) && isset($role->id)) {
            return $this->roles()->where('id', $role->id)->exists();
        }

        return false;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(...$roles): self
    {
        $roleModel = $this->getRoleModel();
        $roleIds = collect($roles)
            ->flatten()
            ->map(function ($role) use ($roleModel) {
                if (is_string($role)) {
                    return $roleModel::where('name', $role)->first()?->id;
                }
                if (is_object($role) && isset($role->id)) {
                    return $role->id;
                }

                return is_numeric($role) ? (int) $role : null;
            })
            ->filter()
            ->toArray();

        foreach ($roleIds as $roleId) {
            \DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $roleId,
                'model_type' => static::class,
                'model_id' => $this->id,
            ]);
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole($role): self
    {
        $roleModel = $this->getRoleModel();
        $roleId = null;
        if (is_string($role)) {
            $roleId = $roleModel::where('name', $role)->first()?->id;
        } elseif (is_object($role) && isset($role->id)) {
            $roleId = $role->id;
        } elseif (is_numeric($role)) {
            $roleId = (int) $role;
        }

        if ($roleId) {
            \DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', static::class)
                ->where('model_id', $this->id)
                ->delete();
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(...$roles): self
    {
        $roleModel = $this->getRoleModel();
        $roleIds = collect($roles)
            ->flatten()
            ->map(function ($role) use ($roleModel) {
                if (is_string($role)) {
                    return $roleModel::where('name', $role)->first()?->id;
                }
                if (is_object($role) && isset($role->id)) {
                    return $role->id;
                }

                return is_numeric($role) ? (int) $role : null;
            })
            ->filter()
            ->toArray();

        // Delete existing roles
        \DB::table('model_has_roles')
            ->where('model_type', static::class)
            ->where('model_id', $this->id)
            ->delete();

        // Insert new roles
        foreach ($roleIds as $roleId) {
            \DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => static::class,
                'model_id' => $this->id,
            ]);
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles, $guard = null): bool
    {
        // Flatten: callers (e.g. isSuperAdmin) pass the guard-scoped
        // super_admin_roles config (['web' => [...], 'landlord' => [...]]); a
        // nested array makes whereIn throw "Nested arrays may not be passed".
        $rolesArray = (array) $roles;
        $names = [];
        array_walk_recursive($rolesArray, function ($r) use (&$names) {
            if (is_string($r)) {
                $names[] = $r;
            }
        });

        return ! empty($names) && $this->roles()->whereIn('name', $names)->exists();
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $superAdminRoles = config('hrmac.super_admin_roles', [
            'Super Administrator',
            'super-admin',
            'tenant_super_administrator',
        ]);

        return $this->hasAnyRole($superAdminRoles);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['Super Administrator', 'Super Admin', 'Admin', 'Administrator']);
    }

    /**
     * Get all permissions - returns empty as we use module access.
     */
    public function getAllPermissions(): Collection
    {
        // We don't use Spatie permissions - module access handles authorization
        return collect([]);
    }

    /**
     * Permissions relationship - returns empty as we use module access.
     */
    public function permissions(): BelongsToMany
    {
        // Return empty relationship - we use role_module_access instead
        return $this->belongsToMany(
            Role::class, // Dummy model, never queried due to whereRaw
            'model_has_permissions',
            'model_id',
            'permission_id'
        )->whereRaw('1 = 0'); // Always empty
    }

    // =========================================================================
    // DEVICE MANAGEMENT
    // =========================================================================

    /**
     * Get the user's devices.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the user's active devices.
     */
    public function activeDevices(): HasMany
    {
        return $this->hasMany(UserDevice::class)->where('is_active', true);
    }

    /**
     * Get the current active device.
     */
    public function currentDevice(): HasOne
    {
        return $this->hasOne(UserDevice::class)
            ->where('is_active', true)
            ->latest('last_used_at');
    }

    /**
     * Alias for currentDevice (compatibility).
     */
    public function activeDevice(): HasOne
    {
        return $this->currentDevice();
    }

    /**
     * Check if single device login is enabled.
     */
    public function hasSingleDeviceLoginEnabled(): bool
    {
        return (bool) $this->single_device_login_enabled;
    }

    /**
     * Accessor for single_device_login (frontend compatibility).
     */
    public function getSingleDeviceLoginAttribute(): bool
    {
        return $this->single_device_login_enabled ?? false;
    }

    /**
     * Enable single device login.
     */
    public function enableSingleDeviceLogin(?string $reason = null): bool
    {
        return $this->update([
            'single_device_login_enabled' => true,
            'device_reset_reason' => $reason,
        ]);
    }

    /**
     * Disable single device login.
     */
    public function disableSingleDeviceLogin(?string $reason = null): bool
    {
        return $this->update([
            'single_device_login_enabled' => false,
            'device_reset_reason' => $reason,
        ]);
    }

    /**
     * Reset user devices (admin action).
     */
    public function resetDevices(?string $reason = null): bool
    {
        $this->devices()->delete();

        return $this->update([
            'device_reset_at' => now(),
            'device_reset_reason' => $reason ?: 'Admin reset',
        ]);
    }

    /**
     * Check if user can login from new device.
     */
    public function canLoginFromDevice(string $deviceId): bool
    {
        if (! $this->hasSingleDeviceLoginEnabled()) {
            return true;
        }

        $existingDevice = $this->devices()
            ->where('device_id', $deviceId)
            ->where('is_active', true)
            ->first();

        if ($existingDevice) {
            return true;
        }

        return ! $this->activeDevices()->exists();
    }

    /**
     * Get device summary for display.
     */
    public function getDeviceSummary(): array
    {
        $devices = $this->devices()->orderBy('last_used_at', 'desc')->get();

        return [
            'total_devices' => $devices->count(),
            'active_devices' => $devices->where('is_active', true)->count(),
            'current_device' => $devices->where('is_active', true)->first(),
            'last_reset' => $this->device_reset_at,
            'reset_reason' => $this->device_reset_reason,
            'single_device_enabled' => $this->single_device_login_enabled,
        ];
    }

    // =========================================================================
    // PROFILE & MEDIA
    // =========================================================================

    /**
     * Get the profile image URL.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        try {
            $url = $this->getFirstMediaUrl('profile_images');

            return ! empty($url) ? $url : null;
        } catch (\Exception $e) {
            Log::warning('Failed to get profile image URL for user '.$this->id.': '.$e->getMessage());

            return null;
        }
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_images')
            ->singleFile();
    }

    // =========================================================================
    // MODULE ACCESS (Core RBAC)
    // =========================================================================

    /**
     * Check if user has access to a module.
     */
    public function hasModuleAccess(string $moduleCode): bool
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return false;
        }

        $service = app('Aero\Core\Services\ModuleAccessService');
        $result = $service->canAccessModule($this, $moduleCode);

        return $result['allowed'] ?? false;
    }

    /**
     * Check if user has access to a submodule.
     */
    public function hasSubModuleAccess(string $moduleCode, string $subModuleCode): bool
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return false;
        }

        $service = app('Aero\Core\Services\ModuleAccessService');
        $result = $service->canAccessSubModule($this, $moduleCode, $subModuleCode);

        return $result['allowed'] ?? false;
    }

    /**
     * Check if user has access to a component.
     */
    public function hasComponentAccess(string $moduleCode, string $subModuleCode, string $componentCode): bool
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return false;
        }

        $service = app('Aero\Core\Services\ModuleAccessService');
        $result = $service->canAccessComponent($this, $moduleCode, $subModuleCode, $componentCode);

        return $result['allowed'] ?? false;
    }

    // =========================================================================
    // MODULE ACCESS METHODS (Core RBAC)
    // =========================================================================

    /**
     * Check if user can perform an action.
     */
    public function canPerformAction(string $moduleCode, string $subModuleCode, string $componentCode, string $actionCode): bool
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return false;
        }

        $service = app('Aero\Core\Services\ModuleAccessService');
        $result = $service->canPerformAction($this, $moduleCode, $subModuleCode, $componentCode, $actionCode);

        return $result['allowed'] ?? false;
    }

    /**
     * Get user's access scope for an action.
     */
    public function getActionAccessScope(int $actionId): ?string
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return null;
        }

        $service = app('Aero\Core\Services\ModuleAccessService');

        return $service->getUserAccessScope($this, $actionId);
    }

    /**
     * Get all accessible modules for this user.
     */
    public function getAccessibleModules(): array
    {
        if (! app()->bound('Aero\Core\Services\ModuleAccessService')) {
            return [];
        }

        $service = app('Aero\Core\Services\ModuleAccessService');

        return $service->getAccessibleModules($this);
    }

    /**
     * Get role module access tree (for frontend UI).
     */
    public function getModuleAccessTree(): array
    {
        if (! app()->bound('Aero\HRMAC\Services\RoleModuleAccessService')) {
            return [
                'modules' => [],
                'sub_modules' => [],
                'components' => [],
                'actions' => [],
            ];
        }

        $roleAccessService = app('Aero\HRMAC\Services\RoleModuleAccessService');
        $role = $this->roles->first();

        if (! $role) {
            return [
                'modules' => [],
                'sub_modules' => [],
                'components' => [],
                'actions' => [],
            ];
        }

        return $roleAccessService->getRoleAccessTree($role);
    }

    // =================================================================
    // UserContract Interface Implementation
    // =================================================================

    /**
     * Get the user's unique identifier.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the user's full name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the user's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the user's phone number (if available).
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /**
     * Check if the user's email has been verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at);
    }

    /**
     * Get the user's profile image URL.
     */
    public function getProfileImageUrl(): ?string
    {
        if ($this->profile_image) {
            return asset('storage/'.$this->profile_image);
        }

        if ($this->avatar_url) {
            return $this->avatar_url;
        }

        return null;
    }

    /**
     * Get the user's locale/language preference.
     */
    public function getLocale(): string
    {
        return $this->locale ?? config('app.locale', 'en');
    }

    /**
     * Get the user's timezone.
     */
    public function getTimezone(): string
    {
        return $this->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the user's roles.
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Get the user's permissions.
     */
    public function getPermissions()
    {
        return $this->permissions ?? collect();
    }

    /**
     * Get the date when the user was created.
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * Get the date when the user was last updated.
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Get the user's notification preferences for a specific channel.
     */
    public function prefersNotificationChannel(string $channel, ?string $eventType = null): bool
    {
        $query = \Illuminate\Support\Facades\DB::table('user_notification_preferences')
            ->where('user_id', $this->id)
            ->where('channel', $channel);

        if ($eventType !== null) {
            $query->where('event_type', $eventType);
        }

        $preference = $query->first();

        if ($preference !== null) {
            return (bool) $preference->enabled;
        }

        // Default: all channels enabled unless explicitly disabled
        return true;
    }

    /**
     * Get the value of a dynamically registered relationship.
     */
    public function getRelationship(string $relationshipName)
    {
        return $this->{$relationshipName} ?? null;
    }

    /**
     * Check if a dynamically registered relationship exists.
     */
    public function hasRelationship(string $relationshipName): bool
    {
        try {
            return ! is_null($this->{$relationshipName});
        } catch (\Exception $e) {
            return false;
        }
    }

    // =====================================================================
    // Searchable Interface Implementation
    // =====================================================================

    public function getSearchableColumns(): array
    {
        return ['name', 'user_name', 'email', 'phone'];
    }

    public function getSearchResultTitle(): string
    {
        return $this->name ?? $this->email ?? ('User #' . $this->id);
    }

    public function getSearchResultUrl(): ?string
    {
        return route('core.users.index');
    }

    public function getSearchResultType(): string
    {
        return 'User';
    }

    public function getSearchResultSubtitle(): ?string
    {
        return $this->email;
    }

    public function getSearchResultIcon(): ?string
    {
        return 'user';
    }
}
