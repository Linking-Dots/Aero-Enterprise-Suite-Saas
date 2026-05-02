<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Sms;

use Aero\Notifications\Contracts\SmsContextResolver;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected array $to = [];
    protected ?string $message = null;
    protected ?string $provider = null;
    protected bool $shouldQueue = false;
    protected ?string $queue = null;
    protected int $maxRetries = 3;

    public static function make(): static { return new static; }
    public function to(string|array $to): static { $this->to = is_array($to) ? $to : [$to]; return $this; }
    public function message(string $msg): static { $this->message = $msg; return $this; }
    public function provider(string $p): static { $this->provider = $p; return $this; }
    public function queue(?string $q = null): static { $this->shouldQueue = true; $this->queue = $q; return $this; }
    public function retry(int $r): static { $this->maxRetries = $r; return $this; }

    public function send(string|array|null $to = null, ?string $message = null): array
    {
        if ($to) $this->to = is_array($to) ? $to : [$to];
        if ($message) $this->message = $message;

        if (empty($this->to) || ! $this->message) {
            return ['success' => false, 'message' => 'Missing SMS recipient or message'];
        }

        if ($this->shouldQueue) {
            $job = new \Aero\Notifications\Jobs\SendSmsJob($this->to, $this->message, $this->provider, $this->maxRetries);
            if ($this->queue) dispatch($job)->onQueue($this->queue); else dispatch($job);
            return ['success' => true, 'message' => 'SMS queued'];
        }

        return $this->sendDirectly();
    }

    public function sendDirectly(string|array|null $to = null, ?string $message = null, ?string $provider = null): array
    {
        if ($to) $this->to = is_array($to) ? $to : [$to];
        if ($message) $this->message = $message;
        if ($provider) $this->provider = $provider;

        $resolver = app(SmsContextResolver::class)->resolve();
        $resolvedProvider = $this->provider ?? $resolver['provider'] ?? 'log';

        $success = true; $failed = [];
        foreach ($this->to as $number) {
            try {
                $result = app(SmsGatewayService::class)->send($number, $this->message, $resolvedProvider);
                if (! $result['success']) { $success = false; $failed[] = $number; }
            } catch (\Throwable $e) {
                Log::error('SMS send failed', ['number' => $number, 'error' => $e->getMessage()]);
                $success = false; $failed[] = $number;
            }
        }

        if (! $success && $resolver['provider'] !== 'log') {
            try {
                foreach ($failed as $number) app(SmsGatewayService::class)->send($number, $this->message, 'log');
            } catch (\Throwable $e) { Log::warning('SMS log failover failed', ['error' => $e->getMessage()]); }
        }

        return ['success' => $success, 'message' => $success ? 'SMS sent' : 'SMS failed for '.count($failed).' recipients', 'failed' => $failed];
    }

    public function template(string $name, array $vars): static
    {
        $this->message = app(SmsGatewayService::class)->renderTemplate($name, $vars);
        return $this;
    }
}
