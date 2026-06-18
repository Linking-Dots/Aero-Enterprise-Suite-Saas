/**
 * W4 · SessionsWidget
 *
 * Shows online-now, today, and this-week session counters,
 * a recent-sessions list, and the device-type breakdown
 * (previously fetched but never rendered — now wired).
 */

import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge,
    Divider, ProgressRow,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

function SessionDot({ active }) {
    return (
        <span
            className={active ? 'dash-session-dot dash-session-dot--online' : 'dash-session-dot'}
            aria-hidden="true"
        />
    );
}

function StatCell({ value, label, highlight }) {
    return (
        <VStack gap={0} align="center" className="dash-session-stat">
            <Text as="div" className={highlight ? 'dash-kpi-value dash-kpi-value--success' : 'dash-kpi-value'}>
                {value ?? '—'}
            </Text>
            <Text size="xs" tone="tertiary">{label}</Text>
        </VStack>
    );
}

const DEVICE_INTENT = { desktop: 'cyan', mobile: 'success', tablet: 'amber' };

export function SessionsWidget({ sessionsData: initialData }) {
    const { data, loading, error, refresh } = useWidgetRefresh('sessionsData', initialData);

    const recentSessions  = data?.recentSessions ?? [];
    const deviceBreakdown = data?.deviceBreakdown ?? [];

    return (
        <Card className="aeos-col-span-1">
            <CardHeader>
                <HStack gap={2} align="center">
                    <Eyebrow tone="primary">Sessions</Eyebrow>
                    <Box grow />
                    <RefreshButton onRefresh={refresh} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            {!loading && (
                <>
                    {/* ── Counters ── */}
                    <HStack gap={0} className="dash-session-stats-row">
                        <StatCell value={data?.onlineNow}   label="Online now" highlight />
                        <StatCell value={data?.activeToday}  label="Today" />
                        <StatCell value={data?.activeThisWeek?.toLocaleString()} label="This week" />
                    </HStack>

                    <Divider />

                    {/* ── Recent sessions ── */}
                    <Text size="xs" tone="tertiary" className="dash-section-eyebrow">
                        Recent sessions
                    </Text>
                    <VStack gap={2} className="aeos-mt-1">
                        {recentSessions.slice(0, 5).map((s, i) => (
                            <HStack key={i} gap={2} align="center">
                                <SessionDot active={s.isActive} />
                                <Text size="sm" style={{ flex: 1 }}>{s.userName}</Text>
                                <Text size="xs" tone="tertiary" mono>{s.timeAgo}</Text>
                            </HStack>
                        ))}
                    </VStack>

                    {deviceBreakdown.length > 0 && (
                        <>
                            <Divider />
                            <Text size="xs" tone="tertiary" className="dash-section-eyebrow">
                                Device types
                            </Text>
                            <VStack gap={2} className="aeos-mt-1">
                                {deviceBreakdown.map(({ type, percentage }) => (
                                    <ProgressRow
                                        key={type}
                                        label={type.charAt(0).toUpperCase() + type.slice(1)}
                                        value={percentage}
                                        max={100}
                                        intent={DEVICE_INTENT[type] ?? 'cyan'}
                                    />
                                ))}
                            </VStack>
                        </>
                    )}
                </>
            )}
        </Card>
    );
}
