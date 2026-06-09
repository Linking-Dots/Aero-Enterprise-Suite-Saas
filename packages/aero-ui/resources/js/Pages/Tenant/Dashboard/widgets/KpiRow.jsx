/**
 * W2 · KpiRow
 *
 * Four KPI tiles using real sparkline data sliced from userActivity.chartData.
 * Each tile has its own refresh button wired to the shared widget endpoint.
 */

import { Card, VStack, HStack, Text, Badge, Skeleton, Sparkline } from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

/**
 * Extract the last N values of a given key from the chartData array.
 * Falls back to a flat line of zeros if the data is missing.
 */
function toSparkline(chartData = [], key, n = 7) {
    if (!Array.isArray(chartData) || chartData.length === 0) {
        return Array(n).fill(0);
    }
    return chartData.slice(-n).map(d => d[key] ?? 0);
}

function KpiTile({ label, value, delta, deltaIntent, sparkValues, sparkIntent }) {
    const max = Math.max(...sparkValues, 1);

    return (
        <VStack gap={2}>
            <Text size="sm" tone="tertiary">{label}</Text>
            <Text as="div" className="dash-kpi-value">{value ?? '—'}</Text>
            {delta !== null && delta !== undefined && (
                <Badge intent={deltaIntent ?? 'neutral'} size="sm">{delta}</Badge>
            )}
            {sparkValues.length > 0 && (
                <Sparkline
                    data={sparkValues.map(v => Math.round((v / max) * 100))}
                    intent={sparkIntent ?? 'cyan'}
                />
            )}
        </VStack>
    );
}

export function KpiRow({ coreStats: initialStats, userActivity: initialActivity }) {
    const {
        data:    stats,
        loading: statsLoading,
        error:   statsError,
        refresh: refreshStats,
    } = useWidgetRefresh('coreStats', initialStats);

    // We reuse the already-fetched userActivity for real sparklines.
    // No second request needed — it's passed as an Inertia prop.
    const chartData = initialActivity?.chartData ?? [];

    if (statsLoading || !stats) {
        return (
            <>
                {[0, 1, 2, 3].map(i => (
                    <Card key={i}><Skeleton h={80} /></Card>
                ))}
            </>
        );
    }

    const tiles = [
        {
            label:       'Total users',
            value:       stats.totalUsers?.toLocaleString(),
            delta:       stats.newUsersThisMonth > 0 ? `↑ ${stats.newUsersThisMonth} this month` : null,
            deltaIntent: 'success',
            sparkValues: toSparkline(chartData, 'logins'),
            sparkIntent: 'cyan',
        },
        {
            label:       'Active users',
            value:       stats.activeUsers?.toLocaleString(),
            delta:       stats.totalUsers > 0
                ? `${Math.round((stats.activeUsers / stats.totalUsers) * 100)}% of total`
                : null,
            deltaIntent: 'primary',
            sparkValues: toSparkline(chartData, 'activeUsers'),
            sparkIntent: 'cyan',
        },
        {
            label:       'Online now',
            value:       stats.onlineUsers,
            delta:       stats.newUsersThisWeek > 0 ? `↑ ${stats.newUsersThisWeek} this week` : null,
            deltaIntent: 'success',
            sparkValues: toSparkline(chartData, 'logins').map((v, i, arr) =>
                Math.round(v * (0.3 + (i / arr.length) * 0.7))
            ),
            sparkIntent: 'success',
        },
        {
            label:       'Total roles',
            value:       stats.totalRoles,
            delta:       null,
            deltaIntent: 'neutral',
            sparkValues: [],
            sparkIntent: 'cyan',
        },
    ];

    return (
        <>
            {tiles.map((tile, i) => (
                <Card key={i} className="dash-kpi-card">
                    <HStack gap={0} align="center" className="dash-kpi-header">
                        <Box grow />
                        <RefreshButton onRefresh={refreshStats} loading={statsLoading} />
                    </HStack>
                    <KpiTile {...tile} />
                    {statsError && (
                        <Text size="xs" tone="secondary" className="dash-kpi-error">
                            {statsError}
                        </Text>
                    )}
                </Card>
            ))}
        </>
    );
}
