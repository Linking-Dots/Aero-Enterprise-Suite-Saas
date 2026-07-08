import { useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import '../../Products/products.css';
import './subscriptions.css';

const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  plans: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  tenants: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
};

const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const STATUS_LABEL = { active: 'Active', trialing: 'Trialing', past_due: 'Past due', incomplete: 'Incomplete', cancelled: 'Cancelled', expired: 'Expired' };
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };

/* ---------------- rail ---------------- */
function SubscriptionsRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Recurring</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>MRR</span><b>{fmtMoney(s.mrr)}</b></div>
          <div className="pc-rail__row"><span>Active</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Trialing</span><b>{s.trialing ?? 0}</b></div>
          <div className="pc-rail__row"><span>At risk</span><b>{(s.past_due ?? 0) + (s.incomplete ?? 0)}</b></div>
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

/* ---------------- cancel modal (plan) ---------------- */
function CancelModal({ sub, onClose }) {
  const form = useForm({ reason: '' });
  const submit = (e) => { e.preventDefault(); form.post(`/billing/subscriptions/${sub.id}/cancel`, { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Cancel subscription</h2>
        <div className="pc-modal__sub">{sub.tenant} · {sub.label}. Access ends per the billing terms.</div>
        <form className="pc-form" onSubmit={submit}>
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

/* ---------------- change-plan modal ---------------- */
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

/* ---------------- page ---------------- */
export default function Index({ stats, plan_subscriptions, product_subscriptions, plans }) {
  const s = stats ?? {};
  const planRows = plan_subscriptions ?? [];
  const productRows = product_subscriptions ?? [];
  const [view, setView] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [cancelSub, setCancelSub] = useState(null);
  const [changeSub, setChangeSub] = useState(null);

  const rows = useMemo(() => {
    let base = view === 'plan' ? planRows : view === 'product' ? productRows : [...planRows, ...productRows];
    if (statusFilter !== 'all') base = base.filter((r) => r.status === statusFilter);
    return base;
  }, [view, statusFilter, planRows, productRows]);

  const activePlan = planRows.filter((r) => r.status === 'active').length;
  const activeProduct = productRows.filter((r) => r.status === 'active').length;
  const atRisk = (s.past_due ?? 0) + (s.incomplete ?? 0);

  const kpis = [
    { label: 'Active subscriptions', value: s.active ?? 0, delta: `${activePlan} plan · ${activeProduct} product`, up: true },
    { label: 'Monthly recurring', value: fmtMoney(s.mrr), delta: `plan ${fmtMoney(s.plan_mrr)} · product ${fmtMoney(s.product_mrr)}`, up: true },
    { label: 'In trial', value: s.trialing ?? 0, delta: 'converting' },
    { label: 'At risk', value: atRisk, delta: `${s.past_due ?? 0} past-due · ${s.incomplete ?? 0} incomplete`, up: atRisk === 0 },
  ];

  const total = Math.max(1, s.total ?? 1);
  const distSegments = ['active', 'trialing', 'past_due', 'incomplete', 'cancelled'];

  const reactivate = (r) => { if (window.confirm(`Reactivate ${r.tenant}'s ${r.label} subscription?`)) router.post(`/billing/subscriptions/${r.id}/reactivate`, {}, { preserveScroll: true }); };
  const cancelProduct = (r) => { if (window.confirm(`Cancel ${r.tenant}'s ${r.label} product subscription?`)) router.post(`/billing/subscriptions/product/${r.id}/cancel`, {}, { preserveScroll: true }); };

  const actionsFor = (r) => {
    if (r.kind === 'product') {
      return ['active', 'trialing'].includes(r.status)
        ? [<button key="c" type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => cancelProduct(r)}>Cancel</button>]
        : [];
    }
    if (r.status === 'cancelled') {
      return [<button key="r" type="button" className="pc-btn pc-btn--sm" onClick={() => reactivate(r)}>Reactivate</button>];
    }
    const acts = [];
    if (['active', 'trialing', 'past_due'].includes(r.status)) {
      acts.push(<button key="p" type="button" className="pc-btn pc-btn--sm" onClick={() => setChangeSub(r)}>Change plan</button>);
    }
    acts.push(<button key="c" type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => setCancelSub(r)}>Cancel</button>);
    return acts;
  };

  return (
    <div className="pc">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Revenue operations</div>
          <h1 className="pc-title">Subscriptions</h1>
          <div className="pc-sub">Every plan and product subscription across the platform — status, revenue, trials and churn risk in one operating view.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => router.visit('/plans')}>{Glyph.plans}<span>Plans</span></button>
        </div>
      </div>

      <div className="pc-kpis sc-kpis4">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* health */}
      <Card>
        <CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Subscription health</h2><div className="pc-panel-h__sub">{s.total ?? 0} records · plan + product</div></div>
          </div>
          <div className="sc-dist">
            {distSegments.map((st) => {
              const n = s[st] ?? 0;
              return n > 0 ? <span key={st} className={`sc-seg-${st}`} style={{ width: `${(n / total) * 100}%` }} /> : null;
            })}
          </div>
          <div className="sc-legend">
            {distSegments.map((st) => (
              <span key={st} className="li" onClick={() => setStatusFilter(statusFilter === st ? 'all' : st)}>
                <span className={`d sc-seg-${st}`} />{STATUS_LABEL[st]} <b>{s[st] ?? 0}</b>
              </span>
            ))}
          </div>
        </CardBody>
      </Card>

      {/* table */}
      <Card>
        <CardBody>
          <div className="pc-panel-h">
            <div className="pc-seg">
              <button type="button" className="pc-seg__b" aria-pressed={view === 'plan'} onClick={() => setView('plan')}>Plan · {s.plan_total ?? 0}</button>
              <button type="button" className="pc-seg__b" aria-pressed={view === 'product'} onClick={() => setView('product')}>Product · {s.product_total ?? 0}</button>
              <button type="button" className="pc-seg__b" aria-pressed={view === 'all'} onClick={() => setView('all')}>All · {s.total ?? 0}</button>
            </div>
            <select className="pc-input sc-statusfilter" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <option value="all">All statuses</option>
              {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
            </select>
          </div>
          <div className="pc-tablewrap">
            <table className="pc-table">
              <thead>
                <tr>
                  <th>Tenant</th>
                  <th className="pc-hide-sm">Plan / Product</th>
                  <th>Status</th>
                  <th className="pc-r">MRR</th>
                  <th className="pc-hide-sm pc-r">Since</th>
                  <th className="pc-r">Actions</th>
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 && <tr><td colSpan={6}><div className="pc-sub ent-empty">No subscriptions match this filter.</div></td></tr>}
                {rows.map((r) => (
                  <tr key={`${r.kind}-${r.id}`}>
                    <td>
                      <div className="pc-mrow">
                        <div className="sc-av">{initials(r.tenant)}</div>
                        <div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.kind}</div></div>
                      </div>
                    </td>
                    <td className="pc-hide-sm"><span className="pc-modtag">{r.label}</span></td>
                    <td><span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span></td>
                    <td className="pc-r pc-price">{fmtMoney(r.amount)}<small>/mo</small></td>
                    <td className="pc-hide-sm pc-r sc-kind">{fmtDate(r.since)}</td>
                    <td className="pc-r"><div className="sc-actions">{actionsFor(r)}</div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardBody>
      </Card>

      {cancelSub && <CancelModal sub={cancelSub} onClose={() => setCancelSub(null)} />}
      {changeSub && <ChangePlanModal sub={changeSub} plans={plans} onClose={() => setChangeSub(null)} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Subscriptions" railTitle="Recurring" rail={<SubscriptionsRail stats={page.props.stats} />}>
    {page}
  </App>
);
