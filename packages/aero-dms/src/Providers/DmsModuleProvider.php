<?php

namespace Aero\Dms\Providers;

use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Services\DashboardRegistry;
use Aero\Core\Services\UserRelationshipRegistry;
use Aero\DMS\Models\Document;
use Aero\DMS\Models\Folder;
use Aero\DMS\Policies\DocumentPolicy;
use Aero\DMS\Policies\FolderPolicy;
use Aero\DMS\Services\DigitalSignatureService;
use Aero\DMS\Services\DMSService;
use Aero\DMS\Services\DocumentApprovalService;
use Aero\DMS\Services\DocumentSearchService;
use Aero\DMS\Services\DocumentService;
use Aero\DMS\Services\DocumentVersioningService;
use Illuminate\Support\Facades\Gate;

/**
 * DMS Module Provider
 *
 * Provides Document Management System functionality including document storage,
 * version control, approval workflows, and secure document sharing.
 *
 * All module metadata is read from config/module.php (single source of truth).
 * This provider only contains module-specific services, policies, and relationships.
 */
class DmsModuleProvider extends AbstractModuleProvider
{
    /**
     * Module code - the only required property.
     * All other metadata is read from config/module.php.
     */
    protected string $moduleCode = 'dms';

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
        // Register main DMS service
        $this->app->singleton('dms', function ($app) {
            return new DMSService;
        });

        // Register specific services
        $this->app->singleton('dms.documents', function ($app) {
            return new DocumentService;
        });

        $this->app->singleton('dms.versioning', function ($app) {
            return new DocumentVersioningService;
        });

        $this->app->singleton('dms.approval', function ($app) {
            return new DocumentApprovalService;
        });

        $this->app->singleton('dms.search', function ($app) {
            return new DocumentSearchService;
        });

        $this->app->singleton('dms.signature', function ($app) {
            return new DigitalSignatureService;
        });

        // Merge DMS-specific configuration
        $dmsConfigPath = $this->getModulePath('config/dms.php');
        if (file_exists($dmsConfigPath)) {
            $this->mergeConfigFrom($dmsConfigPath, 'dms');
        }
    }

    /**
     * Boot DMS module.
     */
    protected function bootModule(): void
    {
        // Register policies
        $this->registerPolicies();

        // Register User model relationships dynamically
        $this->registerUserRelationships();

        // Register navigation items for auto-discovery
        $this->registerNavigation();

        // Register dashboards
        $this->registerDashboards();

        // Publish module assets
        $this->publishes([
            $this->getModulePath('config/module.php') => config_path('modules/dms.php'),
        ], 'dms-config');
    }

    /**
     * Register DMS dashboards with DashboardRegistry.
     */
    protected function registerDashboards(): void
    {
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->register(
            'dms.dashboard',
            'DMS Dashboard',
            'dms',
            'Document management analytics and approvals',
            'DocumentTextIcon',
            'dms.dashboard.view'
        );
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

        // Register document ownership relationship
        $registry->registerRelationship('documents', function ($user) {
            return $user->hasMany(Document::class, 'created_by');
        });

        // Register folder ownership relationship
        $registry->registerRelationship('folders', function ($user) {
            return $user->hasMany(Folder::class, 'created_by');
        });

        // Register shared documents relationship
        $registry->registerRelationship('sharedDocuments', function ($user) {
            return $user->belongsToMany(Document::class, 'document_shares', 'user_id', 'document_id')
                ->withTimestamps()
                ->withPivot(['permission_level', 'shared_by', 'expires_at']);
        });

        // Register scopes for user queries
        $registry->registerScope('withDmsRelations', function ($query) {
            return $query->with([
                'documents',
                'folders',
                'sharedDocuments',
            ]);
        });

        // Register computed accessors
        $registry->registerAccessor('documents_count', function ($user) {
            return $user->documents()->count();
        });

        $registry->registerAccessor('folders_count', function ($user) {
            return $user->folders()->count();
        });

        $registry->registerAccessor('storage_used', function ($user) {
            return $user->documents()->sum('file_size');
        });
    }

    /**
     * Register policies for DMS models.
     */
    protected function registerPolicies(): void
    {
        $policies = [
            Document::class => DocumentPolicy::class,
            Folder::class => FolderPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
}
