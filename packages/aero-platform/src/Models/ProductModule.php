<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\HRMAC\Models\Module;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot row linking a sellable {@see Product} to a technical {@see Module} it grants.
 *
 * A Product bundles one-or-more modules; each row here is one (product, module_code)
 * entitlement mapping. `module_code` is the natural key into the modules registry.
 */
class ProductModule extends CentralModel
{
    protected $table = 'product_modules';

    protected $fillable = [
        'product_id',
        'module_code',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_code', 'code');
    }
}
