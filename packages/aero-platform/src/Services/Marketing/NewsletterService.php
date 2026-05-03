<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Marketing;

use Aero\Platform\Models\NewsletterSubscriber;
use Aero\Platform\Models\PlatformSetting;
use Aero\Notifications\Services\Mail\MailService;
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
