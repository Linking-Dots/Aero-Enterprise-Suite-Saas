<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

/**
 * BC shim: platform models extend \Aero\Platform\Models\CentralModel without a use statement,
 * so PHP resolves the class in this namespace. Delegate to the canonical aero-core shim.
 */
abstract class CentralModel extends \Aero\Core\Models\CentralModel
{
}
