<?php

namespace Aero\Compliance\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\UserRelationshipRegistry;
use Illuminate\Support\Facades\Gate;

/**
 * Compliance Module Provider
 *
 * Provides compliance management functionality including regulatory compliance,
 * audit trails, policy management, and compliance reporting.
 *
 * All module metadata is read from config/module.php (single source of truth).
 * This provider only contains module-specific services, policies, and relationships.
 */
class ComplianceModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code - the only required property.
     * All other metadata is read from config/module.php.
     */
    protected string $moduleCode = 'compliance';

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
     * Routes are registered by AeroComplianceServiceProvider with proper middleware.
     */
    protected function loadRoutes(): void
    {
        // Do nothing - routes handled by AeroComplianceServiceProvider
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register main Compliance service
        $this->app->singleton('compliance', function ($app) {
            return new \Aero\Compliance\Services\ComplianceService;
        });

        // Register specific services
        $this->app->singleton('compliance.requirements', function ($app) {
            return new \Aero\Compliance\Services\RequirementService;
        });

        $this->app->singleton('compliance.audits', function ($app) {
            return new \Aero\Compliance\Services\AuditService;
        });

        $this->app->singleton('compliance.policies', function ($app) {
            return new \Aero\Compliance\Services\PolicyService;
        });

        $this->app->singleton('compliance.reporting', function ($app) {
            return new \Aero\Compliance\Services\ReportingService;
        });

        // ========================================================================
        // PATENTABLE CORE IP SERVICES - HSE & Safety Automation
        // ========================================================================

        // Permit Validation Service - Automatic safety authorization enforcement
        $this->app->singleton(\Aero\Compliance\Services\PermitValidationService::class);

        // Merge Compliance-specific configuration
        $complianceConfigPath = $this->getModulePath('config/compliance.php');
        if (file_exists($complianceConfigPath)) {
            $this->mergeConfigFrom($complianceConfigPath, 'compliance');
        }
    }

    /**
     * Boot Compliance module.
     */
    protected function bootModule(): void
    {
        // Register policies
        $this->registerPolicies();

        // Register User model relationships dynamically
        $this->registerUserRelationships();

        // Register navigation items for auto-discovery
        $this->registerNavigation();

        // Publish module assets
        $this->publishes([
            $this->getModulePath('config/module.php') => config_path('modules/compliance.php'),
        ], 'compliance-config');
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

        // Register compliance requirements assigned to user
        $registry->registerRelationship('complianceRequirements', function ($user) {
            return $user->hasMany(\Aero\Compliance\Models\Requirement::class, 'assigned_to');
        });

        // Register audits where user is auditor
        $registry->registerRelationship('audits', function ($user) {
            return $user->hasMany(\Aero\Compliance\Models\Audit::class, 'auditor_id');
        });

        // Register policies authored by user
        $registry->registerRelationship('policies', function ($user) {
            return $user->hasMany(\Aero\Compliance\Models\Policy::class, 'author_id');
        });

        // Register scopes for user queries
        $registry->registerScope('withComplianceRelations', function ($query) {
            return $query->with([
                'complianceRequirements',
                'audits',
                'policies',
            ]);
        });

        // Register computed accessors
        $registry->registerAccessor('active_compliance_count', function ($user) {
            return $user->complianceRequirements()->where('status', 'active')->count();
        });

        $registry->registerAccessor('pending_audits_count', function ($user) {
            return $user->audits()->where('status', 'pending')->count();
        });
    }

    /**
     * Register policies for Compliance models.
     */
    protected function registerPolicies(): void
    {
        // Policies will be registered when models and policies are created
        $policies = [
            // \Aero\Compliance\Models\Requirement::class => \Aero\Compliance\Policies\RequirementPolicy::class,
            // \Aero\Compliance\Models\Audit::class => \Aero\Compliance\Policies\AuditPolicy::class,
            // \Aero\Compliance\Models\Policy::class => \Aero\Compliance\Policies\PolicyPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
}
