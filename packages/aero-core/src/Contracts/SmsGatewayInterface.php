<?php

declare(strict_types=1);

namespace Aero\Core\Contracts;

interface SmsGatewayInterface
{
    /**
     * Apply SMS settings from database to the gateway.
     */
    public function applySmsSettings(): void;

    /**
     * Send a test SMS.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestSms(string $phone): array;
}
