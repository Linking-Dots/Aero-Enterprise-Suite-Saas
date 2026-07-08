import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, BarsDiverging, CohortGrid, Donut,
  useWorkbench, useCtxMenu,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import './subscriptions.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  plans: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  tenants: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};

const STATUS_LABEL = { active: 'Active', trialing: 'Trialing', past_due: 'Past due', incomplete: 'Incomplete', paused: 'Paused', cancelled: 'Cancelled', expired: 'Expired' };
const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : fmtMoney(n));
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; }
};
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};
const fmtMonthYear = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }); } catch { return s; }
};

const post = (url, data = {}) => router.post(url, data, { preserveScroll: true });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };

/* ---------------- rail ---------------- */
function SubscriptionsRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Recurring</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>MRR</span><b>{fmtMoney(s.mrr)}</b></div>
          <div className="pc-rail__row"><span>ARR</span><b>{fmtMoney(s.arr)}</b></div>
          <div className="pc-rail__row"><span>Active</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Trialing</span><b>{s.trialing ?? 0}</b></div>
          <div className="pc-rail__row"><span>Dunning</span><b>{fmtMoney(s.dunning_amount)}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/plans')}>{Glyph.plans}<span>Plans</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/dashboard')}>{Glyph.billing}<span>Billing</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/tenants')}>{Glyph.tenants}<span>Tenants</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- modals ---------------- */
function CancelModal({ sub, onClose }) {
  const form = useForm({ reason: '', mode: 'period_end' });
  const isProduct = sub.kind === 'product';
  const submit = (e) => {
    e.preventDefault();
    const url = isProduct ? `/billing/subscriptions/product/${sub.id}/cancel` : `/billing/subscriptions/${sub.id}/cancel`;
    form.post(url, { preserveScroll: true, onSuccess: onClose });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Cancel subscription</h2>
        <div className="pc-modal__sub">{sub.tenant} · {sub.label}</div>
        <form className="pc-form" onSubmit={submit}>
          {!isProduct && (
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="c-mode">When</label>
              <select id="c-mode" className="pc-input" value={form.data.mode} onChange={(e) => form.setData('mode', e.target.value)}>
                <option value="period_end">At period end — access continues until the paid period lapses</option>
                <option value="immediate">Immediately — access ends now, product add-ons cascade-cancel</option>
              </select>
            </div>
          )}
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="c-reason">Reason</label>
            <textarea id="c-reason" className="pc-input" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="Why is this being cancelled?" />
            {form.errors.reason && <span className="pc-field__err">{form.errors.reason}</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Keep</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={form.processing}>{form.processing ? 'Cancelling…' : 'Cancel subscription'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function ChangePlanModal({ sub, plans, onClose }) {
  const form = useForm({ plan_id: sub.plan_id ?? '' });
  const submit = (e) => { e.preventDefault(); form.post(`/billing/subscriptions/${sub.id}/change-plan`, { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Change plan</h2>
        <div className="pc-modal__sub">{sub.tenant} — currently on {sub.label}.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="cp-plan">New plan</label>
            <select id="cp-plan" className="pc-input" value={form.data.plan_id} onChange={(e) => form.setData('plan_id', e.target.value)}>
              <option value="">Select a plan…</option>
              {(plans ?? []).map((p) => <option key={p.id} value={p.id}>{p.name} — {fmtMoney(p.price_monthly)}/mo</option>)}
            </select>
            {form.errors.plan_id && <span className="pc-field__err">{form.errors.plan_id}</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing}>{form.processing ? 'Saving…' : 'Change plan'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function CreateModal({ plans, tenants, products, onClose }) {
  const form = useForm({ tenant_id: '', kind: 'plan', plan_id: '', product_id: '', billing_cycle: 'monthly', trial_days: 0 });
  const submit = (e) => { e.preventDefault(); form.post('/billing/subscriptions', { preserveScroll: true, onSuccess: onClose }); };
  const offering = form.data.kind === 'plan'
    ? (plans ?? []).find((p) => String(p.id) === String(form.data.plan_id))
    : (products ?? []).find((p) => String(p.id) === String(form.data.product_id));
  const price = offering
    ? (form.data.billing_cycle === 'yearly'
      ? Number(offering.price_annual ?? offering.yearly_price ?? 0)
      : Number(offering.price_monthly ?? offering.monthly_price ?? 0))
    : null;
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">New subscription</h2>
        <div className="pc-modal__sub">Attach a plan or product subscription to a tenant. Billing starts per the terms below.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="n-tenant">Tenant</label>
            <select id="n-tenant" className="pc-input" value={form.data.tenant_id} onChange={(e) => form.setData('tenant_id', e.target.value)}>
              <option value="">Select a tenant…</option>
              {(tenants ?? []).map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
            </select>
            {form.errors.tenant_id && <span className="pc-field__err">{form.errors.tenant_id}</span>}
          </div>
          <div className="pc-row2">
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="n-kind">Type</label>
              <select id="n-kind" className="pc-input" value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)}>
                <option value="plan">Plan</option>
                <option value="product">Product add-on</option>
              </select>
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="n-off">Offering</label>
              {form.data.kind === 'plan' ? (
                <select id="n-off" className="pc-input" value={form.data.plan_id} onChange={(e) => form.setData('plan_id', e.target.value)}>
                  <option value="">Select a plan…</option>
                  {(plans ?? []).map((p) => <option key={p.id} value={p.id}>{p.name} — {fmtMoney(p.price_monthly)}/mo</option>)}
                </select>
              ) : (
                <select id="n-off" className="pc-input" value={form.data.product_id} onChange={(e) => form.setData('product_id', e.target.value)}>
                  <option value="">Select a product…</option>
                  {(products ?? []).map((p) => <option key={p.id} value={p.id}>{p.name} — {fmtMoney(p.monthly_price)}/mo</option>)}
                </select>
              )}
              {(form.errors.plan_id || form.errors.product_id) && <span className="pc-field__err">{form.errors.plan_id ?? form.errors.product_id}</span>}
            </div>
          </div>
          <div className="pc-row2">
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="n-cycle">Billing cycle</label>
              <select id="n-cycle" className="pc-input" value={form.data.billing_cycle} onChange={(e) => form.setData('billing_cycle', e.target.value)}>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="n-trial">Trial</label>
              <select id="n-trial" className="pc-input" value={form.data.trial_days} onChange={(e) => form.setData('trial_days', Number(e.target.value))}>
                <option value={0}>No trial — bill now</option>
                <option value={14}>14-day trial</option>
                <option value={30}>30-day trial</option>
              </select>
            </div>
          </div>
          {price != null && (
            <div className="pc-ent">
              <div className="pc-ent__l">Summary</div>
              <div className="pc-sub">
                {form.data.trial_days > 0
                  ? `Trial for ${form.data.trial_days} days, then ${fmtMoney(price)}/${form.data.billing_cycle === 'yearly' ? 'yr' : 'mo'}.`
                  : `First charge ${fmtMoney(price)} today, renews ${form.data.billing_cycle}.`} Audit-logged.
              </div>
            </div>
          )}
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing}>{form.processing ? 'Creating…' : 'Create subscription'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ row, plans, onClose, onCancel, onChangePlan }) {
  const [tab, setTab] = useState('overview');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  const ctx = useCtxMenu();

  useEffect(() => {
    setTab('overview');
    setDetail(null);
    setFailed(false);
    if (!row) return undefined;
    const ac = new AbortController();
    fetch(`/billing/subscriptions/detail/${row.kind}/${row.id}`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [row?.kind, row?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!row) return null;
  const isPlan = row.kind === 'plan';
  const base = `/billing/subscriptions/${row.id}`;
  const moreItems = [
    ...(row.status === 'trialing' ? [
      { label: 'Convert trial now', onClick: () => confirmPost(`Convert ${row.tenant}'s trial to a paid subscription now?`, `${base}/trial/convert`) },
      { label: 'Extend trial 14 days', onClick: () => post(`${base}/trial/extend`, { days: 14 }) },
    ] : []),
    ...(isPlan && ['active', 'trialing', 'past_due'].includes(row.status) ? [
      { label: `Switch to ${row.billing_cycle === 'yearly' ? 'monthly' : 'yearly'} billing`, onClick: () => post(`${base}/change-cycle`, { billing_cycle: row.billing_cycle === 'yearly' ? 'monthly' : 'yearly' }) },
    ] : []),
    'sep',
    { label: 'Cancel subscription…', danger: true, onClick: () => onCancel(row) },
  ];

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'invoices', label: `Invoices${detail ? ` · ${detail.invoices.length}` : ''}` },
    { id: 'dunning', label: 'Dunning' },
    { id: 'activity', label: 'Activity' },
  ];

  return (
    <WbDrawer
      open
      onClose={onClose}
      ariaLabel={`Subscription detail — ${row.tenant}`}
      tabs={tabs}
      activeTab={tab}
      onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(row.tenant)}</div>
            <div>
              <div className="sc-dr-title">{row.tenant}</div>
              <div className="sc-dr-code">{row.kind} · {row.label}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">MRR</div><div className="v">{row.amount ? `${fmtMoney(row.amount)}` : '—'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{STATUS_LABEL[row.status] ?? row.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">Renews</div><div className="v">{fmtDateShort(row.renews_at)}</div></div>
          </div>
          {isPlan && (
            <div className="sc-dr-acts">
              {['active', 'trialing', 'past_due'].includes(row.status) && (
                <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => onChangePlan(row)}>Change plan</button>
              )}
              {row.status === 'active' && (
                <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Pause ${row.tenant}'s subscription?`, `${base}/pause`)}>Pause</button>
              )}
              {row.status === 'paused' && (
                <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`${base}/resume`)}>Resume</button>
              )}
              {row.status === 'cancelled' && (
                <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Reactivate ${row.tenant}'s subscription?`, `${base}/reactivate`)}>Reactivate</button>
              )}
              <button type="button" className="pc-btn pc-btn--sm" onClick={(e) => ctx.open(e.currentTarget, moreItems)}>More ▾</button>
            </div>
          )}
        </>
      }
    >
      {tab === 'overview' && (
        <div>
          <div className="pc-drow"><span className="pc-drow__k">{isPlan ? 'Plan' : 'Product'}</span><span className="pc-drow__v">{row.label}{row.amount ? ` — ${fmtMoney(row.amount)}/mo` : ''}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Billing cycle</span><span className="pc-drow__v">{row.billing_cycle ?? '—'}</span></div>
          {row.quantity != null && <div className="pc-drow"><span className="pc-drow__k">Seats</span><span className="pc-drow__v">{row.quantity}</span></div>}
          <div className="pc-drow"><span className="pc-drow__k">Started</span><span className="pc-drow__v">{fmtDate(row.since)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">{row.status === 'trialing' ? 'Trial ends' : 'Renews'}</span><span className="pc-drow__v">{fmtDate(row.status === 'trialing' ? row.trial_ends : row.renews_at)}</span></div>
          {row.cancelled_at && <div className="pc-drow"><span className="pc-drow__k">Cancelled</span><span className="pc-drow__v">{fmtDate(row.cancelled_at)}</span></div>}
          <div className="pc-drow">
            <span className="pc-drow__k">Payment method</span>
            <span className="pc-drow__v">{detail?.payment_method ? `${detail.payment_method.type} ···· ${detail.payment_method.last_four}` : failed ? '—' : detail ? 'None on record' : 'Loading…'}</span>
          </div>
          {detail?.discount_amount > 0 && <div className="pc-drow"><span className="pc-drow__k">Discount</span><span className="pc-drow__v">{fmtMoney(detail.discount_amount)}</span></div>}
          {failed && <div className="pc-sub" style={{ marginTop: 'var(--aeos-space-3)' }}>Could not load extended detail — actions above still work.</div>}
        </div>
      )}
      {tab === 'invoices' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load invoices.' : 'Loading…'}</div>
          : detail.invoices.length === 0 ? <div className="wb-empty">No invoices for this subscription yet.</div>
            : (
              <table className="sc-minit">
                <tbody>
                  {detail.invoices.map((inv) => (
                    <tr key={inv.number}>
                      <td>{inv.number}</td>
                      <td className="sc-kind">{fmtDateShort(inv.date)}</td>
                      <td className="pc-r pc-price">{fmtMoney(inv.amount)}</td>
                      <td className="pc-r"><span className={`pc-chip pc-chip--${inv.status === 'paid' ? 'active' : inv.status === 'overdue' ? 'past_due' : 'incomplete'}`}><span className="pc-chip__dot" />{inv.status}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )
      )}
      {tab === 'dunning' && (
        row.status === 'past_due' ? (
          <div>
            <div className="pc-drow"><span className="pc-drow__k">Days overdue</span><span className="pc-drow__v">{row.days_to_renewal != null && row.days_to_renewal < 0 ? Math.abs(row.days_to_renewal) : '—'}</span></div>
            <div className="sc-dr-sec">Recovery</div>
            <div className="sc-dr-acts">
              <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => post(`${base}/retry-charge`)}>Queue retry</button>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`${base}/remind`)}>Send reminder</button>
            </div>
          </div>
        ) : <div className="wb-empty">No failed payments on this subscription.</div>
      )}
      {tab === 'activity' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
          : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet.</div>
            : (
              <ul className="sc-tl">
                {detail.activity.map((a, i) => (
                  <li key={i}>
                    {a.detail || a.event}
                    <span className="when">{fmtDate(a.at)}{a.actor ? ` · ${a.actor}` : ''}</span>
                  </li>
                ))}
              </ul>
            )
      )}
      {ctx.element}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Index({
  stats, plan_subscriptions, product_subscriptions, plans, tenants, products,
  sparks, mrr_trend, mrr_movement, cohorts, queues,
}) {
  const s = stats ?? {};
  const q = queues ?? {};
  const sp = sparks ?? {};
  const rows = useMemo(
    () => [...(plan_subscriptions ?? []), ...(product_subscriptions ?? [])],
    [plan_subscriptions, product_subscriptions]
  );

  const [cancelSub, setCancelSub] = useState(null);
  const [changeSub, setChangeSub] = useState(null);
  const [creating, setCreating] = useState(false);
  const [drawerRow, setDrawerRow] = useState(null);
  const ctx = useCtxMenu();

  const wb = useWorkbench({
    rows,
    getId: (r) => `${r.kind}-${r.id}`,
    searchText: (r) => `${r.tenant} ${r.label} ${r.status}`,
    views: [
      { id: 'all', label: 'All subscriptions' },
      { id: 'atrisk', label: 'At risk', test: (r) => ['past_due', 'incomplete'].includes(r.status) },
      { id: 'trials', label: 'Trials ending', test: (r) => r.status === 'trialing' },
      { id: 'high', label: 'High value', test: (r) => r.amount >= 300 },
      { id: 'renewals', label: 'Renewing ≤ 30d', test: (r) => r.status === 'active' && r.days_to_renewal != null && r.days_to_renewal >= 0 && r.days_to_renewal <= 30 },
    ],
    facets: {
      kind: { value: 'all', test: (r, v) => r.kind === v },
      status: { value: 'all', test: (r, v) => r.status === v },
      cycle: { value: 'all', test: (r, v) => r.billing_cycle === v },
      mrr: { value: 'all', test: (r, v) => r.amount >= Number(v) },
    },
    sortKey: 'since',
    sortVal: (r, k) => (k === 'since' || k === 'renews') ? String(r[k === 'renews' ? 'renews_at' : 'since'] ?? '') : (k === 'amount' ? r.amount : String(r[k] ?? '')),
    perPage: 10,
    storageKey: 'platform.subscriptions',
  });

  const atRisk = (s.past_due ?? 0) + (s.incomplete ?? 0);
  const total = Math.max(1, s.total ?? 1);
  const donutStatuses = [
    ['active', 'var(--aeos-success)'],
    ['trialing', 'var(--aeos-primary)'],
    ['past_due', 'var(--aeos-warning)'],
    ['incomplete', 'var(--aeos-text-muted)'],
    ['cancelled', 'var(--aeos-danger)'],
  ];
  const churnNow = (sp.churn_pct ?? []).length ? sp.churn_pct[sp.churn_pct.length - 1] : 0;
  const netJul = (mrr_movement?.new ?? []).length
    ? (mrr_movement.new.at(-1) + mrr_movement.expansion.at(-1) - mrr_movement.contraction.at(-1) - mrr_movement.churn.at(-1))
    : 0;

  const kpis = [
    { label: 'Monthly recurring', value: fmtMoney(s.mrr), delta: `${(s.mrr_delta_pct ?? 0) >= 0 ? '▲' : '▼'} ${Math.abs(s.mrr_delta_pct ?? 0)}% vs last month`, up: (s.mrr_delta_pct ?? 0) >= 0, spark: sp.mrr },
    { label: 'Annual run rate', value: fmtK(s.arr ?? 0), delta: `plan ${fmtMoney(s.plan_mrr)} · product ${fmtMoney(s.product_mrr)}`, up: true, spark: (sp.mrr ?? []).map((v) => v * 12) },
    { label: 'Active', value: s.active ?? 0, delta: `${s.plan_total ?? 0} plan · ${s.product_total ?? 0} product records`, spark: sp.active },
    { label: 'In trial', value: s.trialing ?? 0, delta: `${s.trials_ending_7d ?? 0} end within 7 days`, warn: (s.trials_ending_7d ?? 0) > 0, spark: sp.trials },
    { label: 'MRR churn', value: `${churnNow}%`, delta: churnNow <= 2 ? 'healthy' : 'watch closely', up: churnNow <= 2, spark: sp.churn_pct },
    { label: 'In dunning', value: fmtMoney(s.dunning_amount), delta: `${s.dunning_count ?? 0} past-due · ${atRisk} at risk`, down: (s.dunning_count ?? 0) > 0, spark: mrr_movement?.churn },
  ];

  const rowMenu = (r) => {
    const base = `/billing/subscriptions/${r.id}`;
    if (r.kind === 'product') {
      return ['active', 'trialing'].includes(r.status)
        ? [{ label: 'Cancel subscription…', danger: true, onClick: () => setCancelSub(r) }]
        : [{ label: 'View detail', onClick: () => setDrawerRow(r) }];
    }
    switch (r.status) {
      case 'cancelled':
      case 'expired':
        return [{ label: 'Reactivate', onClick: () => confirmPost(`Reactivate ${r.tenant}'s ${r.label} subscription?`, `${base}/reactivate`) }];
      case 'paused':
        return [
          { label: 'Resume', onClick: () => post(`${base}/resume`) },
          'sep',
          { label: 'Cancel subscription…', danger: true, onClick: () => setCancelSub(r) },
        ];
      case 'trialing':
        return [
          { label: 'Convert trial now', onClick: () => confirmPost(`Convert ${r.tenant}'s trial to paid now?`, `${base}/trial/convert`) },
          { label: 'Extend trial 14 days', onClick: () => post(`${base}/trial/extend`, { days: 14 }) },
          { label: 'Change plan', onClick: () => setChangeSub(r) },
          'sep',
          { label: 'Cancel trial…', danger: true, onClick: () => setCancelSub(r) },
        ];
      case 'past_due':
        return [
          { label: 'Queue payment retry', onClick: () => post(`${base}/retry-charge`) },
          { label: 'Send payment reminder', onClick: () => post(`${base}/remind`) },
          { label: 'Change plan', onClick: () => setChangeSub(r) },
          'sep',
          { label: 'Cancel subscription…', danger: true, onClick: () => setCancelSub(r) },
        ];
      default: // active / incomplete
        return [
          { label: 'Change plan', onClick: () => setChangeSub(r) },
          ...(r.status === 'active' ? [{ label: 'Pause subscription', onClick: () => confirmPost(`Pause ${r.tenant}'s subscription?`, `${base}/pause`) }] : []),
          { label: `Switch to ${r.billing_cycle === 'yearly' ? 'monthly' : 'yearly'} billing`, onClick: () => post(`${base}/change-cycle`, { billing_cycle: r.billing_cycle === 'yearly' ? 'monthly' : 'yearly' }) },
          'sep',
          { label: 'Cancel subscription…', danger: true, onClick: () => setCancelSub(r) },
        ];
    }
  };

  const renewsCell = (r) => {
    const d = r.days_to_renewal;
    const overdue = d != null && d < 0 && ['past_due', 'active'].includes(r.status);
    const soon = d != null && d >= 0 && d <= 14 && ['active', 'trialing'].includes(r.status);
    const text = r.status === 'cancelled' ? `Ended ${fmtDateShort(r.cancelled_at)}`
      : r.status === 'incomplete' ? 'Payment pending'
        : overdue ? `${Math.abs(d)}d overdue`
          : r.status === 'trialing' ? `Trial ends ${fmtDateShort(r.trial_ends)}`
            : fmtDateShort(r.renews_at);
    return <span className={`sc-renews${overdue ? ' sc-renews--due' : soon ? ' sc-renews--soon' : ''}`}>{text}</span>;
  };

  const columns = [
    {
      key: 'tenant', label: 'Tenant', sortable: true,
      render: (r) => (
        <div className="pc-mrow">
          <div className="sc-av">{initials(r.tenant)}</div>
          <div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.kind}</div></div>
        </div>
      ),
    },
    { key: 'label', label: 'Plan / Product', hideSm: true, render: (r) => <span className="pc-modtag">{r.label}</span> },
    { key: 'status', label: 'Status', render: (r) => <span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span> },
    { key: 'amount', label: 'MRR', align: 'r', sortable: true, render: (r) => <span className="pc-price">{r.amount ? fmtMoney(r.amount) : '—'}{r.amount ? <small>/mo</small> : null}</span> },
    { key: 'cycle', label: 'Cycle', hideSm: true, render: (r) => <span className="sc-kind">{r.billing_cycle ?? '—'}</span> },
    { key: 'renews', label: 'Renews', sortable: true, render: renewsCell },
    { key: 'since', label: 'Started', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtMonthYear(r.since)}</span> },
    {
      key: 'actions', label: '', align: 'r', width: 44,
      render: (r) => (
        <button type="button" className="wb-kebab" aria-label={`Actions for ${r.tenant}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button>
      ),
    },
  ];

  const bulkPayload = () => wb.selectedRows.map((r) => ({ kind: r.kind, id: r.id }));
  const exportSelectedCsv = () => {
    const header = 'kind,tenant,label,status,billing_cycle,mrr,renews_at,started_at';
    const lines = wb.selectedRows.map((r) =>
      [r.kind, r.tenant, r.label, r.status, r.billing_cycle ?? '', r.amount, r.renews_at ?? '', r.since ?? '']
        .map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `subscriptions-selected-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Revenue operations</div>
          <h1 className="pc-title">Subscriptions</h1>
          <div className="pc-sub">Every plan and product subscription across the platform — recurring revenue, trials, renewals and churn risk in one operating view.</div>
        </div>
        <div className="pc-actions">
          <button
            type="button"
            className="pc-btn"
            onClick={(e) => ctx.open(e.currentTarget, [
              { label: 'Export CSV — all subscriptions', onClick: () => { window.location.href = '/billing/subscriptions/export'; } },
              { label: 'Print this view', onClick: () => window.print() },
            ])}
          >
            {Glyph.export}<span>Export</span>
          </button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={() => setCreating(true)}>{Glyph.plus}<span>New subscription</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}${c.warn ? ' pc-kpi__delta--warn' : ''}${c.down ? ' pc-kpi__delta--down' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color="var(--aeos-primary)" /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">MRR movement</h2><div className="pc-panel-h__sub">New vs churned recurring revenue — last 6 months</div></div>
            <span className={`sc-badge ${netJul >= 0 ? 'sc-badge--ok' : 'sc-badge--bad'}`}>Net {netJul >= 0 ? '+' : '−'}{fmtK(Math.abs(netJul))} this month</span>
          </div>
          <BarsDiverging
            labels={mrr_movement?.labels ?? []}
            up={[
              { key: 'new', label: 'New', color: 'var(--aeos-success)', values: mrr_movement?.new ?? [] },
              { key: 'exp', label: 'Expansion', color: 'var(--aeos-primary)', values: mrr_movement?.expansion ?? [] },
            ]}
            down={[
              { key: 'con', label: 'Contraction', color: 'var(--aeos-warning)', values: mrr_movement?.contraction ?? [] },
              { key: 'churn', label: 'Churn', color: 'var(--aeos-danger)', values: mrr_movement?.churn ?? [] },
            ]}
            net={{ color: 'var(--aeos-text-primary)', values: (mrr_movement?.labels ?? []).map((_, i) => (mrr_movement.new[i] ?? 0) + (mrr_movement.expansion[i] ?? 0) - (mrr_movement.contraction[i] ?? 0) - (mrr_movement.churn[i] ?? 0)) }}
            format={(v) => fmtK(v)}
            height={230}
            ariaLabel="MRR movement by month"
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-new" />New</span>
            <span className="li"><span className="d sc-d-exp" />Expansion</span>
            <span className="li"><span className="d sc-d-con" />Contraction</span>
            <span className="li"><span className="d sc-d-churn" />Churn</span>
            <span className="li"><span className="d d--dot sc-d-net" />Net</span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">MRR trend</h2><div className="pc-panel-h__sub">Plan vs product recurring revenue — 12 months</div></div>
          </div>
          <AreaTrend
            series={[
              { key: 'plan', label: 'Plan MRR', color: 'var(--aeos-primary)', values: mrr_trend?.plan ?? [] },
              { key: 'product', label: 'Product MRR', color: 'var(--aeos-success)', fill: false, values: mrr_trend?.product ?? [] },
            ]}
            labels={(mrr_trend?.labels ?? []).map((l, i) => (i % 2 === 0 ? l : ''))}
            height={230}
            ariaLabel="MRR trend, plan versus product"
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-exp" />Plan <b>{fmtK((mrr_trend?.plan ?? []).at(-1) ?? 0)}</b></span>
            <span className="li"><span className="d sc-d-new" />Product <b>{fmtK((mrr_trend?.product ?? []).at(-1) ?? 0)}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="sc-stack">
            <div>
              <div className="pc-panel-h">
                <div><h2 className="pc-panel-h__title">Health</h2><div className="pc-panel-h__sub">{s.total ?? 0} records — click a status to filter</div></div>
              </div>
              <div className="sc-donut-row">
                <Donut
                  segments={donutStatuses.map(([st, color]) => ({ color, value: s[st] ?? 0 }))}
                  centerValue={`${Math.round(((s.active ?? 0) / total) * 100)}%`}
                  centerLabel="active"
                  size={112}
                />
                <div className="sc-dl">
                  {donutStatuses.map(([st]) => (
                    <button key={st} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === st ? 'all' : st)}>
                      <span className={`d sc-seg-${st}`} />{STATUS_LABEL[st]}<b>{s[st] ?? 0}</b>
                    </button>
                  ))}
                </div>
              </div>
            </div>
            <div>
              <div className="pc-panel-h">
                <div><h2 className="pc-panel-h__title">Retention cohorts</h2><div className="pc-panel-h__sub">% of signup cohort still subscribed</div></div>
              </div>
              <CohortGrid rows={cohorts ?? []} domain={[70, 100]} color="var(--aeos-primary)" />
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* action queues */}
      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Renewals — next 30 days</h2><div className="pc-panel-h__sub">Upcoming charges at their billed amount</div></div>
            <span className="sc-badge sc-badge--ok">{(q.renewals ?? []).length}</span>
          </div>
          {(q.renewals ?? []).length === 0 && <div className="wb-empty">No renewals inside 30 days.</div>}
          {(q.renewals ?? []).slice(0, 4).map((r) => (
            <div key={`${r.kind}-${r.id}`} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label} · {r.billing_cycle}</span></div>
              <span className="sc-qitem__amt">{fmtMoney(r.amount)}</span>
              <span className="sc-qitem__when">{fmtDateShort(r.renews_at)}</span>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('renewals')}>View all renewals →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Trials ending soon</h2><div className="pc-panel-h__sub">Convert or extend before access lapses</div></div>
            <span className={`sc-badge ${(q.trials ?? []).length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{(q.trials ?? []).length}</span>
          </div>
          {(q.trials ?? []).length === 0 && <div className="wb-empty">No running trials.</div>}
          {(q.trials ?? []).slice(0, 4).map((r) => (
            <div key={`${r.kind}-${r.id}`} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label}</span></div>
              <span className="sc-qitem__when sc-qitem__when--soon">{r.days_left}d left</span>
              {r.kind === 'plan' && (
                <>
                  <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => confirmPost(`Convert ${r.tenant}'s trial to paid now?`, `/billing/subscriptions/${r.id}/trial/convert`)}>Convert</button>
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/trial/extend`, { days: 7 })}>+7d</button>
                </>
              )}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('trials')}>View all trials →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Dunning queue</h2><div className="pc-panel-h__sub">Failed payments awaiting recovery</div></div>
            <span className={`sc-badge ${(q.dunning ?? []).length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{fmtMoney(s.dunning_amount)}</span>
          </div>
          {(q.dunning ?? []).length === 0 && <div className="wb-empty">Nothing in dunning — all payments healthy.</div>}
          {(q.dunning ?? []).slice(0, 4).map((r) => (
            <div key={`${r.kind}-${r.id}`} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label}</span></div>
              <span className="sc-qitem__amt">{fmtMoney(r.amount)}</span>
              <span className="sc-qitem__when sc-qitem__when--due">{r.days_overdue}d overdue</span>
              {r.kind === 'plan' && (
                <>
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/retry-charge`)}>Retry</button>
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/remind`)}>Remind</button>
                </>
              )}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('atrisk')}>Open at-risk view →</button></div>
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search tenant, plan or product…" ariaLabel="Search subscriptions" />
          <div className="pc-seg" role="group" aria-label="Subscription kind">
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.kind === 'all'} onClick={() => wb.setFacet('kind', 'all')}>All · {s.total ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.kind === 'plan'} onClick={() => wb.setFacet('kind', 'plan')}>Plan · {s.plan_total ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.kind === 'product'} onClick={() => wb.setFacet('kind', 'product')}>Product · {s.product_total ?? 0}</button>
          </div>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.cycle} onChange={(e) => wb.setFacet('cycle', e.target.value)} aria-label="Billing cycle filter">
            <option value="all">Any cycle</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.mrr} onChange={(e) => wb.setFacet('mrr', e.target.value)} aria-label="MRR filter">
            <option value="all">Any MRR</option>
            <option value="100">≥ $100</option>
            <option value="300">≥ $300</option>
            <option value="800">≥ $800</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Queue payment reminders for ${wb.selection.size} selected subscription(s)?`, '/billing/subscriptions/bulk', { action: 'remind', ids: bulkPayload() })}>Send reminder</button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportSelectedCsv}>Export selected</button>
          <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => {
            const reason = window.prompt(`Cancel ${wb.selection.size} selected subscription(s)?\nReason:`);
            if (reason !== null) post('/billing/subscriptions/bulk', { action: 'cancel', reason: reason || 'Bulk cancellation', ids: bulkPayload() });
          }}>Cancel</button>
        </WbBulkBar>

        <WbTable
          wb={wb}
          columns={columns}
          selectable
          onRowClick={setDrawerRow}
          rowAriaLabel={(r) => `${r.tenant} — ${r.label}, ${STATUS_LABEL[r.status] ?? r.status}`}
          empty={
            <>
              No subscriptions match these filters.
              <br />
              <button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button>
            </>
          }
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawerRow && <DetailDrawer row={rows.find((r) => r.kind === drawerRow.kind && r.id === drawerRow.id) ?? drawerRow} plans={plans} onClose={() => setDrawerRow(null)} onCancel={(r) => { setDrawerRow(null); setCancelSub(r); }} onChangePlan={(r) => { setDrawerRow(null); setChangeSub(r); }} />}
      {cancelSub && <CancelModal sub={cancelSub} onClose={() => setCancelSub(null)} />}
      {changeSub && <ChangePlanModal sub={changeSub} plans={plans} onClose={() => setChangeSub(null)} />}
      {creating && <CreateModal plans={plans} tenants={tenants} products={products} onClose={() => setCreating(false)} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Subscriptions" railTitle="Recurring" rail={<SubscriptionsRail stats={page.props.stats} />}>
    {page}
  </App>
);
