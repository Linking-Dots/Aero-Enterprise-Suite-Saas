<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BenefitOpenEnrollmentPeriod extends TenantModel
{
    use HasFactory;

    protected $table = 'benefit_open_enrollment_periods';

    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'status',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent(Builder $query, Carbon|string|null $referenceDate = null): Builder
    {
        $date = $referenceDate instanceof Carbon
            ? $referenceDate->toDateString()
            : Carbon::parse($referenceDate ?? now())->toDateString();

        return $query
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date);
    }

    public function isCurrent(Carbon|string|null $referenceDate = null): bool
    {
        $date = $referenceDate instanceof Carbon
            ? $referenceDate->toDateString()
            : Carbon::parse($referenceDate ?? now())->toDateString();

        return $this->status === 'active'
            && $this->starts_at !== null
            && $this->ends_at !== null
            && $this->starts_at->toDateString() <= $date
            && $this->ends_at->toDateString() >= $date;
    }
}
