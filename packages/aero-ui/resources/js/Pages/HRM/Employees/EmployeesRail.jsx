/**
 * EmployeesRail — per-page context panel for the command shell's right rail.
 * Mirrors UsersRail: live overview counts + quick actions, reads shared page props.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    UserPlusIcon,
    ClockIcon,
    CalendarDaysIcon,
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

export default function EmployeesRail() {
    const { stats } = usePage().props;

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>WORKFORCE</Text>
                <VStack gap={1}>
                    <RailStat label="Total headcount" value={stats?.total} />
                    <RailStat label="Active"          value={stats?.active} />
                    <RailStat label="On leave"        value={stats?.on_leave} />
                    <RailStat label="Terminated"      value={stats?.terminated} />
                    <RailStat label="Departments"     value={stats?.departments} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <Link href="/hrm/employees/create" className="dash-rail-link">
                        <UserPlusIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>New employee</span>
                    </Link>
                    <Link href="/hrm/attendance/daily" className="dash-rail-link">
                        <ClockIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Attendance</span>
                    </Link>
                    <Link href="/hrm/leave/applications" className="dash-rail-link">
                        <CalendarDaysIcon className="aeos-icon-sm" aria-hidden="true" />
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
