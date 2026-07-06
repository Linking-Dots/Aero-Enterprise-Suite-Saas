/**
 * RolesRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Gives the shared
 * RBAC page useful at-hand context (live role counts + quick actions) instead of an
 * empty context section in command mode. "Create role" / "Assign user" dispatch
 * window events the page listens for (same decoupled pattern as UsersRail).
 *
 * Shared across tenant + platform contexts: the "User management" quick link
 * resolves its route name per-scope rather than hardcoding a context string.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
    PlusIcon,
    UserPlusIcon,
    UsersIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
    return (
        <HStack justify="between" align="center">
            <Text size="sm" tone="secondary">{label}</Text>
            <Text size="sm" weight={600}>{value ?? 0}</Text>
        </HStack>
    );
}

export default function RolesRail({ roles, scope }) {
    const pageProps = usePage().props;
    roles = roles ?? pageProps.roles ?? [];
    scope = scope ?? pageProps.scope ?? 'tenant';

    const total      = roles.length;
    const protectedN = roles.filter(r => r.is_protected).length;
    const custom     = total - protectedN;
    const assigns    = roles.reduce((sum, r) => sum + (r.users_count ?? 0), 0);

    const fire = name => () => window.dispatchEvent(new CustomEvent(name));

    // Roles & access lives alongside a separate Users surface; resolve its route
    // name per-scope rather than hardcoding a context string inside a route() call.
    const usersIndexRouteName = scope === 'platform' ? 'platform.admin.users.index' : 'core.users.index';

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
                <VStack gap={1}>
                    <RailStat label="Total roles"  value={total} />
                    <RailStat label="Custom"       value={custom} />
                    <RailStat label="Protected"    value={protectedN} />
                    <RailStat label="Assignments"  value={assigns} />
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    <button type="button" className="dash-rail-link" onClick={fire('aeos:open-create-role')}>
                        <PlusIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Create role</span>
                    </button>
                    <button type="button" className="dash-rail-link" onClick={fire('aeos:open-assign-role')}>
                        <UserPlusIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>Assign user</span>
                    </button>
                    <Link href={route(usersIndexRouteName)} className="dash-rail-link">
                        <UsersIcon className="aeos-icon-sm" aria-hidden="true" />
                        <span>User management</span>
                    </Link>
                </VStack>
            </VStack>
        </VStack>
    );
}
