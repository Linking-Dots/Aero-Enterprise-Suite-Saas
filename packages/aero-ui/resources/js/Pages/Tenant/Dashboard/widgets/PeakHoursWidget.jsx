/**
 * W11 · PeakHoursWidget  [NEW]
 *
 * Renders userActivity.peakHours as a two-row heatmap (AM / PM).
 * Data shape: peakHours is an array of 24 numbers indexed 0–23,
 * representing login count for each hour of the day.
 *
 * This data was already being fetched by AdminDashboardService but had
 * no UI. This widget surfaces it for the first time.
 *
 * The only inline style is the computed heatmap cell opacity/colour —
 * a single CSS custom property injection via style={{ '--pct': n }}.
 * The actual colour rendering is done in app.css via the class
 * `.dash-heat-cell { background: rgba(var(--aeos-primary-rgb), var(--pct)); }`.
 */

import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Divider,
} from '@aero/ui';
import { RefreshButton } from './WidgetShell.jsx';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';

const HOURS = Array.from({ length: 24 }, (_, i) => i);

function toLabel(h) {
    if (h === 0)  return '12am';
    if (h === 6)  return '6am';
    if (h === 12) return '12pm';
    if (h === 18) return '6pm';
    if (h === 23) return '11pm';
    return '';
}

function HeatCell({ value, max, hour }) {
    const pct = max > 0 ? value / max : 0;
    return (
        <span
            className="dash-heat-cell"
            style={{ '--pct': pct.toFixed(3) }}
            title={`${toLabel(hour) || `${hour}:00`} — ${value} login${value !== 1 ? 's' : ''}`}
            aria-label={`Hour ${hour}: ${value} logins`}
        />
    );
}

export function PeakHoursWidget({ userActivity: initialActivity }) {
    const { data, loading, error, refresh } = useWidgetRefresh('userActivity', initialActivity);

    // The service returns peakHours as [{ hour, count }] rows (not a flat 24-array),
    // so normalize into a 24-length number array indexed by hour. Also tolerate an
    // already-flat array for forward-compatibility.
    const rawPeak   = data?.peakHours ?? [];
    const peakHours = Array(24).fill(0);
    if (rawPeak.length === 24 && rawPeak.every(v => typeof v === 'number')) {
        rawPeak.forEach((v, i) => { peakHours[i] = v; });
    } else {
        rawPeak.forEach(item => {
            const h = Number(item?.hour);
            if (Number.isInteger(h) && h >= 0 && h < 24) peakHours[h] = Number(item.count) || 0;
        });
    }
    const max       = Math.max(...peakHours, 1);

    const peakHour  = peakHours.indexOf(max);
    const peakLabel = `${peakHour % 12 || 12}:00 ${peakHour < 12 ? 'AM' : 'PM'}`;

    const quietStart = peakHours.slice(0, 12).reduce((best, v, i) => v < peakHours[best] ? i : best, 0);
    const quietLabel = `${quietStart || 12}:00 AM`;

    return (
        <Card className="aeos-col-span-2">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">Peak activity hours</Eyebrow>
                        <Text size="sm" tone="secondary">Login frequency by hour of day</Text>
                    </VStack>
                    <Box grow />
                    <Badge intent="tertiary" size="sm">NEW</Badge>
                    <RefreshButton onRefresh={refresh} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            {!loading && (
                <>
                    {/* AM row (hours 0–11) */}
                    <VStack gap={1} className="aeos-mt-2">
                        <Text size="xs" tone="tertiary" mono>AM</Text>
                        <HStack gap={1} className="dash-heat-row">
                            {HOURS.slice(0, 12).map(h => (
                                <HeatCell key={h} value={peakHours[h]} max={max} hour={h} />
                            ))}
                        </HStack>

                        <Text size="xs" tone="tertiary" mono className="aeos-mt-1">PM</Text>
                        <HStack gap={1} className="dash-heat-row">
                            {HOURS.slice(12, 24).map(h => (
                                <HeatCell key={h} value={peakHours[h]} max={max} hour={h} />
                            ))}
                        </HStack>
                    </VStack>

                    {/* Axis labels */}
                    <HStack gap={0} className="dash-heat-axis">
                        {['12am', '6am', '12pm', '6pm', '11pm'].map(l => (
                            <Text key={l} size="xs" tone="tertiary" mono style={{ flex: 1 }}>{l}</Text>
                        ))}
                    </HStack>

                    <Divider />

                    {/* Summary rows */}
                    <VStack gap={2}>
                        <HStack gap={2}><Text size="sm" tone="secondary" style={{ flex: 1 }}>Peak hour</Text><Text size="sm" weight={500}>{peakLabel}</Text></HStack>
                        <HStack gap={2}><Text size="sm" tone="secondary" style={{ flex: 1 }}>Peak logins</Text><Text size="sm" weight={500} mono style={{ color: 'var(--aeos-primary)' }}>{max}</Text></HStack>
                        <HStack gap={2}><Text size="sm" tone="secondary" style={{ flex: 1 }}>Quiet period</Text><Text size="sm" weight={500}>{quietLabel}</Text></HStack>
                    </VStack>

                    {/* Colour gradient legend */}
                    <HStack gap={2} className="aeos-mt-3" align="center">
                        <Text size="xs" tone="tertiary">Low</Text>
                        <span className="dash-heat-legend-gradient" aria-hidden="true" />
                        <Text size="xs" tone="tertiary">High</Text>
                    </HStack>
                </>
            )}
        </Card>
    );
}
