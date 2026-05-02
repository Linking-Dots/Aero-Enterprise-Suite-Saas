<?php

declare(strict_types=1);

namespace Aero\Notifications\Jobs;

use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public array $backoff;

    public function __construct(protected array $payload)
    {
        $this->tries = $payload['maxRetries'] ?? 3;
        $this->backoff = config('aero.notifications.retry.backoff_minutes', [1,5,15]);
    }

    public function handle(MailService $mailService): void
    {
        $result = $mailService->sendMail($this->payload['to'], $this->payload['subject'], $this->payload['htmlBody'], $this->payload['textBody'] ?? null, [
            'cc' => $this->payload['cc'] ?? [], 'bcc' => $this->payload['bcc'] ?? [],
            'replyTo' => $this->payload['replyTo'] ?? null, 'attachments' => $this->payload['attachments'] ?? [],
            'forcePlatformContext' => $this->payload['forcePlatformContext'] ?? false,
        ]);
        if (! $result['success']) throw new \RuntimeException($result['message']);
    }

    public function failed(\Throwable $e): void { Log::error('SendEmailJob failed permanently', ['to' => $this->payload['to'] ?? null, 'error' => $e->getMessage()]); }
}
