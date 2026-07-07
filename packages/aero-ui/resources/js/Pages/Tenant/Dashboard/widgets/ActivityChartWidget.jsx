/**
 * W3 · ActivityChartWidget
 *
 * Three-series area chart (logins, activeUsers, newUsers) with a
 * period switcher (week / month / quarter). Uses the existing
 * GET /dashboard/widget/userActivity?period= endpoint for live switching
 * and the refresh button.
 *
 * Renders the shared, dependency-free @aero/ui AreaTrend (no recharts) so it
 * matches the platform dashboards and stays fully theme-reactive.
 */

import { useState, useCallback, useMemo } from 'react';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge,
    Skeleton, AreaTrend,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

const PERIOD_LABELS = { week: '7 days', month: '30 days', quarter: '90 days' };

const LEGEND = [
    { key: 'logins',      color: 'var(--aeos-primary)',   label: 'Logins' },
    { key: 'activeUsers', color: 'var(--aeos-success)',   label: 'Active users' },
    { key: 'newUsers',    color: 'var(--aeos-secondary)', label: 'New users' },
];

export function ActivityChartWidget({ userActivity: initialActivity }) {
    const [period, setPeriod] = useState('week');

    const {
        data:    activity,
        loading,
        error,
        refresh,
    } = useWidgetRefresh('userActivity', initialActivity, {
        extraParams: `period=${period}`,
    });

    const chartData = activity?.chartData ?? [];

    const hasData = chartData.some(d => (d.logins || 0) + (d.activeUsers || 0) + (d.newUsers || 0) > 0);
    const labels = useMemo(() => chartData.map(d => (d.date ?? '').slice(5)), [chartData]);
    const series = useMemo(() => LEGEND.map(l => ({
        key: l.key,
        label: l.label,
        color: l.color,
        fill: l.key === 'logins',
        values: chartData.map(d => Number(d[l.key] ?? 0)),
    })), [chartData]);

    const switchPeriod = useCallback(async (p) => {
        if (p === period) return;
        setPeriod(p);
        await refresh();
    }, [period, refresh]);

    return (
        <Card className="aeos-col-span-3">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">User activity</Eyebrow>
                        <Text size="sm" tone="secondary">Logins, active users &amp; new registrations</Text>
                    </VStack>
                    <Box grow />
                    <HStack gap={1}>
                        {Object.entries(PERIOD_LABELS).map(([key, label]) => (
                            <Badge
                                key={key}
                                intent={period === key ? 'primary' : 'neutral'}
                                className="dash-period-tab"
                                onClick={() => switchPeriod(key)}
                            >
                                {label}
                            </Badge>
                        ))}
                    </HStack>
                    <RefreshButton onRefresh={refresh} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            {loading ? (
                <Skeleton h={120} />
            ) : !hasData ? (
                <div className="dash-widget-empty">No activity in this period yet.</div>
            ) : (
                <AreaTrend series={series} labels={labels} height={130} ariaLabel="User activity trend" />
            )}

            <HStack gap={4} className="aeos-mt-2">
                {LEGEND.map(({ color, label }) => (
                    <HStack key={label} gap={1} align="center">
                        <span className="dash-legend-swatch" style={{ background: color }} aria-hidden="true" />
                        <Text size="xs" tone="tertiary">{label}</Text>
                    </HStack>
                ))}
            </HStack>
        </Card>
    );
}
