<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadSource extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'color',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the leads for this source
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'source_id');
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
