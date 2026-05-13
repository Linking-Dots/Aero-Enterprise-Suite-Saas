<?php

declare(strict_types=1);

namespace Aero\HRM\Listeners;

use Aero\Contracts\DomainEventContract;
use Aero\Contracts\EmployeeServiceContract;
use Aero\Contracts\NotificationRoutingContract;
use Aero\HRM\Events\BaseHrmEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * HRM Event Subscriber
 *
 * Listens to all HRM domain events and handles:
 * 1. Audit logging (via DomainEventContract metadata)
 * 2. Notification routing (via HRMAC-powered NotificationRoutingContract)
 * 3. Cross-module event broadcasting
 *
 * This subscriber uses:
 * - employee_id exclusively (never user_id directly)
 * - HRMAC for recipient resolution (no hardcoded roles)
 * - EmployeeServiceContract for employee→user mapping when needed
 */
class HrmEventSubscriber implements ShouldQueue
{
    public function __construct(
        protected ?NotificationRoutingContract $notificationRouter = null,
        protected ?EmployeeServiceContract $employeeService = null
    ) {}

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            // Employee Events
            \Aero\HRM\Events\Employee\EmployeeCreated::class => 'handleEmployeeCreated',
            \Aero\HRM\Events\Employee\EmployeeUpdated::class => 'handleEmployeeUpdated',
            \Aero\HRM\Events\Employee\EmployeePromoted::class => 'handleEmployeePromoted',
            \Aero\HRM\Events\Employee\EmployeeResigned::class => 'handleEmployeeResigned',
            \Aero\HRM\Events\Employee\EmployeeTerminated::class => 'handleEmployeeTerminated',

            // Leave Events
            \Aero\HRM\Events\Leave\LeaveRequested::class => 'handleLeaveRequested',
            \Aero\HRM\Events\Leave\LeaveApproved::class => 'handleLeaveApproved',
            \Aero\HRM\Events\Leave\LeaveRejected::class => 'handleLeaveRejected',
            \Aero\HRM\Events\Leave\LeaveCancelled::class => 'handleLeaveCancelled',

            // Attendance Events
            \Aero\HRM\Events\Attendance\AttendancePunchedIn::class => 'handleAttendancePunchedIn',
            \Aero\HRM\Events\Attendance\AttendancePunchedOut::class => 'handleAttendancePunchedOut',
            \Aero\HRM\Events\Attendance\LateArrivalDetected::class => 'handleLateArrivalDetected',

            // Onboarding Events
            \Aero\HRM\Events\Onboarding\OnboardingStarted::class => 'handleOnboardingStarted',
            \Aero\HRM\Events\Onboarding\OnboardingCompleted::class => 'handleOnboardingCompleted',

            // Offboarding Events
            \Aero\HRM\Events\Offboarding\OffboardingStarted::class => 'handleOffboardingStarted',
            \Aero\HRM\Events\Offboarding\OffboardingCompleted::class => 'handleOffboardingCompleted',

            // Performance Events
            \Aero\HRM\Events\Performance\PerformanceReviewCompleted::class => 'handlePerformanceReviewCompleted',

            // Recruitment Events
            \Aero\HRM\Events\Recruitment\ApplicationReceived::class => 'handleApplicationReceived',
            \Aero\HRM\Events\Recruitment\InterviewScheduled::class => 'handleInterviewScheduled',
            \Aero\HRM\Events\Recruitment\OfferExtended::class => 'handleOfferExtended',

            // Safety Events
            \Aero\HRM\Events\Safety\SafetyIncidentReported::class => 'handleSafetyIncidentReported',

            // Training Events
            \Aero\HRM\Events\Training\TrainingScheduled::class => 'handleTrainingScheduled',

            // Root-level Events
            \Aero\HRM\Events\AttendanceLogged::class => 'handleAttendanceLogged',
            \Aero\HRM\Events\EmployeeBirthday::class => 'handleEmployeeBirthday',
            \Aero\HRM\Events\WorkAnniversary::class => 'handleWorkAnniversary',
            \Aero\HRM\Events\CandidateApplied::class => 'handleCandidateApplied',
            \Aero\HRM\Events\ContractExpiring::class => 'handleContractExpiring',
            \Aero\HRM\Events\DocumentExpiring::class => 'handleDocumentExpiring',
            \Aero\HRM\Events\PayrollGenerated::class => 'handlePayrollGenerated',
            \Aero\HRM\Events\ProbationEnding::class => 'handleProbationEnding',
        ];
    }

    // =========================================================================
    // EMPLOYEE EVENT HANDLERS
    // =========================================================================

    public function handleEmployeeCreated(\Aero\HRM\Events\Employee\EmployeeCreated $event): void
    {
        $this->processEvent($event, 'employee_created');
    }

    public function handleEmployeeUpdated(\Aero\HRM\Events\Employee\EmployeeUpdated $event): void
    {
        $this->processEvent($event, 'employee_updated');
    }

    public function handleEmployeePromoted(\Aero\HRM\Events\Employee\EmployeePromoted $event): void
    {
        $this->processEvent($event, 'employee_promoted', [
            'priority' => 'high',
            'notify_team' => true,
        ]);
    }

    public function handleEmployeeResigned(\Aero\HRM\Events\Employee\EmployeeResigned $event): void
    {
        $this->processEvent($event, 'employee_resigned', [
            'priority' => 'high',
            'trigger_offboarding' => true,
        ]);
    }

    public function handleEmployeeTerminated(\Aero\HRM\Events\Employee\EmployeeTerminated $event): void
    {
        $this->processEvent($event, 'employee_terminated', [
            'priority' => 'urgent',
            'trigger_offboarding' => true,
            'revoke_access' => $event->immediate,
        ]);
    }

    // =========================================================================
    // LEAVE EVENT HANDLERS
    // =========================================================================

    public function handleLeaveRequested(\Aero\HRM\Events\Leave\LeaveRequested $event): void
    {
        $this->processEvent($event, 'leave_requested', [
            'notify_approver' => true,
        ]);
    }

    public function handleLeaveApproved(\Aero\HRM\Events\Leave\LeaveApproved $event): void
    {
        $this->processEvent($event, 'leave_approved', [
            'notify_requester' => true,
            'update_calendar' => true,
        ]);
    }

    public function handleLeaveRejected(\Aero\HRM\Events\Leave\LeaveRejected $event): void
    {
        $this->processEvent($event, 'leave_rejected', [
            'notify_requester' => true,
        ]);
    }

    public function handleLeaveCancelled(\Aero\HRM\Events\Leave\LeaveCancelled $event): void
    {
        $this->processEvent($event, 'leave_cancelled');
    }

    // =========================================================================
    // ATTENDANCE EVENT HANDLERS
    // =========================================================================

    public function handleAttendancePunchedIn(\Aero\HRM\Events\Attendance\AttendancePunchedIn $event): void
    {
        $this->processEvent($event, 'attendance_punched_in');
    }

    public function handleAttendancePunchedOut(\Aero\HRM\Events\Attendance\AttendancePunchedOut $event): void
    {
        $this->processEvent($event, 'attendance_punched_out');
    }

    public function handleLateArrivalDetected(\Aero\HRM\Events\Attendance\LateArrivalDetected $event): void
    {
        $this->processEvent($event, 'late_arrival_detected', [
            'notify_manager' => true,
        ]);
    }

    public function handleAttendanceLogged(\Aero\HRM\Events\AttendanceLogged $event): void
    {
        $this->processEvent($event, 'attendance_logged');
    }

    // =========================================================================
    // ONBOARDING EVENT HANDLERS
    // =========================================================================

    public function handleOnboardingStarted(\Aero\HRM\Events\Onboarding\OnboardingStarted $event): void
    {
        $this->processEvent($event, 'onboarding_started', [
            'send_welcome_email' => true,
            'assign_tasks' => true,
        ]);
    }

    public function handleOnboardingCompleted(\Aero\HRM\Events\Onboarding\OnboardingCompleted $event): void
    {
        $this->processEvent($event, 'onboarding_completed', [
            'grant_full_access' => true,
        ]);
    }

    // =========================================================================
    // OFFBOARDING EVENT HANDLERS
    // =========================================================================

    public function handleOffboardingStarted(\Aero\HRM\Events\Offboarding\OffboardingStarted $event): void
    {
        $this->processEvent($event, 'offboarding_started', [
            'schedule_exit_interview' => true,
            'start_asset_return' => true,
        ]);
    }

    public function handleOffboardingCompleted(\Aero\HRM\Events\Offboarding\OffboardingCompleted $event): void
    {
        $this->processEvent($event, 'offboarding_completed', [
            'revoke_access' => true,
            'process_settlement' => true,
        ]);
    }

    // =========================================================================
    // PERFORMANCE EVENT HANDLERS
    // =========================================================================

    public function handlePerformanceReviewCompleted(\Aero\HRM\Events\Performance\PerformanceReviewCompleted $event): void
    {
        $this->processEvent($event, 'performance_review_completed', [
            'notify_employee' => true,
            'trigger_salary_review' => $event->overallRating >= 4.0,
        ]);
    }

    // =========================================================================
    // RECRUITMENT EVENT HANDLERS
    // =========================================================================

    public function handleApplicationReceived(\Aero\HRM\Events\Recruitment\ApplicationReceived $event): void
    {
        $this->processEvent($event, 'application_received', [
            'send_acknowledgment' => true,
        ]);
    }

    public function handleInterviewScheduled(\Aero\HRM\Events\Recruitment\InterviewScheduled $event): void
    {
        $this->processEvent($event, 'interview_scheduled', [
            'send_calendar_invites' => true,
        ]);
    }

    public function handleOfferExtended(\Aero\HRM\Events\Recruitment\OfferExtended $event): void
    {
        $this->processEvent($event, 'offer_extended', [
            'priority' => 'high',
        ]);
    }

    public function handleCandidateApplied(\Aero\HRM\Events\CandidateApplied $event): void
    {
        $this->processEvent($event, 'candidate_applied');
    }

    // =========================================================================
    // SAFETY EVENT HANDLERS
    // =========================================================================

    public function handleSafetyIncidentReported(\Aero\HRM\Events\Safety\SafetyIncidentReported $event): void
    {
        $priority = $event->requiresImmediateAction ? 'urgent' : 'high';

        $this->processEvent($event, 'safety_incident_reported', [
            'priority' => $priority,
            'notify_safety_team' => true,
            'initiate_investigation' => true,
        ]);
    }

    // =========================================================================
    // TRAINING EVENT HANDLERS
    // =========================================================================

    public function handleTrainingScheduled(\Aero\HRM\Events\Training\TrainingScheduled $event): void
    {
        $this->processEvent($event, 'training_scheduled', [
            'send_invitations' => true,
            'notify_enrolled' => true,
        ]);
    }

    // =========================================================================
    // MISCELLANEOUS EVENT HANDLERS
    // =========================================================================

    public function handleEmployeeBirthday(\Aero\HRM\Events\EmployeeBirthday $event): void
    {
        $this->processEvent($event, 'employee_birthday', [
            'send_wishes' => true,
        ]);
    }

    public function handleWorkAnniversary(\Aero\HRM\Events\WorkAnniversary $event): void
    {
        $this->processEvent($event, 'work_anniversary', [
            'send_congratulations' => true,
        ]);
    }

    public function handleContractExpiring(\Aero\HRM\Events\ContractExpiring $event): void
    {
        $this->processEvent($event, 'contract_expiring', [
            'priority' => 'high',
            'days_remaining' => $event->daysRemaining,
        ]);
    }

    public function handleDocumentExpiring(\Aero\HRM\Events\DocumentExpiring $event): void
    {
        $this->processEvent($event, 'document_expiring', [
            'notify_employee' => true,
        ]);
    }

    public function handlePayrollGenerated(\Aero\HRM\Events\PayrollGenerated $event): void
    {
        $this->processEvent($event, 'payroll_generated', [
            'priority' => 'high',
        ]);
    }

    public function handleProbationEnding(\Aero\HRM\Events\ProbationEnding $event): void
    {
        $this->processEvent($event, 'probation_ending', [
            'notify_manager' => true,
            'schedule_review' => true,
        ]);
    }

    // =========================================================================
    // CORE PROCESSING METHODS
    // =========================================================================

    /**
     * Process a domain event - handles audit logging and notification routing.
     *
     * @param  BaseHrmEvent&DomainEventContract  $event
     */
    protected function processEvent(BaseHrmEvent $event, string $eventType, array $options = []): void
    {
        try {
            // 1. Log the event for audit purposes
            $this->logAudit($event, $eventType);

            // 2. Route notifications if the event should notify
            if ($event->shouldNotify() && $this->notificationRouter) {
                $this->routeNotifications($event, $eventType, $options);
            }

            // 3. Dispatch any follow-up actions based on options
            $this->dispatchFollowUpActions($event, $eventType, $options);
        } catch (\Throwable $e) {
            Log::error('Failed to process HRM event', [
                'event_type' => $eventType,
                'entity_type' => $event->getEntityType(),
                'entity_id' => $event->getEntityId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Log the event for audit purposes.
     */
    protected function logAudit(BaseHrmEvent $event, string $eventType): void
    {
        $metadata = $event->getAuditMetadata();

        Log::channel('hrm_audit')->info("HRM Event: {$eventType}", [
            'module' => $metadata['module'],
            'sub_module' => $metadata['sub_module'],
            'component' => $metadata['component'],
            'action' => $metadata['action'],
            'entity_type' => $metadata['entity_type'],
            'entity_id' => $metadata['entity_id'],
            'actor_employee_id' => $metadata['actor_employee_id'],
            'timestamp' => $metadata['timestamp'],
        ]);
    }

    /**
     * Route notifications to appropriate recipients via HRMAC.
     */
    protected function routeNotifications(BaseHrmEvent $event, string $eventType, array $options): void
    {
        if (! $this->notificationRouter) {
            return;
        }

        // Get notification context from the event
        $context = $event->getNotificationContext();
        $context['event_type'] = $eventType;
        $context['options'] = $options;

        // Get recipients via HRMAC-based routing
        $recipients = $this->notificationRouter->getRecipients(
            $event->getModuleCode(),
            $event->getSubModuleCode(),
            $event->getComponentCode(),
            $event->getActionCode(),
            $context
        );

        if ($recipients->isEmpty()) {
            Log::debug("No notification recipients found for {$eventType}", [
                'module' => $event->getModuleCode(),
                'sub_module' => $event->getSubModuleCode(),
            ]);

            return;
        }

        // Dispatch notifications to recipients
        $this->sendNotifications($recipients, $event, $eventType, $options);
    }

    /**
     * Send notifications to the resolved recipients.
     */
    protected function sendNotifications(Collection $recipients, BaseHrmEvent $event, string $eventType, array $options): void
    {
        // Get the notification class for this event type
        $notificationClass = $this->resolveNotificationClass($eventType);

        if (! $notificationClass || ! class_exists($notificationClass)) {
            Log::debug("No notification class found for event type: {$eventType}");

            return;
        }

        try {
            $notification = new $notificationClass($event, $options);
            Notification::send($recipients, $notification);

            Log::info("Notifications sent for {$eventType}", [
                'recipient_count' => $recipients->count(),
                'notification_class' => $notificationClass,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send notifications for {$eventType}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the notification class for a given event type.
     */
    protected function resolveNotificationClass(string $eventType): ?string
    {
        // Map event types to notification classes
        $notificationMap = [
            'leave_requested' => \Aero\HRM\Notifications\LeaveRequestedNotification::class,
            'leave_approved' => \Aero\HRM\Notifications\LeaveApprovedNotification::class,
            'leave_rejected' => \Aero\HRM\Notifications\LeaveRejectedNotification::class,
            'employee_created' => \Aero\HRM\Notifications\EmployeeCreatedNotification::class,
            'employee_promoted' => \Aero\HRM\Notifications\EmployeePromotedNotification::class,
            'safety_incident_reported' => \Aero\HRM\Notifications\SafetyIncidentNotification::class,
            'contract_expiring' => \Aero\HRM\Notifications\ContractExpiringNotification::class,
            'probation_ending' => \Aero\HRM\Notifications\ProbationEndingNotification::class,
            'onboarding_started' => \Aero\HRM\Notifications\OnboardingStartedNotification::class,
            'offboarding_started' => \Aero\HRM\Notifications\OffboardingStartedNotification::class,
            'performance_review_completed' => \Aero\HRM\Notifications\PerformanceReviewNotification::class,
            'training_scheduled' => \Aero\HRM\Notifications\TrainingScheduledNotification::class,
        ];

        return $notificationMap[$eventType] ?? null;
    }

    /**
     * Dispatch follow-up actions based on event options.
     */
    protected function dispatchFollowUpActions(BaseHrmEvent $event, string $eventType, array $options): void
    {
        // Trigger offboarding workflow if needed
        if (! empty($options['trigger_offboarding'])) {
            $this->triggerOffboarding($event);
        }

        // Revoke access if immediate termination
        if (! empty($options['revoke_access'])) {
            $this->revokeAccess($event);
        }

        // Grant full access after onboarding
        if (! empty($options['grant_full_access'])) {
            $this->grantFullAccess($event);
        }
    }

    /**
     * Trigger offboarding workflow.
     */
    protected function triggerOffboarding(BaseHrmEvent $event): void
    {
        // This would dispatch a job to create offboarding record
        Log::info('Offboarding triggered', [
            'entity_type' => $event->getEntityType(),
            'entity_id' => $event->getEntityId(),
        ]);
    }

    /**
     * Revoke system access for an employee.
     */
    protected function revokeAccess(BaseHrmEvent $event): void
    {
        // This would dispatch a job to revoke user access
        Log::info('Access revocation triggered', [
            'entity_type' => $event->getEntityType(),
            'entity_id' => $event->getEntityId(),
        ]);
    }

    /**
     * Grant full system access after onboarding.
     */
    protected function grantFullAccess(BaseHrmEvent $event): void
    {
        // This would dispatch a job to upgrade user access
        Log::info('Full access grant triggered', [
            'entity_type' => $event->getEntityType(),
            'entity_id' => $event->getEntityId(),
        ]);
    }
}
