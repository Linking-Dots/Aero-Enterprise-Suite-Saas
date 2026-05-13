<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface MailSenderInterface
{
    /**
     * Send a test email. Returns result array.
     *
     * @return array{success: bool, message: string, using_database_settings?: bool}
     */
    public function sendTestEmail(string $toAddress): array;
}
