/**
 * DashboardRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. This gives the
 * dashboard useful at-hand context (quick actions + shortcuts) instead of an
 * empty context section in command mode.
 */
import { Link } from '@inertiajs/react';
import { VStack, Text } from '@aero/ui';
import {
    UserPlusIcon,
    ShieldCheckIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
} from '@heroicons/react/24/outline';

const QUICK_ACTIONS = [
    { label: 'Create user',    href: '/users/create',    Icon: UserPlusIcon },
    { label: 'Roles & access', href: '/roles',           Icon: ShieldCheckIcon },
    { label: 'Audit logs',     href: '/audit-logs',      Icon: ClipboardDocumentListIcon },
    { label: 'Settings',       href: '/settings/system', Icon: Cog6ToothIcon },
];

export default function DashboardRail() {
    const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform || '');

    return (
        <VStack gap={5} className="dash-rail">
            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
                <VStack gap={1}>
                    {QUICK_ACTIONS.map(({ label, href, Icon }) => (
                        <Link key={href} href={href} className="dash-rail-link">
                            <Icon className="aeos-icon-sm" aria-hidden="true" />
                            <span>{label}</span>
                        </Link>
                    ))}
                </VStack>
            </VStack>

            <VStack gap={2}>
                <Text size="xs" tone="tertiary" mono>SHORTCUTS</Text>
                <div className="dash-rail-shortcut">
                    <span>Search</span>
                    <kbd className="dash-rail-kbd">{isMac ? '⌘K' : 'Ctrl K'}</kbd>
                </div>
            </VStack>
        </VStack>
    );
}
