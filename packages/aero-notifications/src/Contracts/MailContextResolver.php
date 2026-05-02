<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

/**
 * Resolves mail configuration at runtime without tying the notification engine
 * to specific setting models (SystemSetting, PlatformSetting, .env, etc.).
 */
interface MailContextResolver
{
    /**
     * Resolve current mail configuration array.
     *
     * @return array{
     *     configured: bool,
     *     driver: string,
     *     host: string,
     *     port: int,
     *     username: string,
     *     password: string,
     *     encryption: string,
     *     verify_peer: bool,
     *     from_address: string,
     *     from_name: string,
     * }
     */
    public function resolve(): array;
}
