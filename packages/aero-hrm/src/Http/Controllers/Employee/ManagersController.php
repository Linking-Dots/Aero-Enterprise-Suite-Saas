<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Core\Models\User;
use Aero\HRM\Http\Controllers\Controller;

class ManagersController extends Controller
{
    /**
     * Get a list of all managers for dropdowns.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            // Get users with HRM employee access (managers typically have this)
            $managers = \Aero\HRMAC\Facades\HRMAC::getUsersWithSubModuleAccess('hrm', 'employees')
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])
                ->sortBy('name')
                ->values();

            return response()->json($managers);
        } catch (\Exception $e) {
            // Fallback to all active users
            return response()->json(
                User::active()
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
            );
        }
    }
}
