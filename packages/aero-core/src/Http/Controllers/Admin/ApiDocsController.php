<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\ModuleRegistry;
use Inertia\Inertia;
use Inertia\Response;

class ApiDocsController extends Controller
{
    public function __construct(private ModuleRegistry $moduleRegistry) {}

    public function index(): Response
    {
        return Inertia::render('Core/Api/Docs', [
            'api_version'  => config('app.version', '1.0.0'),
            'base_url'     => url('/api'),
            'modules'      => $this->moduleRegistry->getActiveModules(),
            'openapi_url'  => url('/api/openapi.json'),
        ]);
    }
}
