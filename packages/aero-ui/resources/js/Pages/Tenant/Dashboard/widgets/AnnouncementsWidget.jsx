/**
 * W9 · AnnouncementsWidget
 *
 * Priority-sorted, dismissible announcements. Dismiss calls the existing
 * POST /dashboard/announcements/{id}/dismiss endpoint using the HRMAC
 * CSRF pattern. Pinned announcements are marked with a pushpin icon.
 *
 * Intent mapping:
 *   high     → danger
 *   medium   → amber
 *   low/info → primary (cyan)
 */

import { useState } from 'react';
import {
    Card, CardHeader, CardFooter,
    HStack, VStack, Box,
    Text, Eyebrow, Badge,
} from '@aero/ui';
import { XMarkIcon } from '@heroicons/react/24/outline';
import { Link }      from '@inertiajs/react';

function priorityIntent(priority = '') {
    const p = priority.toLowerCase();
    if (p === 'high')   return 'danger';
    if (p === 'medium') return 'amber';
    return 'primary';
}

function AnnRow({ ann, onDismiss }) {
    return (
        <HStack gap={3} align="flex-start" className="dash-ann-row">
            <span className="dash-ann-pin" aria-hidden="true">
                {ann.isPinned ? '📌' : ' '}
            </span>
            <VStack gap={1} style={{ flex: 1 }}>
                <HStack gap={2} align="center">
                    <Text size="sm" weight={500} style={{ flex: 1 }}>{ann.title}</Text>
                    <Badge intent={priorityIntent(ann.priority)} size="sm">
                        {ann.priority}
                    </Badge>
                </HStack>
                <Text size="sm" tone="secondary">{ann.body}</Text>
                <Text size="xs" tone="tertiary" mono>{ann.author} · {ann.createdAt}</Text>
            </VStack>
            {ann.isDismissible && (
                <button
                    type="button"
                    className="dash-dismiss-btn"
                    onClick={() => onDismiss(ann.id)}
                    aria-label="Dismiss announcement"
                >
                    <XMarkIcon className="aeos-icon-xs" />
                </button>
            )}
        </HStack>
    );
}

export function AnnouncementsWidget({ announcements: initialData }) {
    const [list, setList] = useState(initialData ?? []);

    async function handleDismiss(id) {
        // Optimistic removal
        setList(prev => prev.filter(a => a.id !== id));

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

        try {
            await fetch(`/dashboard/announcements/${id}/dismiss`, {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN':      csrfToken,
                    'Accept':            'application/json',
                    'X-Requested-With':  'XMLHttpRequest',
                },
            });
        } catch {
            // No rollback on failure — dismissal is a soft preference.
        }
    }

    if (!list.length) return null;

    return (
        <Card className="aeos-col-span-2">
            <CardHeader>
                <HStack gap={2} align="center">
                    <Eyebrow tone="primary">Announcements</Eyebrow>
                    <Box grow />
                    <Link href={route('core.announcements.index')} className="dash-view-all-link">
                        View all →
                    </Link>
                </HStack>
            </CardHeader>

            <VStack gap={0}>
                {list.slice(0, 4).map(ann => (
                    <AnnRow key={ann.id} ann={ann} onDismiss={handleDismiss} />
                ))}
            </VStack>
        </Card>
    );
}
