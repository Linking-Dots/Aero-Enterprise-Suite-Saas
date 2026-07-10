<?php

namespace Aero\Platform\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PartnerDemoSeeder
 *
 * Seeds a realistic reseller / channel-partner program: 9 partners across
 * statuses (active / pending / suspended), ~38 commission entries spread over
 * the last 6 months (pending / approved / paid with paid_at), and assigns
 * demo tenants to partners via tenants.reseller_partner_id so managed-tenant
 * books, channel MRR and the leaderboard are all populated for the FYP demo.
 * Idempotent: partners keyed by email; commissions and tenant assignments
 * for these partners are rebuilt on each run.
 *
 * Run: php artisan db:seed --class="Aero\Platform\Database\Seeders\PartnerDemoSeeder"
 */
class PartnerDemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260711);
        $conn = DB::connection('central');

        // [name, email, rate (fraction), status, portal_slug, brand]
        $partners = [
            ['Nordic Cloud Solutions', 'sales@nordiccloud.demo', 0.25, 'active', 'nordic-cloud', '#4f46e5'],
            ['APAC Business Systems', 'partner@apacbiz.demo', 0.20, 'active', 'apac-biz', '#0891b2'],
            ['Iberia SaaS Partners', 'hola@iberiasaas.demo', 0.15, 'active', 'iberia', '#16a34a'],
            ['Gulf Tech Alliance', 'partners@gulftech.demo', 0.15, 'active', 'gulf-tech', '#d97706'],
            ['Alpine Digital GmbH', 'kontakt@alpinedigital.demo', 0.18, 'active', 'alpine', '#7c3aed'],
            ['Andes Software Group', 'ventas@andessoft.demo', 0.12, 'active', 'andes', '#0f766e'],
            ['MidWest IT Group', 'hello@midwestit.demo', 0.18, 'pending', 'midwest-it', '#4f46e5'],
            ['Bengal Cloud Services', 'info@bengalcloud.demo', 0.20, 'pending', 'bengal-cloud', '#be123c'],
            ['LatAm Cloud Bridge', 'contato@lacbridge.demo', 0.12, 'suspended', 'lac-bridge', '#64748b'],
        ];

        $ids = [];
        foreach ($partners as $i => $p) {
            $createdDays = 150 - $i * 12;
            $conn->table('reseller_partners')->updateOrInsert(['email' => $p[1]], [
                'name' => $p[0],
                'commission_rate' => $p[2],
                'status' => $p[3],
                'portal_slug' => $p[4],
                'portal_config' => json_encode([
                    'brand_color' => $p[5],
                    'welcome' => 'Welcome to the '.$p[0].' client portal.',
                    'support_email' => $p[1],
                ]),
                'approved_by' => $p[3] === 'active' ? 1 : null,
                'approved_at' => $p[3] === 'active' ? now()->subDays($createdDays - mt_rand(2, 9)) : null,
                'created_at' => now()->subDays($createdDays),
                'updated_at' => now(),
            ]);
            $ids[$p[1]] = (int) $conn->table('reseller_partners')->where('email', $p[1])->value('id');
        }

        $active = [
            $ids['sales@nordiccloud.demo'],
            $ids['partner@apacbiz.demo'],
            $ids['hola@iberiasaas.demo'],
            $ids['partners@gulftech.demo'],
            $ids['kontakt@alpinedigital.demo'],
            $ids['ventas@andessoft.demo'],
        ];
        $suspended = $ids['contato@lacbridge.demo'];

        // ---- tenant books: spread demo tenants across partners ----
        $tenantIds = $conn->table('tenants')
            ->where('id', 'like', 'demo-%')->whereNull('deleted_at')
            ->orderBy('created_at')->pluck('id')->all();

        $conn->table('tenants')->whereIn('reseller_partner_id', array_values($ids))
            ->update(['reseller_partner_id' => null]);

        // Weighted books: leaders manage more tenants; suspended keeps a small book.
        $bookPlan = [];
        foreach ($tenantIds as $n => $tid) {
            $bookPlan[$tid] = match (true) {
                $n % 4 === 0 => $active[0],                       // Nordic — biggest book
                $n % 4 === 1 => $active[$n % 2 === 1 ? 1 : 2],    // APAC / Iberia
                $n % 8 === 2 => $active[3],                       // Gulf
                $n % 8 === 6 => $active[4],                       // Alpine
                $n % 16 === 3 => $suspended,                      // LatAm history
                default => null,                                  // direct-managed
            };
        }
        foreach ($bookPlan as $tid => $pid) {
            if ($pid !== null) {
                $conn->table('tenants')->where('id', $tid)->update(['reseller_partner_id' => $pid]);
            }
        }

        // ---- commission ledger: ~38 entries over 6 months ----
        $conn->table('partner_commissions')->whereIn('partner_id', array_values($ids))->delete();

        $managed = $conn->table('tenants')->whereNotNull('reseller_partner_id')
            ->whereIn('reseller_partner_id', array_values($ids))
            ->get(['id', 'reseller_partner_id']);

        $rateById = [];
        foreach ($partners as $p) {
            $rateById[$ids[$p[1]]] = $p[2];
        }

        $rows = [];
        $invoice = 2001;
        foreach ($managed as $t) {
            // one commission per tenant per ~monthly invoice, 3–6 months of history
            $months = mt_rand(3, 6);
            for ($m = $months; $m >= 1; $m--) {
                $created = now()->subMonths($m)->addDays(mt_rand(0, 24))->setTime(mt_rand(8, 18), mt_rand(0, 59));
                $base = mt_rand(99, 499);
                $amount = round($base * $rateById[$t->reseller_partner_id], 2);
                // older entries mostly settled; recent ones pending/approved
                $status = $m >= 3 ? (mt_rand(0, 9) < 8 ? 'paid' : 'approved')
                    : ($m === 2 ? (mt_rand(0, 1) ? 'paid' : 'approved') : (mt_rand(0, 2) ? 'pending' : 'approved'));
                if ($t->reseller_partner_id === $suspended) {
                    $status = 'paid'; // suspended partner's book is fully settled
                }
                $rows[] = [
                    'partner_id' => $t->reseller_partner_id,
                    'tenant_id' => $t->id,
                    'invoice_id' => $invoice++,
                    'amount' => $amount,
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? (clone $created)->addDays(mt_rand(3, 21)) : null,
                    'created_at' => $created,
                    'updated_at' => now(),
                ];
            }
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            $conn->table('partner_commissions')->insert($chunk);
        }

        $this->command?->info('  ✓ partners seeded: '.count($ids).' partners, '.count($rows).' commissions, '.$managed->count().' managed tenants');
    }
}
