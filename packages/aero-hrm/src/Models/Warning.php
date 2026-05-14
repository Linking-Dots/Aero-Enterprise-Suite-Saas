<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warning extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'warning_number',
        'employee_id',
        'disciplinary_case_id',
        'type',
        'reason',
        'issued_date',
        'expiry_date',
        'issued_by',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Warning type constants
     */
    public const TYPE_VERBAL = 'verbal';

    public const TYPE_WRITTEN = 'written';

    public const TYPE_FINAL = 'final';

    /**
     * Get the employee for this warning.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the disciplinary case for this warning.
     */
    public function disciplinaryCase(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    /**
     * Get the user who issued this warning.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if warning is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Generate unique warning number
     */
    public static function generateWarningNumber(): string
    {
        $year = date('Y');
        $lastWarning = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastWarning ? (int) substr($lastWarning->warning_number, -4) + 1 : 1;

        return 'WRN'.$year.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
