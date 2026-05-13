<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentPayment extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_rent_payments';

    protected $fillable = [
        'lease_agreement_id', 'payment_number', 'due_date', 'amount_due',
        'amount_paid', 'payment_date', 'payment_method', 'reference_number',
        'late_fee', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'lease_agreement_id' => 'integer',
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
        'late_fee' => 'decimal:2',
        'created_by' => 'integer',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_PAID = 'paid';

    const STATUS_PARTIAL = 'partial';

    const STATUS_LATE = 'late';

    const STATUS_OVERDUE = 'overdue';

    const METHOD_CASH = 'cash';

    const METHOD_CHECK = 'check';

    const METHOD_BANK_TRANSFER = 'bank_transfer';

    const METHOD_CREDIT_CARD = 'credit_card';

    const METHOD_ONLINE = 'online';

    const METHOD_MONEY_ORDER = 'money_order';

    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue()
    {
        return $this->due_date < now()->toDateString() && ! $this->isPaid();
    }

    public function isLate()
    {
        $lease = $this->leaseAgreement;
        if (! $lease || ! $lease->grace_period_days) {
            return $this->isOverdue();
        }

        $graceEndDate = $this->due_date->addDays($lease->grace_period_days);

        return now()->toDateString() > $graceEndDate && ! $this->isPaid();
    }

    public function getBalanceAttribute()
    {
        return ($this->amount_due + ($this->late_fee ?? 0)) - ($this->amount_paid ?? 0);
    }

    public function getDaysLateAttribute()
    {
        if ($this->isPaid()) {
            return 0;
        }

        $today = now()->toDateString();
        if ($this->due_date >= $today) {
            return 0;
        }

        return $this->due_date->diffInDays($today);
    }

    public function calculateLateFee()
    {
        $lease = $this->leaseAgreement;
        if (! $lease || $this->isPaid() || ! $this->isLate()) {
            return 0;
        }

        return $lease->late_fee ?? 0;
    }

    public function applyLateFee()
    {
        if (! $this->late_fee && $this->isLate()) {
            $this->late_fee = $this->calculateLateFee();
            $this->save();
        }
    }

    public function markAsPaid($amount, $paymentMethod = null, $referenceNumber = null)
    {
        $this->amount_paid = $amount;
        $this->payment_date = now();
        $this->payment_method = $paymentMethod;
        $this->reference_number = $referenceNumber;

        if ($amount >= $this->amount_due + ($this->late_fee ?? 0)) {
            $this->status = self::STATUS_PAID;
        } else {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('due_date', $year)
            ->whereMonth('due_date', $month);
    }

    public function scopeByProperty($query, $propertyId)
    {
        return $query->whereHas('leaseAgreement', function ($q) use ($propertyId) {
            $q->where('property_id', $propertyId);
        });
    }
}
