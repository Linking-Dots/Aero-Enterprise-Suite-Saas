<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\HRM\Database\Factories\PayComponentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a single pay component (earning or deduction) used in salary structures.
 *
 * @property int         $id
 * @property string      $code
 * @property string      $name
 * @property string      $kind        earning|deduction
 * @property string      $calc_type   fixed|percent_of_basic|percent_of_gross|formula
 * @property string      $value
 * @property bool        $taxable
 * @property bool        $active
 */
class PayComponent extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_pay_components';

    protected $fillable = [
        'code',
        'name',
        'kind',
        'calc_type',
        'value',
        'taxable',
        'active',
    ];

    protected $casts = [
        'value'   => 'decimal:4',
        'taxable' => 'boolean',
        'active'  => 'boolean',
    ];

    protected static function newFactory(): Factory
    {
        return PayComponentFactory::new();
    }
}
