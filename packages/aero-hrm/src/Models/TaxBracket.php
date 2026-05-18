<?php

declare(strict_types=1);

namespace Aero\HRM\Models;

use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Progressive tax bracket for payroll tax calculations.
 *
 * @property int         $id
 * @property string      $country_code
 * @property string      $income_from
 * @property string|null $income_to
 * @property string      $rate
 * @property int         $effective_year
 */
class TaxBracket extends TenantModel
{
    use HasFactory;

    protected $table = 'hrm_tax_brackets';

    protected $fillable = [
        'country_code',
        'income_from',
        'income_to',
        'rate',
        'effective_year',
    ];

    protected $casts = [
        'income_from'    => 'decimal:2',
        'income_to'      => 'decimal:2',
        'rate'           => 'decimal:4',
        'effective_year' => 'integer',
    ];

    protected static function newFactory(): \Aero\HRM\Database\Factories\TaxBracketFactory
    {
        return \Aero\HRM\Database\Factories\TaxBracketFactory::new();
    }
}
