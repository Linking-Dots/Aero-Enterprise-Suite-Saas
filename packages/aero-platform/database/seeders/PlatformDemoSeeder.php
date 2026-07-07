<?php

namespace Aero\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * PlatformDemoSeeder
 *
 * Idempotent landlord-DB demo data so every platform-admin page shows realistic
 * (non-empty) data for the FYP demo. Uses deterministic `demo-*` primary keys +
 * updateOrInsert so re-running never duplicates. Raw DB inserts only (never
 * Tenant::create) so no provisioning jobs/observers fire and no tenant databases
 * are created. All timestamps are Asia/Dhaka.
 *
 * Run standalone:  php artisan db:seed --class="Aero\Platform\Database\Seeders\PlatformDemoSeeder"
 * Or chain from PlatformDatabaseSeeder.
 */
class PlatformDemoSeeder extends Seeder
{
    private string $tz = 'Asia/Dhaka';

    /** @var array<string,array{name:string,sub:string,status:string,plan:string,cur:string,monthsAgo:int,region:string}> */
    private array $tenantSpecs = [];

    /** @var array<string,object> plan-id => plan row (with prices) */
    private array $plans = [];

    /** @var array<string,object> product-id => product row */
    private array $products = [];

    public function run(): void
    {
        mt_srand(20260706); // reproducible "random" distributions
        Carbon::setTestNow(Carbon::now($this->tz));

        $this->command->info('🌱 PlatformDemoSeeder: seeding landlord demo data...');

        $this->cluster1_plans();
        $this->cluster2_tenants();
        $this->cluster3_subscriptions();
        $this->cluster4_billing();
        $this->cluster4b_billingExtras();
        $this->cluster5_money();
        $this->cluster6_analytics();
        $this->cluster7_growth();
        $this->cluster8_ops();

        Carbon::setTestNow();
        $this->command->info('✅ PlatformDemoSeeder complete.');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                            */
    /* ------------------------------------------------------------------ */

    private function now(): Carbon
    {
        return Carbon::now($this->tz);
    }

    private function ago(int $days, int $hour = 10, int $min = 0): Carbon
    {
        return Carbon::now($this->tz)->subDays($days)->setTime($hour, $min, mt_rand(0, 59));
    }

    private function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    private function demoTenantIds(): array
    {
        return array_keys($this->tenantSpecs());
    }

    /**
     * The canonical 24 demo tenants. Deterministic. status drives everything downstream.
     */
    private function tenantSpecs(): array
    {
        if ($this->tenantSpecs) {
            return $this->tenantSpecs;
        }

        // [name, subdomain, status, planName, currency, monthsAgo, region]
        $rows = [
            ['Nimbus Retail Group',      'nimbus',       'active',       'Professional', 'USD', 11, 'North America'],
            ['Vertex Logistics',         'vertexlog',    'active',       'Business',     'USD', 10, 'North America'],
            ['Aurora Health Systems',    'aurorahealth', 'active',       'Enterprise',   'USD', 12, 'North America'],
            ['Meridian Consulting',      'meridian',     'active',       'Starter',      'GBP', 9,  'Europe'],
            ['Cobalt Manufacturing',     'cobalt',       'active',       'Business',     'EUR', 8,  'Europe'],
            ['Solstice Media',           'solstice',     'active',       'Professional', 'USD', 7,  'North America'],
            ['Harbor Financial',         'harborfin',    'active',       'Enterprise',   'USD', 10, 'North America'],
            ['Willow Creek Foods',       'willowcreek',  'active',       'Starter',      'USD', 6,  'North America'],
            ['Zenith Software',          'zenithsoft',   'active',       'Professional', 'EUR', 9,  'Europe'],
            ['Terra Agritech',           'terraagri',    'active',       'Business',     'BDT', 5,  'Asia'],
            ['Pinnacle Realty',          'pinnacle',     'active',       'Starter',      'USD', 4,  'North America'],
            ['Lumen Education',          'lumenedu',     'active',       'Professional', 'GBP', 8,  'Europe'],
            ['Cascade Ventures',         'cascade',      'trial',        'Business',     'USD', 1,  'North America'],
            ['Ember Analytics',          'emberanalytics','trial',       'Professional', 'USD', 1,  'North America'],
            ['Quartz Dynamics',          'quartz',       'trial',        'Starter',      'EUR', 0,  'Europe'],
            ['Slate & Co',               'slateco',      'pending',      'Starter',      'USD', 0,  'North America'],
            ['Beacon Interactive',       'beacon',       'pending',      'Business',     'USD', 0,  'North America'],
            ['Onyx Trading',             'onyxtrading',  'provisioning', 'Professional', 'USD', 0,  'Asia'],
            ['Fjord Design Studio',      'fjord',        'provisioning', 'Starter',      'EUR', 0,  'Europe'],
            ['Halcyon Group',            'halcyon',      'suspended',    'Business',     'USD', 7,  'North America'],
            ['Drift Logistics',          'driftlog',     'suspended',    'Starter',      'USD', 5,  'North America'],
            ['Pallet Freight Co',        'palletfreight','failed',       'Starter',      'USD', 0,  'North America'],
            ['Verdant Farms',            'verdant',      'failed',       'Business',     'BDT', 0,  'Asia'],
            ['Old Mill Brewing',         'oldmill',      'archived',     'Professional', 'USD', 12, 'North America'],
        ];

        $specs = [];
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $id = sprintf('demo-tnnt-%02d', $i);
            $specs[$id] = [
                'name'      => $r[0],
                'sub'       => $r[1],
                'status'    => $r[2],
                'plan'      => $r[3],
                'cur'       => $r[4],
                'monthsAgo' => $r[5],
                'region'    => $r[6],
            ];
        }

        return $this->tenantSpecs = $specs;
    }

    private function planByName(string $name): ?object
    {
        foreach ($this->plans as $p) {
            if (strcasecmp($p->name, $name) === 0) {
                return $p;
            }
        }
        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 1 — Plan price enrichment                                  */
    /* ------------------------------------------------------------------ */

    private function cluster1_plans(): void
    {
        $prices = [
            'Free'         => [0, 0],
            'Starter'      => [29, 290],
            'Business'     => [79, 790],
            'Professional' => [149, 1490],
            'Enterprise'   => [399, 3990],
        ];

        foreach (DB::table('plans')->get() as $plan) {
            if (isset($prices[$plan->name])) {
                [$m, $a] = $prices[$plan->name];
                DB::table('plans')->where('id', $plan->id)->update([
                    'monthly_price' => $m,
                    'yearly_price'  => $a,
                    'price_monthly' => $m,
                    'price_annual'  => $a,
                    'updated_at'    => $this->now(),
                ]);
            }
        }

        $this->plans = [];
        foreach (DB::table('plans')->get() as $plan) {
            $this->plans[$plan->id] = $plan;
        }
        foreach (DB::table('products')->get() as $prod) {
            $this->products[$prod->id] = $prod;
        }

        $this->command->info('  ✓ plans enriched ('.count($this->plans).' plans, '.count($this->products).' products)');
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 2 — Tenants + Domains                                      */
    /* ------------------------------------------------------------------ */

    private function cluster2_tenants(): void
    {
        $count = 0;
        foreach ($this->tenantSpecs() as $id => $s) {
            $created = $this->ago($s['monthsAgo'] * 30 + mt_rand(0, 20), mt_rand(8, 18));

            $suspendedAt = null;
            $suspensionReason = null;
            $archivedAt = null;
            $archivedReason = null;
            $provisioningStep = null;

            if ($s['status'] === 'suspended') {
                $suspendedAt = $this->ago(mt_rand(5, 40));
                $suspensionReason = $this->pick(['Payment overdue > 30 days', 'Terms of service violation', 'Requested by account owner']);
            }
            if ($s['status'] === 'archived') {
                $archivedAt = $this->ago(mt_rand(20, 90));
                $archivedReason = 'Account closed — data retention window';
            }
            if ($s['status'] === 'provisioning') {
                $provisioningStep = $this->pick(['creating_database', 'running_migrations', 'seeding_defaults']);
            }
            if ($s['status'] === 'failed') {
                $provisioningStep = 'failed:database_create';
            }

            $emailVerified = in_array($s['status'], ['active', 'suspended', 'archived', 'trial'], true) ? $created->copy()->addMinutes(6) : null;

            DB::table('tenants')->updateOrInsert(['id' => $id], [
                'name'                    => $s['name'],
                'type'                    => 'company',
                'subdomain'               => $s['sub'],
                'email'                   => 'admin@'.$s['sub'].'.com',
                'phone'                   => '+1'.mt_rand(200, 989).mt_rand(2000000, 9999999),
                'currency'                => $s['cur'],
                'exchange_rate_at_signup' => 1.0,
                'data'                    => json_encode(['currency' => $s['cur'], 'region' => $s['region']]),
                'status'                  => $s['status'],
                'suspended_at'            => $suspendedAt,
                'suspension_reason'       => $suspensionReason,
                'archived_at'             => $archivedAt,
                'archived_reason'         => $archivedReason,
                'provisioning_step'       => $provisioningStep,
                'admin_email_verified_at' => $emailVerified,
                'company_email_verified_at' => $emailVerified,
                'registration_step'       => $s['status'] === 'pending' ? 'verify_email' : 'complete',
                'created_at'              => $created,
                'updated_at'              => $suspendedAt ?? $archivedAt ?? $created,
                'deleted_at'              => null,
            ]);

            // primary domain (skip for pending/failed that never provisioned a domain? keep for realism)
            $domStatus = match ($s['status']) {
                'active', 'suspended', 'archived' => 'active',
                'trial'                            => 'active',
                'provisioning'                     => 'pending',
                'failed'                           => 'failed',
                default                            => 'pending',
            };
            DB::table('domains')->updateOrInsert(
                ['domain' => $s['sub'].'.aeos365.com'],
                [
                    'tenant_id'  => $id,
                    'is_primary' => 1,
                    'is_custom'  => 0,
                    'status'     => $domStatus,
                    'verified_at'=> $domStatus === 'active' ? $created : null,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]
            );

            $count++;
        }

        $this->command->info("  ✓ tenants+domains seeded ($count tenants)");
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 3 — Subscriptions + ProductSubscriptions + modules + audit */
    /* ------------------------------------------------------------------ */

    private function cluster3_subscriptions(): void
    {
        $demoIds = $this->demoTenantIds();

        // clean log-style rows tied to demo tenants for idempotency
        DB::table('subscription_audit_logs')->whereIn('tenant_id', $demoIds)->delete();

        $subCount = 0;
        $prodSubCount = 0;
        foreach ($this->tenantSpecs() as $tenantId => $s) {
            $plan = $this->planByName($s['plan']);
            if (! $plan) {
                continue;
            }

            $created = optional(DB::table('tenants')->where('id', $tenantId)->first())->created_at;
            $created = $created ? Carbon::parse($created, $this->tz) : $this->ago(120);

            $cycle = mt_rand(0, 100) < 35 ? 'yearly' : 'monthly';
            $amount = $cycle === 'yearly' ? (float) $plan->yearly_price : (float) $plan->monthly_price;

            $subStatus = match ($s['status']) {
                'active'                 => 'active',
                'trial'                  => 'trialing',
                'suspended'              => 'past_due',
                'archived'               => 'cancelled',
                'provisioning', 'pending', 'failed' => 'incomplete',
                default                  => 'active',
            };

            $periodStart = $created->copy();
            $periodEnd = $cycle === 'yearly' ? $created->copy()->addYear() : $created->copy()->addMonthNoOverflow();
            // roll forward to a current period for active subs
            if ($subStatus === 'active') {
                while ($periodEnd->isPast()) {
                    $periodStart = $periodEnd->copy();
                    $periodEnd = $cycle === 'yearly' ? $periodEnd->copy()->addYear() : $periodEnd->copy()->addMonthNoOverflow();
                }
            }

            $subId = 'demo-sub-'.substr($tenantId, 10); // demo-sub-01..24
            DB::table('subscriptions')->updateOrInsert(['id' => $subId], [
                'billable_type'       => 'Aero\\Platform\\Models\\Tenant',
                'billable_id'         => $tenantId,
                'tenant_id'           => $tenantId,
                'type'                => 'default',
                'name'                => 'plan',
                'plan_id'             => $plan->id,
                'billing_cycle'       => $cycle,
                'amount'              => $amount,
                'currency'            => $s['cur'],
                'status'              => $subStatus,
                'current_period_start'=> $periodStart,
                'current_period_end'  => $periodEnd,
                'next_billing_date'   => $subStatus === 'active' ? $periodEnd : null,
                'trial_starts_at'     => $s['status'] === 'trial' ? $created : null,
                'trial_ends_at'       => $s['status'] === 'trial' ? $created->copy()->addDays(14) : null,
                'starts_at'           => $created,
                'ends_at'             => $subStatus === 'cancelled' ? $created->copy()->addMonths(3) : null,
                'cancelled_at'        => $subStatus === 'cancelled' ? $created->copy()->addMonths(3) : null,
                'payment_method'      => $this->pick(['card', 'card', 'card', 'bank_transfer']),
                'metadata'            => json_encode(['demo' => true]),
                'created_at'          => $created,
                'updated_at'          => $periodStart,
                'deleted_at'          => null,
            ]);
            $subCount++;

            // product subscription (HRM add-on) for ~half of active/trial tenants
            if (in_array($s['status'], ['active', 'trial'], true) && $this->products && mt_rand(0, 100) < 55) {
                $prod = array_values($this->products)[0];
                $pCycle = $cycle;
                $pAmount = ($pCycle === 'yearly' ? (float) $prod->yearly_price : (float) $prod->monthly_price);
                $psId = 'demo-psub-'.substr($tenantId, 10);
                DB::table('product_subscriptions')->updateOrInsert(['id' => $psId], [
                    'tenant_id'      => $tenantId,
                    'product_id'     => $prod->id,
                    'billing_cycle'  => $pCycle,
                    'amount'         => $pAmount,
                    'currency'       => $s['cur'],
                    'status'         => $subStatus === 'trialing' ? 'trialing' : 'active',
                    'starts_at'      => $created,
                    'trial_starts_at'=> $s['status'] === 'trial' ? $created : null,
                    'trial_ends_at'  => $s['status'] === 'trial' ? $created->copy()->addDays(14) : null,
                    'payment_method' => 'card',
                    'metadata'       => json_encode(['demo' => true]),
                    'created_at'     => $created,
                    'updated_at'     => $created,
                    'deleted_at'     => null,
                ]);
                $prodSubCount++;

                // subscription_module mirror row
                DB::table('subscription_modules')->updateOrInsert(['id' => 'demo-smod-'.substr($tenantId, 10)], [
                    'billable_type'  => 'Aero\\Platform\\Models\\Tenant',
                    'billable_id'    => $tenantId,
                    'module_code'    => $prod->module_code,
                    'billing_cycle'  => $pCycle,
                    'amount'         => $pAmount,
                    'currency'       => $s['cur'],
                    'status'         => 'active',
                    'starts_at'      => $created,
                    'created_at'     => $created,
                    'updated_at'     => $created,
                    'deleted_at'     => null,
                ]);
            }

            // subscription lifecycle events -> subscription_audit_logs
            $events = [['created', 'Subscription created on '.$s['plan'].' plan', $created]];
            if ($s['status'] === 'active' && mt_rand(0, 100) < 30) {
                $events[] = ['plan_changed', 'Upgraded plan', $created->copy()->addMonths(2)];
            }
            if ($subStatus === 'past_due') {
                $events[] = ['payment_failed', 'Payment failed — entered dunning', $created->copy()->addMonths(3)];
                $events[] = ['suspended', 'Subscription suspended for non-payment', $created->copy()->addMonths(3)->addDays(14)];
            }
            if ($subStatus === 'cancelled') {
                $events[] = ['cancelled', 'Subscription cancelled by customer', $created->copy()->addMonths(3)];
            }
            foreach ($events as $e) {
                DB::table('subscription_audit_logs')->insert([
                    'tenant_id'       => $tenantId,
                    'subscription_id' => $subId,
                    'event_type'      => $e[0],
                    'description'     => $e[1],
                    'ip_address'      => '203.0.113.'.mt_rand(2, 250),
                    'created_at'      => $e[2],
                ]);
            }
        }

        $this->command->info("  ✓ subscriptions seeded ($subCount plan subs, $prodSubCount product subs)");
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 4 — Billing                                                */
    /* ------------------------------------------------------------------ */

    private function cluster4_billing(): void
    {
        $demoIds = $this->demoTenantIds();

        // payment gateways (natural key = code)
        $gateways = [
            ['stripe', 'Stripe', 1, 1],
            ['paypal', 'PayPal', 1, 0],
            ['bank_transfer', 'Bank Transfer', 1, 0],
            ['sslcommerz', 'SSLCommerz (BDT)', 0, 0],
        ];
        foreach ($gateways as $g) {
            DB::table('payment_gateways')->updateOrInsert(['code' => $g[0]], [
                'label'      => $g[1],
                'is_enabled' => $g[2],
                'is_default' => $g[3],
                'config'     => json_encode(['mode' => 'live', 'demo' => true]),
                'created_at' => $this->ago(300),
                'updated_at' => $this->now(),
            ]);
        }

        // invoices: 6 months history per active/suspended tenant. clean demo invoices first.
        $demoInvoiceIds = DB::table('invoices')->where('id', 'like', 'demo-inv-%')->pluck('id')->all();
        if ($demoInvoiceIds) {
            DB::table('invoice_line_items')->whereIn('invoice_id', $demoInvoiceIds)->delete();
            DB::table('invoices')->whereIn('id', $demoInvoiceIds)->delete();
        }

        $invCount = 0;
        $seq = 1000;
        foreach ($this->tenantSpecs() as $tenantId => $s) {
            if (! in_array($s['status'], ['active', 'suspended', 'archived'], true)) {
                continue;
            }
            $sub = DB::table('subscriptions')->where('tenant_id', $tenantId)->first();
            if (! $sub) {
                continue;
            }
            $plan = $this->plans[$sub->plan_id] ?? null;
            $monthly = $plan ? (float) $plan->monthly_price : (float) $sub->amount;
            if ($monthly <= 0) {
                $monthly = 29;
            }

            $months = $s['status'] === 'archived' ? 3 : 6;
            for ($m = $months; $m >= 1; $m--) {
                $seq++;
                $issued = $this->ago($m * 30, mt_rand(1, 9));
                $subtotal = $monthly;
                $discount = mt_rand(0, 100) < 20 ? round($monthly * 0.1, 2) : 0;
                $tax = round(($subtotal - $discount) * 0.0, 2); // no tax by default
                $total = $subtotal - $discount + $tax;

                // status: recent-most may be open/overdue; older paid; suspended -> overdue latest
                if ($m === 1) {
                    $status = $s['status'] === 'suspended' ? 'overdue' : $this->pick(['paid', 'open']);
                } elseif ($m === 2 && $s['status'] === 'suspended') {
                    $status = 'overdue';
                } else {
                    $status = 'paid';
                }
                $paidAt = $status === 'paid' ? $issued->copy()->addDays(mt_rand(0, 5)) : null;
                $due = $issued->copy()->addDays(14);

                $invId = sprintf('demo-inv-%04d', $seq);
                DB::table('invoices')->insert([
                    'id'                  => $invId,
                    'reference'           => 'REF-'.$seq,
                    'amount'              => $total,
                    'billable_type'       => 'Aero\\Platform\\Models\\Tenant',
                    'billable_id'         => $tenantId,
                    'subscription_id'     => $sub->id,
                    'invoice_number'      => 'INV-2026-'.$seq,
                    'status'              => $status,
                    'currency'            => strtolower($s['cur']),
                    'subtotal'            => $subtotal,
                    'discount_amount'     => $discount,
                    'tax_amount'          => $tax,
                    'total'               => $total,
                    'amount_paid'         => $status === 'paid' ? $total : 0,
                    'amount_due'          => $status === 'paid' ? 0 : $total,
                    'billing_period_start'=> $issued->copy()->startOfMonth()->toDateString(),
                    'billing_period_end'  => $issued->copy()->endOfMonth()->toDateString(),
                    'payment_method'      => 'card',
                    'paid_at'             => $paidAt,
                    'due_date'            => $due,
                    'metadata'            => json_encode(['demo' => true]),
                    'created_at'          => $issued,
                    'updated_at'          => $paidAt ?? $issued,
                ]);

                DB::table('invoice_line_items')->insert([
                    'id'          => (string) Str::uuid(),
                    'invoice_id'  => $invId,
                    'type'        => 'plan',
                    'description' => $s['plan'].' plan — monthly',
                    'quantity'    => 1,
                    'unit_price'  => $subtotal,
                    'amount'      => $subtotal,
                    'discount'    => $discount,
                    'plan_id'     => $sub->plan_id,
                    'sort_order'  => 0,
                    'created_at'  => $issued,
                    'updated_at'  => $issued,
                ]);
                $invCount++;
            }
        }

        $this->command->info("  ✓ billing seeded ($invCount invoices, ".count($gateways)." gateways)");
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 4b — Refunds, CreditNotes, Coupons, Dunning, Invoice cfg   */
    /* ------------------------------------------------------------------ */

    private function cluster4b_billingExtras(): void
    {
        $demoIds = $this->demoTenantIds();

        // Refunds — a few against paid invoices (natural key = reference)
        $paidInvoices = DB::table('invoices')->where('id', 'like', 'demo-inv-%')->where('status', 'paid')->orderBy('id')->limit(6)->get();
        $r = 0;
        foreach ($paidInvoices as $inv) {
            $r++;
            $amt = round($inv->total * (mt_rand(30, 100) / 100), 2);
            $status = $this->pick(['processed', 'processed', 'pending', 'approved']);
            DB::table('refunds')->updateOrInsert(['reference' => 'RFND-'.str_pad((string) $r, 4, '0', STR_PAD_LEFT)], [
                'tenant_id'     => $inv->billable_id,
                'invoice_id'    => null, // invoices use uuid pk; refunds.invoice_id is bigint (schema mismatch)
                'amount'        => $amt,
                'currency'      => strtoupper($inv->currency),
                'reason'        => $this->pick(['Duplicate charge', 'Service downgrade — prorated', 'Customer goodwill credit', 'Billing error']),
                'status'        => $status,
                'requested_by'  => 1,
                'approved_by'   => $status !== 'pending' ? 1 : null,
                'processed_by'  => $status === 'processed' ? 1 : null,
                'approved_at'   => $status !== 'pending' ? Carbon::parse($inv->created_at, $this->tz)->addDays(2) : null,
                'processed_at'  => $status === 'processed' ? Carbon::parse($inv->created_at, $this->tz)->addDays(3) : null,
                'created_at'    => Carbon::parse($inv->created_at, $this->tz)->addDay(),
                'updated_at'    => Carbon::parse($inv->created_at, $this->tz)->addDays(3),
            ]);
        }

        // Credit notes
        $creditTenants = array_slice($this->demoTenantIds(), 0, 4);
        $cn = 0;
        foreach ($creditTenants as $tid) {
            $cn++;
            $amt = mt_rand(20, 200);
            DB::table('credit_notes')->updateOrInsert(['reference' => 'CN-'.str_pad((string) $cn, 4, '0', STR_PAD_LEFT)], [
                'tenant_id'   => $tid,
                'amount'      => $amt,
                'currency'    => 'USD',
                'reason'      => $this->pick(['Service credit', 'Loyalty discount', 'SLA breach compensation']),
                'amount_used' => $cn % 2 === 0 ? round($amt / 2, 2) : 0,
                'status'      => $cn % 2 === 0 ? 'partially_applied' : 'open',
                'created_by'  => 1,
                'created_at'  => $this->ago(mt_rand(10, 120)),
                'updated_at'  => $this->now(),
            ]);
        }

        // Coupon campaigns + coupons (natural keys; capture real bigint ids for FKs)
        $campaigns = [
            ['Spring Launch 2026', 'active'],
            ['Black Friday', 'ended'],
            ['Partner Referral', 'active'],
        ];
        $campaignIds = [];
        foreach ($campaigns as $c) {
            DB::table('coupon_campaigns')->updateOrInsert(['name' => $c[0]], [
                'description' => $c[0].' promotional campaign',
                'status'      => $c[1],
                'starts_at'   => $this->ago(120),
                'ends_at'     => $c[1] === 'ended' ? $this->ago(30) : $this->now()->addDays(30),
                'created_by'  => 1,
                'created_at'  => $this->ago(120),
                'updated_at'  => $this->now(),
            ]);
            $campaignIds[$c[0]] = DB::table('coupon_campaigns')->where('name', $c[0])->value('id');
        }
        $coupons = [
            ['WELCOME20', 'Welcome 20% Off', 'percent', 20, 'Spring Launch 2026', 'active', 100, 34],
            ['SAVE50', '$50 Off Annual', 'fixed', 50, 'Spring Launch 2026', 'active', 50, 12],
            ['BF2026', 'Black Friday 40%', 'percent', 40, 'Black Friday', 'archived', 500, 218],
            ['PARTNER15', 'Partner 15%', 'percent', 15, 'Partner Referral', 'active', null, 7],
        ];
        $couponIds = [];
        foreach ($coupons as $c) {
            DB::table('coupons')->updateOrInsert(['code' => $c[0]], [
                'name'             => $c[1],
                'type'             => $c[2],
                'value'            => $c[3],
                'currency'         => $c[2] === 'fixed' ? 'USD' : null,
                'duration'         => $this->pick(['once', 'forever', 'repeating']),
                'max_redemptions'  => $c[6],
                'redemption_count' => $c[7],
                'expires_at'       => $c[5] === 'archived' ? $this->ago(30) : $this->now()->addDays(60),
                'status'           => $c[5],
                'campaign_id'      => $campaignIds[$c[4]] ?? null,
                'created_by'       => 1,
                'created_at'       => $this->ago(120),
                'updated_at'       => $this->now(),
            ]);
            $couponIds[] = DB::table('coupons')->where('code', $c[0])->value('id');
        }
        // Redemptions
        DB::table('coupon_redemptions')->whereIn('coupon_id', $couponIds)->delete();
        $redemptionTenants = array_slice($this->demoTenantIds(), 0, 8);
        foreach ($redemptionTenants as $i => $tid) {
            DB::table('coupon_redemptions')->insert([
                'coupon_id'         => $couponIds[$i % 3],
                'tenant_id'         => $tid,
                'subscribable_type' => null, // subscribable_id is bigint; subscriptions use uuid pk
                'subscribable_id'   => null,
                'discount_applied'  => mt_rand(10, 60),
                'redeemed_at'       => $this->ago(mt_rand(10, 100)),
                'created_at'        => $this->ago(mt_rand(10, 100)),
                'updated_at'        => $this->now(),
            ]);
        }

        // Dunning rules (natural key = name)
        $dunning = [
            ['First reminder', 1, 'email', 1],
            ['Second reminder', 7, 'email', 1],
            ['Final notice', 14, 'email', 1],
            ['Suspend account', 21, 'suspend', 1],
        ];
        foreach ($dunning as $d) {
            DB::table('dunning_rules')->updateOrInsert(['name' => $d[0]], [
                'day_offset'  => $d[1],
                'action'      => $d[2],
                'is_active'   => $d[3],
                'order_index' => $d[1],
                'created_at'  => $this->ago(200),
                'updated_at'  => $this->now(),
            ]);
        }

        // Invoice template + settings (singletons; natural key = name)
        DB::table('invoice_templates')->updateOrInsert(['name' => 'Default Template'], [
            'html_body'  => '<h1>Invoice {{invoice_number}}</h1><p>{{company_name}}</p><table>{{line_items}}</table><p>Total: {{total}}</p>',
            'is_default' => 1,
            'created_at' => $this->ago(200),
            'updated_at' => $this->now(),
        ]);
        $tplId = DB::table('invoice_templates')->where('name', 'Default Template')->value('id');
        DB::table('invoice_settings')->updateOrInsert(['id' => 1], [
            'prefix'             => 'INV',
            'next_number'        => 1100,
            'digit_padding'      => 5,
            'active_template_id' => $tplId,
            'company_name'       => 'AEOS365 Inc.',
            'footer_text'        => 'Thank you for your business. Questions? billing@aeos365.com',
            'created_at'         => $this->ago(200),
            'updated_at'         => $this->now(),
        ]);

        $this->command->info('  ✓ billing extras seeded (refunds, credit notes, coupons, dunning, invoice cfg)');
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 5 — Money                                                  */
    /* ------------------------------------------------------------------ */

    private function cluster5_money(): void
    {
        $currencies = [
            ['USD', 'US Dollar', '$', 1.0],
            ['EUR', 'Euro', '€', 0.92],
            ['GBP', 'British Pound', '£', 0.79],
            ['BDT', 'Bangladeshi Taka', '৳', 109.5],
            ['AUD', 'Australian Dollar', 'A$', 1.52],
            ['CAD', 'Canadian Dollar', 'C$', 1.36],
            ['INR', 'Indian Rupee', '₹', 83.2],
        ];
        foreach ($currencies as $c) {
            DB::table('platform_currencies')->updateOrInsert(['code' => $c[0]], [
                'name'                 => $c[1],
                'symbol'               => $c[2],
                'exchange_rate_to_usd' => $c[3],
                'is_active'            => 1,
                'rate_updated_at'      => $this->now(),
                'created_at'           => $this->ago(300),
                'updated_at'           => $this->now(),
            ]);
        }

        // Exchange rates (upsert by from/to/effective_date) — recent snapshot
        foreach ($currencies as $c) {
            if ($c[0] === 'USD') {
                continue;
            }
            DB::table('exchange_rates')->updateOrInsert(
                ['from_currency' => 'USD', 'to_currency' => $c[0], 'effective_date' => $this->now()->toDateString()],
                [
                    'rate'       => $c[3],
                    'source'     => 'demo',
                    'metadata'   => json_encode(['demo' => true]),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]
            );
        }

        // NOTE: regional_prices.priceable_id is bigint but Plan/Product use uuid PKs —
        // the polymorphic table structurally cannot reference them, so it is skipped.

        $this->command->info('  ✓ money seeded ('.count($currencies).' currencies, exchange rates)');
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 6 — Analytics (180 days)                                   */
    /* ------------------------------------------------------------------ */

    private function cluster6_analytics(): void
    {
        // Per-tenant revenue contribution + signup date (demo + established real ones)
        $contrib = []; // [ ['date'=>Carbon, 'mrr'=>float, 'productMrr'=>float, 'active'=>bool, 'trial'=>bool] ]
        foreach ($this->tenantSpecs() as $tid => $s) {
            $sub = DB::table('subscriptions')->where('tenant_id', $tid)->first();
            if (! $sub) {
                continue;
            }
            $plan = $this->plans[$sub->plan_id] ?? null;
            $planMrr = $sub->billing_cycle === 'yearly' ? ((float) $sub->amount / 12) : (float) $sub->amount;
            $ps = DB::table('product_subscriptions')->where('tenant_id', $tid)->first();
            $prodMrr = $ps ? ($ps->billing_cycle === 'yearly' ? ((float) $ps->amount / 12) : (float) $ps->amount) : 0;
            $contrib[] = [
                'date'     => Carbon::parse($sub->created_at, $this->tz),
                'planMrr'  => $planMrr,
                'prodMrr'  => $prodMrr,
                'active'   => in_array($s['status'], ['active'], true),
                'trial'    => $s['status'] === 'trial',
                'churned'  => in_array($s['status'], ['archived'], true),
            ];
        }

        $start = $this->now()->copy()->subDays(180)->startOfDay();
        $today = $this->now()->copy()->startOfDay();
        $daysSeeded = 0;

        for ($d = $start->copy(); $d->lte($today); $d->addDay()) {
            $dateStr = $d->toDateString();
            $planMrr = 0.0;
            $prodMrr = 0.0;
            $activeTenants = 0;
            $trialTenants = 0;
            $totalTenants = 0;
            $newSignups = 0;
            $churned = 0;
            $trialsStarted = 0;

            foreach ($contrib as $c) {
                if ($c['date']->copy()->startOfDay()->lte($d)) {
                    $totalTenants++;
                    if ($c['active']) {
                        $planMrr += $c['planMrr'];
                        $prodMrr += $c['prodMrr'];
                        $activeTenants++;
                    }
                    if ($c['trial']) {
                        $trialTenants++;
                    }
                }
                if ($c['date']->copy()->startOfDay()->eq($d)) {
                    $newSignups++;
                    if ($c['trial']) {
                        $trialsStarted++;
                    }
                }
            }

            $mrr = round($planMrr + $prodMrr, 2);
            $arr = round($mrr * 12, 2);
            // daily revenue = invoices marked paid that day
            $dailyRevenue = (float) DB::table('invoices')
                ->where('id', 'like', 'demo-inv-%')
                ->whereDate('paid_at', $dateStr)
                ->sum('amount_paid');

            DB::table('platform_stats_daily')->updateOrInsert(['date' => $dateStr], [
                'total_tenants'      => $totalTenants,
                'active_tenants'     => $activeTenants,
                'total_users'        => $activeTenants * mt_rand(4, 22),
                'active_users'       => $activeTenants * mt_rand(2, 12),
                'total_revenue'      => $dailyRevenue,
                'total_mrr'          => $mrr,
                'total_storage_mb'   => $totalTenants * mt_rand(200, 2200),
                'total_api_requests' => $activeTenants * mt_rand(400, 3200),
                'new_signups'        => $newSignups,
                'churned_tenants'    => 0,
                'trials_started'     => $trialsStarted,
                'trials_converted'   => mt_rand(0, 100) < 8 ? 1 : 0,
                'created_at'         => $d,
                'updated_at'         => $d,
            ]);

            DB::table('platform_metrics_daily')->updateOrInsert(['date' => $dateStr], [
                'mrr'            => $mrr,
                'arr'            => $arr,
                'plan_mrr'       => round($planMrr, 2),
                'product_mrr'    => round($prodMrr, 2),
                'plan_arr'       => round($planMrr * 12, 2),
                'product_arr'    => round($prodMrr * 12, 2),
                'new_tenants'    => $newSignups,
                'churned_tenants'=> 0,
                'active_tenants' => $activeTenants,
                'trial_tenants'  => $trialTenants,
                'total_revenue'  => $dailyRevenue,
                'created_at'     => $d,
                'updated_at'     => $d,
            ]);
            $daysSeeded++;
        }

        // Feature usage events
        DB::table('feature_usage_events')->whereIn('tenant_id', $this->demoTenantIds())->delete();
        $features = ['hrm.employees.view', 'hrm.attendance.clock', 'hrm.leave.request', 'hrm.payroll.run', 'reports.export', 'settings.update', 'dashboard.view', 'crm.deals.create'];
        $activeTids = [];
        foreach ($this->tenantSpecs() as $tid => $s) {
            if ($s['status'] === 'active') {
                $activeTids[] = $tid;
            }
        }
        $fuRows = [];
        foreach ($activeTids as $tid) {
            $n = mt_rand(15, 45);
            for ($i = 0; $i < $n; $i++) {
                $when = $this->ago(mt_rand(0, 180), mt_rand(6, 22));
                $fuRows[] = [
                    'tenant_id'    => $tid,
                    'feature_code' => $this->pick($features),
                    'user_id'      => mt_rand(1, 40),
                    'occurred_at'  => $when,
                    'created_at'   => $when,
                    'updated_at'   => $when,
                ];
            }
        }
        foreach (array_chunk($fuRows, 500) as $chunk) {
            DB::table('feature_usage_events')->insert($chunk);
        }

        // Funnel definitions
        $funnels = [
            ['Signup Conversion', [['key' => 'visit', 'label' => 'Landing visit'], ['key' => 'register', 'label' => 'Started signup'], ['key' => 'verify', 'label' => 'Verified email'], ['key' => 'provision', 'label' => 'Provisioned'], ['key' => 'active', 'label' => 'First login']]],
            ['Trial to Paid', [['key' => 'trial', 'label' => 'Trial started'], ['key' => 'engaged', 'label' => 'Used a feature'], ['key' => 'payment', 'label' => 'Added payment'], ['key' => 'paid', 'label' => 'Converted']]],
        ];
        foreach ($funnels as $f) {
            DB::table('funnel_definitions')->updateOrInsert(['name' => $f[0]], [
                'steps'      => json_encode($f[1]),
                'created_by' => 1,
                'created_at' => $this->ago(120),
                'updated_at' => $this->now(),
            ]);
        }

        $this->command->info("  ✓ analytics seeded ($daysSeeded days, ".count($fuRows).' usage events, funnels)');
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 7 — Growth                                                 */
    /* ------------------------------------------------------------------ */

    private function cluster7_growth(): void
    {
        // Prospect leads
        DB::table('prospect_leads')->where('email', 'like', '%@demo-lead.%')->delete();
        $leadNames = ['Olivia Bennett', 'Marcus Chen', 'Priya Nair', 'Diego Ramirez', 'Sofia Rossi', 'James O\'Brien', 'Aiko Tanaka', 'Liam Walsh', 'Fatima Al-Sayed', 'Noah Schmidt', 'Emma Laurent', 'Kwame Mensah', 'Isabella Costa', 'Yuki Sato', 'Hannah Berg'];
        $sources = ['organic', 'google_ads', 'referral', 'linkedin', 'webinar', 'cold_outreach'];
        $statuses = ['new', 'new', 'contacted', 'qualified', 'nurturing', 'converted', 'lost'];
        foreach ($leadNames as $i => $name) {
            $st = $this->pick($statuses);
            $created = $this->ago(mt_rand(1, 150));
            DB::table('prospect_leads')->insert([
                'email'         => strtolower(str_replace([' ', '\''], ['.', ''], $name)).'@demo-lead.com',
                'name'          => $name,
                'company_name'  => $this->pick(['Bright', 'Summit', 'Nova', 'Peak', 'Union', 'Vantage']).' '.$this->pick(['Labs', 'Group', 'Partners', 'Industries', 'Co']),
                'phone'         => '+1'.mt_rand(200, 989).mt_rand(2000000, 9999999),
                'country'       => $this->pick(['US', 'GB', 'DE', 'BD', 'CA', 'AU', 'IN']),
                'source'        => $this->pick($sources),
                'status'        => $st,
                'score'         => mt_rand(10, 98),
                'interest_level'=> $this->pick(['low', 'medium', 'high']),
                'interests'     => json_encode([$this->pick(['hrm', 'crm', 'finance', 'pos'])]),
                'contacted_at'  => in_array($st, ['contacted', 'qualified', 'nurturing', 'converted'], true) ? $created->copy()->addDays(1) : null,
                'converted_at'  => $st === 'converted' ? $created->copy()->addDays(mt_rand(5, 30)) : null,
                'last_activity_at' => $created->copy()->addDays(mt_rand(0, 10)),
                'created_at'    => $created,
                'updated_at'    => $this->now(),
            ]);
        }

        // Partial registrations (abandoned signups)
        DB::table('partial_registrations')->where('email', 'like', '%@demo-partial.%')->delete();
        for ($i = 1; $i <= 6; $i++) {
            $created = $this->ago(mt_rand(0, 20));
            DB::table('partial_registrations')->insert([
                'email'      => 'abandoned'.$i.'@demo-partial.com',
                'token'      => Str::random(48),
                'step'       => $this->pick(['company_info', 'verify_email', 'choose_plan', 'payment']),
                'data'       => json_encode(['company_name' => 'Prospect '.$i, 'plan' => 'starter']),
                'expires_at' => $created->copy()->addDays(7),
                'ip_address' => '198.51.100.'.mt_rand(2, 250),
                'created_at' => $created,
                'updated_at' => $created,
            ]);
        }

        // Newsletter subscribers
        DB::table('newsletter_subscribers')->where('email', 'like', '%@demo-news.%')->delete();
        for ($i = 1; $i <= 40; $i++) {
            $st = $this->pick(['subscribed', 'subscribed', 'subscribed', 'pending', 'unsubscribed']);
            $created = $this->ago(mt_rand(0, 180));
            DB::table('newsletter_subscribers')->insert([
                'email'         => 'subscriber'.$i.'@demo-news.com',
                'name'          => 'Subscriber '.$i,
                'status'        => $st,
                'confirmed_at'  => $st === 'subscribed' ? $created->copy()->addHours(2) : null,
                'unsubscribed_at' => $st === 'unsubscribed' ? $created->copy()->addDays(mt_rand(10, 60)) : null,
                'source'        => $this->pick(['footer', 'blog', 'popup', 'checkout']),
                'created_at'    => $created,
                'updated_at'    => $this->now(),
            ]);
        }

        // Affiliates (natural key = email)
        $affiliates = [
            ['Growth Collective', 'partners@growthco.demo', 'GROWTH10', 'active', 20, 4200.50, 890.00, 3310.50, 48, 31],
            ['SaaS Reviews Hub', 'aff@saasreviews.demo', 'SAASHUB', 'active', 15, 1875.00, 375.00, 1500.00, 22, 15],
            ['Tech Influencer Media', 'deals@techinflu.demo', 'TECHINF', 'pending', 25, 0, 0, 0, 3, 0],
        ];
        $affIds = [];
        foreach ($affiliates as $a) {
            DB::table('affiliates')->updateOrInsert(['email' => $a[1]], [
                'name'                => $a[0],
                'referral_code'       => $a[2],
                'status'              => $a[3],
                'commission_rate'     => $a[4],
                'commission_type'     => 'percentage',
                'cookie_days'         => 30,
                'payout_method'       => 'paypal',
                'minimum_payout'      => 100,
                'total_earnings'      => $a[5],
                'pending_earnings'    => $a[6],
                'paid_earnings'       => $a[7],
                'total_referrals'     => $a[8],
                'successful_referrals'=> $a[9],
                'approved_at'         => $a[3] === 'active' ? $this->ago(150) : null,
                'last_referral_at'    => $a[8] > 0 ? $this->ago(mt_rand(1, 30)) : null,
                'created_at'          => $this->ago(180),
                'updated_at'          => $this->now(),
            ]);
            $affIds[$a[1]] = DB::table('affiliates')->where('email', $a[1])->value('id');
        }
        // Affiliate referrals + payouts
        DB::table('affiliate_referrals')->whereIn('affiliate_id', array_values($affIds))->delete();
        DB::table('affiliate_payouts')->whereIn('affiliate_id', array_values($affIds))->delete();
        foreach ($affIds as $email => $aid) {
            $n = mt_rand(3, 10);
            for ($i = 0; $i < $n; $i++) {
                $reg = $this->ago(mt_rand(1, 170));
                $converted = mt_rand(0, 100) < 65;
                DB::table('affiliate_referrals')->insert([
                    'affiliate_id'      => $aid,
                    'visitor_id'        => Str::uuid(),
                    'ip_address'        => '203.0.113.'.mt_rand(2, 250),
                    'status'            => $converted ? 'converted' : 'pending',
                    'tenant_email'      => 'ref'.$i.'@'.substr($email, strpos($email, '@') + 1),
                    'transaction_amount'=> $converted ? mt_rand(29, 399) : null,
                    'commission_amount' => $converted ? mt_rand(5, 80) : null,
                    'commission_status' => $converted ? $this->pick(['pending', 'approved', 'paid']) : 'pending',
                    'registered_at'     => $reg,
                    'converted_at'      => $converted ? $reg->copy()->addDays(mt_rand(1, 14)) : null,
                    'created_at'        => $reg,
                    'updated_at'        => $this->now(),
                ]);
            }
            if (! str_contains($email, 'techinflu')) {
                DB::table('affiliate_payouts')->insert([
                    'affiliate_id'         => $aid,
                    'amount'               => mt_rand(200, 900),
                    'currency'             => 'USD',
                    'status'               => $this->pick(['completed', 'completed', 'processing']),
                    'payout_method'        => 'paypal',
                    'transaction_reference'=> 'PP-'.strtoupper(Str::random(10)),
                    'processed_at'         => $this->ago(mt_rand(5, 40)),
                    'completed_at'         => $this->ago(mt_rand(1, 5)),
                    'created_at'           => $this->ago(mt_rand(5, 40)),
                    'updated_at'           => $this->now(),
                ]);
            }
        }

        // Reseller partners + commissions (natural key = email; capture bigint ids)
        // commission_rate is decimal(5,4) — stored as a fraction (0.25 == 25%)
        $partners = [
            ['Nordic Cloud Solutions', 'sales@nordiccloud.demo', 0.25, 'active', 'nordic-cloud'],
            ['APAC Business Systems', 'partner@apacbiz.demo', 0.20, 'active', 'apac-biz'],
            ['MidWest IT Group', 'hello@midwestit.demo', 0.18, 'pending', 'midwest-it'],
        ];
        $partnerIds = [];
        foreach ($partners as $p) {
            DB::table('reseller_partners')->updateOrInsert(['email' => $p[1]], [
                'name'            => $p[0],
                'commission_rate' => $p[2],
                'status'          => $p[3],
                'portal_slug'     => $p[4],
                'portal_config'   => json_encode(['brand_color' => '#4f46e5']),
                'approved_by'     => $p[3] === 'active' ? 1 : null,
                'approved_at'     => $p[3] === 'active' ? $this->ago(120) : null,
                'created_at'      => $this->ago(150),
                'updated_at'      => $this->now(),
            ]);
            $partnerIds[] = DB::table('reseller_partners')->where('email', $p[1])->value('id');
        }
        DB::table('partner_commissions')->whereIn('partner_id', $partnerIds)->delete();
        $activeTids = array_values(array_filter($this->demoTenantIds(), fn ($tid) => $this->tenantSpecs()[$tid]['status'] === 'active'));
        foreach (array_slice($activeTids, 0, 8) as $i => $tid) {
            DB::table('partner_commissions')->insert([
                'partner_id' => $partnerIds[$i % 2],
                'tenant_id'  => $tid,
                'amount'     => mt_rand(20, 120),
                'status'     => $this->pick(['pending', 'paid', 'approved']),
                'paid_at'    => mt_rand(0, 1) ? $this->ago(mt_rand(1, 30)) : null,
                'created_at' => $this->ago(mt_rand(10, 120)),
                'updated_at' => $this->now(),
            ]);
        }

        $this->command->info('  ✓ growth seeded (leads, partials, newsletter, affiliates, partners)');
    }

    /* ------------------------------------------------------------------ */
    /* Cluster 8 — Ops / Security                                         */
    /* ------------------------------------------------------------------ */

    private function cluster8_ops(): void
    {
        $demoIds = $this->demoTenantIds();

        // Error logs
        DB::table('error_logs')->where('trace_id', 'like', 'demo-%')->delete();
        $errTypes = ['ValidationException', 'QueryException', 'TokenMismatchException', 'HttpNotFoundException', 'RuntimeException', 'ModelNotFoundException'];
        $modules = ['hrm', 'billing', 'auth', 'core', 'crm'];
        for ($i = 1; $i <= 40; $i++) {
            $when = $this->ago(mt_rand(0, 60), mt_rand(0, 23));
            $resolved = mt_rand(0, 100) < 60;
            DB::table('error_logs')->insert([
                'trace_id'       => substr('demo-'.str_replace('-', '', (string) Str::uuid()), 0, 36),
                'source_domain'  => $this->pick($demoIds ? array_map(fn ($t) => $this->tenantSpecs()[$t]['sub'].'.aeos365.com', array_slice($demoIds, 0, 6)) : ['app.aeos365.com']),
                'tenant_id'      => $this->pick($demoIds),
                'error_type'     => $this->pick($errTypes),
                'http_code'      => $this->pick([500, 500, 422, 404, 403, 419]),
                'request_method' => $this->pick(['GET', 'POST', 'PUT', 'DELETE']),
                'request_url'    => '/'.$this->pick(['dashboard', 'employees', 'billing/invoices', 'settings', 'api/v1/sync']),
                'error_message'  => $this->pick(['Undefined array key "id"', 'SQLSTATE[23000]: Integrity constraint violation', 'CSRF token mismatch', 'Route not found', 'Call to a member function on null']),
                'module'         => $this->pick($modules),
                'is_resolved'    => $resolved ? 1 : 0,
                'resolved_by'    => $resolved ? 1 : null,
                'resolved_at'    => $resolved ? $when->copy()->addHours(mt_rand(1, 48)) : null,
                'created_at'     => $when,
                'updated_at'     => $when,
            ]);
        }

        // Platform audit logs (append-only/immutable — insert once, never delete)
        $auditActions = [
            ['tenant.suspended', 'suspend', 'Tenant suspended for non-payment'],
            ['tenant.created', 'create', 'New tenant provisioned'],
            ['plan.updated', 'update', 'Plan pricing updated'],
            ['coupon.created', 'create', 'Coupon created'],
            ['refund.approved', 'approve', 'Refund approved'],
            ['feature_flag.toggled', 'update', 'Feature flag toggled'],
            ['impersonation.started', 'access', 'Admin started tenant impersonation'],
            ['settings.updated', 'update', 'Platform settings updated'],
        ];
        $auditSeeded = DB::table('platform_audit_logs')->where('request_id', 'demo-seed')->count();
        for ($i = $auditSeeded; $i < 30; $i++) {
            $a = $this->pick($auditActions);
            $when = $this->ago(mt_rand(0, 90), mt_rand(0, 23));
            DB::table('platform_audit_logs')->insert([
                'actor_id'      => 1,
                'actor_name'    => $this->pick(['Platform Admin', 'Emam Hosen', 'Support Agent']),
                'actor_ip'      => '203.0.113.'.mt_rand(2, 250),
                'event_type'    => $a[0],
                'action'        => $a[1],
                'description'   => $a[2],
                'subject_type'  => 'Aero\\Platform\\Models\\Tenant',
                'subject_id'    => $this->pick($demoIds),
                'subject_label' => $this->tenantSpecs()[$this->pick($demoIds)]['name'],
                'request_id'    => 'demo-seed',
                'http_method'   => $this->pick(['POST', 'PUT', 'PATCH']),
                'created_at'    => $when,
            ]);
        }

        // Platform access logs (PII access — append-only; insert once)
        $accessSeeded = DB::table('platform_access_logs')->where('resource_id', 'like', 'demo-tnnt-%')->count();
        for ($i = $accessSeeded; $i < 20; $i++) {
            $when = $this->ago(mt_rand(0, 60), mt_rand(0, 23));
            DB::table('platform_access_logs')->insert([
                'accessor_id'     => 1,
                'accessor_name'   => $this->pick(['Platform Admin', 'Support Agent']),
                'accessor_ip'     => '203.0.113.'.mt_rand(2, 250),
                'resource_type'   => 'tenant.billing_profile',
                'resource_id'     => $this->pick($demoIds),
                'subject_label'   => 'Billing contact PII',
                'fields_accessed' => json_encode(['tax_id', 'account_number']),
                'created_at'      => $when,
            ]);
        }

        // Feature flags (natural key = code)
        $flags = [
            ['new_billing_ui', 'New Billing UI', 100, 1],
            ['ai_assistant', 'AI Assistant Beta', 35, 1],
            ['advanced_analytics', 'Advanced Analytics', 60, 1],
            ['self_serve_downgrade', 'Self-serve Downgrade', 0, 0],
            ['multi_currency_checkout', 'Multi-currency Checkout', 80, 1],
        ];
        $flagIds = [];
        foreach ($flags as $f) {
            DB::table('feature_flags')->updateOrInsert(['code' => $f[0]], [
                'name'        => $f[1],
                'description' => $f[1].' rollout',
                'rollout_pct' => $f[2],
                'is_active'   => $f[3],
                'is_archived' => 0,
                'created_by'  => 1,
                'created_at'  => $this->ago(120),
                'updated_at'  => $this->now(),
            ]);
            $flagIds[$f[0]] = DB::table('feature_flags')->where('code', $f[0])->value('id');
        }
        // Tenant overrides
        DB::table('feature_flag_tenant_overrides')->whereIn('tenant_id', $demoIds)->delete();
        foreach (array_slice($demoIds, 0, 6) as $i => $tid) {
            DB::table('feature_flag_tenant_overrides')->insert([
                'flag_id'    => $flagIds['ai_assistant'],
                'tenant_id'  => $tid,
                'is_active'  => $i % 2,
                'set_by'     => 1,
                'created_at' => $this->ago(mt_rand(5, 60)),
                'updated_at' => $this->now(),
            ]);
        }
        // Experiments
        DB::table('feature_flag_experiments')->updateOrInsert(['name' => 'AI Assistant CTA test'], [
            'flag_id'     => $flagIds['ai_assistant'],
            'control_pct' => 50,
            'variant_pct' => 50,
            'started_at'  => $this->ago(45),
            'created_at'  => $this->ago(45),
            'updated_at'  => $this->now(),
        ]);

        // Quota warnings
        DB::table('quota_warnings')->whereIn('tenant_id', $demoIds)->delete();
        $activeTids = array_values(array_filter($demoIds, fn ($tid) => in_array($this->tenantSpecs()[$tid]['status'], ['active', 'trial'], true)));
        foreach (array_slice($activeTids, 0, 7) as $i => $tid) {
            $pct = mt_rand(75, 99);
            DB::table('quota_warnings')->insert([
                'tenant_id'      => $tid,
                'quota_type'     => $this->pick(['storage', 'users', 'api_requests']),
                'percentage'     => $pct,
                'threshold_type' => $pct >= 90 ? 'critical' : 'warning',
                'first_warned_at'=> $this->ago(mt_rand(3, 20)),
                'last_warned_at' => $this->ago(mt_rand(0, 2)),
                'warning_count'  => mt_rand(1, 5),
                'is_dismissed'   => 0,
                'created_at'     => $this->ago(mt_rand(3, 20)),
                'updated_at'     => $this->now(),
            ]);
        }

        // Rate limit configs (uuid pk)
        DB::table('rate_limit_configs')->updateOrInsert(['id' => 'demo-rl-global'], [
            'tenant_id'             => null,
            'limit_type'            => 'global',
            'max_requests'          => 1000,
            'time_window_seconds'   => 60,
            'burst_limit'           => 1200,
            'throttle_percentage'   => 90,
            'block_duration_seconds'=> 300,
            'is_active'             => 1,
            'created_at'            => $this->ago(200),
            'updated_at'            => $this->now(),
        ]);
        foreach (array_slice($activeTids, 0, 2) as $i => $tid) {
            DB::table('rate_limit_configs')->updateOrInsert(['id' => 'demo-rl-'.substr($tid, 10)], [
                'tenant_id'           => $tid,
                'limit_type'          => 'tenant',
                'max_requests'        => 5000,
                'time_window_seconds' => 60,
                'is_active'           => 1,
                'created_at'          => $this->ago(60),
                'updated_at'          => $this->now(),
            ]);
        }

        // Maintenance windows (natural key = title)
        $windows = [
            ['Database maintenance', 'completed', -20, -20],
            ['Q3 infrastructure upgrade', 'scheduled', 7, 7],
            ['SSL certificate rotation', 'scheduled', 14, 14],
        ];
        foreach ($windows as $w) {
            $start = $this->now()->copy()->addDays($w[2])->setTime(2, 0);
            DB::table('maintenance_windows')->updateOrInsert(['title' => $w[0]], [
                'message'    => $w[0].' — brief service interruption expected.',
                'status'     => $w[1],
                'starts_at'  => $start,
                'ends_at'    => $start->copy()->addHours(2),
                'created_at' => $this->ago(30),
                'updated_at' => $this->now(),
            ]);
        }

        // API keys (natural key = key)
        $keys = [
            ['Production Integration', 'ak_live_'.Str::random(24), ['read', 'write'], 1],
            ['Analytics Read-only', 'ak_live_'.Str::random(24), ['read'], 1],
            ['Legacy Webhook (revoked)', 'ak_live_'.Str::random(24), ['read', 'write'], 0],
        ];
        foreach ($keys as $k) {
            DB::table('integrations_api_keys')->updateOrInsert(['name' => $k[0]], [
                'user_id'     => 1,
                'key_prefix'  => substr($k[1], 0, 8),
                'key_hash'    => hash('sha256', $k[1]),
                'key'         => $k[1],
                'scopes'      => json_encode($k[2]),
                'is_active'   => $k[3],
                'last_used_at'=> $k[3] ? $this->ago(mt_rand(0, 10)) : null,
                'revoked_at'  => $k[3] ? null : $this->ago(30),
                'created_at'  => $this->ago(150),
                'updated_at'  => $this->now(),
            ]);
        }

        // Connectors (natural key = name)
        $connectors = [
            ['Slack Notifications', 'slack', 'active'],
            ['QuickBooks Sync', 'accounting', 'active'],
            ['Salesforce CRM', 'crm', 'error'],
            ['AWS S3 Backup', 'storage', 'active'],
        ];
        foreach ($connectors as $c) {
            DB::table('integrations_connectors')->updateOrInsert(['name' => $c[0]], [
                'type'        => $c[1],
                'description' => $c[0].' integration',
                'config'      => json_encode(['endpoint' => 'https://api.example.com', 'demo' => true]),
                'is_active'   => $c[2] === 'active' ? 1 : 0,
                'status'      => $c[2],
                'last_sync_at'=> $c[2] === 'active' ? $this->ago(0, mt_rand(0, 12)) : $this->ago(3),
                'created_at'  => $this->ago(120),
                'updated_at'  => $this->now(),
            ]);
        }

        // Standalone licenses (uuid pk)
        $prod = array_values($this->products)[0] ?? null;
        if ($prod) {
            for ($i = 1; $i <= 8; $i++) {
                $st = $this->pick(['active', 'active', 'active', 'expired', 'suspended']);
                DB::table('standalone_licenses')->updateOrInsert(['id' => 'demo-lic-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT)], [
                    'product_id'      => $prod->id,
                    'license_key'     => 'AEOS-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)),
                    'customer_email'  => 'license'.$i.'@standalone-demo.com',
                    'customer_name'   => $this->pick(['Redwood LLC', 'Delta Systems', 'Crestview Ltd', 'Blue Harbor', 'Ironclad Inc']),
                    'status'          => $st,
                    'activation_count'=> mt_rand(1, 3),
                    'max_activations' => 3,
                    'purchase_source' => $this->pick(['direct', 'reseller', 'marketplace']),
                    'billing_type'    => $this->pick(['perpetual', 'annual']),
                    'expires_at'      => $st === 'expired' ? $this->ago(20) : $this->now()->addYear(),
                    'last_validated_at'=> $this->ago(mt_rand(0, 15)),
                    'current_version' => '1.'.mt_rand(0, 4).'.'.mt_rand(0, 9),
                    'created_at'      => $this->ago(mt_rand(30, 300)),
                    'updated_at'      => $this->now(),
                ]);
            }
        }

        // Enterprise plan requests (uuid pk)
        $entTenants = array_slice($activeTids, 0, 3);
        foreach ($entTenants as $i => $tid) {
            $st = $this->pick(['pending', 'under_review', 'approved']);
            DB::table('enterprise_plan_requests')->updateOrInsert(['id' => 'demo-epr-'.substr($tid, 10)], [
                'tenant_id'             => $tid,
                'requested_by_user_id'  => 1,
                'status'                => $st,
                'plan_details'          => json_encode(['seats' => mt_rand(100, 800), 'modules' => ['hrm', 'crm', 'finance'], 'sla' => '99.9%']),
                'business_justification'=> 'Scaling to '.mt_rand(100, 800).' employees; need dedicated infra + SSO.',
                'contract_length'       => $this->pick([12, 24, 36]),
                'proposed_monthly_price'=> mt_rand(1500, 6000),
                'proposed_yearly_price' => mt_rand(15000, 60000),
                'currency'              => 'USD',
                'reviewed_by_admin_id'  => $st !== 'pending' ? 1 : null,
                'reviewed_at'           => $st !== 'pending' ? $this->ago(mt_rand(1, 10)) : null,
                'created_at'            => $this->ago(mt_rand(5, 40)),
                'updated_at'            => $this->now(),
            ]);
        }

        // Quota enforcement settings — enrich existing 5 rows if defaults empty
        foreach (DB::table('quota_enforcement_settings')->get() as $q) {
            DB::table('quota_enforcement_settings')->where('id', $q->id)->update([
                'warning_threshold_percentage'  => $q->warning_threshold_percentage ?? 75,
                'critical_threshold_percentage' => $q->critical_threshold_percentage ?? 90,
                'block_threshold_percentage'    => $q->block_threshold_percentage ?? 100,
                'send_email'                    => 1,
                'updated_at'                    => $this->now(),
            ]);
        }

        $this->command->info('  ✓ ops/security seeded (errors, audit, access, flags, quotas, rate-limits, maintenance, api keys, connectors, licenses, enterprise requests)');
        $this->command->warn('  ⚠ SecurityEvent skipped — model exists but security_events table has no migration.');
    }
}
