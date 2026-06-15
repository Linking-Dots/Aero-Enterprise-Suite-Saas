<?php

namespace Aero\HRM\Http\Controllers\Employee;

use Aero\Core\Models\User;
use Aero\HRM\Events\Training\TrainingScheduled;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Training;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingMaterial;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class TrainingController extends Controller
{
    /**
     * Display a listing of the trainings.
     */
    public function index(Request $request)
    {
        if (! Schema::hasTable('trainings')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return Inertia::render('HRM/Training/Index', [
                'trainings' => $emptyPaginator,
                'filters' => $request->only(['search', 'status', 'category_id', 'department_id', 'sort_by', 'sort_order']),
                'categories' => TrainingCategory::select('id', 'name')->get(),
                'departments' => Department::select('id', 'name')->get(),
                'statuses' => [
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'scheduled', 'name' => 'Scheduled'],
                    ['id' => 'active', 'name' => 'Active'],
                    ['id' => 'completed', 'name' => 'Completed'],
                    ['id' => 'cancelled', 'name' => 'Cancelled'],
                ],
            ]);
        }

        $trainings = Training::with(['category', 'instructor', 'department'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->department_id, function ($query, $departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->orderBy($request->input('sort_by', 'start_date'), $request->input('sort_order', 'desc'))
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('HRM/Training/Index', [
            'trainings' => $trainings,
            'filters' => $request->only(['search', 'status', 'category_id', 'department_id', 'sort_by', 'sort_order']),
            'categories' => TrainingCategory::select('id', 'name')->get(),
            'departments' => Department::select('id', 'name')->get(),
            'statuses' => [
                ['id' => 'draft', 'name' => 'Draft'],
                ['id' => 'scheduled', 'name' => 'Scheduled'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'completed', 'name' => 'Completed'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new training.
     */
    public function create()
    {
        return Inertia::render('HRM/Training/Create', [
            'categories' => TrainingCategory::where('is_active', true)->get(['id', 'name']),
            'instructors' => $this->getUsersWithTrainingAccess(),
            'departments' => Department::all(['id', 'name']),
            'types' => [
                ['id' => 'course', 'name' => 'Course'],
                ['id' => 'workshop', 'name' => 'Workshop'],
                ['id' => 'seminar', 'name' => 'Seminar'],
                ['id' => 'certification', 'name' => 'Certification'],
                ['id' => 'on_the_job', 'name' => 'On-the-job Training'],
                ['id' => 'webinar', 'name' => 'Webinar'],
                ['id' => 'conference', 'name' => 'Conference'],
            ],
        ]);
    }

    /**
     * Store a newly created training in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:training_categories,id',
            'type' => 'required|string',
            'status' => 'required|in:draft,scheduled,active,completed,cancelled',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration' => 'nullable|numeric',
            'duration_unit' => 'nullable|in:hours,days,weeks,months',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'instructor_id' => 'nullable|exists:users,id',
            'max_participants' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'certification' => 'nullable|string|max:255',
            'approval_required' => 'boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $validated['created_by'] = Auth::id();

        $training = Training::create($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $training->addMedia($file)->toMediaCollection('training_attachments');
            }
        }

        // Dispatch TrainingScheduled event if status is scheduled
        if ($validated['status'] === 'scheduled') {
            // Get enrolled employee IDs (if any were pre-enrolled)
            $enrolledEmployeeIds = [];
            if ($request->has('enrolled_employees')) {
                $enrolledEmployeeIds = $request->input('enrolled_employees', []);
            }
            event(new TrainingScheduled($training, $enrolledEmployeeIds));
        }

        return redirect()->route('hr.training.show', $training->id)
            ->with('success', 'Training created successfully.');
    }

    /**
     * Display the specified training.
     */
    public function show($id)
    {
        $training = Training::with([
            'category',
            'instructor',
            'department',
            'creator',
            'materials',
            'enrollments.user',
        ])->findOrFail($id);

        // Check if current user is enrolled
        $userEnrollment = null;
        if (Auth::check()) {
            $userEnrollment = $training->enrollments()
                ->where('user_id', Auth::id())
                ->first();
        }

        return Inertia::render('HRM/Training/Show', [
            'training' => $training,
            'userEnrollment' => $userEnrollment,
            'attachments' => $training->getMedia('training_attachments'),
            'canEnroll' => $training->status === 'active' && ! $training->isFull(),
            'availableSpots' => $training->availableSpots(),
        ]);
    }

    /**
     * Show the form for editing the specified training.
     */
    public function edit($id)
    {
        $training = Training::with([
            'category',
            'instructor',
            'department',
        ])->findOrFail($id);

        return Inertia::render('HRM/Training/Edit', [
            'training' => $training,
            'categories' => TrainingCategory::where('is_active', true)->get(['id', 'name']),
            'instructors' => $this->getUsersWithTrainingAccess(),
            'departments' => Department::all(['id', 'name']),
            'types' => [
                ['id' => 'course', 'name' => 'Course'],
                ['id' => 'workshop', 'name' => 'Workshop'],
                ['id' => 'seminar', 'name' => 'Seminar'],
                ['id' => 'certification', 'name' => 'Certification'],
                ['id' => 'on_the_job', 'name' => 'On-the-job Training'],
                ['id' => 'webinar', 'name' => 'Webinar'],
                ['id' => 'conference', 'name' => 'Conference'],
            ],
            'attachments' => $training->getMedia('training_attachments'),
        ]);
    }

    /**
     * Update the specified training in storage.
     */
    public function update(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:training_categories,id',
            'type' => 'required|string',
            'status' => 'required|in:draft,scheduled,active,completed,cancelled',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration' => 'nullable|numeric',
            'duration_unit' => 'nullable|in:hours,days,weeks,months',
            'location' => 'nullable|string|max:255',
            'is_online' => 'boolean',
            'instructor_id' => 'nullable|exists:users,id',
            'max_participants' => 'nullable|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'prerequisites' => 'nullable|array',
            'learning_outcomes' => 'nullable|array',
            'certification' => 'nullable|string|max:255',
            'approval_required' => 'boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $training->update($validated);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $training->addMedia($file)->toMediaCollection('training_attachments');
            }
        }

        if ($request->has('delete_attachments') && is_array($request->delete_attachments)) {
            foreach ($request->delete_attachments as $mediaId) {
                $media = $training->getMedia('training_attachments')->find($mediaId);
                if ($media) {
                    $media->delete();
                }
            }
        }

        return redirect()->route('hr.training.show', $training->id)
            ->with('success', 'Training updated successfully.');
    }

    /**
     * Remove the specified training from storage.
     */
    public function destroy($id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return redirect()->route('hr.training.index')
            ->with('success', 'Training deleted successfully.');
    }

    /**
     * Display a listing of the training categories.
     */
    public function categories()
    {
        $categories = TrainingCategory::withCount('trainings')
            ->orderBy('name')
            ->paginate(10);

        return Inertia::render('HRM/Training/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created training category in storage.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:training_categories,id',
        ]);

        $validated['created_by'] = Auth::id();

        $category = TrainingCategory::create($validated);

        return redirect()->back()
            ->with('success', 'Training category created successfully.');
    }

    /**
     * Update the specified training category in storage.
     */
    public function updateCategory(Request $request, $id)
    {
        $category = TrainingCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:training_categories,id',
        ]);

        $category->update($validated);

        return redirect()->back()
            ->with('success', 'Training category updated successfully.');
    }

    /**
     * Remove the specified training category from storage.
     */
    public function destroyCategory($id)
    {
        $category = TrainingCategory::findOrFail($id);

        // Check if there are trainings in this category
        if ($category->trainings()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete category with associated trainings.');
        }

        $category->delete();

        return redirect()->back()
            ->with('success', 'Training category deleted successfully.');
    }

    /**
     * Display a listing of the materials for a training.
     */
    public function materials($id)
    {
        $training = Training::with('materials')->findOrFail($id);

        return Inertia::render('HRM/Training/Materials/Index', [
            'training' => $training,
            'materials' => $training->materials()->orderBy('order')->paginate(10),
        ]);
    }

    /**
     * Store a newly created training material in storage.
     */
    public function storeMaterial(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'url' => 'nullable|url',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'visibility' => 'required|in:public,staff,completion_based',
        ]);

        $validated['training_id'] = $training->id;
        $validated['created_by'] = Auth::id();

        $material = TrainingMaterial::create($validated);

        if ($request->hasFile('file')) {
            $material->addMedia($request->file('file'))->toMediaCollection('material_files');
        }

        return redirect()->back()
            ->with('success', 'Training material added successfully.');
    }

    /**
     * Update the specified training material in storage.
     */
    public function updateMaterial(Request $request, $id, $materialId)
    {
        $material = TrainingMaterial::where('training_id', $id)->findOrFail($materialId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'url' => 'nullable|url',
            'is_required' => 'boolean',
            'order' => 'nullable|integer',
            'visibility' => 'required|in:public,staff,completion_based',
        ]);

        $material->update($validated);

        if ($request->hasFile('file')) {
            // Remove existing file if any
            $material->clearMediaCollection('material_files');
            // Add new file
            $material->addMedia($request->file('file'))->toMediaCollection('material_files');
        }

        return redirect()->back()
            ->with('success', 'Training material updated successfully.');
    }

    /**
     * Remove the specified training material from storage.
     */
    public function destroyMaterial($id, $materialId)
    {
        $material = TrainingMaterial::where('training_id', $id)->findOrFail($materialId);
        $material->delete();

        return redirect()->back()
            ->with('success', 'Training material deleted successfully.');
    }

    /**
     * Display a listing of the enrollments for a training.
     */
    public function enrollments($id)
    {
        $training = Training::findOrFail($id);

        $enrollments = $training->enrollments()
            ->with('user')
            ->orderBy('enrollment_date')
            ->paginate(15);

        return Inertia::render('HRM/Training/Enrollments/Index', [
            'training' => $training,
            'enrollments' => $enrollments,
            'statusOptions' => [
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'approved', 'name' => 'Approved'],
                ['id' => 'rejected', 'name' => 'Rejected'],
                ['id' => 'in_progress', 'name' => 'In Progress'],
                ['id' => 'completed', 'name' => 'Completed'],
                ['id' => 'withdrawn', 'name' => 'Withdrawn'],
            ],
        ]);
    }

    /**
     * Store a newly created training enrollment in storage.
     */
    public function storeEnrollment(Request $request, $id)
    {
        $training = Training::findOrFail($id);

        // Check if the training is full
        if ($training->isFull() && $request->status === 'approved') {
            return redirect()->back()
                ->with('error', 'This training is already full.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:pending,approved,rejected,in_progress,completed,withdrawn',
            'enrollment_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'score' => 'nullable|numeric',
            'feedback' => 'nullable|string',
            'certificate_issued' => 'boolean',
            'rejected_reason' => 'nullable|string',
        ]);

        // Check if the user is already enrolled
        if ($training->enrollments()->where('user_id', $request->user_id)->exists()) {
            return redirect()->back()
                ->with('error', 'This user is already enrolled in the training.');
        }

        $validated['training_id'] = $training->id;
        $validated['enrollment_date'] = $validated['enrollment_date'] ?? now();
        $validated['approved_by'] = Auth::id();

        TrainingEnrollment::create($validated);

        return redirect()->back()
            ->with('success', 'User enrolled in training successfully.');
    }

    /**
     * Update the specified training enrollment in storage.
     */
    public function updateEnrollment(Request $request, $id, $enrollmentId)
    {
        $enrollment = TrainingEnrollment::where('training_id', $id)->findOrFail($enrollmentId);

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,in_progress,completed,withdrawn',
            'completion_date' => 'nullable|date',
            'score' => 'nullable|numeric',
            'feedback' => 'nullable|string',
            'certificate_issued' => 'boolean',
            'rejected_reason' => 'nullable|string',
        ]);

        // If approving and the training is full, prevent it
        if ($enrollment->status !== 'approved' && $request->status === 'approved') {
            $training = Training::findOrFail($id);
            if ($training->isFull()) {
                return redirect()->back()
                    ->with('error', 'This training is already full.');
            }
        }

        // If marking as completed, set completion date if not provided
        if ($request->status === 'completed' && ! $request->completion_date) {
            $validated['completion_date'] = now();
        }

        $enrollment->update($validated);

        return redirect()->back()
            ->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Remove the specified training enrollment from storage.
     */
    public function destroyEnrollment($id, $enrollmentId)
    {
        $enrollment = TrainingEnrollment::where('training_id', $id)->findOrFail($enrollmentId);
        $enrollment->delete();

        return redirect()->back()
            ->with('success', 'Enrollment deleted successfully.');
    }

    /**
     * Get users with training module access for instructor dropdowns.
     */
    protected function getUsersWithTrainingAccess(): \Illuminate\Support\Collection
    {
        try {
            return \Aero\HRMAC\Facades\HRMAC::getUsersWithSubModuleAccess('hrm', 'training')
                ->map(fn ($user) => ['id' => $user->id, 'name' => $user->name]);
        } catch (\Exception $e) {
            // Fallback to all active users
            return \Aero\Core\Models\User::active()
                ->get(['id', 'name']);
        }
    }
}
