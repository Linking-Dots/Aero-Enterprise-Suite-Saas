<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    /**
     * Enroll multiple employees into a session.
     *
     * @param  array<int>  $employeeIds
     * @return array<TrainingEnrollment>
     */
    public function enrollMany(int $sessionId, array $employeeIds, mixed $actor, string $source = 'admin'): array
    {
        return DB::transaction(function () use ($sessionId, $employeeIds, $actor, $source): array {
            $session = TrainingSession::findOrFail($sessionId);
            $enrolledCount = $session->enrolledCount();

            $created = [];

            foreach ($employeeIds as $employeeId) {
                // Skip if already enrolled (any status)
                $exists = TrainingEnrollment::where('session_id', $sessionId)
                    ->where('employee_id', $employeeId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $status = ($enrolledCount < $session->capacity)
                    ? TrainingEnrollment::STATUS_ENROLLED
                    : TrainingEnrollment::STATUS_WAITLISTED;

                /** @var TrainingEnrollment $enrollment */
                $enrollment = TrainingEnrollment::create([
                    'session_id' => $sessionId,
                    'employee_id' => $employeeId,
                    'status' => $status,
                    'source' => $source,
                    'enrolled_by' => $actor->id,
                ]);

                if ($status === TrainingEnrollment::STATUS_ENROLLED) {
                    $enrolledCount++;
                }

                $this->audit->log(
                    event: TrainingAuditEvents::TRAINING_ENROLLMENT_CREATED,
                    action: 'enrolled',
                    subject: $enrollment,
                    description: "Employee #{$employeeId} enrolled in session #{$sessionId} with status '{$status}'.",
                );

                $created[] = $enrollment;
            }

            return $created;
        });
    }

    /**
     * Mark attendance for a batch of enrollments.
     *
     * @param  array<int, string>  $attendanceMap  [enrollment_id => 'attended'|'no_show']
     * @return array<TrainingEnrollment>
     */
    public function markAttendance(TrainingSession $session, array $attendanceMap, mixed $actor): array
    {
        return DB::transaction(function () use ($session, $attendanceMap, $actor): array {
            $enrollmentIds = array_keys($attendanceMap);

            $enrollments = TrainingEnrollment::whereIn('id', $enrollmentIds)
                ->where('session_id', $session->id)
                ->get();

            $updated = [];

            foreach ($enrollments as $enrollment) {
                $attendanceStatus = $attendanceMap[$enrollment->id];

                $enrollment->status = $attendanceStatus;
                $enrollment->marked_by = $actor->id;

                if ($attendanceStatus === TrainingEnrollment::STATUS_ATTENDED) {
                    $enrollment->attended_at = now();
                }

                $enrollment->save();

                $this->audit->log(
                    event: TrainingAuditEvents::TRAINING_ATTENDANCE_MARKED,
                    action: 'attendance_marked',
                    subject: $enrollment,
                    description: "Attendance marked as '{$attendanceStatus}' for enrollment #{$enrollment->id}.",
                );

                $updated[] = $enrollment;
            }

            return $updated;
        });
    }

    public function cancel(TrainingEnrollment $enrollment, mixed $actor): void
    {
        DB::transaction(function () use ($enrollment, $actor): void {
            $sessionId = $enrollment->session_id;

            $enrollment->status = TrainingEnrollment::STATUS_CANCELLED;
            $enrollment->save();

            $this->audit->log(
                event: TrainingAuditEvents::TRAINING_ENROLLMENT_CANCELLED,
                action: 'cancelled',
                subject: $enrollment,
                description: "Enrollment #{$enrollment->id} cancelled for session #{$sessionId}.",
                metadata: ['cancelled_by' => $actor->id],
            );

            // Promote first waitlisted enrollment to enrolled
            $waitlisted = TrainingEnrollment::where('session_id', $sessionId)
                ->where('status', TrainingEnrollment::STATUS_WAITLISTED)
                ->oldest()
                ->first();

            if ($waitlisted) {
                $waitlisted->status = TrainingEnrollment::STATUS_ENROLLED;
                $waitlisted->save();
            }
        });
    }
}
