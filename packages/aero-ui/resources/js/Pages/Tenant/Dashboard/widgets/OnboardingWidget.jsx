/**
 * W8 · OnboardingWidget
 *
 * Step-tracker checklist. Only rendered when onboardingProgress is non-null
 * and completion < 100 (hidden automatically once fully complete).
 * Polished version of the original widget — same data shape, cleaner UI.
 */

import { Link } from '@inertiajs/react';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Progress,
} from '@aero/ui';
import { CheckIcon } from '@heroicons/react/24/solid';
import { useWidgetRefresh } from '@/hooks/useWidgetRefresh.js';
import { RefreshButton }    from './WidgetShell.jsx';

function StepRow({ step }) {
    const isDone = step.completed;
    return (
        <HStack gap={3} align="center" className="dash-step-row">
            <span
                className={`dash-step-check ${isDone ? 'dash-step-check--done' : 'dash-step-check--pending'}`}
                aria-label={isDone ? 'Complete' : 'Incomplete'}
            >
                {isDone && <CheckIcon className="aeos-icon-xs" />}
            </span>
            <Text
                size="sm"
                style={{ flex: 1, textDecoration: isDone ? 'line-through' : 'none' }}
                tone={isDone ? 'tertiary' : 'primary'}
            >
                {step.label}
            </Text>
            {!isDone && step.href && (
                <Badge
                    as={Link}
                    href={step.href}
                    intent="amber"
                    size="sm"
                >
                    Go →
                </Badge>
            )}
        </HStack>
    );
}

export function OnboardingWidget({ onboardingProgress: initialData }) {
    const { data, loading, error, refresh } = useWidgetRefresh('onboardingProgress', initialData);

    // Hide widget when fully complete or when data is null.
    if (!data || data.percentComplete >= 100) return null;

    const steps      = data.steps ?? [];
    const done       = steps.filter(s => s.completed).length;
    const total      = steps.length;
    const pct        = data.percentComplete ?? Math.round((done / Math.max(total, 1)) * 100);

    return (
        <Card className="aeos-col-span-2">
            <CardHeader>
                <HStack gap={2} align="center">
                    <VStack gap={0}>
                        <Eyebrow tone="primary">Onboarding</Eyebrow>
                        <Text size="sm" tone="secondary">{done} of {total} steps complete</Text>
                    </VStack>
                    <Box grow />
                    <Badge intent="amber">{pct}%</Badge>
                    <RefreshButton onRefresh={refresh} loading={loading} />
                </HStack>
            </CardHeader>

            {error && (
                <Text size="sm" tone="secondary" className="dash-widget-error">{error}</Text>
            )}

            <Progress value={pct} intent="amber" className="dash-onboarding-progress" />

            {!loading && (
                <VStack gap={0} className="aeos-mt-3">
                    {steps.map((step, i) => <StepRow key={i} step={step} />)}
                </VStack>
            )}
        </Card>
    );
}
