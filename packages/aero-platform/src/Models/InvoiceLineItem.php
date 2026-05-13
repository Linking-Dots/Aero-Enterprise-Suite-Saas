<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Invoice Line Item Model
 *
 * A single line on an invoice (subscription charge, module charge, usage, etc.).
 *
 * @property string $id UUID
 * @property string $invoice_id FK to invoices
 * @property string $type subscription, module, usage, addon, adjustment, tax
 * @property string $description Human-readable description
 * @property int $quantity
 * @property string $unit_price DECIMAL
 * @property string $amount DECIMAL (quantity × unit_price − discount)
 */
class InvoiceLineItem extends CentralModel
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'invoice_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'discount',
        'plan_id',
        'module_code',
        'metadata',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * The invoice this line item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
