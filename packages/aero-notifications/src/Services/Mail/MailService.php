<?php

declare(strict_types=1);

namespace Aero\Notifications\Services\Mail;

use Aero\Notifications\Contracts\MailContextResolver;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService
{
    protected array $to = [], $cc = [], $bcc = [], $attachments = [];
    protected ?string $replyTo = null, $textBody = null;
    protected string $subject = '', $htmlBody = '';
    protected bool $forcePlatformContext = false, $shouldQueue = false;
    protected ?string $queue = null; protected ?int $delay = null;
    protected int $maxRetries = 3;

    public static function make(): static { return new static; }
    public function to(string|array $to): static { $this->to = is_array($to) ? $to : [$to]; return $this; }
    public function cc(string|array $cc): static { $this->cc = is_array($cc) ? $cc : [$cc]; return $this; }
    public function bcc(string|array $bcc): static { $this->bcc = is_array($bcc) ? $bcc : [$bcc]; return $this; }
    public function replyTo(string $r): static { $this->replyTo = $r; return $this; }
    public function subject(string $s): static { $this->subject = $s; return $this; }
    public function html(string $h): static { $this->htmlBody = $h; return $this; }
    public function text(string $t): static { $this->textBody = $t; return $this; }
    public function attach(string $path, ?string $name = null, ?string $mime = null): static { $this->attachments[] = ['path' => $path, 'name' => $name, 'mimeType' => $mime]; return $this; }
    public function queue(?string $q = null, ?int $d = null): static { $this->shouldQueue = true; $this->queue = $q; $this->delay = $d; return $this; }
    public function retry(int $r): static { $this->maxRetries = $r; return $this; }
    public function usePlatformSettings(): static { $this->forcePlatformContext = true; return $this; }

    public function send(): array
    {
        if ($this->shouldQueue) {
            $job = new \Aero\Notifications\Jobs\SendEmailJob([
                'to' => $this->to, 'cc' => $this->cc, 'bcc' => $this->bcc, 'replyTo' => $this->replyTo,
                'subject' => $this->subject, 'htmlBody' => $this->htmlBody, 'textBody' => $this->textBody,
                'attachments' => $this->attachments, 'forcePlatformContext' => $this->forcePlatformContext,
                'maxRetries' => $this->maxRetries,
            ]);
            if ($this->delay) $job->delay($this->delay);
            if ($this->queue) dispatch($job)->onQueue($this->queue); else dispatch($job);
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
            if ($config['driver'] === 'log') return $this->sendWithLog($to, $subject, $html, $config);
            if ($config['driver'] !== 'smtp') return ['success' => false, 'message' => 'Unsupported driver: '.$config['driver']];
            return $this->sendWithSmtp($to, $subject, $html, $text, $config, $opts);
        } catch (\Throwable $e) {
            Log::error('MailService error', ['to' => $to, 'subject' => $subject, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function sendWithSmtp(string|array $to, string $subject, string $html, ?string $text, array $config, array $opts): array
    {
        $scheme = $config['encryption'] === 'ssl' ? 'smtps' : 'smtp';
        $dsn = sprintf('%s://%s:%s@%s:%d%s', $scheme, urlencode($config['username']), urlencode($config['password']), $config['host'], $config['port'], $config['verify_peer'] ? '' : '?verify_peer=0');
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);
        $email = (new Email)->from(new Address($config['from_address'], $config['from_name']))->subject($subject)->html($html);
        foreach (is_array($to) ? $to : [$to] as $r) $email->addTo($r);
        if ($text) $email->text($text);
        if (!empty($opts['cc'])) foreach ((array) $opts['cc'] as $c) $email->addCc($c);
        if (!empty($opts['bcc'])) foreach ((array) $opts['bcc'] as $b) $email->addBcc($b);
        if (!empty($opts['replyTo'])) $email->replyTo($opts['replyTo']);
        if (!empty($opts['attachments'])) foreach ($opts['attachments'] as $a) $email->attachFromPath($a['path'], $a['name'] ?? null, $a['mimeType'] ?? null);
        $mailer->send($email);
        Log::info('Mail sent', ['to' => $to, 'subject' => $subject]);
        return ['success' => true, 'message' => 'Email sent'];
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
        $html = '<div style="font-family:Arial;max-width:600px;margin:0 auto;"><h2 style="color:#4F46E5;">Test Email</h2><p>Configuration:</p><ul><li>Host: '.$config['host'].':'.$config['port'].'</li><li>From: '.$config['from_address'].'</li><li>Time: '.now()->toDateTimeString().'</li></ul></div>';
        return array_merge($this->sendMail($to, $subject, $html), ['using_database_settings' => $config['configured'] ?? false]);
    }
}
