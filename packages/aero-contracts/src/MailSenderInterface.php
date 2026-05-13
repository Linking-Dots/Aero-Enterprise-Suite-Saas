<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface MailSenderInterface
{
    /**
     * @return array{success: bool, message: string, using_database_settings?: bool}
     */
    public function sendTestEmail(string $toAddress): array;
}
