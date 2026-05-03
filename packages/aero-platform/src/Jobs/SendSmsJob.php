<?php

namespace Aero\Platform\Jobs;

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

    /**
     * Number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The phone number
     */
    protected string $phoneNumber;

    /**
     * The message
     */
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message, int $tries = 3)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->tries = $tries;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            $result = $smsService->sendDirectly($this->phoneNumber, $this->message);

            if (! $result['success']) {
                throw new \Exception($result['message'] ?? 'SMS sending failed');
            }

            Log::info('SendSmsJob: SMS sent successfully', [
                'phone' => $this->phoneNumber,
                'provider' => $result['provider'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('SendSmsJob: Failed to send SMS', [
                'error' => $e->getMessage(),
                'phone' => $this->phoneNumber,
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob: Job permanently failed after all retries', [
            'error' => $exception->getMessage(),
            'phone' => $this->phoneNumber,
        ]);
    }
}
