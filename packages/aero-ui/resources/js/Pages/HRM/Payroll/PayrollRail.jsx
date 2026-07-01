/**
 * PayrollRail — per-page context panel for the command shell's right rail.
 * Mirrors UsersRail: payroll overview + quick actions.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    PlayIcon,
    RectangleStackIcon,
    Squares2X2Icon,
    ReceiptPercentIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
    return (
        <HStack justify="between" align="center" className="users-rail-stat">
            <Text size="sm" tone="secondary">{label}</Text>
            <Text size="sm" weight={600}>{value ?? 0}</Text>
        </HStack>
    );
}

const fmtMoney = (v) => Number(v ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 });

export default function PayrollRail() {
    const { stats } = usePage().props;

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
                <VStack gap={1}>
                    <RailStat label="Total runs" value={stats?.total} />
                    <RailStat label="Approved"   value={stats?.approved} />
                    <RailStat label="Net paid"   value={fmtMoney(stats?.net_paid)} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <Link href="/hrm/payroll/runs/create" className="dash-rail-link">
                        <PlayIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>New payroll run</span>
                    </Link>
                    <Link href="/hrm/payroll/structures" className="dash-rail-link">
                        <RectangleStackIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Salary structures</span>
                    </Link>
                    <Link href="/hrm/payroll/components" className="dash-rail-link">
                        <Squares2X2Icon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Pay components</span>
                    </Link>
                    <Link href="/hrm/payroll/settings/tax" className="dash-rail-link">
                        <ReceiptPercentIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Tax settings</span>
                    </Link>
                </VStack>
            </VStack>
        </VStack>
    );
}
