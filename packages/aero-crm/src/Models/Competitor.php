<?php

namespace Aero\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competitor extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'website',
        'description',
        'strengths',
        'weaknesses',
        'products',
        'is_active',
    ];

    protected $casts = [
        'products' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get deals lost to this competitor
     */
    public function dealsLostTo()
    {
        return $this->hasMany(Deal::class, 'competitor_id')
            ->where('status', Deal::STATUS_LOST);
    }

    /**
     * Get count of deals lost to this competitor
     */
    public function getDealsLostCountAttribute(): int
    {
        return $this->dealsLostTo()->count();
    }

    /**
     * Get total value of deals lost to this competitor
     */
    public function getDealsLostValueAttribute()
    {
        return $this->dealsLostTo()->sum('value');
    }

    /**
     * Scope for active competitors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
