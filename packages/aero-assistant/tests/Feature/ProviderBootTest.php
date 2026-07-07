<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Feature;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Tests\PackageTestCase;
use Aero\Contracts\Ai\AiProvider;

class ProviderBootTest extends PackageTestCase
{
    public function test_container_resolves_configured_ai_provider(): void
    {
        $provider = $this->app->make(AiProvider::class);
        $this->assertInstanceOf(GeminiProvider::class, $provider);
    }
}
