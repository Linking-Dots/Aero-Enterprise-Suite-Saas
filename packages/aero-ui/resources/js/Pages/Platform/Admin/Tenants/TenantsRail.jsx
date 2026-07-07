/**
 * TenantsRail — per-page context panel for the command shell's right rail.
 *
 * Only the command shell renders a rail; other shells ignore it. Mirrors the
 * UsersRail pattern: an OVERVIEW block (live status-grouped counts from the page
 * props) plus QUICK ACTIONS. Counts come from usePage().props.stats so the rail
 * stays in sync with the KPI strip and table without its own fetch.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
  PlusIcon,
  ArrowPathIcon,
  Squares2X2Icon,
  ClockIcon,
} from '@heroicons/react/24/outline';

/* Lifecycle statuses in the order the tenant list surfaces them. Dot colors are
   driven entirely by theme tokens via .tenants-rail-dot--* classes. */
const STATUS_ROWS = [
  ['active',       'Active'],
  ['trial',        'Trial'],
  ['pending',      'Pending'],
  ['provisioning', 'Provisioning'],
  ['suspended',    'Suspended'],
  ['failed',       'Failed'],
  ['archived',     'Archived'],
];

function RailStat({ label, value, statusKey }) {
  return (
    <HStack justify="between" align="center" className="tenants-rail-stat">
      <HStack gap={2} align="center">
        {statusKey && (
          <span aria-hidden="true" className={`tenants-rail-dot tenants-rail-dot--${statusKey}`} />
        )}
        <Text size="sm" tone="secondary">{label}</Text>
      </HStack>
      <Text size="sm" weight={600} mono>{value ?? 0}</Text>
    </HStack>
  );
}

export default function TenantsRail() {
  const { stats } = usePage().props;
  const byStatus = stats?.byStatus ?? {};

  return (
    <VStack gap={5} className="dash-rail">
      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
        <VStack gap={1}>
          <RailStat label="Total tenants" value={stats?.total} />
          {STATUS_ROWS.map(([key, label]) => (
            <RailStat key={key} label={label} value={byStatus[key]} statusKey={key} />
          ))}
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
        <VStack gap={1}>
          <Link href={route('platform.admin.tenants.create')} className="dash-rail-link">
            <PlusIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>New tenant</span>
          </Link>
          <Link href={route('platform.admin.onboarding.provisioning')} className="dash-rail-link">
            <ClockIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Provisioning queue</span>
          </Link>
          <Link href={route('platform.admin.tenants.bulk.history')} className="dash-rail-link">
            <ArrowPathIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Bulk history</span>
          </Link>
          <Link href={route('platform.admin.plans.index')} className="dash-rail-link">
            <Squares2X2Icon className="aeos-icon-sm" aria-hidden="true" />
            <span>Plans &amp; billing</span>
          </Link>
        </VStack>
      </VStack>
    </VStack>
  );
}
