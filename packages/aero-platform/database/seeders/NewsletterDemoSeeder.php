<?php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\NewsletterCampaign;
use Aero\Platform\Models\NewsletterSubscriber;
use Illuminate\Database\Seeder;

/**
 * NewsletterDemoSeeder
 *
 * Seeds a realistic newsletter audience (~180 subscribers across statuses/sources)
 * plus a campaign history (sent broadcasts with engagement metrics, one scheduled,
 * one draft) so the Newsletter command centre is fully populated for the FYP demo.
 * Reproducible; clears prior demo rows (email @demo-news.com / campaign names tagged)
 * before re-seeding.
 *
 * Run: php artisan db:seed --class="Aero\Platform\Database\Seeders\NewsletterDemoSeeder"
 */
class NewsletterDemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(20260710);

        NewsletterSubscriber::where('email', 'like', '%@demo-news.com')->forceDelete();
        NewsletterCampaign::whereJsonContains('metadata->demo', true)->forceDelete();

        $firsts = ['Amelia', 'Noah', 'Olivia', 'Liam', 'Ava', 'Ethan', 'Sophia', 'Mason', 'Isabella', 'Lucas',
            'Mia', 'Ravi', 'Aisha', 'Chen', 'Yuki', 'Omar', 'Sara', 'Tobias', 'Priya', 'Diego',
            'Fatima', 'Kwame', 'Elena', 'Hiro', 'Nadia', 'Marco', 'Leila', 'Sven', 'Ingrid', 'Tariq', 'Vera', 'Nina'];
        $lasts = ['Carter', 'Osei', 'Nakamura', 'Khan', 'Silva', 'Andersen', 'Costa', 'Mbeki', 'Rossi', 'Haddad',
            'Larsen', 'Das', 'Wei', 'Okafor', 'Novak', 'Fernandez', 'Bergstrom', 'Adebayo', 'Volkov', 'Reyes', 'Moll', 'Vogel'];
        $sources = ['popup', 'checkout', 'blog', 'footer', 'website', 'registration'];
        $prefsPool = ['product', 'offers', 'weekly-digest', 'company-news', 'events'];

        // Status mix: ~72% confirmed, ~16% pending, ~12% unsubscribed.
        $statusPlan = array_merge(
            array_fill(0, 130, NewsletterSubscriber::STATUS_CONFIRMED),
            array_fill(0, 29, NewsletterSubscriber::STATUS_PENDING),
            array_fill(0, 21, NewsletterSubscriber::STATUS_UNSUBSCRIBED),
        );
        shuffle($statusPlan);

        foreach ($statusPlan as $i => $status) {
            $first = $firsts[array_rand($firsts)];
            $last = $lasts[array_rand($lasts)];
            $createdDaysAgo = mt_rand(2, 185);
            $created = now()->subDays($createdDaysAgo)->setTime(mt_rand(6, 22), mt_rand(0, 59));

            $sub = new NewsletterSubscriber;
            $sub->email = strtolower($first.'.'.$last.($i + 1)).'@demo-news.com';
            $sub->name = "$first $last";
            $sub->status = $status;
            $sub->source = $sources[array_rand($sources)];
            $sub->preferences = array_values(array_unique(array_map(fn () => $prefsPool[array_rand($prefsPool)], range(0, mt_rand(0, 2)))));
            $sub->ip_address = mt_rand(1, 255).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(1, 255);
            $sub->created_at = $created;
            $sub->updated_at = $created;

            if ($status === NewsletterSubscriber::STATUS_CONFIRMED) {
                $sub->confirmed_at = (clone $created)->addMinutes(mt_rand(5, 2880));
            } elseif ($status === NewsletterSubscriber::STATUS_PENDING) {
                $sub->confirmation_token = \Illuminate\Support\Str::random(64);
            } elseif ($status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
                $sub->confirmed_at = (clone $created)->addDay();
                $sub->unsubscribed_at = (clone $created)->addDays(mt_rand(10, 90));
                $sub->unsubscribe_reason = ['Too many emails', 'No longer relevant', 'Never signed up', 'Content not useful'][mt_rand(0, 3)];
            }
            $sub->save();
        }

        // Audience size for realistic recipient counts.
        $confirmed = NewsletterSubscriber::where('status', NewsletterSubscriber::STATUS_CONFIRMED)->count();

        $campaigns = [
            ['June product update — new billing console', 'all_confirmed', null, 'sent', 12],
            ['Summer sale — 20% off annual plans', 'source', 'blog', 'sent', 26],
            ['May digest #23 — what shipped', 'all_confirmed', null, 'sent', 42],
            ['Welcome series — getting started', 'source', 'checkout', 'sent', 55],
            ['Spring feature roundup', 'all_confirmed', null, 'sent', 70],
            ['Case study — how Acme scaled', 'source', 'footer', 'sent', 88],
            ['Weekly digest #24', 'all_confirmed', null, 'scheduled', -2],
            ['Feature announcement — Aeon AI', 'all_confirmed', null, 'draft', 0],
        ];

        foreach ($campaigns as [$subject, $audType, $audSource, $status, $daysAgo]) {
            $recipients = $audType === 'all_confirmed'
                ? $confirmed
                : (int) max(20, round($confirmed * (mt_rand(20, 45) / 100)));

            $c = new NewsletterCampaign;
            $c->name = $subject;
            $c->subject = $subject;
            $c->preheader = 'A quick note from the AEOS365 team';
            $c->from_name = 'AEOS365';
            $c->from_email = 'hello@aeos365.com';
            $c->body = "Hi {{name}},\n\n".$subject.". Here's what's new this month across the platform — read on for the highlights.\n\n— The AEOS365 team";
            $c->status = $status;
            $c->audience_type = $audType;
            $c->audience_source = $audSource;
            $c->metadata = ['demo' => true];

            if ($status === NewsletterCampaign::STATUS_SENT) {
                $sentAt = now()->subDays($daysAgo);
                $openRate = mt_rand(360, 500) / 1000;   // 36–50%
                $clickRate = mt_rand(60, 130) / 1000;   // 6–13%
                $c->recipients_count = $recipients;
                $c->sent_count = $recipients;
                $c->open_count = (int) round($recipients * $openRate);
                $c->click_count = (int) round($recipients * $clickRate);
                $c->bounce_count = (int) round($recipients * 0.008);
                $c->unsubscribe_count = (int) round($recipients * 0.003);
                $c->sent_at = $sentAt;
                $c->created_at = (clone $sentAt)->subDays(mt_rand(1, 4));
                $c->updated_at = $sentAt;
            } elseif ($status === NewsletterCampaign::STATUS_SCHEDULED) {
                $c->recipients_count = $recipients;
                $c->scheduled_at = now()->subDays($daysAgo); // daysAgo negative => future
                $c->created_at = now()->subDays(3);
            } else { // draft
                $c->created_at = now()->subHours(mt_rand(1, 20));
                $c->updated_at = now()->subHours(mt_rand(0, 3));
            }
            $c->save();
        }

        $this->command?->info('Seeded '.count($statusPlan).' subscribers + '.count($campaigns).' campaigns.');
    }
}
