<?php

declare(strict_types=1);

namespace Aero\Notifications\Console\Commands;

use Aero\Notifications\Services\Mail\MailService;
use Illuminate\Console\Command;

class TestMailConfiguration extends Command
{
    protected $signature = 'aero:notifications:test-mail {to?} {--subject=Test Email}';
    protected $description = 'Send a test email using the current mail context configuration';

    public function handle(MailService $mailService): int
    {
        $to = $this->argument('to') ?? config('mail.from.address');
        $result = $mailService->sendTestEmail($to, $this->option('subject'));
        if ($result['success']) { $this->info('Test email sent successfully.'); if (! empty($result['using_database_settings'])) $this->info('Used database settings.'); return self::SUCCESS; }
        $this->error('Failed: '.$result['message']); return self::FAILURE;
    }
}
