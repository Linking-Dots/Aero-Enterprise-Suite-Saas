<?php

namespace Aero\HRM\Providers;

use Aero\Contracts\AuditServiceInterface;
use Aero\Contracts\EmployeeServiceContract;
use Aero\Contracts\Providers\AbstractModuleProvider;
use Aero\Core\Models\User;
use Aero\Core\Services\DashboardRegistry;
use Aero\Core\Services\UserRelationshipRegistry;
use Aero\HRM\Console\Commands\SendOnboardingRemindersCommand;
use Aero\HRM\Http\Middleware\EnsureEmployeeProfile;
use Aero\HRM\Jobs\CheckBirthdaysJob;
use Aero\HRM\Jobs\CheckExpiringContractsJob;
use Aero\HRM\Jobs\CheckExpiringDocumentsJob;
use Aero\HRM\Jobs\CheckProbationEndingJob;
use Aero\HRM\Jobs\CheckWorkAnniversariesJob;
use Aero\HRM\Models\Attendance;
use Aero\HRM\Models\AttendanceType;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\EmployeeEducation;
use Aero\HRM\Models\EmployeeWorkExperience;
use Aero\HRM\Models\Leave;
use Aero\HRM\Models\SafetyInspection;
use Aero\HRM\Models\SafetyTraining;
use Aero\HRM\Observers\EmployeeQuotaObserver;
use Aero\HRM\Policies\AttendancePolicy;
use Aero\HRM\Policies\EmployeePolicy;
use Aero\HRM\Policies\LeavePolicy;
use Aero\HRM\Policies\SafetyInspectionPolicy;
use Aero\HRM\Policies\SafetyTrainingPolicy;
use Aero\HRM\Services\AIAnalytics\AttritionPredictionService;
use Aero\HRM\Services\AIAnalytics\BurnoutRiskService;
use Aero\HRM\Services\AIAnalytics\PerformancePredictionService;
use Aero\HRM\Services\AIAnalytics\RecruitmentAnalyticsService;
use Aero\HRM\Services\AIAnalytics\WorkforceAnalyticsService;
use Aero\HRM\Services\Analytics\AttritionRiskService;
use Aero\HRM\Services\Analytics\DEIService;
use Aero\HRM\Services\Analytics\HeadcountAnalyticsService;
use Aero\HRM\Services\Analytics\PulseSurveyService;
use Aero\HRM\Services\Analytics\TurnoverAnalyticsService;
use Aero\HRM\Services\Analytics\WorkforcePlanService;
use Aero\HRM\Services\Asset\AssetAllocationService;
use Aero\HRM\Services\AttendanceCalculationService;
use Aero\HRM\Services\Benefits\BenefitCatalogService;
use Aero\HRM\Services\Benefits\EligibilityService;
use Aero\HRM\Services\Benefits\EnrollmentPeriodService;
use Aero\HRM\Services\Benefits\OpenEnrollmentService;
use Aero\HRM\Services\DEIAnalyticsService;
use Aero\HRM\Services\Disciplinary\DisciplinaryCaseService;
use Aero\HRM\Services\Disciplinary\ExitInterviewService;
use Aero\HRM\Services\Disciplinary\GrievanceService;
use Aero\HRM\Services\Disciplinary\ReferenceGenerator;
use Aero\HRM\Services\Disciplinary\WarningService;
use Aero\HRM\Services\EmployeeService;
use Aero\HRM\Services\Expense\ExpenseClaimService;
use Aero\HRM\Services\HRMetricsAggregatorService;
use Aero\HRM\Services\HrmNotificationChannelResolver;
use Aero\HRM\Services\LeaveApplicationService;
use Aero\HRM\Services\LeaveBalanceService;
use Aero\HRM\Services\Payroll\PayrollApprovalService;
use Aero\HRM\Services\Payroll\PayrollCalculator;
use Aero\HRM\Services\Payroll\PayrollRunGenerator;
use Aero\HRM\Services\Payroll\PayslipPdfRenderer;
use Aero\HRM\Services\PayrollCalculationService;
use Aero\HRM\Services\Performance\Feedback360Service;
use Aero\HRM\Services\Performance\GoalLifecycleService;
use Aero\HRM\Services\Performance\PIPService;
use Aero\HRM\Services\Performance\ReviewCycleService;
use Aero\HRM\Services\Performance\ReviewSubmissionService;
use Aero\HRM\Services\Recruitment\ApplicationPipelineService;
use Aero\HRM\Services\Recruitment\InterviewScheduler;
use Aero\HRM\Services\Recruitment\JobLifecycleService;
use Aero\HRM\Services\Recruitment\OfferService;
use Aero\HRM\Services\Recruitment\OnboardingService;
use Aero\HRM\Services\Safety\SafetyIncidentService;
use Aero\HRM\Services\Safety\SafetyInspectionService;
use Aero\HRM\Services\Safety\SafetyKpiService;
use Aero\HRM\Services\Safety\SafetyTrainingService;
use Aero\HRM\Services\Training\CourseService;
use Aero\HRM\Services\Training\EnrollmentService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;

/**
 * HRM Module Provider
 *
 * Provides Human Resources Management functionality including employee management,
 * attendance tracking, leave management, payroll, performance reviews, and recruitment.
 *
 * All module metadata is read from config/module.php (single source of truth).
 * This provider only contains module-specific services, policies, and relationships.
 */
class HRMServiceProvider extends AbstractModuleProvider
{
    /**
     * Module code - the only required property.
     * All other metadata is read from config/module.php.
     */
    protected string $moduleCode = 'hrm';

    /**
     * Get the module path.
     */
    protected function getModulePath(string $path = ''): string
    {
        $basePath = dirname(__DIR__, 2);

        return $path ? $basePath.'/'.$path : $basePath;
    }

    /**
     * Load HRM routes via parent (AbstractModuleProvider).
     *
     * Routes live in routes/web.php (Inertia surface) and routes/api.php
     * (REST surface — HRM Push H.T2). The base class loads the web routes
     * with SaaS/standalone outer middleware; we also load the API routes
     * inside the same registration scope so tokens issued via aero-core's
     * ApiKey admin can hit /api/hrm/* immediately after install.
     */
    protected function loadRoutes(): void
    {
        parent::loadRoutes();

        $apiRoutes = $this->getModulePath('routes/api.php');
        if (file_exists($apiRoutes)) {
            $this->loadRoutesFrom($apiRoutes);
        }
    }

    /**
     * Register module services.
     */
    protected function registerServices(): void
    {
        // Register HRM Event Service Provider
        $this->app->register(HrmEventServiceProvider::class);

        // Register HRM Notification Channel Resolver (HRM-specific, no core dependency)
        $this->app->singleton(HrmNotificationChannelResolver::class, function ($app) {
            return new HrmNotificationChannelResolver;
        });

        // Register EmployeeService - implements Core's EmployeeServiceContract
        // This enables cross-package employee data access without direct model coupling
        $this->app->singleton(EmployeeService::class, function ($app) {
            return new EmployeeService;
        });

        // Also bind to the Core contract interface
        $this->app->singleton(
            EmployeeServiceContract::class,
            EmployeeService::class
        );

        // Register main HRM service
        $this->app->singleton('hrm', function ($app) {
            return new HRMetricsAggregatorService;
        });

        // Register specific services
        $this->app->singleton(LeaveApplicationService::class, function ($app) {
            return new LeaveApplicationService(
                $app->make(AuditServiceInterface::class)
            );
        });

        $this->app->singleton('hrm.leave', function ($app) {
            return new LeaveBalanceService;
        });

        $this->app->singleton('hrm.attendance', function ($app) {
            return new AttendanceCalculationService;
        });

        $this->app->singleton('hrm.payroll', function ($app) {
            return new PayrollCalculationService;
        });

        // Payroll v2 services
        $this->app->singleton(PayrollCalculator::class);

        $this->app->singleton(PayrollRunGenerator::class, function ($app) {
            return new PayrollRunGenerator(
                $app->make(PayrollCalculator::class),
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(PayrollApprovalService::class, function ($app) {
            return new PayrollApprovalService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(PayslipPdfRenderer::class);

        // Register Performance Management Services (H6)
        $this->app->singleton(ReviewCycleService::class, function ($app) {
            return new ReviewCycleService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(ReviewSubmissionService::class, function ($app) {
            return new ReviewSubmissionService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(GoalLifecycleService::class, function ($app) {
            return new GoalLifecycleService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(Feedback360Service::class, function ($app) {
            return new Feedback360Service(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(PIPService::class, function ($app) {
            return new PIPService(
                $app->make(AuditServiceInterface::class),
            );
        });

        // Register Recruitment Services (H7)
        $this->app->singleton(JobLifecycleService::class, function ($app) {
            return new JobLifecycleService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(ApplicationPipelineService::class, function ($app) {
            return new ApplicationPipelineService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(InterviewScheduler::class, function ($app) {
            return new InterviewScheduler(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(OfferService::class, function ($app) {
            return new OfferService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(OnboardingService::class, function ($app) {
            return new OnboardingService(
                $app->make(AuditServiceInterface::class),
            );
        });

        // Register Training Services (H8)
        $this->app->singleton(CourseService::class, function ($app) {
            return new CourseService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(EnrollmentService::class, function ($app) {
            return new EnrollmentService(
                $app->make(AuditServiceInterface::class),
            );
        });

        // Register DEI Analytics Service
        $this->app->singleton(DEIAnalyticsService::class);

        // Register H10 Analytics Services
        $this->app->singleton(HeadcountAnalyticsService::class);
        $this->app->singleton(TurnoverAnalyticsService::class);
        $this->app->singleton(AttritionRiskService::class);
        $this->app->singleton(DEIService::class);
        $this->app->singleton(PulseSurveyService::class);
        $this->app->singleton(WorkforcePlanService::class);

        // Register AI Analytics Services
        $this->app->singleton(AttritionPredictionService::class);
        $this->app->singleton(BurnoutRiskService::class);
        $this->app->singleton(PerformancePredictionService::class);
        $this->app->singleton(RecruitmentAnalyticsService::class);
        $this->app->singleton(WorkforceAnalyticsService::class);

        // Register Benefits Services (H11)
        $this->app->singleton(EligibilityService::class);

        $this->app->singleton(BenefitCatalogService::class, function ($app) {
            return new BenefitCatalogService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(EnrollmentPeriodService::class, function ($app) {
            return new EnrollmentPeriodService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(OpenEnrollmentService::class, function ($app) {
            return new OpenEnrollmentService(
                $app->make(EligibilityService::class),
                $app->make(AuditServiceInterface::class),
            );
        });

        // Register Disciplinary Services (H12)
        $this->app->singleton(ReferenceGenerator::class);

        $this->app->singleton(DisciplinaryCaseService::class, function ($app) {
            return new DisciplinaryCaseService(
                $app->make(ReferenceGenerator::class),
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(WarningService::class, function ($app) {
            return new WarningService(
                $app->make(ReferenceGenerator::class),
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(ExitInterviewService::class, function ($app) {
            return new ExitInterviewService(
                $app->make(AuditServiceInterface::class),
            );
        });

        $this->app->singleton(GrievanceService::class, function ($app) {
            return new GrievanceService(
                $app->make(ReferenceGenerator::class),
                $app->make(AuditServiceInterface::class),
            );
        });

        // Register Asset Services (H14)
        $this->app->singleton(AssetAllocationService::class, function ($app) {
            return new AssetAllocationService($app->make(AuditServiceInterface::class));
        });

        // Register Expense Services (H15)
        $this->app->singleton(ExpenseClaimService::class, function ($app) {
            return new ExpenseClaimService($app->make(AuditServiceInterface::class));
        });

        // Register Safety Services (H13)
        $this->app->singleton(SafetyKpiService::class);

        $this->app->singleton(SafetyIncidentService::class, function ($app) {
            return new SafetyIncidentService($app->make(AuditServiceInterface::class));
        });

        $this->app->singleton(SafetyInspectionService::class, function ($app) {
            return new SafetyInspectionService($app->make(AuditServiceInterface::class));
        });

        $this->app->singleton(SafetyTrainingService::class, function ($app) {
            return new SafetyTrainingService($app->make(AuditServiceInterface::class));
        });

        // Merge HRM-specific configuration
        $hrmConfigPath = $this->getModulePath('config/hrm.php');
        if (file_exists($hrmConfigPath)) {
            $this->mergeConfigFrom($hrmConfigPath, 'hrm');
        }
    }

    /**
     * Boot HRM module.
     */
    protected function bootModule(): void
    {
        // Register middleware alias for self-service employee guard
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('employee.required', EnsureEmployeeProfile::class);

        // Register policies
        $this->registerPolicies();

        // Register model observers
        Employee::observe(EmployeeQuotaObserver::class);

        // Register User model relationships dynamically
        $this->registerUserRelationships();

        // Register navigation items for auto-discovery
        $this->registerNavigation();

        // Register console commands
        $this->registerCommands();

        // Register scheduled jobs
        $this->registerScheduledJobs();

        // Register HRM dashboards with DashboardRegistry
        $this->registerDashboards();
    }

    /**
     * Register HRM scheduled jobs.
     *
     * These jobs run daily to check for employee-related events:
     * - Birthdays and work anniversaries
     * - Expiring documents, probation periods, and contracts
     */
    protected function registerScheduledJobs(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Birthday and Anniversary checks - run at 8:00 AM
            $schedule->job(new CheckBirthdaysJob)
                ->dailyAt('08:00')
                ->name('hrm:check-birthdays')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->job(new CheckWorkAnniversariesJob)
                ->dailyAt('08:00')
                ->name('hrm:check-work-anniversaries')
                ->withoutOverlapping()
                ->onOneServer();

            // Document and Contract expiry checks - run at 9:00 AM
            $schedule->job(new CheckExpiringDocumentsJob)
                ->dailyAt('09:00')
                ->name('hrm:check-expiring-documents')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->job(new CheckProbationEndingJob)
                ->dailyAt('09:00')
                ->name('hrm:check-probation-ending')
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->job(new CheckExpiringContractsJob)
                ->dailyAt('09:00')
                ->name('hrm:check-expiring-contracts')
                ->withoutOverlapping()
                ->onOneServer();
        });
    }

    /**
     * Register HRM dashboards with the DashboardRegistry.
     *
     * This allows roles to be assigned to specific HRM dashboards:
     * - hrm.dashboard: For HR Managers and Staff (full analytics)
     * - hrm.employee.dashboard: For regular employees (personal view)
     */
    protected function registerDashboards(): void
    {
        // Only register if the registry is available
        if (! $this->app->bound(DashboardRegistry::class)) {
            return;
        }

        $registry = $this->app->make(DashboardRegistry::class);

        $registry->registerMany([
            [
                'route' => 'hrm.dashboard',
                'label' => 'HRM Dashboard',
                'module' => 'hrm',
                'description' => 'Full HR analytics for HR Managers and Staff',
                'icon' => 'UserGroupIcon',
                'requiredPermission' => 'hrm.dashboard',
            ],
            [
                'route' => 'hrm.employee.dashboard',
                'label' => 'Employee Dashboard',
                'module' => 'hrm',
                'description' => 'Personal dashboard for employees (leaves, attendance, payslips)',
                'icon' => 'UserIcon',
                'requiredPermission' => 'hrm.employee-self-service',
            ],
        ]);
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

        // Register employee relationship via both registry and resolveRelationUsing
        // resolveRelationUsing enables property access ($user->employee) via Eloquent __get
        User::resolveRelationUsing('employee', function ($user) {
            return $user->hasOne(Employee::class);
        });

        $registry->registerRelationship('employee', function ($user) {
            return $user->hasOne(Employee::class);
        });

        // Register department through employee
        $registry->registerRelationship('department', function ($user) {
            return $user->hasOneThrough(
                Department::class,
                Employee::class,
                'user_id',
                'id',
                'id',
                'department_id'
            );
        });

        // Register designation through employee
        $registry->registerRelationship('designation', function ($user) {
            return $user->hasOneThrough(
                Designation::class,
                Employee::class,
                'user_id',
                'id',
                'id',
                'designation_id'
            );
        });

        // Register leaves relationship
        $registry->registerRelationship('leaves', function ($user) {
            return $user->hasMany(Leave::class, 'user_id');
        });

        // Register attendances relationship
        $registry->registerRelationship('attendances', function ($user) {
            return $user->hasMany(Attendance::class, 'user_id');
        });

        // Register attendance type relationship
        $registry->registerRelationship('attendanceType', function ($user) {
            return $user->belongsTo(AttendanceType::class, 'attendance_type_id');
        });
        $registry->registerRelationship('educations', function ($user) {
            return $user->hasMany(EmployeeEducation::class, 'user_id');
        });
        $registry->registerRelationship('experiences', function ($user) {
            return $user->hasMany(EmployeeWorkExperience::class, 'user_id');
        });

        // Register scopes for user queries
        $registry->registerScope('employees', function ($query) {
            return $query->whereHas('employee');
        });

        $registry->registerScope('nonEmployees', function ($query) {
            return $query->whereDoesntHave('employee');
        });

        $registry->registerScope('withBasicRelations', function ($query) {
            return $query->with(['employee', 'employee.department', 'employee.designation']);
        });

        $registry->registerScope('withFullRelations', function ($query) {
            return $query->with([
                'employee',
                'employee.department',
                'employee.designation',
                'leaves',
                'attendances',
            ]);
        });

        // Register computed accessors
        $registry->registerAccessor('is_employee', function ($user) {
            return $user->employee !== null;
        });

        $registry->registerAccessor('employee_id', function ($user) {
            return $user->employee?->id;
        });

        $registry->registerAccessor('department_name', function ($user) {
            return $user->employee?->department?->name;
        });

        $registry->registerAccessor('designation_name', function ($user) {
            return $user->employee?->designation?->name;
        });
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        // Register model policies if they exist
        $policies = [
            Employee::class => EmployeePolicy::class,
            Leave::class => LeavePolicy::class,
            Attendance::class => AttendancePolicy::class,
            SafetyInspection::class => SafetyInspectionPolicy::class,
            SafetyTraining::class => SafetyTrainingPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SendOnboardingRemindersCommand::class,
            ]);
        }
    }
}
