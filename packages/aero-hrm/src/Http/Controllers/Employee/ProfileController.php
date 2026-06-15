<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Core\Models\User;
use Aero\Core\Support\TenantCache;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRM Profile Controller
 *
 * Manages Employee profiles in the HRM module.
 *
 * Architecture:
 * - Employee model: Contains all HRM-specific fields (department, designation, gender, birthday, etc.)
 * - User model: Contains auth/identity fields only (name, email, phone, password)
 * - Employee belongsTo User via user_id
 *
 * This controller operates on the Employee model and updates User fields through the relationship.
 */
class ProfileController extends Controller
{
    /**
     * User fields that can be updated through the profile form.
     * These are auth/identity fields that live on the User model.
     */
    private const USER_FIELDS = [
        'name',
        'email',
        'phone',
    ];

    /**
     * Employee fields that can be updated through the profile form.
     * These are HRM-specific fields that live on the Employee model.
     */
    private const EMPLOYEE_FIELDS = [
        'department_id',
        'designation_id',
        'manager_id',
        'employee_code',
        'date_of_joining',
        'date_of_leaving',
        'employment_type',
        'status',
        'basic_salary',
        'work_location',
        'shift',
        'birthday',
        'gender',
        'nationality',
        'religion',
        'marital_status',
        'blood_group',
        'passport_no',
        'passport_exp_date',
        'employment_of_spouse',
        'number_of_children',
        'notes',
    ];

    /**
     * Field mappings from frontend names to model field names.
     */
    private const FIELD_MAPPINGS = [
        'department' => 'department_id',
        'designation' => 'designation_id',
        'report_to' => 'manager_id',
        'employee_id' => 'employee_code',
        'dob' => 'birthday',
    ];

    /**
     * Display the employee profile page.
     *
     * @param  User  $user  The user whose employee profile to display
     */
    public function index(Request $request, User $user): Response
    {
        // Get or create Employee record for this user
        $employee = $this->getOrCreateEmployee($user);

        // Load relationships
        $employee->load(['department', 'designation', 'manager.user', 'user']);

        // Get all employees for the "Reports To" dropdown
        $allEmployees = Employee::with('user:id,name,email')
            ->select('id', 'user_id', 'employee_code')
            ->get();

        return Inertia::render('HRM/UserProfile', [
            'title' => $user->name,
            'employee' => $this->formatEmployeeForFrontend($employee),
            'user' => $user,
            'allEmployees' => $allEmployees,
            'departments' => Department::all(),
            'designations' => Designation::all(),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the employee profile.
     *
     * Splits data between User (auth fields) and Employee (HRM fields) models.
     */
    public function update(Request $request): JsonResponse
    {
        // Validate the request
        $validated = $this->validateProfileUpdate($request);

        $user = User::findOrFail($request->id);
        $employee = $this->getOrCreateEmployee($user);

        DB::beginTransaction();

        try {
            $messages = [];

            // Split data between User and Employee models
            $userData = [];
            $employeeData = [];

            foreach ($validated as $key => $value) {
                if ($key === 'id' || $key === 'ruleSet') {
                    continue;
                }

                // Map frontend field names to model field names
                $mappedKey = self::FIELD_MAPPINGS[$key] ?? $key;

                if (in_array($key, self::USER_FIELDS) || in_array($mappedKey, self::USER_FIELDS)) {
                    $userData[$key] = $value;
                } elseif (in_array($mappedKey, self::EMPLOYEE_FIELDS)) {
                    $employeeData[$mappedKey] = $value;
                }
            }

            // Update User model (auth fields only)
            if (! empty($userData)) {
                $userMessages = $this->updateUserFields($user, $userData);
                $messages = array_merge($messages, $userMessages);
            }

            // Update Employee model (HRM fields)
            if (! empty($employeeData)) {
                $employeeMessages = $this->updateEmployeeFields($employee, $employeeData);
                $messages = array_merge($messages, $employeeMessages);
            }

            // Save both models
            $user->save();
            $employee->save();

            DB::commit();

            // Clear cache
            TenantCache::forget("profile_stats_{$user->id}");

            Log::info('Employee profile updated', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
                'updated_by' => Auth::id(),
                'user_fields' => array_keys($userData),
                'employee_fields' => array_keys($employeeData),
            ]);

            // Refresh data
            $employee->refresh();
            $employee->load(['department', 'designation', 'manager.user', 'user']);

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'employee' => $this->formatEmployeeForFrontend($employee),
                'user' => $user->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update employee profile', [
                'user_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update profile. '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate profile update request.
     */
    private function validateProfileUpdate(Request $request): array
    {
        $userId = $request->id;

        $rules = [
            'id' => 'required|integer|exists:users,id',
            'ruleSet' => 'nullable|string',

            // User fields (auth/identity)
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$userId}",
            'phone' => "nullable|string|unique:users,phone,{$userId}",

            // Employee fields (HRM-specific)
            'gender' => 'nullable|string|in:male,female,other,Male,Female,Other',
            'birthday' => 'nullable|date',
            'date_of_joining' => 'nullable|date',
            'address' => 'nullable|string',
            'employee_id' => 'nullable|string', // Maps to employee_code
            'department' => 'nullable|exists:departments,id', // Maps to department_id
            'designation' => 'nullable|exists:designations,id', // Maps to designation_id
            'report_to' => 'nullable|exists:employees,id', // Maps to manager_id
            'nationality' => 'nullable|string',
            'religion' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'passport_no' => 'nullable|string',
            'passport_exp_date' => 'nullable|date',
            'employment_type' => 'nullable|string',
            'work_location' => 'nullable|string',
        ];

        $messages = [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already in use.',
            'phone.unique' => 'This phone number is already in use.',
            'department.exists' => 'Selected department does not exist.',
            'designation.exists' => 'Selected designation does not exist.',
        ];

        return $request->validate($rules, $messages);
    }

    /**
     * Update User model fields.
     */
    private function updateUserFields(User $user, array $data): array
    {
        $messages = [];

        foreach ($data as $key => $value) {
            if ($value !== null && $user->{$key} !== $value) {
                $user->{$key} = $value;
                $messages[] = ucfirst(str_replace('_', ' ', $key)).' updated.';
            }
        }

        return $messages;
    }

    /**
     * Update Employee model fields.
     */
    private function updateEmployeeFields(Employee $employee, array $data): array
    {
        $messages = [];

        // Handle department change - may need to clear designation
        if (isset($data['department_id']) && $employee->department_id !== (int) $data['department_id']) {
            $oldDept = $employee->department_id;
            $employee->department_id = $data['department_id'];

            $dept = Department::find($data['department_id']);
            $messages[] = 'Department updated to '.($dept->name ?? 'Unknown').'.';

            // Optionally clear designation if department changes
            if ($oldDept !== null && ! isset($data['designation_id'])) {
                $employee->designation_id = null;
            }

            unset($data['department_id']);
        }

        // Handle designation change
        if (isset($data['designation_id'])) {
            if ($employee->designation_id !== (int) $data['designation_id']) {
                $employee->designation_id = $data['designation_id'] ?: null;

                if ($data['designation_id']) {
                    $desig = Designation::find($data['designation_id']);
                    $messages[] = 'Designation updated to '.($desig->title ?? 'Unknown').'.';
                } else {
                    $messages[] = 'Designation cleared.';
                }
            }
            unset($data['designation_id']);
        }

        // Handle manager change
        if (isset($data['manager_id'])) {
            if ($employee->manager_id !== (int) $data['manager_id']) {
                $employee->manager_id = $data['manager_id'] ?: null;

                if ($data['manager_id']) {
                    $manager = Employee::with('user')->find($data['manager_id']);
                    $messages[] = 'Reports to updated to '.($manager->user->name ?? 'Unknown').'.';
                } else {
                    $messages[] = 'Manager cleared.';
                }
            }
            unset($data['manager_id']);
        }

        // Handle remaining fields
        foreach ($data as $key => $value) {
            // Skip null values for optional fields
            if ($value === null || $value === '') {
                if ($employee->{$key} !== null) {
                    $employee->{$key} = null;
                }

                continue;
            }

            if ($employee->{$key} != $value) {
                $employee->{$key} = $value;
                $messages[] = $this->getFieldDisplayName($key).' updated.';
            }
        }

        return $messages;
    }

    /**
     * Get or create an Employee record for a User.
     */
    private function getOrCreateEmployee(User $user): Employee
    {
        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            $employee = Employee::create([
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            Log::info('Created Employee record for User', [
                'user_id' => $user->id,
                'employee_id' => $employee->id,
            ]);
        }

        return $employee;
    }

    /**
     * Format Employee data for frontend consumption.
     */
    private function formatEmployeeForFrontend(Employee $employee): array
    {
        $user = $employee->user;

        return [
            // Employee identifiers
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'employee_code' => $employee->employee_code,

            // User fields (auth/identity) - accessed through relationship
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_image_url' => $user->profile_image_url,
            'active' => ! $user->trashed(),

            // Employee personal info
            'gender' => $employee->gender,
            'birthday' => $employee->birthday?->format('Y-m-d'),
            'nationality' => $employee->nationality,
            'religion' => $employee->religion,
            'marital_status' => $employee->marital_status,
            'blood_group' => $employee->blood_group,
            'passport_no' => $employee->passport_no,
            'passport_exp_date' => $employee->passport_exp_date?->format('Y-m-d'),

            // Employment info
            'department_id' => $employee->department_id,
            'department' => $employee->department,
            'designation_id' => $employee->designation_id,
            'designation' => $employee->designation,
            'manager_id' => $employee->manager_id,
            'manager' => $employee->manager ? [
                'id' => $employee->manager->id,
                'name' => $employee->manager->user->name ?? 'Unknown',
            ] : null,
            'date_of_joining' => $employee->date_of_joining?->format('Y-m-d'),
            'date_of_leaving' => $employee->date_of_leaving?->format('Y-m-d'),
            'employment_type' => $employee->employment_type,
            'status' => $employee->status,
            'work_location' => $employee->work_location,
            'shift' => $employee->shift,

            // Timestamps
            'created_at' => $employee->created_at,
            'updated_at' => $employee->updated_at,
        ];
    }

    /**
     * Get human-readable field name.
     */
    private function getFieldDisplayName(string $key): string
    {
        $names = [
            'employee_code' => 'Employee ID',
            'department_id' => 'Department',
            'designation_id' => 'Designation',
            'manager_id' => 'Reports To',
            'date_of_joining' => 'Date of Joining',
            'date_of_leaving' => 'Date of Leaving',
            'employment_type' => 'Employment Type',
            'work_location' => 'Work Location',
            'birthday' => 'Birthday',
            'gender' => 'Gender',
            'nationality' => 'Nationality',
            'religion' => 'Religion',
            'marital_status' => 'Marital Status',
            'blood_group' => 'Blood Group',
            'passport_no' => 'Passport Number',
            'passport_exp_date' => 'Passport Expiry Date',
        ];

        return $names[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Get profile statistics for dashboard cards.
     */
    public function stats(Request $request, User $user): JsonResponse
    {
        try {
            $cacheKey = "profile_stats_{$user->id}";

            $stats = TenantCache::remember($cacheKey, 3600, function () use ($user) {
                $employee = Employee::where('user_id', $user->id)
                    ->with(['department', 'designation'])
                    ->first();

                if (! $employee) {
                    return [
                        'completion_percentage' => 0,
                        'total_sections' => 8,
                        'completed_sections' => 0,
                        'last_updated' => null,
                        'profile_views' => 0,
                        'sections_status' => [],
                    ];
                }

                // Calculate profile completion based on Employee model
                $sections = [
                    'basic_info' => $user->name && $user->email,
                    'contact_info' => $user->phone,
                    'personal_info' => $employee->birthday && $employee->gender,
                    'work_info' => $employee->department_id && $employee->designation_id,
                    'emergency_contact' => $user->emergencyContacts()->exists(),
                    'bank_info' => $user->bankDetail()->exists(),
                    'education' => $user->educations()->exists(),
                    'experience' => $user->experiences()->exists(),
                ];

                $completed = collect($sections)->filter()->count();
                $total = count($sections);

                // Get profile views (if tracking is implemented)
                $profile_views = DB::table('profile_views')
                    ->where('user_id', $user->id)
                    ->count() ?? 0;

                return [
                    'completion_percentage' => round(($completed / $total) * 100),
                    'total_sections' => $total,
                    'completed_sections' => $completed,
                    'last_updated' => $employee->updated_at,
                    'profile_views' => $profile_views,
                    'sections_status' => $sections,
                ];
            });

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export profile data.
     */
    public function export(Request $request, User $user): JsonResponse
    {
        try {
            $employee = Employee::where('user_id', $user->id)
                ->with(['department', 'designation', 'manager.user'])
                ->first();

            $exportData = [
                'basic_information' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'employee_code' => $employee?->employee_code,
                    'date_of_joining' => $employee?->date_of_joining?->format('Y-m-d'),
                ],
                'personal_information' => [
                    'birthday' => $employee?->birthday?->format('Y-m-d'),
                    'gender' => $employee?->gender,
                    'nationality' => $employee?->nationality,
                    'religion' => $employee?->religion,
                    'marital_status' => $employee?->marital_status,
                ],
                'work_information' => [
                    'department' => $employee?->department?->name,
                    'designation' => $employee?->designation?->title,
                    'reports_to' => $employee?->manager?->user?->name,
                    'employment_type' => $employee?->employment_type,
                    'work_location' => $employee?->work_location,
                ],
                'emergency_contacts' => $user->emergencyContacts->toArray(),
                'bank_information' => $user->bankDetail?->toArray(),
                'exported_at' => now()->toISOString(),
                'exported_by' => Auth::user()->name,
            ];

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'filename' => "profile_{$user->name}_".now()->format('Y-m-d_H-i-s').'.json',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export profile data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search profiles.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = Employee::with(['user', 'department', 'designation']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('employee_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('department')) {
                $query->where('department_id', $request->department);
            }

            if ($request->filled('designation')) {
                $query->where('designation_id', $request->designation);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            $perPage = $request->get('per_page', 15);
            $employees = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $employees->items(),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search profiles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track profile view.
     */
    public function trackView(Request $request, User $user): JsonResponse
    {
        try {
            if (Auth::id() !== $user->id) {
                DB::table('profile_views')->insert([
                    'user_id' => $user->id,
                    'viewer_id' => Auth::id(),
                    'viewed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                TenantCache::forget("profile_stats_{$user->id}");
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false], 200);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
