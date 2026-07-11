/**
 * Billing command center — tenant self-service subscription hub.
 *
 * Overhauls the old PageHeader/Tabs Subscription page onto the pc-* command
 * centre bar. The backend (TenantSubscriptionController + presenter) is unchanged
 * and fully reused: tab-scoped data (?tab=), plan change (upgrade/downgrade),
 * cancel, add-on subscribe/cancel, invoice PDF — every operation HRMAC-gated.
 */
import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import AiUsageCard from '@/aeon/AiUsageCard.jsx';
import { money } from './money.js';

import '../../Platform/Admin/Products/products.css';
import './billing.css';

const ONLY = ['tab', 'summary', 'plan', 'usage', 'products', 'catalog', 'plans', 'currentPlanId', 'invoices'];

const ic = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const I = {
  download: ic(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  card: ic(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
};
const confirmDo = (m, fn) => { if (window.confirm(m)) fn(); };
const statusClass = (s) => `bl-st bl-st--${String(s || '').toLowerCase()}`;
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const pct = (used, limit) => (limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0);
const meterClass = (p) => (p >= 90 ? 'is-danger' : p >= 75 ? 'is-warn' : '');

/* ---------------- usage meter ---------------- */
function UsageBar({ label, used, limit, unit }) {
  const p = pct(used, limit);
  return (
    <div className="bl-usebar">
      <div className="bl-usebar__top">
        <b>{label}</b>
        <span className="bl-usebar__n">{used}{unit} {limit > 0 ? `/ ${limit}${unit}` : <span className="bl-mut">· unlimited</span>}{limit > 0 && ` · ${p}%`}</span>
      </div>
      <div className="bl-meter"><i className={meterClass(p)} style={{ width: `${limit > 0 ? p : 6}%` }} /></div>
    </div>
  );
}

/* ---------------- panels ---------------- */
function OverviewPanel({ summary, plan, usage, products, canUpgrade, canCancel, canProducts, onChange, onManageProducts, onCancel, cancelling }) {
  const s = summary ?? {};
  const u = usage ?? {};
  return (
    <div className="bl-ov">
      <Card><CardBody>
        <div className="bl-planhero">
          <div>
            <div className="pc-eyebrow" style={{ marginBottom: 6 }}><span className="pc-eyebrow__dot" /> Current plan</div>
            <div className="bl-planname">{s.plan_name ?? 'No active plan'}</div>
            {s.price != null && <div className="bl-planprice"><b>{money(s.price, s.currency)}</b> / {s.interval} · {s.status ?? '—'}</div>}
          </div>
          <div className="pc-actions">
            {canUpgrade && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={onChange}>Change plan</button>}
            {canCancel && s.status !== 'cancelled' && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={onCancel} disabled={cancelling}>{cancelling ? 'Cancelling…' : 'Cancel'}</button>}
          </div>
        </div>
        {plan?.features?.length > 0 && <ul className="bl-feat">{plan.features.map((f, i) => <li key={i}>{f}</li>)}</ul>}
        <div style={{ marginTop: 'var(--aeos-space-4)' }}>
          <UsageBar label="Team members" used={u.users?.used ?? 0} limit={u.users?.limit ?? 0} unit="" />
          <UsageBar label="Storage" used={u.storage?.used_gb ?? 0} limit={u.storage?.limit_gb ?? 0} unit=" GB" />
        </div>
      </CardBody></Card>

      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Your add-ons</h2><div className="pc-panel-h__sub">Billed separately from your plan</div></div></div>
        {(products ?? []).length === 0 && <div className="bl-empty">No add-ons yet.</div>}
        {(products ?? []).map((p) => (
          <div className="bl-addon" key={p.id}>
            <span>{p.name}</span>
            <span className="bl-addon__r"><span className={statusClass(p.status)}>{p.status}</span><b>{money(p.price, p.currency)}</b><span className="bl-mut">/mo</span></span>
          </div>
        ))}
        {canProducts && <div className="qfoot" style={{ marginTop: 'var(--aeos-space-3)' }}><button type="button" className="pc-btn pc-btn--sm" onClick={onManageProducts}>Browse marketplace →</button></div>}
      </CardBody></Card>
    </div>
  );
}

function PlansPanel({ plans, currentPlanId, currentPrice, onChangePlan, onCancel, changingId, cancelling, canUpgrade, canCancel }) {
  return (
    <>
      <div className="bl-plans">
        {(plans ?? []).map((p) => {
          const isCurrent = p.id === currentPlanId;
          const isUpgrade = currentPrice == null || p.price >= currentPrice;
          return (
            <Card key={p.id} className={`bl-plan${isCurrent ? ' is-current' : ''}`}>
              <CardBody className="bl-plan__body">
                {isCurrent && <span className="bl-plan__badge">CURRENT</span>}
                <div className="bl-plan__name">{p.name}</div>
                <div className="bl-plan__price">{money(p.price, p.currency)} <span>/ {p.interval}</span></div>
                {p.features?.length > 0 && <ul className="bl-feat">{p.features.map((f, i) => <li key={i}>{f}</li>)}</ul>}
                {isCurrent ? (
                  <button type="button" className="pc-btn pc-btn--sm bl-plan__btn" disabled>Your plan</button>
                ) : (
                  <button type="button" className={`pc-btn pc-btn--sm bl-plan__btn${isUpgrade ? ' pc-btn--primary' : ''}`}
                    disabled={!canUpgrade || changingId === p.id} onClick={() => onChangePlan(p.id)}>
                    {changingId === p.id ? 'Working…' : (isUpgrade ? 'Upgrade' : 'Downgrade')}
                  </button>
                )}
              </CardBody>
            </Card>
          );
        })}
      </div>
      {canCancel && currentPlanId && (
        <div style={{ marginTop: 'var(--aeos-space-4)', textAlign: 'center' }}>
          <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={onCancel} disabled={cancelling}>{cancelling ? 'Cancelling…' : 'Cancel subscription'}</button>
        </div>
      )}
    </>
  );
}

function ProductsPanel({ subscriptions, catalog, onSubscribe, onCancel, subscribingId, cancellingId, canSubscribe, canCancel }) {
  return (
    <>
      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Your add-ons</h2><div className="pc-panel-h__sub">Active subscriptions billed separately</div></div></div>
        {(subscriptions ?? []).length === 0 && <div className="bl-empty">No active add-ons.</div>}
        {(subscriptions ?? []).map((p) => (
          <div className="bl-addon" key={p.id}>
            <span>{p.name}</span>
            <span className="bl-addon__r"><span className={statusClass(p.status)}>{p.status}</span><b>{money(p.price, p.currency)}</b><span className="bl-mut">/mo</span>
              {canCancel && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => onCancel(p.id)} disabled={cancellingId === p.id}>{cancellingId === p.id ? '…' : 'Cancel'}</button>}
            </span>
          </div>
        ))}
      </CardBody></Card>

      <div style={{ marginTop: 'var(--aeos-space-4)' }}>
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Marketplace</h2><div className="pc-panel-h__sub">Add-ons available for your workspace</div></div></div>
          {(catalog ?? []).length === 0 && <div className="bl-empty">No add-ons available.</div>}
          <div className="bl-cat">
            {(catalog ?? []).map((c) => (
              <Card className="bl-catcard" key={c.id}>
                <CardBody className="bl-catcard__body">
                  <div className="bl-catcard__top">
                    <div><div className="bl-catcard__name">{c.name}</div></div>
                    <div className="bl-catcard__price">{money(c.price, c.currency)}<span>/mo</span></div>
                  </div>
                  <div className="bl-catcard__desc">{c.description || '—'}</div>
                  <div style={{ marginTop: 'var(--aeos-space-3)' }}>
                    {c.subscribed ? <button type="button" className="pc-btn pc-btn--sm" disabled>Subscribed</button>
                      : canSubscribe ? <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => onSubscribe(c.id)} disabled={subscribingId === c.id}>{subscribingId === c.id ? 'Subscribing…' : 'Subscribe'}</button>
                      : <button type="button" className="pc-btn pc-btn--sm" disabled>Subscribe</button>}
                  </div>
                </CardBody>
              </Card>
            ))}
          </div>
        </CardBody></Card>
      </div>
    </>
  );
}

function UsagePanel({ usage }) {
  const u = usage ?? {};
  const metrics = u.metrics ?? {};
  const keys = Object.keys(metrics);
  return (
    <Card><CardBody>
      <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Usage &amp; quotas</h2><div className="pc-panel-h__sub">Consumption against your plan limits</div></div></div>
      <UsageBar label="Team members" used={u.users?.used ?? 0} limit={u.users?.limit ?? 0} unit="" />
      <UsageBar label="Storage" used={u.storage?.used_gb ?? 0} limit={u.storage?.limit_gb ?? 0} unit=" GB" />
      <AiUsageCard variant="row" />
      {keys.length > 0 && <>
        <div className="pc-panel-h" style={{ marginTop: 'var(--aeos-space-4)' }}><div><h2 className="pc-panel-h__title" style={{ fontSize: 'var(--aeos-text-base)' }}>Metered usage this period</h2></div></div>
        {keys.map((k) => (
          <div className="bl-addon" key={k}><span style={{ textTransform: 'capitalize' }}>{k.replace(/_/g, ' ')}</span><b className="bl-num">{metrics[k]}</b></div>
        ))}
      </>}
    </CardBody></Card>
  );
}

function InvoicesPanel({ invoices, onPage }) {
  const rows = invoices?.data ?? [];
  const cur = invoices?.current_page ?? 1;
  const last = invoices?.last_page ?? 1;
  return (
    <Card><CardBody>
      <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Billing history</h2><div className="pc-panel-h__sub">Invoices for this workspace</div></div></div>
      {rows.length === 0 ? <div className="bl-empty">No invoices yet.</div> : (
        <table className="bl-table">
          <thead><tr><th>Invoice</th><th>Date</th><th>Period</th><th className="bl-num">Amount</th><th>Status</th><th /></tr></thead>
          <tbody>{rows.map((iv) => (
            <tr key={iv.id}>
              <td><b>{iv.number || `#${iv.id}`}</b></td>
              <td>{fmtDate(iv.date)}</td>
              <td className="bl-mut">{iv.period_start ? `${fmtDate(iv.period_start)} – ${fmtDate(iv.period_end)}` : '—'}</td>
              <td className="bl-num">{money(iv.amount, iv.currency)}</td>
              <td><span className={statusClass(iv.status)}>{iv.status || '—'}</span></td>
              <td style={{ textAlign: 'right' }}>
                {iv.has_pdf ? <a className="pc-btn pc-btn--sm" href={route('core.subscription.invoices.download', iv.id)}>{I.download}<span>PDF</span></a> : <span className="bl-mut">—</span>}
              </td>
            </tr>
          ))}</tbody>
        </table>
      )}
      {last > 1 && (
        <div className="bl-pager">
          <span>Page {cur} of {last}</span>
          <span style={{ display: 'flex', gap: 8 }}>
            <button type="button" className="pc-btn pc-btn--sm" disabled={cur <= 1} onClick={() => onPage(cur - 1)}>Prev</button>
            <button type="button" className="pc-btn pc-btn--sm" disabled={cur >= last} onClick={() => onPage(cur + 1)}>Next</button>
          </span>
        </div>
      )}
    </CardBody></Card>
  );
}

/* ---------------- rail ---------------- */
function Rail({ summary }) {
  const s = summary ?? {};
  return (
    <div className="pc-rail bl">
      <div>
        <div className="pc-panel-h__title">Billing</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Plan</span><b>{s.plan_name ?? '—'}</b></div>
          <div className="pc-rail__row"><span>Price</span><b>{s.price != null ? `${money(s.price, s.currency)}/${s.interval}` : '—'}</b></div>
          <div className="pc-rail__row"><span>Status</span><b>{s.status ?? '—'}</b></div>
          {s.days_left != null && <div className="pc-rail__row"><span>Trial left</span><b>{s.days_left}d</b></div>}
          <div className="pc-rail__row"><span>Users</span><b>{s.users?.used ?? 0}{s.users?.limit ? ` / ${s.users.limit}` : ''}</b></div>
          <div className="pc-rail__row"><span>Storage</span><b>{s.storage?.used_gb ?? 0}{s.storage?.limit_gb ? ` / ${s.storage.limit_gb}` : ''} GB</b></div>
        </div>
      </div>
    </div>
  );
}

/* ================= page ================= */
export default function SubscriptionIndex(props) {
  const { tab: initialTab, summary, plan, usage, products, catalog, plans, currentPlanId, invoices } = props;
  const toast = useToast();
  const canUsage = useHRMAC('core.subscription.usage.view');
  const canInvoices = useHRMAC('core.subscription.invoices.view');
  const canUpgrade = useHRMAC('core.subscription.plans.upgrade');
  const canCancel = useHRMAC('core.subscription.plans.cancel');
  const canProducts = useHRMAC('core.subscription.products.view');
  const canSubscribe = useHRMAC('core.subscription.products.subscribe');
  const canCancelAddon = useHRMAC('core.subscription.products.cancel');

  const [tab, setTab] = useState(initialTab || 'overview');
  const [changingId, setChangingId] = useState(null);
  const [cancelling, setCancelling] = useState(false);
  const [subscribingId, setSubscribingId] = useState(null);
  const [cancellingAddonId, setCancellingAddonId] = useState(null);

  useEffect(() => { setTab(initialTab || 'overview'); }, [initialTab]);

  const switchTab = (next) => { setTab(next); router.get(route('core.subscription.index'), { tab: next }, { preserveState: true, preserveScroll: true, only: ONLY }); };
  const changePlan = (planId) => confirmDo('Switch to this plan? This affects your billing.', () => {
    setChangingId(planId);
    router.post(route('core.subscription.change-plan'), { plan_id: planId }, { preserveScroll: true, onSuccess: () => toast.success('Plan updated.'), onError: () => toast.error('Could not change plan.'), onFinish: () => setChangingId(null) });
  });
  const cancel = () => confirmDo('Cancel your subscription? It stays active until the end of the billing period.', () => {
    setCancelling(true);
    router.post(route('core.subscription.cancel'), {}, { preserveScroll: true, onSuccess: () => toast.success('Cancellation scheduled.'), onError: () => toast.error('Could not cancel subscription.'), onFinish: () => setCancelling(false) });
  });
  const subscribeProduct = (productId) => confirmDo('Subscribe to this add-on? It is billed separately from your plan.', () => {
    setSubscribingId(productId);
    router.post(route('core.subscription.products.subscribe'), { product_id: productId }, { preserveScroll: true, only: ONLY, onSuccess: () => toast.success('Add-on subscribed.'), onError: () => toast.error('Could not subscribe to add-on.'), onFinish: () => setSubscribingId(null) });
  });
  const cancelProduct = (subscriptionId) => confirmDo('Cancel this add-on? It stays active until the end of the billing period.', () => {
    setCancellingAddonId(subscriptionId);
    router.post(route('core.subscription.products.cancel', subscriptionId), {}, { preserveScroll: true, only: ONLY, onSuccess: () => toast.success('Add-on cancellation scheduled.'), onError: () => toast.error('Could not cancel add-on.'), onFinish: () => setCancellingAddonId(null) });
  });
  const invoicesPage = (page) => router.get(route('core.subscription.index'), { tab: 'invoices', page }, { preserveState: true, preserveScroll: true, only: ['invoices', 'tab'] });

  const tabs = [
    { value: 'overview', label: 'Overview' },
    { value: 'plans', label: 'Plans' },
    canProducts && { value: 'products', label: 'Add-ons' },
    canUsage && { value: 'usage', label: 'Usage' },
    canInvoices && { value: 'invoices', label: 'Invoices' },
  ].filter(Boolean);

  const s = summary ?? {};
  const uPct = pct(s.users?.used ?? 0, s.users?.limit ?? 0);
  const stPct = pct(s.storage?.used_gb ?? 0, s.storage?.limit_gb ?? 0);
  const kpis = useMemo(() => ([
    { label: 'Current plan', value: s.plan_name ?? 'None', delta: s.price != null ? `${money(s.price, s.currency)} / ${s.interval}` : 'no active plan' },
    { label: 'Status', value: (s.status ?? '—'), delta: s.days_left != null ? `trial · ${s.days_left} days left` : 'billing active', chip: s.status },
    { label: 'Team members', value: s.users?.limit ? `${s.users.used} / ${s.users.limit}` : (s.users?.used ?? 0), meter: uPct, delta: s.users?.limit ? `${uPct}% of seats` : 'unlimited' },
    { label: 'Storage', value: s.storage?.limit_gb ? `${s.storage.used_gb} / ${s.storage.limit_gb} GB` : `${s.storage?.used_gb ?? 0} GB`, meter: stPct, delta: s.storage?.limit_gb ? `${stPct}% used` : 'unlimited' },
  ]), [summary]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className="pc bl">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Workspace · Billing</div>
          <h1 className="pc-title">Billing &amp; Subscription</h1>
          <div className="pc-sub">Your plan, usage, add-ons and billing history — upgrades, downgrades, cancellation and invoices in one place.</div>
        </div>
        <div className="pc-actions">
          {canUpgrade && <button type="button" className="pc-btn pc-btn--primary" onClick={() => switchTab('plans')}>{I.card}<span>Change plan</span></button>}
        </div>
      </div>

      <div className="bl-kpis">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value" style={{ textTransform: c.label === 'Status' ? 'capitalize' : 'none' }}>{c.value}</div>
            <div className="pc-kpi__delta">{c.chip && <span className={statusClass(c.chip)} style={{ marginRight: 6 }}>{c.chip}</span>}{c.delta}</div>
            {c.meter != null && <div className="bl-meter"><i className={meterClass(c.meter)} style={{ width: `${c.meter}%` }} /></div>}
          </CardBody></Card>
        ))}
      </div>

      <div className="bl-tabs" role="tablist">
        {tabs.map((t) => (
          <button key={t.value} type="button" role="tab" aria-selected={tab === t.value} className={`bl-tab${tab === t.value ? ' is-active' : ''}`} onClick={() => switchTab(t.value)}>{t.label}</button>
        ))}
      </div>

      {tab === 'plans' ? (
        <PlansPanel plans={plans} currentPlanId={currentPlanId} currentPrice={s.price} onChangePlan={changePlan} onCancel={cancel} changingId={changingId} cancelling={cancelling} canUpgrade={canUpgrade} canCancel={canCancel} />
      ) : tab === 'products' ? (
        <ProductsPanel subscriptions={products} catalog={catalog} onSubscribe={subscribeProduct} onCancel={cancelProduct} subscribingId={subscribingId} cancellingId={cancellingAddonId} canSubscribe={canSubscribe} canCancel={canCancelAddon} />
      ) : tab === 'usage' ? (
        <UsagePanel usage={usage} />
      ) : tab === 'invoices' ? (
        <InvoicesPanel invoices={invoices} onPage={invoicesPage} />
      ) : (
        <OverviewPanel summary={summary} plan={plan} usage={usage} products={products} canUpgrade={canUpgrade} canCancel={canCancel} canProducts={canProducts}
          onChange={() => switchTab('plans')} onManageProducts={() => switchTab('products')} onCancel={cancel} cancelling={cancelling} />
      )}
    </div>
  );
}

SubscriptionIndex.layout = (page) => (
  <App title="Billing & Subscription" railTitle="Billing" rail={<Rail summary={page.props.summary} />}>{page}</App>
);
