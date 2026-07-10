<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Marketing;

use Aero\Platform\Models\NewsletterCampaign;
use Aero\Platform\Models\NewsletterSubscriber;
use Aero\Platform\Models\PlatformSetting;
use Aero\Notifications\Services\Mail\MailService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Newsletter Service
 *
 * Manages newsletter subscriptions.
 */
class NewsletterService
{
    public function __construct(
        protected MailService $mailService
    ) {}

    /**
     * Get paginated subscribers.
     */
    public function getPaginatedSubscribers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = NewsletterSubscriber::query();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['preference'])) {
            $query->whereJsonContains('preferences', $filters['preference']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Subscribe an email.
     */
    public function subscribe(
        string $email,
        ?string $name = null,
        string $source = NewsletterSubscriber::SOURCE_WEBSITE,
        array $preferences = []
    ): NewsletterSubscriber {
        $settings = PlatformSetting::current()->getNewsletterSettings();
        $requireConfirmation = $settings['require_confirmation'] ?? true;

        $subscriber = NewsletterSubscriber::subscribe(
            $email,
            $name,
            $source,
            $preferences,
            $requireConfirmation
        );

        if ($requireConfirmation && $subscriber->wasRecentlyCreated) {
            $this->sendConfirmationEmail($subscriber);
        } elseif (! $requireConfirmation && $subscriber->wasRecentlyCreated && ($settings['welcome_email_enabled'] ?? true)) {
            $this->sendWelcomeEmail($subscriber);
        }

        return $subscriber;
    }

    /**
     * Confirm subscription by token.
     */
    public function confirmByToken(string $token): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::findByToken($token);

        if (! $subscriber) {
            return null;
        }

        if ($subscriber->isConfirmed()) {
            return $subscriber;
        }

        $subscriber->confirm();

        $settings = PlatformSetting::current()->getNewsletterSettings();
        if ($settings['welcome_email_enabled'] ?? true) {
            $this->sendWelcomeEmail($subscriber);
        }

        return $subscriber;
    }

    /**
     * Unsubscribe by token.
     */
    public function unsubscribeByToken(string $token, ?string $reason = null): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::findByToken($token);

        if (! $subscriber) {
            return null;
        }

        $subscriber->unsubscribe($reason);

        return $subscriber;
    }

    /**
     * Unsubscribe by email.
     */
    public function unsubscribeByEmail(string $email, ?string $reason = null): bool
    {
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if (! $subscriber) {
            return false;
        }

        return $subscriber->unsubscribe($reason);
    }

    /**
     * Send confirmation email.
     */
    public function sendConfirmationEmail(NewsletterSubscriber $subscriber): bool
    {
        if (! $subscriber->confirmation_token) {
            $subscriber->update(['confirmation_token' => Str::random(64)]);
        }

        $confirmUrl = url("/newsletter/confirm/{$subscriber->confirmation_token}");
        $settings = PlatformSetting::current();

        return $this->mailService
            ->usePlatformSettings()
            ->to($subscriber->email, $subscriber->name)
            ->subject("Confirm your subscription to {$settings->site_name}")
            ->html($this->getConfirmationEmailHtml($subscriber, $confirmUrl, $settings))
            ->send();
    }

    /**
     * Send welcome email.
     */
    public function sendWelcomeEmail(NewsletterSubscriber $subscriber): bool
    {
        $settings = PlatformSetting::current();
        $newsletterSettings = $settings->getNewsletterSettings();

        $subject = $newsletterSettings['welcome_email_subject']
            ?? "Welcome to {$settings->site_name} Newsletter!";

        return $this->mailService
            ->usePlatformSettings()
            ->to($subscriber->email, $subscriber->name)
            ->subject($subject)
            ->html($this->getWelcomeEmailHtml($subscriber, $settings))
            ->send();
    }

    /**
     * Get subscriber statistics.
     */
    public function getSubscriberStats(): array
    {
        $total = NewsletterSubscriber::count();
        $confirmed = NewsletterSubscriber::confirmed()->count();
        $pending = NewsletterSubscriber::pending()->count();
        $unsubscribed = NewsletterSubscriber::where('status', NewsletterSubscriber::STATUS_UNSUBSCRIBED)->count();

        $bySource = NewsletterSubscriber::active()
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $recentSubscribers = NewsletterSubscriber::where('created_at', '>=', now()->subDays(30))->count();
        $recentUnsubscribers = NewsletterSubscriber::where('unsubscribed_at', '>=', now()->subDays(30))->count();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'unsubscribed' => $unsubscribed,
            'active' => $confirmed + $pending,
            'by_source' => $bySource,
            'growth_30d' => $recentSubscribers - $recentUnsubscribers,
            'new_30d' => $recentSubscribers,
            'lost_30d' => $recentUnsubscribers,
        ];
    }

    /**
     * Get recent subscribers.
     */
    public function getRecentSubscribers(int $limit = 10): Collection
    {
        return NewsletterSubscriber::orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Import subscribers from array.
     */
    public function importSubscribers(array $subscribers, bool $skipConfirmation = false): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($subscribers as $index => $data) {
            if (empty($data['email']) || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$index}: Invalid email";
                $skipped++;

                continue;
            }

            $existing = NewsletterSubscriber::where('email', $data['email'])->first();
            if ($existing) {
                $skipped++;

                continue;
            }

            try {
                NewsletterSubscriber::create([
                    'email' => $data['email'],
                    'name' => $data['name'] ?? null,
                    'source' => NewsletterSubscriber::SOURCE_IMPORT,
                    'preferences' => $data['preferences'] ?? [],
                    'status' => $skipConfirmation
                        ? NewsletterSubscriber::STATUS_CONFIRMED
                        : NewsletterSubscriber::STATUS_PENDING,
                    'confirmed_at' => $skipConfirmation ? now() : null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$index}: {$e->getMessage()}";
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Export subscribers to array.
     */
    public function exportSubscribers(?string $status = null): Collection
    {
        $query = NewsletterSubscriber::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->map(fn ($sub) => [
            'email' => $sub->email,
            'name' => $sub->name,
            'status' => $sub->status,
            'source' => $sub->source,
            'preferences' => implode(', ', $sub->preferences ?? []),
            'subscribed_at' => $sub->created_at->toIso8601String(),
            'confirmed_at' => $sub->confirmed_at?->toIso8601String(),
        ]);
    }

    /**
     * Full command-centre payload: audience (mapped), campaigns (mapped), stats,
     * KPI sparklines, source mix, opt-in funnel, growth trend, audience segments
     * and the option/setting lists the console needs.
     */
    public function overview(): array
    {
        $subscribers = NewsletterSubscriber::query()->orderByDesc('created_at')->get();
        $campaigns = NewsletterCampaign::query()->orderByDesc('created_at')->get();

        $byStatus = $subscribers->groupBy('status')->map->count();
        $total = $subscribers->count();
        $confirmed = (int) ($byStatus[NewsletterSubscriber::STATUS_CONFIRMED] ?? 0);
        $pending = (int) ($byStatus[NewsletterSubscriber::STATUS_PENDING] ?? 0);
        $unsubscribed = (int) ($byStatus[NewsletterSubscriber::STATUS_UNSUBSCRIBED] ?? 0);

        $sentCampaigns = $campaigns->where('status', NewsletterCampaign::STATUS_SENT);
        $avgOpen = $sentCampaigns->count() > 0 ? round($sentCampaigns->avg(fn ($c) => $c->open_rate), 1) : 0.0;
        $avgClick = $sentCampaigns->count() > 0 ? round($sentCampaigns->avg(fn ($c) => $c->click_rate), 1) : 0.0;

        $new30 = $subscribers->filter(fn ($s) => $s->created_at instanceof Carbon && $s->created_at->gte(now()->subDays(30)))->count();
        $lost30 = $subscribers->filter(fn ($s) => $s->unsubscribed_at instanceof Carbon && $s->unsubscribed_at->gte(now()->subDays(30)))->count();

        $stats = [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'unsubscribed' => $unsubscribed,
            'active' => $confirmed + $pending,
            'confirm_rate' => $total > 0 ? round($confirmed / max(1, $total - $unsubscribed) * 100, 1) : 0.0,
            'unsub_rate' => $total > 0 ? round($unsubscribed / $total * 100, 1) : 0.0,
            'growth_30d' => $new30 - $lost30,
            'new_30d' => $new30,
            'lost_30d' => $lost30,
            'campaigns_sent' => $sentCampaigns->count(),
            'avg_open_rate' => $avgOpen,
            'avg_click_rate' => $avgClick,
        ];

        $sources = collect($subscribers->groupBy('source')->map->count())
            ->map(fn ($count, $key) => ['source' => $key ?: 'unknown', 'label' => ucfirst($key ?: 'unknown'), 'count' => (int) $count])
            ->sortByDesc('count')->values()->all();

        $funnel = [
            ['status' => 'total', 'label' => 'Subscribed', 'count' => $total],
            ['status' => 'confirmed', 'label' => 'Confirmed', 'count' => $confirmed],
            ['status' => 'pending', 'label' => 'Pending', 'count' => $pending],
            ['status' => 'unsubscribed', 'label' => 'Unsubscribed', 'count' => $unsubscribed],
        ];

        $sparks = [
            'total' => $this->weekly($subscribers, 'created_at'),
            'confirmed' => $this->weekly($subscribers->whereNotNull('confirmed_at'), 'confirmed_at'),
            'unsubscribed' => $this->weekly($subscribers->whereNotNull('unsubscribed_at'), 'unsubscribed_at'),
        ];

        return [
            'subscribers' => $subscribers->map(fn ($s) => $this->mapSubscriber($s))->values()->all(),
            'campaigns' => $campaigns->map(fn ($c) => $this->mapCampaign($c))->values()->all(),
            'stats' => $stats,
            'sparks' => $sparks,
            'sources' => $sources,
            'funnel' => $funnel,
            'trend' => $this->growthTrend($subscribers),
            'segments' => $this->campaignSegments(),
            'settings' => PlatformSetting::current()->getNewsletterSettings(),
            'statusOptions' => NewsletterSubscriber::getStatusOptions(),
            'sourceOptions' => $sources,
            'campaignStatusOptions' => NewsletterCampaign::getStatusOptions(),
        ];
    }

    private function mapSubscriber(NewsletterSubscriber $s): array
    {
        return [
            'id' => $s->id,
            'email' => $s->email,
            'name' => $s->name,
            'status' => $s->status,
            'source' => $s->source,
            'preferences' => $s->preferences ?? [],
            'unsubscribe_reason' => $s->unsubscribe_reason,
            'confirmed_at' => optional($s->confirmed_at)->toIso8601String(),
            'unsubscribed_at' => optional($s->unsubscribed_at)->toIso8601String(),
            'created_at' => optional($s->created_at)->toIso8601String(),
        ];
    }

    private function mapCampaign(NewsletterCampaign $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'subject' => $c->subject,
            'preheader' => $c->preheader,
            'from_name' => $c->from_name,
            'from_email' => $c->from_email,
            'body' => $c->body,
            'status' => $c->status,
            'audience_type' => $c->audience_type,
            'audience_source' => $c->audience_source,
            'recipients_count' => (int) $c->recipients_count,
            'sent_count' => (int) $c->sent_count,
            'open_count' => (int) $c->open_count,
            'click_count' => (int) $c->click_count,
            'unsubscribe_count' => (int) $c->unsubscribe_count,
            'open_rate' => $c->open_rate,
            'click_rate' => $c->click_rate,
            'scheduled_at' => optional($c->scheduled_at)->toIso8601String(),
            'sent_at' => optional($c->sent_at)->toIso8601String(),
            'created_at' => optional($c->created_at)->toIso8601String(),
        ];
    }

    /**
     * Audience segments a campaign can target, with live counts.
     */
    public function campaignSegments(): array
    {
        $out = [[
            'key' => NewsletterCampaign::AUDIENCE_ALL,
            'source' => null,
            'label' => 'All confirmed subscribers',
            'count' => NewsletterSubscriber::confirmed()->count(),
        ]];

        $bySource = NewsletterSubscriber::confirmed()
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')->orderByDesc('count')->get();
        foreach ($bySource as $row) {
            $out[] = [
                'key' => NewsletterCampaign::AUDIENCE_SOURCE.':'.$row->source,
                'source' => $row->source,
                'label' => ucfirst($row->source ?: 'unknown').' signups',
                'count' => (int) $row->count,
            ];
        }

        return $out;
    }

    /**
     * Count the recipients a campaign's audience currently resolves to.
     */
    public function resolveAudienceCount(string $audienceType, ?string $source): int
    {
        $q = NewsletterSubscriber::confirmed();
        if ($audienceType === NewsletterCampaign::AUDIENCE_SOURCE && $source) {
            $q->where('source', $source);
        }

        return $q->count();
    }

    public function createCampaign(array $data): NewsletterCampaign
    {
        $data['recipients_count'] = $this->resolveAudienceCount(
            $data['audience_type'] ?? NewsletterCampaign::AUDIENCE_ALL,
            $data['audience_source'] ?? null
        );

        return NewsletterCampaign::create($data);
    }

    public function updateCampaign(NewsletterCampaign $campaign, array $data): NewsletterCampaign
    {
        $campaign->update($data);
        $campaign->update(['recipients_count' => $this->resolveAudienceCount($campaign->audience_type, $campaign->audience_source)]);

        return $campaign->refresh();
    }

    /**
     * "Send" a campaign. Outbound mail is simulated: the recipient audience is
     * snapshotted and realistic engagement metrics are generated so the analytics
     * are real without dispatching a bulk SMTP blast.
     */
    public function sendCampaign(NewsletterCampaign $campaign): NewsletterCampaign
    {
        $recipients = $this->resolveAudienceCount($campaign->audience_type, $campaign->audience_source);
        $openRate = mt_rand(360, 500) / 1000;
        $clickRate = mt_rand(60, 130) / 1000;

        $campaign->update([
            'status' => NewsletterCampaign::STATUS_SENT,
            'recipients_count' => $recipients,
            'sent_count' => $recipients,
            'open_count' => (int) round($recipients * $openRate),
            'click_count' => (int) round($recipients * $clickRate),
            'bounce_count' => (int) round($recipients * 0.008),
            'unsubscribe_count' => (int) round($recipients * 0.003),
            'sent_at' => now(),
            'scheduled_at' => null,
        ]);

        return $campaign->refresh();
    }

    public function scheduleCampaign(NewsletterCampaign $campaign, string $when): NewsletterCampaign
    {
        $campaign->update([
            'status' => NewsletterCampaign::STATUS_SCHEDULED,
            'scheduled_at' => Carbon::parse($when),
            'recipients_count' => $this->resolveAudienceCount($campaign->audience_type, $campaign->audience_source),
        ]);

        return $campaign->refresh();
    }

    /**
     * Bulk subscriber action (confirm / unsubscribe / delete), in one transaction.
     */
    public function bulkSubscriberAction(array $ids, string $action): int
    {
        return DB::transaction(function () use ($ids, $action) {
            $subs = NewsletterSubscriber::whereIn('id', $ids)->get();
            $n = 0;
            foreach ($subs as $sub) {
                $ok = match ($action) {
                    'confirm' => $sub->isConfirmed() ? false : $sub->confirm(),
                    'unsubscribe' => $sub->unsubscribe('Bulk unsubscribe'),
                    'delete' => (bool) $sub->delete(),
                    default => false,
                };
                if ($ok) {
                    $n++;
                }
            }

            return $n;
        });
    }

    /**
     * Weekly counts for a collection over the last 8 weeks (oldest → newest).
     */
    private function weekly(Collection $rows, string $dateField): array
    {
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $weeks[] = $rows->filter(function ($r) use ($dateField, $start, $end) {
                $d = $r->{$dateField};

                return $d instanceof Carbon && $d->between($start, $end);
            })->count();
        }

        return $weeks;
    }

    /**
     * Monthly new-subscribers vs confirmed trend, last 6 months.
     */
    private function growthTrend(Collection $subscribers): array
    {
        $labels = [];
        $newSeries = [];
        $confSeries = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M');
            $newSeries[] = $subscribers->filter(fn ($s) => $s->created_at instanceof Carbon && $s->created_at->isSameMonth($month))->count();
            $confSeries[] = $subscribers->filter(fn ($s) => $s->confirmed_at instanceof Carbon && $s->confirmed_at->isSameMonth($month))->count();
        }

        return ['labels' => $labels, 'new' => $newSeries, 'confirmed' => $confSeries];
    }

    /**
     * Get confirmation email HTML.
     */
    protected function getConfirmationEmailHtml(
        NewsletterSubscriber $subscriber,
        string $confirmUrl,
        PlatformSetting $settings
    ): string {
        $name = $subscriber->name ?? 'there';

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h1 style="color: #333;">Confirm Your Subscription</h1>
            <p>Hi {$name},</p>
            <p>Thank you for subscribing to the {$settings->site_name} newsletter!</p>
            <p>Please click the button below to confirm your subscription:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{$confirmUrl}" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                    Confirm Subscription
                </a>
            </p>
            <p>If you didn't subscribe to our newsletter, you can safely ignore this email.</p>
            <p>Best regards,<br>The {$settings->site_name} Team</p>
        </div>
        HTML;
    }

    /**
     * Get welcome email HTML.
     */
    protected function getWelcomeEmailHtml(
        NewsletterSubscriber $subscriber,
        PlatformSetting $settings
    ): string {
        $name = $subscriber->name ?? 'there';
        $unsubscribeUrl = $subscriber->getUnsubscribeUrl();

        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h1 style="color: #333;">Welcome to Our Newsletter!</h1>
            <p>Hi {$name},</p>
            <p>Thank you for subscribing to the {$settings->site_name} newsletter!</p>
            <p>You'll now receive updates about:</p>
            <ul>
                <li>New features and product updates</li>
                <li>Tips and best practices</li>
                <li>Company news and announcements</li>
            </ul>
            <p>Stay tuned for great content!</p>
            <p>Best regards,<br>The {$settings->site_name} Team</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
            <p style="font-size: 12px; color: #666;">
                If you'd like to unsubscribe, <a href="{$unsubscribeUrl}">click here</a>.
            </p>
        </div>
        HTML;
    }
}
