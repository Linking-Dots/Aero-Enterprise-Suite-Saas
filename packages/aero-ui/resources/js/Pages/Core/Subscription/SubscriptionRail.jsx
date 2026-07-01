/**
 * SubscriptionRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Gives the
 * billing hub at-hand context (current plan snapshot + section links) instead
 * of an empty context section in command mode.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text, useHRMAC } from '@aero/ui';
import {
  Squares2X2Icon,
  RectangleStackIcon,
  PuzzlePieceIcon,
  ChartBarIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';

function RailStat({ label, value }) {
  return (
    <HStack justify="between" align="center" className="users-rail-stat">
      <Text size="sm" tone="secondary">{label}</Text>
      <Text size="sm" weight={600}>{value ?? '—'}</Text>
    </HStack>
  );
}

export default function SubscriptionRail() {
  const { summary, products } = usePage().props;
  const s = summary ?? {};
  const users = s.users ?? {};
  const addonCount = Array.isArray(products) ? products.length : null;

  const canProducts = useHRMAC('core.subscription.products.view');
  const canUsage    = useHRMAC('core.subscription.usage.view');
  const canInvoices = useHRMAC('core.subscription.invoices.view');

  const usersValue = users.limit != null
    ? `${users.used ?? 0} / ${users.limit === 0 ? '∞' : users.limit}`
    : (users.used ?? '—');

  return (
    <VStack gap={5} className="dash-rail">
      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>CURRENT PLAN</Text>
        <VStack gap={1}>
          <RailStat label="Plan" value={s.plan_name ?? '—'} />
          <RailStat label="Status" value={s.status ?? '—'} />
          <RailStat label="Users" value={usersValue} />
          {addonCount != null && <RailStat label="Add-ons" value={addonCount} />}
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>SECTIONS</Text>
        <VStack gap={1}>
          <Link href="/subscription" className="dash-rail-link">
            <Squares2X2Icon className="aeos-icon-sm" aria-hidden="true" /><span>Overview</span>
          </Link>
          <Link href="/subscription/plans" className="dash-rail-link">
            <RectangleStackIcon className="aeos-icon-sm" aria-hidden="true" /><span>Plans</span>
          </Link>
          {canProducts && (
            <Link href="/subscription/products" className="dash-rail-link">
              <PuzzlePieceIcon className="aeos-icon-sm" aria-hidden="true" /><span>Add-ons</span>
            </Link>
          )}
          {canUsage && (
            <Link href="/subscription/usage" className="dash-rail-link">
              <ChartBarIcon className="aeos-icon-sm" aria-hidden="true" /><span>Usage</span>
            </Link>
          )}
          {canInvoices && (
            <Link href="/subscription/invoices" className="dash-rail-link">
              <DocumentTextIcon className="aeos-icon-sm" aria-hidden="true" /><span>Invoices</span>
            </Link>
          )}
        </VStack>
      </VStack>
    </VStack>
  );
}
