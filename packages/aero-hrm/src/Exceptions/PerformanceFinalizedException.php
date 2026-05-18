<?php

declare(strict_types=1);

namespace Aero\HRM\Exceptions;

class PerformanceFinalizedException extends \DomainException
{
    public function __construct(string $message = 'This performance record has been finalized and cannot be modified.')
    {
        parent::__construct($message);
    }
}
