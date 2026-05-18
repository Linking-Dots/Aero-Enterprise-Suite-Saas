<?php

declare(strict_types=1);

namespace Aero\HRM\Audit;

final class SelfServiceAuditEvents
{
    public const PAYSLIP_VIEWED = 'self_service.payslip.viewed';

    public const PAYSLIP_DOWNLOADED = 'self_service.payslip.downloaded';

    public const LEAVE_APPLIED = 'self_service.leave.applied';

    public const LEAVE_CANCELLED = 'self_service.leave.cancelled';

    public const PROFILE_UPDATED = 'self_service.profile.updated';
}
