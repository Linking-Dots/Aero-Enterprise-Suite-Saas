/**
 * W6 · StoragePlanWidget
 *
 * Combines storageAnalytics + subscriptionInfo into one card.
 * Renders storage and user-seat quota bars side by side,
 * plus a plan badge and an upgrade CTA if the tenant is on trial.
 */

import { Link } from '@inertiajs/react';
import {
    Card, CardHeader, CardFooter,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Divider,
    ProgressRow,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const gb = bytes / (1024 ** 3);
    if (gb >= 1) return `${gb.toFixed(1)} GB`;
    const mb = bytes / (1024 ** 2);
    return `${mb.toFixed(0)} MB`;
}

function MetricMini({ label, value, sub }) {
    return (
        <VStack gap={1} className="dash-metric-mini">
            <Text size="xs" tone="tertiary">{label}</Text>
            <Text as="div" className="dash-kpi-value">{value ?? '—'}</Text>
            {sub && <Text size="xs" tone="tertiary">{sub}</Text>}
        </VStack>
    );
}

function planIntent(status, isOnTrial) {
    if (isOnTrial)           return 'amber';
    if (status === 'active') return 'success';
    return 'neutral';
}

export function StoragePlanWidget({
    storageAnalytics: initialStorage,
    subscriptionInfo: initialSub,
}) {
    const {
        data:    storage,
        loading: storageLoading,
        error:   storageError,
        refresh: refreshStorage,
    } = useWidgetRefresh('storageAnalytics', initialStorage);

    const {
        data:    sub,
        loading: subLoading,
        refresh: refreshSub,
    } = useWidgetRefresh('subscriptionInfo', initialSub);

    const loading = storageLoading || subLoading;
    const error   = storageError;

    const usedBytes  = storage?.usedBytes  ?? 0;
    const totalBytes = storage?.totalBytes ?? 1;
    const usedPct    = Math.round((usedBytes / totalBytes) * 100);

    const usedSeats  = sub?.usage?.users ?? 0;
    const totalSeats = sub?.quota?.users ?? 100;
    const seatPct    = Math.round((usedSeats / totalSeats) * 100);

    const isOnTrial  = sub?.isOnTrial   ?? false;
    const daysLeft   = sub?.daysRemaining ?? null;
    const planName   = sub?.plan?.name  ?? null;
    const planStatus = sub?.status      ?? null;

    const storageIntent = usedPct > 85 ? 'amber' : 'success';
    const seatIntent    = seatPct  > 85 ? 'amber' : 'cyan';

    return (
        <Card className="aeos-col-span-2">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">Storage &amp; plan</Eyebrow>
                        <Text size="sm" tone="secondary">Quota and subscription</Text>
                    </VStack>
                    <Box grow />
                    {planName && (
                        <Badge intent={planIntent(planStatus, isOnTrial)}>
                            {planName}{isOnTrial ? ' · Trial' : ''}
                        </Badge>
                    )}
                    <RefreshButton onRefresh={async () => { await refreshStorage(); await refreshSub(); }} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            {!loading && (
                <>
                    <HStack gap={2} className="dash-metric-mini-row">
                        <MetricMini
                            label="Storage used"
                            value={formatBytes(usedBytes)}
                            sub={`of ${formatBytes(totalBytes)}`}
                        />
                        <MetricMini
                            label="User seats"
                            value={usedSeats.toLocaleString()}
                            sub={`of ${totalSeats.toLocaleString()}`}
                        />
                    </HStack>

                    <VStack gap={3} className="aeos-mt-3">
                        <ProgressRow
                            label="Storage"
                            value={usedPct}
                            max={100}
                            intent={storageIntent}
                        />
                        <ProgressRow
                            label="User seats"
                            value={seatPct}
                            max={100}
                            intent={seatIntent}
                        />
                    </VStack>

                    <Divider />

                    <HStack gap={2} align="center">
                        <VStack gap={0}>
                            <Text size="xs" tone="tertiary">Subscription</Text>
                            <Text size="sm" weight={500}>
                                {daysLeft !== null ? `${daysLeft} days remaining` : planName ?? '—'}
                            </Text>
                        </VStack>
                        <Box grow />
                        {isOnTrial && (
                            <Badge
                                as={Link}
                                href={route('subscription.index')}
                                intent="amber"
                            >
                                Upgrade →
                            </Badge>
                        )}
                    </HStack>
                </>
            )}
        </Card>
    );
}
