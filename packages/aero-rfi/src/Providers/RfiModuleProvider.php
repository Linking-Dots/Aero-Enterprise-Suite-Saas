<?php

namespace Aero\Rfi\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\UserRelationshipRegistry;
use Aero\Rfi\Events\RfiApproved;
use Aero\Rfi\Events\RfiRejected;
use Aero\Rfi\Events\RfiSubmitted;
use Aero\Rfi\Models\Objection;
use Aero\Rfi\Models\Rfi;
use Aero\Rfi\Models\WorkLocation;
use Aero\Rfi\Policies\ObjectionPolicy;
use Aero\Rfi\Policies\RfiPolicy;
use Aero\Rfi\Policies\WorkLocationPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

/**
 * RFI Module Provider
 *
 * Provides RFI (Request for Inspection) management functionality including
 * daily works, objections, and work locations.
 *
 * All module metadata is read from config/module.php (single source of truth).
 * This provider only contains module-specific services, policies, and relationships.
 */
class RfiModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code - the only required property.
     * All other metadata is read from config/module.php.
     */
    protected string $moduleCode = 'rfi';

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Override parent loadRoutes to prevent duplicate route registration.
     * Routes are registered by AeroRfiServiceProvider with proper middleware.
     */
    protected function loadRoutes(): void
    {
        // Intentionally no-op.
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register main RFI service
        $this->app->singleton('rfi', function ($app) {
            return new \Aero\Rfi\Services\RfiService;
        });

        // Register specific services
        $this->app->singleton('rfi.service', function ($app) {
            return new \Aero\Rfi\Services\RfiService;
        });

        $this->app->singleton('rfi.objection', function ($app) {
            return new \Aero\Rfi\Services\ObjectionService;
        });

        // Register RFI specialized services
        $this->app->singleton(\Aero\Rfi\Services\RfiPaginationService::class);
        $this->app->singleton(\Aero\Rfi\Services\RfiImportService::class);
        $this->app->singleton(\Aero\Rfi\Services\RfiCrudService::class);
        $this->app->singleton(\Aero\Rfi\Services\RfiFileService::class);
        $this->app->singleton(\Aero\Rfi\Services\RfiValidationService::class);
        $this->app->singleton(\Aero\Rfi\Services\RfiSummaryService::class);

        // Register Chainage Gap Analysis Service (PATENTABLE)
        $this->app->singleton(\Aero\Rfi\Services\ChainageGapAnalysisService::class);

        // ========================================================================
        // PATENTABLE CORE IP SERVICES - Construction Tech SaaS Innovation
        // ========================================================================

        // GPS Validation Service - Anti-fraud location verification
        $this->app->singleton(\Aero\Rfi\Services\GeoFencingService::class);

        // Layer Continuity Validator - Sequential construction enforcement (CORE IP)
        $this->app->singleton(\Aero\Rfi\Services\LinearContinuityValidator::class);

        // Weather Validation Service - Environmental constraints checking
        $this->app->singleton(\Aero\Quality\Services\WeatherValidationService::class, function ($app) {
            return new \Aero\Quality\Services\WeatherValidationService;
        });

        // Merge RFI-specific configuration
        $rfiConfigPath = $this->getModulePath('config/rfi.php');
        if (file_exists($rfiConfigPath)) {
            $this->mergeConfigFrom($rfiConfigPath, 'rfi');
        }
    }

    /**
     * Boot RFI module.
     */
    protected function bootModule(): void
    {
        // Register policies
        $this->registerPolicies();

        // Register User model relationships dynamically
        $this->registerUserRelationships();

        // Register navigation items for auto-discovery
        $this->registerNavigation();

        // Register event listeners for RFI workflow (PATENTABLE integration)
        $this->registerEventListeners();
    }

    /**
     * Register User model relationships via UserRelationshipRegistry.
     * This allows the core User model to be extended without hard dependencies.
     */
    protected function registerUserRelationships(): void
    {
        if (! $this->app->bound(UserRelationshipRegistry::class)) {
            return;
        }

        $registry = $this->app->make(UserRelationshipRegistry::class);

        // Register RFIs where user is incharge
        $registry->registerRelationship('rfisAsIncharge', function ($user) {
            return $user->hasMany(Rfi::class, 'incharge_user_id');
        });

        // Register RFIs where user is assigned
        $registry->registerRelationship('rfisAsAssigned', function ($user) {
            return $user->hasMany(Rfi::class, 'assigned_user_id');
        });

        // Register objections created by user
        $registry->registerRelationship('objections', function ($user) {
            return $user->hasMany(Objection::class, 'created_by');
        });

        // Register work locations where user is incharge
        $registry->registerRelationship('workLocations', function ($user) {
            return $user->hasMany(WorkLocation::class, 'incharge_user_id');
        });

        // Register scopes for user queries
        $registry->registerScope('withRfiRelations', function ($query) {
            return $query->with([
                'rfisAsIncharge',
                'rfisAsAssigned',
                'workLocations',
            ]);
        });

        // Register computed accessors
        $registry->registerAccessor('rfis_count', function ($user) {
            return $user->rfisAsIncharge()->count() + $user->rfisAsAssigned()->count();
        });

        $registry->registerAccessor('active_objections_count', function ($user) {
            return $user->objections()->whereIn('status', ['draft', 'submitted', 'under_review'])->count();
        });
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        $policies = [
            Rfi::class => RfiPolicy::class,
            Objection::class => ObjectionPolicy::class,
            WorkLocation::class => WorkLocationPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Register event listeners for RFI workflow.
     * These listeners enable the PATENTABLE integration between modules.
     */
    protected function registerEventListeners(): void
    {
        // RfiApproved -> Auto-generate BOQ Measurement
        Event::listen(
            RfiApproved::class,
            [\Aero\Project\Listeners\GenerateBoqMeasurementOnApproval::class, 'handle']
        );

        // RfiRejected -> Auto-create NCR if severe
        Event::listen(
            RfiRejected::class,
            [\Aero\Quality\Listeners\CreateNcrOnRfiRejection::class, 'handle']
        );

        // RfiSubmitted -> Create ChainageProgress record
        Event::listen(
            RfiSubmitted::class,
            function (RfiSubmitted $event) {
                if ($event->workLayerId) {
                    $service = app(\Aero\Rfi\Services\ChainageGapAnalysisService::class);
                    $service->recordRfiSubmission($event->rfi, $event->workLayerId);
                }
            }
        );
    }
}

