<?php

namespace Aero\Platform\Models;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Database\Factories\LandlordUserFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * LandlordUser Model (Platform Admin)
 *
 * Represents platform administrators who manage the multi-tenant SaaS
 * from the admin.platform.com domain. These users exist ONLY in the
 * central database and have access to tenant management, billing,
 * and platform-wide settings.
 *
 * This model uses the same structure as the User model to share
 * roles and permissions between platform admins and tenant users.
 *
 * SECURITY CONSIDERATIONS:
 * - Stored in central database (not affected by tenant context)
 * - Separate from tenant User model to enforce isolation
 * - Uses Spatie HasRoles for permission management
 * - Should have MFA enabled in production
 *
 * @property int $id Primary key
 * @property string $user_name Username
 * @property string $name Full name
 * @property string $email Unique email address
 * @property string $password Hashed password
 * @property bool $active Whether the account is active
 * @property string|null $phone Phone number
 * @property string|null $profile_image Profile image path
 * @property string $timezone User timezone
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 */
class LandlordUser extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * CRITICAL: Force this model to ALWAYS use the central database connection.
     *
     * This ensures landlord users are never accidentally queried from
     * a tenant database, even when tenancy is initialized.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'landlord_users';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LandlordUserFactory
    {
        return LandlordUserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_name',
        'name',
        'email',
        'password',
        'phone',
        'active',
        'profile_image',
        'timezone',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_image_url',
    ];

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to filter active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter inactive users only.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Get the roles that belong to the user (HRMAC roles on the central DB).
     */
    public function roles(): BelongsToMany
    {
        $roleModel = config('hrmac.models.role', Role::class);

        return $this->belongsToMany(
            $roleModel,
            'model_has_roles',
            'model_id',
            'role_id'
        )->where('model_has_roles.model_type', static::class);
    }

    // =========================================================================
    // ROLE METHODS
    // =========================================================================

    /**
     * Check if user has a specific role.
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }

        if (is_object($role) && isset($role->id)) {
            return $this->roles()->where('id', $role->id)->exists();
        }

        return false;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles, $guard = null): bool
    {
        return $this->roles()->whereIn('name', (array) $roles)->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(...$roles): self
    {
        $roleModel = config('hrmac.models.role', Role::class);
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
            \DB::connection('central')->table('model_has_roles')->updateOrInsert([
                'role_id' => $roleId,
                'model_type' => static::class,
                'model_id' => $this->id,
            ]);
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(...$roles): self
    {
        $roleModel = config('hrmac.models.role', Role::class);
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

        \DB::connection('central')->table('model_has_roles')
            ->where('model_type', static::class)
            ->where('model_id', $this->id)
            ->delete();

        foreach ($roleIds as $roleId) {
            \DB::connection('central')->table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => static::class,
                'model_id' => $this->id,
            ]);
        }

        $this->unsetRelation('roles');

        return $this;
    }

    /**
     * Get all permissions - returns empty as we use module access.
     */
    public function getAllPermissions(): Collection
    {
        return collect([]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if the user is a super admin. Recognizes any configured HRMAC
     * super-admin role (e.g. "Super Platform Admin") rather than a single
     * hardcoded name, so the super-admin wildcard reaches the frontend HRMAC map.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(config('hrmac.super_admin_roles', ['Super Administrator']));
    }

    /**
     * Check if the user is an admin (any admin role).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['Super Administrator', 'Platform Admin']);
    }

    /**
     * Check if the user is support staff.
     */
    public function isSupport(): bool
    {
        return $this->hasRole('Platform Support');
    }

    /**
     * Check if the user is a super administrator.
     * Super administrators bypass all module access checks.
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->hasRole('Super Administrator');
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Twill checks credentials with ['published' => 1].
     * Map our `active` column to the `published` attribute it expects.
     */
    public function getPublishedAttribute(): bool
    {
        return $this->active === true;
    }

    /**
     * Record a login event.
     */
    public function recordLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Get the user's initials for avatar fallback.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }

    /**
     * Get the profile image URL or generate a default.
     */
    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('storage/'.$this->profile_image);
        }

        // Generate a Gravatar URL as fallback
        $hash = md5(strtolower(trim($this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
