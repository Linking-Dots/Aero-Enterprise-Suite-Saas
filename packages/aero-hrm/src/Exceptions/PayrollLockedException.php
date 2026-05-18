<?php

declare(strict_types=1);

namespace Aero\HRM\Exceptions;

use DomainException;

/**
 * Thrown when a mutation is attempted on a locked (approved) payroll run or its payslips.
 */
final class PayrollLockedException extends DomainException {}
