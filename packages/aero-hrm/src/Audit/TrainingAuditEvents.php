<?php

declare(strict_types=1);

namespace Aero\HRM\Audit;

final class TrainingAuditEvents
{
    public const COURSE_CREATED = 'training.course.created';

    public const COURSE_UPDATED = 'training.course.updated';

    public const COURSE_DELETED = 'training.course.deleted';

    public const SESSION_SCHEDULED = 'training.session.scheduled';

    public const SESSION_UPDATED = 'training.session.updated';

    public const TRAINING_ENROLLMENT_CREATED = 'training.enrollment.created';

    public const TRAINING_ENROLLMENT_CANCELLED = 'training.enrollment.cancelled';

    public const TRAINING_ATTENDANCE_MARKED = 'training.attendance.marked';

    public const TRAINING_FEEDBACK_SUBMITTED = 'training.feedback.submitted';
}
