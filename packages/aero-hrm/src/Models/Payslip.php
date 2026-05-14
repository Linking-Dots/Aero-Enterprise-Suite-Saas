<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends TenantModel
{
    use HasFactory;

    protected $table = 'payslips';

    protected $fillable = [
        'payroll_id',
        'user_id',
        'payslip_number',
        'pay_period_start',
        'pay_period_end',
        'basic_salary',
        'gross_salary',
        'total_allowances',
        'total_deductions',
        'net_salary',
        'generated_at',
        'sent_at',
        'pdf_path',
        'email_sent',
        'status',
    ];

    protected $casts = [
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'email_sent' => 'boolean',
    ];

    /**
     * Get the payroll that owns the payslip.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the employee that owns the payslip.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get formatted pay period.
     */
    public function getPayPeriodAttribute()
    {
        return $this->pay_period_start->format('M Y');
    }

    /**
     * Get formatted payslip number.
     */
    public function getFormattedPayslipNumberAttribute()
    {
        return 'PAY-'.str_pad($this->payslip_number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if payslip is generated.
     */
    public function isGenerated()
    {
        return $this->status === 'generated';
    }

    /**
     * Check if payslip is sent.
     */
    public function isSent()
    {
        return $this->email_sent;
    }

    /**
     * Generate payslip number.
     */
    public static function generatePayslipNumber()
    {
        $lastPayslip = self::orderBy('id', 'desc')->first();

        return $lastPayslip ? $lastPayslip->payslip_number + 1 : 1;
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by pay period.
     */
    public function scopeByPayPeriod($query, $start, $end)
    {
        return $query->whereBetween('pay_period_start', [$start, $end]);
    }
}
