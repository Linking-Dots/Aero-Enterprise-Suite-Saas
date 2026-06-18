/**
 * W7 · AuditLogWidget
 *
 * Paginated feed of recent audit-log entries with four filter tabs
 * (All / Created / Modified / Deleted). Maps each action string to
 * a Badge intent. Replaces the previous hard-truncated 7-row table.
 */

import { useState } from 'react';
import { Link }     from '@inertiajs/react';
import {
    Card, CardHeader, CardFooter,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Divider,
} from '@aero/ui';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

/* Map a raw action string to a Badge intent + display label. */
function resolveAction(action = '') {
    const a = action.toLowerCase();
    if (a.includes('created') || a.includes('added') || a.includes('enabled'))  return { intent: 'success', label: action };
    if (a.includes('updated') || a.includes('changed') || a.includes('modified')) return { intent: 'primary', label: action };
    if (a.includes('deleted') || a.includes('removed') || a.includes('disabled')) return { intent: 'danger',  label: action };
    return { intent: 'neutral', label: action };
}

/* Filter-tab categories — maps to a substring match on the action. */
const FILTER_TABS = [
    { key: 'all',      label: 'All' },
    { key: 'created',  label: 'Created' },
    { key: 'modified', label: 'Modified' },
    { key: 'deleted',  label: 'Deleted' },
];

function matchesFilter(entry, filter) {
    if (filter === 'all') return true;
    const a = (entry.action ?? '').toLowerCase();
    if (filter === 'created')  return a.includes('created') || a.includes('added') || a.includes('enabled');
    if (filter === 'modified') return a.includes('updated') || a.includes('changed') || a.includes('modified');
    if (filter === 'deleted')  return a.includes('deleted') || a.includes('removed') || a.includes('disabled');
    return true;
}

function AuditDot({ action }) {
    const { intent } = resolveAction(action);
    return (
        <span className={`dash-audit-dot dash-audit-dot--${intent}`} aria-hidden="true" />
    );
}

export function AuditLogWidget({ recentAuditLog: initialData }) {
    const [filter, setFilter] = useState('all');

    const { data, loading, error, refresh } = useWidgetRefresh('recentAuditLog', initialData);

    const entries = (data ?? []).filter(e => matchesFilter(e, filter));

    return (
        <Card className="aeos-col-span-3">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">Audit log</Eyebrow>
                        <Text size="sm" tone="secondary">Recent platform activity</Text>
                    </VStack>
                    <Box grow />
                    <HStack gap={1}>
                        {FILTER_TABS.map(({ key, label }) => (
                            <Badge
                                key={key}
                                intent={filter === key ? 'primary' : 'neutral'}
                                className="dash-period-tab"
                                onClick={() => setFilter(key)}
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

            {!loading && (
                <>
                    <VStack gap={0} className="dash-audit-list">
                        {entries.length === 0 ? (
                            <Text size="sm" tone="tertiary" className="dash-audit-empty">
                                No entries for this filter.
                            </Text>
                        ) : entries.slice(0, 8).map((entry, i) => {
                            const { intent, label } = resolveAction(entry.action);
                            return (
                                <HStack key={i} gap={2} align="flex-start" className="dash-audit-row">
                                    <AuditDot action={entry.action} />
                                    <VStack gap={0} style={{ flex: 1 }}>
                                        <Text size="sm">
                                            <Text as="span" weight={500}>{entry.user}</Text>
                                            {' '}{entry.description}
                                        </Text>
                                        <Text size="xs" tone="tertiary" mono>{entry.timeAgo}</Text>
                                    </VStack>
                                    <Badge intent={intent} size="sm">{label}</Badge>
                                </HStack>
                            );
                        })}
                    </VStack>

                    <CardFooter align="left">
                        <Link
                            href={route('core.audit-logs.index')}
                            className="dash-view-all-link"
                        >
                            View all {data?.length ?? ''} entries →
                        </Link>
                    </CardFooter>
                </>
            )}
        </Card>
    );
}
