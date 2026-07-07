/**
 * BillingRail — command-shell context panel for the Billing dashboard. Mirrors
 * TenantsRail/PlansRail: an OVERVIEW block (revenue + subs), an INVOICES status
 * breakdown, and QUICK ACTIONS. Reads usePage().props.overview so it stays in
 * sync with the page without its own fetch.
 */
import { Link, usePage } from '@inertiajs/react';
import { VStack, HStack, Text } from '@aero/ui';
import {
  PlusIcon,
  ArrowPathRoundedSquareIcon,
  DocumentTextIcon,
  Cog6ToothIcon,
} from '@heroicons/react/24/outline';

const moneyK = (v) => {
  const n = Number(v ?? 0);
  return n >= 1000 ? `$${(n / 1000).toLocaleString(undefined, { maximumFractionDigits: 1 })}k`
                   : `$${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
};

function RailStat({ label, value, statusKey }) {
  return (
    <HStack justify="between" align="center" className="tenants-rail-stat">
      <HStack gap={2} align="center">
        {statusKey && <span aria-hidden="true" className={`plans-rail-dot plans-rail-dot--${statusKey}`} />}
        <Text size="sm" tone="secondary">{label}</Text>
      </HStack>
      <Text size="sm" weight={600}>{value}</Text>
    </HStack>
  );
}

export default function BillingRail() {
  const { overview } = usePage().props;
  const h = overview?.heroes ?? {};
  const inv = overview?.invoices ?? {};

  return (
    <VStack gap={5} className="dash-rail">
      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>OVERVIEW</Text>
        <VStack gap={1}>
          <RailStat label="MRR"          value={moneyK(h.mrr)} />
          <RailStat label="ARR"          value={moneyK(h.arr)} />
          <RailStat label="Active subs"  value={h.activeSubs ?? 0} />
          <RailStat label="Trialing"     value={h.trialingSubs ?? 0} />
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>INVOICES</Text>
        <VStack gap={1}>
          <RailStat label="Paid"    value={inv.paid?.count ?? 0}    statusKey="starter" />
          <RailStat label="Open"    value={inv.open?.count ?? 0}    statusKey="enterprise" />
          <RailStat label="Overdue" value={inv.overdue?.count ?? 0} statusKey="professional" />
        </VStack>
      </VStack>

      <VStack gap={2}>
        <Text size="xs" tone="tertiary" mono>QUICK ACTIONS</Text>
        <VStack gap={1}>
          <Link href={route('platform.admin.billing.invoices.index')} className="dash-rail-link">
            <DocumentTextIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>All invoices</span>
          </Link>
          <Link href={route('platform.admin.billing.subscriptions.index')} className="dash-rail-link">
            <ArrowPathRoundedSquareIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Subscriptions</span>
          </Link>
          <Link href={route('platform.admin.plans.index')} className="dash-rail-link">
            <PlusIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Plans</span>
          </Link>
          <Link href={route('platform.admin.billing.gateways.index')} className="dash-rail-link">
            <Cog6ToothIcon className="aeos-icon-sm" aria-hidden="true" />
            <span>Payment gateways</span>
          </Link>
        </VStack>
      </VStack>
    </VStack>
  );
}
