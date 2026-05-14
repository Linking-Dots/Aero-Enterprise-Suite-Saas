<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementRequestItem extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'item_name',
        'specifications',
        'quantity',
        'unit_of_measure',
        'estimated_unit_price',
        'estimated_total',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
    ];

    /**
     * Get the procurement request.
     */
    public function procurementRequest()
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    /**
     * Calculate estimated total when unit price or quantity changes.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->quantity && $item->estimated_unit_price) {
                $item->estimated_total = $item->quantity * $item->estimated_unit_price;
            }
        });
    }
}
