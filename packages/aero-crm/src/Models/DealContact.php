<?php

namespace Aero\Crm\Models;

use Illuminate\Database\Eloquent\Model;

class DealContact extends TenantModel
{
    protected $fillable = [
        'deal_id',
        'contact_id',
        'name',
        'email',
        'phone',
        'title',
        'role',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Contact roles in deal
     */
    const ROLE_DECISION_MAKER = 'decision_maker';

    const ROLE_INFLUENCER = 'influencer';

    const ROLE_USER = 'user';

    const ROLE_GATEKEEPER = 'gatekeeper';

    const ROLE_CHAMPION = 'champion';

    const ROLE_OTHER = 'other';

    /**
     * Get the deal
     */
    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Scope for primary contacts
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Set as primary contact (and unset others)
     */
    public function setAsPrimary(): bool
    {
        // Unset other primary contacts
        static::where('deal_id', $this->deal_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        return $this->update(['is_primary' => true]);
    }
}
