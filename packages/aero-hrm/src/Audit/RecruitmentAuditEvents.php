<?php

declare(strict_types=1);

namespace Aero\HRM\Audit;

final class RecruitmentAuditEvents
{
    public const JOB_CREATED = 'recruitment.job.created';

    public const JOB_UPDATED = 'recruitment.job.updated';

    public const JOB_PUBLISHED = 'recruitment.job.published';

    public const JOB_CLOSED = 'recruitment.job.closed';

    public const APPLICATION_STAGE_CHANGED = 'recruitment.application.stage_changed';

    public const APPLICATION_REJECTED = 'recruitment.application.rejected';

    public const INTERVIEW_SCHEDULED = 'recruitment.interview.scheduled';

    public const INTERVIEW_RESCHEDULED = 'recruitment.interview.rescheduled';

    public const OFFER_SENT = 'recruitment.offer.sent';

    public const EMPLOYEE_ONBOARDED = 'recruitment.onboarding.completed';
}
