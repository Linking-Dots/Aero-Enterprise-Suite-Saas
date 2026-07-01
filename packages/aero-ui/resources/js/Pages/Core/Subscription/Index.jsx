import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { IndexPageLayout, Tabs, Stat, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import OverviewPanel from './panels/OverviewPanel.jsx';
import PlansPanel from './panels/PlansPanel.jsx';
import UsagePanel from './panels/UsagePanel.jsx';
import InvoicesPanel from './panels/InvoicesPanel.jsx';

function money(amount, currency = 'USD') {
  if (amount == null) return '—';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

const ONLY = ['tab', 'summary', 'plan', 'usage', 'products', 'plans', 'currentPlanId', 'invoices'];

export default function SubscriptionIndex({ tab: initialTab, summary, plan, usage, products, plans, currentPlanId, invoices }) {
  const toast = useToast();
  const canUsage    = useHRMAC('core.subscription.usage.view');
  const canInvoices = useHRMAC('core.subscription.invoices.view');
  const canUpgrade  = useHRMAC('core.subscription.plans.upgrade');
  const canCancel   = useHRMAC('core.subscription.plans.cancel');

  const [tab, setTab] = useState(initialTab || 'overview');
  const [changingId, setChangingId] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const offStart  = router.on('start',  () => setLoading(true));
    const offFinish = router.on('finish', () => setLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const switchTab = next => {
    setTab(next);
    router.get(route('core.subscription.index'), { tab: next }, {
      preserveState: true, preserveScroll: true, only: ONLY,
    });
  };

  const changePlan = planId => {
    if (!confirm('Switch to this plan? This affects your billing.')) return;
    setChangingId(planId);
    router.post(route('core.subscription.change-plan'), { plan_id: planId }, {
      preserveScroll: true,
      onSuccess: () => toast.success('Plan updated.'),
      onError:   () => toast.error('Failed to change plan.'),
      onFinish:  () => setChangingId(null),
    });
  };

  const cancel = () => {
    if (!confirm('Cancel your subscription? It stays active until the end of the billing period.')) return;
    router.post(route('core.subscription.cancel'), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Subscription cancellation scheduled.'),
      onError:   () => toast.error('Failed to cancel subscription.'),
    });
  };

  const invoicesPage = page => {
    router.get(route('core.subscription.index'), { tab: 'invoices', page }, {
      preserveState: true, preserveScroll: true, only: ['invoices', 'tab'],
    });
  };

  const tabs = [
    { value: 'overview', label: 'Overview' },
    { value: 'plans',    label: 'Plans' },
    canUsage    && { value: 'usage',    label: 'Usage' },
    canInvoices && { value: 'invoices', label: 'Invoices' },
  ].filter(Boolean);

  const s = summary ?? {};
  const usersStat = s.users ?? { used: 0, limit: 0 };
  const storageStat = s.storage ?? { used_gb: 0, limit_gb: 0 };

  return (
    <IndexPageLayout
      title="Subscription & Billing"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Subscription & Billing' },
      ]}
      description="Manage your plan, usage, and billing history."
      tabs={<Tabs value={tab} tabs={tabs} onChange={switchTab} />}
      kpis={[
        <Stat key="plan" title="Current Plan" value={s.plan_name ?? '—'} icon="sparkles" iconTone="indigo" />,
        <Stat key="price" title="Billing" value={s.price != null ? money(s.price, s.currency) : '—'}
          description={s.interval ? `per ${s.interval}` : undefined} icon="trending" iconTone="success" />,
        <Stat key="users" title="Users"
          value={`${usersStat.used} / ${usersStat.limit === 0 ? '∞' : usersStat.limit}`} icon="users" iconTone="amber" />,
        <Stat key="storage" title="Storage"
          value={`${storageStat.used_gb} / ${storageStat.limit_gb === 0 ? '∞' : `${storageStat.limit_gb} GB`}`}
          icon="inbox" iconTone="amber" />,
      ]}
      table={
        tab === 'plans' ? (
          <PlansPanel plans={plans} currentPlanId={currentPlanId}
            onChangePlan={canUpgrade ? changePlan : () => toast.error('You lack permission to change plans.')}
            onCancel={cancel} changingId={changingId} canCancel={canCancel} />
        ) : tab === 'usage' ? (
          <UsagePanel usage={usage} />
        ) : tab === 'invoices' ? (
          <InvoicesPanel invoices={invoices} loading={loading} onPage={invoicesPage} />
        ) : (
          <OverviewPanel summary={summary} plan={plan} usage={usage} products={products} />
        )
      }
    />
  );
}

SubscriptionIndex.layout = page => <App title="Subscription & Billing">{page}</App>;
