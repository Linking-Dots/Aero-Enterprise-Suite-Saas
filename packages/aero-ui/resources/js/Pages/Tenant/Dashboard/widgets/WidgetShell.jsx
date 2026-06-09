/**
 * WidgetShell — shared wrapper for every dashboard widget.
 *
 * Provides:
 *   • A refresh button wired to the useWidgetRefresh hook
 *   • Consistent card-header layout (eyebrow + subtitle + actions slot)
 *   • Per-widget loading skeleton overlay
 *   • Per-widget error state (non-fatal inline alert)
 *
 * All styling via existing @aero/ui CSS classes — no inline style blocks
 * except the single dynamic spin animation on the refresh icon.
 */

import {
    ArrowPathIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow,
    Skeleton,
} from '@aero/ui';

/**
 * RefreshButton — small icon button in each widget header.
 */
export function RefreshButton({ onRefresh, loading }) {
    return (
        <button
            type="button"
            className="dash-widget-refresh-btn"
            onClick={onRefresh}
            disabled={loading}
            aria-label="Refresh widget"
            title="Refresh"
        >
            <ArrowPathIcon
                className="aeos-icon-xs"
                style={loading ? { animation: 'aeos-spin .6s linear infinite' } : undefined}
            />
        </button>
    );
}

/**
 * WidgetSkeleton — uniform loading placeholder.
 */
export function WidgetSkeleton({ rows = 4 }) {
    return (
        <VStack gap={3}>
            {Array.from({ length: rows }).map((_, i) => (
                <Skeleton key={i} h={14} w={i % 2 === 0 ? '100%' : '65%'} />
            ))}
        </VStack>
    );
}

/**
 * WidgetShell — outer Card + standardised header for every dashboard widget.
 */
export function WidgetShell({
    eyebrow,
    subtitle,
    actions,
    loading,
    error,
    onRefresh,
    className,
    children,
}) {
    return (
        <Card className={className}>
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">{eyebrow}</Eyebrow>
                        {subtitle && (
                            <Text size="sm" tone="secondary">{subtitle}</Text>
                        )}
                    </VStack>
                    <Box grow />
                    {actions}
                    {onRefresh && (
                        <RefreshButton onRefresh={onRefresh} loading={loading} />
                    )}
                </HStack>
            </CardHeader>

            {error && (
                <HStack gap={2} className="dash-widget-error" align="center">
                    <ExclamationTriangleIcon className="aeos-icon-xs dash-widget-error-icon" />
                    <Text size="sm" tone="secondary">{error}</Text>
                </HStack>
            )}

            {loading ? <WidgetSkeleton /> : children}
        </Card>
    );
}
