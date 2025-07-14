<?php

namespace App\Http\Controllers;

use App\Models\HRM\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     * 
     * @return \Inertia\Response
     */
    public function index(Request $request): \Inertia\Response
    {
        $query = Department::with(['parent', 'manager', 'children']);
        
        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Get departments with pagination
        $departments = $query->paginate(10);
        
        // Get managers for dropdown
        $managers = User::whereHas('roles', function($q) {
            $q->where('name', 'like', '%Manager%')
              ->orWhere('name', 'like', '%Director%')
              ->orWhere('name', 'like', '%Head%');
        })->get(['id', 'name']);
        
        // Get parent departments for dropdown
        $parentDepartments = Department::whereNull('parent_id')
                            ->orWhere('parent_id', 0)
                            ->get(['id', 'name']);
        
        // Department statistics
        $stats = [
            'total' => Department::count(),
            'active' => Department::where('is_active', true)->count(),
            'inactive' => Department::where('is_active', false)->count(),
            'parent_departments' => Department::whereNull('parent_id')->orWhere('parent_id', 0)->count(),
        ];
        
        return Inertia::render('Departments', [
            'title' => 'Department Management',
            'departments' => $departments,
            'managers' => $managers,
            'parentDepartments' => $parentDepartments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
    
    /**
     * Store a newly created department
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:departments',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'established_date' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Create new department
        $department = Department::create($request->all());
        
        return response()->json([
            'message' => 'Department created successfully',
            'department' => $department
        ], 201);
    }
    
    /**
     * Display the specified department
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $department = Department::with(['parent', 'manager', 'children'])
                     ->findOrFail($id);
                     
        return response()->json($department);
    }
    
    /**
     * Update the specified department
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => [
                'nullable', 
                'string', 
                'max:50',
                Rule::unique('departments')->ignore($id)
            ],
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'established_date' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Update department
        $department->update($request->all());
        
        return response()->json([
            'message' => 'Department updated successfully',
            'department' => $department
        ]);
    }
    
    /**
     * Remove the specified department (soft delete)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        
        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete department with active employees',
                'errors' => ['department' => 'Department has active employees. Reassign them before deleting.']
            ], 422);
        }
        
        // Soft delete the department
        $department->delete();
        
        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }

    /**
     * Update a user's department
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserDepartment(Request $request, $id)
    {
        try {
            $request->validate([
                'department' => 'required|integer|exists:departments,id',
            ]);

            $user = User::findOrFail($id);

            // Update the user's department
            $departmentChanged = $user->department_id !== $request->input('department');
            $user->department_id = $request->input('department');

            // If department changed, reset designation
            if ($departmentChanged) {
                $user->designation = null;
            }

            $user->save();

            return response()->json(['messages' => ['Department updated successfully']], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['errors' => ['User not found']], 404);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['An unexpected error occurred. Please try again later.']], 500);
        }
    }

    /**
     * Get department statistics
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => Department::count(),
            'active' => Department::where('is_active', true)->count(),
            'inactive' => Department::where('is_active', false)->count(),
            'parent_departments' => Department::whereNull('parent_id')->orWhere('parent_id', 0)->count(),
        ];
        
        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Get departments data for API requests
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDepartments(Request $request)
    {
        $query = Department::with(['parent', 'manager', 'children']);
        
        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Apply parent department filter if provided
        if ($request->has('parent_department') && $request->parent_department !== 'all') {
            if ($request->parent_department === 'none') {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            } else {
                $query->where('parent_id', $request->parent_department);
            }
        }
        
        // Get departments with pagination
        $departments = $query->paginate($request->input('per_page', 10));
        
        return response()->json([
            'departments' => $departments
        ]);
    }
}
