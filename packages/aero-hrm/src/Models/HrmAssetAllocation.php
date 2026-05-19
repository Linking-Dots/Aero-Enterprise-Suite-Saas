<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrmAssetAllocation extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_asset_allocations';

    protected $fillable = [
        'asset_id',
        'employee_id',
        'allocated_at',
        'returned_at',
        'condition_on_allocation',
        'condition_on_return',
        'allocation_notes',
        'return_notes',
        'allocated_by',
        'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(HrmAsset::class, 'asset_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
