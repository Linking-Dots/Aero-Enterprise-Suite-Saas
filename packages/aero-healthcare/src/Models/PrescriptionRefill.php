<?php

namespace Aero\Healthcare\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionRefill extends TenantModel
{
    use HasFactory;

    protected $table = 'healthcare_prescription_refills';

    protected $fillable = [
        'prescription_id', 'refill_date', 'quantity_dispensed',
        'pharmacy_name', 'pharmacist_name', 'status', 'notes', 'processed_by',
    ];

    protected $casts = [
        'prescription_id' => 'integer',
        'refill_date' => 'date',
        'quantity_dispensed' => 'integer',
        'processed_by' => 'integer',
    ];

    const STATUS_REQUESTED = 'requested';

    const STATUS_APPROVED = 'approved';

    const STATUS_DISPENSED = 'dispensed';

    const STATUS_DENIED = 'denied';

    const STATUS_CANCELLED = 'cancelled';

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isDispensed()
    {
        return $this->status === self::STATUS_DISPENSED;
    }

    public function isPending()
    {
        return in_array($this->status, [self::STATUS_REQUESTED, self::STATUS_APPROVED]);
    }

    public function scopeDispensed($query)
    {
        return $query->where('status', self::STATUS_DISPENSED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_REQUESTED, self::STATUS_APPROVED]);
    }
}
