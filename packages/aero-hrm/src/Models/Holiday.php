<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'end_date',
        'type',
        'is_recurring',
        'applicable_to',
        'is_active',
    ];

    protected $dates = [
        'date',
        'end_date',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'applicable_to' => 'array',
    ];

    protected $appends = [
        'duration',
    ];

    // Holiday types
    public const TYPES = [
        'public' => 'Public Holiday',
        'religious' => 'Religious Holiday',
        'national' => 'National Holiday',
        'company' => 'Company Holiday',
        'optional' => 'Optional Holiday',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>', Carbon::now());
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereYear('date', Carbon::now()->year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getTypeNameAttribute()
    {
        return self::TYPES[$this->type] ?? 'Holiday';
    }

    public function getDurationAttribute()
    {
        if (! $this->date || ! $this->end_date) {
            return 1;
        }

        $fromDate = Carbon::parse($this->date);
        $toDate = Carbon::parse($this->end_date);

        // Calculate the absolute difference and add 1 for inclusive counting
        return abs($toDate->diffInDays($fromDate)) + 1;
    }

    public function getIsUpcomingAttribute()
    {
        return Carbon::parse($this->date)->isFuture();
    }

    public function getIsOngoingAttribute()
    {
        $now = Carbon::now();

        return Carbon::parse($this->date)->lte($now) && Carbon::parse($this->end_date ?? $this->date)->gte($now);
    }

    public function getIsPastAttribute()
    {
        return Carbon::parse($this->end_date ?? $this->date)->isPast();
    }

    public function getStatusAttribute()
    {
        if ($this->is_ongoing) {
            return 'ongoing';
        } elseif ($this->is_upcoming) {
            return 'upcoming';
        } else {
            return 'past';
        }
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'updated_by');
    }

    // Static methods
    public static function getUpcomingHolidays($limit = 5)
    {
        return self::active()
            ->upcoming()
            ->orderBy('date', 'asc')
            ->limit($limit)
            ->get();
    }

    public static function getHolidaysInRange(Carbon $startDate, Carbon $endDate)
    {
        return self::active()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->orderBy('date', 'asc')
            ->get();
    }

    public static function getTotalWorkingDays($year = null)
    {
        $year = $year ?? Carbon::now()->year;
        $totalDays = Carbon::create($year)->isLeapYear() ? 366 : 365;

        // Calculate holiday days using the duration accessor
        $holidays = self::whereYear('date', $year)
            ->active()
            ->get();

        $holidayDays = $holidays->sum(function ($holiday) {
            return $holiday->duration;
        });

        // Subtract weekends (assuming 52 weeks * 2 days = 104 weekend days)
        $weekendDays = 104;

        return $totalDays - $holidayDays - $weekendDays;
    }
}
