<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Fakes;

use Aero\Contracts\Ai\AeonQuotaContract;

/**
 * Configurable AI quota for enforcement tests. Records whether record() ran so a
 * test can assert a delivered message was (or wasn't) counted.
 */
class FakeAeonQuota implements AeonQuotaContract
{
    public int $recorded = 0;

    /** @param array<string,mixed> $status */
    public function __construct(private array $status = []) {}

    public function status(): array
    {
        return $this->status + [
            'enabled' => true, 'allowed' => true, 'used' => 0,
            'limit' => -1, 'remaining' => -1, 'model' => 'flash',
        ];
    }

    public function record(): void
    {
        $this->recorded++;
    }
}
