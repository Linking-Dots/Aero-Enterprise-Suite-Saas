<?php

namespace Aero\Platform\Models;

use Aero\Auth\Models\User;
use Aero\Platform\Database\Factories\LandlordUserFactory;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * LandlordUser Model (Platform Admin)
 *
 * Represents platform administrators who manage the multi-tenant SaaS from the
 * admin domain. Landlords exist ONLY in the CENTRAL database.
 *
 * Auth-identity unification — Unit 2C, mechanism B:
 * This is now a THIN, central-bound subclass of the unified
 * Aero\Auth\Models\User. Landlords are rows in the shared central `users` table;
 * the `landlord` guard/provider resolve them there via this model's
 * $connection='central' (the provider-driven connection strategy). Role and
 * permission behaviour (roles(), assignRole(), syncRoles(), hasRole(),
 * hasAnyRole(), isSuperAdmin(), ...) is INHERITED from User and keyed on
 * getMorphClass(). Because this subclass is NOT registered in the morph map,
 * getMorphClass() returns the class FQN 'Aero\Platform\Models\LandlordUser' —
 * identical to the legacy hand-rolled static::class — so existing
 * model_has_roles landlord rows keep resolving and
 * 2026_06_09_000001_normalize_user_morph_type correctly continues to skip them.
 *
 * EnforcesTenantContext (from User) no-ops on the central connection (Unit 2B),
 * so a central-bound landlord never trips the tenant isolation guard.
 *
 * @property int $id Primary key
 * @property string|null $user_name Username
 * @property string $name Full name
 * @property string $email Unique email address
 * @property string $password Hashed password
 * @property bool $active Whether the account is active
 * @property string|null $phone Phone number
 * @property string|null $profile_image Profile image path
 * @property string|null $timezone User timezone
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 */
class LandlordUser extends User
{
    use TwoFactorAuthenticatable;

    /**
     * CRITICAL: landlords are always resolved on the central connection, never
     * from a tenant database — even when tenancy is initialized.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * Landlords are rows in the shared central `users` table (Unit 2C).
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): LandlordUserFactory
    {
        return LandlordUserFactory::new();
    }

    /**
     * The attributes that are mass assignable (landlord-relevant subset of the
     * shared users table).
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
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_image_url',
    ];

    /**
     * The attributes that should be cast. Merged with User::$casts by the
     * framework (getCasts()), so password=>hashed etc. remain in effect.
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

    // =========================================================================
    // LANDLORD-SPECIFIC HELPERS
    //
    // roles()/assignRole()/syncRoles()/removeRole()/hasRole()/hasAnyRole()/
    // isSuperAdmin()/getAllPermissions()/scopeActive()/scopeInactive() are all
    // inherited from Aero\Auth\Models\User. Only landlord-specific overrides and
    // helpers live here.
    // =========================================================================

    /**
     * Check if the user is an admin (any landlord admin role).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['Super Administrator', 'Platform Admin', 'Super Platform Admin']);
    }

    /**
     * Check if the user is support staff.
     */
    public function isSupport(): bool
    {
        return $this->hasRole('Platform Support');
    }

    /**
     * Super-admin accessor (`is_super_admin`) so the wildcard reaches the
     * frontend HRMAC map.
     */
    public function getIsSuperAdminAttribute(): bool
    {
        return $this->isSuperAdmin();
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
        $words = explode(' ', (string) $this->name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }

    /**
     * Get the profile image URL or generate a Gravatar fallback. Overrides the
     * media-library-based accessor on User (landlords use a simpler scheme).
     */
    public function getProfileImageUrlAttribute(): string
    {
        if ($this->profile_image) {
            return asset('storage/'.$this->profile_image);
        }

        $hash = md5(strtolower(trim((string) $this->email)));

        return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
    }
}
