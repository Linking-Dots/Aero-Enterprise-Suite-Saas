<?php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LandlordRole extends CentralModel
{
    use HasFactory;

    public const SYSTEM_SUPER_ADMIN = 'super-admin';

    protected $connection = 'central';

    protected $table = 'landlord_roles';

    protected $fillable = ['name', 'description', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            LandlordUser::class,
            'landlord_user_role',
            'landlord_role_id',
            'landlord_user_id'
        )->withTimestamps();
    }

    public function hasPermission(string $path): bool
    {
        return in_array($path, $this->permissions ?? [], true);
    }
}
