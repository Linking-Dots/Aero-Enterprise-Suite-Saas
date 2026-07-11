<?php

declare(strict_types=1);

namespace Aero\Assistant\Providers;

use Aero\Assistant\Console\Commands\IndexKnowledge;
use Aero\Assistant\Providers\Models\GeminiProvider;
use Aero\Assistant\Providers\Models\OpenAiCompatProvider;
use Aero\Assistant\Services\AeonService;
use Aero\Assistant\Services\IndexingService;
use Aero\Assistant\Services\RagService;
use Aero\Assistant\Data\QueryTool;
use Aero\Assistant\Data\SchemaCatalog;
use Aero\Assistant\Operations\FormSpecBuilder;
use Aero\Assistant\Operations\OperationResolver;
use Aero\Assistant\Operations\RulesIntrospector;
use Aero\Assistant\Tools\PrepareOperationTool;
use Aero\Assistant\Tools\ToolRegistry;
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
            // Provider chosen by the central control plane when available.
            $provider = \Aero\Assistant\Support\AeonConfig::resolve()['provider'] ?? config('aeon.provider', 'gemini');

            return match ($provider) {
                'openai' => new OpenAiCompatProvider(),
                default  => new GeminiProvider(),
            };
        });

        $this->app->singleton(RagService::class);
        $this->app->singleton(IndexingService::class);

        // Dynamic, schema-aware data access: one generic tool queries ANY table
        // (validated against the live schema). Feature packages can still tag
        // their own specialised 'aeon.tools' for curated answers.
        $this->app->singleton(SchemaCatalog::class);
        $this->app->singleton(QueryTool::class);

        // The generic operation engine: introspects the app's own routes +
        // validation to build a pre-filled form Aeon posts to the REAL endpoint
        // (validation + HRMAC + audit all run on submit). One tool = any write.
        $this->app->singleton(OperationResolver::class);
        $this->app->singleton(RulesIntrospector::class);
        $this->app->singleton(FormSpecBuilder::class);
        $this->app->singleton(PrepareOperationTool::class);

        $this->app->tag([QueryTool::class, PrepareOperationTool::class], 'aeon.tools');
        $this->app->singleton(ToolRegistry::class, fn ($app) => new ToolRegistry($app->tagged('aeon.tools')));

        $this->app->singleton(AeonService::class);
    }

    protected function bootModule(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([IndexKnowledge::class]);
        }

        // Zero-operator-work KB currency: a daily checksum-guarded reindex picks
        // up structure changes (new modules/tables/docs after a deploy). Unchanged
        // chunks are skipped, so a no-change night costs zero embedding calls.
        // Live DATA needs no reindex — query_data always reads the live tables.
        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function ($schedule) {
            if (! config('aeon.enabled', true)) {
                return;
            }
            if (class_exists(\Stancl\Tenancy\Tenancy::class)) {
                $schedule->command('tenants:run aeon:index')->dailyAt('04:10'); // SaaS: per tenant DB
            } else {
                $schedule->command('aeon:index')->dailyAt('04:10'); // standalone: single DB
            }
        });
    }
}
