<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Encryption\EncryptedField;
use Aero\HRM\Database\Factories\PayslipFactory;
use Aero\HRM\Exceptions\PayrollLockedException;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A single employee payslip generated as part of a PayrollRun.
 *
 * Security requirements:
 *  - bank_account_number / bank_name / bank_routing_number are encrypted at rest via EncryptedField.
 *  - LogsActivity trait tracks all mutations for GDPR/audit purposes.
 *  - Immutability: once the parent PayrollRun is locked, no updates or deletes are allowed.
 *
 * @property int         $id
 * @property int         $payroll_run_id
 * @property int         $employee_id
 * @property string      $gross
 * @property string      $tax
 * @property string      $deductions_total
 * @property string      $net
 * @property array       $line_items
 * @property array       $employee_snapshot
 * @property string|null $bank_account_number  (encrypted)
 * @property string|null $bank_name            (encrypted)
 * @property string|null $bank_routing_number  (encrypted)
 */
class Payslip extends TenantModel
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'hrm_payslips';

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'gross',
        'tax',
        'deductions_total',
        'net',
        'line_items',
        'employee_snapshot',
        'bank_account_number',
        'bank_name',
        'bank_routing_number',
    ];

    protected $casts = [
        'bank_account_number' => EncryptedField::class,
        'bank_name'           => EncryptedField::class,
        'bank_routing_number' => EncryptedField::class,
        'line_items'          => 'array',
        'employee_snapshot'   => 'array',
        'gross'               => 'decimal:2',
        'tax'                 => 'decimal:2',
        'deductions_total'    => 'decimal:2',
        'net'                 => 'decimal:2',
    ];

    protected static function newFactory(): Factory
    {
        return PayslipFactory::new();
    }

    protected static function booted(): void
    {
        // Block updates when the parent run is locked.
        static::updating(function (Payslip $slip): void {
            if ($slip->run?->isLocked()) {
                throw new PayrollLockedException(
                    "Payslip #{$slip->id} belongs to a locked payroll run and cannot be modified."
                );
            }
        });

        // Block deletion when the parent run is locked.
        static::deleting(function (Payslip $slip): void {
            if ($slip->run?->isLocked()) {
                throw new PayrollLockedException(
                    "Payslip #{$slip->id} belongs to a locked payroll run and cannot be deleted."
                );
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // ── Spatie LogsActivity ───────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "Payslip {$eventName}");
    }
}
