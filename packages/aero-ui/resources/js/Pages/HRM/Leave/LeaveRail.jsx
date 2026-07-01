/**
 * LeaveRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Mirrors the
 * UsersRail pattern: live overview counts + quick actions, decoupled from the
 * page's local state (reads shared page props).
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    PlusIcon,
    TagIcon,
    ScaleIcon,
    CalendarDaysIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
    return (
        <HStack justify="between" align="center" className="users-rail-stat">
            <Text size="sm" tone="secondary">{label}</Text>
            <Text size="sm" weight={600}>{value ?? 0}</Text>
        </HStack>
    );
}

export default function LeaveRail() {
    const { stats } = usePage().props;

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
                <VStack gap={1}>
                    <RailStat label="Total requests" value={stats?.total} />
                    <RailStat label="Pending"        value={stats?.pending} />
                    <RailStat label="Approved"       value={stats?.approved} />
                    <RailStat label="Rejected"       value={stats?.rejected} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <Link href="/hrm/leave/applications/create" className="dash-rail-link">
                        <PlusIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Apply for leave</span>
                    </Link>
                    <Link href="/hrm/leave/types" className="dash-rail-link">
                        <TagIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Leave types</span>
                    </Link>
                    <Link href="/hrm/leave/balance" className="dash-rail-link">
                        <ScaleIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Leave balances</span>
                    </Link>
                    <Link href="/hrm/leave/calendar" className="dash-rail-link">
                        <CalendarDaysIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Holiday calendar</span>
                    </Link>
                </VStack>
            </VStack>
        </VStack>
    );
}
