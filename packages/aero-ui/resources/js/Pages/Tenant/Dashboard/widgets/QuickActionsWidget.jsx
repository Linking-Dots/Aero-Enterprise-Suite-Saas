/**
 * W10 · QuickActionsWidget
 *
 * Permission-gated action grid using real Heroicons instead of emoji fallbacks.
 * The icon name from the PHP payload is resolved through a curated map.
 * Actions not in the icon map fall back to <Square2StackIcon>.
 */

import { Link } from '@inertiajs/react';
import {
    Card, CardHeader,
    HStack, VStack, Box,
    Text, Eyebrow,
} from '@aero/ui';
import {
    UserPlusIcon,
    EnvelopeIcon,
    ShieldCheckIcon,
    BuildingOfficeIcon,
    ShieldExclamationIcon,
    PuzzlePieceIcon,
    UsersIcon,
    KeyIcon,
    Cog6ToothIcon,
    ArrowUpTrayIcon,
    DocumentTextIcon,
    Square2StackIcon,
    ChevronRightIcon,
} from '@heroicons/react/24/outline';

const ICON_MAP = {
    'user-plus':           UserPlusIcon,
    'envelope':            EnvelopeIcon,
    'shield-check':        ShieldCheckIcon,
    'building-office':     BuildingOfficeIcon,
    'shield-exclamation':  ShieldExclamationIcon,
    'puzzle-piece':        PuzzlePieceIcon,
    'users':               UsersIcon,
    'key':                 KeyIcon,
    'cog':                 Cog6ToothIcon,
    'cog-6-tooth':         Cog6ToothIcon,
    'arrow-up-tray':       ArrowUpTrayIcon,
    'document-text':       DocumentTextIcon,
};

const ICON_TONE_CLASS = {
    cyan:    'dash-qa-icon dash-qa-icon--cyan',
    success: 'dash-qa-icon dash-qa-icon--success',
    amber:   'dash-qa-icon dash-qa-icon--amber',
    indigo:  'dash-qa-icon dash-qa-icon--indigo',
    neutral: 'dash-qa-icon dash-qa-icon--neutral',
};

function QaRow({ action }) {
    const IconComponent = ICON_MAP[action.icon] ?? Square2StackIcon;
    const iconClass     = ICON_TONE_CLASS[action.tone ?? 'neutral'] ?? ICON_TONE_CLASS.neutral;

    return (
        <Link
            href={action.href}
            className="dash-qa-row"
            aria-label={action.label}
        >
            <span className={iconClass} aria-hidden="true">
                <IconComponent className="aeos-icon-sm" />
            </span>
            <Text size="sm" weight={500} style={{ flex: 1 }}>{action.label}</Text>
            <ChevronRightIcon className="aeos-icon-xs dash-qa-arrow" />
        </Link>
    );
}

export function QuickActionsWidget({ quickActions = [] }) {
    if (!quickActions.length) return null;

    return (
        <Card className="aeos-col-span-1">
            <CardHeader>
                <Eyebrow tone="primary">Quick actions</Eyebrow>
            </CardHeader>

            <VStack gap={2}>
                {quickActions.map((action, i) => (
                    <QaRow key={i} action={action} />
                ))}
            </VStack>
        </Card>
    );
}
