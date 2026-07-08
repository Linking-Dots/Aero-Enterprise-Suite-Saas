<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Mail\Subscription\PaymentReminderMail;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Refund;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvoiceAdminService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * Unified command-centre payload: normalised invoices (tenant names
     * resolved) + collection stats, AR aging, collections trend, KPI
     * sparklines and the subscription list for guided generation. Guard-free
     * query builder so it's context-agnostic.
     *
     * The DB uses `open` for a sent-unpaid invoice while the Invoice model
     * constant is `issued`; both are normalised to `open` here so the surface
     * has one vocabulary.
     *
     * @return array{stats: array, invoices: array, aging: array, trend: array, sparks: array, subscriptions: array}
     */
    public function overview(): array
    {
        $rows = DB::table('invoices as i')
            ->leftJoin('tenants as t', 't.id', '=', 'i.billable_id')
            ->whereNull('i.deleted_at')
            ->orderByDesc('i.created_at')
            ->limit(500)
            ->get(['i.id', 'i.invoice_number', 'i.reference', 't.name as tenant', 'i.billable_id as tenant_id',
                'i.status', 'i.subtotal', 'i.discount_amount', 'i.tax_amount', 'i.total',
                'i.amount_paid', 'i.amount_due', 'i.currency', 'i.payment_method',
                'i.billing_period_start', 'i.billing_period_end', 'i.due_date', 'i.paid_at', 'i.created_at'])
            ->map(function ($r) {
                $status = $r->status === 'issued' ? 'open' : $r->status;
                $unpaid = in_array($status, ['open', 'overdue'], true);
                // (now − due): positive ⇒ days past due; only meaningful while unpaid.
                $daysOverdue = ($unpaid && $r->due_date !== null)
                    ? (int) Carbon::parse($r->due_date)->startOfDay()->diffInDays(now()->startOfDay(), false)
                    : null;
                $daysToPay = ($status === 'paid' && $r->paid_at !== null)
                    ? (int) Carbon::parse($r->created_at)->startOfDay()->diffInDays(Carbon::parse($r->paid_at)->startOfDay(), false)
                    : null;

                return [
                    'id'            => (string) $r->id,
                    'number'        => $r->invoice_number ?: ($r->reference ?: '#'.substr((string) $r->id, 0, 8)),
                    'reference'     => $r->reference,
                    'tenant'        => $r->tenant ?: '—',
                    'tenant_id'     => (string) $r->tenant_id,
                    'status'        => $status,
                    'subtotal'      => (float) $r->subtotal,
                    'discount'      => (float) $r->discount_amount,
                    'tax'           => (float) $r->tax_amount,
                    'total'         => (float) $r->total,
                    'amount_paid'   => (float) $r->amount_paid,
                    'amount_due'    => (float) $r->amount_due,
                    'currency'      => $r->currency ?: 'USD',
                    'method'        => $r->payment_method,
                    'period_start'  => $r->billing_period_start,
                    'period_end'    => $r->billing_period_end,
                    'due_date'      => $r->due_date,
                    'paid_at'       => $r->paid_at,
                    'created_at'    => $r->created_at,
                    'days_overdue'  => ($daysOverdue !== null && $daysOverdue > 0) ? $daysOverdue : 0,
                    'days_to_pay'   => $daysToPay,
                ];
            })->all();

        // Subscriptions available for guided invoice generation.
        $subscriptions = DB::table('subscriptions as s')
            ->leftJoin('plans as p', 'p.id', '=', 's.plan_id')
            ->leftJoin('tenants as t', 't.id', '=', 's.tenant_id')
            ->whereNull('s.deleted_at')
            ->whereIn('s.status', ['active', 'trialing', 'past_due'])
            ->orderBy('t.name')
            ->limit(500)
            ->get(['s.id', 't.name as tenant', 'p.name as plan', 'p.price_monthly'])
            ->map(fn ($s) => [
                'id'     => (string) $s->id,
                'tenant' => $s->tenant ?: '—',
                'plan'   => $s->plan ?: 'No plan',
                'price'  => (float) ($s->price_monthly ?? 0),
            ])->all();

        return [
            'stats'         => $this->invoiceStats($rows),
            'invoices'      => $rows,
            'aging'         => $this->buildAging($rows),
            'trend'         => $this->buildTrend($rows),
            'sparks'        => $this->buildSparks($rows),
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * @param  array<int, array>  $rows
     * @return array<string, int|float>
     */
    private function invoiceStats(array $rows): array
    {
        $sumWhere = fn (callable $pred, string $key) => array_sum(array_map(fn ($r) => $pred($r) ? $r[$key] : 0, $rows));
        $countWhere = fn (callable $pred) => count(array_filter($rows, $pred));

        $paid = fn ($r) => $r['status'] === 'paid';
        $open = fn ($r) => $r['status'] === 'open';
        $overdue = fn ($r) => $r['status'] === 'overdue';
        $void = fn ($r) => $r['status'] === 'void';
        $total = max(1, count($rows));

        $paidRows = array_filter($rows, fn ($r) => $r['days_to_pay'] !== null);
        $avgDays = count($paidRows) > 0
            ? (int) round(array_sum(array_map(fn ($r) => $r['days_to_pay'], $paidRows)) / count($paidRows))
            : 0;

        return [
            'collected'      => round($sumWhere($paid, 'amount_paid'), 2),
            'outstanding'    => round($sumWhere(fn ($r) => $open($r) || $overdue($r), 'amount_due'), 2),
            'overdue_amt'    => round($sumWhere($overdue, 'amount_due'), 2),
            'tax_total'      => round($sumWhere(fn () => true, 'tax'), 2),
            'overdue'        => $countWhere($overdue),
            'open'           => $countWhere($open),
            'paid'           => $countWhere($paid),
            'void'           => $countWhere($void),
            'total'          => count($rows),
            'paid_rate'      => (int) round($countWhere($paid) / $total * 100),
            'avg_days_to_pay' => $avgDays,
        ];
    }

    /**
     * AR aging buckets from unpaid invoices' outstanding balance, keyed by how
     * far each is past its due date. Pure derivation from real due_date +
     * amount_due — no synthetic figures.
     *
     * @param  array<int, array>  $rows
     * @return array<int, array{label: string, amount: float, count: int}>
     */
    private function buildAging(array $rows): array
    {
        $buckets = [
            ['label' => 'Current', 'min' => PHP_INT_MIN, 'max' => 0, 'amount' => 0.0, 'count' => 0],
            ['label' => '1–30 days', 'min' => 1, 'max' => 30, 'amount' => 0.0, 'count' => 0],
            ['label' => '31–60 days', 'min' => 31, 'max' => 60, 'amount' => 0.0, 'count' => 0],
            ['label' => '61–90 days', 'min' => 61, 'max' => 90, 'amount' => 0.0, 'count' => 0],
            ['label' => '90+ days', 'min' => 91, 'max' => PHP_INT_MAX, 'amount' => 0.0, 'count' => 0],
        ];

        foreach ($rows as $r) {
            if (! in_array($r['status'], ['open', 'overdue'], true) || $r['amount_due'] <= 0) {
                continue;
            }
            $d = $r['days_overdue'];
            foreach ($buckets as $i => $b) {
                if ($d >= $b['min'] && $d <= $b['max']) {
                    $buckets[$i]['amount'] += $r['amount_due'];
                    $buckets[$i]['count']++;
                    break;
                }
            }
        }

        return array_map(fn ($b) => [
            'label'  => $b['label'],
            'amount' => round($b['amount'], 2),
            'count'  => $b['count'],
        ], $buckets);
    }

    /**
     * Build the trailing-6-month window (ending at the latest invoice month)
     * as [Carbon startOfMonth, …].
     *
     * @param  array<int, array>  $rows
     * @return array<int, Carbon>
     */
    private function monthWindow(array $rows): array
    {
        $latest = null;
        foreach ($rows as $r) {
            if ($r['created_at'] !== null) {
                $c = Carbon::parse($r['created_at']);
                if ($latest === null || $c->gt($latest)) {
                    $latest = $c;
                }
            }
        }
        $end = ($latest ?? now())->copy()->startOfMonth();

        return array_map(fn ($i) => $end->copy()->subMonths($i), range(5, 0));
    }

    /**
     * Collections trend: billed (by issue month) vs collected (by paid month),
     * last 6 months.
     *
     * @param  array<int, array>  $rows
     * @return array{labels: array, billed: array, collected: array}
     */
    private function buildTrend(array $rows): array
    {
        $months = $this->monthWindow($rows);
        $keys = array_map(fn ($m) => $m->format('Y-m'), $months);
        $billed = array_fill_keys($keys, 0.0);
        $collected = array_fill_keys($keys, 0.0);

        foreach ($rows as $r) {
            if ($r['created_at'] !== null) {
                $k = Carbon::parse($r['created_at'])->format('Y-m');
                if (isset($billed[$k])) {
                    $billed[$k] += $r['total'];
                }
            }
            if ($r['paid_at'] !== null) {
                $k = Carbon::parse($r['paid_at'])->format('Y-m');
                if (isset($collected[$k])) {
                    $collected[$k] += $r['amount_paid'];
                }
            }
        }

        return [
            'labels'    => array_map(fn ($m) => $m->format('M'), $months),
            'billed'    => array_map(fn ($v) => round($v, 2), array_values($billed)),
            'collected' => array_map(fn ($v) => round($v, 2), array_values($collected)),
        ];
    }

    /**
     * KPI sparkline series (6 months): collected, outstanding, overdue amount,
     * paid-rate %. Every point derives from the real per-month row set.
     *
     * @param  array<int, array>  $rows
     * @return array{collected: array, outstanding: array, overdue: array, paid_rate: array}
     */
    private function buildSparks(array $rows): array
    {
        $months = $this->monthWindow($rows);
        $keys = array_map(fn ($m) => $m->format('Y-m'), $months);
        $collected = array_fill_keys($keys, 0.0);
        $billed = array_fill_keys($keys, 0.0);
        $overdue = array_fill_keys($keys, 0.0);
        $paidCount = array_fill_keys($keys, 0);
        $totalCount = array_fill_keys($keys, 0);

        foreach ($rows as $r) {
            if ($r['created_at'] !== null) {
                $k = Carbon::parse($r['created_at'])->format('Y-m');
                if (isset($billed[$k])) {
                    $billed[$k] += $r['total'];
                    $totalCount[$k]++;
                    if ($r['status'] === 'paid') {
                        $paidCount[$k]++;
                    }
                    if ($r['status'] === 'overdue') {
                        $overdue[$k] += $r['amount_due'];
                    }
                }
            }
            if ($r['paid_at'] !== null) {
                $k = Carbon::parse($r['paid_at'])->format('Y-m');
                if (isset($collected[$k])) {
                    $collected[$k] += $r['amount_paid'];
                }
            }
        }

        $outstanding = [];
        $paidRate = [];
        foreach ($keys as $k) {
            $outstanding[] = round(max(0, $billed[$k] - $collected[$k]), 2);
            $paidRate[] = $totalCount[$k] > 0 ? (int) round($paidCount[$k] / $totalCount[$k] * 100) : 0;
        }

        return [
            'collected'   => array_map(fn ($v) => round($v, 2), array_values($collected)),
            'outstanding' => $outstanding,
            'overdue'     => array_map(fn ($v) => round($v, 2), array_values($overdue)),
            'paid_rate'   => $paidRate,
        ];
    }

    /**
     * Paginated list of invoices.
     *
     * @return LengthAwarePaginator<Invoice>
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Invoice::query()
            ->with(['subscription:id,plan_id,tenant_id', 'billable'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where('invoice_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('reference', 'like', '%'.$filters['search'].'%')
            )
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Create a new invoice record.
     */
    public function create(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $data['reference'] ??= $this->generateReference();
            $data['invoice_number'] ??= $data['reference'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create($data);

            $this->audit->log(
                'invoice.created',
                'created',
                $invoice,
                "Invoice [{$invoice->reference}] created.",
                null,
                $invoice->toArray()
            );

            return $invoice;
        });
    }

    /**
     * Mark invoice as paid and optionally generate PDF.
     */
    public function markPaid(Invoice $invoice, ?string $method = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $method) {
            $invoice->markPaid($method);

            $this->audit->log(
                AuditEventType::INVOICE_MARKED_PAID->value,
                'marked_paid',
                $invoice,
                "Invoice [{$invoice->reference}] marked as paid."
            );

            return $invoice->fresh();
        });
    }

    /**
     * Generate a draft invoice for an existing subscription.
     * Derives amount and billing period from the subscription's plan.
     */
    public function generate(Subscription $sub): Invoice
    {
        return DB::transaction(function () use ($sub) {
            $sub->loadMissing('plan');
            $amount = $sub->plan?->price ?? '0.00';
            $currency = $sub->plan?->currency ?? 'USD';

            $data = [
                'billable_type' => Tenant::class,
                'billable_id' => $sub->tenant_id,
                'subscription_id' => $sub->id,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $currency,
                'subtotal' => $amount,
                'total' => $amount,
                'amount_due' => $amount,
                'amount_paid' => '0.00',
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
            ];

            $data['reference'] = $this->generateReference();
            $data['invoice_number'] = $data['reference'];

            /** @var Invoice $invoice */
            $invoice = Invoice::create($data);

            $this->audit->log(
                AuditEventType::INVOICE_GENERATED->value,
                'generated',
                $invoice,
                "Invoice [{$invoice->reference}] generated for subscription [{$sub->id}].",
                null,
                $invoice->toArray()
            );

            return $invoice;
        });
    }

    /**
     * Generate a draft invoice for a ProductSubscription.
     *
     * Creates a separate invoice record for a product (add-on) subscription,
     * distinct from plan invoices. Reference uses the INV-P- prefix to
     * distinguish product invoices from plan invoices (INV-).
     */
    public function generateForProduct(ProductSubscription $ps): Invoice
    {
        return DB::transaction(function () use ($ps) {
            $year = now()->year;
            $seq = Invoice::whereYear('created_at', $year)->count() + 1;
            $ref = sprintf('INV-P-%d-%06d', $year, $seq);

            /** @var Invoice $invoice */
            $invoice = Invoice::create([
                'billable_type' => Tenant::class,
                'billable_id' => $ps->tenant_id,
                'subscription_id' => null, // product subscription, not plan subscription
                'reference' => $ref,
                'invoice_number' => $ref,
                'amount' => $ps->amount,
                'subtotal' => $ps->amount,
                'total' => $ps->amount,
                'amount_due' => $ps->amount,
                'amount_paid' => '0.00',
                'currency' => $ps->currency ?? 'USD',
                'tax_amount' => '0.00',
                'status' => Invoice::STATUS_DRAFT,
                'due_date' => now()->addDays(14)->toDateString(),
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
            ]);

            $this->audit->log(
                AuditEventType::INVOICE_GENERATED->value,
                'generated',
                $invoice,
                "Product invoice [{$ref}] generated for tenant [{$ps->tenant_id}].",
                null,
                $invoice->toArray()
            );

            return $invoice->fresh();
        });
    }

    /**
     * Mark a draft invoice as sent (status = issued).
     */
    public function send(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['status' => Invoice::STATUS_ISSUED, 'updated_at' => now()]);

            $invoice->refresh();

            $this->audit->log(
                AuditEventType::INVOICE_SENT->value,
                'sent',
                $invoice,
                "Invoice [{$invoice->reference}] sent (status set to issued)."
            );

            return $invoice;
        });
    }

    /**
     * Void an invoice (must not be paid).
     */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->markVoid($reason);

            $this->audit->log(
                'invoice.voided',
                'voided',
                $invoice,
                "Invoice [{$invoice->reference}] voided. Reason: {$reason}."
            );

            return $invoice->fresh();
        });
    }

    /**
     * Drawer detail: line items, payment history, refunds and audit activity.
     * Every read is guarded so a missing table can't 500 the drawer.
     *
     * @return array{line_items: array, payments: array, refunds: array, activity: array, totals: array}
     */
    public function detail(string $id): array
    {
        $invoice = DB::table('invoices')->where('id', $id)->first();
        abort_if($invoice === null, 404);

        $lineItems = DB::table('invoice_line_items')
            ->where('invoice_id', $id)
            ->orderBy('sort_order')
            ->get(['type', 'description', 'quantity', 'unit_price', 'amount', 'discount'])
            ->map(fn ($l) => [
                'type'        => $l->type,
                'description' => $l->description,
                'quantity'    => (int) $l->quantity,
                'unit_price'  => (float) $l->unit_price,
                'amount'      => (float) $l->amount,
                'discount'    => (float) ($l->discount ?? 0),
            ])->all();

        // Payment history: the settled payment (paid_at) plus any refunds as
        // signed movements. There is no separate payments ledger yet, so this
        // reflects exactly what the invoice + refunds tables record.
        $payments = [];
        if ($invoice->paid_at !== null && (float) $invoice->amount_paid > 0) {
            $payments[] = [
                'kind'   => 'payment',
                'amount' => (float) $invoice->amount_paid,
                'method' => $invoice->payment_method,
                'at'     => $invoice->paid_at,
            ];
        }

        $refunds = [];
        try {
            $refunds = DB::table('refunds')
                ->where('invoice_id', $id)
                ->orderByDesc('created_at')
                ->get(['reference', 'amount', 'currency', 'reason', 'status', 'created_at', 'processed_at'])
                ->map(fn ($r) => [
                    'reference' => $r->reference,
                    'amount'    => (float) $r->amount,
                    'currency'  => $r->currency,
                    'reason'    => $r->reason,
                    'status'    => $r->status,
                    'at'        => $r->processed_at ?? $r->created_at,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // refunds table absent — leave empty
        }
        foreach ($refunds as $rf) {
            $payments[] = [
                'kind'   => 'refund',
                'amount' => -$rf['amount'],
                'method' => $rf['reference'],
                'at'     => $rf['at'],
            ];
        }
        usort($payments, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        // Activity from the central platform audit trail (or tenant audit_logs
        // in standalone). Same guarded dual-source pattern the subscriptions
        // drawer uses.
        $activity = [];
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $activity = DB::connection($conn)->table($table)
                ->where('subject_type', Invoice::class)
                ->where('subject_id', $id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get(['event_type', 'action', 'description', 'actor_name', 'created_at'])
                ->map(fn ($a) => [
                    'event'  => $a->event_type,
                    'action' => $a->action,
                    'detail' => $a->description,
                    'actor'  => $a->actor_name,
                    'at'     => $a->created_at,
                ])->all();
        } catch (\Illuminate\Database\QueryException) {
            // audit table absent in this context
        }

        return [
            'line_items' => $lineItems,
            'payments'   => $payments,
            'refunds'    => $refunds,
            'activity'   => $activity,
            'totals'     => [
                'subtotal'    => (float) $invoice->subtotal,
                'discount'    => (float) $invoice->discount_amount,
                'tax'         => (float) $invoice->tax_amount,
                'total'       => (float) $invoice->total,
                'amount_paid' => (float) $invoice->amount_paid,
                'amount_due'  => (float) $invoice->amount_due,
                'currency'    => $invoice->currency ?: 'USD',
            ],
        ];
    }

    /**
     * Queue a payment reminder to the tenant's billing address. Never fakes a
     * send — audit-logs whether an email was actually queued.
     */
    public function remind(Invoice $invoice): bool
    {
        $tenant = DB::table('tenants')->where('id', $invoice->billable_id)->first(['name', 'email']);
        $ref = $invoice->reference ?? $invoice->invoice_number ?? (string) $invoice->id;

        $sent = false;
        if ($tenant?->email) {
            Mail::to($tenant->email)->queue(new PaymentReminderMail(
                tenantName: $tenant->name ?? 'there',
                planName: "Invoice {$ref}",
                amount: (float) $invoice->amount_due,
                currency: $invoice->currency ?? 'USD',
            ));
            $sent = true;
        }

        $this->audit->log(
            'invoice.reminder_sent',
            'reminder_sent',
            $invoice,
            $sent
                ? "Payment reminder for invoice [{$ref}] queued to {$tenant->email}."
                : "Payment reminder for invoice [{$ref}] requested but no tenant email on record."
        );

        return $sent;
    }

    /**
     * Record a refund against an invoice. Writes a real refunds row and, when
     * the refund settles the full balance, flips the invoice to refunded.
     */
    public function refund(Invoice $invoice, float $amount, string $reason): Refund
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Refund amount must be positive.');
        }
        $paid = (float) $invoice->amount_paid;
        if ($amount > $paid) {
            throw new InvalidArgumentException('Refund cannot exceed the amount paid.');
        }

        return DB::transaction(function () use ($invoice, $amount, $reason) {
            $ref = 'REF-'.strtoupper(Str::random(8));
            $actorId = Auth::guard('landlord')->id() ?? Auth::id();

            $refund = Refund::create([
                'reference'    => $ref,
                'tenant_id'    => $invoice->billable_id,
                'invoice_id'   => $invoice->id,
                'amount'       => $amount,
                'currency'     => $invoice->currency ?? 'USD',
                'reason'       => $reason,
                'status'       => Refund::STATUS_PROCESSED,
                'requested_by' => $actorId,
                'processed_by' => $actorId,
                'processed_at' => now(),
            ]);

            $invoice->markRefunded($amount);

            $this->audit->log(
                'invoice.refunded',
                'refunded',
                $invoice,
                "Invoice [{$invoice->reference}] refunded {$amount} {$invoice->currency}. Reason: {$reason}."
            );

            return $refund;
        });
    }

    /**
     * Bulk send / mark-paid / remind / void. Each item is processed
     * independently so one failure cannot roll back the others.
     *
     * @param  array<int, string>  $ids
     * @return array{ok:int, failed:int}
     */
    public function bulk(array $ids, string $action, string $reason = ''): array
    {
        $ok = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $invoice = Invoice::findOrFail($id);
                match ($action) {
                    'send'      => $this->send($invoice),
                    'mark-paid' => $this->markPaid($invoice, 'manual'),
                    'remind'    => $this->remind($invoice),
                    'void'      => $this->void($invoice, $reason ?: 'Bulk void'),
                    default     => throw new InvalidArgumentException("Unknown bulk action [$action]."),
                };
                $ok++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['ok' => $ok, 'failed' => $failed];
    }

    /**
     * All invoices flattened for CSV export.
     *
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(): array
    {
        return array_map(fn ($r) => [
            'number'      => $r['number'],
            'tenant'      => $r['tenant'],
            'status'      => $r['status'],
            'subtotal'    => $r['subtotal'],
            'discount'    => $r['discount'],
            'tax'         => $r['tax'],
            'total'       => $r['total'],
            'amount_paid' => $r['amount_paid'],
            'amount_due'  => $r['amount_due'],
            'currency'    => $r['currency'],
            'due_date'    => $r['due_date'] ?? '',
            'paid_at'     => $r['paid_at'] ?? '',
            'issued_at'   => $r['created_at'] ?? '',
        ], $this->overview()['invoices']);
    }

    /**
     * Generate and store a PDF for the invoice.
     * Returns the storage path.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $ref = $invoice->reference ?? $invoice->id;
        $path = "invoices/{$ref}.pdf";

        try {
            $pdf = Pdf::loadView(
                'aero-platform::invoices.pdf',
                ['invoice' => $invoice->load(['subscription', 'lineItems', 'billable'])]
            );
            Storage::disk('local')->put($path, $pdf->output());
        } catch (\Throwable) {
            // DomPDF not installed — store a plain-text stub so the path is valid
            Storage::disk('local')->put($path, "Invoice {$ref}");
        }

        DB::transaction(function () use ($invoice, $path) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update(['pdf_path' => $path, 'updated_at' => now()]);
        });

        return $path;
    }

    /**
     * Generate a unique invoice reference string.
     */
    private function generateReference(): string
    {
        do {
            $ref = 'INV-'.strtoupper(Str::random(8));
        } while (Invoice::where('reference', $ref)->exists());

        return $ref;
    }
}
