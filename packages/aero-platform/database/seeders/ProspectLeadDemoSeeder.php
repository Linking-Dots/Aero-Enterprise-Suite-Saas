<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\ProspectLead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ProspectLeadDemoSeeder
 *
 * Seeds ~60 realistic prospect leads so the Leads CRM command centre shows a
 * full pipeline (every stage, source, score band and assignee) for the FYP demo.
 * Reproducible via a fixed mt_srand; clears prior demo leads (email @demo-lead.com)
 * before re-seeding so re-runs never duplicate.
 *
 * Run: php artisan db:seed --class="Aero\Platform\Database\Seeders\ProspectLeadDemoSeeder"
 */
class ProspectLeadDemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260710);

        // Clear prior demo leads so the seeder is idempotent.
        ProspectLead::where('email', 'like', '%@demo-lead.com')->forceDelete();

        $firsts = ['Amelia', 'Noah', 'Olivia', 'Liam', 'Ava', 'Ethan', 'Sophia', 'Mason', 'Isabella', 'Lucas',
            'Mia', 'Ravi', 'Aisha', 'Chen', 'Yuki', 'Omar', 'Sara', 'Tobias', 'Priya', 'Diego',
            'Fatima', 'Kwame', 'Elena', 'Hiro', 'Nadia', 'Marco', 'Leila', 'Sven', 'Ingrid', 'Tariq'];
        $lasts = ['Carter', 'Osei', 'Nakamura', 'Khan', 'Silva', 'Andersen', 'Costa', 'Mbeki', 'Rossi', 'Haddad',
            'Larsen', 'Das', 'Wei', 'Okafor', 'Novak', 'Fernandez', 'Bergström', 'Adebayo', 'Volkov', 'Reyes'];
        $companies = ['Northwind Tech', 'Acme Retail', 'Kumasi Foods', 'Sakura HR', 'Fjord Logistics', 'Union Partners',
            'BrightBPO', 'Solu Labs', 'Meridian Health', 'Vega Media', 'Delta Freight', 'Orbit Studios',
            'Cedar Financial', 'Lumen Energy', 'Atlas Manufacturing', 'Harbor Retail', 'Nova Learning',
            'Pinnacle Legal', 'Quanta Analytics', 'Riverstone Group', 'Summit Foods', 'Terra Agro',
            'Vertex Software', 'Wavelength Media', 'Zephyr Travel', 'Apex Dental', 'Bluebird Care'];
        $countries = ['US', 'GB', 'GH', 'JP', 'NO', 'IN', 'BR', 'DE', 'NG', 'AE', 'SE', 'CA', 'AU', 'FR'];
        $sources = [
            ProspectLead::SOURCE_WEBSITE, ProspectLead::SOURCE_REFERRAL, ProspectLead::SOURCE_SOCIAL,
            ProspectLead::SOURCE_ADVERTISING, ProspectLead::SOURCE_EVENT, ProspectLead::SOURCE_NEWSLETTER,
            ProspectLead::SOURCE_DEMO_REQUEST,
        ];
        $interestsPool = ['hrm', 'payroll', 'finance', 'crm', 'pos', 'inventory', 'projects', 'assets'];
        $utmCampaigns = ['q3-hrm', 'payroll-launch', 'brand-search', 'retargeting', 'webinar-june', 'partner-co'];
        $lostReasons = ['Chose a competitor', 'Budget constraints', 'No decision — went quiet', 'Not a fit (too small)', 'Timing — revisit next year'];

        // Pipeline shape: weighted toward the top of the funnel.
        $statusPlan = array_merge(
            array_fill(0, 20, ProspectLead::STATUS_NEW),
            array_fill(0, 14, ProspectLead::STATUS_CONTACTED),
            array_fill(0, 10, ProspectLead::STATUS_QUALIFIED),
            array_fill(0, 8, ProspectLead::STATUS_CONVERTED),
            array_fill(0, 8, ProspectLead::STATUS_LOST),
        );

        $userIds = DB::connection('central')->table('users')->pluck('id')->all();

        $usedNames = [];
        $rows = 0;

        foreach ($statusPlan as $i => $status) {
            $first = $firsts[array_rand($firsts)];
            $last = $lasts[array_rand($lasts)];
            $name = "$first $last";
            // keep emails unique
            $slug = strtolower($first.'.'.$last).($usedNames[$name] ?? '');
            $usedNames[$name] = ($usedNames[$name] ?? 0) + 1;

            $source = $sources[array_rand($sources)];
            $interest = ['low', 'medium', 'high'][mt_rand(0, 2)];
            // qualified/converted skew higher interest
            if (in_array($status, [ProspectLead::STATUS_QUALIFIED, ProspectLead::STATUS_CONVERTED], true)) {
                $interest = ['medium', 'high', 'high'][mt_rand(0, 2)];
            }

            $createdDaysAgo = mt_rand(3, 175);
            $created = now()->subDays($createdDaysAgo)->setTime(mt_rand(8, 18), mt_rand(0, 59));

            $lead = new ProspectLead;
            $lead->email = $slug.'@demo-lead.com';
            $lead->name = $name;
            $lead->company_name = $companies[array_rand($companies)];
            $lead->phone = mt_rand(0, 3) === 0 ? null : '+'.mt_rand(1, 44).mt_rand(100000000, 999999999);
            $lead->country = $countries[array_rand($countries)];
            $lead->source = $source;
            $lead->source_detail = $source === ProspectLead::SOURCE_DEMO_REQUEST ? 'pricing page' : ($source === ProspectLead::SOURCE_REFERRAL ? 'partner referral' : null);
            $lead->status = $status;
            $lead->interest_level = $interest;
            $lead->interests = array_values(array_unique(array_map(fn () => $interestsPool[array_rand($interestsPool)], range(0, mt_rand(0, 2)))));
            $lead->utm_data = $source === ProspectLead::SOURCE_ADVERTISING
                ? ['source' => 'google', 'medium' => 'cpc', 'campaign' => $utmCampaigns[array_rand($utmCampaigns)]]
                : [];
            $lead->notes = mt_rand(0, 2) === 0 ? 'Requested a '.mt_rand(5, 40).'-seat demo; evaluating against an incumbent.' : null;
            $lead->assigned_to = (! empty($userIds) && $status !== ProspectLead::STATUS_NEW && mt_rand(0, 100) < 78)
                ? $userIds[array_rand($userIds)]
                : (! empty($userIds) && mt_rand(0, 100) < 25 ? $userIds[array_rand($userIds)] : null);

            // Stage timestamps in order.
            $cursor = (clone $created);
            if (in_array($status, [ProspectLead::STATUS_CONTACTED, ProspectLead::STATUS_QUALIFIED, ProspectLead::STATUS_CONVERTED], true)
                || ($status === ProspectLead::STATUS_LOST && mt_rand(0, 1))) {
                $cursor = $cursor->addDays(mt_rand(1, 5));
                $lead->contacted_at = (clone $cursor);
            }
            if (in_array($status, [ProspectLead::STATUS_QUALIFIED, ProspectLead::STATUS_CONVERTED], true)) {
                $cursor = $cursor->addDays(mt_rand(2, 9));
                $lead->qualified_at = (clone $cursor);
            }
            if ($status === ProspectLead::STATUS_CONVERTED) {
                $cursor = $cursor->addDays(mt_rand(2, 14));
                $lead->converted_at = (clone $cursor);
                // converted_tenant_id is an integer column; demo tenants use string
                // ids, so we leave the link null (status alone marks conversion).
            }
            if ($status === ProspectLead::STATUS_LOST) {
                $meta = $lead->metadata ?? [];
                $meta['lost_reason'] = $lostReasons[array_rand($lostReasons)];
                $lead->metadata = $meta;
            }
            $lead->last_activity_at = (clone $cursor);
            $lead->created_at = $created;
            $lead->updated_at = (clone $cursor);
            $lead->save();

            // Score after save (uses the model's real scoring formula).
            $lead->calculateScore();
            $rows++;
        }

        $this->command?->info("Seeded {$rows} demo prospect leads.");
    }
}
