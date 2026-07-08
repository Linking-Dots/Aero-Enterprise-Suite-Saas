<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Aero\Platform\Observers\InvoiceImmutableObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice Model
 *
 * Represents a billing invoice stored in a dedicated table (not tenant.data JSON).
 * Supports both Stripe-originated and locally-generated invoices.
 *
 * @property string $id UUID primary key
 * @property string $billable_type Polymorphic billable type
 * @property string $billable_id Polymorphic billable ID
 * @property string|null $subscription_id Plan subscription reference
 * @property string|null $subscription_module_id Module subscription reference
 * @property string $invoice_number Unique invoice number
 * @property string $status draft, issued, paid, void, overdue, refunded
 * @property string $currency ISO currency code
 * @property string $subtotal DECIMAL subtotal before tax/discount
 * @property string $total DECIMAL total after adjustments
 * @property string $amount_paid DECIMAL amount paid
 * @property string $amount_due DECIMAL remaining amount due
 */
class Invoice extends CentralModel
{
    use HasUuids, SoftDeletes;

    /**
     * Boot the model — register immutability observer.
     */
    protected static function booted(): void
    {
        parent::booted();
        static::observe(InvoiceImmutableObserver::class);
    }

    public $incrementing = false;

    protected $keyType = 'string';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PAID = 'paid';

    public const STATUS_VOID = 'void';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'billable_type',
        'billable_id',
        'subscription_id',
        'subscription_module_id',
        'invoice_number',
        'status',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'amount_due',
        'billing_period_start',
        'billing_period_end',
        'payment_method',
        'external_invoice_id',
        'paid_at',
        'due_date',
        'metadata',
        'notes',
        // P-2 additions
        'reference',
        'amount',
        'stripe_invoice_id',
        'pdf_path',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'paid_at' => 'datetime',
        'due_date' => 'date',
        'metadata' => 'array',
        // P-2 additions
        'amount' => 'decimal:2',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * The billable entity (Tenant) that owns this invoice.
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The plan subscription associated with this invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * The module subscription associated with this invoice.
     */
    public function subscriptionModule(): BelongsTo
    {
        return $this->belongsTo(SubscriptionModule::class);
    }

    /**
     * Line items for this invoice.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Resolve route-model bindings by the string primary key without the
     * HasUuids UUID-format guard. Invoices may carry non-UUID string ids
     * (seeded/imported records), and `id` is the string PK regardless of
     * format — the default HasUuids binding would 404 those.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function markPaid(?string $method = null, ?string $reference = null, ?float $amount = null): bool
    {
        $paidAmount = $amount ?? $this->total;

        return $this->update([
            'status' => self::STATUS_PAID,
            'amount_paid' => $paidAmount,
            'amount_due' => max(0, $this->total - $paidAmount),
            'paid_at' => now(),
            'payment_method' => $method ?? $this->payment_method,
        ]);
    }

    public function markVoid(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_VOID,
            'notes' => $reason ? ($this->notes ? $this->notes.' | '.$reason : $reason) : $this->notes,
        ]);
    }

    public function markRefunded(float $amount): bool
    {
        return $this->update([
            'status' => self::STATUS_REFUNDED,
            'refunded_amount' => ($this->refunded_amount ?? 0) + $amount,
        ]);
    }
}
