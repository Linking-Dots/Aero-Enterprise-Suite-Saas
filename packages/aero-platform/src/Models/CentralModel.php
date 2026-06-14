<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

/**
 * BC shim: platform models extend \Aero\Platform\Models\CentralModel without a use statement,
 * so PHP resolves the class in this namespace. Delegates directly to the canonical
 * base in aero-contracts — NOT via aero-core, so platform carries no code edge to
 * its sibling (strict core ⟂ platform isolation, V6).
 */
abstract class CentralModel extends \Aero\Contracts\Models\CentralModel
{
}
