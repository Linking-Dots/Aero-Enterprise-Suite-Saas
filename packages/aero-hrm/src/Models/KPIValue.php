<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KPIValue extends TenantModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kpi_id',
        'value',
        'date',
        'notes',
        'recorded_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'float',
        'date' => 'date',
    ];

    /**
     * Get the KPI that owns the value.
     */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(KPI::class);
    }

    /**
     * Get the user who recorded this value.
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
