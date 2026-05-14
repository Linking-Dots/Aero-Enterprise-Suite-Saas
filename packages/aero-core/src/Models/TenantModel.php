<?php

namespace Aero\Core\Models;

/**
 * Backward-compatibility shim — TenantModel now lives in aero-contracts.
 * Both \Aero\Core\Models\TenantModel and \Aero\Contracts\Models\TenantModel
 * resolve to the same class hierarchy via this thin subclass.
 *
 * @see \Aero\Contracts\Models\TenantModel
 */
abstract class TenantModel extends \Aero\Contracts\Models\TenantModel {}
