<?php

declare(strict_types=1);

namespace Aero\Notifications\Contracts;

/**
 * Resolve branding data (company name, logo, colors) for notification templates.
 * Downstream packages bind their own implementation.
 */
interface BrandingResolver
{
    /**
     * @return array{
     *     company_name: string,
     *     logo_url: ?string,
     *     primary_color: string,
     *     support_email: string,
     *     support_phone: string,
     * }
     */
    public function resolve(): array;
}
