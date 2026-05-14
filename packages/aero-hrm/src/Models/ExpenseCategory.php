<?php

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_amount',
        'requires_receipt',
        'requires_approval',
        'approval_levels',
        'is_active',
        'allowed_file_types',
        'max_file_size_mb',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'requires_receipt' => 'boolean',
        'requires_approval' => 'boolean',
        'approval_levels' => 'integer',
        'is_active' => 'boolean',
        'allowed_file_types' => 'array',
        'max_file_size_mb' => 'integer',
    ];

    /**
     * Get the expense claims for this category.
     */
    public function expenseClaims(): HasMany
    {
        return $this->hasMany(ExpenseClaim::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the amount is within the maximum limit.
     */
    public function isAmountValid(float $amount): bool
    {
        if ($this->max_amount === null) {
            return true;
        }

        return $amount <= $this->max_amount;
    }
}
