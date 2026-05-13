<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Sms;

use Aero\Contracts\SmsGatewayInterface;
use Aero\Notifications\Contracts\SmsContextResolver;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsGatewayService implements SmsGatewayInterface
{
    protected array $providers = ['twilio', 'bulksmsbd', 'elitbuzz', 'ssl_wireless', 'aws_sns', 'log'];

    public function renderTemplate(string $name, array $vars): string
    {
        $template = config('aero.sms-templates.'.$name, config('sms-templates.'.$name, $name));
        foreach ($vars as $key => $val) {
            $template = str_replace("{{$key}}", (string) $val, $template);
        }

        return $template;
    }

    public function send(string $to, string $message, ?string $provider = null): array
    {
        $config = app(SmsContextResolver::class)->resolve();
        $provider = $provider ?? $config['provider'] ?? 'log';
        if (! $provider || $provider === 'log') {
            return $this->sendViaLog($to, $message, $config);
        }

        $method = 'sendVia'.str_replace(['_', '-'], '', ucwords($provider, '_-'));
        if (! method_exists($this, $method)) {
            return ['success' => false, 'message' => "Unknown provider: {$provider}"];
        }

        $to = $this->normalizePhoneNumber($to);
        try {
            return $this->$method($to, $message, $config['credentials'] ?? []);
        } catch (\Throwable $e) {
            Log::error("SMS gateway error [{$provider}]", ['to' => $to, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function sendViaTwilio(string $to, string $msg, array $c): array
    {
        if (! class_exists(Client::class)) {
            return ['success' => false, 'message' => 'Twilio SDK not installed'];
        }
        $client = new Client($c['sid'] ?? $c['twilio_sid'], $c['token'] ?? $c['twilio_auth_token']);
        $client->messages->create($to, ['from' => $c['from'] ?? $c['twilio_from'], 'body' => $msg]);

        return ['success' => true, 'message' => 'Twilio SMS sent'];
    }

    protected function sendViaAwsSns(string $to, string $msg, array $c): array
    {
        if (! class_exists(SnsClient::class)) {
            return ['success' => false, 'message' => 'AWS SDK not installed'];
        }
        $sns = new SnsClient(['version' => 'latest', 'region' => $c['region'] ?? 'us-east-1', 'credentials' => ['key' => $c['key'] ?? $c['aws_key'], 'secret' => $c['secret'] ?? $c['aws_secret']]]);
        $sns->publish(['PhoneNumber' => $to, 'Message' => $msg]);

        return ['success' => true, 'message' => 'SNS SMS sent'];
    }

    protected function sendViaBulkSmsBd(string $to, string $msg, array $c): array
    {
        $resp = Http::get('https://bulksmsbd.net/api/smsapi', ['api_key' => $c['api_key'], 'senderid' => $c['sender_id'] ?? $c['senderid'], 'number' => $to, 'message' => $msg]);

        return ['success' => $resp->successful(), 'message' => $resp->body()];
    }

    protected function sendViaElitBuzz(string $to, string $msg, array $c): array
    {
        $resp = Http::asForm()->post($c['url'] ?? 'https://msg.elitbuzz-bd.com/smsapi', ['api_key' => $c['api_key'], 'type' => 'text', 'contacts' => $to, 'senderid' => $c['sender_id'] ?? $c['senderid'], 'msg' => $msg]);

        return ['success' => $resp->successful(), 'message' => $resp->body()];
    }

    protected function sendViaSslWireless(string $to, string $msg, array $c): array
    {
        $resp = Http::post($c['url'] ?? 'https://smsplus.sslwireless.com/api/v3/send-sms', ['api_token' => $c['api_token'], 'sid' => $c['sid'], 'sms' => $msg, 'msisdn' => $to, 'csms_id' => uniqid()]);

        return ['success' => $resp->successful(), 'message' => $resp->body()];
    }

    protected function sendViaLog(string $to, string $msg, array $c): array
    {
        Log::info("SMS [LOG] to {$to}: {$msg}");

        return ['success' => true, 'message' => 'SMS logged'];
    }

    protected function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9+]/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '+88'.$number;
        }

        return $number;
    }

    /**
     * Apply SMS settings from database/runtime configuration.
     * Backward-compatible method for platform setting controllers.
     */
    public function applySmsSettings(): void
    {
        $config = app(SmsContextResolver::class)->resolve();
        if (! empty($config['configured'])) {
            config(['services.sms.default' => $config['provider'] ?? 'log']);
            config(['services.sms.credentials' => $config['credentials'] ?? []]);
        }
    }

    /**
     * Send a test SMS to the given phone number.
     * Backward-compatible method for platform setting controllers.
     */
    public function sendTestSms(string $phone): array
    {
        return $this->send($phone, 'This is a test SMS from aeos365.');
    }
}
