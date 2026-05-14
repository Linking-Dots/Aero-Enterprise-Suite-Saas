<?php

namespace Aero\Scm\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomsDeclaration extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'declaration_number',
        'declaration_type',
        'declarable_type',
        'declarable_id',
        'origin_country',
        'destination_country',
        'port_of_entry',
        'port_of_exit',
        'declared_value',
        'currency',
        'duties_amount',
        'taxes_amount',
        'total_charges',
        'hs_codes',
        'declaration_date',
        'clearance_date',
        'status',
        'customs_officer',
        'notes',
    ];

    protected $casts = [
        'declared_value' => 'decimal:2',
        'duties_amount' => 'decimal:2',
        'taxes_amount' => 'decimal:2',
        'total_charges' => 'decimal:2',
        'hs_codes' => 'array',
        'declaration_date' => 'date',
        'clearance_date' => 'date',
    ];

    /**
     * Get the declarable model (polymorphic).
     */
    public function declarable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to get declarations by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('declaration_type', $type);
    }

    /**
     * Scope to get declarations by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get import declarations.
     */
    public function scopeImports($query)
    {
        return $query->where('declaration_type', 'import');
    }

    /**
     * Scope to get export declarations.
     */
    public function scopeExports($query)
    {
        return $query->where('declaration_type', 'export');
    }

    /**
     * Check if declaration is cleared.
     */
    public function isCleared()
    {
        return $this->status === 'cleared';
    }

    /**
     * Check if declaration is pending.
     */
    public function isPending()
    {
        return in_array($this->status, ['pending', 'submitted', 'under_review']);
    }

    /**
     * Calculate total charges.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($declaration) {
            $declaration->total_charges = $declaration->duties_amount + $declaration->taxes_amount;
        });
    }
}
