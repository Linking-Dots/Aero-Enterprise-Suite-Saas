<?php

declare(strict_types=1);

namespace Aero\Contracts;

interface SmsGatewayInterface
{
    public function applySmsSettings(): void;

    /**
     * @return array{success: bool, message: string}
     */
    public function sendTestSms(string $phone): array;
}
