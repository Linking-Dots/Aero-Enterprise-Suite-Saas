<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Mail;

use Aero\Contracts\MailSenderInterface;
use Aero\Notifications\Contracts\MailContextResolver;
use Aero\Notifications\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService implements MailSenderInterface
{
    protected array $to = [];

    protected array $cc = [];

    protected array $bcc = [];

    protected array $attachments = [];

    protected ?string $replyTo = null;

    protected ?string $textBody = null;

    protected string $subject = '';

    protected string $htmlBody = '';

    protected bool $forcePlatformContext = false;

    protected bool $shouldQueue = false;

    protected ?string $queue = null;

    protected ?int $delay = null;

    protected int $maxRetries = 3;

    public static function make(): static
    {
        return new static;
    }

    public function to(string|array $to, ?string $name = null): static
    {
        $this->to = is_array($to) ? $to : [$to];

        return $this;
    }

    public function template(string $templateName, array $variables = []): static
    {
        $html = view($templateName, $variables)->render();
        $this->htmlBody = $html;

        return $this;
    }

    public function cc(string|array $cc): static
    {
        $this->cc = is_array($cc) ? $cc : [$cc];

        return $this;
    }

    public function bcc(string|array $bcc): static
    {
        $this->bcc = is_array($bcc) ? $bcc : [$bcc];

        return $this;
    }

    public function replyTo(string $r): static
    {
        $this->replyTo = $r;

        return $this;
    }

    public function subject(string $s): static
    {
        $this->subject = $s;

        return $this;
    }

    public function html(string $h): static
    {
        $this->htmlBody = $h;

        return $this;
    }

    public function text(string $t): static
    {
        $this->textBody = $t;

        return $this;
    }

    public function attach(string $path, ?string $name = null, ?string $mime = null): static
    {
        $this->attachments[] = ['path' => $path, 'name' => $name, 'mimeType' => $mime];

        return $this;
    }

    public function queue(?string $q = null, ?int $d = null): static
    {
        $this->shouldQueue = true;
        $this->queue = $q;
        $this->delay = $d;

        return $this;
    }

    public function retry(int $r): static
    {
        $this->maxRetries = $r;

        return $this;
    }

    public function usePlatformSettings(): static
    {
        $this->forcePlatformContext = true;

        return $this;
    }

    public function send(): array
    {
        if ($this->shouldQueue) {
            $job = new SendEmailJob([
                'to' => $this->to, 'cc' => $this->cc, 'bcc' => $this->bcc, 'replyTo' => $this->replyTo,
                'subject' => $this->subject, 'htmlBody' => $this->htmlBody, 'textBody' => $this->textBody,
                'attachments' => $this->attachments, 'forcePlatformContext' => $this->forcePlatformContext,
                'maxRetries' => $this->maxRetries,
            ]);
            if ($this->delay) {
                $job->delay($this->delay);
            }
            if ($this->queue) {
                dispatch($job)->onQueue($this->queue);
            } else {
                dispatch($job);
            }

            return ['success' => true, 'message' => 'Email queued'];
        }

        return $this->sendMail($this->to, $this->subject, $this->htmlBody, $this->textBody, [
            'cc' => $this->cc, 'bcc' => $this->bcc, 'replyTo' => $this->replyTo,
            'attachments' => $this->attachments, 'forcePlatformContext' => $this->forcePlatformContext,
        ]);
    }

    public function sendMail(string|array $to, string $subject, string $html, ?string $text = null, array $opts = []): array
    {
        try {
            $config = app(MailContextResolver::class)->resolve();
            $driver = $config['driver'] ?? 'log';

            return match ($driver) {
                'log' => $this->sendWithLog($to, $subject, $html, $config),
                'array' => $this->sendWithArray($to, $subject, $html, $config),
                default => $this->sendWithLaravelMailer($to, $subject, $html, $text, $config, $opts),
            };
        } catch (\Throwable $e) {
            Log::error('MailService error', ['to' => $to, 'subject' => $subject, 'error' => $e->getMessage()]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function sendWithLaravelMailer(string|array $to, string $subject, string $html, ?string $text, array $config, array $opts): array
    {
        $driver = $config['driver'] ?? 'smtp';

        // For SMTP with disabled peer verification, use raw Symfony transport
        // because Laravel's Mail facade does not expose verify_peer options.
        if ($driver === 'smtp' && ($config['verify_peer'] ?? true) === false) {
            return $this->sendWithRawSmtp($to, $subject, $html, $text, $config, $opts);
        }

        $mailerConfig = $this->buildLaravelMailerConfig($driver, $config);
        $mailerName = 'aero_'.uniqid();

        config(['mail.mailers.'.$mailerName => $mailerConfig]);

        try {
            Mail::mailer($mailerName)->html($html, function ($message) use ($to, $subject, $text, $opts, $config) {
                $message->to($to)
                    ->subject($subject)
                    ->from($config['from_address'], $config['from_name']);

                if ($text) {
                    $message->text($text);
                }
                if (! empty($opts['cc'])) {
                    $message->cc($opts['cc']);
                }
                if (! empty($opts['bcc'])) {
                    $message->bcc($opts['bcc']);
                }
                if (! empty($opts['replyTo'])) {
                    $message->replyTo($opts['replyTo']);
                }
                if (! empty($opts['attachments'])) {
                    foreach ($opts['attachments'] as $a) {
                        $message->attach($a['path'], ['as' => $a['name'] ?? null, 'mime' => $a['mimeType'] ?? null]);
                    }
                }
            });

            Log::info('Mail sent', ['to' => $to, 'subject' => $subject, 'driver' => $driver]);

            return ['success' => true, 'message' => 'Email sent'];
        } finally {
            config(['mail.mailers.'.$mailerName => null]);
        }
    }

    protected function sendWithRawSmtp(string|array $to, string $subject, string $html, ?string $text, array $config, array $opts): array
    {
        $scheme = ($config['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp';
        $dsn = sprintf(
            '%s://%s:%s@%s:%d?verify_peer=0',
            $scheme,
            urlencode($config['username'] ?? ''),
            urlencode($config['password'] ?? ''),
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 587)
        );

        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $email = (new Email)
            ->from(new Address($config['from_address'], $config['from_name']))
            ->subject($subject)
            ->html($html);

        foreach (is_array($to) ? $to : [$to] as $r) {
            $email->addTo($r);
        }

        if ($text) {
            $email->text($text);
        }
        if (! empty($opts['cc'])) {
            foreach ((array) $opts['cc'] as $c) {
                $email->addCc($c);
            }
        }
        if (! empty($opts['bcc'])) {
            foreach ((array) $opts['bcc'] as $b) {
                $email->addBcc($b);
            }
        }
        if (! empty($opts['replyTo'])) {
            $email->replyTo($opts['replyTo']);
        }
        if (! empty($opts['attachments'])) {
            foreach ($opts['attachments'] as $a) {
                $email->attachFromPath($a['path'], $a['name'] ?? null, $a['mimeType'] ?? null);
            }
        }

        $mailer->send($email);

        Log::info('Mail sent via raw SMTP (verify_peer disabled)', ['to' => $to, 'subject' => $subject]);

        return ['success' => true, 'message' => 'Email sent'];
    }

    protected function buildLaravelMailerConfig(string $driver, array $config): array
    {
        return match ($driver) {
            'smtp' => [
                'transport' => 'smtp',
                'url' => null,
                'host' => $config['host'] ?? '127.0.0.1',
                'port' => $config['port'] ?? 587,
                'encryption' => $config['encryption'] ?? 'tls',
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'timeout' => null,
                'local_domain' => null,
            ],
            'sendmail' => [
                'transport' => 'sendmail',
                'path' => $config['sendmail_path'] ?? '/usr/sbin/sendmail -bs',
            ],
            'mailgun' => [
                'transport' => 'mailgun',
                'domain' => $config['mailgun_domain'] ?? config('services.mailgun.domain'),
                'secret' => $config['mailgun_secret'] ?? config('services.mailgun.secret'),
                'endpoint' => $config['mailgun_endpoint'] ?? config('services.mailgun.endpoint', 'api.mailgun.net'),
                'scheme' => $config['mailgun_scheme'] ?? config('services.mailgun.scheme', 'https'),
            ],
            'postmark' => [
                'transport' => 'postmark',
                'token' => $config['postmark_token'] ?? config('services.postmark.token'),
            ],
            'ses' => [
                'transport' => 'ses',
                'key' => $config['ses_key'] ?? config('services.ses.key'),
                'secret' => $config['ses_secret'] ?? config('services.ses.secret'),
                'region' => $config['ses_region'] ?? config('services.ses.region', 'us-east-1'),
            ],
            default => [
                'transport' => $driver,
                ...$config,
            ],
        };
    }

    protected function sendWithArray(string|array $to, string $subject, string $html, array $config): array
    {
        $mailerName = 'aero_array_'.uniqid();
        config(['mail.mailers.'.$mailerName => ['transport' => 'array']]);

        try {
            Mail::mailer($mailerName)->html($html, function ($message) use ($to, $subject, $config) {
                $message->to($to)
                    ->subject($subject)
                    ->from($config['from_address'], $config['from_name']);
            });

            return ['success' => true, 'message' => 'Email captured'];
        } finally {
            config(['mail.mailers.'.$mailerName => null]);
        }
    }

    protected function sendWithLog(string|array $to, string $subject, string $html, array $config): array
    {
        Log::debug('Mail [LOG]: '.(is_array($to) ? implode(', ', $to) : $to), ['subject' => $subject, 'from' => $config['from_address'], 'preview' => substr(strip_tags($html), 0, 200).'...']);

        return ['success' => true, 'message' => 'Email logged'];
    }

    public function sendTestEmail(string $to, ?string $subject = null): array
    {
        $subject = $subject ?? 'Test - '.config('app.name');
        $config = app(MailContextResolver::class)->resolve();
        $driver = $config['driver'] ?? 'smtp';
        $driverInfo = match ($driver) {
            'smtp' => 'Host: '.($config['host'] ?? '127.0.0.1').':'.($config['port'] ?? 587),
            'sendmail' => 'Sendmail path: '.($config['sendmail_path'] ?? '/usr/sbin/sendmail -bs'),
            'mailgun' => 'Mailgun domain: '.($config['mailgun_domain'] ?? config('services.mailgun.domain', 'N/A')),
            'postmark' => 'Postmark token: '.(str_repeat('*', 8)),
            'ses' => 'AWS SES region: '.($config['ses_region'] ?? config('services.ses.region', 'us-east-1')),
            'array' => 'Array (testing) driver',
            'log' => 'Log driver',
            default => 'Driver: '.$driver,
        };
        $html = '<div style="font-family:Arial;max-width:600px;margin:0 auto;"><h2 style="color:#4F46E5;">Test Email</h2><p>Configuration:</p><ul><li>'.$driverInfo.'</li><li>From: '.($config['from_address'] ?? 'N/A').'</li><li>Time: '.now()->toDateTimeString().'</li></ul></div>';

        return array_merge($this->sendMail($to, $subject, $html), ['using_database_settings' => $config['configured'] ?? false]);
    }
}
