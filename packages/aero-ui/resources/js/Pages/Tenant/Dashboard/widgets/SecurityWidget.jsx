/**
 * W5 · SecurityWidget
 *
 * Displays MFA adoption %, failed logins, active sessions,
 * and the last security event. Replaces the previous fake
 * Math.min(x * 10, 100) progress-bar hack with real percentage values.
 *
 * An inline warning Alert fires if failedLogins > 0 so the admin
 * is never unaware of a bad-actor signal.
 */

import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge,
    Divider, Alert,
    ProgressRow,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

function MetricMini({ label, value, intent }) {
    return (
        <VStack gap={1} className="dash-metric-mini">
            <Text size="xs" tone="tertiary">{label}</Text>
            <Text as="div" className={`dash-kpi-value dash-kpi-value--${intent ?? 'default'}`}>
                {value ?? '—'}
            </Text>
        </VStack>
    );
}

export function SecurityWidget({ securityOverview: initialData }) {
    const { data, loading, error, refresh } = useWidgetRefresh('securityOverview', initialData);

    const failedLogins    = data?.failedLoginsLast24h ?? 0;
    const mfaPercent      = data?.mfaAdoptionPercent ?? 0;
    const activeSessions  = data?.activeSessions ?? 0;
    const lastEvent       = data?.lastSecurityEvent ?? null;
    const hasAlert        = failedLogins > 0;

    const overallIntent = hasAlert ? 'warning' : 'success';
    const overallLabel  = hasAlert ? `${failedLogins} failed login${failedLogins !== 1 ? 's' : ''}` : 'No alerts';

    return (
        <Card className="aeos-col-span-2">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">Security</Eyebrow>
                        <Text size="sm" tone="secondary">Posture overview</Text>
                    </VStack>
                    <Box grow />
                    <Badge intent={overallIntent} dot>{overallLabel}</Badge>
                    <RefreshButton onRefresh={refresh} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            {hasAlert && (
                <Alert intent="warning" title={`${failedLogins} failed login attempt${failedLogins !== 1 ? 's' : ''} in the last 24 h`} />
            )}

            {!loading && (
                <>
                    {/* ── Metric mini cards ── */}
                    <HStack gap={2} className="dash-metric-mini-row">
                        <MetricMini label="Failed logins (24h)" value={failedLogins} intent={failedLogins > 0 ? 'danger' : 'success'} />
                        <MetricMini label="MFA adoption"        value={`${mfaPercent}%`} intent="default" />
                        <MetricMini label="Active sessions"     value={activeSessions}  intent="default" />
                    </HStack>

                    {/* ── Progress bars ── */}
                    <VStack gap={3} className="aeos-mt-3">
                        <ProgressRow
                            label="MFA adoption"
                            value={mfaPercent}
                            max={100}
                            intent={mfaPercent >= 80 ? 'success' : mfaPercent >= 50 ? 'cyan' : 'amber'}
                        />
                        <ProgressRow
                            label="Failed logins"
                            value={Math.min(failedLogins, 100)}
                            max={100}
                            intent={failedLogins > 0 ? 'amber' : 'success'}
                        />
                        <ProgressRow
                            label="Active sessions (30 m)"
                            value={activeSessions}
                            max={data?.totalUsers ?? 100}
                            intent="cyan"
                        />
                    </VStack>

                    {lastEvent && (
                        <>
                            <Divider />
                            <HStack gap={2}>
                                <Text size="xs" tone="tertiary">Last security event:</Text>
                                <Badge intent="neutral" mono size="sm">{lastEvent.action}</Badge>
                                <Text size="sm" tone="secondary">
                                    {lastEvent.user} · {lastEvent.timeAgo}
                                </Text>
                            </HStack>
                        </>
                    )}
                </>
            )}
        </Card>
    );
}
