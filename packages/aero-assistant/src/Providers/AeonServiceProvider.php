<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers;

use Aero\Assistant\Console\Commands\IndexKnowledge;
use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Services\IndexingService;
use Aero\Assistant\Services\RagService;
use Aero\Assistant\Tools\ToolRegistry;
use Aero\Assistant\Tools\UserStatsTool;
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

        $this->app->singleton(RagService::class);
        $this->app->singleton(IndexingService::class);

        // Data tools Aeon can call (feature packages tag their own too).
        $this->app->singleton(UserStatsTool::class);
        $this->app->tag([UserStatsTool::class], 'aeon.tools');
        $this->app->singleton(ToolRegistry::class, fn ($app) => new ToolRegistry($app->tagged('aeon.tools')));

        $this->app->singleton(AeonService::class);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([IndexKnowledge::class]);
        }
    }
}
