import { useCallback, useEffect, useMemo, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useWorkbench, useCtxMenu,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './onboarding.css';

/* ---------------- shared bits ---------------- */
const svg = (p) => (<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>);
const Glyph = {
  tenants: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  analytics: svg(<><path d="M4 20V10M10 20V4M16 20v-7M22 20H2" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5M4 21h16" /></>),
  gear: svg(<><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H4.5a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 6.2 8" /></>),
};

const STATUS = {
  active: 'Active', trial: 'Trial', pending: 'Pending', provisioning: 'Provisioning',
  failed: 'Failed', suspended: 'Suspended', archived: 'Archived', cancelled: 'Cancelled',
};
const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const fmtDateShort = (s) => { if (!s) return '—'; try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };
const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;

/* ---------------- rail ---------------- */
function OnboardingRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Pipeline</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Pending</span><b>{k.pending ?? 0}</b></div>
          <div className="pc-rail__row"><span>Provisioning</span><b>{k.provisioning ?? 0}</b></div>
          <div className="pc-rail__row"><span>Failed</span><b>{k.failed ?? 0}</b></div>
          <div className="pc-rail__row"><span>Active trials</span><b>{k.active_trials ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Fleet</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Active tenants</span><b>{k.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Suspended</span><b>{k.suspended ?? 0}</b></div>
          <div className="pc-rail__row"><span>New this month</span><b>{k.new_this_month ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/tenants')}>{Glyph.tenants}<span>Tenants</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/analytics')}>{Glyph.analytics}<span>Analytics</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing')}>{Glyph.billing}<span>Billing</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- modals ---------------- */
function ReasonModal({ title, sub, label, danger, submitLabel, onSubmit, onClose }) {
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); setBusy(true); Promise.resolve(onSubmit(reason)).finally(() => { setBusy(false); onClose(); }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{title}</h2>
        <div className="pc-modal__sub">{sub}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="rm-reason">{label}</label>
            <textarea id="rm-reason" className="pc-input" value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Add a reason…" />
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className={`pc-btn ${danger ? 'pc-btn--danger' : 'pc-btn--primary'}`} disabled={busy}>{busy ? 'Working…' : submitLabel}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function ExtendModal({ tenant, onSubmit, onClose }) {
  const [days, setDays] = useState(14);
  const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); setBusy(true); Promise.resolve(onSubmit(days)).finally(() => { setBusy(false); onClose(); }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Extend trial</h2>
        <div className="pc-modal__sub">{tenant.tenant} — add more trial time.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="ex-days">Extend by</label>
            <select id="ex-days" className="pc-input" value={days} onChange={(e) => setDays(Number(e.target.value))}>
              <option value={7}>7 days</option><option value={14}>14 days</option><option value={30}>30 days</option>
            </select>
          </div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Extending…' : 'Extend trial'}</button></div>
        </form>
      </div>
    </div>
  );
}

function ConvertModal({ tenant, plans, onSubmit, onClose }) {
  const [planId, setPlanId] = useState(plans[0]?.id ?? '');
  const [cycle, setCycle] = useState('monthly');
  const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); setBusy(true); Promise.resolve(onSubmit(planId, cycle)).finally(() => { setBusy(false); onClose(); }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Convert to paid</h2>
        <div className="pc-modal__sub">{tenant.tenant} — end the trial and start a paid subscription.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-row2">
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="cv-plan">Plan</label>
              <select id="cv-plan" className="pc-input" value={planId} onChange={(e) => setPlanId(e.target.value)}>
                {plans.map((p) => <option key={p.id} value={p.id}>{p.name} — {fmtMoney(p.price_monthly)}/mo</option>)}
              </select>
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="cv-cycle">Billing cycle</label>
              <select id="cv-cycle" className="pc-input" value={cycle} onChange={(e) => setCycle(e.target.value)}>
                <option value="monthly">Monthly</option><option value="yearly">Yearly</option>
              </select>
            </div>
          </div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Converting…' : 'Convert to paid'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function DetailDrawer({ row, csrf, onAction, onClose }) {
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    setDetail(null); setFailed(false);
    if (!row) return undefined;
    const ac = new AbortController();
    fetch(`/onboarding/tenants/${row.id}/detail`, { headers: { Accept: 'application/json' }, signal: ac.signal })
      .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status))))).then(setDetail)
      .catch((e) => { if (e.name !== 'AbortError') setFailed(true); });
    return () => ac.abort();
  }, [row?.id]); // eslint-disable-line react-hooks/exhaustive-deps
  if (!row) return null;

  const s = row.status;
  const acts = [];
  if (s === 'pending') { acts.push(['Approve & provision', 'ok', () => onAction('approve', row)]); acts.push(['Reject…', 'danger', () => onAction('reject', row)]); }
  if (s === 'failed') acts.push(['Retry provisioning', 'primary', () => onAction('retry', row)]);
  if (s === 'trial') { acts.push(['Convert to paid', 'primary', () => onAction('convert', row)]); acts.push(['Extend trial', '', () => onAction('extend', row)]); acts.push(['Cancel trial…', 'danger', () => onAction('cancel', row)]); }
  if (s === 'suspended') acts.push(['Reactivate', 'primary', () => onAction('reactivate', row)]);
  if (!['suspended', 'archived'].includes(s)) acts.push(['Suspend…', '', () => onAction('suspend', row)]);
  if (s !== 'archived') acts.push(['Archive…', 'danger', () => onAction('archive', row)]);

  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Tenant — ${row.tenant}`}
      head={(
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(row.tenant)}</div>
            <div><div className="sc-dr-title">{row.tenant}</div><div className="sc-dr-code">{row.email} · {row.subdomain}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{STATUS[s] ?? s}</div></div>
            <div className="sc-dr-kpi"><div className="l">Verified</div><div className="v">{row.verified ? 'Yes' : 'No'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Plan</div><div className="v">{row.plan}</div></div>
          </div>
          <div className="sc-dr-acts" style={{ flexWrap: 'wrap' }}>
            {acts.map(([label, intent, fn]) => (
              <button key={label} type="button" className={`pc-btn pc-btn--sm ${intent === 'ok' ? 'pc-btn--primary' : intent === 'primary' ? 'pc-btn--primary' : intent === 'danger' ? 'pc-btn--danger' : ''}`} onClick={fn}>{label}</button>
            ))}
          </div>
        </>
      )}
    >
      <div className="pc-drow"><span className="pc-drow__k">Registration step</span><span className="pc-drow__v">{row.reg_step ?? '—'}</span></div>
      {row.prov_step && <div className="pc-drow"><span className="pc-drow__k">Provisioning step</span><span className="pc-drow__v">{row.prov_step}</span></div>}
      {row.trial_ends && <div className="pc-drow"><span className="pc-drow__k">Trial ends</span><span className="pc-drow__v">{fmtDate(row.trial_ends)}</span></div>}
      <div className="pc-drow"><span className="pc-drow__k">Registered</span><span className="pc-drow__v">{fmtDate(row.created_at)}</span></div>
      {detail?.reasons && Object.keys(detail.reasons).length > 0 && (
        <div className="ob-dr-reasons">
          {Object.entries(detail.reasons).map(([k, v]) => <div className="ob-dr-reason" key={k}><b>{k}:</b> {v}</div>)}
        </div>
      )}
      <div className="sc-dr-sec">Activity</div>
      {detail == null ? <div className="wb-empty">{failed ? 'Could not load activity.' : 'Loading…'}</div>
        : detail.activity.length === 0 ? <div className="wb-empty">No recorded activity yet.</div>
          : (
            <ul className="sc-tl">
              {detail.activity.map((a, i) => <li key={i}>{a.message}<span className="when">{fmtDate(a.at)}{a.actor ? ` · ${a.actor}` : ''}</span></li>)}
            </ul>
          )}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Onboarding({ overview }) {
  const o = overview ?? {};
  const k = o.kpis ?? {};
  const funnel = o.funnel ?? [];
  const trend = o.trend ?? { labels: [], counts: [] };
  const statusDist = o.status_dist ?? [];
  const q = o.queues ?? {};
  const tenants = useMemo(() => o.tenants ?? [], [o.tenants]);
  const automation = o.automation ?? [];
  const settings = o.settings ?? {};
  const templates = o.templates ?? [];
  const plans = o.plans ?? [];
  const csrf = usePage().props.csrfToken;
  const ctx = useCtxMenu();

  const [drawerRow, setDrawerRow] = useState(null);
  const [modal, setModal] = useState(null); // {type, row}

  const api = useCallback((name, id, body = {}) => fetch(route(`admin.onboarding.${name}`, id), {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(body),
  }).then((r) => r.json().catch(() => ({}))).finally(() => router.reload({ only: ['overview'], preserveScroll: true })), [csrf]);

  const toggleRule = (id, active) => fetch(route('admin.onboarding.automation.toggle'), {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ rule_id: id, is_active: active }),
  }).finally(() => router.reload({ only: ['overview'], preserveScroll: true }));

  // route into the correct flow (confirm / modal) per action
  const onAction = (type, row) => {
    setDrawerRow(null);
    switch (type) {
      case 'approve': if (window.confirm(`Approve ${row.tenant} and queue provisioning?`)) api('approve', row.id); break;
      case 'retry': if (window.confirm(`Retry provisioning for ${row.tenant}?`)) api('provisioning.retry', row.id); break;
      case 'reactivate': if (window.confirm(`Reactivate ${row.tenant}?`)) api('tenants.reactivate', row.id); break;
      case 'reject': setModal({ type: 'reject', row }); break;
      case 'suspend': setModal({ type: 'suspend', row }); break;
      case 'archive': setModal({ type: 'archive', row }); break;
      case 'cancel': setModal({ type: 'cancel', row }); break;
      case 'extend': setModal({ type: 'extend', row }); break;
      case 'convert': setModal({ type: 'convert', row }); break;
      default: break;
    }
  };

  const kpis = [
    { label: 'Pending approvals', value: k.pending ?? 0, delta: `${(q.pending ?? []).filter((p) => !p.verified).length} unverified`, cls: (k.pending ?? 0) > 0 ? 'warn' : 'up' },
    { label: 'In provisioning', value: k.provisioning ?? 0, delta: `${k.failed ?? 0} failed — need retry`, cls: (k.failed ?? 0) > 0 ? 'down' : '' },
    { label: 'Active trials', value: k.active_trials ?? 0, delta: `${k.expiring_soon ?? 0} ending ≤7 days`, cls: (k.expiring_soon ?? 0) > 0 ? 'warn' : '' },
    { label: 'New this month', value: k.new_this_month ?? 0, delta: 'signups', cls: 'up' },
    { label: 'Suspended', value: k.suspended ?? 0, delta: `${k.archived ?? 0} archived`, cls: '' },
    { label: 'Active tenants', value: k.active ?? 0, delta: 'live on the platform', cls: 'up' },
  ];

  const fnMax = Math.max(1, ...funnel.map((f) => f.count));
  const distColors = { active: 'var(--aeos-success)', trial: 'var(--aeos-tertiary, var(--aeos-primary))', pending: 'var(--aeos-warning)', provisioning: 'var(--aeos-primary)', failed: 'var(--aeos-danger)', suspended: 'var(--aeos-text-muted)', archived: 'var(--aeos-text-muted)', cancelled: 'var(--aeos-text-muted)' };
  const distTotal = statusDist.reduce((a, d) => a + d.count, 0);

  const columns = [
    { key: 'tenant', label: 'Tenant', sortable: true, render: (r) => (
      <div className="pc-mrow"><div className="sc-av">{initials(r.tenant)}</div><div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.email}</div></div></div>) },
    { key: 'status', label: 'Status', render: (r) => <span className={`ob-chip ob-chip--${r.status}`}><span className="ob-chip__dot" />{STATUS[r.status] ?? r.status}{!r.verified && r.status === 'pending' ? '' : ''}</span> },
    { key: 'plan', label: 'Plan', hideSm: true, render: (r) => <span className="sc-kind">{r.plan}</span> },
    { key: 'verified', label: 'Verified', hideSm: true, render: (r) => (r.verified ? <span className="ob-mini">Yes</span> : <span className="ob-unver">Unverified</span>) },
    { key: 'created_at', label: 'Registered', align: 'r', sortable: true, render: (r) => <span className="sc-kind">{fmtDateShort(r.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.tenant}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button> },
  ];

  const rowMenu = (r) => {
    const items = [{ label: 'View detail', onClick: () => setDrawerRow(r) }, 'sep'];
    if (r.status === 'pending') { items.push({ label: 'Approve & provision', onClick: () => onAction('approve', r) }, { label: 'Reject…', danger: true, onClick: () => onAction('reject', r) }); }
    if (r.status === 'failed') items.push({ label: 'Retry provisioning', onClick: () => onAction('retry', r) });
    if (r.status === 'trial') items.push({ label: 'Convert to paid', onClick: () => onAction('convert', r) }, { label: 'Extend trial', onClick: () => onAction('extend', r) }, { label: 'Cancel trial…', danger: true, onClick: () => onAction('cancel', r) });
    if (r.status === 'suspended') items.push({ label: 'Reactivate', onClick: () => onAction('reactivate', r) });
    if (!['suspended', 'archived'].includes(r.status)) items.push({ label: 'Suspend…', onClick: () => onAction('suspend', r) });
    if (r.status !== 'archived') items.push({ label: 'Archive…', danger: true, onClick: () => onAction('archive', r) });
    return items;
  };

  const wb = useWorkbench({
    rows: tenants,
    getId: (r) => r.id,
    searchText: (r) => `${r.tenant} ${r.email} ${r.subdomain} ${r.status}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'pending', label: 'Pending', test: (r) => r.status === 'pending' },
      { id: 'provisioning', label: 'Provisioning', test: (r) => ['provisioning', 'failed'].includes(r.status) },
      { id: 'trial', label: 'Trials', test: (r) => r.status === 'trial' },
      { id: 'suspended', label: 'Suspended', test: (r) => r.status === 'suspended' },
    ],
    facets: { status: { value: 'all', test: (r, v) => r.status === v } },
    sortKey: 'created_at', sortVal: (r, key) => String(r[key] ?? ''),
    perPage: 10, storageKey: 'platform.onboarding',
  });

  const bulk = (action) => {
    const ids = wb.selectedRows.map((r) => r.id);
    if (ids.length === 0) return;
    let reason = null;
    if (['reject', 'archive'].includes(action)) { reason = window.prompt(`Reason for bulk ${action}:`); if (reason === null) return; }
    fetch(route('admin.onboarding.bulk'), { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify({ action, ids, reason }) })
      .finally(() => router.reload({ only: ['overview'], preserveScroll: true }));
  };

  return (
    <div className="pc obx">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Onboarding</div>
          <h1 className="pc-title">Onboarding</h1>
          <div className="pc-sub">From signup to activated tenant — approvals, provisioning, trials and conversion in one operating console. Approve, provision, extend, convert, suspend or archive without leaving the page.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => window.print()}>{Glyph.export}<span>Export</span></button>
          <button type="button" className="pc-btn" onClick={() => router.visit('/onboarding/settings')}>{Glyph.gear}<span>Settings</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody><div className="pc-kpi">
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value">{c.value}</div>
            <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'down' ? ' pc-kpi__delta--down' : ''}${c.cls === 'warn' ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
          </div></CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="ob-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Conversion funnel</h2><div className="pc-panel-h__sub">This month — signup → paid</div></div>
            <span className={`sc-badge ${(k.conversion_pct ?? 0) > 0 ? 'sc-badge--ok' : 'sc-badge--warn'}`}>{k.conversion_pct ?? 0}% end-to-end</span></div>
          <div className="ob-funnel">
            {funnel.map((f, i) => {
              const drop = i > 0 && funnel[i - 1].count > 0 ? Math.round((1 - f.count / funnel[i - 1].count) * 100) : 0;
              const colors = ['var(--aeos-primary)', 'var(--aeos-tertiary, var(--aeos-primary))', 'var(--aeos-success)'];
              return (
                <div className="ob-fnrow" key={f.stage}>
                  <span className="ob-fnrow__cap">{f.stage}</span>
                  <span className="ob-fntrack"><span className="ob-fnfill" style={{ width: `${Math.max(12, (f.count / fnMax) * 100)}%`, background: colors[i % 3] }}>{f.count}</span></span>
                  <span className="ob-fndrop">{i > 0 ? `−${drop}%` : '100%'}</span>
                </div>
              );
            })}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Signups</h2><div className="pc-panel-h__sub">New registrations — weekly</div></div></div>
          <AreaTrend series={[{ key: 'signups', label: 'Signups', color: 'var(--aeos-primary)', values: trend.counts }]} labels={trend.labels} height={200} ariaLabel="Weekly signups" empty="No signups in this window." />
          <div className="sc-dl sc-dl--row"><span className="li"><span className="d sc-d-exp" />Signups <b>{(trend.counts ?? []).reduce((a, b) => a + b, 0)}</b> in 8 weeks</span></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Tenant status</h2><div className="pc-panel-h__sub">{distTotal} tenants</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={statusDist.map((d) => ({ color: distColors[d.status] ?? 'var(--aeos-text-muted)', value: d.count }))} centerValue={`${distTotal}`} centerLabel="tenants" size={116} />
            <div className="sc-dl">
              {statusDist.map((d) => (
                <button key={d.status} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === d.status ? 'all' : d.status)}>
                  <span className="d" style={{ background: distColors[d.status] ?? 'var(--aeos-text-muted)' }} />{d.label}<b>{d.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* action queues */}
      <div className="ob-queues">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Pending approvals</h2><div className="pc-panel-h__sub">Awaiting review</div></div><span className={`sc-badge ${(q.pending ?? []).length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{(q.pending ?? []).length}</span></div>
          {(q.pending ?? []).length === 0 && <div className="wb-empty">No pending registrations.</div>}
          {(q.pending ?? []).map((r) => (
            <div key={r.id}>
              <div className="sc-qitem"><div className="sc-av">{initials(r.tenant)}</div><div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.email}</span></div>{!r.verified && <span className="ob-unver">Unverified</span>}</div>
              <div className="ob-qacts"><button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => onAction('approve', r)}>Approve</button><button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => onAction('reject', r)}>Reject</button></div>
            </div>
          ))}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Provisioning</h2><div className="pc-panel-h__sub">In progress &amp; failed</div></div><span className={`sc-badge ${(k.failed ?? 0) ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{k.failed ?? 0} failed</span></div>
          {(q.provisioning ?? []).length === 0 && <div className="wb-empty">Provisioning queue is empty.</div>}
          {(q.provisioning ?? []).map((r) => (
            <div key={r.id}>
              <div className="sc-qitem"><div className="sc-av">{initials(r.tenant)}</div><div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.step}</span></div><span className={`ob-chip ob-chip--${r.status}`}><span className="ob-chip__dot" />{STATUS[r.status] ?? r.status}</span></div>
              {r.status === 'failed' && <div className="ob-qacts"><button type="button" className="pc-btn pc-btn--sm" onClick={() => onAction('retry', r)}>Retry</button></div>}
            </div>
          ))}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Trials ending</h2><div className="pc-panel-h__sub">Convert or extend</div></div><span className={`sc-badge ${(q.trials ?? []).length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{(q.trials ?? []).length}</span></div>
          {(q.trials ?? []).length === 0 && <div className="wb-empty">No active trials.</div>}
          {(q.trials ?? []).map((r) => {
            const row = { ...r, tenant: r.tenant, status: 'trial' };
            const overdue = r.days_left != null && r.days_left < 0;
            return (
              <div key={r.id}>
                <div className="sc-qitem"><div className="sc-av">{initials(r.tenant)}</div><div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.plan}</span></div>
                  <span className={`sc-qitem__when ${overdue ? 'sc-qitem__when--due' : 'sc-qitem__when--soon'}`}>{overdue ? `${Math.abs(r.days_left)}d overdue` : `${r.days_left}d left`}</span></div>
                <div className="ob-qacts"><button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => onAction('convert', row)}>Convert</button><button type="button" className="pc-btn pc-btn--sm" onClick={() => onAction('extend', row)}>Extend</button><button type="button" className="pc-btn pc-btn--sm" onClick={() => onAction('cancel', row)}>Cancel</button></div>
              </div>
            );
          })}
        </CardBody></Card>
      </div>

      {/* lifecycle workbench */}
      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Tenant lifecycle</h2><div className="pc-panel-h__sub">Every tenant in the onboarding pipeline — search, filter, act</div></div></div>
        <WbToolbar>
          <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search tenant, email or subdomain…" ariaLabel="Search tenants" />
          <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
            <option value="all">All statuses</option>
            {Object.entries(STATUS).map(([k2, v]) => <option key={k2} value={k2}>{v}</option>)}
          </select>
          <WbColumns wb={wb} columns={columns} />
        </WbToolbar>
        <WbViews wb={wb} />
        <WbBulkBar wb={wb}>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulk('approve')}>Approve</button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulk('retry')}>Retry</button>
          <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => bulk('archive')}>Archive</button>
        </WbBulkBar>
        <WbTable wb={wb} columns={columns} selectable onRowClick={setDrawerRow}
          rowAriaLabel={(r) => `${r.tenant} — ${STATUS[r.status] ?? r.status}`}
          empty={<>No tenants match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>} />
        <WbFooter wb={wb} />
      </CardBody></Card>

      {/* automation + settings + templates */}
      <div className="ob-low">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Automation rules</h2><div className="pc-panel-h__sub">Hands-off onboarding</div></div><span className="sc-badge sc-badge--ok">{automation.filter((r) => r.active).length} active</span></div>
          {automation.map((r) => (
            <div className="ob-rule" key={r.id}>
              <div className="ob-rule__n"><b>{r.name}</b><span>{r.desc}</span></div>
              <button type="button" className="ob-sw" aria-pressed={r.active} aria-label={`Toggle ${r.name}`} onClick={() => toggleRule(r.id, !r.active)} />
            </div>
          ))}
        </CardBody></Card>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-3)' }}>
          <Card><CardBody>
            <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Onboarding settings</h2><div className="pc-panel-h__sub">Registration policy &amp; defaults</div></div><button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/onboarding/settings')}>Edit</button></div>
            <div className="ob-setrow"><span className="ob-setrow__k">Default trial</span><span className="ob-setrow__v">{settings.default_trial_days ?? 14} days</span></div>
            <div className="ob-setrow"><span className="ob-setrow__k">Email verification</span><span className="ob-setrow__v">{settings.require_email_verification ? 'Required' : 'Off'}</span></div>
            <div className="ob-setrow"><span className="ob-setrow__k">Manual approval</span><span className="ob-setrow__v">{settings.require_manual_approval ? 'On' : 'Off'}</span></div>
            <div className="ob-setrow"><span className="ob-setrow__k">CAPTCHA</span><span className="ob-setrow__v">{settings.enable_captcha ? 'On' : 'Off'}</span></div>
            <div className="ob-setrow"><span className="ob-setrow__k">Max signups / IP</span><span className="ob-setrow__v">{settings.max_registrations_per_ip ?? 5}</span></div>
            <div className="ob-setrow"><span className="ob-setrow__k">Blocked domains</span><span className="ob-setrow__v">{(settings.blocked_domains || '—').split(',')[0]}{(settings.blocked_domains || '').split(',').length > 1 ? ` +${(settings.blocked_domains).split(',').length - 1}` : ''}</span></div>
          </CardBody></Card>

          <Card><CardBody>
            <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Email templates</h2><div className="pc-panel-h__sub">Lifecycle emails</div></div></div>
            {templates.map((t) => (
              <div className="ob-tmpl" key={t.id}><div className="ob-tmpl__ic">✉</div><div className="ob-tmpl__n"><b>{t.name}</b><span>{t.desc}</span></div></div>
            ))}
          </CardBody></Card>
        </div>
      </div>

      {/* overlays */}
      {ctx.element}
      {drawerRow && <DetailDrawer row={tenants.find((t) => t.id === drawerRow.id) ?? drawerRow} csrf={csrf} onAction={onAction} onClose={() => setDrawerRow(null)} />}
      {modal?.type === 'reject' && <ReasonModal title="Reject registration" sub={`${modal.row.tenant} — this archives the registration.`} label="Reason" danger submitLabel="Reject" onSubmit={(reason) => api('reject', modal.row.id, { reason: reason || 'Rejected by admin' })} onClose={() => setModal(null)} />}
      {modal?.type === 'suspend' && <ReasonModal title="Suspend tenant" sub={`${modal.row.tenant} — access is blocked until reactivated.`} label="Reason" danger submitLabel="Suspend" onSubmit={(reason) => api('tenants.suspend', modal.row.id, { reason: reason || 'Suspended by admin' })} onClose={() => setModal(null)} />}
      {modal?.type === 'archive' && <ReasonModal title="Archive tenant" sub={`${modal.row.tenant} — removes it from active pipelines.`} label="Reason" danger submitLabel="Archive" onSubmit={(reason) => api('tenants.archive', modal.row.id, { reason: reason || 'Archived by admin' })} onClose={() => setModal(null)} />}
      {modal?.type === 'cancel' && <ReasonModal title="Cancel trial" sub={`${modal.row.tenant} — ends the trial now.`} label="Reason" danger submitLabel="Cancel trial" onSubmit={(reason) => api('trials.cancel', modal.row.id, { reason: reason || 'Cancelled by admin' })} onClose={() => setModal(null)} />}
      {modal?.type === 'extend' && <ExtendModal tenant={modal.row} onSubmit={(days) => api('trials.extend', modal.row.id, { days })} onClose={() => setModal(null)} />}
      {modal?.type === 'convert' && <ConvertModal tenant={modal.row} plans={plans} onSubmit={(plan_id, billing_cycle) => api('trials.convert', modal.row.id, { plan_id, billing_cycle })} onClose={() => setModal(null)} />}
    </div>
  );
}

Onboarding.layout = (page) => (
  <App title="Onboarding" railTitle="Onboarding" rail={<OnboardingRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
