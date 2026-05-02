<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Notification;

use Aero\Notifications\Services\Mail\MailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailDigestService
{
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    protected array $categories = ['tasks','messages','alerts','system'];

    public function __construct(protected MailService $mailService) {}

    public function generateDigest(int $userId, string $frequency, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        $since = $frequency === self::FREQ_DAILY ? $date->copy()->startOfDay()->subDay() : $date->copy()->startOfWeek()->subWeek();
        $items = []; // downstream packages hook into this via events or callbacks
        foreach ($this->categories as $cat) { $items[$cat] = $this->fetchCategoryItems($userId, $cat, $since, $date); }
        $total = collect($items)->flatten(1)->count();
        return ['frequency' => $frequency, 'period_start' => $since->toDateTimeString(), 'period_end' => $date->toDateTimeString(), 'total_items' => $total, 'items' => $items];
    }

    public function sendDigest(array $digest, object $notifiable, ?array $prefs = null): array
    {
        if ($digest['total_items'] === 0) return ['success' => true, 'message' => 'No items to digest'];
        try {
            $html = view('aero-notifications::emails.digest', compact('digest'))->render();
            $subj = config('app.name').' '.ucfirst($digest['frequency']).' Digest';
            return $this->mailService->to($notifiable->email ?? '')->subject($subj)->html($html)->send();
        } catch (\Throwable $e) { Log::error('Digest send failed', ['error' => $e->getMessage()]); return ['success' => false, 'message' => $e->getMessage()]; }
    }

    public function processDueDigests(): void
    {
        // To be hooked by downstream packages that iterate users with digest frequency
        // $this->generateDigest($user->id, EmailDigestService::FREQ_DAILY);
        // $this->sendDigest($digest, $user);
    }

    protected function fetchCategoryItems(int $userId, string $category, Carbon $since, Carbon $until): array
    {
        // Stub: downstream packages override or extend
        return [];
    }
}
