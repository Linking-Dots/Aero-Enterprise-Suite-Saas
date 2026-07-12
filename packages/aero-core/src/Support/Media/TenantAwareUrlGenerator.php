<?php

declare(strict_types=1);

namespace Aero\Core\Support\Media;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

/**
 * Media URLs that survive multi-tenancy.
 *
 * With Stancl's FilesystemTenancyBootstrapper the public disk root is
 * tenant-suffixed (storage/tenant{id}/app/public) but the disk's `url`
 * config still points at the central /storage symlink — so Spatie's
 * default generator produces URLs that 404/403 for every tenant upload.
 * When tenancy is initialized we route through tenant_asset() instead
 * (stancl.tenancy.asset serves from the suffixed storage). Central and
 * standalone contexts fall through to the default behaviour.
 */
class TenantAwareUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        if (function_exists('tenancy') && tenancy()->initialized && function_exists('tenant_asset')) {
            return tenant_asset($this->getPathRelativeToRoot());
        }

        return parent::getUrl();
    }
}
