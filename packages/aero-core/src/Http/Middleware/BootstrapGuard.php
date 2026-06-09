<?php

namespace Aero\Core\Http\Middleware;

use Aero\Installation\Http\Middleware\BootstrapGuard as InstallationBootstrapGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BootstrapGuard extends InstallationBootstrapGuard
{
}
