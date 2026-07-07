/**
 * PlansRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Mirrors the
 * TenantsRail pattern: an OVERVIEW block (catalog totals from the page props),
 * a BY-TIER breakdown of active subscribers, and QUICK ACTIONS. Counts come from
 * usePage().props.stats so the rail stays in sync with the KPI strip and table
 * without its own fetch.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
  PlusIcon,
  CreditCardIcon,
  Squares2X2Icon,
  UsersIcon,
} from '@heroicons/react/24/outline';

/* Tiers in catalog order. Dot colors are driven entirely by theme tokens via
   .plans-rail-dot--* classes. */
const TIER_ROWS = [
  ['free',         'Free'],
  ['starter',      'Starter'],
  ['professional', 'Professional'],
  ['enterprise',   'Enterprise'],
];

/* Compact MRR for the narrow rail. */
function fmtMrr(v) {
  const n = Number(v ?? 0);
  if (n >= 1000) return `$${(n / 1000).toLocaleString(undefined, { maximumFractionDigits: 1 })}k`;
  return `$${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

function RailStat({ label, value, tierKey }) {
  return (
    <HStack justify="between" align="center" className="tenants-rail-stat">
      <HStack gap={2} align="center">
        {tierKey && (
          <span aria-hidden="true" className={`plans-rail-dot plans-rail-dot--${tierKey}`} />
        )}
        <Text size="sm" tone="secondary">{label}</Text>
      </HStack>
      <Text size="sm" weight={600}>{value}</Text>
    </HStack>
  );
}

export default function PlansRail() {
  const { stats } = usePage().props;
  const byTier = stats?.byTier ?? {};

  return (
    <VStack gap={5} className="dash-rail">
      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
        <VStack gap={1}>
          <RailStat label="Total plans"     value={stats?.total ?? 0} />
          <RailStat label="Public / private" value={`${stats?.public ?? 0} / ${stats?.private ?? 0}`} />
          <RailStat label="Subscribers"     value={stats?.subscribers ?? 0} />
          <RailStat label="Plan MRR"        value={fmtMrr(stats?.mrr)} />
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>SUBSCRIBERS BY TIER</Text>
        <VStack gap={1}>
          {TIER_ROWS.map(([key, label]) => (
            <RailStat key={key} label={label} value={byTier[key] ?? 0} tierKey={key} />
          ))}
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
        <VStack gap={1}>
          <Link href={route('platform.admin.plans.create')} className="dash-rail-link">
            <PlusIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>New plan</span>
          </Link>
          <Link href={route('platform.admin.billing.subscriptions.index')} className="dash-rail-link">
            <Squares2X2Icon className="aeos-icon-sm" aria-hidden="true" />
            <span>Subscriptions</span>
          </Link>
          <Link href={route('platform.admin.billing.dashboard')} className="dash-rail-link">
            <CreditCardIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Billing &amp; invoicing</span>
          </Link>
          <Link href={route('platform.admin.tenants.index')} className="dash-rail-link">
            <UsersIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Tenants</span>
          </Link>
        </VStack>
      </VStack>
    </VStack>
  );
}
