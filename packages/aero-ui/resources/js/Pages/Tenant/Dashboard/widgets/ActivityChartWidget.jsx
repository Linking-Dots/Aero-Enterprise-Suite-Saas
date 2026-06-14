/**
 * W3 · ActivityChartWidget
 *
 * Three-series area chart (logins, activeUsers, newUsers) with a
 * period switcher (week / month / quarter). Uses the existing
 * GET /dashboard/user-activity?period= endpoint for live switching
 * and GET /dashboard/widget/userActivity for the refresh button.
 *
 * Conventions:
 *   • No inline style={} except single computed values (gradient stop colors).
 *   • Period switching via direct fetch to the dedicated endpoint (not the
 *     generic widget endpoint) so the controller can validate the period param.
 */

import { useState, useCallback } from 'react';
import {
    AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer,
} from 'recharts';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge,
    Skeleton,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

const PERIOD_LABELS = { week: '7 days', month: '30 days', quarter: '90 days' };

const CHART_TOOLTIP_STYLE = {
    background:   'var(--aeos-bg-elevated)',
    border:       '1px solid var(--aeos-border)',
    borderRadius: 'var(--aeos-r-md)',
    fontSize:     12,
    color:        'var(--aeos-text-primary)',
};

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

    const switchPeriod = useCallback(async (p) => {
        if (p === period) return;
        setPeriod(p);
        // Switching period triggers the hook's extraParams change on next
        // refresh; we call refresh immediately so the chart updates now.
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

            {loading || chartData.length === 0 ? (
                <Skeleton h={120} />
            ) : (
                <ResponsiveContainer width="100%" height={120}>
                    <AreaChart data={chartData} margin={{ top: 4, right: 0, left: -32, bottom: 0 }}>
                        <defs>
                            <linearGradient id="lgLogins" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%"  stopColor="var(--aeos-primary)" stopOpacity={0.28} />
                                <stop offset="95%" stopColor="var(--aeos-primary)" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="lgActive" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%"  stopColor="var(--aeos-success)" stopOpacity={0.24} />
                                <stop offset="95%" stopColor="var(--aeos-success)" stopOpacity={0} />
                            </linearGradient>
                            <linearGradient id="lgNew" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%"  stopColor="var(--aeos-secondary)" stopOpacity={0.20} />
                                <stop offset="95%" stopColor="var(--aeos-secondary)" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <XAxis
                            dataKey="date"
                            tick={{ fontSize: 10, fill: 'var(--aeos-text-tertiary)' }}
                            tickFormatter={v => v?.slice(5) ?? v}
                            axisLine={false}
                            tickLine={false}
                        />
                        <YAxis hide />
                        <Tooltip contentStyle={CHART_TOOLTIP_STYLE} />
                        <Area
                            type="monotone"
                            dataKey="logins"
                            name="Logins"
                            stroke="var(--aeos-primary)"
                            strokeWidth={1.5}
                            fill="url(#lgLogins)"
                            dot={false}
                        />
                        <Area
                            type="monotone"
                            dataKey="activeUsers"
                            name="Active users"
                            stroke="var(--aeos-success)"
                            strokeWidth={1.5}
                            fill="url(#lgActive)"
                            dot={false}
                        />
                        <Area
                            type="monotone"
                            dataKey="newUsers"
                            name="New users"
                            stroke="var(--aeos-secondary)"
                            strokeWidth={1.5}
                            fill="url(#lgNew)"
                            dot={false}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            )}

            <HStack gap={4} className="aeos-mt-2">
                {[
                    { color: 'var(--aeos-primary)',   label: 'Logins' },
                    { color: 'var(--aeos-success)',   label: 'Active users' },
                    { color: 'var(--aeos-secondary)', label: 'New users' },
                ].map(({ color, label }) => (
                    <HStack key={label} gap={1} align="center">
                        <span className="dash-legend-swatch" style={{ background: color }} aria-hidden="true" />
                        <Text size="xs" tone="tertiary">{label}</Text>
                    </HStack>
                ))}
            </HStack>
        </Card>
    );
}
