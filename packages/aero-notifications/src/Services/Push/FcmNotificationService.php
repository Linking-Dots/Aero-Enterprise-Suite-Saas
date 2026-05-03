<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Push;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmNotificationService
{
    protected ?\Kreait\Firebase\Messaging $messaging = null;

    public function __construct(?string $credentialsPath = null)
    {
        $path = $credentialsPath ?? config('services.firebase.credentials', config('firebase.credentials.file'));
        if ($path && file_exists($path)) {
            $this->messaging = (new Factory)->withServiceAccount($path)->createMessaging();
        }
    }

    public function isConfigured(): bool { return $this->messaging !== null; }

    public function sendNotification(string $token, string $title, string $body, array $data = []): bool
    {
        return $this->send($token, $title, $body, $data);
    }

    public function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (! $this->messaging) { Log::error('FCM not configured'); return false; }
        try {
            $msg = CloudMessage::new()->withTarget('token', $token)->withNotification(Notification::create($title, $body))->withData($data);
            $this->messaging->send($msg);
            return true;
        } catch (\Throwable $e) { Log::error('FCM send error', ['token' => $token, 'error' => $e->getMessage()]); return false; }
    }

    public function sendMulticastNotification(array $tokens, string $title, string $body, array $data = []): array
    {
        if (! $this->messaging) { Log::error('FCM not configured'); return ['success' => false, 'failed' => count($tokens)]; }
        $valid = array_values(array_filter($tokens)); $results = ['success' => true, 'sent' => 0, 'failed' => 0, 'invalid_tokens' => []];
        if (empty($valid)) return $results;
        try {
            $msg = CloudMessage::new()->withNotification(Notification::create($title, $body))->withData($data);
            $batch = $this->messaging->sendMulticast($msg, $valid);
            $results['sent'] = $batch->successes()->count(); $results['failed'] = $batch->failures()->count();
            foreach ($batch->failures() as $failure) { if (str_contains($failure->error()->getMessage(), 'invalid token')) $results['invalid_tokens'][] = $valid[$failure->target()->type() === 'token' ? $failure->target()->value() : $failure->message()->target()->value()] ?? null; }
            $results['success'] = $results['failed'] === 0;
        } catch (\Throwable $e) { Log::error('FCM multicast error', ['error' => $e->getMessage()]); $results['success'] = false; $results['failed'] = count($valid); }
        return $results;
    }
}
