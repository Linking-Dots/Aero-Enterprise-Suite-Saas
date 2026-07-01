import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { PageHeader, Tabs, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import SubscriptionRail from './SubscriptionRail.jsx';
import OverviewPanel from './panels/OverviewPanel.jsx';
import PlansPanel from './panels/PlansPanel.jsx';
import ProductsPanel from './panels/ProductsPanel.jsx';
import UsagePanel from './panels/UsagePanel.jsx';
import InvoicesPanel from './panels/InvoicesPanel.jsx';

const ONLY = ['tab', 'summary', 'plan', 'usage', 'products', 'catalog', 'plans', 'currentPlanId', 'invoices'];

export default function SubscriptionIndex({ tab: initialTab, summary, plan, usage, products, catalog, plans, currentPlanId, invoices }) {
  const toast = useToast();
  const canUsage        = useHRMAC('core.subscription.usage.view');
  const canInvoices     = useHRMAC('core.subscription.invoices.view');
  const canUpgrade      = useHRMAC('core.subscription.plans.upgrade');
  const canCancel       = useHRMAC('core.subscription.plans.cancel');
  const canProducts     = useHRMAC('core.subscription.products.view');
  const canSubscribe    = useHRMAC('core.subscription.products.subscribe');
  const canCancelAddon  = useHRMAC('core.subscription.products.cancel');

  const [tab, setTab]               = useState(initialTab || 'overview');
  const [changingId, setChangingId] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const [subscribingId, setSubscribingId] = useState(null);
  const [cancellingAddonId, setCancellingAddonId] = useState(null);
  const [loading, setLoading]       = useState(false);

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
      onError:   () => toast.error('Could not change plan.'),
      onFinish:  () => setChangingId(null),
    });
  };

  const cancel = () => {
    if (!confirm('Cancel your subscription? It stays active until the end of the billing period.')) return;
    setCancelling(true);
    router.post(route('core.subscription.cancel'), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Cancellation scheduled.'),
      onError:   () => toast.error('Could not cancel subscription.'),
      onFinish:  () => setCancelling(false),
    });
  };

  const subscribeProduct = productId => {
    if (!confirm('Subscribe to this add-on? It is billed separately from your plan.')) return;
    setSubscribingId(productId);
    router.post(route('core.subscription.products.subscribe'), { product_id: productId }, {
      preserveScroll: true, only: ONLY,
      onSuccess: () => toast.success('Add-on subscribed.'),
      onError:   () => toast.error('Could not subscribe to add-on.'),
      onFinish:  () => setSubscribingId(null),
    });
  };

  const cancelProduct = subscriptionId => {
    if (!confirm('Cancel this add-on? It stays active until the end of the billing period.')) return;
    setCancellingAddonId(subscriptionId);
    router.post(route('core.subscription.products.cancel', subscriptionId), {}, {
      preserveScroll: true, only: ONLY,
      onSuccess: () => toast.success('Add-on cancellation scheduled.'),
      onError:   () => toast.error('Could not cancel add-on.'),
      onFinish:  () => setCancellingAddonId(null),
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
    canProducts && { value: 'products', label: 'Add-ons' },
    canUsage    && { value: 'usage',    label: 'Usage' },
    canInvoices && { value: 'invoices', label: 'Invoices' },
  ].filter(Boolean);

  return (
    <div className="aeos-page-layout aeos-page-layout-index">
      <PageHeader
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Subscription & Billing' },
        ]}
        title="Subscription & Billing"
        description="Manage your plan, add-ons, usage, and billing history."
        tabs={<Tabs value={tab} tabs={tabs} onChange={switchTab} />}
      />

      <div className="aeos-billing-body">
        {tab === 'plans' ? (
          <PlansPanel
            plans={plans}
            currentPlanId={currentPlanId}
            onChangePlan={canUpgrade ? changePlan : () => toast.error('You do not have permission to change plans.')}
            onCancel={cancel}
            changingId={changingId}
            cancelling={cancelling}
            canCancel={canCancel}
          />
        ) : tab === 'products' ? (
          <ProductsPanel
            subscriptions={products}
            catalog={catalog}
            onSubscribe={subscribeProduct}
            onCancel={cancelProduct}
            subscribingId={subscribingId}
            cancellingId={cancellingAddonId}
            canSubscribe={canSubscribe}
            canCancel={canCancelAddon}
          />
        ) : tab === 'usage' ? (
          <UsagePanel usage={usage} />
        ) : tab === 'invoices' ? (
          <InvoicesPanel invoices={invoices} loading={loading} onPage={invoicesPage} />
        ) : (
          <OverviewPanel
            summary={summary}
            plan={plan}
            usage={usage}
            products={products}
            canUpgrade={canUpgrade}
            canCancel={canCancel}
            canManageProducts={canProducts}
            onChange={() => switchTab('plans')}
            onManageProducts={() => switchTab('products')}
            onCancel={cancel}
            cancelling={cancelling}
          />
        )}
      </div>
    </div>
  );
}

SubscriptionIndex.layout = page => (
  <App title="Subscription & Billing" railTitle="Billing" rail={<SubscriptionRail />}>{page}</App>
);
