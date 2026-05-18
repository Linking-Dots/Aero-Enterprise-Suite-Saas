<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\SalaryStructureFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

/**
 * A named salary structure grouping a basic salary amount with an ordered
 * list of PayComponent IDs to apply during payroll calculation.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $basic
 * @property array|null  $component_ids
 * @property bool        $active
 */
class SalaryStructure extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_salary_structures';

    protected $fillable = [
        'name',
        'basic',
        'component_ids',
        'active',
    ];

    protected $casts = [
        'basic'         => 'decimal:2',
        'component_ids' => 'array',
        'active'        => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return SalaryStructureFactory::new();
    }

    /**
     * Return pay components in the exact order specified by component_ids.
     *
     * Uses PHP-side sort to remain SQLite-compatible (avoids MySQL-only FIELD()).
     */
    public function components(): Collection
    {
        $ids = $this->component_ids ?? [];

        if (empty($ids)) {
            return collect();
        }

        $components = PayComponent::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int|string $id) => $components[$id] ?? null)
            ->filter()
            ->values();
    }
}
