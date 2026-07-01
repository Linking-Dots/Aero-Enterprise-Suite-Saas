/**
 * AttendanceRail — per-page context panel for the command shell's right rail.
 * Mirrors UsersRail: today's attendance overview + quick actions.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    CalendarDaysIcon,
    UsersIcon,
    ClockIcon,
    BanknotesIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
    return (
        <HStack justify="between" align="center" className="users-rail-stat">
            <Text size="sm" tone="secondary">{label}</Text>
            <Text size="sm" weight={600}>{value ?? 0}</Text>
        </HStack>
    );
}

export default function AttendanceRail() {
    const { stats } = usePage().props;

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>THIS DAY</Text>
                <VStack gap={1}>
                    <RailStat label="Marked"  value={stats?.total} />
                    <RailStat label="Present" value={stats?.present} />
                    <RailStat label="Late"    value={stats?.late} />
                    <RailStat label="Absent"  value={stats?.absent} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <Link href="/hrm/attendance/monthly" className="dash-rail-link">
                        <CalendarDaysIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Monthly view</span>
                    </Link>
                    <Link href="/hrm/employees" className="dash-rail-link">
                        <UsersIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Employees</span>
                    </Link>
                    <Link href="/hrm/leave/applications" className="dash-rail-link">
                        <ClockIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Leave</span>
                    </Link>
                    <Link href="/hrm/payroll/runs" className="dash-rail-link">
                        <BanknotesIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Payroll</span>
                    </Link>
                </VStack>
            </VStack>
        </VStack>
    );
}
