/**
 * W1 · WelcomeWidget
 *
 * Full-width command header. Shows greeting, name, date/time,
 * plan badge, and a live system-status pill.
 */

import { usePage } from '@inertiajs/react';
import {
    Card,
    HStack, VStack, Box,
    Text, Eyebrow, Badge, Avatar,
} from '@aero/ui';

function planIntent(status, isOnTrial) {
    if (isOnTrial)           return 'amber';
    if (status === 'active') return 'success';
    return 'neutral';
}

function healthIntent(overall) {
    if (overall === 'healthy')   return 'success';
    if (overall === 'degraded')  return 'amber';
    if (overall === 'unhealthy') return 'danger';
    return 'neutral';
}

export function WelcomeWidget({ welcomeData = {}, subscriptionInfo = null, systemHealth = null }) {
    const { auth } = usePage().props;
    const user     = auth?.user;

    const name      = welcomeData.userName ?? user?.name ?? 'there';
    const firstName = name.split(' ')[0];
    const initials  = name.split(' ').slice(0, 2).map(p => p[0]).join('').toUpperCase();

    const planName    = subscriptionInfo?.plan?.name ?? null;
    const planStatus  = subscriptionInfo?.status ?? null;
    const isOnTrial   = subscriptionInfo?.isOnTrial ?? false;
    const daysLeft    = subscriptionInfo?.daysRemaining ?? null;

    const overallHealth = systemHealth?.overall ?? 'unknown';

    return (
        <Card className="aeos-col-span-4 dash-welcome-card">
            <HStack gap={4} align="center">
                <Avatar name={name} size={48} />

                <VStack gap={1}>
                    <Eyebrow tone="primary">{welcomeData.greeting ?? 'Welcome back'}</Eyebrow>
                    <Text as="h2" className="dash-welcome-name">{firstName}</Text>
                    <Text size="sm" tone="secondary">{welcomeData.date ?? ''}</Text>
                </VStack>

                <Box grow />

                <HStack gap={2} wrap="wrap">
                    {planName && (
                        <Badge intent={planIntent(planStatus, isOnTrial)} dot>
                            {planName}
                            {isOnTrial && daysLeft !== null ? ` · ${daysLeft}d trial` : ''}
                        </Badge>
                    )}
                    <Badge intent={healthIntent(overallHealth)} dot>
                        {overallHealth === 'healthy' ? 'System healthy' : overallHealth}
                    </Badge>
                    <Badge intent="neutral" mono>{welcomeData.time ?? ''}</Badge>
                </HStack>
            </HStack>
        </Card>
    );
}
