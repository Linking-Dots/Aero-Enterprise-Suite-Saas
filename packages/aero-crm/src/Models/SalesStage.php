<?php

namespace Aero\Crm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesStage extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'order',
        'probability',
        'is_active',
        'color',
    ];

    protected $casts = [
        'order' => 'integer',
        'probability' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the opportunities in this stage
     */
    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }

    /**
     * Scope for active stages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by order field
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
