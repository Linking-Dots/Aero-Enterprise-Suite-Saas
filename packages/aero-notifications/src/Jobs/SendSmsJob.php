<?php

declare(strict_types=1);

namespace Aero\Notifications\Jobs;

use Aero\Notifications\Services\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public array $backoff;

    public function __construct(
        protected array|string $to,
        protected string $message,
        protected ?string $provider = null,
        protected int $maxRetries = 3
    ) {
        $this->tries = $maxRetries;
        $this->backoff = config('aero.notifications.retry.backoff_minutes', [1,5,15]);
    }

    public function handle(SmsService $smsService): void
    {
        $result = $smsService->sendDirectly($this->to, $this->message, $this->provider);
        if (! $result['success']) throw new \RuntimeException($result['message'] ?? 'SMS failed');
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendSmsJob failed permanently', ['to' => $this->to, 'error' => $e->getMessage()]);
    }
}
