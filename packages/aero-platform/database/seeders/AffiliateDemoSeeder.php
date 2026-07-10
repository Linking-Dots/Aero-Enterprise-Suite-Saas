<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\Affiliate;
use Aero\Platform\Models\AffiliatePayout;
use Aero\Platform\Models\AffiliateReferral;
use Illuminate\Database\Seeder;

/**
 * AffiliateDemoSeeder
 *
 * Seeds a realistic affiliate program — ~24 affiliates across every status, with
 * referrals (clicked/registered/converted) and a payout history — so the Affiliates
 * CRM command centre is fully populated for the FYP demo. Reproducible; clears prior
 * demo rows (email @demo-aff.com) first so re-runs never duplicate.
 *
 * Run: php artisan db:seed --class="Aero\Platform\Database\Seeders\AffiliateDemoSeeder"
 */
class AffiliateDemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260710);

        // Clear prior demo rows (children first).
        $priorIds = Affiliate::where('email', 'like', '%@demo-aff.com')->pluck('id');
        if ($priorIds->isNotEmpty()) {
            AffiliateReferral::whereIn('affiliate_id', $priorIds)->delete();
            AffiliatePayout::whereIn('affiliate_id', $priorIds)->delete();
            Affiliate::whereIn('id', $priorIds)->forceDelete();
        }

        $names = [
            ['Rankmath Media', 'rankmath.io'], ['SaaSDeals', 'saasdeals.in'], ['Delta Growth Co.', 'deltagrowth.co'],
            ['Nova Review Blog', 'novareview.com'], ['Vega Media', 'vega.co'], ['Priya Nair', 'priyanair.dev'],
            ['Marco Haddad', 'marcohaddad.com'], ['BrightFunnel', 'brightfunnel.io'], ['Growthhackers Ltd', 'ghackers.io'],
            ['Toolstack Weekly', 'toolstack.week'], ['Ada Okafor', 'adaokafor.me'], ['Peak Referrals', 'peakref.com'],
            ['Sam Brooks', 'brooks.dev'], ['Zenith Partners', 'zenithp.co'], ['CloudPulse', 'cloudpulse.io'],
            ['Ingrid Larsen', 'ingridwrites.no'], ['DevTools Digest', 'devtoolsdigest.com'], ['Orbit Affiliates', 'orbitaff.com'],
            ['Kwame Mensah', 'kwame.gh'], ['StackShare Pro', 'stacksharepro.com'], ['Lena Vogel', 'lenavogel.de'],
            ['Summit Media', 'summitmedia.co'], ['Rohan Patel', 'rohanpatel.in'], ['Fjord Digital', 'fjorddigital.no'],
        ];
        $methods = [Affiliate::PAYOUT_PAYPAL, Affiliate::PAYOUT_BANK_TRANSFER, Affiliate::PAYOUT_STRIPE];

        // Status distribution: mostly approved, a few pending/suspended/rejected.
        $statusPlan = array_merge(
            array_fill(0, 17, Affiliate::STATUS_APPROVED),
            array_fill(0, 4, Affiliate::STATUS_PENDING),
            array_fill(0, 2, Affiliate::STATUS_SUSPENDED),
            array_fill(0, 1, Affiliate::STATUS_REJECTED),
        );

        foreach ($names as $i => [$name, $domain]) {
            $status = $statusPlan[$i] ?? Affiliate::STATUS_APPROVED;
            $method = $methods[array_rand($methods)];
            $rate = [15, 20, 25, 30, 20, 20][mt_rand(0, 5)];
            $createdDaysAgo = mt_rand(20, 200);

            $aff = new Affiliate;
            $aff->name = $name;
            $aff->referral_code = Affiliate::generateUniqueReferralCode();
            $aff->email = strtolower(preg_replace('/[^a-z0-9]/i', '', explode(' ', $name)[0])).($i + 1).'@demo-aff.com';
            $aff->company_name = $name;
            $aff->website = 'https://'.$domain;
            $aff->status = $status;
            $aff->commission_rate = $rate;
            $aff->commission_type = mt_rand(0, 5) === 0 ? Affiliate::COMMISSION_FIXED : Affiliate::COMMISSION_PERCENTAGE;
            $aff->fixed_commission = $aff->commission_type === Affiliate::COMMISSION_FIXED ? [30, 40, 50][mt_rand(0, 2)] : 0;
            $aff->cookie_days = [30, 45, 60, 90][mt_rand(0, 3)];
            $aff->payout_method = $method;
            $aff->payout_details = ['account' => 'billing@'.$domain];
            $aff->minimum_payout = [50, 50, 100][mt_rand(0, 2)];
            $aff->approved_at = $status === Affiliate::STATUS_APPROVED ? now()->subDays($createdDaysAgo - 3) : null;
            $aff->created_at = now()->subDays($createdDaysAgo);
            $aff->updated_at = now()->subDays(mt_rand(0, 10));
            $aff->save();

            // Referrals only for approved/suspended affiliates that have been active.
            if (in_array($status, [Affiliate::STATUS_APPROVED, Affiliate::STATUS_SUSPENDED], true)) {
                $refCount = mt_rand(6, 40);
                $converted = 0;
                $pendingEarn = 0.0;
                $paidEarn = 0.0;
                $lastRef = null;

                for ($r = 0; $r < $refCount; $r++) {
                    $refDaysAgo = mt_rand(1, min($createdDaysAgo, 120));
                    $refAt = now()->subDays($refDaysAgo)->setTime(mt_rand(8, 20), mt_rand(0, 59));
                    $lastRef = $lastRef === null || $refAt->gt($lastRef) ? $refAt : $lastRef;

                    // ~28% convert, ~20% register, rest just click.
                    $roll = mt_rand(0, 100);
                    $ref = new AffiliateReferral;
                    $ref->affiliate_id = $aff->id;
                    $ref->visitor_id = md5($aff->id.'-'.$r);
                    $ref->ip_address = mt_rand(1, 255).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(1, 255);
                    $ref->landing_page = 'https://aeos365.com/?ref='.$aff->referral_code;
                    $ref->utm_data = ['utm_source' => ['blog', 'youtube', 'twitter', 'newsletter'][mt_rand(0, 3)]];
                    $ref->created_at = $refAt;
                    $ref->updated_at = $refAt;

                    if ($roll < 28) {
                        $amount = [29, 49, 99, 149, 299][mt_rand(0, 4)];
                        $commission = $aff->commission_type === Affiliate::COMMISSION_FIXED
                            ? (float) $aff->fixed_commission
                            : round($amount * ($aff->commission_rate / 100), 2);
                        $ref->status = AffiliateReferral::STATUS_CONVERTED;
                        $ref->transaction_amount = $amount;
                        $ref->commission_amount = $commission;
                        $ref->registered_at = (clone $refAt)->addDay();
                        $ref->converted_at = (clone $refAt)->addDays(mt_rand(2, 10));
                        // ~55% of converted commissions already paid out.
                        if (mt_rand(0, 100) < 55) {
                            $ref->commission_status = AffiliateReferral::COMMISSION_PAID;
                            $ref->commission_paid_at = (clone $ref->converted_at)->addDays(mt_rand(5, 20));
                            $paidEarn += $commission;
                        } else {
                            $ref->commission_status = AffiliateReferral::COMMISSION_APPROVED;
                            $pendingEarn += $commission;
                        }
                        $converted++;
                    } elseif ($roll < 48) {
                        $ref->status = AffiliateReferral::STATUS_REGISTERED;
                        $ref->registered_at = (clone $refAt)->addDay();
                        $ref->commission_status = AffiliateReferral::COMMISSION_PENDING;
                    } else {
                        $ref->status = AffiliateReferral::STATUS_CLICKED;
                        $ref->commission_status = AffiliateReferral::COMMISSION_PENDING;
                    }
                    $ref->save();
                }

                $aff->update([
                    'total_referrals' => $refCount,
                    'successful_referrals' => $converted,
                    'total_earnings' => round($pendingEarn + $paidEarn, 2),
                    'pending_earnings' => round($pendingEarn, 2),
                    'paid_earnings' => round($paidEarn, 2),
                    'last_referral_at' => $lastRef,
                ]);

                // Payout history for what has been paid.
                if ($paidEarn > 0) {
                    $chunks = mt_rand(1, 3);
                    $per = round($paidEarn / $chunks, 2);
                    for ($c = 0; $c < $chunks; $c++) {
                        $doneAt = now()->subDays(mt_rand(10, 90));
                        AffiliatePayout::create([
                            'affiliate_id' => $aff->id,
                            'amount' => $per,
                            'currency' => 'USD',
                            'status' => AffiliatePayout::STATUS_COMPLETED,
                            'payout_method' => $method,
                            'payout_details' => ['account' => 'billing@'.$domain],
                            'transaction_reference' => 'TXN-'.strtoupper(substr(md5($aff->id.$c), 0, 10)),
                            'processed_at' => (clone $doneAt)->subDay(),
                            'completed_at' => $doneAt,
                            'created_at' => (clone $doneAt)->subDays(2),
                            'updated_at' => $doneAt,
                        ]);
                    }
                }
            }
        }

        $this->command?->info('Seeded '.count($names).' demo affiliates with referrals + payouts.');
    }
}
