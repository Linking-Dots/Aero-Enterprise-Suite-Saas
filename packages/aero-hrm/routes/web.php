<?php

use Aero\HRM\Http\Controllers\AIAnalyticsController;
use Aero\HRM\Http\Controllers\Asset\AssetCategoryController;
use Aero\HRM\Http\Controllers\Asset\AssetController;
use Aero\HRM\Http\Controllers\Attendance\AttendanceController;
use Aero\HRM\Http\Controllers\Attendance\OvertimeController as AttendanceOvertimeController;
use Aero\HRM\Http\Controllers\Attendance\ShiftMarketplaceController;
use Aero\HRM\Http\Controllers\Attendance\TimesheetController;
use Aero\HRM\Http\Controllers\CareerPathController;
use Aero\HRM\Http\Controllers\CompensationPlanningController;
use Aero\HRM\Http\Controllers\DEIAnalyticsController;
use Aero\HRM\Http\Controllers\Disciplinary\ActionTypeController;
use Aero\HRM\Http\Controllers\Disciplinary\DisciplinaryCaseController;
use Aero\HRM\Http\Controllers\Disciplinary\WarningController;
use Aero\HRM\Http\Controllers\Employee\BenefitsController;
use Aero\HRM\Http\Controllers\Employee\DepartmentController;
use Aero\HRM\Http\Controllers\Employee\DesignationController;
use Aero\HRM\Http\Controllers\Employee\EducationController;
use Aero\HRM\Http\Controllers\Employee\EmployeeController;
use Aero\HRM\Http\Controllers\Employee\EmployeeDashboardController;
use Aero\HRM\Http\Controllers\Employee\EmployeeDocumentController;
use Aero\HRM\Http\Controllers\Employee\EmployeeImageController;
use Aero\HRM\Http\Controllers\Employee\EmployeeProfileController;
use Aero\HRM\Http\Controllers\Employee\EmployeeSelfServiceController;
use Aero\HRM\Http\Controllers\Employee\ExperienceController;
use Aero\HRM\Http\Controllers\Employee\HolidayController;
use Aero\HRM\Http\Controllers\Employee\HrAnalyticsController;
use Aero\HRM\Http\Controllers\Employee\HrDocumentController;
use Aero\HRM\Http\Controllers\Employee\ManagersController;
use Aero\HRM\Http\Controllers\Employee\OnboardingController;
use Aero\HRM\Http\Controllers\Employee\PayrollController;
use Aero\HRM\Http\Controllers\Employee\ProfileController;
use Aero\HRM\Http\Controllers\Employee\ProfileImageController;
use Aero\HRM\Http\Controllers\Employee\SalaryStructureController;
use Aero\HRM\Http\Controllers\Employee\SkillsController;
use Aero\HRM\Http\Controllers\Employee\TimeOffController;
use Aero\HRM\Http\Controllers\Employee\TimeOffManagementController;
use Aero\HRM\Http\Controllers\Employee\TrainingController;
use Aero\HRM\Http\Controllers\Employee\WorkplaceSafetyController;
use Aero\HRM\Http\Controllers\EmployeeHistoryController;
use Aero\HRM\Http\Controllers\ExitInterviewController;
use Aero\HRM\Http\Controllers\Expense\ExpenseCategoryController;
use Aero\HRM\Http\Controllers\Expense\ExpenseClaimController;
use Aero\HRM\Http\Controllers\Feedback360Controller;
use Aero\HRM\Http\Controllers\GrievanceController;
use Aero\HRM\Http\Controllers\HRMDashboardController;
use Aero\HRM\Http\Controllers\Leave\BulkLeaveController;
use Aero\HRM\Http\Controllers\Leave\LeaveAccrualController;
use Aero\HRM\Http\Controllers\Leave\LeaveApplicationController;
use Aero\HRM\Http\Controllers\Leave\LeaveBalanceController;
use Aero\HRM\Http\Controllers\Leave\LeaveCalendarController;
use Aero\HRM\Http\Controllers\Leave\LeaveController;
use Aero\HRM\Http\Controllers\Leave\LeaveSettingController as LeaveLeaveSettingController;
use Aero\HRM\Http\Controllers\Leave\LeaveTypeController;
use Aero\HRM\Http\Controllers\OrgStructure\GradeController;
use Aero\HRM\Http\Controllers\OrgStructure\WorkLocationController;
use Aero\HRM\Http\Controllers\OvertimeController;
use Aero\HRM\Http\Controllers\Payroll\PayComponentController as PayrollPayComponentController;
use Aero\HRM\Http\Controllers\Payroll\PayrollRunController as PayrollPayrollRunController;
use Aero\HRM\Http\Controllers\Payroll\PayslipController as PayrollPayslipController;
use Aero\HRM\Http\Controllers\Payroll\SalaryStructureController as PayrollSalaryStructureController;
use Aero\HRM\Http\Controllers\Payroll\TaxSettingController as PayrollTaxSettingController;
use Aero\HRM\Http\Controllers\Performance\GoalController;
use Aero\HRM\Http\Controllers\Performance\PerformanceCalibrationController;
use Aero\HRM\Http\Controllers\Performance\PerformanceImprovementPlanController;
use Aero\HRM\Http\Controllers\Performance\PerformanceReviewController;
use Aero\HRM\Http\Controllers\Performance\SkillMatrixController;
use Aero\HRM\Http\Controllers\PulseSurveyController;
use Aero\HRM\Http\Controllers\Recruitment\RecruitmentController;
use Aero\HRM\Http\Controllers\Settings\AttendanceSettingController;
use Aero\HRM\Http\Controllers\Settings\HrmSettingController;
use Aero\HRM\Http\Controllers\Settings\LeaveSettingController;
use Aero\HRM\Http\Controllers\SuccessionPlanningController;
use Aero\HRM\Http\Controllers\TalentMarketplaceController;
use Aero\HRM\Http\Controllers\WellbeingController;
use Aero\HRM\Http\Controllers\WorkforcePlanningController;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Aero HRM Routes
|--------------------------------------------------------------------------
|
| All routes for the Aero HRM package including:
| - Employee Management
| - Attendance & Leave
| - Payroll & Performance
| - Recruitment & Training
|
| Route Naming Convention:
| - All route names automatically get 'hrm.' prefix from service provider
| - All paths automatically get /hrm prefix from service provider
| - Routes defined here should NOT add additional 'hr.' prefix
|
| Service Provider Configuration:
| - Prefix: 'hrm' (results in paths: /hrm/*)
| - Name: 'hrm.' (results in names: hrm.*)
| - Middleware: ['web', 'auth'] (standalone) or ['web', 'tenant', 'auth'] (SaaS)
|
| Examples:
| - Route defined as: Route::get('/dashboard', ...)->name('dashboard')
| - Actual route: /hrm/dashboard (name: hrm.dashboard)
|
| These routes are automatically registered by the AeroHrmServiceProvider.
|
*/

// ============================================================================
// PUBLIC/GLOBAL HRM ROUTES (No module prefix)
// ============================================================================

// Leave Summary Route - Accessible without hrm prefix (backward compatibility)
Route::middleware(['auth', 'verified', 'hrmac:hrm.time-off'])
    ->get('/leave-summary', [LeaveController::class, 'summary'])
    ->name('leave.summary');

// Profile search for admin usage (cross-module functionality)
Route::middleware(['auth', 'verified', 'hrmac:hrm.employees'])
    ->get('/profiles/search', [ProfileController::class, 'search'])
    ->name('profiles.search');

// ============================================================================
// AUTHENTICATED HRM ROUTES
// ============================================================================
// Note: Service provider adds 'hrm.' prefix and '/hrm' path automatically
Route::middleware(['auth', 'verified'])->group(function () {
    // HR Dashboard (for HR Managers and Admins)
    Route::middleware(['hrmac:hrm.dashboard'])->group(function () {
        Route::get('/dashboard', [HRMDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/stats', [HRMDashboardController::class, 'stats'])->name('dashboard.stats');
    });

    // Employee Dashboard (for regular employees - personal view)
    Route::middleware(['hrmac:hrm.employee-self-service'])->group(function () {
        Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])
            ->name('employee.dashboard');
        Route::get('/employee/dashboard/attendance-chart', [EmployeeDashboardController::class, 'attendanceChart'])
            ->name('employee.dashboard.attendance-chart');
    });

    // Performance Calibration
    Route::middleware(['hrmac:hrm.performance.calibration.view'])->group(function () {
        Route::get('/performance/calibration', [PerformanceCalibrationController::class, 'index'])->name('performance.calibration.index');
        Route::get('/performance/calibration/{id}', [PerformanceCalibrationController::class, 'show'])->name('performance.calibration.show');
    });
    Route::middleware(['hrmac:hrm.performance.calibration.manage'])->group(function () {
        Route::post('/performance/calibration', [PerformanceCalibrationController::class, 'store'])->name('performance.calibration.store');
        Route::put('/performance/calibration/{id}/rating', [PerformanceCalibrationController::class, 'updateRating'])->name('performance.calibration.update-rating');
        Route::post('/performance/calibration/{id}/finalize', [PerformanceCalibrationController::class, 'finalize'])->name('performance.calibration.finalize');
    });

    // Performance Management
    Route::middleware(['hrmac:hrm.performance'])->group(function () {
        Route::get('/performance', [PerformanceReviewController::class, 'index'])->name('performance.index');
        Route::get('/performance/stats', [PerformanceReviewController::class, 'stats'])->name('performance.stats');
        Route::get('/performance/create', [PerformanceReviewController::class, 'create'])->name('performance.create');
        Route::post('/performance', [PerformanceReviewController::class, 'store'])->name('performance.store');
        Route::get('/performance/{id}', [PerformanceReviewController::class, 'show'])->whereNumber('id')->name('performance.show');
        Route::get('/performance/{id}/edit', [PerformanceReviewController::class, 'edit'])->whereNumber('id')->name('performance.edit');
        Route::put('/performance/{id}', [PerformanceReviewController::class, 'update'])->whereNumber('id')->name('performance.update');
        Route::delete('/performance/{id}', [PerformanceReviewController::class, 'destroy'])->whereNumber('id')->name('performance.destroy');

        // Performance Templates
        Route::get('/performance/templates', [PerformanceReviewController::class, 'templates'])->name('performance.templates.index');
        Route::get('/performance/templates/create', [PerformanceReviewController::class, 'createTemplate'])->name('performance.templates.create');
        Route::post('/performance/templates', [PerformanceReviewController::class, 'storeTemplate'])->name('performance.templates.store');
        Route::get('/performance/templates/{id}', [PerformanceReviewController::class, 'showTemplate'])->whereNumber('id')->name('performance.templates.show');
        Route::get('/performance/templates/{id}/edit', [PerformanceReviewController::class, 'editTemplate'])->whereNumber('id')->name('performance.templates.edit');
        Route::put('/performance/templates/{id}', [PerformanceReviewController::class, 'updateTemplate'])->whereNumber('id')->name('performance.templates.update');
        Route::delete('/performance/templates/{id}', [PerformanceReviewController::class, 'destroyTemplate'])->whereNumber('id')->name('performance.templates.destroy');

        // =====================================================================
        // GOALS (OKR) Management
        // =====================================================================
        Route::prefix('goals')->name('goals.')->group(function () {
            Route::get('/', [GoalController::class, 'index'])->name('index');
            Route::get('/stats', [GoalController::class, 'stats'])->name('stats');
            Route::get('/paginate', [GoalController::class, 'paginate'])->name('paginate');
            Route::get('/create', [GoalController::class, 'create'])->name('create');
            Route::post('/', [GoalController::class, 'store'])->name('store');
            Route::get('/team', [GoalController::class, 'teamGoals'])->name('team');
            Route::get('/analytics', [GoalController::class, 'analytics'])->name('analytics');
            Route::get('/{goalId}', [GoalController::class, 'show'])->name('show');
            Route::put('/{goalId}', [GoalController::class, 'update'])->name('update');
            Route::delete('/{goalId}', [GoalController::class, 'destroy'])->name('destroy');
            Route::post('/{goalId}/complete', [GoalController::class, 'complete'])->name('complete');
            Route::put('/{goalId}/progress', [GoalController::class, 'updateProgress'])->name('update-progress');
            Route::post('/{goalId}/check-in', [GoalController::class, 'checkIn'])->name('check-in');
            Route::put('/{goalId}/key-results/{keyResultId}', [GoalController::class, 'updateKeyResult'])->name('key-results.update');
        });

        // =====================================================================
        // COMPETENCIES & SKILL MATRIX
        // =====================================================================
        Route::prefix('competencies')->name('competencies.')->group(function () {
            Route::get('/', [SkillMatrixController::class, 'index'])->name('index');
            Route::post('/', [SkillMatrixController::class, 'store'])->name('store');
            Route::put('/{competencyId}', [SkillMatrixController::class, 'update'])->name('update');
            Route::delete('/{competencyId}', [SkillMatrixController::class, 'destroy'])->name('destroy');
            Route::get('/role-frameworks', [SkillMatrixController::class, 'roleFrameworks'])->name('role-frameworks');
            Route::post('/role-frameworks', [SkillMatrixController::class, 'createRoleFramework'])->name('role-frameworks.store');
            Route::get('/team-matrix', [SkillMatrixController::class, 'teamMatrix'])->name('team-matrix');
            Route::get('/analytics', [SkillMatrixController::class, 'analytics'])->name('analytics');
            Route::get('/employees/{employeeId}', [SkillMatrixController::class, 'employeeProfile'])->name('employee-profile');
            Route::post('/employees/{employeeId}/{competencyId}/assess', [SkillMatrixController::class, 'assessCompetency'])->name('assess');
            Route::get('/employees/{employeeId}/gap-analysis', [SkillMatrixController::class, 'gapAnalysis'])->name('gap-analysis');
            Route::post('/employees/{employeeId}/{competencyId}/endorse', [SkillMatrixController::class, 'endorse'])->name('endorse');
        });
    });

    // Training Management
    Route::middleware(['hrmac:hrm.training'])->group(function () {
        Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
        Route::get('/training/create', [TrainingController::class, 'create'])->name('training.create');
        Route::post('/training', [TrainingController::class, 'store'])->name('training.store');
        Route::get('/training/{id}', [TrainingController::class, 'show'])->name('training.show');
        Route::get('/training/{id}/edit', [TrainingController::class, 'edit'])->name('training.edit');
        Route::put('/training/{id}', [TrainingController::class, 'update'])->name('training.update');
        Route::delete('/training/{id}', [TrainingController::class, 'destroy'])->name('training.destroy');

        // Training Categories
        Route::get('/training/categories', [TrainingController::class, 'categories'])->name('training.categories.index');
        Route::post('/training/categories', [TrainingController::class, 'storeCategory'])->name('training.categories.store');
        Route::put('/training/categories/{id}', [TrainingController::class, 'updateCategory'])->name('training.categories.update');
        Route::delete('/training/categories/{id}', [TrainingController::class, 'destroyCategory'])->name('training.categories.destroy');

        // Training Materials
        Route::get('/training/{id}/materials', [TrainingController::class, 'materials'])->name('training.materials.index');
        Route::post('/training/{id}/materials', [TrainingController::class, 'storeMaterial'])->name('training.materials.store');
        Route::put('/training/{id}/materials/{materialId}', [TrainingController::class, 'updateMaterial'])->name('training.materials.update');
        Route::delete('/training/{id}/materials/{materialId}', [TrainingController::class, 'destroyMaterial'])->name('training.materials.destroy');

        // Training Enrollment
        Route::get('/training/{id}/enrollments', [TrainingController::class, 'enrollments'])->name('training.enrollments.index');
        Route::post('/training/{id}/enrollments', [TrainingController::class, 'storeEnrollment'])->name('training.enrollments.store');
        Route::put('/training/{id}/enrollments/{enrollmentId}', [TrainingController::class, 'updateEnrollment'])->name('training.enrollments.update');
        Route::delete('/training/{id}/enrollments/{enrollmentId}', [TrainingController::class, 'destroyEnrollment'])->name('training.enrollments.destroy');
    });

    // Recruitment Management
    Route::middleware(['hrmac:hrm.recruitment'])->group(function () {
        Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment.index');
        Route::post('/recruitment', [RecruitmentController::class, 'store'])->name('recruitment.store');

        // AJAX API routes for modal operations (MUST be before {id} routes)
        Route::post('/recruitment/ajax', [RecruitmentController::class, 'storeAjax'])->name('recruitment.store.ajax');

        // AJAX/Data Routes for SPA refreshes (MUST be before {id} routes)
        Route::get('/recruitment/data', [RecruitmentController::class, 'indexData'])->name('recruitment.data.index');

        // Statistics (MUST be before {id} routes)
        Route::get('/recruitment/statistics', [RecruitmentController::class, 'getStatistics'])->name('recruitment.statistics');

        // Bulk Operations (MUST be before {id} routes)
        Route::patch('/recruitment/applications/bulk-update', [RecruitmentController::class, 'bulkUpdateApplications'])->name('recruitment.applications.bulk-update');

        // Dynamic ID routes (MUST be after static routes)
        Route::get('/recruitment/{id}', [RecruitmentController::class, 'show'])->name('recruitment.show');
        Route::get('/recruitment/{id}/edit', [RecruitmentController::class, 'edit'])->name('recruitment.edit');
        Route::put('/recruitment/{id}', [RecruitmentController::class, 'update'])->name('recruitment.update');
        Route::delete('/recruitment/{id}', [RecruitmentController::class, 'destroy'])->name('recruitment.destroy');

        // Kanban Board View
        Route::get('/recruitment/{id}/kanban', [RecruitmentController::class, 'kanban'])->name('recruitment.kanban');

        // AJAX API routes for modal operations
        Route::put('/recruitment/{id}/ajax', [RecruitmentController::class, 'updateAjax'])->name('recruitment.update.ajax');

        // AJAX/Data Routes for SPA refreshes
        Route::get('/recruitment/{id}/data', [RecruitmentController::class, 'showData'])->name('recruitment.data.show');
        Route::get('/recruitment/{id}/applications/data', [RecruitmentController::class, 'applicationsData'])->name('recruitment.data.applications');

        // Job status management
        Route::post('/recruitment/{id}/publish', [RecruitmentController::class, 'publish'])->name('recruitment.publish');
        Route::post('/recruitment/{id}/unpublish', [RecruitmentController::class, 'unpublish'])->name('recruitment.unpublish');
        Route::post('/recruitment/{id}/close', [RecruitmentController::class, 'close'])->name('recruitment.close');

        // Reports
        Route::get('/recruitment/{id}/report', [RecruitmentController::class, 'generateJobReport'])->name('recruitment.report');
        Route::get('/recruitment/{id}/applications/export', [RecruitmentController::class, 'exportApplications'])->name('recruitment.applications.export');

        // Job Applications
        Route::get('/recruitment/{id}/applications', [RecruitmentController::class, 'applications'])->name('recruitment.applications.index');
        Route::get('/recruitment/{id}/applications/create', [RecruitmentController::class, 'createApplication'])->name('recruitment.applications.create');
        Route::post('/recruitment/{id}/applications', [RecruitmentController::class, 'storeApplication'])->name('recruitment.applications.store');
        Route::get('/recruitment/{id}/applications/{applicationId}', [RecruitmentController::class, 'showApplication'])->name('recruitment.applications.show');
        Route::put('/recruitment/{id}/applications/{applicationId}', [RecruitmentController::class, 'updateApplication'])->name('recruitment.applications.update');
        Route::delete('/recruitment/{id}/applications/{applicationId}', [RecruitmentController::class, 'destroyApplication'])->name('recruitment.applications.destroy');

        // Application Stage Update (for Kanban drag & drop)
        Route::put('/recruitment/{id}/applications/{applicationId}/stage', [RecruitmentController::class, 'updateStage'])->name('recruitment.applications.update-stage');

        // Interviews
        Route::get('/recruitment/{id}/applications/{applicationId}/interviews', [RecruitmentController::class, 'interviews'])->name('recruitment.interviews.index');
        Route::post('/recruitment/{id}/applications/{applicationId}/interviews', [RecruitmentController::class, 'storeInterview'])->name('recruitment.interviews.store');
        Route::put('/recruitment/{id}/applications/{applicationId}/interviews/{interviewId}', [RecruitmentController::class, 'updateInterview'])->name('recruitment.interviews.update');
        Route::delete('/recruitment/{id}/applications/{applicationId}/interviews/{interviewId}', [RecruitmentController::class, 'destroyInterview'])->name('recruitment.interviews.destroy');

        // Job Offers
        Route::post('/recruitment/{id}/applications/{applicationId}/offer', [RecruitmentController::class, 'extendOffer'])->name('recruitment.applications.extend-offer');
    });

    // Employee Onboarding & Offboarding
    Route::middleware(['hrmac:hrm.onboarding'])->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
        Route::get('/onboarding/create', [OnboardingController::class, 'create'])->name('onboarding.create');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
        Route::get('/onboarding/{id}', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::get('/onboarding/{id}/edit', [OnboardingController::class, 'edit'])->name('onboarding.edit');
        Route::put('/onboarding/{id}', [OnboardingController::class, 'update'])->name('onboarding.update');
        Route::delete('/onboarding/{id}', [OnboardingController::class, 'destroy'])->name('onboarding.destroy');

        // Onboarding Wizard
        Route::get('/onboarding/wizard/{employee}', [OnboardingController::class, 'wizard'])->name('onboarding.wizard');
        Route::post('/onboarding/wizard/{employee}/personal', [OnboardingController::class, 'savePersonal'])->name('onboarding.save-personal');
        Route::post('/onboarding/wizard/{employee}/job', [OnboardingController::class, 'saveJob'])->name('onboarding.save-job');
        Route::post('/onboarding/wizard/{employee}/documents', [OnboardingController::class, 'saveDocuments'])->name('onboarding.save-documents');
        Route::post('/onboarding/wizard/{employee}/bank', [OnboardingController::class, 'saveBank'])->name('onboarding.save-bank');
        Route::post('/onboarding/wizard/{employee}/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

        // Offboarding
        Route::get('/offboarding', [OnboardingController::class, 'offboardingIndex'])->name('offboarding.index');
        Route::get('/offboarding/create', [OnboardingController::class, 'createOffboarding'])->name('offboarding.create');
        Route::post('/offboarding', [OnboardingController::class, 'storeOffboarding'])->name('offboarding.store');
        Route::get('/offboarding/{id}', [OnboardingController::class, 'showOffboarding'])->name('offboarding.show');
        Route::put('/offboarding/{id}', [OnboardingController::class, 'updateOffboarding'])->name('offboarding.update');
        Route::delete('/offboarding/{id}', [OnboardingController::class, 'destroyOffboarding'])->name('offboarding.destroy');

        // Checklists
        Route::get('/checklists', [OnboardingController::class, 'checklists'])->name('checklists.index');
        Route::post('/checklists', [OnboardingController::class, 'storeChecklist'])->name('checklists.store');
        Route::put('/checklists/{id}', [OnboardingController::class, 'updateChecklist'])->name('checklists.update');
        Route::delete('/checklists/{id}', [OnboardingController::class, 'destroyChecklist'])->name('checklists.destroy');
    });

    // Skills & Competency Management
    Route::middleware(['hrmac:hrm.employees.skills'])->group(function () {
        Route::get('/skills', [SkillsController::class, 'index'])->name('skills.index');
        Route::get('/skills/stats', [SkillsController::class, 'stats'])->name('skills.stats');
        Route::get('/skills/matrix', [SkillsController::class, 'matrix'])->name('skills.matrix');
        Route::get('/skills/all-employee-skills', [SkillsController::class, 'allEmployeeSkills'])->name('skills.all-employee-skills');
        Route::post('/skills', [SkillsController::class, 'store'])->name('skills.store');
        Route::put('/skills/{id}', [SkillsController::class, 'update'])->name('skills.update');
        Route::delete('/skills/{id}', [SkillsController::class, 'destroy'])->name('skills.destroy');

        // Employee Skills
        Route::get('/employee-skills/{employeeId}', [SkillsController::class, 'employeeSkills'])->name('employee.skills.index');
        Route::post('/employee-skills/{employeeId}', [SkillsController::class, 'storeEmployeeSkill'])->name('employee.skills.store');
        Route::put('/employee-skills/{employeeId}/{skillId}', [SkillsController::class, 'updateEmployeeSkill'])->name('employee.skills.update');
        Route::post('/employee-skills/{employeeId}/{skillId}/verify', [SkillsController::class, 'verifyEmployeeSkill'])->name('employee.skills.verify');
        Route::delete('/employee-skills/{employeeId}/{skillId}', [SkillsController::class, 'destroyEmployeeSkill'])->name('employee.skills.destroy');
    });

    // Time Off Management (Industry Standard)
    Route::middleware(['hrmac:hrm.time-off'])->group(function () {
        // Time Off Dashboard
        Route::get('/time-off', [TimeOffManagementController::class, 'index'])->name('timeoff.index');
        Route::get('/time-off/dashboard', [TimeOffManagementController::class, 'index'])->name('timeoff.dashboard');

        // Company Holidays Management
        Route::get('/time-off/holidays', [TimeOffManagementController::class, 'holidays'])->name('timeoff.holidays');

        // Leave Requests Management
        Route::get('/time-off/leave-requests', [TimeOffManagementController::class, 'leaveRequests'])->name('timeoff.leave-requests');

        // Time Off Calendar
        Route::get('/time-off/calendar', [TimeOffManagementController::class, 'calendar'])->name('timeoff.calendar');

        // Leave Balances
        Route::get('/time-off/balances', [TimeOffManagementController::class, 'balances'])->name('timeoff.balances');

        // Time Off Reports
        Route::get('/time-off/reports', [TimeOffManagementController::class, 'reports'])->name('timeoff.reports');

        // Employee Self-Service Time Off
        Route::get('/time-off/employee-requests', [TimeOffManagementController::class, 'employeeRequests'])->name('timeoff.employee-requests');
    });

    // ============================================================================
    // Leave Accrual Rules
    // ============================================================================
    Route::prefix('leaves/accrual')->name('leaves.accrual.')->group(function () {
        Route::get('/', [LeaveAccrualController::class, 'index'])->name('index')
            ->middleware('hrmac:hrm.leaves.leave-accrual.view');
        Route::post('/', [LeaveAccrualController::class, 'store'])->name('store')
            ->middleware('hrmac:hrm.leaves.leave-accrual.create');
        Route::post('/process', [LeaveAccrualController::class, 'processAccruals'])->name('process')
            ->middleware('hrmac:hrm.leaves.leave-accrual.run');
        Route::get('/history', [LeaveAccrualController::class, 'history'])->name('history');
        Route::post('/manual-adjustment', [LeaveAccrualController::class, 'manualAdjustment'])->name('manual-adjustment')
            ->middleware('hrmac:hrm.leaves.leave-accrual.update');
        Route::put('/{rule}', [LeaveAccrualController::class, 'update'])->name('update')
            ->middleware('hrmac:hrm.leaves.leave-accrual.update');
        Route::delete('/{rule}', [LeaveAccrualController::class, 'destroy'])->name('destroy')
            ->middleware('hrmac:hrm.leaves.leave-accrual.delete');
    });

    // Legacy Time Off routes (for backward compatibility)
    Route::middleware(['hrmac:hrm.time-off'])->group(function () {
        Route::get('/time-off-legacy', [TimeOffController::class, 'index'])->name('timeoff-legacy.index');
        Route::get('/time-off-legacy/calendar', [TimeOffController::class, 'calendar'])->name('timeoff-legacy.calendar');
        Route::get('/time-off-legacy/approvals', [TimeOffController::class, 'approvals'])->name('timeoff-legacy.approvals');
        Route::post('/time-off-legacy/{id}/approve', [TimeOffController::class, 'approve'])->name('timeoff-legacy.approve');
        Route::post('/time-off-legacy/{id}/reject', [TimeOffController::class, 'reject'])->name('timeoff-legacy.reject');
        Route::get('/time-off-legacy/reports', [TimeOffController::class, 'reports'])->name('timeoff-legacy.reports');
        Route::get('/time-off-legacy/settings', [TimeOffController::class, 'settings'])->name('timeoff-legacy.settings');
        Route::put('/time-off-legacy/settings', [TimeOffController::class, 'updateSettings'])->name('timeoff-legacy.settings.update');
    });

    // Employee Benefits Administration
    Route::middleware(['hrmac:hrm.employees.benefits'])->group(function () {
        Route::get('/benefits', [BenefitsController::class, 'index'])->name('benefits.index');
        Route::get('/benefits/stats', [BenefitsController::class, 'stats'])->name('benefits.stats');
        Route::get('/benefits/open-enrollment-periods', [BenefitsController::class, 'openEnrollmentPeriods'])->name('benefits.open-enrollment-periods.index');
        Route::get('/benefits/enrollments', [BenefitsController::class, 'enrollments'])->name('benefits.enrollments.index');
        Route::post('/benefits/enrollments', [BenefitsController::class, 'storeEnrollment'])->name('benefits.enrollments.store');
        Route::post('/benefits/enrollments/{id}/approve', [BenefitsController::class, 'approveEnrollment'])->name('benefits.enrollments.approve');
        Route::get('/benefits/create', [BenefitsController::class, 'create'])->name('benefits.create');
        Route::post('/benefits', [BenefitsController::class, 'store'])->name('benefits.store');
        Route::get('/benefits/{id}', [BenefitsController::class, 'show'])->name('benefits.show');
        Route::get('/benefits/{id}/edit', [BenefitsController::class, 'edit'])->name('benefits.edit');
        Route::put('/benefits/{id}', [BenefitsController::class, 'update'])->name('benefits.update');
        Route::delete('/benefits/{id}', [BenefitsController::class, 'destroy'])->name('benefits.destroy');

        // Employee Benefits
        Route::get('/employee-benefits/{employeeId}', [BenefitsController::class, 'employeeBenefits'])->name('employee.benefits.index');
        Route::post('/employee-benefits/{employeeId}', [BenefitsController::class, 'assignBenefit'])->name('employee.benefits.assign');
        Route::put('/employee-benefits/{employeeId}/{benefitId}', [BenefitsController::class, 'updateEmployeeBenefit'])->name('employee.benefits.update');
        Route::delete('/employee-benefits/{employeeId}/{benefitId}', [BenefitsController::class, 'removeEmployeeBenefit'])->name('employee.benefits.remove');
    });

    // Enhanced Time-off Management
    Route::middleware(['hrmac:hrm.time-off'])->group(function () {
        Route::get('/time-off', [TimeOffController::class, 'index'])->name('timeoff.index');
        Route::get('/time-off/calendar', [TimeOffController::class, 'calendar'])->name('timeoff.calendar');
        Route::get('/time-off/approvals', [TimeOffController::class, 'approvals'])->name('timeoff.approvals');
        Route::post('/time-off/approve/{id}', [TimeOffController::class, 'approve'])->name('timeoff.approve');
        Route::post('/time-off/reject/{id}', [TimeOffController::class, 'reject'])->name('timeoff.reject');
        Route::get('/time-off/reports', [TimeOffController::class, 'reports'])->name('timeoff.reports');
        Route::get('/time-off/settings', [TimeOffController::class, 'settings'])->name('timeoff.settings');
        Route::put('/time-off/settings', [TimeOffController::class, 'updateSettings'])->name('timeoff.settings.update');
    });

    // Workplace Health & Safety
    Route::middleware(['hrmac:hrm.safety'])->group(function () {
        Route::get('/safety', [WorkplaceSafetyController::class, 'index'])->name('safety.index');
        Route::get('/safety/stats', [WorkplaceSafetyController::class, 'stats'])->name('safety.stats');
        Route::get('/safety/incidents', [WorkplaceSafetyController::class, 'incidents'])->name('safety.incidents.index');
        Route::get('/safety/incidents/create', [WorkplaceSafetyController::class, 'createIncident'])->name('safety.incidents.create');
        Route::post('/safety/incidents', [WorkplaceSafetyController::class, 'storeIncident'])->name('safety.incidents.store');
        Route::get('/safety/incidents/{id}', [WorkplaceSafetyController::class, 'showIncident'])->name('safety.incidents.show');
        Route::put('/safety/incidents/{id}', [WorkplaceSafetyController::class, 'updateIncident'])->name('safety.incidents.update');
        Route::delete('/safety/incidents/{id}', [WorkplaceSafetyController::class, 'destroyIncident'])->name('safety.incidents.destroy');
        Route::post('/safety/incidents/{id}/resolve', [WorkplaceSafetyController::class, 'resolveIncident'])->name('safety.incidents.resolve');

        // Safety Inspections
        Route::get('/safety/inspections', [WorkplaceSafetyController::class, 'inspections'])->name('safety.inspections.index');
        Route::get('/safety/inspections/create', [WorkplaceSafetyController::class, 'createInspection'])->name('safety.inspections.create');
        Route::post('/safety/inspections', [WorkplaceSafetyController::class, 'storeInspection'])->name('safety.inspections.store');
        Route::get('/safety/inspections/{id}', [WorkplaceSafetyController::class, 'showInspection'])->name('safety.inspections.show');
        Route::put('/safety/inspections/{id}', [WorkplaceSafetyController::class, 'updateInspection'])->name('safety.inspections.update');
        Route::delete('/safety/inspections/{id}', [WorkplaceSafetyController::class, 'destroyInspection'])->name('safety.inspections.destroy');

        // Safety Training
        Route::get('/safety/training', [WorkplaceSafetyController::class, 'training'])->name('safety.training.index');
        Route::get('/safety/training/create', [WorkplaceSafetyController::class, 'createTraining'])->name('safety.training.create');
        Route::post('/safety/training', [WorkplaceSafetyController::class, 'storeTraining'])->name('safety.training.store');
        Route::get('/safety/training/{id}', [WorkplaceSafetyController::class, 'showTraining'])->name('safety.training.show');
        Route::put('/safety/training/{id}', [WorkplaceSafetyController::class, 'updateTraining'])->name('safety.training.update');
    });

    // HR Analytics & Reporting
    Route::middleware(['hrmac:hrm.hr-reports'])->group(function () {
        // Redirect /hr-analytics to /analytics for navigation consistency
        Route::get('/hr-analytics', fn () => redirect()->route('hrm.analytics.index'))->name('hr-analytics.index');
        Route::get('/analytics', [HrAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/attendance', [HrAnalyticsController::class, 'attendanceAnalytics'])->name('analytics.attendance');
        Route::get('/analytics/performance', [HrAnalyticsController::class, 'performanceAnalytics'])->name('analytics.performance');
        Route::get('/analytics/recruitment', [HrAnalyticsController::class, 'recruitmentAnalytics'])->name('analytics.recruitment');
        Route::get('/analytics/turnover', [HrAnalyticsController::class, 'turnoverAnalytics'])->name('analytics.turnover');
        Route::get('/analytics/training', [HrAnalyticsController::class, 'trainingAnalytics'])->name('analytics.training');
        Route::get('/analytics/reports', [HrAnalyticsController::class, 'reports'])->name('analytics.reports');
        Route::post('/analytics/reports/generate', [HrAnalyticsController::class, 'generateReport'])->name('analytics.reports.generate');
    });

    // HR Document Management
    Route::middleware(['hrmac:hrm.documents'])->group(function () {
        Route::get('/documents', [HrDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/create', [HrDocumentController::class, 'create'])->name('documents.create');
        Route::post('/documents', [HrDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{id}', [HrDocumentController::class, 'show'])->name('documents.show');
        Route::put('/documents/{id}', [HrDocumentController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{id}', [HrDocumentController::class, 'destroy'])->name('documents.destroy');

        // Document Categories
        Route::get('/document-categories', [HrDocumentController::class, 'categories'])->name('documents.categories.index');
        Route::post('/document-categories', [HrDocumentController::class, 'storeCategory'])->name('documents.categories.store');
        Route::put('/document-categories/{id}', [HrDocumentController::class, 'updateCategory'])->name('documents.categories.update');
        Route::delete('/document-categories/{id}', [HrDocumentController::class, 'destroyCategory'])->name('documents.categories.destroy');

        // Employee Documents
        Route::get('/employee-documents/{employeeId}', [HrDocumentController::class, 'employeeDocuments'])->name('employee.documents.index');
        Route::post('/employee-documents/{employeeId}', [HrDocumentController::class, 'storeEmployeeDocument'])->name('employee.documents.store');
        Route::get('/employee-documents/{employeeId}/{documentId}', [HrDocumentController::class, 'showEmployeeDocument'])->name('employee.documents.show');
        Route::delete('/employee-documents/{employeeId}/{documentId}', [HrDocumentController::class, 'destroyEmployeeDocument'])->name('employee.documents.destroy');
    });

    // Enhanced Employee Self-Service Portal
    Route::middleware(['hrmac:hrm.employees.self-service'])->group(function () {
        Route::get('/self-service', [EmployeeSelfServiceController::class, 'index'])->name('selfservice.index');
        Route::get('/self-service/profile', [EmployeeSelfServiceController::class, 'profile'])->name('selfservice.profile');
        Route::put('/self-service/profile', [EmployeeSelfServiceController::class, 'updateProfile'])->name('selfservice.profile.update');
        Route::get('/self-service/documents', [EmployeeSelfServiceController::class, 'documents'])->name('selfservice.documents');
        Route::get('/self-service/benefits', [EmployeeSelfServiceController::class, 'benefits'])->name('selfservice.benefits');
        Route::get('/self-service/benefits/open-enrollment', [BenefitsController::class, 'openEnrollment'])->name('selfservice.benefits.open-enrollment');
        Route::get('/self-service/benefits/open-enrollment/payload', [BenefitsController::class, 'selfServiceEnrollmentPayload'])->name('selfservice.benefits.open-enrollment.payload');
        Route::post('/self-service/benefits/open-enrollment', [BenefitsController::class, 'submitSelfServiceEnrollment'])->name('selfservice.benefits.open-enrollment.submit');
        Route::get('/self-service/time-off', [EmployeeSelfServiceController::class, 'timeOff'])->name('selfservice.timeoff');
        Route::post('/self-service/time-off', [EmployeeSelfServiceController::class, 'requestTimeOff'])->name('selfservice.timeoff.request');
        Route::get('/self-service/trainings', [EmployeeSelfServiceController::class, 'trainings'])->name('selfservice.trainings');
        Route::get('/self-service/payslips', [EmployeeSelfServiceController::class, 'payslips'])->name('selfservice.payslips');
        Route::get('/self-service/performance', [EmployeeSelfServiceController::class, 'performance'])->name('selfservice.performance');
        Route::get('/self-service/career-path', [EmployeeSelfServiceController::class, 'careerPath'])->name('selfservice.careerpath');
        Route::get('/self-service/personal-information', [EmployeeSelfServiceController::class, 'personalInformation'])->name('selfservice.personal-information');
        Route::put('/self-service/personal-information', [EmployeeSelfServiceController::class, 'updatePersonalInformation'])->name('selfservice.personal-information.update');
        Route::get('/self-service/bank-information', [EmployeeSelfServiceController::class, 'bankInformation'])->name('selfservice.bank-information');
        Route::put('/self-service/bank-information', [EmployeeSelfServiceController::class, 'updateBankInformation'])->name('selfservice.bank-information.update');
    });

    // Payroll Management System
    Route::middleware(['hrmac:hrm.payroll'])->group(function () {
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/structures', [PayrollController::class, 'structures'])->name('payroll.structures'); // Salary structures
        Route::get('/payroll/components', [PayrollController::class, 'components'])->name('payroll.components'); // Salary components
        Route::get('/payroll/run', [PayrollController::class, 'run'])->name('payroll.run'); // Payroll run
        Route::get('/payroll/payslips', [PayrollController::class, 'payslips'])->name('payroll.payslips'); // Payslips list
        Route::get('/payroll/tax', [PayrollController::class, 'taxSetup'])->name('payroll.tax'); // Tax setup
        Route::get('/payroll/declarations', [PayrollController::class, 'index'])->name('payroll.declarations'); // IT/Tax declarations
        Route::get('/payroll/loans', [PayrollController::class, 'index'])->name('payroll.loans'); // Loan & Advance management
        Route::get('/payroll/bank-file', [PayrollController::class, 'index'])->name('payroll.bank-file'); // Bank file generator
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{id}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/payroll/{id}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
        Route::put('/payroll/{id}', [PayrollController::class, 'update'])->name('payroll.update');
        Route::delete('/payroll/{id}', [PayrollController::class, 'destroy'])->name('payroll.destroy');

        // Process Payroll
        Route::post('/payroll/{id}/process', [PayrollController::class, 'processPayroll'])->name('payroll.process');

        // Bulk Operations
        Route::post('/payroll/bulk/generate', [PayrollController::class, 'bulkGenerate'])->name('payroll.bulk.generate');
        Route::post('/payroll/bulk/process', [PayrollController::class, 'bulkProcess'])->name('payroll.bulk.process');

        // Payslips
        Route::get('/payroll/{id}/payslip', [PayrollController::class, 'viewPayslip'])->name('payroll.payslip.view');
        Route::post('/payroll/{id}/payslip/generate', [PayrollController::class, 'generatePayslip'])->name('payroll.payslip.generate');
        Route::post('/payroll/payslips/bulk-generate', [PayrollController::class, 'bulkGeneratePayslips'])->name('payroll.payslips.bulk.generate');
        Route::get('/payroll/{id}/payslip/download', [PayrollController::class, 'downloadPayslip'])->name('payroll.payslip.download');
        Route::post('/payroll/{id}/payslip/email', [PayrollController::class, 'sendPayslipEmail'])->name('payroll.payslip.email');

        // Reports
        Route::get('/payroll/reports', [PayrollController::class, 'reports'])->name('payroll.reports');
        Route::post('/payroll/reports/monthly-summary', [PayrollController::class, 'monthlySummaryReport'])->name('payroll.reports.monthly');
        Route::post('/payroll/reports/tax', [PayrollController::class, 'taxReport'])->name('payroll.reports.tax');
        Route::post('/payroll/reports/bank-transfer', [PayrollController::class, 'bankTransferReport'])->name('payroll.reports.bank');
        Route::post('/payroll/reports/statutory', [PayrollController::class, 'statutoryReport'])->name('payroll.reports.statutory');
    });

    // Employee Management - Core CRUD operations (legacy JSON API — broad gate)
    Route::middleware(['hrmac:hrm.employees'])->group(function () {
        Route::get('/employees/paginate', [EmployeeController::class, 'paginate'])->name('employees.paginate');
        Route::get('/employees/stats', [EmployeeController::class, 'stats'])->name('employees.stats');
        Route::get('/employees/list', [EmployeeController::class, 'list'])->name('employees.list');
        Route::get('/employees/pending-onboarding', [EmployeeController::class, 'getPendingOnboarding'])->name('employees.pending-onboarding');
        Route::get('/employees/onboarding-analytics', [EmployeeController::class, 'getOnboardingAnalytics'])->name('employees.onboarding-analytics');
        Route::post('/employees/onboard', [EmployeeController::class, 'onboard'])->name('employees.onboard');
        Route::post('/employees/onboard-bulk', [EmployeeController::class, 'bulkOnboard'])->name('employees.onboard-bulk');
    });

    // Employee Management — Inertia CRUD with granular HRMAC gates
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->middleware('hrmac:hrm.employees.list.view')->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])
            ->middleware('hrmac:hrm.employees.list.edit')->name('create');
        Route::post('/', [EmployeeController::class, 'store'])
            ->middleware('hrmac:hrm.employees.list.edit')->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])
            ->middleware('hrmac:hrm.employees.detail.view')->name('show');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])
            ->middleware('hrmac:hrm.employees.detail.edit')->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])
            ->middleware('hrmac:hrm.employees.detail.edit')->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])
            ->middleware('hrmac:hrm.employees.detail.edit')->name('destroy');
        Route::post('/{employee}/restore', [EmployeeController::class, 'restore'])
            ->middleware('hrmac:hrm.employees.detail.edit')->name('restore')->withTrashed();
    });

    // Employee Profile Management (Bank Details, Emergency Contacts)
    Route::middleware(['hrmac:hrm.employees'])->prefix('employees/{user}')->name('employees.')->group(function () {
        // Profile Overview
        Route::get('/profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [EmployeeProfileController::class, 'update'])->name('profile.update');

        // Bank Details
        Route::get('/bank-details', [EmployeeProfileController::class, 'getBankDetails'])->name('bank-details.show');
        Route::post('/bank-details/verify', [EmployeeProfileController::class, 'verifyBankDetails'])
            ->middleware('hrmac:hrm.employees.verify')
            ->name('bank-details.verify');

        // Emergency Contacts
        Route::get('/emergency-contacts', [EmployeeProfileController::class, 'getEmergencyContacts'])->name('emergency-contacts.index');
        Route::post('/emergency-contacts', [EmployeeProfileController::class, 'addEmergencyContact'])->name('emergency-contacts.store');
        Route::delete('/emergency-contacts/{contact}', [EmployeeProfileController::class, 'deleteEmergencyContact'])->name('emergency-contacts.destroy');

        // Document Management
        Route::get('/documents', [EmployeeDocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}', [EmployeeDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
        Route::put('/documents/{document}', [EmployeeDocumentController::class, 'update'])->name('documents.update');
        Route::delete('/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/documents/{document}/verify', [EmployeeDocumentController::class, 'verify'])
            ->middleware('hrmac:hrm.documents.verify')
            ->name('documents.verify');
    });

    // Document Expiry Dashboard (HR Admin)
    Route::middleware(['hrmac:hrm.documents'])->group(function () {
        Route::get('/documents/expiring', [EmployeeDocumentController::class, 'expiring'])->name('documents.expiring');
    });

    // Salary Structure Management
    Route::middleware(['hrmac:hrm.payroll'])->prefix('salary-structure')->name('salary-structure.')->group(function () {
        Route::get('/', [SalaryStructureController::class, 'index'])->name('index');
        Route::post('/', [SalaryStructureController::class, 'store'])->name('store');
        Route::put('/{id}', [SalaryStructureController::class, 'update'])->name('update');
        Route::delete('/{id}', [SalaryStructureController::class, 'destroy'])->name('destroy');

        // Employee Salary Management
        Route::get('/employee/{employeeId}', [SalaryStructureController::class, 'employeeSalary'])->name('employee.salary');
        Route::post('/assign', [SalaryStructureController::class, 'assignToEmployee'])->name('assign');
        Route::post('/calculate-preview', [SalaryStructureController::class, 'calculatePreview'])->name('calculate-preview');
    });

    // Managers for dropdowns
    Route::get('/managers', [ManagersController::class, 'index'])->name('managers.list');

    // Employee self-service routes
    Route::middleware(['hrmac:hrm.time-off.own-leave'])->group(function () {
        Route::get('/leaves-employee', [LeaveController::class, 'index1'])->name('leaves-employee');
        Route::post('/leave-add', [LeaveController::class, 'create'])->name('leave-add');
        Route::post('/leave-update', [LeaveController::class, 'update'])->name('leave-update');
        Route::delete('/leave-delete', [LeaveController::class, 'delete'])->name('leave-delete');
        Route::get('/leaves-paginate', [LeaveController::class, 'paginate'])->name('leaves.paginate');
        Route::get('/leaves-stats', [LeaveController::class, 'stats'])->name('leaves.stats');
        Route::get('/leaves/balances', [LeaveController::class, 'getBalances'])->name('leaves.balances');
    });

    // Attendance self-service routes
    Route::middleware(['hrmac:hrm.attendance.own-attendance'])->group(function () {
        Route::get('/attendance-employee', [AttendanceController::class, 'index2'])->name('attendance-employee');
        Route::get('/attendance/attendance-today', [AttendanceController::class, 'getCurrentUserPunch'])->name('attendance.current-user-punch');
        Route::get('/get-current-user-attendance-for-date', [AttendanceController::class, 'getCurrentUserAttendanceForDate'])->name('getCurrentUserAttendanceForDate');
        Route::get('/attendance/calendar-data', [AttendanceController::class, 'getCalendarData'])->name('attendance.calendar-data');
    });

    // Expenses self-service route
    Route::middleware(['hrmac:hrm.expenses'])->group(function () {
        Route::get('/my-expenses', [ExpenseClaimController::class, 'myExpenses'])->name('my-expenses');
    });

    // Punch routes - require punch permission
    Route::middleware(['hrmac:hrm.attendance.own-attendance,punch'])->group(function () {
        Route::post('/punchIn', [AttendanceController::class, 'punchIn'])->name('punchIn');
        Route::post('/punchOut', [AttendanceController::class, 'punchOut'])->name('punchOut');
        Route::post('/attendance/punch', [AttendanceController::class, 'punch'])->name('attendance.punch');
    });

    // General access routes (available to all authenticated users)
    Route::get('/attendance/export/excel', [AttendanceController::class, 'exportExcel'])->name('attendance.exportExcel');
    Route::get('/admin/attendance/export/excel', [AttendanceController::class, 'exportAdminExcel'])->name('attendance.exportAdminExcel');
    Route::get('/admin/attendance/export/pdf', [AttendanceController::class, 'exportAdminPdf'])->name('attendance.exportAdminPdf');
    Route::get('/attendance/export/pdf', [AttendanceController::class, 'exportPdf'])->name('attendance.exportPdf');
    Route::get('/get-all-users-attendance-for-date', [AttendanceController::class, 'getAllUsersAttendanceForDate'])->name('getAllUsersAttendanceForDate');
    Route::get('/get-present-users-for-date', [AttendanceController::class, 'getPresentUsersForDate'])->name('getPresentUsersForDate');
    Route::get('/get-absent-users-for-date', [AttendanceController::class, 'getAbsentUsersForDate'])->name('getAbsentUsersForDate');
    Route::get('/get-client-ip', [AttendanceController::class, 'getClientIp'])->name('getClientIp');

    // Holiday routes (Legacy - redirects to Time Off Management)
    Route::middleware(['hrmac:hrm.time-off.holidays'])->group(function () {
        Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays');
        Route::post('/holidays-add', [HolidayController::class, 'create'])->name('holidays-add');
        Route::delete('/holidays-delete', [HolidayController::class, 'delete'])->name('holidays-delete');

        // Legacy redirect for old holiday routes
        Route::get('/holidays-legacy', [HolidayController::class, 'index'])->name('holidays-legacy');
    });

    // Profile Routes - own profile access
    Route::middleware(['hrmac:core.my-profile'])->group(function () {
        Route::get('/profile/{user}', [ProfileController::class, 'index'])->name('profile');
        Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile/delete', [ProfileController::class, 'delete'])->name('profile.delete');

        // Profile Image Routes - User's profile image (managed in Core, accessible from HRM)
        // These routes manage the User's identity/authentication profile image
        Route::post('/profile/image/upload', [ProfileImageController::class, 'upload'])->name('profile.image.upload');
        Route::delete('/profile/image/remove', [ProfileImageController::class, 'remove'])->name('profile.image.remove');

        // New API endpoints for enhanced profile functionality (consistent with other modules)
        Route::get('/profile/{user}/stats', [ProfileController::class, 'stats'])->name('profile.stats');
        Route::get('/profile/{user}/export', [ProfileController::class, 'export'])->name('profile.export');
        Route::post('/profile/{user}/track-view', [ProfileController::class, 'trackView'])->name('profile.trackView');

        // Education Routes:
        Route::post('/education/update', [EducationController::class, 'update'])->name('education.update');
        Route::delete('/education/delete', [EducationController::class, 'delete'])->name('education.delete');

        // Experience Routes:
        Route::post('/experience/update', [ExperienceController::class, 'update'])->name('experience.update');
        Route::delete('/experience/delete', [ExperienceController::class, 'delete'])->name('experience.delete');
    });

    // ========================================================================
    // EMPLOYEE IMAGE ROUTES (Separate from User Profile Image)
    // ========================================================================
    // These routes manage the Employee's HR image (badges, org charts, ID cards)
    // This is SEPARATE from the User's profile image which is for identity/auth
    Route::middleware(['hrmac:hrm.employees'])->prefix('employees')->name('employees.')->group(function () {
        Route::get('/{employee}/image', [EmployeeImageController::class, 'show'])->name('image.show');
        Route::post('/image/upload', [EmployeeImageController::class, 'upload'])->name('image.upload');
        Route::delete('/image/remove', [EmployeeImageController::class, 'remove'])->name('image.remove');
    });

    // Leave management routes
    Route::middleware(['hrmac:hrm.time-off'])->group(function () {
        Route::get('/leaves', [LeaveController::class, 'index2'])->name('leaves');
        Route::get('/leave-summary', [LeaveController::class, 'leaveSummary'])->name('leave-summary');
        Route::post('/leave-update-status', [LeaveController::class, 'updateStatus'])->name('leave-update-status');

        // Leave summary export routes
        Route::get('/leave-summary/export/excel', [LeaveController::class, 'exportExcel'])->name('leave.summary.exportExcel');
        Route::get('/leave-summary/export/pdf', [LeaveController::class, 'exportPdf'])->name('leave.summary.exportPdf');

        // Leave analytics
        Route::get('/leaves/analytics', [LeaveController::class, 'getAnalytics'])->name('leaves.analytics');

        // Approval workflow
        Route::get('/leaves/pending-approvals', [LeaveController::class, 'pendingApprovals'])->name('leaves.pending-approvals');
    });

    // Leave bulk operations (admin only)
    Route::middleware(['hrmac:hrm.time-off.leave-management,approve'])->group(function () {
        Route::post('/leaves/bulk-approve', [LeaveController::class, 'bulkApprove'])->name('leaves.bulk-approve');
        Route::post('/leaves/bulk-reject', [LeaveController::class, 'bulkReject'])->name('leaves.bulk-reject');

        // Approval workflow actions
        Route::post('/leaves/{id}/approve', [LeaveController::class, 'approveLeave'])->name('leaves.approve');
        Route::post('/leaves/{id}/reject', [LeaveController::class, 'rejectLeave'])->name('leaves.reject');
    });

    // Bulk leave creation routes
    Route::middleware(['hrmac:hrm.time-off.leave-management,create'])->group(function () {
        Route::post('/leaves/bulk/validate', [BulkLeaveController::class, 'validateDates'])->name('leaves.bulk.validate');
        Route::post('/leaves/bulk', [BulkLeaveController::class, 'store'])->name('leaves.bulk.store');
        Route::get('/leaves/bulk/leave-types', [BulkLeaveController::class, 'getLeaveTypes'])->name('leaves.bulk.leave-types');
        Route::get('/leaves/bulk/calendar-data', [BulkLeaveController::class, 'getCalendarData'])->name('leaves.bulk.calendar-data');
    });

    // Bulk leave deletion route
    Route::middleware(['hrmac:hrm.time-off.leave-management,delete'])->group(function () {
        Route::delete('/leaves/bulk', [BulkLeaveController::class, 'bulkDelete'])->name('leaves.bulk.delete');
    });

    // Leave settings routes
    Route::middleware(['hrmac:hrm.time-off.leave-settings'])->group(function () {
        Route::get('/leave-settings', [LeaveSettingController::class, 'index'])->name('leave-settings');
        Route::get('/leave-types', [LeaveSettingController::class, 'index'])->name('leave-types'); // Alias route
        Route::post('/add-leave-type', [LeaveSettingController::class, 'store'])->name('add-leave-type');
        Route::put('/update-leave-type/{id}', [LeaveSettingController::class, 'update'])->name('update-leave-type');
        Route::delete('/delete-leave-type/{id}', [LeaveSettingController::class, 'destroy'])->name('delete-leave-type');
    });

    // Legacy alias removed — replaced by hrm.employees.index in the granular HRMAC group above

    // Department management routes - Departments is under hrm.employees.departments in navigation
    Route::middleware(['hrmac:hrm.employees.departments'])->get('/departments', [DepartmentController::class, 'index'])->name('departments');
    Route::middleware(['hrmac:hrm.employees.departments'])->get('/api/departments', [DepartmentController::class, 'getDepartments'])->name('api.departments');
    Route::middleware(['hrmac:hrm.employees.departments'])->get('/departments/stats', [DepartmentController::class, 'getStats'])->name('departments.stats');
    Route::middleware(['hrmac:hrm.employees.departments,department-list,create'])->post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::middleware(['hrmac:hrm.employees.departments'])->get('/departments/{id}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::middleware(['hrmac:hrm.employees.departments,department-list,update'])->put('/departments/{id}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::middleware(['hrmac:hrm.employees.departments,department-list,delete'])->delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('departments.delete');
    Route::middleware(['hrmac:hrm.employees.departments,department-list,update'])->put('/users/{id}/department', [DepartmentController::class, 'updateUserDepartment'])->name('users.update-department');

    // Organization Chart route
    Route::middleware(['hrmac:hrm.employees.departments'])->get('/org-chart', [DepartmentController::class, 'orgChart'])->name('org-chart');

    // Route::middleware(['hrmac:hrm.organization'])->get('/jurisdiction', [JurisdictionController::class, 'index'])->name('jurisdiction'); // TODO: Move to compliance package

    // Holiday management routes
    Route::middleware(['hrmac:hrm.time-off.holidays,holiday-list,create'])->post('/holiday-add', [HolidayController::class, 'create'])->name('holiday-add');
    Route::middleware(['hrmac:hrm.time-off.holidays,holiday-list,delete'])->delete('/holiday-delete', [HolidayController::class, 'delete'])->name('holiday-delete');

    // Attendance management routes
    Route::middleware(['hrmac:hrm.attendance'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index1'])->name('attendance'); // Main attendance page alias
        Route::get('/attendances', [AttendanceController::class, 'index1'])->name('attendances');
        Route::get('/attendance/daily', [AttendanceController::class, 'index1'])->name('attendance.daily'); // Daily attendance view (alias)
        Route::get('/attendance/calendar', [AttendanceController::class, 'index1'])->name('attendance.calendar'); // Monthly calendar view
        Route::get('/attendance/logs', [AttendanceController::class, 'index1'])->name('attendance.logs'); // Attendance logs view
        Route::get('/attendance/adjustments', [AttendanceController::class, 'index1'])->name('attendance.adjustments'); // Adjustment requests
        Route::get('/attendance/rules', [AttendanceController::class, 'index1'])->name('attendance.rules'); // Device/IP/Geo rules
        Route::get('/shifts', [AttendanceController::class, 'index1'])->name('shifts'); // Shift scheduling
        Route::get('/overtime/rules', [AttendanceController::class, 'index1'])->name('overtime.rules'); // Overtime rules
        Route::get('/my-attendance', [AttendanceController::class, 'index2'])->name('my-attendance'); // Employee attendance view
        Route::get('/timesheet', [AttendanceController::class, 'index3'])->name('timesheet'); // New TimeSheet page route
        Route::get('/attendances-admin-paginate', [AttendanceController::class, 'paginate'])->name('attendancesAdmin.paginate');
        Route::get('/attendance/locations-today', [AttendanceController::class, 'getUserLocationsForDate'])->name('getUserLocationsForDate');
        Route::get('/admin/get-present-users-for-date', [AttendanceController::class, 'getPresentUsersForDate'])->name('admin.getPresentUsersForDate');
        Route::get('/admin/get-absent-users-for-date', [AttendanceController::class, 'getAbsentUsersForDate'])->name('admin.getAbsentUsersForDate');
        Route::get('/attendance/monthly-stats', [AttendanceController::class, 'getMonthlyAttendanceStats'])->name('attendance.monthlyStats');
        // Location and timesheet update check routes
        Route::get('check-user-locations-updates/{date}', [AttendanceController::class, 'checkForLocationUpdates'])
            ->name('check-user-locations-updates');
        Route::get('check-timesheet-updates/{date}/{month?}', [AttendanceController::class, 'checkTimesheetUpdates'])
            ->name('check-timesheet-updates');
    });

    // Attendance management routes (admin actions)
    Route::middleware(['hrmac:hrm.attendance.attendance-list,manage'])->group(function () {
        Route::post('/attendance/mark-as-present', [AttendanceController::class, 'markAsPresent'])->name('attendance.mark-as-present');
        Route::post('/attendance/bulk-mark-as-present', [AttendanceController::class, 'bulkMarkAsPresent'])->name('attendance.bulk-mark-as-present');
    });

    // Employee attendance stats route
    Route::middleware(['hrmac:hrm.attendance.own-attendance'])->group(function () {
        Route::get('/attendance/my-monthly-stats', [AttendanceController::class, 'getMonthlyAttendanceStats'])->name('attendance.myMonthlyStats');
    });

    Route::middleware(['hrmac:hrm.attendance.attendance-settings'])->group(function () {
        Route::get('/settings/attendance', [AttendanceSettingController::class, 'index'])->name('attendance-settings.index');
        Route::post('/settings/attendance', [AttendanceSettingController::class, 'updateSettings'])->name('attendance-settings.update');
        Route::post('settings/attendance-type', [AttendanceSettingController::class, 'storeType'])->name('attendance-types.store');
        Route::put('settings/attendance-type/{id}', [AttendanceSettingController::class, 'updateType'])->name('attendance-types.update');
        Route::delete('settings/attendance-type/{id}', [AttendanceSettingController::class, 'destroyType'])->name('attendance-types.destroy');

        // Multi-config management routes
        Route::post('settings/attendance-type/{id}/add-item', [AttendanceSettingController::class, 'addConfigItem'])->name('attendance-types.addItem');
        Route::delete('settings/attendance-type/{id}/remove-item', [AttendanceSettingController::class, 'removeConfigItem'])->name('attendance-types.removeItem');
        Route::post('settings/attendance-type/{id}/generate-qr', [AttendanceSettingController::class, 'generateQrCode'])->name('attendance-types.generateQr');
    });

    // Shift Marketplace (Shift Swaps & Open Pickups)
    Route::prefix('attendance/shift-marketplace')->name('attendance.shift-marketplace.')->group(function () {
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.view'])->get('/', [ShiftMarketplaceController::class, 'index'])->name('index');
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.create'])->post('/', [ShiftMarketplaceController::class, 'store'])->name('store');
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.create'])->post('{swap}/accept', [ShiftMarketplaceController::class, 'accept'])->name('accept');
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.approve'])->post('{swap}/approve', [ShiftMarketplaceController::class, 'approve'])->name('approve');
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.reject'])->post('{swap}/reject', [ShiftMarketplaceController::class, 'reject'])->name('reject');
        Route::middleware(['hrmac:hrm.attendance.shift-marketplace.create'])->post('{swap}/cancel', [ShiftMarketplaceController::class, 'cancel'])->name('cancel');
        Route::get('{swap}', [ShiftMarketplaceController::class, 'show'])->name('show');
    });

    // HR Module Settings - Redirect /settings to default settings page
    Route::middleware(['auth', 'verified', 'hrmac:hrm.settings'])->get('/settings', fn () => redirect()->route('hrm.settings.hr.onboarding'))->name('settings.index');

    // HR Module Settings
    Route::prefix('settings/hr')->middleware(['auth', 'verified'])->group(function () {
        Route::middleware(['hrmac:hrm.settings.onboarding-settings'])->get('/onboarding', [HrmSettingController::class, 'index'])->name('settings.hr.onboarding');
        Route::middleware(['hrmac:hrm.settings.skills-settings'])->get('/skills', [HrmSettingController::class, 'index'])->name('settings.hr.skills');
        Route::middleware(['hrmac:hrm.settings.benefits-settings'])->get('/benefits', [HrmSettingController::class, 'index'])->name('settings.hr.benefits');
        Route::middleware(['hrmac:hrm.settings.safety-settings'])->get('/safety', [HrmSettingController::class, 'index'])->name('settings.hr.safety');
        Route::middleware(['hrmac:hrm.settings.documents-settings'])->get('/documents', [HrmSettingController::class, 'index'])->name('settings.hr.documents');

        // Update routes
        Route::middleware(['hrmac:hrm.settings.onboarding-settings,setting-list,update'])->post('/onboarding', [HrmSettingController::class, 'updateOnboardingSettings'])->name('settings.hr.onboarding.update');
        Route::middleware(['hrmac:hrm.settings.skills-settings,setting-list,update'])->post('/skills', [HrmSettingController::class, 'updateSkillsSettings'])->name('settings.hr.skills.update');
        Route::middleware(['hrmac:hrm.settings.benefits-settings,setting-list,update'])->post('/benefits', [HrmSettingController::class, 'updateBenefitsSettings'])->name('settings.hr.benefits.update');
        Route::middleware(['hrmac:hrm.settings.safety-settings,setting-list,update'])->post('/safety', [HrmSettingController::class, 'updateSafetySettings'])->name('settings.hr.safety.update');
        Route::middleware(['hrmac:hrm.settings.documents-settings,setting-list,update'])->post('/documents', [HrmSettingController::class, 'updateDocumentSettings'])->name('settings.hr.documents.update');
    });

    // Designation Management - Designations is under hrm.employees.designations in navigation
    Route::middleware(['hrmac:hrm.employees.designations'])->group(function () {
        // Initial page render (Inertia)
        Route::get('/designations', [DesignationController::class, 'index'])->name('designations.index');
        // API data fetch (JSON)
        Route::get('/designations/json', [DesignationController::class, 'getDesignations'])->name('designations.json');
        // Stats endpoint for frontend analytics
        Route::get('/designations/stats', [DesignationController::class, 'stats'])->name('designations.stats');
        Route::post('/designations', [DesignationController::class, 'store'])->name('designations.store');
        Route::get('/designations/{id}', [DesignationController::class, 'show'])->name('designations.show');
        Route::put('/designations/{id}', [DesignationController::class, 'update'])->name('designations.update');
        Route::delete('/designations/{id}', [DesignationController::class, 'destroy'])->name('designations.destroy');
        // For dropdowns and API
        Route::get('/designations/list', [DesignationController::class, 'list'])->name('designations.list');
    });

    // Expense Claims Management
    Route::middleware(['hrmac:hrm.expenses'])->prefix('expenses')->name('expenses.')->group(function () {
        // Main index page (Inertia)
        Route::get('/', [ExpenseClaimController::class, 'index'])->name('index');
        Route::get('/my-claims', [ExpenseClaimController::class, 'myExpensesPaginate'])->name('my-claims');
        Route::get('/categories', [ExpenseCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/list', [ExpenseCategoryController::class, 'list'])->name('categories.list');
        Route::get('/categories/paginate', [ExpenseCategoryController::class, 'paginate'])->name('categories.paginate');
        Route::get('/categories/stats', [ExpenseCategoryController::class, 'stats'])->name('categories.stats');
        Route::post('/categories', [ExpenseCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{id}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [ExpenseCategoryController::class, 'destroy'])->name('categories.destroy');
        // API endpoints for data fetching
        Route::get('/paginate', [ExpenseClaimController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [ExpenseClaimController::class, 'stats'])->name('stats');
        // CRUD operations
        Route::post('/', [ExpenseClaimController::class, 'store'])->name('store');
        Route::put('/{id}', [ExpenseClaimController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExpenseClaimController::class, 'destroy'])->name('destroy');
        // Workflow actions
        Route::post('/{id}/approve', [ExpenseClaimController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [ExpenseClaimController::class, 'reject'])->name('reject');
    });

    // Asset Management
    Route::middleware(['hrmac:hrm.assets'])->prefix('assets')->name('assets.')->group(function () {
        // Main index page (Inertia)
        Route::get('/', [AssetController::class, 'index'])->name('index');
        Route::get('/allocations', [AssetController::class, 'allocations'])->name('allocations');
        Route::get('/allocations/index', [AssetController::class, 'allocationsIndex'])->name('allocations.index');
        Route::get('/categories', [AssetCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/list', [AssetCategoryController::class, 'list'])->name('categories.list');
        // API endpoints for data fetching
        Route::get('/paginate', [AssetController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [AssetController::class, 'stats'])->name('stats');
        // CRUD operations
        Route::post('/', [AssetController::class, 'store'])->name('store');
        Route::put('/{id}', [AssetController::class, 'update'])->name('update');
        Route::delete('/{id}', [AssetController::class, 'destroy'])->name('destroy');
        // Asset allocation workflow
        Route::post('/{id}/allocate', [AssetController::class, 'allocate'])->name('allocate');
        Route::post('/{id}/return', [AssetController::class, 'returnAsset'])->name('return');
    });

    // Disciplinary Management
    Route::middleware(['hrmac:hrm.disciplinary'])->prefix('disciplinary')->name('disciplinary.')->group(function () {
        // Index redirect - redirects /disciplinary to /disciplinary/cases
        Route::get('/', fn () => redirect()->route('hrm.disciplinary.cases.index'))->name('index');
        // Main index page (Inertia)
        Route::get('/cases', [DisciplinaryCaseController::class, 'index'])->name('cases.index');
        // API endpoints for data fetching
        Route::get('/cases/paginate', [DisciplinaryCaseController::class, 'paginate'])->name('cases.paginate');
        Route::get('/cases/stats', [DisciplinaryCaseController::class, 'stats'])->name('cases.stats');
        // CRUD operations
        Route::post('/cases', [DisciplinaryCaseController::class, 'store'])->name('cases.store');
        Route::put('/cases/{id}', [DisciplinaryCaseController::class, 'update'])->name('cases.update');
        Route::delete('/cases/{id}', [DisciplinaryCaseController::class, 'destroy'])->name('cases.destroy');
        // Workflow actions
        Route::post('/cases/{id}/start-investigation', [DisciplinaryCaseController::class, 'startInvestigation'])->name('cases.start-investigation');
        Route::post('/cases/{id}/take-action', [DisciplinaryCaseController::class, 'takeAction'])->name('cases.take-action');
        Route::post('/cases/{id}/close', [DisciplinaryCaseController::class, 'close'])->name('cases.close');
        Route::post('/cases/{id}/appeal', [DisciplinaryCaseController::class, 'appeal'])->name('cases.appeal');

        // Warnings
        Route::get('/warnings', [WarningController::class, 'index'])->name('warnings.index');
        Route::get('/warnings/data', [WarningController::class, 'getData'])->name('warnings.data');
        Route::post('/warnings', [WarningController::class, 'store'])->name('warnings.store');
        Route::put('/warnings/{id}', [WarningController::class, 'update'])->name('warnings.update');
        Route::delete('/warnings/{id}', [WarningController::class, 'destroy'])->name('warnings.destroy');

        // Action Types
        Route::get('/action-types', [ActionTypeController::class, 'index'])->name('action-types.index');
        Route::get('/action-types/data', [ActionTypeController::class, 'getData'])->name('action-types.data');
        Route::post('/action-types', [ActionTypeController::class, 'store'])->name('action-types.store');
        Route::put('/action-types/{id}', [ActionTypeController::class, 'update'])->name('action-types.update');
        Route::delete('/action-types/{id}', [ActionTypeController::class, 'destroy'])->name('action-types.destroy');
    });

    Route::get('/api/designations/list', function () {
        return response()->json(Designation::select('id', 'title as name')->get());
    })->name('api.designations.list');

    Route::get('/api/departments/list', function () {
        return response()->json(Department::select('id', 'name')->get());
    })->name('departments.list');

    // =========================================================================
    // AI Analytics - Next-Generation Predictive HR Intelligence
    // =========================================================================
    Route::middleware(['hrmac:hrm.ai-analytics'])->prefix('ai-analytics')->name('ai-analytics.')->group(function () {
        // Dashboard
        Route::get('/', [AIAnalyticsController::class, 'dashboard'])->name('dashboard');

        // Attrition Predictions
        Route::get('/attrition', [AIAnalyticsController::class, 'attritionPredictions'])->name('attrition');

        // Burnout Risk Analysis
        Route::get('/burnout', [AIAnalyticsController::class, 'burnoutRisks'])->name('burnout');

        // Behavioral Anomaly Detection
        Route::get('/anomalies', [AIAnalyticsController::class, 'anomalies'])->name('anomalies');
        Route::post('/anomalies/{anomaly}/resolve', [AIAnalyticsController::class, 'resolveAnomaly'])->name('anomalies.resolve');

        // Talent Mobility & Internal Recommendations
        Route::get('/talent-mobility', [AIAnalyticsController::class, 'talentMobility'])->name('talent-mobility');

        // Engagement & Sentiment Analytics
        Route::get('/engagement', [AIAnalyticsController::class, 'engagementSentiment'])->name('engagement');

        // Employee Net Promoter Score dashboard
        Route::get('/enps', [AIAnalyticsController::class, 'enpsDashboard'])->name('enps');

        // AI Insights (cross-cutting alerts)
        Route::get('/insights', [AIAnalyticsController::class, 'insights'])->name('insights');
        Route::post('/insights/{insight}/resolve', [AIAnalyticsController::class, 'resolveInsight'])->name('insights.resolve');

        // Employee Risk Profile (detailed view)
        Route::get('/employees/{employee}/risk-profile', [AIAnalyticsController::class, 'employeeRiskProfile'])->name('employee-risk-profile');

        // Run predictions (admin action)
        Route::post('/run-predictions', [AIAnalyticsController::class, 'runPredictions'])->name('run-predictions');
    });

    // Wellbeing & Burnout Monitor
    Route::middleware(['hrmac:hrm.ai-analytics.wellbeing-monitor.view'])->group(function () {
        Route::get('/wellbeing', [WellbeingController::class, 'index'])->name('wellbeing.index');
        Route::get('/wellbeing/{id}/detail', [WellbeingController::class, 'employeeDetail'])->name('wellbeing.employee-detail');
    });
    Route::middleware(['hrmac:hrm.ai-analytics.wellbeing-monitor.manage'])->group(function () {
        Route::post('/wellbeing/{id}/intervention', [WellbeingController::class, 'markIntervention'])->name('wellbeing.intervention');
    });

    // =========================================================================
    // Succession Planning - Talent Pipeline & Critical Position Management
    // =========================================================================
    Route::middleware(['hrmac:hrm.succession-planning'])->prefix('succession-planning')->name('succession.')->group(function () {
        Route::get('/', [SuccessionPlanningController::class, 'index'])->name('index');
        Route::get('/paginate', [SuccessionPlanningController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [SuccessionPlanningController::class, 'stats'])->name('stats');
        Route::post('/', [SuccessionPlanningController::class, 'store'])->name('store');
        Route::get('/pipeline-report', [SuccessionPlanningController::class, 'pipelineReport'])->name('pipeline-report');
        Route::get('/{id}', [SuccessionPlanningController::class, 'show'])->name('show');
        Route::put('/{id}', [SuccessionPlanningController::class, 'update'])->name('update');
        Route::delete('/{id}', [SuccessionPlanningController::class, 'destroy'])->name('destroy');

        // Candidates
        Route::get('/{planId}/potential-candidates', [SuccessionPlanningController::class, 'getPotentialCandidates'])->name('potential-candidates');
        Route::post('/{planId}/candidates', [SuccessionPlanningController::class, 'addCandidate'])->name('candidates.store');
        Route::put('/{planId}/candidates/{candidateId}', [SuccessionPlanningController::class, 'updateCandidate'])->name('candidates.update');
        Route::delete('/{planId}/candidates/{candidateId}', [SuccessionPlanningController::class, 'removeCandidate'])->name('candidates.destroy');
    });

    // =========================================================================
    // Overtime Management - Hours Tracking, Approval & Compensation
    // =========================================================================
    Route::middleware(['hrmac:hrm.overtime'])->prefix('overtime')->name('overtime.')->group(function () {
        Route::get('/', [OvertimeController::class, 'index'])->name('index');
        Route::get('/paginate', [OvertimeController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [OvertimeController::class, 'stats'])->name('stats');
        Route::post('/', [OvertimeController::class, 'store'])->name('store');
        Route::put('/{id}', [OvertimeController::class, 'update'])->name('update');
        Route::delete('/{id}', [OvertimeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [OvertimeController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [OvertimeController::class, 'reject'])->name('reject');
        Route::post('/bulk-approve', [OvertimeController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/{id}/compensate', [OvertimeController::class, 'markCompensated'])->name('compensate');
        Route::get('/employees/{employeeId}/summary', [OvertimeController::class, 'employeeSummary'])->name('employee-summary');
    });

    // =========================================================================
    // Grievance Management - Employee Complaints & Resolution
    // =========================================================================
    Route::middleware(['hrmac:hrm.grievances'])->prefix('grievances')->name('grievances.')->group(function () {
        Route::get('/', [GrievanceController::class, 'index'])->name('index');
        Route::get('/paginate', [GrievanceController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [GrievanceController::class, 'stats'])->name('stats');
        Route::get('/categories', [GrievanceController::class, 'categories'])->name('categories');
        Route::post('/categories', [GrievanceController::class, 'storeCategory'])->name('categories.store');
        Route::post('/', [GrievanceController::class, 'store'])->name('store');
        Route::get('/{id}', [GrievanceController::class, 'show'])->name('show');
        Route::put('/{id}', [GrievanceController::class, 'update'])->name('update');
        Route::delete('/{id}', [GrievanceController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/assign', [GrievanceController::class, 'assign'])->name('assign');
        Route::post('/{id}/investigate', [GrievanceController::class, 'startInvestigation'])->name('investigate');
        Route::post('/{id}/resolve', [GrievanceController::class, 'resolve'])->name('resolve');
        Route::post('/{id}/close', [GrievanceController::class, 'close'])->name('close');
        Route::post('/{id}/notes', [GrievanceController::class, 'addNote'])->name('notes.store');
    });

    // =========================================================================
    // Exit Interviews - Offboarding Feedback & Analytics
    // =========================================================================
    Route::middleware(['hrmac:hrm.exit-interviews'])->prefix('exit-interviews')->name('exit-interviews.')->group(function () {
        Route::get('/', [ExitInterviewController::class, 'index'])->name('index');
        Route::get('/paginate', [ExitInterviewController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [ExitInterviewController::class, 'stats'])->name('stats');
        Route::get('/analytics', [ExitInterviewController::class, 'analytics'])->name('analytics');
        Route::post('/', [ExitInterviewController::class, 'store'])->name('store');
        Route::get('/{id}', [ExitInterviewController::class, 'show'])->name('show');
        Route::put('/{id}', [ExitInterviewController::class, 'update'])->name('update');
        Route::delete('/{id}', [ExitInterviewController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/complete', [ExitInterviewController::class, 'complete'])->name('complete');
    });

    // =========================================================================
    // Pulse Surveys - Quick Engagement & Sentiment Check-ins
    // =========================================================================
    Route::middleware(['hrmac:hrm.pulse-surveys'])->prefix('pulse-surveys')->name('pulse-surveys.')->group(function () {
        Route::get('/', [PulseSurveyController::class, 'index'])->name('index');
        Route::get('/paginate', [PulseSurveyController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [PulseSurveyController::class, 'stats'])->name('stats');
        Route::get('/analytics', [PulseSurveyController::class, 'analytics'])->name('analytics');
        Route::get('/my-pending', [PulseSurveyController::class, 'myPendingSurveys'])->name('my-pending');
        Route::post('/', [PulseSurveyController::class, 'store'])->name('store');
        Route::get('/{id}', [PulseSurveyController::class, 'show'])->name('show');
        Route::put('/{id}', [PulseSurveyController::class, 'update'])->name('update');
        Route::delete('/{id}', [PulseSurveyController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/activate', [PulseSurveyController::class, 'activate'])->name('activate');
        Route::post('/{id}/pause', [PulseSurveyController::class, 'pause'])->name('pause');
        Route::post('/{id}/complete', [PulseSurveyController::class, 'complete'])->name('complete');
        Route::post('/{id}/respond', [PulseSurveyController::class, 'submitResponse'])->name('respond');
    });

    // =========================================================================
    // Employee History - Compensation, Promotions, Transfers
    // =========================================================================
    Route::prefix('employee-history')->name('employee-history.')->group(function () {
        Route::get('/', [EmployeeHistoryController::class, 'index'])->name('index');

        // Compensation History
        Route::get('/compensations', [EmployeeHistoryController::class, 'compensationHistory'])->name('compensations');
        Route::post('/compensations', [EmployeeHistoryController::class, 'storeCompensation'])->name('compensations.store');

        // Promotion History
        Route::get('/promotions', [EmployeeHistoryController::class, 'promotionHistory'])->name('promotions');
        Route::post('/promotions', [EmployeeHistoryController::class, 'storePromotion'])->name('promotions.store');

        // Transfer History
        Route::get('/transfers', [EmployeeHistoryController::class, 'transferHistory'])->name('transfers');
        Route::post('/transfers', [EmployeeHistoryController::class, 'storeTransfer'])->name('transfers.store');

        // Complete Employee History View
        Route::get('/employees/{employeeId}', [EmployeeHistoryController::class, 'employeeHistory'])->name('employee');
    });

    // =========================================================================
    // Career Path Management - Career Progression & Employee Development
    // =========================================================================
    Route::middleware(['hrmac:hrm.career-pathing'])->prefix('career-paths')->name('career-paths.')->group(function () {
        Route::get('/', [CareerPathController::class, 'index'])->name('index');
        Route::get('/paginate', [CareerPathController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [CareerPathController::class, 'stats'])->name('stats');
        Route::post('/', [CareerPathController::class, 'store'])->name('store');
        Route::get('/progressions', [CareerPathController::class, 'employeeProgressions'])->name('progressions');
        Route::get('/{id}', [CareerPathController::class, 'show'])->name('show');
        Route::put('/{id}', [CareerPathController::class, 'update'])->name('update');
        Route::delete('/{id}', [CareerPathController::class, 'destroy'])->name('destroy');

        // Milestones
        Route::get('/{id}/milestones', [CareerPathController::class, 'milestones'])->name('milestones');
        Route::post('/{id}/milestones', [CareerPathController::class, 'addMilestone'])->name('milestones.store');
        Route::put('/{id}/milestones/{milestoneId}', [CareerPathController::class, 'updateMilestone'])->name('milestones.update');
        Route::delete('/{id}/milestones/{milestoneId}', [CareerPathController::class, 'deleteMilestone'])->name('milestones.destroy');

        // Employee Assignments
        Route::post('/{id}/assign-employee', [CareerPathController::class, 'assignEmployee'])->name('assign-employee');
        Route::put('/{id}/progressions/{progressionId}', [CareerPathController::class, 'updateProgression'])->name('progressions.update');
    });

    // =========================================================================
    // 360° Feedback - Multi-Rater Performance Feedback
    // =========================================================================
    Route::middleware(['hrmac:hrm.feedback-360'])->prefix('feedback-360')->name('feedback-360.')->group(function () {
        Route::get('/', [Feedback360Controller::class, 'index'])->name('index');
        Route::get('/paginate', [Feedback360Controller::class, 'paginate'])->name('paginate');
        Route::get('/stats', [Feedback360Controller::class, 'stats'])->name('stats');
        Route::get('/my-pending', [Feedback360Controller::class, 'myPendingFeedback'])->name('my-pending');
        Route::post('/', [Feedback360Controller::class, 'store'])->name('store');
        Route::get('/{id}', [Feedback360Controller::class, 'show'])->name('show');
        Route::put('/{id}', [Feedback360Controller::class, 'update'])->name('update');
        Route::delete('/{id}', [Feedback360Controller::class, 'destroy'])->name('destroy');
        Route::post('/{id}/launch', [Feedback360Controller::class, 'launch'])->name('launch');
        Route::post('/{id}/reviewers', [Feedback360Controller::class, 'addReviewers'])->name('reviewers.store');
        Route::post('/{id}/responses/{responseId}', [Feedback360Controller::class, 'submitResponse'])->name('responses.submit');
    });

    // =========================================================================
    // Compensation Planning - Salary Reviews & Market Benchmarking
    // =========================================================================
    // Redirect /compensation to /compensation-planning for navigation consistency
    Route::middleware(['hrmac:hrm.compensation-planning'])->get('/compensation', fn () => redirect()->route('hrm.compensation.index'))->name('compensation-redirect');

    Route::middleware(['hrmac:hrm.compensation-planning'])->prefix('compensation-planning')->name('compensation.')->group(function () {
        Route::get('/', [CompensationPlanningController::class, 'index'])->name('index');
        Route::get('/paginate', [CompensationPlanningController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [CompensationPlanningController::class, 'stats'])->name('stats');
        Route::get('/analytics', [CompensationPlanningController::class, 'analytics'])->name('analytics');
        Route::post('/', [CompensationPlanningController::class, 'store'])->name('store');
        Route::get('/{id}', [CompensationPlanningController::class, 'show'])->name('show');
        Route::put('/{id}', [CompensationPlanningController::class, 'update'])->name('update');
        Route::delete('/{id}', [CompensationPlanningController::class, 'destroy'])->name('destroy');

        // Adjustments
        Route::get('/{id}/adjustments', [CompensationPlanningController::class, 'adjustments'])->name('adjustments');
        Route::post('/{id}/adjustments', [CompensationPlanningController::class, 'addAdjustment'])->name('adjustments.store');
        Route::put('/{id}/adjustments/{adjustmentId}', [CompensationPlanningController::class, 'updateAdjustment'])->name('adjustments.update');
        Route::delete('/{id}/adjustments/{adjustmentId}', [CompensationPlanningController::class, 'deleteAdjustment'])->name('adjustments.destroy');
        Route::post('/{id}/adjustments/{adjustmentId}/approve', [CompensationPlanningController::class, 'approveAdjustment'])->name('adjustments.approve');
        Route::post('/{id}/adjustments/{adjustmentId}/reject', [CompensationPlanningController::class, 'rejectAdjustment'])->name('adjustments.reject');
    });

    // =========================================================================
    // Workforce Planning - Headcount Forecasting & Strategic Planning
    // =========================================================================
    Route::middleware(['hrmac:hrm.workforce-planning'])->prefix('workforce-planning')->name('workforce-planning.')->group(function () {
        Route::get('/', [WorkforcePlanningController::class, 'index'])->name('index');
        Route::get('/paginate', [WorkforcePlanningController::class, 'paginate'])->name('paginate');
        Route::get('/stats', [WorkforcePlanningController::class, 'stats'])->name('stats');
        Route::get('/forecast', [WorkforcePlanningController::class, 'forecast'])->name('forecast');
        Route::post('/', [WorkforcePlanningController::class, 'store'])->name('store');
        Route::get('/{id}', [WorkforcePlanningController::class, 'show'])->name('show');
        Route::put('/{id}', [WorkforcePlanningController::class, 'update'])->name('update');
        Route::delete('/{id}', [WorkforcePlanningController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/approve', [WorkforcePlanningController::class, 'approve'])->name('approve');

        // Positions
        Route::get('/{id}/positions', [WorkforcePlanningController::class, 'positions'])->name('positions');
        Route::post('/{id}/positions', [WorkforcePlanningController::class, 'addPosition'])->name('positions.store');
        Route::put('/{id}/positions/{positionId}', [WorkforcePlanningController::class, 'updatePosition'])->name('positions.update');
        Route::delete('/{id}/positions/{positionId}', [WorkforcePlanningController::class, 'deletePosition'])->name('positions.destroy');
    });

    // Talent Marketplace - Employee view
    Route::middleware(['hrmac:hrm.workforce-planning.talent-marketplace.view'])->group(function () {
        Route::get('/talent-marketplace', [TalentMarketplaceController::class, 'index'])->name('talent-marketplace.index');
    });
    Route::middleware(['hrmac:hrm.workforce-planning.talent-marketplace.apply'])->group(function () {
        Route::post('/talent-marketplace/{id}/apply', [TalentMarketplaceController::class, 'applyOpportunity'])->name('talent-marketplace.apply');
    });

    // Talent Marketplace - HR Admin view
    Route::middleware(['hrmac:hrm.workforce-planning.talent-marketplace.manage'])->group(function () {
        Route::get('/talent-marketplace/admin', [TalentMarketplaceController::class, 'adminIndex'])->name('talent-marketplace.admin');
        Route::post('/talent-marketplace', [TalentMarketplaceController::class, 'storeOpportunity'])->name('talent-marketplace.store');
        Route::put('/talent-marketplace/{id}', [TalentMarketplaceController::class, 'updateOpportunity'])->name('talent-marketplace.update');
        Route::post('/talent-marketplace/{id}/close', [TalentMarketplaceController::class, 'closeOpportunity'])->name('talent-marketplace.close');
    });

    // DEI Analytics
    Route::middleware(['hrmac:hrm.workforce-planning.dei-analytics.view'])->group(function () {
        Route::get('/dei-analytics', [DEIAnalyticsController::class, 'index'])->name('dei-analytics.index');
    });

    // =========================================================================
    // Performance Improvement Plans (PIP)
    // =========================================================================
    Route::prefix('performance/improvement-plans')->name('performance.pip.')
        ->middleware('hrmac:hrm.performance.improvement_plans.view')
        ->group(function () {
            Route::get('/', [PerformanceImprovementPlanController::class, 'index'])->name('index');
            Route::get('/{pipPlan}', [PerformanceImprovementPlanController::class, 'show'])->name('show');
            Route::get('/{pipPlan}/goals', [PerformanceImprovementPlanController::class, 'goals'])->name('goals');

            Route::post('/', [PerformanceImprovementPlanController::class, 'store'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.create')
                ->name('store');

            Route::put('/{pipPlan}', [PerformanceImprovementPlanController::class, 'update'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.update')
                ->name('update');

            Route::patch('/{pipPlan}/status', [PerformanceImprovementPlanController::class, 'updateStatus'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.update')
                ->name('update-status');

            Route::delete('/{pipPlan}', [PerformanceImprovementPlanController::class, 'destroy'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.delete')
                ->name('destroy');

            Route::post('/{pipPlan}/goals', [PerformanceImprovementPlanController::class, 'storeGoal'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.update')
                ->name('goals.store');

            Route::put('/{pipPlan}/goals/{goal}', [PerformanceImprovementPlanController::class, 'updateGoal'])
                ->withoutMiddleware('hrmac:hrm.performance.improvement_plans.view')
                ->middleware('hrmac:hrm.performance.improvement_plans.update')
                ->name('goals.update');
        });

    // ============================================================================
    // ORG STRUCTURE ROUTES
    // ============================================================================
    Route::prefix('org-structure')->name('org.')->group(function () {

        Route::prefix('departments')->name('departments.')->group(function () {
            Route::get('/', [DepartmentController::class, 'index'])->middleware('hrmac:hrm.org-structure.departments.view')->name('index');
            Route::get('/chart', [DepartmentController::class, 'orgChart'])->middleware('hrmac:hrm.org-structure.departments.view')->name('chart');
            Route::get('/create', [DepartmentController::class, 'create'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('create');
            Route::post('/', [DepartmentController::class, 'store'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('store');
            Route::get('/{department}', [DepartmentController::class, 'show'])->middleware('hrmac:hrm.org-structure.departments.view')->name('show');
            Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('edit');
            Route::put('/{department}', [DepartmentController::class, 'update'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('update');
            Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.departments.edit')->name('destroy');
        });

        Route::prefix('designations')->name('designations.')->group(function () {
            Route::get('/', [DesignationController::class, 'index'])->middleware('hrmac:hrm.org-structure.designations.view')->name('index');
            Route::post('/', [DesignationController::class, 'store'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('store');
            Route::put('/{designation}', [DesignationController::class, 'update'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('update');
            Route::delete('/{designation}', [DesignationController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.designations.edit')->name('destroy');
        });

        Route::prefix('grades')->name('grades.')->group(function () {
            Route::get('/', [GradeController::class, 'index'])->middleware('hrmac:hrm.org-structure.grades.view')->name('index');
            Route::post('/', [GradeController::class, 'store'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('store');
            Route::put('/{grade}', [GradeController::class, 'update'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('update');
            Route::delete('/{grade}', [GradeController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.grades.edit')->name('destroy');
        });

        Route::prefix('work-locations')->name('work-locations.')->group(function () {
            Route::get('/', [WorkLocationController::class, 'index'])->middleware('hrmac:hrm.org-structure.work-locations.view')->name('index');
            Route::post('/', [WorkLocationController::class, 'store'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('store');
            Route::put('/{workLocation}', [WorkLocationController::class, 'update'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('update');
            Route::delete('/{workLocation}', [WorkLocationController::class, 'destroy'])->middleware('hrmac:hrm.org-structure.work-locations.edit')->name('destroy');
        });
    });

    // ── Leave Management ────────────────────────────────────────────────────────
    Route::prefix('leave/types')->name('leave.types.')->group(function () {
        Route::get('/', [LeaveTypeController::class, 'index'])->middleware('hrmac:hrm.leaves.leave-types.view')->name('index');
        Route::post('/', [LeaveTypeController::class, 'store'])->middleware('hrmac:hrm.leaves.leave-types.edit')->name('store');
        Route::put('/{type}', [LeaveTypeController::class, 'update'])->middleware('hrmac:hrm.leaves.leave-types.edit')->name('update');
        Route::delete('/{type}', [LeaveTypeController::class, 'destroy'])->middleware('hrmac:hrm.leaves.leave-types.edit')->name('destroy');
    });

    Route::prefix('leave/applications')->name('leave.applications.')->group(function () {
        Route::get('/', [LeaveApplicationController::class, 'index'])->middleware('hrmac:hrm.leaves.leave-requests.view')->name('index');
        Route::get('/create', [LeaveApplicationController::class, 'create'])->middleware('hrmac:hrm.leaves.leave-requests.create')->name('create');
        Route::post('/', [LeaveApplicationController::class, 'store'])->middleware('hrmac:hrm.leaves.leave-requests.create')->name('store');
        Route::get('/{application}', [LeaveApplicationController::class, 'show'])->middleware('hrmac:hrm.leaves.leave-requests.view')->name('show');
        Route::post('/{application}/approve', [LeaveApplicationController::class, 'approve'])->middleware('hrmac:hrm.leaves.leave-requests.approve')->name('approve');
        Route::post('/{application}/reject', [LeaveApplicationController::class, 'reject'])->middleware('hrmac:hrm.leaves.leave-requests.approve')->name('reject');
        Route::post('/{application}/cancel', [LeaveApplicationController::class, 'cancel'])->middleware('hrmac:hrm.leaves.leave-requests.view')->name('cancel');
    });

    Route::get('leave/balance', [LeaveBalanceController::class, 'index'])->middleware('hrmac:hrm.leaves.leave-balances.view')->name('leave.balance.index');
    Route::get('leave/calendar', [LeaveCalendarController::class, 'index'])->middleware('hrmac:hrm.leaves.holiday-calendar.view')->name('leave.calendar.index');
    Route::get('leave/settings', [LeaveLeaveSettingController::class, 'index'])->middleware('hrmac:hrm.leaves.leave-policies.view')->name('leave.settings.index');
    Route::put('leave/settings', [LeaveLeaveSettingController::class, 'update'])->middleware('hrmac:hrm.leaves.leave-policies.edit')->name('leave.settings.update');

    // ── Attendance ──────────────────────────────────────────────────────────────
    // Clock in/out + my status
    Route::get('attendance/clock', [AttendanceController::class, 'clockStatus'])->middleware('hrmac:hrm.attendance.my-attendance.view')->name('attendance.clock');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->middleware('hrmac:hrm.attendance.my-attendance.view')->name('attendance.clock-in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->middleware('hrmac:hrm.attendance.my-attendance.view')->name('attendance.clock-out');

    // Admin daily + monthly
    Route::get('attendance/daily', [AttendanceController::class, 'daily'])->middleware('hrmac:hrm.attendance.daily-attendance.view')->name('attendance.daily');
    Route::get('attendance/monthly', [AttendanceController::class, 'monthly'])->middleware('hrmac:hrm.attendance.daily-attendance.view')->name('attendance.monthly');

    // Overtime
    Route::prefix('attendance/overtime')->name('attendance.overtime.')->group(function () {
        Route::get('/', [AttendanceOvertimeController::class, 'index'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('index');
        Route::get('/create', [AttendanceOvertimeController::class, 'create'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('create');
        Route::post('/', [AttendanceOvertimeController::class, 'store'])->middleware('hrmac:hrm.overtime.overtime-records.view')->name('store');
        Route::post('/{overtime}/approve', [AttendanceOvertimeController::class, 'approve'])->middleware('hrmac:hrm.overtime.overtime-records.approve')->name('approve');
        Route::post('/{overtime}/reject', [AttendanceOvertimeController::class, 'reject'])->middleware('hrmac:hrm.overtime.overtime-records.approve')->name('reject');
    });

    // Timesheets
    Route::prefix('attendance/timesheets')->name('attendance.timesheets.')->group(function () {
        Route::get('/', [TimesheetController::class, 'index'])->middleware('hrmac:hrm.attendance.attendance-logs.view')->name('index');
        Route::put('/{timesheet}', [TimesheetController::class, 'update'])->middleware('hrmac:hrm.attendance.attendance-logs.view')->name('update');
    });

    // Shift Marketplace
    Route::prefix('attendance/shifts')->name('attendance.shifts.')->group(function () {
        Route::get('/marketplace', [ShiftMarketplaceController::class, 'index'])->middleware('hrmac:hrm.attendance.shift-marketplace.view')->name('marketplace');
        Route::post('/marketplace', [ShiftMarketplaceController::class, 'store'])->middleware('hrmac:hrm.attendance.shift-marketplace.create')->name('marketplace.store');
        Route::post('/marketplace/{swap}/approve', [ShiftMarketplaceController::class, 'approve'])->middleware('hrmac:hrm.attendance.shift-marketplace.approve')->name('marketplace.approve');
    });

    // ── Payroll v2 ─────────────────────────────────────────────────────────────

    // Salary Structures
    Route::prefix('payroll/structures')->name('payroll.structures.')->group(function () {
        Route::get('/', [PayrollSalaryStructureController::class, 'index'])
            ->middleware('hrmac:hrm.payroll.salary-structures.view')
            ->name('index');
        Route::get('/create', [PayrollSalaryStructureController::class, 'create'])
            ->middleware('hrmac:hrm.payroll.salary-structures.view')
            ->name('create');
        Route::post('/', [PayrollSalaryStructureController::class, 'store'])
            ->middleware('hrmac:hrm.payroll.salary-structures.create')
            ->name('store');
        Route::put('/{structure}', [PayrollSalaryStructureController::class, 'update'])
            ->middleware('hrmac:hrm.payroll.salary-structures.update')
            ->name('update');
        Route::delete('/{structure}', [PayrollSalaryStructureController::class, 'destroy'])
            ->middleware('hrmac:hrm.payroll.salary-structures.delete')
            ->name('destroy');
    });

    // Pay Components
    Route::prefix('payroll/components')->name('payroll.components.')->group(function () {
        Route::get('/', [PayrollPayComponentController::class, 'index'])
            ->middleware('hrmac:hrm.payroll.salary-components.view')
            ->name('index');
        Route::post('/', [PayrollPayComponentController::class, 'store'])
            ->middleware('hrmac:hrm.payroll.salary-components.create')
            ->name('store');
        Route::put('/{component}', [PayrollPayComponentController::class, 'update'])
            ->middleware('hrmac:hrm.payroll.salary-components.update')
            ->name('update');
        Route::delete('/{component}', [PayrollPayComponentController::class, 'destroy'])
            ->middleware('hrmac:hrm.payroll.salary-components.delete')
            ->name('destroy');
    });

    // Payroll Runs
    Route::prefix('payroll/runs')->name('payroll.runs.')->group(function () {
        Route::get('/', [PayrollPayrollRunController::class, 'index'])
            ->middleware('hrmac:hrm.payroll.payroll-run.view')
            ->name('index');
        Route::get('/create', [PayrollPayrollRunController::class, 'create'])
            ->middleware('hrmac:hrm.payroll.payroll-run.execute')
            ->name('create');
        Route::post('/', [PayrollPayrollRunController::class, 'store'])
            ->middleware('hrmac:hrm.payroll.payroll-run.execute')
            ->name('store');
        Route::get('/{run}', [PayrollPayrollRunController::class, 'show'])
            ->middleware('hrmac:hrm.payroll.payroll-run.view')
            ->name('show');
        Route::put('/{run}', [PayrollPayrollRunController::class, 'update'])
            ->middleware('hrmac:hrm.payroll.payroll-run.execute')
            ->name('update');
        Route::post('/{run}/approve', [PayrollPayrollRunController::class, 'approve'])
            ->middleware('hrmac:hrm.payroll.payroll-run.lock')
            ->name('approve');
    });

    // Payslips (dual-auth: self-service or admin — controller handles gate check)
    Route::prefix('payroll/payslips')->name('payroll.payslips.')->group(function () {
        Route::get('/{payslip}', [PayrollPayslipController::class, 'show'])
            ->name('show');
        Route::get('/{payslip}/download', [PayrollPayslipController::class, 'download'])
            ->name('download');
    });

    // Tax Settings
    Route::prefix('payroll/settings')->name('payroll.settings.')->group(function () {
        Route::get('/tax', [PayrollTaxSettingController::class, 'index'])
            ->middleware('hrmac:hrm.payroll.tax-setup.view')
            ->name('tax.index');
        Route::post('/tax', [PayrollTaxSettingController::class, 'store'])
            ->middleware('hrmac:hrm.payroll.tax-setup.manage')
            ->name('tax.store');
    });

});
