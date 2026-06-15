<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Core\Models\User;
use Aero\HRM\Events\Employee\EmployeeResigned;
use Aero\HRM\Events\Offboarding\OffboardingCompleted;
use Aero\HRM\Events\Offboarding\OffboardingStarted;
use Aero\HRM\Events\Onboarding\OnboardingCompleted;
use Aero\HRM\Events\Onboarding\OnboardingStarted;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\HR\StoreOffboardingRequest;
use Aero\HRM\Http\Requests\HR\StoreOnboardingRequest;
use Aero\HRM\Http\Requests\HR\UpdateOffboardingRequest;
use Aero\HRM\Http\Requests\HR\UpdateOnboardingRequest;
use Aero\HRM\Models\Checklist;
use Aero\HRM\Models\Offboarding;
use Aero\HRM\Models\OffboardingStep;
use Aero\HRM\Models\OffboardingTask;
use Aero\HRM\Models\Onboarding;
use Aero\HRM\Models\OnboardingStep;
use Aero\HRM\Models\OnboardingTask;
use App\Http\Controllers\Tenant\HRM\Employee\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    /**
     * Display a listing of onboarding processes.
     */
    public function index()
    {
        $this->authorize('viewAny', Onboarding::class);

        $onboardings = Onboarding::with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('HRM/Onboarding/Index', [
            'title' => 'Employee Onboarding',
            'onboardings' => $onboardings,
        ]);
    }

    /**
     * Show the form for creating a new onboarding process.
     */
    public function create()
    {
        $this->authorize('create', Onboarding::class);

        $employees = User::select('id', 'name')
            ->whereNull('deleted_at') // replaced status check
            ->orderBy('name')
            ->get();

        return Inertia::render('HRM/Onboarding/Create', [
            'title' => 'Create Onboarding Process',
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created onboarding process.
     */
    public function store(StoreOnboardingRequest $request)
    {
        $this->authorize('create', Onboarding::class);

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $onboarding = Onboarding::create([
                'employee_id' => $validated['employee_id'],
                'start_date' => $validated['start_date'],
                'expected_completion_date' => $validated['expected_completion_date'],
                'status' => Onboarding::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['tasks'] as $taskData) {
                OnboardingTask::create([
                    'onboarding_id' => $onboarding->id,
                    'task' => $taskData['task'],
                    'description' => $taskData['description'] ?? null,
                    'due_date' => $taskData['due_date'] ?? null,
                    'assigned_to' => $taskData['assigned_to'] ?? null,
                    'status' => OnboardingTask::STATUS_PENDING,
                ]);
            }

            // Dispatch OnboardingStarted event
            event(new OnboardingStarted($onboarding, auth()->id()));

            DB::commit();

            return redirect()->route('hr.onboarding.show', $onboarding->id)
                ->with('success', 'Onboarding process created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create onboarding process', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create onboarding process. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified onboarding process.
     */
    public function show($id)
    {
        $onboarding = Onboarding::with(['employee', 'tasks.assignee'])
            ->findOrFail($id);

        $this->authorize('view', $onboarding);

        return Inertia::render('HRM/Onboarding/Show', [
            'title' => 'Onboarding Details',
            'onboarding' => $onboarding,
        ]);
    }

    /**
     * Show the form for editing the specified onboarding process.
     */
    public function edit($id)
    {
        $onboarding = Onboarding::with(['employee', 'tasks'])
            ->findOrFail($id);

        $this->authorize('update', $onboarding);

        $employees = User::select('id', 'name')
            ->orderBy('name')
            ->get();

        $assignees = User::select('id', 'name')
            ->whereNull('deleted_at') // replaced status check
            ->orderBy('name')
            ->get();

        return Inertia::render('HRM/Onboarding/Edit', [
            'title' => 'Edit Onboarding Process',
            'onboarding' => $onboarding,
            'employees' => $employees,
            'assignees' => $assignees,
        ]);
    }

    /**
     * Update the specified onboarding process.
     */
    public function update(UpdateOnboardingRequest $request, $id)
    {
        $onboarding = Onboarding::with('tasks')->findOrFail($id);

        $this->authorize('update', $onboarding);

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $onboarding->update([
                'start_date' => $validated['start_date'],
                'expected_completion_date' => $validated['expected_completion_date'],
                'actual_completion_date' => $validated['actual_completion_date'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $existingTaskIds = $onboarding->tasks->pluck('id')->toArray();
            $updatedTaskIds = [];

            foreach ($validated['tasks'] as $taskData) {
                if (! empty($taskData['id'])) {
                    $task = OnboardingTask::findOrFail($taskData['id']);
                    $task->update([
                        'task' => $taskData['task'],
                        'description' => $taskData['description'] ?? null,
                        'due_date' => $taskData['due_date'] ?? null,
                        'completed_date' => $taskData['completed_date'] ?? null,
                        'status' => $taskData['status'],
                        'assigned_to' => $taskData['assigned_to'] ?? null,
                        'notes' => $taskData['notes'] ?? null,
                    ]);
                    $updatedTaskIds[] = $task->id;
                } else {
                    $task = OnboardingTask::create([
                        'onboarding_id' => $onboarding->id,
                        'task' => $taskData['task'],
                        'description' => $taskData['description'] ?? null,
                        'due_date' => $taskData['due_date'] ?? null,
                        'completed_date' => $taskData['completed_date'] ?? null,
                        'status' => $taskData['status'],
                        'assigned_to' => $taskData['assigned_to'] ?? null,
                        'notes' => $taskData['notes'] ?? null,
                    ]);
                    $updatedTaskIds[] = $task->id;
                }
            }

            $tasksToDelete = array_diff($existingTaskIds, $updatedTaskIds);
            if ($tasksToDelete) {
                OnboardingTask::whereIn('id', $tasksToDelete)->delete();
            }

            DB::commit();

            return redirect()->route('hr.onboarding.show', $onboarding->id)
                ->with('success', 'Onboarding process updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update onboarding process', ['error' => $e->getMessage(), 'onboarding_id' => $onboarding->id]);

            return redirect()->back()
                ->with('error', 'Failed to update onboarding process. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified onboarding process.
     */
    public function destroy($id)
    {
        $onboarding = Onboarding::findOrFail($id);

        $this->authorize('delete', $onboarding);

        try {
            $onboarding->delete();

            return redirect()->route('hr.onboarding.index')
                ->with('success', 'Onboarding process deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete onboarding process: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to delete onboarding process.');
        }
    }

    /**
     * Display the wizard onboarding interface for an employee.
     */
    public function wizard($employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        $departments = \App\Models\Department::select('id', 'name')
            ->orderBy('name')
            ->get();

        $designations = \App\Models\Designation::select('id', 'title')
            ->orderBy('title')
            ->get();

        $attendanceTypes = \App\Models\AttendanceType::select('id', 'name')
            ->orderBy('name')
            ->get();

        $managers = User::select('id', 'name')
            ->where('id', '!=', $employeeId)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('HRM/Onboarding/Wizard', [
            'title' => 'Employee Onboarding Wizard',
            'employee' => $employee,
            'departments' => $departments,
            'designations' => $designations,
            'attendanceTypes' => $attendanceTypes,
            'managers' => $managers,
        ]);
    }

    /**
     * Save personal information step.
     */
    public function savePersonal(Request $request, $employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$employee->id,
            'phone' => 'required|string|max:20',
            'birthday' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_relationship' => 'required|string|max:100',
        ]);

        $employee->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'birthday' => $validated['birthday'],
            'gender' => $validated['gender'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code'],
            'country' => $validated['country'],
            'emergency_contact_primary_name' => $validated['emergency_contact_name'],
            'emergency_contact_primary_phone' => $validated['emergency_contact_phone'],
            'emergency_contact_primary_relationship' => $validated['emergency_contact_relationship'],
        ]);

        return response()->json(['message' => 'Personal information saved successfully']);
    }

    /**
     * Save job details step.
     */
    public function saveJob(Request $request, $employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        $validated = $request->validate([
            'employee_id' => 'required|string|unique:users,employee_id,'.$employee->id,
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'attendance_type_id' => 'nullable|exists:attendance_types,id',
            'hire_date' => 'required|date',
            'reports_to' => 'nullable|exists:users,id',
            'work_location' => 'nullable|string|max:255',
            'employment_type' => 'required|in:full-time,part-time,contract,intern',
            'probation_period' => 'nullable|integer|min:0|max:12',
        ]);

        $employee->update([
            'employee_id' => $validated['employee_id'],
            'department_id' => $validated['department_id'],
            'designation_id' => $validated['designation_id'],
            'attendance_type_id' => $validated['attendance_type_id'],
            'hire_date' => $validated['hire_date'],
            'reporting_manager_id' => $validated['reports_to'],
            'work_location' => $validated['work_location'],
            'employment_type' => $validated['employment_type'],
            'probation_period' => $validated['probation_period'],
        ]);

        return response()->json(['message' => 'Job details saved successfully']);
    }

    /**
     * Save documents step.
     */
    public function saveDocuments(Request $request, $employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        $validated = $request->validate([
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'id_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'address_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'education_certificates.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'experience_letters.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'passport_no' => 'nullable|string|max:50',
            'passport_exp_date' => 'nullable|date',
            'nid' => 'nullable|string|max:50',
        ]);

        // Handle file uploads
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('onboarding/documents/'.$employee->id, 'public');
            $employee->update(['resume_path' => $resumePath]);
        }

        if ($request->hasFile('id_proof')) {
            $idProofPath = $request->file('id_proof')->store('onboarding/documents/'.$employee->id, 'public');
            $employee->update(['id_proof_path' => $idProofPath]);
        }

        if ($request->hasFile('address_proof')) {
            $addressProofPath = $request->file('address_proof')->store('onboarding/documents/'.$employee->id, 'public');
            $employee->update(['address_proof_path' => $addressProofPath]);
        }

        $employee->update([
            'passport_no' => $validated['passport_no'],
            'passport_exp_date' => $validated['passport_exp_date'],
            'nid' => $validated['nid'],
        ]);

        return response()->json(['message' => 'Documents saved successfully']);
    }

    /**
     * Save bank details step.
     */
    public function saveBank(Request $request, $employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_account_no' => 'required|string|max:50',
            'bank_account_name' => 'required|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_routing_no' => 'nullable|string|max:50',
            'bank_swift_code' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:50',
        ]);

        $employee->update($validated);

        return response()->json(['message' => 'Bank details saved successfully']);
    }

    /**
     * Complete the onboarding process.
     */
    public function complete($employeeId)
    {
        $employee = User::findOrFail($employeeId);

        $this->authorize('create', Onboarding::class);

        DB::beginTransaction();

        try {
            // Create onboarding record
            $onboarding = Onboarding::create([
                'employee_id' => $employee->id,
                'start_date' => now(),
                'expected_completion_date' => now()->addDays(30),
                'status' => Onboarding::STATUS_IN_PROGRESS,
                'notes' => 'Created via onboarding wizard',
            ]);

            // Create default onboarding tasks
            $defaultTasks = [
                ['task' => 'Complete HR documentation', 'due_date' => now()->addDays(3)],
                ['task' => 'IT equipment setup', 'due_date' => now()->addDays(1)],
                ['task' => 'Office tour and introductions', 'due_date' => now()->addDays(2)],
                ['task' => 'Review company policies', 'due_date' => now()->addDays(5)],
                ['task' => 'Set up email and accounts', 'due_date' => now()->addDays(1)],
            ];

            foreach ($defaultTasks as $task) {
                OnboardingTask::create([
                    'onboarding_id' => $onboarding->id,
                    'task' => $task['task'],
                    'due_date' => $task['due_date'],
                    'status' => OnboardingTask::STATUS_PENDING,
                ]);
            }

            // Mark employee as active (active = not soft-deleted; restore if needed)
            if ($employee->trashed()) {
                $employee->restore();
            }

            // Mark onboarding as completed
            $onboarding->update(['status' => Onboarding::STATUS_COMPLETED]);

            // Dispatch OnboardingCompleted event
            $daysTaken = $onboarding->start_date->diffInDays(now());
            event(new OnboardingCompleted($onboarding, now(), $daysTaken));

            DB::commit();

            return response()->json([
                'message' => 'Onboarding completed successfully',
                'redirect' => route('hr.onboarding.show', $onboarding->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete onboarding', ['error' => $e->getMessage(), 'employee_id' => $employeeId]);

            return response()->json(['message' => 'Failed to complete onboarding'], 500);
        }
    }

    /**
     * Display a listing of offboarding processes.
     */
    public function offboardingIndex()
    {
        $this->authorize('viewAny', Offboarding::class);

        $offboardings = Offboarding::with('employee')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('HRM/Offboarding/Index', [
            'title' => 'Employee Offboarding',
            'offboardings' => $offboardings,
        ]);
    }

    /**
     * Show the form for creating a new offboarding process.
     */
    public function createOffboarding()
    {
        $this->authorize('create', Offboarding::class);

        $employees = User::select('id', 'name')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('HRM/Offboarding/Create', [
            'title' => 'Create Offboarding Process',
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created offboarding process.
     */
    public function storeOffboarding(StoreOffboardingRequest $request)
    {
        $this->authorize('create', Offboarding::class);

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $offboarding = Offboarding::create([
                'employee_id' => $validated['employee_id'],
                'initiation_date' => $validated['initiation_date'],
                'last_working_date' => $validated['last_working_date'],
                'exit_interview_date' => $validated['exit_interview_date'] ?? null,
                'reason' => $validated['reason'],
                'status' => $validated['status'] ?? Offboarding::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['tasks'] as $taskData) {
                OffboardingTask::create([
                    'offboarding_id' => $offboarding->id,
                    'task' => $taskData['task'],
                    'description' => $taskData['description'] ?? null,
                    'due_date' => $taskData['due_date'] ?? null,
                    'assigned_to' => $taskData['assigned_to'] ?? null,
                    'status' => OffboardingTask::STATUS_PENDING,
                ]);
            }

            // Dispatch EmployeeResigned event if reason is resignation
            $employee = User::with('employee')->findOrFail($validated['employee_id']);
            if (strtolower($validated['reason']) === 'resignation' && $employee->employee) {
                event(new EmployeeResigned(
                    $employee->employee,
                    now(),
                    $validated['last_working_date'],
                    $validated['notes'] ?? 'Voluntary resignation',
                    $validated['initiation_date']->diffInDays($validated['last_working_date'])
                ));
            }

            // Dispatch OffboardingStarted event
            event(new OffboardingStarted($offboarding, $validated['reason'], auth()->id()));

            DB::commit();

            return redirect()->route('hr.offboarding.show', $offboarding->id)
                ->with('success', 'Offboarding process created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create offboarding process', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create offboarding process. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified offboarding process.
     */
    public function showOffboarding($id)
    {
        $offboarding = Offboarding::with(['employee', 'tasks.assignee'])->findOrFail($id);
        $this->authorize('view', $offboarding);

        return Inertia::render('HRM/Offboarding/Show', [
            'title' => 'Offboarding Details',
            'offboarding' => $offboarding,
        ]);
    }

    /**
     * Update the specified offboarding process.
     */
    public function updateOffboarding(UpdateOffboardingRequest $request, $id)
    {
        $offboarding = Offboarding::with('tasks')->findOrFail($id);
        $this->authorize('update', $offboarding);

        $validated = $request->validated();

        // Track the old status to detect completion
        $oldStatus = $offboarding->status;

        DB::beginTransaction();
        try {
            $offboarding->update([
                'initiation_date' => $validated['initiation_date'],
                'last_working_date' => $validated['last_working_date'],
                'exit_interview_date' => $validated['exit_interview_date'] ?? null,
                'reason' => $validated['reason'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $existingTaskIds = $offboarding->tasks->pluck('id')->toArray();
            $updatedTaskIds = [];
            foreach ($validated['tasks'] as $taskData) {
                if (! empty($taskData['id'])) {
                    $task = OffboardingTask::findOrFail($taskData['id']);
                    $task->update([
                        'task' => $taskData['task'],
                        'description' => $taskData['description'] ?? null,
                        'due_date' => $taskData['due_date'] ?? null,
                        'completed_date' => $taskData['completed_date'] ?? null,
                        'status' => $taskData['status'],
                        'assigned_to' => $taskData['assigned_to'] ?? null,
                        'notes' => $taskData['notes'] ?? null,
                    ]);
                    $updatedTaskIds[] = $task->id;
                } else {
                    $task = OffboardingTask::create([
                        'offboarding_id' => $offboarding->id,
                        'task' => $taskData['task'],
                        'description' => $taskData['description'] ?? null,
                        'due_date' => $taskData['due_date'] ?? null,
                        'completed_date' => $taskData['completed_date'] ?? null,
                        'status' => $taskData['status'],
                        'assigned_to' => $taskData['assigned_to'] ?? null,
                        'notes' => $taskData['notes'] ?? null,
                    ]);
                    $updatedTaskIds[] = $task->id;
                }
            }
            $tasksToDelete = array_diff($existingTaskIds, $updatedTaskIds);
            if ($tasksToDelete) {
                OffboardingTask::whereIn('id', $tasksToDelete)->delete();
            }
            DB::commit();

            // Dispatch OffboardingCompleted event if status changed to 'completed'
            if ($oldStatus !== 'completed' && $validated['status'] === 'completed') {
                // Check if all tasks are completed
                $offboarding->refresh()->load('tasks');
                $allClearancesObtained = $offboarding->tasks->every(fn ($task) => $task->status === 'completed');

                event(new OffboardingCompleted(
                    $offboarding,
                    now(),
                    $allClearancesObtained,
                    Auth::id()
                ));
            }

            return redirect()->route('hr.offboarding.show', $offboarding->id)
                ->with('success', 'Offboarding process updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update offboarding process', ['error' => $e->getMessage(), 'offboarding_id' => $offboarding->id]);

            return redirect()->back()
                ->with('error', 'Failed to update offboarding process. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified offboarding process.
     */
    public function destroyOffboarding($id)
    {
        $offboarding = Offboarding::findOrFail($id);
        $this->authorize('delete', $offboarding);

        try {
            $offboarding->delete();

            return redirect()->route('hr.offboarding.index')
                ->with('success', 'Offboarding process deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete offboarding process', [
                'error' => $e->getMessage(),
                'offboarding_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete offboarding process.');
        }
    }

    /**
     * Show checklist templates.
     */
    public function checklists()
    {
        $this->authorize('viewAny', Checklist::class);
        $onboardingSteps = OnboardingStep::orderBy('order')->get();
        $offboardingSteps = OffboardingStep::orderBy('order')->get();
        $checklists = Checklist::orderBy('name')->get();

        return Inertia::render('HR/Checklists/Index', [
            'title' => 'Onboarding & Offboarding Checklists',
            'onboardingSteps' => $onboardingSteps,
            'offboardingSteps' => $offboardingSteps,
            'checklists' => $checklists,
        ]);
    }

    /**
     * Store a checklist template.
     */
    public function storeChecklist(\Illuminate\Http\Request $request)
    {
        $this->authorize('create', Checklist::class);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:onboarding,offboarding',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.label' => 'required_with:items|string|max:255',
        ]);
        $checklist = Checklist::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'items' => $data['items'] ?? [],
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Checklist created.');
    }

    /**
     * Update a checklist template.
     */
    public function updateChecklist(\Illuminate\Http\Request $request, $id)
    {
        $checklist = Checklist::findOrFail($id);
        $this->authorize('update', $checklist);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.label' => 'required_with:items|string|max:255',
            'active' => 'sometimes|boolean',
        ]);
        $checklist->update($data);

        return redirect()->back()->with('success', 'Checklist updated.');
    }

    /**
     * Delete a checklist template.
     */
    public function destroyChecklist($id)
    {
        $checklist = Checklist::findOrFail($id);
        $this->authorize('delete', $checklist);
        $checklist->delete();

        return redirect()->back()->with('success', 'Checklist deleted.');
    }
}
