<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Models\User;
use Aero\HRM\Database\Factories\PayrollRunFactory;
use Aero\HRM\Exceptions\PayrollLockedException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single payroll processing run covering a date range.
 *
 * Immutability rule: once locked_at is set (after approval), no further updates
 * or deletes are permitted on this run or its associated payslips.
 *
 * @property int              $id
 * @property string           $label
 * @property \Carbon\Carbon   $period_start
 * @property \Carbon\Carbon   $period_end
 * @property string           $status
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null         $approved_by
 * @property \Carbon\Carbon|null $locked_at
 * @property string           $total_gross
 * @property string           $total_net
 */
class PayrollRun extends TenantModel
{
    use HasFactory;

    public const STATUS_DRAFT    = 'draft';
    public const STATUS_REVIEW   = 'review';
    public const STATUS_APPROVED = 'approved';

    protected $table = 'hrm_payroll_runs';

    /** approved_by / approved_at / locked_at are NOT in fillable — set directly. */
    protected $fillable = [
        'label',
        'period_start',
        'period_end',
        'status',
        'total_gross',
        'total_net',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'approved_at'  => 'datetime',
        'locked_at'    => 'datetime',
        'total_gross'  => 'decimal:2',
        'total_net'    => 'decimal:2',
    ];

    protected static function newFactory(): Factory
    {
        return PayrollRunFactory::new();
    }

    protected static function booted(): void
    {
        // Block updates on locked runs.
        static::updating(function (PayrollRun $run): void {
            if ($run->getOriginal('locked_at') !== null) {
                throw new PayrollLockedException(
                    "Payroll run #{$run->id} is locked and cannot be modified."
                );
            }
        });

        // Block deletion of locked runs.
        static::deleting(function (PayrollRun $run): void {
            if ($run->locked_at !== null) {
                throw new PayrollLockedException(
                    "Payroll run #{$run->id} is locked and cannot be deleted."
                );
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
