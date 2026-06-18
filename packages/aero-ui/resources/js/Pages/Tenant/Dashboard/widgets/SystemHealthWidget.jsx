/**
 * W12 · SystemHealthWidget
 *
 * Full-width footer card displaying service statuses (DB / Cache / Queue),
 * failed-jobs count with a drill-down link, platform version, and
 * quick-action ghost buttons (Settings / Audit logs / Health report).
 *
 * Failed-jobs count is now an actionable link to the Horizon/queue panel
 * rather than a passive badge — resolving the original gap.
 */

import { Link } from '@inertiajs/react';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Divider,
    Avatar, Button,
} from '@aero/ui';
import { usePage } from '@inertiajs/react';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

const SERVICE_INTENT = {
    healthy:   'success',
    degraded:  'amber',
    unhealthy: 'danger',
    unknown:   'neutral',
};

function ServicePill({ name, status }) {
    const intent = SERVICE_INTENT[status] ?? 'neutral';
    return (
        <Badge intent={intent} dot size="sm">{name}</Badge>
    );
}

export function SystemHealthWidget({ systemHealth: initialData }) {
    const { auth }   = usePage().props;
    const user       = auth?.user;
    // roles may arrive as strings or role objects — coerce to display strings.
    const userRoles  = (user?.roles ?? []).map(r => (typeof r === 'string' ? r : (r?.name ?? r?.title ?? ''))).filter(Boolean);

    const { data, loading, error, refresh } = useWidgetRefresh('systemHealth', initialData);

    const services    = data?.services ?? [];
    const failedJobs  = data?.failedJobs ?? 0;
    const overall     = data?.overall ?? 'unknown';
    const platform    = data?.platform ?? {};

    return (
        <Card className="aeos-col-span-4 dash-health-card">
            <HStack gap={4} align="center" wrap="wrap">

                {/* ── User identity ── */}
                {user && (
                    <>
                        <HStack gap={3} align="center">
                            <Avatar name={user.name} size={36} />
                            <VStack gap={0}>
                                <Text size="sm" weight={500}>{user.name}</Text>
                                <Text size="xs" tone="tertiary" mono>{user.email}</Text>
                                {userRoles[0] && (
                                    <Badge intent="neutral" size="sm">{userRoles[0]}</Badge>
                                )}
                            </VStack>
                        </HStack>
                        <span className="dash-health-divider" aria-hidden="true" />
                    </>
                )}

                {/* ── Services ── */}
                <VStack gap={2} style={{ flex: 1, minWidth: 200 }}>
                    <Eyebrow tone="primary">System health</Eyebrow>
                    <HStack gap={2} wrap="wrap">
                        <Badge intent={SERVICE_INTENT[overall] ?? 'neutral'} dot>
                            {overall}
                        </Badge>
                        {services.map(s => (
                            <ServicePill key={s.name} name={s.name} status={s.status} />
                        ))}
                        <Badge
                            intent={failedJobs > 0 ? 'danger' : 'success'}
                            as={failedJobs > 0 ? Link : 'span'}
                            href={failedJobs > 0 ? route('core.audit-logs.queues') : undefined}
                        >
                            {failedJobs} failed job{failedJobs !== 1 ? 's' : ''}
                            {failedJobs > 0 ? ' ↗' : ''}
                        </Badge>
                        <RefreshButton onRefresh={refresh} loading={loading} />
                    </HStack>
                    {error && <Text size="xs" tone="secondary">{error}</Text>}
                </VStack>

                {/* ── Platform version ── */}
                {platform?.version && (
                    <>
                        <span className="dash-health-divider" aria-hidden="true" />
                        <VStack gap={0}>
                            <Text size="xs" tone="tertiary">Platform</Text>
                            <Text size="sm" weight={500}>AEOS {platform.version}</Text>
                            <Text size="xs" tone="tertiary" mono>
                                {platform.phpVersion ? `PHP ${platform.phpVersion}` : ''}
                                {platform.phpVersion && platform.laravelVersion ? ' · ' : ''}
                                {platform.laravelVersion ? `Laravel ${platform.laravelVersion}` : ''}
                            </Text>
                        </VStack>
                    </>
                )}

                <Box grow />

                {/* ── Quick actions ── */}
                <HStack gap={2}>
                    <Button
                        as={Link}
                        href={route('core.settings.system')}
                        intent="ghost"
                        size="sm"
                    >
                        Settings
                    </Button>
                    <Button
                        as={Link}
                        href={route('core.audit-logs.index')}
                        intent="ghost"
                        size="sm"
                    >
                        Audit logs
                    </Button>
                    <Button
                        as={Link}
                        href={route('core.system-health.index')}
                        intent="outline"
                        size="sm"
                    >
                        Health report
                    </Button>
                </HStack>

            </HStack>
        </Card>
    );
}
