<?php

namespace Aero\Core\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

/**
 * Base Controller for Aero Core
 *
 * All Aero Core controllers should extend this class.
 * This makes the package independent of the host application's base controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * Resolve a safely-bounded `per_page` value from a paginated request.
     *
     * Phase 0 Task 10 of foundation 10/10 push — closes the unguarded
     * `?per_page=999999` DOS vector identified in the architecture audit.
     *
     * Use in index controllers:
     *
     *   $items = Employee::query()->paginate($this->boundedPerPage($request));
     *
     * @param int $default  per-page count when the request omits the parameter
     * @param int $max      hard upper bound (defaults to 100 — tune per resource)
     */
    protected function boundedPerPage(Request $request, int $default = 20, int $max = 100): int
    {
        return max(1, min((int) $request->input('per_page', $default), $max));
    }
}
