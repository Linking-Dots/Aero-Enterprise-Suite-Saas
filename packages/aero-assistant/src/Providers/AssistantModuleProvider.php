<?php

namespace Aero\Assistant\Providers;

use Aero\Assistant\Services\AiModelService;
use Aero\Assistant\Services\AssistantService;
use Aero\Assistant\Services\IndexingService;
use Aero\Assistant\Services\RagService;
use Aero\Contracts\Providers\AbstractModuleProvider;

/**
 * Assistant Module Provider
 *
 * Provides AI-powered assistant with RAG capabilities for user guidance,
 * task automation, and contextual help.
 */
class AssistantModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code.
     */
    protected string $moduleCode = 'assistant';

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register core AI services
        $this->app->singleton(AiModelService::class);
        $this->app->singleton(RagService::class);
        $this->app->singleton(AssistantService::class);
        $this->app->singleton(IndexingService::class);

        // Merge configuration
        $this->mergeConfigFrom(
            $this->getModulePath('config/assistant.php'),
            'assistant'
        );
    }

    /**
     * Boot assistant module.
     */
    protected function bootModule(): void
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Aero\Assistant\Console\Commands\IndexKnowledgeBase::class,
                \Aero\Assistant\Console\Commands\AssistantStats::class,
            ]);

            // Publish configuration
            $this->publishes([
                $this->getModulePath('config/assistant.php') => config_path('assistant.php'),
            ], 'assistant-config');
        }
    }

    /**
     * Register this module with the ModuleRegistry.
     */
    public function register(): void
    {
        parent::register();

        // Register with module registry if available
        if ($this->app->bound(\Aero\Core\Services\ModuleRegistry::class)) {
            $registry = $this->app->make(\Aero\Core\Services\ModuleRegistry::class);
            $registry->register($this);
        }
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        parent::boot();

        // Boot module-specific logic
        $this->bootModule();
    }
}
