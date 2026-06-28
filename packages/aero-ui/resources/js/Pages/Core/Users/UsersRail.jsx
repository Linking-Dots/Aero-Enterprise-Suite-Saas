/**
 * UsersRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Gives the Users
 * page useful at-hand context (live counts + quick actions) instead of an empty
 * context section in command mode. "Invite user" dispatches a window event the
 * Users page listens for (same decoupled pattern as the global search overlay).
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    EnvelopeIcon,
    UserPlusIcon,
    ShieldCheckIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
    return (
        <HStack justify="between" align="center" className="users-rail-stat">
            <Text size="sm" tone="secondary">{label}</Text>
            <Text size="sm" weight={600}>{value ?? 0}</Text>
        </HStack>
    );
}

export default function UsersRail() {
    const { stats } = usePage().props;
    const openInvite = () => window.dispatchEvent(new CustomEvent('aeos:open-invite'));

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
                <VStack gap={1}>
                    <RailStat label="Total users"     value={stats?.total} />
                    <RailStat label="Active"          value={stats?.active} />
                    <RailStat label="Deactivated"     value={stats?.inactive} />
                    <RailStat label="Pending invites" value={stats?.pending} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <button type="button" className="dash-rail-link" onClick={openInvite}>
                        <EnvelopeIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Invite user</span>
                    </button>
                    <Link href="/users/create" className="dash-rail-link">
                        <UserPlusIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Create user</span>
                    </Link>
                    <Link href="/roles" className="dash-rail-link">
                        <ShieldCheckIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Roles &amp; access</span>
                    </Link>
                </VStack>
            </VStack>
        </VStack>
    );
}
