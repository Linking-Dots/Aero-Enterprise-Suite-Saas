<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Emergency Contact Model
 *
 * Stores emergency contacts for employees.
 * Has a 1:Many relationship with User model.
 */
class EmergencyContact extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'emergency_contacts';

    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'phone',
        'alternate_phone',
        'email',
        'address',
        'city',
        'country',
        'priority',
        'is_primary',
        'notify_on_emergency',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_primary' => 'boolean',
        'notify_on_emergency' => 'boolean',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        // Ensure only one primary contact per user
        static::saving(function ($contact) {
            if ($contact->is_primary) {
                static::where('user_id', $contact->user_id)
                    ->where('id', '!=', $contact->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
