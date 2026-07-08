import { useEffect, useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './tenants.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};

const STATUS_LABEL = {
  active: 'Active', trial: 'Trial', pending: 'Pending', provisioning: 'Provisioning',
  suspended: 'Suspended', failed: 'Failed', archived: 'Archived', cancelled: 'Cancelled', frozen: 'Frozen',
};
const CUR = { USD: '$', GBP: '£', EUR: '€', BDT: '৳', AUD: 'A$', CAD: 'C$' };
const sym = (c) => CUR[c] ?? '$';
const fmtMoney = (n, c = 'USD') => `${sym(c)}${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`);
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; }
};
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};

const post = (url, data = {}, opts = {}) => router.post(url, data, { preserveScroll: true, ...opts });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };
const xsrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');

/* ---------------- rail ---------------- */
function TenantsRail({ stats }) {
  const s = stats ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Portfolio</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Total tenants</span><b>{s.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Recurring revenue</span><b>{fmtK(s.mrr)}</b></div>
          <div className="pc-rail__row"><span>Outstanding</span><b>{fmtMoney(s.outstanding)}</b></div>
          <div className="pc-rail__row"><span>Needs attention</span><b>{(s.suspended ?? 0) + (s.failed ?? 0) + (s.pending ?? 0)}</b></div>
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

/* ---------------- modals ---------------- */
function SuspendModal({ tenant, onClose }) {
  const form = useForm({ reason: '' });
  const submit = (e) => { e.preventDefault(); form.post(`/tenants/${tenant.id}/suspend`, { preserveScroll: true, onSuccess: onClose }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Suspend {tenant.name}</h2>
        <div className="pc-modal__sub">Users will be blocked from signing in until the tenant is reactivated. The reason is recorded on the audit trail.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="s-reason">Reason</label>
            <textarea id="s-reason" className="pc-input" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="e.g. Non-payment — 2 cycles overdue" autoFocus />
            {form.errors.reason && <span className="pc-field__err">{form.errors.reason}</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={form.processing || !form.data.reason.trim()}>{form.processing ? 'Suspending…' : 'Suspend tenant'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function ForgetModal({ tenant, onClose, onDone }) {
  const [reason, setReason] = useState('');
  const [confirmName, setConfirmName] = useState('');
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const nameOk = confirmName.trim() === tenant.name;
  const reasonOk = reason.trim().length >= 10;

  const submit = (e) => {
    e.preventDefault();
    if (!nameOk || !reasonOk) return;
    setBusy(true); setErr(null);
    fetch(`/tenants/${tenant.id}/forget`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
      body: JSON.stringify({ reason, confirm: true }),
    })
      .then((r) => (r.ok ? r.json() : r.json().then((j) => Promise.reject(new Error(j.message || `HTTP ${r.status}`)))))
      .then((j) => { onDone(j.message || 'Tenant permanently purged.'); })
      .catch((e2) => { setErr(e2.message); setBusy(false); });
  };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Forget {tenant.name}</h2>
        <div className="pc-modal__sub">GDPR right-to-be-forgotten (Audit D7).</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="tn-danger-note">
            This <b>permanently purges</b> the tenant database and record — it bypasses the 30-day soft-delete window and <b>cannot be undone</b>.
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="f-reason">Reason (audit requirement, min 10 chars)</label>
            <textarea id="f-reason" className="pc-input" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Why is this tenant being forgotten?" />
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="f-name">Type <span className="tn-confirm-code">{tenant.name}</span> to confirm</label>
            <input id="f-name" className="pc-input" value={confirmName} onChange={(e) => setConfirmName(e.target.value)} placeholder={tenant.name} autoComplete="off" />
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--danger" disabled={busy || !nameOk || !reasonOk}>{busy ? 'Purging…' : 'Permanently forget'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ row, onClose, actions }) {
  const [tab, setTab] = useState('overview');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  const ctx = useCtxMenu();

  useEffect(() => {
    setTab('overview'); setDetail(null); setFailed(false);
    if (!row) return undefined;
    const ac = new AbortController();
    fetch(`/tenants/${row.id}/detail`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
      .then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [row?.id]); // eslint-disable-line react-hooks/exhaustive-deps

  if (!row) return null;
  const isSuspended = row.status === 'suspended' || row.status === 'frozen';
  const c = row.currency;

  const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'billing', label: 'Billing' },
    { id: 'activity', label: `Activity${detail ? ` · ${detail.activity.length}` : ''}` },
  ];

  const moreItems = [
    ...(actions.canView ? [{ label: 'Open full profile', onClick: () => router.visit(`/tenants/${row.id}`) }] : []),
    ...(actions.canArchive && !isSuspended ? [{ label: 'Archive', onClick: () => actions.archive(row) }] : []),
    ...(actions.canForget ? ['sep', { label: 'Forget (GDPR)…', danger: true, onClick: () => actions.forget(row) }] : []),
  ];

  return (
    <WbDrawer
      open
      onClose={onClose}
      ariaLabel={`Tenant detail — ${row.name}`}
      tabs={tabs}
      activeTab={tab}
      onTab={setTab}
      head={
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(row.name)}</div>
            <div>
              <div className="sc-dr-title">{row.name}</div>
              <div className="sc-dr-code">{row.domain ?? row.subdomain ?? row.id}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{STATUS_LABEL[row.status] ?? row.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">MRR</div><div className="v">{row.mrr == null ? '—' : fmtMoney(row.mrr, c)}</div></div>
            <div className="sc-dr-kpi"><div className="l">Outstanding</div><div className="v">{row.outstanding > 0 ? fmtMoney(row.outstanding, c) : '—'}</div></div>
          </div>
          <div className="sc-dr-acts">
            {isSuspended
              ? actions.canActivate && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.activate(row)}>Reactivate</button>
              : actions.canImpersonate && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.impersonate(row)}>Impersonate</button>}
            {!isSuspended && row.status !== 'archived' && actions.canSuspend && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.suspend(row)}>Suspend</button>}
            {moreItems.length > 0 && <button type="button" className="pc-btn pc-btn--sm" onClick={(e) => ctx.open(e.currentTarget, moreItems)}>More ▾</button>}
          </div>
        </>
      }
    >
      {tab === 'overview' && (
        <div>
          <div className="pc-drow"><span className="pc-drow__k">Tenant ID</span><span className="pc-drow__v" style={{ fontFamily: 'var(--aeos-font-mono)', fontSize: '11px' }}>{row.id}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Type</span><span className="pc-drow__v">{row.is_demo ? 'Demo tenant' : 'Company'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Billing email</span><span className="pc-drow__v">{row.email ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Currency</span><span className="pc-drow__v">{c}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Plan</span><span className="pc-drow__v">{row.plan ?? '— no active plan'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Joined</span><span className="pc-drow__v">{fmtDate(row.created_at)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Payment method</span><span className="pc-drow__v">{row.has_card ? `•••• ${row.pm_last_four}` : 'No card on file'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Dedicated DB (BYOC)</span><span className="pc-drow__v">{row.byoc_enabled ? 'Enabled' : 'Shared'}</span></div>
          {row.suspension_reason && (
            <div className="tn-danger-note" style={{ marginTop: 'var(--aeos-space-3)' }}>Suspended {fmtDate(row.suspended_at)} — {row.suspension_reason}</div>
          )}
        </div>
      )}
      {tab === 'billing' && (
        <div>
          <div className="sc-dr-sec">Subscription</div>
          {detail == null ? <div className="wb-empty">{failed ? 'Could not load billing.' : 'Loading…'}</div>
            : detail.subscription == null ? <div className="wb-empty">No subscription on record yet.</div>
              : (
                <>
                  <div className="pc-drow"><span className="pc-drow__k">Plan</span><span className="pc-drow__v">{detail.subscription.plan}</span></div>
                  <div className="pc-drow"><span className="pc-drow__k">State</span><span className="pc-drow__v">{detail.subscription.status}</span></div>
                  <div className="pc-drow"><span className="pc-drow__k">Charged</span><span className="pc-drow__v pc-price">{fmtMoney(detail.subscription.amount, detail.subscription.currency)}/{detail.subscription.cycle === 'yearly' ? 'yr' : 'mo'}</span></div>
                  {detail.subscription.trial_ends && <div className="pc-drow"><span className="pc-drow__k">Trial ends</span><span className="pc-drow__v">{fmtDate(detail.subscription.trial_ends)}</span></div>}
                  <div className="pc-drow"><span className="pc-drow__k">Started</span><span className="pc-drow__v">{fmtDate(detail.subscription.started_at)}</span></div>
                </>
              )}
          <div className="sc-dr-sec">Invoices</div>
          {detail == null ? <div className="wb-empty">Loading…</div>
            : detail.invoices.length === 0 ? <div className="wb-empty">No invoices for this tenant yet.</div>
              : (
                <table className="sc-minit">
                  <tbody>
                    {detail.invoices.map((inv, i) => (
                      <tr key={i}>
                        <td><div className="pc-mname">{inv.number}</div><div className="sc-kind">{STATUS_LABEL[inv.status] ?? inv.status} · {fmtDateShort(inv.created_at)}</div></td>
                        <td className="pc-r">{fmtMoney(inv.total, inv.currency)}</td>
                        <td className="pc-r">{inv.amount_due > 0 ? <span className="tn-inv-due">{fmtMoney(inv.amount_due, inv.currency)} due</span> : <span className="tn-inv-paid">paid</span>}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
          {row.outstanding > 0 && (
            <div className="qfoot" style={{ marginTop: 'var(--aeos-space-3)' }}>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/invoices')}>Open in Invoices →</button>
            </div>
          )}
        </div>
      )}
      {tab === 'activity' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
          : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet — lifecycle actions appear here as they happen.</div>
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
export default function Index({ stats, tenants, trend, planMix, sparks }) {
  const toast = useToast();
  const s = stats ?? {};
  const sp = sparks ?? {};
  const list = useMemo(() => tenants ?? [], [tenants]);

  const canView = useHRMAC('tenants.tenant-list.view');
  const canCreate = useHRMAC('tenants.tenant-list.create');
  const canSuspend = useHRMAC('tenants.tenant-list.suspend');
  const canActivate = useHRMAC('tenants.tenant-list.activate');
  const canImpersonate = useHRMAC('tenants.tenant-list.impersonate');
  const canForget = useHRMAC('tenants.tenant-list.forget');
  const canArchive = useHRMAC('tenant-operations.tenant-archive.archive');
  const canBulk = useHRMAC('tenant-operations.bulk-actions.bulk-suspend');

  const [suspendT, setSuspendT] = useState(null);
  const [forgetT, setForgetT] = useState(null);
  const [drawerRow, setDrawerRow] = useState(null);
  const ctx = useCtxMenu();

  const impersonate = (r) => post(`/tenants/${r.id}/impersonate`, {}, { onError: () => toast.error('Could not impersonate tenant.') });
  const activate = (r) => post(`/tenants/${r.id}/activate`, {}, { onSuccess: () => toast.success(`${r.name} reactivated.`) });
  const archive = (r) => confirmPost(`Archive ${r.name}? Their workspace is retired but recoverable.`, `/tenants/${r.id}/archive`);
  const restore = (r) => post(`/tenants/${r.id}/restore`, {}, { onSuccess: () => toast.success(`${r.name} restored.`) });
  const actions = { canView, canSuspend, canActivate, canImpersonate, canForget, canArchive, impersonate, activate, archive, restore, suspend: setSuspendT, forget: setForgetT };

  const wb = useWorkbench({
    rows: list,
    getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.subdomain ?? ''} ${r.email ?? ''} ${r.plan ?? ''} ${r.status}`,
    views: [
      { id: 'all', label: 'All tenants' },
      { id: 'active', label: 'Active', test: (r) => r.status === 'active' },
      { id: 'trial', label: 'Trial', test: (r) => r.status === 'trial' },
      { id: 'attention', label: 'Needs attention', test: (r) => ['suspended', 'failed', 'pending', 'provisioning'].includes(r.status) },
      { id: 'owing', label: 'Owing', test: (r) => r.outstanding > 0 },
      { id: 'demo', label: 'Demo', test: (r) => r.is_demo },
    ],
    facets: {
      group: { value: 'all', test: (r, v) => r.status === v },
      status: { value: 'all', test: (r, v) => r.status === v },
      plan: { value: 'all', test: (r, v) => (v === '__none' ? !r.plan : r.plan === v) },
      currency: { value: 'all', test: (r, v) => r.currency === v },
      type: { value: 'all', test: (r, v) => (v === 'demo' ? r.is_demo : !r.is_demo) },
    },
    sortKey: 'created_at',
    sortVal: (r, k) => (k === 'mrr' || k === 'outstanding') ? (r[k] ?? -1) : String(r[k] ?? ''),
    perPage: 12,
    storageKey: 'platform.tenants',
  });

  const planOptions = useMemo(() => planMix?.map((p) => p.plan) ?? [], [planMix]);

  /* KPI band — 6-up with sparklines, all real */
  const kpis = [
    { label: 'Total tenants', value: s.total ?? 0, delta: `+${(trend?.signups ?? []).at(-1) ?? 0} this month`, up: true, spark: sp.total, color: 'var(--aeos-primary)' },
    { label: 'Active', value: s.active ?? 0, delta: 'currently serving users', up: true, spark: sp.active, color: 'var(--aeos-success)' },
    { label: 'Trial', value: s.trial ?? 0, delta: 'in trial window', spark: sp.trial, color: 'var(--aeos-primary)' },
    { label: 'Suspended', value: s.suspended ?? 0, delta: `${(s.failed ?? 0) + (s.pending ?? 0)} more need attention`, down: (s.suspended ?? 0) > 0, up: (s.suspended ?? 0) === 0, spark: sp.suspended, color: 'var(--aeos-danger)' },
    { label: 'Recurring revenue', value: fmtK(s.mrr), delta: 'MRR · active + trialing', up: true, spark: sp.mrr, color: 'var(--aeos-success)' },
    { label: 'Live vs demo', value: s.live ?? 0, delta: `${s.live ?? 0} live · ${s.demo ?? 0} demo`, spark: sp.live, color: 'var(--aeos-primary)' },
  ];

  /* status donut */
  const donutStatuses = [
    ['active', 'var(--aeos-success)'],
    ['trial', 'var(--aeos-primary)'],
    ['suspended', 'var(--aeos-danger)'],
    ['provisioning', 'var(--aeos-warning)'],
    ['pending', 'color-mix(in srgb, var(--aeos-warning) 55%, transparent)'],
    ['failed', 'color-mix(in srgb, var(--aeos-danger) 55%, transparent)'],
    ['archived', 'var(--aeos-text-muted)'],
  ];
  const activeRate = s.total ? Math.round(((s.active ?? 0) / s.total) * 100) : 0;

  /* plan mix bars */
  const mix = planMix ?? [];
  const mixMax = Math.max(1, ...mix.map((m) => m.count));

  /* attention + collections queues */
  const attention = useMemo(() => list.filter((r) => ['suspended', 'failed', 'pending', 'provisioning'].includes(r.status)), [list]);
  const owing = useMemo(() => list.filter((r) => r.outstanding > 0).sort((a, b) => b.outstanding - a.outstanding), [list]);
  const trials = useMemo(() => list.filter((r) => r.status === 'trial'), [list]);

  const rowMenu = (r) => {
    const isSuspended = r.status === 'suspended' || r.status === 'frozen';
    return [
      ...(canView ? [{ label: 'View profile', onClick: () => router.visit(`/tenants/${r.id}`) }] : []),
      ...(canImpersonate && !isSuspended ? [{ label: 'Impersonate', onClick: () => impersonate(r) }] : []),
      ...(canActivate && isSuspended ? [{ label: 'Reactivate', onClick: () => activate(r) }] : []),
      ...(canSuspend && !isSuspended && r.status !== 'archived' ? [{ label: 'Suspend…', onClick: () => setSuspendT(r) }] : []),
      ...(canArchive && r.status !== 'archived' ? [{ label: 'Archive', onClick: () => archive(r) }] : []),
      ...(canActivate && r.status === 'archived' ? [{ label: 'Restore', onClick: () => restore(r) }] : []),
      ...(canForget ? ['sep', { label: 'Forget (GDPR)…', danger: true, onClick: () => setForgetT(r) }] : []),
    ];
  };

  const columns = [
    {
      key: 'name', label: 'Tenant', sortable: true,
      render: (r) => (
        <div className="pc-mrow">
          <div className="sc-av">{initials(r.name)}</div>
          <div><div className="pc-mname">{r.name}</div><div className="sc-kind">{r.subdomain ? `${r.subdomain}.aeos365.com` : '—'}</div></div>
        </div>
      ),
    },
    { key: 'type', label: 'Type', hideSm: true, render: (r) => (r.is_demo ? <span className="tn-tag tn-tag--demo">Demo</span> : <span className="tn-tag">Company</span>) },
    { key: 'plan', label: 'Plan', sortable: true, render: (r) => (r.plan ? <span>{r.plan}</span> : <span className="pc-free">—</span>) },
    { key: 'status', label: 'Status', render: (r) => <span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span> },
    { key: 'mrr', label: 'MRR', align: 'r', sortable: true, render: (r) => (r.mrr == null ? <span className="pc-free">—</span> : <span className="pc-price">{fmtMoney(r.mrr, r.currency)}</span>) },
    { key: 'outstanding', label: 'Outstanding', align: 'r', sortable: true, render: (r) => (r.outstanding > 0 ? <span className="tn-owe">{fmtMoney(r.outstanding, r.currency)}</span> : <span className="pc-free">—</span>) },
    { key: 'created_at', label: 'Joined', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtDateShort(r.created_at)}</span> },
    {
      key: 'actions', label: '', align: 'r', width: 44,
      render: (r) => (
        <button type="button" className="wb-kebab" aria-label={`Actions for ${r.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button>
      ),
    },
  ];

  const bulkIds = () => wb.selectedRows.map((r) => r.id);
  const exportSelectedCsv = () => {
    const header = 'name,subdomain,email,type,status,plan,currency,mrr,outstanding,joined';
    const lines = wb.selectedRows.map((r) =>
      [r.name, r.subdomain ?? '', r.email ?? '', r.is_demo ? 'demo' : 'company', r.status, r.plan ?? '', r.currency, r.mrr ?? '', r.outstanding, r.created_at ?? '']
        .map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `tenants-selected-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };
  const bulkSuspend = () => {
    const reason = window.prompt(`Suspend ${wb.selection.size} selected tenant(s)?\nReason (recorded on audit trail):`);
    if (reason && reason.trim()) post('/tenants/bulk', { type: 'suspend', reason, tenant_ids: bulkIds() });
  };

  return (
    <div className="pc tn">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Tenant Operations</div>
          <h1 className="pc-title">Tenants</h1>
          <div className="pc-sub">Every tenant account and its lifecycle — status, plan, recurring revenue and outstanding balance in one operating view, with impersonation, suspension, GDPR forget and bulk actions at hand.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [
            { label: 'Export CSV — all tenants', onClick: () => { window.location.href = '/tenants/export'; } },
            { label: 'Print this view', onClick: () => window.print() },
          ])}>{Glyph.export}<span>Export</span></button>
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => router.visit('/tenants/create')}>{Glyph.plus}<span>New tenant</span></button>}
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}${c.down ? ' pc-kpi__delta--down' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color ?? 'var(--aeos-primary)'} /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Signups trend</h2><div className="pc-panel-h__sub">New tenants per month — last 6 months</div></div>
            <span className="sc-badge sc-badge--ok">{s.total ?? 0} total</span>
          </div>
          <AreaTrend
            series={[
              { key: 'signups', label: 'New signups', color: 'var(--aeos-primary)', values: trend?.signups ?? [] },
              { key: 'cumulative', label: 'Cumulative', color: 'var(--aeos-text-secondary)', fill: false, values: trend?.cumulative ?? [] },
            ]}
            labels={trend?.labels ?? []}
            height={200}
            ariaLabel="Signups trend, new versus cumulative"
          />
          <div className="tn-trend-foot">
            <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />New signups <b>{(trend?.signups ?? []).at(-1) ?? 0}</b></span>
            <span className="li"><span className="d" style={{ background: 'var(--aeos-text-secondary)' }} />Cumulative <b>{(trend?.cumulative ?? []).at(-1) ?? 0}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Status health</h2><div className="pc-panel-h__sub">{s.total ?? 0} tenants — click a status to filter</div></div>
          </div>
          <div className="sc-donut-row">
            <Donut
              segments={donutStatuses.map(([st, color]) => ({ color, value: s.byStatus?.[st] ?? 0 }))}
              centerValue={`${activeRate}%`}
              centerLabel="active"
              size={112}
            />
            <div className="sc-dl">
              {donutStatuses.filter(([st]) => (s.byStatus?.[st] ?? 0) > 0).map(([st, color]) => (
                <button key={st} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === st ? 'all' : st)}>
                  <span className="d" style={{ background: color }} />{STATUS_LABEL[st]}<b>{s.byStatus?.[st] ?? 0}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Plan mix</h2><div className="pc-panel-h__sub">Active + trialing subscriptions</div></div>
          </div>
          <div className="tn-mix">
            {mix.length === 0 && <div className="wb-empty">No active subscriptions yet.</div>}
            {mix.map((m, i) => (
              <button
                key={m.plan}
                type="button"
                className={`tn-mixrow${wb.facetValues.plan === m.plan ? ' is-on' : ''}`}
                onClick={() => wb.setFacet('plan', wb.facetValues.plan === m.plan ? 'all' : m.plan)}
              >
                <span className="tn-mixrow__cap">{m.plan}</span>
                <span className="tn-mixrow__track"><span className={`tn-mixrow__fill tn-mix-${i % 4}`} style={{ width: `${Math.max((m.count / mixMax) * 100, 6)}%` }} /></span>
                <span className="tn-mixrow__n"><b>{m.count}</b> · {fmtK(m.mrr)}</span>
              </button>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* action queues */}
      <div className="sc-queues">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Needs attention</h2><div className="pc-panel-h__sub">Suspended, failed &amp; pending</div></div>
            <span className={`sc-badge ${attention.length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{attention.length}</span>
          </div>
          {attention.length === 0 && <div className="wb-empty">Nothing needs attention — the fleet is healthy.</div>}
          {attention.slice(0, 4).map((r) => (
            <div key={r.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.name}</b><span>{r.subdomain}</span></div>
              <span className={`pc-chip pc-chip--${r.status}`}><span className="pc-chip__dot" />{STATUS_LABEL[r.status] ?? r.status}</span>
              {(r.status === 'suspended' || r.status === 'failed') && canActivate
                ? <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => activate(r)}>Reactivate</button>
                : <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDrawerRow(r)}>Review</button>}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('attention')}>Open attention view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Outstanding balances</h2><div className="pc-panel-h__sub">Unpaid across invoices</div></div>
            <span className={`sc-badge ${owing.length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{fmtMoney(s.outstanding)}</span>
          </div>
          {owing.length === 0 && <div className="wb-empty">No outstanding balances — collections are clean.</div>}
          {owing.slice(0, 4).map((r) => (
            <div key={r.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.name}</b><span>{r.plan ?? '—'}</span></div>
              <span className="sc-qitem__amt">{fmtMoney(r.outstanding, r.currency)}</span>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDrawerRow(r)}>Open</button>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('owing')}>Open balances view →</button></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Trials in flight</h2><div className="pc-panel-h__sub">Convert or nudge</div></div>
            <span className={`sc-badge ${trials.length ? 'sc-badge--ok' : 'sc-badge--warn'}`}>{trials.length}</span>
          </div>
          {trials.length === 0 && <div className="wb-empty">No active trials right now.</div>}
          {trials.slice(0, 4).map((r) => (
            <div key={r.id} className="sc-qitem">
              <div className="sc-qitem__who"><b>{r.name}</b><span>{r.plan ?? 'no plan'}</span></div>
              <span className="sc-qitem__when sc-qitem__when--soon">trial</span>
              {canImpersonate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => impersonate(r)}>Impersonate</button>}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="pc-btn pc-btn--sm" onClick={() => wb.activateView('trial')}>Open trial view →</button></div>
        </CardBody></Card>
      </div>

      {/* workbench */}
      <Card><CardBody>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name, subdomain or email…" ariaLabel="Search tenants" />
          <div className="pc-seg" role="group" aria-label="Tenant group">
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'all'} onClick={() => wb.setFacet('group', 'all')}>All · {s.total ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'active'} onClick={() => wb.setFacet('group', 'active')}>Active · {s.active ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'trial'} onClick={() => wb.setFacet('group', 'trial')}>Trial · {s.trial ?? 0}</button>
            <button type="button" className="pc-seg__b" aria-pressed={wb.facetValues.group === 'suspended'} onClick={() => wb.setFacet('group', 'suspended')}>Suspended · {s.suspended ?? 0}</button>
          </div>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {Object.keys(STATUS_LABEL).map((k) => <option key={k} value={k}>{STATUS_LABEL[k]}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.plan} onChange={(e) => wb.setFacet('plan', e.target.value)} aria-label="Plan filter">
            <option value="all">Any plan</option>
            {planOptions.map((p) => <option key={p} value={p}>{p}</option>)}
            <option value="__none">No plan</option>
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.currency} onChange={(e) => wb.setFacet('currency', e.target.value)} aria-label="Currency filter">
            <option value="all">Any currency</option>
            {['USD', 'EUR', 'GBP', 'BDT'].map((cu) => <option key={cu} value={cu}>{cu}</option>)}
          </select>
          <select className="pc-input sc-statusfilter" value={wb.facetValues.type} onChange={(e) => wb.setFacet('type', e.target.value)} aria-label="Type filter">
            <option value="all">Live &amp; demo</option>
            <option value="live">Live only</option>
            <option value="demo">Demo only</option>
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>

        <WbViews wb={wb} />

        <WbBulkBar wb={wb}>
          {canSuspend && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={bulkSuspend}>Suspend</button>}
          {canActivate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Reactivate ${wb.selection.size} selected tenant(s)?`, '/tenants/bulk', { type: 'reactivate', tenant_ids: bulkIds() })}>Reactivate</button>}
          {canArchive && <button type="button" className="pc-btn pc-btn--sm" onClick={() => confirmPost(`Archive ${wb.selection.size} selected tenant(s)?`, '/tenants/bulk', { type: 'archive', tenant_ids: bulkIds() })}>Archive</button>}
          <button type="button" className="pc-btn pc-btn--sm" onClick={exportSelectedCsv}>Export selected</button>
        </WbBulkBar>

        <WbTable
          wb={wb}
          columns={columns}
          selectable={canBulk}
          onRowClick={setDrawerRow}
          rowAriaLabel={(r) => `${r.name}, ${STATUS_LABEL[r.status] ?? r.status}`}
          empty={
            <>
              No tenants match these filters.
              <br />
              <button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button>
            </>
          }
        />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawerRow && <DetailDrawer row={list.find((r) => r.id === drawerRow.id) ?? drawerRow} onClose={() => setDrawerRow(null)} actions={{ ...actions, suspend: (r) => { setDrawerRow(null); setSuspendT(r); }, forget: (r) => { setDrawerRow(null); setForgetT(r); }, archive: (r) => { setDrawerRow(null); archive(r); } }} />}
      {suspendT && <SuspendModal tenant={suspendT} onClose={() => setSuspendT(null)} />}
      {forgetT && <ForgetModal tenant={forgetT} onClose={() => setForgetT(null)} onDone={(msg) => { setForgetT(null); toast.success(msg); router.reload({ only: ['tenants', 'stats', 'trend', 'planMix', 'sparks'] }); }} />}
    </div>
  );
}

Index.layout = (page) => (
  <App title="Tenants" railTitle="Tenant operations" rail={<TenantsRail stats={page.props.stats} />}>
    {page}
  </App>
);
