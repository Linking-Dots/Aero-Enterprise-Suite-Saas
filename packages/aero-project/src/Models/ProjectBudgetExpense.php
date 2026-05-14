<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudgetExpense extends TenantModel
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'project_id',
        'category',
        'description',
        'amount',
        'currency',
        'expense_date',
        'receipt_number',
        'vendor',
        'approved',
        'approved_by',
        'approved_at',
        'notes',
        'receipt_file_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the budget that owns the expense.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(ProjectBudget::class);
    }

    /**
     * Get the project that owns the expense.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who approved the expense.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for approved expenses.
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    /**
     * Scope for pending expenses.
     */
    public function scopePending($query)
    {
        return $query->where('approved', false);
    }

    /**
     * Get expense category options.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'materials' => 'Materials',
            'labor' => 'Labor',
            'equipment' => 'Equipment',
            'travel' => 'Travel',
            'software' => 'Software',
            'consulting' => 'Consulting',
            'training' => 'Training',
            'miscellaneous' => 'Miscellaneous',
        ];
    }
}
