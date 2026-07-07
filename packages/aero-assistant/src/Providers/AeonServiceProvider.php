<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers;

use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Services\AeonService;
use Aero\Contracts\Ai\AiProvider;
use Aero\Contracts\Providers\AbstractModuleProvider;

class AeonServiceProvider extends AbstractModuleProvider
{
    protected string $moduleCode = 'aeon';

    protected function getModulePath(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);

        return $path ? $base.'/'.$path : $base;
    }

    protected function registerServices(): void
    {
        $this->mergeConfigFrom($this->getModulePath('config/aeon.php'), 'aeon');

        $this->app->singleton(AiProvider::class, function () {
            return match (config('aeon.provider', 'gemini')) {
                'gemini' => new GeminiProvider(),
                default  => new GeminiProvider(),
            };
        });

        $this->app->singleton(AeonService::class);
    }
}
