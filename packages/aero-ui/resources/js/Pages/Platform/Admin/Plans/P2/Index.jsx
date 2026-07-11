import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaSpark,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './plans.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  reorder: svg(<><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></>),
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
};
const check = svg(<path d="M20 6 9 17l-5-5" />);

const TIER_LABEL = { free: 'Free', starter: 'Starter', professional: 'Professional', enterprise: 'Enterprise' };
const STATUS_LABEL = { active: 'Active', draft: 'Draft', archived: 'Archived' };
const CUR = { USD: '$', GBP: '£', EUR: '€', BDT: '৳', AUD: 'A$', CAD: 'C$' };
const sym = (c) => CUR[c] ?? '$';
const fmt = (n, c = 'USD') => `${sym(c)}${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`);
const tierGlyph = (t) => (t === 'enterprise' ? '◆' : t === 'professional' ? '▲' : t === 'starter' ? '●' : '○');
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };

const post = (url, data = {}, opts = {}) => router.post(url, data, { preserveScroll: true, ...opts });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };

/* ---------------- rail ---------------- */
function PlansRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Catalog</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Plans</span><b>{s.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active subscribers</span><b>{s.subscribers ?? 0}</b></div>
          <div className="pc-rail__row"><span>Plan MRR</span><b>{fmtK(s.mrr)}</b></div>
          <div className="pc-rail__row"><span>ARR</span><b>{fmtK(s.arr)}</b></div>
          <div className="pc-rail__row"><span>Public</span><b>{s.public ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/subscriptions')}>{Glyph.subs}<span>Subscriptions</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/invoices')}>{Glyph.billing}<span>Invoices</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- editor modal (create / edit) ---------------- */
const POLICIES = ['end_of_cycle', 'immediate', 'negotiated'];
function EditorModal({ plan, onClose }) {
  const isNew = !plan;
  const [features, setFeatures] = useState(plan?.features?.length ? [...plan.features] : ['']);
  const form = useForm({
    name: plan?.name ?? '',
    tier: plan?.tier ?? 'starter',
    description: plan?.description ?? '',
    price_monthly: plan?.price_monthly ?? 0,
    price_annual: plan?.price_annual ?? 0,
    currency: plan?.currency ?? 'USD',
    trial_days: plan?.trial_days ?? 14,
    grace_days: plan?.grace_days ?? 7,
    max_users: plan?.max_users ?? 10,
    max_storage_gb: plan?.max_storage_gb ?? 25,
    ai_enabled: plan?.limits?.max_ai_messages != null,
    ai_model: plan?.limits?.ai_model ?? 'flash',
    ai_messages: plan?.limits?.max_ai_messages ?? 500,
    downgrade_policy: plan?.downgrade_policy ?? 'end_of_cycle',
    status: plan?.status ?? 'active',
    is_public: plan ? !!plan.is_public : true,
    is_featured: plan ? !!plan.is_featured : false,
  });

  const submit = (e) => {
    e.preventDefault();
    const clean = features.map((f) => f.trim()).filter(Boolean);
    form.transform((d) => ({ ...d, features: clean }));
    const opts = { preserveScroll: true, onSuccess: onClose };
    if (isNew) form.post('/plans', opts);
    else form.put(`/plans/${plan.id}`, opts);
  };

  const setF = (i, v) => setFeatures((a) => a.map((x, j) => (j === i ? v : x)));
  const err = (k) => form.errors[k] && <span className="pc-field__err">{form.errors[k]}</span>;

  return (
    <div className="pc-modal-overlay pn-modal-wide" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{isNew ? 'New plan' : `Edit ${plan.name}`}</h2>
        <div className="pc-modal__sub">{isNew ? 'Define pricing, quotas, features and policies.' : `slug: ${plan.slug} · audit-logged`}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pn-grid2">
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-name">Plan name</label>
              <input id="p-name" className="pc-input" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="e.g. Professional" autoFocus />{err('name')}</div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-tier">Tier</label>
              <select id="p-tier" className="pc-input" value={form.data.tier} onChange={(e) => form.setData('tier', e.target.value)}>
                {Object.keys(TIER_LABEL).map((t) => <option key={t} value={t}>{TIER_LABEL[t]}</option>)}
              </select></div>
          </div>
          <div className="pc-field"><label className="pc-field__label" htmlFor="p-desc">Description</label>
            <textarea id="p-desc" className="pc-input" rows={2} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder="Short marketing description" /></div>

          <div className="pn-sectitle">Pricing</div>
          <div className="pn-grid3">
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-m">Monthly price</label>
              <input id="p-m" type="number" step="0.01" min="0" className="pc-input" value={form.data.price_monthly} onChange={(e) => form.setData('price_monthly', e.target.value)} />{err('price_monthly')}</div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-y">Annual price</label>
              <input id="p-y" type="number" step="0.01" min="0" className="pc-input" value={form.data.price_annual} onChange={(e) => form.setData('price_annual', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-cur">Currency</label>
              <select id="p-cur" className="pc-input" value={form.data.currency} onChange={(e) => form.setData('currency', e.target.value)}>
                {['USD', 'EUR', 'GBP', 'BDT'].map((c) => <option key={c} value={c}>{c}</option>)}
              </select></div>
          </div>

          <div className="pn-sectitle">Trial &amp; quotas</div>
          <div className="pn-grid3">
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-trial">Trial days</label>
              <input id="p-trial" type="number" min="0" className="pc-input" value={form.data.trial_days} onChange={(e) => form.setData('trial_days', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-grace">Grace days</label>
              <input id="p-grace" type="number" min="0" className="pc-input" value={form.data.grace_days} onChange={(e) => form.setData('grace_days', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-users">Max users (0 = ∞)</label>
              <input id="p-users" type="number" min="0" className="pc-input" value={form.data.max_users} onChange={(e) => form.setData('max_users', e.target.value)} /></div>
          </div>
          <div className="pn-grid2">
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-store">Storage (GB)</label>
              <input id="p-store" type="number" min="0" className="pc-input" value={form.data.max_storage_gb} onChange={(e) => form.setData('max_storage_gb', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-down">Downgrade policy</label>
              <select id="p-down" className="pc-input" value={form.data.downgrade_policy} onChange={(e) => form.setData('downgrade_policy', e.target.value)}>
                {POLICIES.map((p) => <option key={p} value={p}>{p.replace(/_/g, ' ')}</option>)}
              </select></div>
          </div>

          <div className="pn-sectitle">AI Assistant (Aeon)</div>
          <label className="pn-switch" style={{ marginBottom: form.data.ai_enabled ? 10 : 0 }}>
            <input type="checkbox" checked={form.data.ai_enabled} onChange={(e) => form.setData('ai_enabled', e.target.checked)} /> Include the AI assistant in this plan
          </label>
          {form.data.ai_enabled && (
            <div className="pn-grid2">
              <div className="pc-field"><label className="pc-field__label" htmlFor="p-aimodel">Model tier</label>
                <select id="p-aimodel" className="pc-input" value={form.data.ai_model} onChange={(e) => form.setData('ai_model', e.target.value)}>
                  <option value="flash">Flash — fast, all tiers</option>
                  <option value="pro">Pro — premium model</option>
                  <option value="all">All — every model + BYO key</option>
                </select></div>
              <div className="pc-field"><label className="pc-field__label" htmlFor="p-aimsg">Messages / month (0 = ∞)</label>
                <input id="p-aimsg" type="number" min="0" className="pc-input" value={form.data.ai_messages} onChange={(e) => form.setData('ai_messages', e.target.value)} />{err('ai_messages')}</div>
            </div>
          )}

          <div className="pn-sectitle">Features</div>
          <div className="pn-featedit">
            {features.map((f, i) => (
              <div className="pn-featrow" key={i}>
                <input className="pc-input" value={f} onChange={(e) => setF(i, e.target.value)} placeholder="Feature line" />
                <button type="button" onClick={() => setFeatures((a) => a.filter((_, j) => j !== i))} aria-label="Remove feature">✕</button>
              </div>
            ))}
          </div>
          <button type="button" className="pc-btn pc-btn--sm" style={{ alignSelf: 'flex-start' }} onClick={() => setFeatures((a) => [...a, ''])}>+ Add feature</button>

          <div className="pn-sectitle">Visibility &amp; status</div>
          <div className="pn-grid3">
            <label className="pn-switch"><input type="checkbox" checked={form.data.is_public} onChange={(e) => form.setData('is_public', e.target.checked)} /> Public</label>
            <label className="pn-switch"><input type="checkbox" checked={form.data.is_featured} onChange={(e) => form.setData('is_featured', e.target.checked)} /> Featured</label>
            <div className="pc-field"><label className="pc-field__label" htmlFor="p-status">Status</label>
              <select id="p-status" className="pc-input" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                {['active', 'draft', 'archived'].map((s) => <option key={s} value={s}>{STATUS_LABEL[s]}</option>)}
              </select></div>
          </div>

          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing || !form.data.name.trim()}>{form.processing ? 'Saving…' : (isNew ? 'Create plan' : 'Save changes')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- reorder modal (drag-sortable) ---------------- */
function ReorderModal({ plans, onClose }) {
  const [order, setOrder] = useState(() => [...plans].sort((a, b) => a.sort_order - b.sort_order));
  const [dragI, setDragI] = useState(null);
  const [overI, setOverI] = useState(null);
  const [busy, setBusy] = useState(false);

  const drop = () => {
    if (dragI === null || overI === null || dragI === overI) { setDragI(null); setOverI(null); return; }
    setOrder((a) => { const n = [...a]; const [m] = n.splice(dragI, 1); n.splice(overI, 0, m); return n; });
    setDragI(null); setOverI(null);
  };
  const save = () => {
    setBusy(true);
    post('/plans/reorder', { ids: order.map((p) => p.id) }, { onSuccess: onClose, onError: () => setBusy(false) });
  };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Reorder pricing page</h2>
        <div className="pc-modal__sub">Drag to set the order plans appear on the public pricing page.</div>
        <div className="pn-reorder">
          {order.map((p, i) => (
            <div
              key={p.id}
              className={`pn-rerow${dragI === i ? ' is-drag' : ''}${overI === i && dragI !== i ? ' is-over' : ''}`}
              draggable
              onDragStart={() => setDragI(i)}
              onDragOver={(e) => { e.preventDefault(); setOverI(i); }}
              onDragEnd={drop}
              onDrop={drop}
            >
              <span className="pn-rerow__grip" aria-hidden="true">⠿</span>
              <span className={`pn-tier pn-tier--${p.tier}`}>{i + 1}</span>
              <span className="pn-rerow__n">{p.name}</span>
              <span className="pn-rerow__price">{p.price_monthly > 0 ? `${sym(p.currency)}${p.price_monthly}/mo` : 'Free'}</span>
            </div>
          ))}
        </div>
        <div className="pc-modal__actions">
          <span className="pc-spacer" />
          <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={save} disabled={busy}>{busy ? 'Saving…' : 'Save order'}</button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ plan, initialTab, onClose, actions }) {
  const [tab, setTab] = useState(initialTab || 'overview');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  const ctx = useCtxMenu();

  useEffect(() => {
    setTab(initialTab || 'overview'); setDetail(null); setFailed(false);
    if (!plan) return undefined;
    const ac = new AbortController();
    fetch(`/plans/${plan.id}/detail`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [plan?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!plan) return null;
  const c = plan.currency;
  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'subs', label: `Subscribers · ${plan.active_subs}` },
    { id: 'revenue', label: 'Revenue' },
    { id: 'activity', label: 'Activity' },
  ];
  const moreItems = [
    ...(actions.canEdit ? [{ label: 'Edit plan', onClick: () => actions.edit(plan) }] : []),
    ...(actions.canClone ? [{ label: 'Duplicate', onClick: () => actions.clone(plan) }] : []),
    ...(actions.canEdit ? [{ label: plan.is_featured ? 'Remove featured' : 'Mark featured', onClick: () => actions.feature(plan) }] : []),
    ...(actions.canArchive ? ['sep', { label: 'Archive', onClick: () => actions.archive(plan) }] : []),
    ...(actions.canDelete ? [{ label: 'Delete', danger: true, onClick: () => actions.remove(plan) }] : []),
  ];

  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Plan detail — ${plan.name}`} tabs={tabs} activeTab={tab} onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{tierGlyph(plan.tier)}</div>
            <div>
              <div className="sc-dr-title">{plan.name} {plan.is_featured && <span className="pn-star" title="Featured">★</span>}</div>
              <div className="sc-dr-code">{plan.slug} · {TIER_LABEL[plan.tier]}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Price</div><div className="v">{plan.price_monthly > 0 ? `${fmt(plan.price_monthly, c)}/mo` : 'Free'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Subscribers</div><div className="v">{plan.active_subs}{plan.trial_subs ? ` +${plan.trial_subs}t` : ''}</div></div>
            <div className="sc-dr-kpi"><div className="l">MRR</div><div className="v">{fmt(plan.mrr, c)}</div></div>
          </div>
          <div className="sc-dr-acts">
            {actions.canEdit && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.edit(plan)}>Edit</button>}
            {actions.canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.publish(plan)}>{plan.is_public ? 'Unpublish' : 'Publish'}</button>}
            {moreItems.length > 0 && <button type="button" className="pc-btn pc-btn--sm" onClick={(e) => ctx.open(e.currentTarget, moreItems)}>More ▾</button>}
          </div>
        </>
      }
    >
      {tab === 'overview' && (
        <div>
          <div className="pc-drow"><span className="pc-drow__k">Monthly</span><span className="pc-drow__v">{plan.price_monthly > 0 ? fmt(plan.price_monthly, c) : 'Free'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Annual</span><span className="pc-drow__v">{plan.price_annual > 0 ? `${fmt(plan.price_annual, c)}${plan.price_annual < plan.price_monthly * 12 ? ` · ${Math.round((1 - plan.price_annual / (plan.price_monthly * 12)) * 100)}% off` : ''}` : '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Trial period</span><span className="pc-drow__v">{plan.trial_days} days</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Grace period</span><span className="pc-drow__v">{plan.grace_days} days</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Max users</span><span className="pc-drow__v">{plan.max_users === 0 ? 'Unlimited' : plan.max_users}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Storage</span><span className="pc-drow__v">{plan.max_storage_gb} GB</span></div>
          {plan.downgrade_policy && <div className="pc-drow"><span className="pc-drow__k">Downgrade policy</span><span className="pc-drow__v">{String(plan.downgrade_policy).replace(/_/g, ' ')}</span></div>}
          <div className="pc-drow"><span className="pc-drow__k">Visibility</span><span className="pc-drow__v">{plan.is_public ? 'Public' : 'Private'}</span></div>
          {plan.features?.length > 0 && (
            <>
              <div className="sc-dr-sec">Features ({plan.features.length})</div>
              {plan.features.map((f, i) => <div className="pn-feat" key={i}>{check}{f}</div>)}
            </>
          )}
        </div>
      )}
      {tab === 'subs' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load subscribers.' : 'Loading…'}</div>
          : detail.subscribers.length === 0 ? <div className="wb-empty">No subscribers on this plan yet.</div>
            : (
              <table className="sc-minit"><tbody>
                {detail.subscribers.map((s, i) => (
                  <tr key={i}>
                    <td><div className="pc-mname">{s.tenant}</div><div className="sc-kind">{s.status}{s.cycle ? ` · ${s.cycle}` : ''}</div></td>
                    <td className="pc-r">{fmt(s.amount, s.currency)}</td>
                  </tr>
                ))}
              </tbody></table>
            )
      )}
      {tab === 'revenue' && (
        detail == null ? <div className="wb-empty">Loading…</div>
          : (
            <>
              <div className="sc-dr-kpis">
                <div className="sc-dr-kpi"><div className="l">MRR</div><div className="v">{fmt(detail.revenue.mrr, c)}</div></div>
                <div className="sc-dr-kpi"><div className="l">ARR</div><div className="v">{fmt(detail.revenue.arr, c)}</div></div>
                <div className="sc-dr-kpi"><div className="l">ARPU</div><div className="v">{detail.revenue.active ? fmt(detail.revenue.arpu, c) : '—'}</div></div>
              </div>
              <div className="pc-drow"><span className="pc-drow__k">Active subscribers</span><span className="pc-drow__v">{detail.revenue.active}</span></div>
              <div className="pc-drow"><span className="pc-drow__k">In trial</span><span className="pc-drow__v">{detail.revenue.trial}</span></div>
              <div className="wb-empty" style={{ marginTop: 'var(--aeos-space-3)' }}>Revenue derives from the live subscriptions ledger (active + trialing, monthly-normalized).</div>
            </>
          )
      )}
      {tab === 'activity' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
          : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet — plan changes appear here as they happen.</div>
            : (
              <ul className="sc-tl">
                {detail.activity.map((a, i) => (
                  <li key={i}>{a.detail || a.event}<span className="when">{fmtDate(a.at)}{a.actor ? ` · ${a.actor}` : ''}</span></li>
                ))}
              </ul>
            )
      )}
      {ctx.element}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Index({ stats, plans, ladder, sparks }) {
  const toast = useToast();
  const s = stats ?? {};
  const sp = sparks ?? {};
  const list = useMemo(() => plans ?? [], [plans]);

  const canCreate = useHRMAC('plan-management.plan-list.create');
  const canEdit = useHRMAC('plan-management.plan-list.edit');
  const canDelete = useHRMAC('plan-management.plan-list.delete');
  const canArchive = useHRMAC('plan-management.plan-list.archive');
  const canClone = useHRMAC('plan-management.plan-list.clone');

  const [editorPlan, setEditorPlan] = useState(undefined); // undefined = closed, null = new
  const [reordering, setReordering] = useState(false);
  const [drawer, setDrawer] = useState(null); // { plan, tab }
  const ctx = useCtxMenu();

  const clone = (p) => post(`/plans/${p.id}/clone`, {}, { onSuccess: () => toast.success(`${p.name} duplicated.`) });
  const publish = (p) => post(`/plans/${p.id}/toggle-public`, { public: !p.is_public }, { onSuccess: () => toast.success(p.is_public ? `${p.name} unpublished.` : `${p.name} published.`) });
  const feature = (p) => post(`/plans/${p.id}/toggle-featured`, { featured: !p.is_featured }, { onSuccess: () => toast.success(p.is_featured ? `${p.name} unfeatured.` : `${p.name} featured.`) });
  const archive = (p) => confirmPost(`Archive ${p.name}? It will be hidden from the pricing page.`, `/plans/${p.id}/archive`);
  const remove = (p) => {
    if (p.active_subs > 0) { toast.error(`Cannot delete ${p.name} — ${p.active_subs} active subscriber(s).`); return; }
    if (!window.confirm(`Delete ${p.name}? This cannot be undone.`)) return;
    router.delete(`/plans/${p.id}`, { preserveScroll: true, onSuccess: () => toast.success(`${p.name} deleted.`), onError: () => toast.error('Could not delete plan.') });
  };
  const move = (p, dir) => {
    const ordered = [...list].sort((a, b) => a.sort_order - b.sort_order);
    const i = ordered.findIndex((x) => x.id === p.id);
    const j = i + dir;
    if (j < 0 || j >= ordered.length) return;
    [ordered[i], ordered[j]] = [ordered[j], ordered[i]];
    post('/plans/reorder', { ids: ordered.map((x) => x.id) }, { onSuccess: () => toast.success('Order updated.') });
  };
  const actions = { canEdit, canDelete, canArchive, canClone, edit: setEditorPlan, clone, publish, feature, archive, remove };

  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.slug} ${r.tier}`,
    views: [
      { id: 'all', label: 'All plans' },
      { id: 'active', label: 'Active', test: (r) => r.status === 'active' },
      { id: 'public', label: 'Public', test: (r) => r.is_public },
      { id: 'featured', label: 'Featured', test: (r) => r.is_featured },
      { id: 'free', label: 'Free', test: (r) => r.price_monthly <= 0 },
      { id: 'draft', label: 'Draft', test: (r) => r.status === 'draft' },
    ],
    facets: {
      tier: { value: 'all', test: (r, v) => r.tier === v },
      status: { value: 'all', test: (r, v) => r.status === v },
      vis: { value: 'all', test: (r, v) => (v === 'public' ? r.is_public : !r.is_public) },
    },
    sortKey: 'sort_order',
    sortVal: (r, k) => (['price_monthly', 'active_subs', 'mrr', 'sort_order'].includes(k) ? (r[k] ?? 0) : String(r[k] ?? '')),
    perPage: 20,
    storageKey: 'platform.plans',
  });

  /* KPI band */
  const kpis = [
    { label: 'Total plans', value: s.total ?? 0, delta: `${s.active ?? 0} active · ${s.draft ?? 0} draft`, spark: sp.subscribers, color: 'var(--aeos-primary)' },
    { label: 'Active subscribers', value: s.subscribers ?? 0, delta: 'paying now', up: true, spark: sp.subscribers, color: 'var(--aeos-success)' },
    { label: 'Plan MRR', value: fmtK(s.mrr), delta: 'active + trialing', up: true, spark: sp.mrr, color: 'var(--aeos-success)' },
    { label: 'Trials in flight', value: s.trials ?? 0, delta: 'converting', color: 'var(--aeos-warning)' },
    { label: 'Public plans', value: s.public ?? 0, delta: `${s.public ?? 0} public · ${s.private ?? 0} private`, color: 'var(--aeos-primary)' },
    { label: 'ARR', value: fmtK(s.arr), delta: 'annual run-rate', up: true, spark: sp.mrr, color: 'var(--aeos-success)' },
  ];

  /* charts */
  const lad = ladder ?? [];
  const ladMax = Math.max(1, ...lad.map((l) => l.price));
  const subsMix = useMemo(() => [...list].sort((a, b) => (b.active_subs + b.trial_subs) - (a.active_subs + a.trial_subs)), [list]);
  const subsMax = Math.max(1, ...subsMix.map((p) => p.active_subs + p.trial_subs));
  const mrrMix = useMemo(() => [...list].sort((a, b) => b.mrr - a.mrr), [list]);
  const mrrMax = Math.max(1, ...mrrMix.map((p) => p.mrr));

  const topRev = mrrMix.filter((p) => p.mrr > 0).slice(0, 4);
  const trialQ = useMemo(() => list.filter((p) => p.trial_subs > 0).sort((a, b) => b.trial_subs - a.trial_subs), [list]);
  const attnQ = useMemo(() => list.filter((p) => p.active_subs === 0 && p.trial_subs === 0), [list]);

  const rowMenu = (p) => [
    ...(canEdit ? [{ label: 'Edit plan', onClick: () => setEditorPlan(p) }] : []),
    { label: 'View subscribers', onClick: () => setDrawer({ plan: p, tab: 'subs' }) },
    ...(canClone ? [{ label: 'Duplicate', onClick: () => clone(p) }] : []),
    ...(canEdit ? [{ label: p.is_public ? 'Unpublish' : 'Publish', onClick: () => publish(p) }] : []),
    ...(canEdit ? [{ label: p.is_featured ? 'Remove featured' : 'Mark featured', onClick: () => feature(p) }] : []),
    ...(canEdit ? ['sep', { label: 'Move up', onClick: () => move(p, -1) }, { label: 'Move down', onClick: () => move(p, 1) }] : []),
    ...(canArchive ? [{ label: 'Archive', onClick: () => archive(p) }] : []),
    ...(canDelete ? [{ label: p.active_subs > 0 ? 'Delete (has subscribers)' : 'Delete', danger: true, onClick: () => remove(p) }] : []),
  ];

  const columns = [
    {
      key: 'name', label: 'Plan', sortable: true,
      render: (p) => (
        <div className="pc-mrow">
          <div className="pn-av">{tierGlyph(p.tier)}</div>
          <div><div className="pc-mname">{p.name} {p.is_featured && <span className="pn-star" title="Featured">★</span>}</div><div className="sc-kind">{p.slug}</div></div>
        </div>
      ),
    },
    { key: 'tier', label: 'Tier', hideSm: true, render: (p) => <span className={`pn-tier pn-tier--${p.tier}`}>{TIER_LABEL[p.tier]}</span> },
    { key: 'price_monthly', label: 'Monthly', align: 'r', sortable: true, render: (p) => (p.price_monthly > 0 ? <span className="pc-price">{fmt(p.price_monthly, p.currency)}<small>/mo</small></span> : <span className="pc-free">Free</span>) },
    { key: 'price_annual', label: 'Annual', align: 'r', hideSm: true, render: (p) => (p.price_annual > 0 ? <span className="sc-kind">{fmt(p.price_annual, p.currency)}/yr</span> : <span className="pc-free">—</span>) },
    { key: 'active_subs', label: 'Subscribers', align: 'r', sortable: true, render: (p) => <span><b>{p.active_subs}</b>{p.trial_subs ? <span className="sc-kind"> +{p.trial_subs}t</span> : ''}</span> },
    { key: 'mrr', label: 'MRR', align: 'r', sortable: true, render: (p) => (p.mrr > 0 ? <span className="pn-mrr">{fmt(p.mrr, p.currency)}</span> : <span className="pc-free">—</span>) },
    { key: 'status', label: 'Status', render: (p) => <span className={`pc-chip pc-chip--${p.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[p.status] ?? p.status}</span> },
    { key: 'is_public', label: 'Visibility', hideSm: true, render: (p) => <span className="pn-vis">{p.is_public ? '🌐 Public' : '🔒 Private'}</span> },
    {
      key: 'actions', label: '', align: 'r', width: 44,
      render: (p) => <button type="button" className="wb-kebab" aria-label={`Actions for ${p.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(p))}>⋯</button>,
    },
  ];

  const bulkIds = () => wb.selectedRows.map((r) => r.id);
  const exportSelectedCsv = () => {
    const header = 'name,slug,tier,status,visibility,featured,monthly,annual,currency,subscribers,mrr';
    const lines = wb.selectedRows.map((r) => [r.name, r.slug, r.tier, r.status, r.is_public ? 'public' : 'private', r.is_featured ? 'yes' : 'no', r.price_monthly, r.price_annual, r.currency, r.active_subs, r.mrr].map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `plans-selected-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc pn">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Revenue &amp; Catalog</div>
          <h1 className="pc-title">Plans</h1>
          <div className="pc-sub">The pricing catalog — every plan, its tier, price ladder, subscribers and revenue in one operating view, with full authoring, publish, feature, reorder, archive and delete.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [
            { label: 'Export CSV — all plans', onClick: () => { window.location.href = '/plans/export'; } },
            { label: 'Print this view', onClick: () => window.print() },
          ])}>{Glyph.export}<span>Export</span></button>
          {canEdit && <button type="button" className="pc-btn" onClick={() => setReordering(true)}>{Glyph.reorder}<span>Reorder</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setEditorPlan(null)}>{Glyph.plus}<span>New plan</span></button>}
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color ?? 'var(--aeos-primary)'} /></div>}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Price ladder</h2><div className="pc-panel-h__sub">Monthly price by tier — click to filter</div></div><span className="sc-badge sc-badge--ok">{s.total ?? 0} plans</span></div>
          <div className="pn-ladder">
            {lad.map((l) => (
              <button key={l.slug} type="button" className={`pn-lad${wb.facetValues.tier === l.tier ? ' is-on' : ''}`} title={l.name}
                onClick={() => wb.setFacet('tier', wb.facetValues.tier === l.tier ? 'all' : l.tier)}>
                <div className="pn-lad__bar" style={{ height: `${l.price === 0 ? 4 : Math.max((l.price / ladMax) * 128, 8)}px` }}><span className="pn-lad__price">{l.price === 0 ? 'Free' : `$${l.price}`}</span></div>
                <div className="pn-lad__cap">{l.name}<b>{l.subs} subs</b></div>
              </button>
            ))}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Subscribers by plan</h2><div className="pc-panel-h__sub">Active + trialing — click to filter</div></div></div>
          <div className="pn-mix">
            {subsMix.map((p, i) => (
              <button key={p.id} type="button" className={`pn-mixrow${wb.q === p.name ? ' is-on' : ''}`} onClick={() => wb.setQ(wb.q === p.name ? '' : p.name)}>
                <span className="pn-mixrow__cap">{p.name}</span>
                <span className="pn-mixrow__track"><span className={`pn-mixrow__fill pn-mix-${i % 5}`} style={{ width: `${Math.max((p.active_subs + p.trial_subs) / subsMax * 100, 3)}%` }} /></span>
                <span className="pn-mixrow__n"><b>{p.active_subs + p.trial_subs}</b> subs</span>
              </button>
            ))}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">MRR contribution</h2><div className="pc-panel-h__sub">Monthly recurring revenue per plan</div></div><span className="sc-badge sc-badge--ok">{fmtK(s.mrr)}</span></div>
          <div className="pn-mix">
            {mrrMix.map((p, i) => (
              <button key={p.id} type="button" className={`pn-mixrow${wb.q === p.name ? ' is-on' : ''}`} onClick={() => wb.setQ(wb.q === p.name ? '' : p.name)}>
                <span className="pn-mixrow__cap">{p.name}</span>
                <span className="pn-mixrow__track"><span className={`pn-mixrow__fill pn-mix-${i % 5}`} style={{ width: `${Math.max(p.mrr / mrrMax * 100, 2)}%` }} /></span>
                <span className="pn-mixrow__n"><b>{fmt(p.mrr, p.currency)}</b></span>
              </button>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* queues */}
      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Top by revenue</h2><div className="pc-panel-h__sub">Biggest MRR contributors</div></div></div>
          {topRev.length === 0 && <div className="wb-empty">No paying subscribers yet.</div>}
          {topRev.map((p) => (
            <div key={p.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{p.name}</b><span>{TIER_LABEL[p.tier]} · {p.active_subs} subs</span></div>
              <span className="sc-qitem__amt">{fmt(p.mrr, p.currency)}</span>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDrawer({ plan: p, tab: 'revenue' })}>Open</button>
            </div>
          ))}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Trials converting</h2><div className="pc-panel-h__sub">Plans with live trials</div></div><span className={`sc-badge ${s.trials ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{s.trials ?? 0}</span></div>
          {trialQ.length === 0 && <div className="wb-empty">No plans with active trials.</div>}
          {trialQ.map((p) => (
            <div key={p.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{p.name}</b><span>{p.trial_days}-day trial</span></div>
              <span className="sc-qitem__when sc-qitem__when--soon">{p.trial_subs} trialing</span>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDrawer({ plan: p, tab: 'subs' })}>View</button>
            </div>
          ))}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Needs attention</h2><div className="pc-panel-h__sub">No subscribers or draft</div></div><span className={`sc-badge ${attnQ.length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{attnQ.length}</span></div>
          {attnQ.length === 0 && <div className="wb-empty">Every plan has subscribers.</div>}
          {attnQ.map((p) => (
            <div key={p.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{p.name}</b><span>{p.price_monthly > 0 ? fmt(p.price_monthly, p.currency) + '/mo' : 'Free'}</span></div>
              <span className="sc-badge sc-badge--warn">0 subs</span>
              {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setEditorPlan(p)}>Edit</button>}
            </div>
          ))}
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search plan name or slug…" ariaLabel="Search plans" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.tier} onChange={(e) => wb.setFacet('tier', e.target.value)} aria-label="Tier filter">
            <option value="all">All tiers</option>
            {Object.keys(TIER_LABEL).map((t) => <option key={t} value={t}>{TIER_LABEL[t]}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {['active', 'draft', 'archived'].map((k) => <option key={k} value={k}>{STATUS_LABEL[k]}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.vis} onChange={(e) => wb.setFacet('vis', e.target.value)} aria-label="Visibility filter">
            <option value="all">Public &amp; private</option>
            <option value="public">Public</option>
            <option value="private">Private</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.selectedRows.forEach((r) => !r.is_public && publish(r))}>Publish</button>}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.selectedRows.forEach((r) => r.is_public && publish(r))}>Unpublish</button>}
          {canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.selectedRows.forEach((r) => !r.is_featured && feature(r))}>Feature</button>}
          {canArchive && <button type="button" className="pc-btn pc-btn--sm" onClick={() => { if (window.confirm(`Archive ${wb.selection.size} selected plan(s)?`)) wb.selectedRows.forEach((r) => post(`/plans/${r.id}/archive`)); }}>Archive</button>}
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportSelectedCsv}>Export selected</button>
        </WbBulkBar>

        <WbTable wb={wb} columns={columns} selectable onRowClick={(p) => setDrawer({ plan: p, tab: 'overview' })}
          rowAriaLabel={(p) => `${p.name}, ${TIER_LABEL[p.tier]}, ${STATUS_LABEL[p.status] ?? p.status}`}
          empty={<>No plans match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>}
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <DetailDrawer plan={list.find((p) => p.id === drawer.plan.id) ?? drawer.plan} initialTab={drawer.tab} onClose={() => setDrawer(null)}
        actions={{ ...actions, edit: (p) => { setDrawer(null); setEditorPlan(p); }, archive: (p) => { setDrawer(null); archive(p); }, remove: (p) => { setDrawer(null); remove(p); } }} />}
      {editorPlan !== undefined && <EditorModal plan={editorPlan} onClose={() => setEditorPlan(undefined)} />}
      {reordering && <ReorderModal plans={list} onClose={() => setReordering(false)} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Plans" railTitle="Catalog" rail={<PlansRail stats={page.props.stats} />}>
    {page}
  </App>
);
