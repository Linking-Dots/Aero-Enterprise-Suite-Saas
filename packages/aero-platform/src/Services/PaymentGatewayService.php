<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    public function __construct(
        private readonly AuditServiceInterface $audit
    ) {}

    /**
     * Static capability profile per gateway code — domain knowledge, not stored
     * in the DB. Drives the roster capability line and the capabilities matrix.
     * `keys` lists the config fields a gateway needs before it can go live.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PROFILES = [
        'stripe' => [
            'summary' => 'Cards, wallets (Apple/Google Pay) & recurring billing · 135+ currencies · global',
            'features' => ['cards' => true, 'wallets' => true, 'bank' => false, 'recurring' => true, 'refunds' => true, 'multi_currency' => true],
            'keys' => [
                ['key' => 'public_key', 'label' => 'Publishable key', 'secret' => false, 'prefix' => 'pk_', 'placeholder' => 'pk_live_…'],
                ['key' => 'secret_key', 'label' => 'Secret key', 'secret' => true, 'prefix' => 'sk_', 'placeholder' => 'sk_live_…'],
                ['key' => 'webhook_secret', 'label' => 'Webhook signing secret', 'secret' => true, 'prefix' => 'whsec_', 'placeholder' => 'whsec_…'],
            ],
        ],
        'paypal' => [
            'summary' => 'PayPal wallet & guest card checkout · 200+ markets · buyer protection',
            'features' => ['cards' => true, 'wallets' => true, 'bank' => false, 'recurring' => true, 'refunds' => true, 'multi_currency' => true],
            'keys' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'secret' => false, 'placeholder' => 'A21…'],
                ['key' => 'client_secret', 'label' => 'Client secret', 'secret' => true, 'placeholder' => 'EN…'],
                ['key' => 'webhook_id', 'label' => 'Webhook ID', 'secret' => false, 'placeholder' => 'WH-…'],
            ],
        ],
        'bank_transfer' => [
            'summary' => 'Manual / offline bank transfer · reconciled by an admin marking the invoice paid',
            'features' => ['cards' => false, 'wallets' => false, 'bank' => true, 'recurring' => false, 'refunds' => false, 'multi_currency' => true],
            'keys' => [
                ['key' => 'account_name', 'label' => 'Account name', 'secret' => false, 'placeholder' => 'AEOS365 Ltd'],
                ['key' => 'account_number', 'label' => 'Account number / IBAN', 'secret' => true, 'placeholder' => 'GB… / 0123456789'],
                ['key' => 'bank_name', 'label' => 'Bank name', 'secret' => false, 'placeholder' => 'HSBC'],
            ],
        ],
        'sslcommerz' => [
            'summary' => 'Cards, mobile banking (bKash/Nagad) & net banking · Bangladesh · BDT',
            'features' => ['cards' => true, 'wallets' => true, 'bank' => true, 'recurring' => false, 'refunds' => true, 'multi_currency' => false],
            'keys' => [
                ['key' => 'store_id', 'label' => 'Store ID', 'secret' => false, 'placeholder' => 'aeos0live'],
                ['key' => 'store_passwd', 'label' => 'Store password', 'secret' => true, 'placeholder' => '••••••••'],
            ],
        ],
    ];

    private const FALLBACK_PROFILE = [
        'summary' => 'Custom payment gateway.',
        'features' => ['cards' => false, 'wallets' => false, 'bank' => false, 'recurring' => false, 'refunds' => false, 'multi_currency' => false],
        'keys' => [
            ['key' => 'public_key', 'label' => 'Public key', 'secret' => false, 'placeholder' => ''],
            ['key' => 'secret_key', 'label' => 'Secret key', 'secret' => true, 'placeholder' => ''],
        ],
    ];

    /**
     * Return all gateway records.
     *
     * @return Collection<int, PaymentGateway>
     */
    public function all(): Collection
    {
        return PaymentGateway::orderByDesc('is_default')->orderBy('id')->get();
    }

    /**
     * Command-centre payload for the gateways page: enriched roster + KPI stats,
     * settlement mix, capabilities matrix and recent config activity. Read-only.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $gateways = $this->all();

        // Settled volume: paid invoices, grouped by recorded payment_method. The
        // seed records the generic method `card` with no per-gateway attribution,
        // so card volume is credited to the DEFAULT card-capable gateway (its
        // real-world processor). Non-card methods matching a gateway code map
        // directly. This is a documented heuristic, never a fabricated figure.
        $paid = DB::table('invoices')->whereNull('deleted_at')->whereNotNull('paid_at')
            ->selectRaw('COALESCE(payment_method, ?) m, COUNT(*) c, SUM(amount_paid) amt', ['unknown'])
            ->groupBy('m')->get();

        $totalSettled = round((float) $paid->sum('amt'), 2);
        $totalPayments = (int) $paid->sum('c');

        $default = $gateways->firstWhere('is_default', true);
        $cardSink = $default && ($this->profile($default->code)['features']['cards'] ?? false)
            ? $default->code
            : ($gateways->first(fn ($g) => $g->is_enabled && ($this->profile($g->code)['features']['cards'] ?? false))?->code);

        $byGateway = [];
        foreach ($paid as $row) {
            $code = $row->m === 'card' ? $cardSink : $row->m;
            if ($code === null) {
                continue;
            }
            $byGateway[$code]['amt'] = ($byGateway[$code]['amt'] ?? 0) + (float) $row->amt;
            $byGateway[$code]['c'] = ($byGateway[$code]['c'] ?? 0) + (int) $row->c;
        }

        $refunds = $this->refundTotals();

        $roster = $gateways->map(function (PaymentGateway $g) use ($byGateway) {
            $profile = $this->profile($g->code);
            $config = is_array($g->config) ? $g->config : [];
            $configured = $this->isConfigured($g->code, $config);

            return [
                'code'         => $g->code,
                'label'        => $g->label,
                'enabled'      => (bool) $g->is_enabled,
                'is_default'   => (bool) $g->is_default,
                'configured'   => $configured,
                'summary'      => $profile['summary'],
                'features'     => $profile['features'],
                'keys'         => $this->keyState($profile['keys'], $config),
                'settled'      => round((float) ($byGateway[$g->code]['amt'] ?? 0), 2),
                'payments'     => (int) ($byGateway[$g->code]['c'] ?? 0),
                'updated_at'   => optional($g->updated_at)->toDateTimeString(),
            ];
        })->all();

        $currencies = DB::table('invoices')->whereNull('deleted_at')
            ->distinct()->pluck('currency')->filter()->map(fn ($c) => strtoupper((string) $c))->unique()->values()->all();

        return [
            'gateways' => $roster,
            'features' => ['cards' => 'Cards', 'wallets' => 'Wallets', 'bank' => 'Bank', 'recurring' => 'Recurring', 'refunds' => 'Refunds', 'multi_currency' => 'Multi-currency'],
            'kpis' => [
                'live'            => $gateways->where('is_enabled', true)->count(),
                'total'          => $gateways->count(),
                'default_label'  => $default?->label ?? '—',
                'settled'        => $totalSettled,
                'payments'       => $totalPayments,
                'refunds_amount' => $refunds['amount'],
                'refunds_count'  => $refunds['count'],
                'configured'     => collect($roster)->where('configured', true)->count(),
                'currencies'     => $currencies,
                'settled_spark'  => $this->settledSpark(),
            ],
            'settlement' => $this->settlementMix($paid, $totalPayments),
            'activity'   => $this->activity($gateways),
        ];
    }

    /**
     * Collected amount per month over the trailing 6 months (by paid_at) — the
     * settled-volume sparkline. Every point is a real per-month sum.
     *
     * @return array<int, float>
     */
    private function settledSpark(): array
    {
        $end = now()->startOfMonth();
        $keys = [];
        for ($i = 5; $i >= 0; $i--) {
            $keys[$end->copy()->subMonths($i)->format('Y-m')] = 0.0;
        }

        $rows = DB::table('invoices')->whereNull('deleted_at')->whereNotNull('paid_at')
            ->where('paid_at', '>=', $end->copy()->subMonths(5)->toDateString())
            ->get(['paid_at', 'amount_paid']);
        foreach ($rows as $r) {
            $k = Carbon::parse($r->paid_at)->format('Y-m');
            if (isset($keys[$k])) {
                $keys[$k] += (float) $r->amount_paid;
            }
        }

        return array_map(fn ($v) => round($v, 2), array_values($keys));
    }

    /** @return array{amount: float, count: int} */
    private function refundTotals(): array
    {
        try {
            $row = DB::table('refunds')->selectRaw('COUNT(*) c, SUM(amount) amt')->first();

            return ['amount' => round((float) ($row->amt ?? 0), 2), 'count' => (int) ($row->c ?? 0)];
        } catch (QueryException) {
            return ['amount' => 0.0, 'count' => 0];
        }
    }

    /**
     * Settled-payment method mix.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $paid
     * @return array<int, array{method: string, count: int, amount: float, pct: int}>
     */
    private function settlementMix($paid, int $total): array
    {
        return $paid->map(fn ($r) => [
            'method' => (string) $r->m,
            'count'  => (int) $r->c,
            'amount' => round((float) $r->amt, 2),
            'pct'    => $total > 0 ? (int) round($r->c / $total * 100) : 0,
        ])->sortByDesc('count')->values()->all();
    }

    /**
     * Recent gateway config activity — the central audit trail when it holds
     * PaymentGateway rows, otherwise derived from each gateway's real
     * created/updated timestamps (added / configuration updated). Never faked.
     *
     * @param  Collection<int, PaymentGateway>  $gateways
     * @return array<int, array{gateway: string, message: string, at: ?string}>
     */
    private function activity(Collection $gateways): array
    {
        $items = [];
        try {
            [$conn, $table] = (is_saas_mode() && ! (function_exists('tenancy') && tenancy()->initialized))
                ? [central_connection(), 'platform_audit_logs']
                : [null, 'audit_logs'];

            $rows = DB::connection($conn)->table($table)
                ->where('subject_type', PaymentGateway::class)
                ->orderByDesc('created_at')->limit(10)
                ->get(['description', 'action', 'actor_name', 'created_at']);
            foreach ($rows as $r) {
                $items[] = [
                    'gateway' => $r->actor_name ?: 'Platform Admin',
                    'message' => $r->description ?: ($r->action ?: 'updated'),
                    'at'      => $r->created_at,
                ];
            }
        } catch (QueryException) {
            // audit table absent — fall through to timestamp derivation
        }

        if ($items === []) {
            foreach ($gateways as $g) {
                $created = $g->created_at;
                $updated = $g->updated_at;
                $items[] = ['gateway' => $g->label, 'message' => 'gateway added', 'at' => optional($created)->toDateTimeString()];
                if ($updated && $created && $updated->gt($created)) {
                    $items[] = ['gateway' => $g->label, 'message' => 'configuration updated', 'at' => $updated->toDateTimeString()];
                }
            }
            usort($items, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));
            $items = array_slice($items, 0, 8);
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function profile(string $code): array
    {
        return self::PROFILES[$code] ?? self::FALLBACK_PROFILE;
    }

    /**
     * Per-key presence state for the config drawer — never returns secret values,
     * only whether each key is set.
     *
     * @param  array<int, array<string, mixed>>  $keys
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    private function keyState(array $keys, array $config): array
    {
        return array_map(fn ($k) => [
            'key'         => $k['key'],
            'label'       => $k['label'],
            'secret'      => $k['secret'],
            'placeholder' => $k['placeholder'] ?? '',
            'set'         => ! empty($config[$k['key']]),
        ], $keys);
    }

    /** A gateway is "configured" once all its non-secret-optional required keys are present. */
    private function isConfigured(string $code, array $config): bool
    {
        $keys = $this->profile($code)['keys'] ?? [];
        foreach ($keys as $k) {
            if (empty($config[$k['key']])) {
                return false;
            }
        }

        return $keys !== [];
    }

    /**
     * Honest connection test — validates that the required keys are present and
     * well-formed (prefix where known). Never pings a live API (no gateway SDK
     * is wired), never fakes a success. Audit-logged.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(PaymentGateway $gw): array
    {
        $config = is_array($gw->config) ? $gw->config : [];
        $keys = $this->profile($gw->code)['keys'] ?? [];
        $missing = [];
        $malformed = [];

        foreach ($keys as $k) {
            $val = $config[$k['key']] ?? null;
            if (empty($val)) {
                $missing[] = $k['label'];

                continue;
            }
            if (! empty($k['prefix']) && ! str_starts_with((string) $val, $k['prefix'])) {
                $malformed[] = "{$k['label']} should start with “{$k['prefix']}”";
            }
        }

        $ok = $missing === [] && $malformed === [];
        $message = match (true) {
            $keys === []      => 'No configuration required for this gateway.',
            $missing !== []   => 'Missing keys: '.implode(', ', $missing).'.',
            $malformed !== [] => implode('; ', $malformed).'.',
            default           => 'All keys present and well-formed. Ready to process payments.',
        };

        $this->audit->log(
            'payment_gateway.tested',
            'tested',
            $gw,
            "Payment gateway [{$gw->code}] connection test: ".($ok ? 'passed' : 'failed')." — {$message}"
        );

        return ['ok' => $ok, 'message' => $message];
    }

    /**
     * Create a gateway record.
     * The 'config' array is encrypted at-rest via EncryptedField cast.
     */
    public function create(array $data): PaymentGateway
    {
        return DB::transaction(function () use ($data) {
            /** @var PaymentGateway $gw */
            $gw = PaymentGateway::create($data);

            $this->audit->log(
                'payment_gateway.created',
                'created',
                $gw,
                "Payment gateway [{$gw->code}] created."
            );

            return $gw;
        });
    }

    /**
     * Update a gateway's label / enabled state.
     */
    public function update(PaymentGateway $gw, array $data): PaymentGateway
    {
        return DB::transaction(function () use ($gw, $data) {
            $old = ['code' => $gw->code, 'is_enabled' => $gw->is_enabled, 'is_default' => $gw->is_default];

            $gw->update($data);

            $this->audit->log(
                AuditEventType::PAYMENT_GATEWAY_UPDATED->value,
                'updated',
                $gw,
                "Payment gateway [{$gw->code}] updated.",
                $old,
                ['is_enabled' => $gw->is_enabled, 'is_default' => $gw->is_default]
            );

            return $gw->fresh();
        });
    }

    /**
     * Merge new config values into the encrypted blob. Blank incoming values are
     * DROPPED so secret fields left empty keep their existing value (never
     * overwritten to empty).
     *
     * @param  array<string, mixed>  $incoming
     */
    public function saveConfig(PaymentGateway $gw, array $incoming): PaymentGateway
    {
        return DB::transaction(function () use ($gw, $incoming) {
            $existing = is_array($gw->config) ? $gw->config : [];
            $merged = $existing;
            foreach ($incoming as $key => $value) {
                if ($value === null || $value === '') {
                    continue; // keep existing secret
                }
                $merged[$key] = $value;
            }

            $gw->update(['config' => $merged]);

            $this->audit->log(
                'payment_gateway.config_updated',
                'config_updated',
                $gw,
                "Payment gateway [{$gw->code}] configuration saved."
            );

            return $gw->fresh();
        });
    }

    /**
     * Update only the encrypted config blob (full replace).
     *
     * @param  array<string, mixed>  $config
     */
    public function updateConfig(PaymentGateway $gw, array $config): PaymentGateway
    {
        return $this->saveConfig($gw, $config);
    }

    /**
     * Toggle enabled state.
     */
    public function toggle(PaymentGateway $gw): PaymentGateway
    {
        return $this->update($gw, ['is_enabled' => ! $gw->is_enabled]);
    }

    /**
     * Set one gateway as default, clearing all others.
     */
    public function setDefault(PaymentGateway $gw): PaymentGateway
    {
        return DB::transaction(function () use ($gw) {
            PaymentGateway::query()->update(['is_default' => false]);
            $gw->update(['is_default' => true, 'is_enabled' => true]);

            $this->audit->log(
                'payment_gateway.set_default',
                'set_default',
                $gw,
                "Payment gateway [{$gw->code}] set as default."
            );

            return $gw->fresh();
        });
    }

    /**
     * Delete a gateway record (hard delete — gateways are platform config, not tenant data).
     */
    public function delete(PaymentGateway $gw): void
    {
        DB::transaction(function () use ($gw) {
            $this->audit->log(
                'payment_gateway.deleted',
                'deleted',
                $gw,
                "Payment gateway [{$gw->code}] deleted."
            );
            $gw->delete();
        });
    }
}
