<?php

declare(strict_types=1);

namespace Aero\HRM\Providers;

use Aero\HRM\Events\Attendance\AttendancePunchedIn;
use Aero\HRM\Events\Attendance\AttendancePunchedOut;
use Aero\HRM\Events\Attendance\LateArrivalDetected;
use Aero\HRM\Events\ContractExpiring;
use Aero\HRM\Events\DocumentExpiring;
use Aero\HRM\Events\Employee\EmployeeCreated;
use Aero\HRM\Events\Employee\EmployeePromoted;
use Aero\HRM\Events\Employee\EmployeeResigned;
use Aero\HRM\Events\Employee\EmployeeTerminated;
use Aero\HRM\Events\EmployeeBirthday;
use Aero\HRM\Events\Leave\LeaveApproved;
use Aero\HRM\Events\Leave\LeaveCancelled;
use Aero\HRM\Events\Leave\LeaveRejected;
use Aero\HRM\Events\Leave\LeaveRequested;
use Aero\HRM\Events\Offboarding\OffboardingCompleted;
use Aero\HRM\Events\Offboarding\OffboardingStarted;
use Aero\HRM\Events\Onboarding\OnboardingCompleted;
use Aero\HRM\Events\Onboarding\OnboardingStarted;
use Aero\HRM\Events\PayrollGenerated;
use Aero\HRM\Events\Performance\PerformanceReviewCompleted;
use Aero\HRM\Events\ProbationEnding;
use Aero\HRM\Events\Recruitment\ApplicationReceived;
use Aero\HRM\Events\Recruitment\InterviewScheduled;
use Aero\HRM\Events\Recruitment\OfferExtended;
use Aero\HRM\Events\Safety\SafetyIncidentReported;
use Aero\HRM\Events\Training\TrainingScheduled;
use Aero\HRM\Events\WorkAnniversary;
use Aero\HRM\Listeners\Attendance\CalculateOvertimeIfAny;
use Aero\HRM\Listeners\Attendance\NotifyManagerOfLateArrival;
use Aero\HRM\Listeners\Attendance\SendAttendanceSummary;
use Aero\HRM\Listeners\Attendance\SendLateArrivalNotification;
use Aero\HRM\Listeners\Attendance\SendPunchInConfirmation;
use Aero\HRM\Listeners\Employee\InitiateImmediateOffboarding;
use Aero\HRM\Listeners\Employee\NotifyHROfResignation;
use Aero\HRM\Listeners\Employee\NotifyManagerOfNewEmployee;
use Aero\HRM\Listeners\Employee\RevokeSystemAccess;
use Aero\HRM\Listeners\Employee\SendEmployeeWelcomeNotification;
use Aero\HRM\Listeners\Employee\SendPromotionNotification;
use Aero\HRM\Listeners\Employee\SendTerminationNotification;
use Aero\HRM\Listeners\HrmEventSubscriber;
use Aero\HRM\Listeners\Leave\NotifyEmployeeOfLeaveApproval;
use Aero\HRM\Listeners\Leave\NotifyEmployeeOfLeaveRejection;
use Aero\HRM\Listeners\Leave\NotifyOnLeaveCancellation;
use Aero\HRM\Listeners\Leave\StartWorkflowOnLeaveRequest;
use Aero\HRM\Listeners\Leave\UpdateBalanceOnLeaveApproval;
use Aero\HRM\Listeners\Leave\UpdateBalanceOnLeaveCancellation;
use Aero\HRM\Listeners\Leave\UpdateBalanceOnLeaveRejection;
use Aero\HRM\Listeners\Leave\UpdateBalanceOnLeaveRequest;
use Aero\HRM\Listeners\NotifyManagerOfLeaveRequest;
use Aero\HRM\Listeners\Offboarding\RevokeSystemAccessCompletely;
use Aero\HRM\Listeners\Offboarding\ScheduleExitInterview;
use Aero\HRM\Listeners\Offboarding\SendFinalSettlementNotification;
use Aero\HRM\Listeners\Offboarding\SendOffboardingCompletionNotification;
use Aero\HRM\Listeners\Offboarding\SendOffboardingNotification;
use Aero\HRM\Listeners\Onboarding\AssignOnboardingTasks;
use Aero\HRM\Listeners\Onboarding\SendOnboardingCompletionNotification;
use Aero\HRM\Listeners\Onboarding\SendOnboardingWelcomeNotification;
use Aero\HRM\Listeners\Performance\SendReviewCompletionNotification;
use Aero\HRM\Listeners\Recruitment\SendApplicationAcknowledgment;
use Aero\HRM\Listeners\Recruitment\SendInterviewInvitation;
use Aero\HRM\Listeners\Recruitment\SendOfferLetterNotification;
use Aero\HRM\Listeners\Recruitment\TrackOfferAcceptance;
use Aero\HRM\Listeners\Safety\NotifySafetyTeam;
use Aero\HRM\Listeners\SendBirthdayNotifications;
use Aero\HRM\Listeners\SendContractExpiryNotifications;
use Aero\HRM\Listeners\SendDocumentExpiryNotifications;
use Aero\HRM\Listeners\SendPayslipNotificationsNew;
use Aero\HRM\Listeners\SendProbationEndingNotifications;
use Aero\HRM\Listeners\SendWorkAnniversaryNotifications;
use Aero\HRM\Listeners\Training\SendTrainingInvitation;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * HRM Event Service Provider
 *
 * Explicitly registers all HRM events and their listeners.
 * This provides better discoverability and control over event handling.
 *
 * Architecture:
 * - Individual listeners handle specific business logic (balance updates, etc.)
 * - HrmEventSubscriber handles cross-cutting concerns (audit logging, HRMAC-based routing)
 */
class HrmEventServiceProvider extends ServiceProvider
{
    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        HrmEventSubscriber::class,
    ];

    /**
     * The event to listener mappings for the HRM module.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Employee Lifecycle Events
        EmployeeCreated::class => [
            SendEmployeeWelcomeNotification::class,
            NotifyManagerOfNewEmployee::class,
        ],

        EmployeePromoted::class => [
            SendPromotionNotification::class,
        ],

        EmployeeResigned::class => [
            NotifyHROfResignation::class,
        ],

        EmployeeTerminated::class => [
            SendTerminationNotification::class,
            RevokeSystemAccess::class,
            InitiateImmediateOffboarding::class,
        ],

        // Attendance Events
        AttendancePunchedIn::class => [
            SendPunchInConfirmation::class,
        ],

        AttendancePunchedOut::class => [
            SendAttendanceSummary::class,
            CalculateOvertimeIfAny::class,
        ],

        LateArrivalDetected::class => [
            SendLateArrivalNotification::class,
            NotifyManagerOfLateArrival::class,
        ],

        // Onboarding/Offboarding Events
        OnboardingStarted::class => [
            SendOnboardingWelcomeNotification::class,
            AssignOnboardingTasks::class,
        ],

        OnboardingCompleted::class => [
            SendOnboardingCompletionNotification::class,
        ],

        OffboardingStarted::class => [
            SendOffboardingNotification::class,
            ScheduleExitInterview::class,
        ],

        OffboardingCompleted::class => [
            SendOffboardingCompletionNotification::class,
            RevokeSystemAccessCompletely::class,
            SendFinalSettlementNotification::class,
        ],

        // Recruitment Events
        ApplicationReceived::class => [
            SendApplicationAcknowledgment::class,
        ],

        InterviewScheduled::class => [
            SendInterviewInvitation::class,
        ],

        OfferExtended::class => [
            SendOfferLetterNotification::class,
            TrackOfferAcceptance::class,
        ],

        // Performance Events
        PerformanceReviewCompleted::class => [
            SendReviewCompletionNotification::class,
        ],

        // Training Events
        TrainingScheduled::class => [
            SendTrainingInvitation::class,
        ],

        // Safety Events
        SafetyIncidentReported::class => [
            NotifySafetyTeam::class,
        ],

        // Leave Events
        LeaveRequested::class => [
            UpdateBalanceOnLeaveRequest::class,
            NotifyManagerOfLeaveRequest::class,
            StartWorkflowOnLeaveRequest::class,
        ],

        LeaveApproved::class => [
            UpdateBalanceOnLeaveApproval::class,
            NotifyEmployeeOfLeaveApproval::class,
        ],

        LeaveRejected::class => [
            UpdateBalanceOnLeaveRejection::class,
            NotifyEmployeeOfLeaveRejection::class,
        ],

        LeaveCancelled::class => [
            UpdateBalanceOnLeaveCancellation::class,
            NotifyOnLeaveCancellation::class,
        ],

        // Payroll Events
        PayrollGenerated::class => [
            SendPayslipNotificationsNew::class,
        ],

        // Employee Milestone Events
        EmployeeBirthday::class => [
            SendBirthdayNotifications::class,
        ],

        WorkAnniversary::class => [
            SendWorkAnniversaryNotifications::class,
        ],

        // Document & Contract Events
        DocumentExpiring::class => [
            SendDocumentExpiryNotifications::class,
        ],

        ProbationEnding::class => [
            SendProbationEndingNotifications::class,
        ],

        ContractExpiring::class => [
            SendContractExpiryNotifications::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Explicit registration preferred for clarity
    }
}
