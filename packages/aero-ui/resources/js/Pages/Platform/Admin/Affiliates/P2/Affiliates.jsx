import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbBulkBar, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './affiliates.css';

/* ---------------- glyphs ---------------- */
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  pay: svg(<><circle cx="12" cy="12" r="9" /><path d="M12 8v8M8 12h8" /></>),
  cog: svg(<><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 6.8 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3 13.6H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 6.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 4.6h0A1.6 1.6 0 0 0 11 3.1V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 2.7 1.1l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z" /></>),
};

/* ---------------- domain maps ---------------- */
const AFF_STATUS = {
  pending: { label: 'Pending', color: 'var(--aeos-warning)', cls: 'af-s-pend' },
  approved: { label: 'Approved', color: 'var(--aeos-success)', cls: 'af-s-appr' },
  suspended: { label: 'Suspended', color: 'var(--aeos-danger)', cls: 'af-s-susp' },
  rejected: { label: 'Rejected', color: 'var(--aeos-text-muted)', cls: 'af-s-rej' },
};
const PAY_STATUS = {
  pending: { label: 'Pending', color: 'var(--aeos-warning)', cls: 'af-s-pend' },
  processing: { label: 'Processing', color: 'var(--aeos-primary)', cls: 'af-s-proc' },
  completed: { label: 'Completed', color: 'var(--aeos-success)', cls: 'af-s-appr' },
  failed: { label: 'Failed', color: 'var(--aeos-danger)', cls: 'af-s-susp' },
};
const REF_STATUS = { clicked: 'Clicked', registered: 'Registered', converted: 'Converted', refunded: 'Refunded' };
const METHOD = { bank_transfer: 'Bank', paypal: 'PayPal', stripe: 'Stripe' };

const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const money = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const money2 = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const moneyK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : money(n));
const commissionLabel = (a) => (a.commission_type === 'fixed' ? `${money(a.fixed_commission)} flat` : `${a.commission_rate}%`);
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const fmtShort = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };
const ago = (s) => { if (!s) return '—'; const d = (Date.now() - new Date(s).getTime()) / 1000; if (d < 86400) return `${Math.max(1, Math.round(d / 3600))}h ago`; if (d < 86400 * 30) return `${Math.round(d / 86400)}d ago`; return fmtShort(s); };

/* ---------------- api helper ---------------- */
const xsrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
const api = (method, url, body) => fetch(url, {
  method, credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
  body: body ? JSON.stringify(body) : undefined,
}).then((r) => (r.ok ? r.json().catch(() => ({})) : r.json().then((j) => Promise.reject(new Error(j.message || `HTTP ${r.status}`)), () => Promise.reject(new Error(`HTTP ${r.status}`)))));
const getJson = (url) => fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))));
const reload = () => router.reload({ only: ['overview'] });

/* ---------------- rail ---------------- */
function AffiliatesRail({ overview }) {
  const o = overview ?? {};
  const s = o.stats ?? {};
  const queue = o.queue ?? [];
  const pending = (o.affiliates ?? []).filter((a) => a.status === 'pending').slice(0, 5);
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Program</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Active affiliates</span><b>{s.approved ?? 0}</b></div>
          <div className="pc-rail__row"><span>Conversion</span><b>{s.conversion_rate ?? 0}%</b></div>
          <div className="pc-rail__row"><span>Commission due</span><b>{moneyK(s.pending_commission)}</b></div>
          <div className="pc-rail__row"><span>Paid (LTD)</span><b>{moneyK(s.paid_ltd)}</b></div>
        </div>
      </div>
      {queue.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Payouts due</div>
          <div className="af-railq">
            {queue.slice(0, 5).map((q) => (
              <div key={q.id} className="af-railq__it"><span className="af-railq__nm">{q.name}</span><span className="af-money">{money(q.pending)}</span></div>
            ))}
          </div>
        </div>
      )}
      {pending.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Pending approval</div>
          <div className="af-railq">
            {pending.map((a) => <div key={a.id} className="af-railq__it"><span className="af-railq__nm">{a.name}</span><span className="af-railq__sub">{fmtShort(a.created_at)}</span></div>)}
          </div>
        </div>
      )}
    </div>
  );
}

/* ---------------- affiliate form modal ---------------- */
function AffiliateFormModal({ affiliate, onClose, onDone }) {
  const editing = !!affiliate;
  const [d, setD] = useState(() => ({
    name: affiliate?.name ?? '', email: affiliate?.email ?? '', phone: affiliate?.phone ?? '',
    company_name: affiliate?.company ?? '', website: affiliate?.website ?? '',
    commission_rate: affiliate?.commission_rate ?? 20, commission_type: affiliate?.commission_type ?? 'percentage',
    cookie_days: affiliate?.cookie_days ?? 30, minimum_payout: affiliate?.minimum_payout ?? 50, auto_approve: false,
  }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const submit = (e) => {
    e.preventDefault();
    if (!editing && (!d.name.trim() || !d.email.trim())) { setErr('Name and email are required.'); return; }
    setBusy(true); setErr(null);
    const req = editing
      ? api('PUT', `/affiliates/${affiliate.id}`, { name: d.name, phone: d.phone, company_name: d.company_name, website: d.website, commission_rate: Number(d.commission_rate), commission_type: d.commission_type, cookie_days: Number(d.cookie_days), minimum_payout: Number(d.minimum_payout) })
      : api('POST', '/affiliates', { ...d, commission_rate: Number(d.commission_rate) });
    req.then(() => onDone(editing ? 'Affiliate updated.' : 'Affiliate created.')).catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? `Edit ${affiliate.name}` : 'New affiliate'}</h2>
        <div className="pc-modal__sub">{editing ? 'Update contact details and commission terms.' : 'Recruit a partner. A unique referral code is generated automatically.'}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="af-form-grid">
            <div className="pc-field"><label className="pc-field__label">Name *</label><input className="pc-input" value={d.name} onChange={(e) => set('name', e.target.value)} disabled={editing} autoFocus /></div>
            <div className="pc-field"><label className="pc-field__label">Email *</label><input className="pc-input" type="email" value={d.email} onChange={(e) => set('email', e.target.value)} disabled={editing} /></div>
            <div className="pc-field"><label className="pc-field__label">Company</label><input className="pc-input" value={d.company_name} onChange={(e) => set('company_name', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Website</label><input className="pc-input" value={d.website} onChange={(e) => set('website', e.target.value)} placeholder="https://" /></div>
            <div className="pc-field"><label className="pc-field__label">Commission type</label>
              <select className="pc-input" value={d.commission_type} onChange={(e) => set('commission_type', e.target.value)}><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select>
            </div>
            <div className="pc-field"><label className="pc-field__label">{d.commission_type === 'fixed' ? 'Fixed amount ($)' : 'Rate (%)'}</label><input className="pc-input" type="number" value={d.commission_rate} onChange={(e) => set('commission_rate', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Cookie window (days)</label><input className="pc-input" type="number" value={d.cookie_days} onChange={(e) => set('cookie_days', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Minimum payout ($)</label><input className="pc-input" type="number" value={d.minimum_payout} onChange={(e) => set('minimum_payout', e.target.value)} /></div>
          </div>
          {!editing && <label className="af-check"><input type="checkbox" checked={d.auto_approve} onChange={(e) => set('auto_approve', e.target.checked)} /> Approve immediately</label>}
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : (editing ? 'Save changes' : 'Create affiliate')}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- reason modal (reject / suspend) ---------------- */
function ReasonModal({ title, verb, danger, onClose, onConfirm }) {
  const [reason, setReason] = useState(''); const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); setBusy(true); onConfirm(reason.trim() || null); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{title}</h2>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Reason (optional, recorded on the affiliate)</label><textarea className="pc-input" value={reason} onChange={(e) => setReason(e.target.value)} autoFocus /></div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className={`pc-btn ${danger ? 'pc-btn--danger' : 'pc-btn--primary'}`} disabled={busy}>{busy ? '…' : verb}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- complete payout modal ---------------- */
function CompletePayoutModal({ payout, onClose, onConfirm }) {
  const [txn, setTxn] = useState(''); const [busy, setBusy] = useState(false);
  const submit = (e) => { e.preventDefault(); if (!txn.trim()) return; setBusy(true); onConfirm(txn.trim()); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Complete payout · {money2(payout.amount)}</h2>
        <div className="pc-modal__sub">Marks the payout paid, moves the affiliate’s pending balance to paid, and settles the related commissions.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Transaction reference *</label><input className="pc-input" value={txn} onChange={(e) => setTxn(e.target.value)} placeholder="e.g. PP-8842-XY" autoFocus /></div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !txn.trim()}>{busy ? 'Completing…' : 'Mark completed'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- settings modal ---------------- */
function SettingsModal({ settings, onClose, onDone }) {
  const [d, setD] = useState(() => ({
    enabled: settings?.enabled ?? true,
    default_commission_rate: settings?.default_commission_rate ?? 10,
    default_commission_type: settings?.default_commission_type ?? 'percentage',
    cookie_days: settings?.cookie_days ?? 30, minimum_payout: settings?.minimum_payout ?? 50,
    auto_approve_affiliates: settings?.auto_approve_affiliates ?? false,
  }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const submit = (e) => {
    e.preventDefault(); setBusy(true); setErr(null);
    api('PUT', '/affiliates/settings', { ...d, default_commission_rate: Number(d.default_commission_rate), cookie_days: Number(d.cookie_days), minimum_payout: Number(d.minimum_payout) })
      .then(() => onDone('Program settings saved.')).catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Program settings</h2>
        <div className="pc-modal__sub">Defaults applied to new affiliate applications.</div>
        <form className="pc-form" onSubmit={submit}>
          <label className="af-check"><input type="checkbox" checked={d.enabled} onChange={(e) => set('enabled', e.target.checked)} /> Affiliate program enabled</label>
          <label className="af-check"><input type="checkbox" checked={d.auto_approve_affiliates} onChange={(e) => set('auto_approve_affiliates', e.target.checked)} /> Auto-approve new applications</label>
          <div className="af-form-grid">
            <div className="pc-field"><label className="pc-field__label">Default type</label><select className="pc-input" value={d.default_commission_type} onChange={(e) => set('default_commission_type', e.target.value)}><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></div>
            <div className="pc-field"><label className="pc-field__label">Default rate</label><input className="pc-input" type="number" value={d.default_commission_rate} onChange={(e) => set('default_commission_rate', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Cookie window (days)</label><input className="pc-input" type="number" value={d.cookie_days} onChange={(e) => set('cookie_days', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Minimum payout ($)</label><input className="pc-input" type="number" value={d.minimum_payout} onChange={(e) => set('minimum_payout', e.target.value)} /></div>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : 'Save settings'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function AffiliateDrawer({ aff, onClose, actions, canPayout }) {
  const [tab, setTab] = useState('overview');
  const [detail, setDetail] = useState(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    setTab('overview'); setDetail(null); setFailed(false);
    if (!aff) return undefined;
    const ac = new AbortController();
    Promise.all([getJson(`/affiliates/${aff.id}/referrals?perPage=8`), getJson(`/affiliates/${aff.id}/payouts?perPage=8`)])
      .then(([refs, pays]) => setDetail({ referrals: refs.data ?? [], payouts: pays.data ?? [] }))
      .catch(() => setFailed(true));
    return () => ac.abort();
  }, [aff?.id]); // eslint-disable-line react-hooks/exhaustive-deps
  if (!aff) return null;
  const st = AFF_STATUS[aff.status] ?? {};
  const tabs = [{ id: 'overview', label: 'Overview' }, { id: 'referrals', label: 'Referrals' }, { id: 'payouts', label: 'Payouts' }];
  const copy = () => { navigator.clipboard?.writeText(aff.referral_url); actions.toast('Referral link copied.'); };
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Affiliate — ${aff.name}`} tabs={tabs} activeTab={tab} onTab={setTab}
      head={(
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(aff.name)}</div>
            <div><div className="sc-dr-title">{aff.name}</div><div className="sc-dr-code">{aff.email}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{st.label ?? aff.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">Pending</div><div className="v">{money(aff.pending_earnings)}</div></div>
            <div className="sc-dr-kpi"><div className="l">Lifetime</div><div className="v">{money(aff.total_earnings)}</div></div>
          </div>
          <div className="sc-dr-acts">
            {canPayout && aff.eligible && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.createPayout(aff)}>Create payout · {money(aff.pending_earnings)}</button>}
            {actions.canApprove && aff.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.approve(aff)}>Approve</button>}
            {actions.canSuspend && aff.status === 'approved' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.suspend(aff)}>Suspend</button>}
            {actions.canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.edit(aff)}>Edit</button>}
            <button type="button" className="pc-btn pc-btn--sm" onClick={copy}>Copy link</button>
          </div>
        </>
      )}
    >
      {tab === 'overview' && (
        <div>
          <div className="af-linkrow"><span className="pc-drow__k">Referral link</span><code className="af-code">{aff.referral_url}</code></div>
          <div className="pc-drow"><span className="pc-drow__k">Code</span><span className="pc-drow__v"><span className="af-code">{aff.referral_code}</span></span></div>
          <div className="pc-drow"><span className="pc-drow__k">Company</span><span className="pc-drow__v">{aff.company ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Website</span><span className="pc-drow__v">{aff.website ?? '—'}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Commission</span><span className="pc-drow__v">{commissionLabel(aff)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Cookie window</span><span className="pc-drow__v">{aff.cookie_days} days</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Payout method</span><span className="pc-drow__v">{METHOD[aff.payout_method] ?? '— not set'} · min {money(aff.minimum_payout)}</span></div>
          <div className="sc-dr-sec">Earnings</div>
          <div className="pc-drow"><span className="pc-drow__k">Pending</span><span className="pc-drow__v af-money">{money2(aff.pending_earnings)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Paid to date</span><span className="pc-drow__v">{money2(aff.paid_earnings)}</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Referrals</span><span className="pc-drow__v">{aff.total_referrals} · {aff.successful_referrals} converted ({aff.conversion_rate}%)</span></div>
          <div className="pc-drow"><span className="pc-drow__k">Approved</span><span className="pc-drow__v">{fmtDate(aff.approved_at)}</span></div>
        </div>
      )}
      {tab === 'referrals' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load referrals.' : 'Loading…'}</div>
          : detail.referrals.length === 0 ? <div className="wb-empty">No referrals recorded yet.</div>
            : <table className="sc-minit"><tbody>{detail.referrals.map((r) => (
              <tr key={r.id}><td><div className="pc-mname">{r.tenant_email ?? r.landing_page ?? 'visitor'}</div><div className="sc-kind">{fmtShort(r.created_at)}</div></td><td className="pc-r"><span className={`pc-chip ${r.status === 'converted' ? 'af-s-appr' : ''}`}>{REF_STATUS[r.status] ?? r.status}</span></td><td className="pc-r">{r.commission_amount > 0 ? <span className="af-money">{money2(r.commission_amount)}</span> : <span className="pc-free">—</span>}</td></tr>
            ))}</tbody></table>
      )}
      {tab === 'payouts' && (
        detail == null ? <div className="wb-empty">{failed ? 'Could not load payouts.' : 'Loading…'}</div>
          : detail.payouts.length === 0 ? <div className="wb-empty">No payouts yet.</div>
            : <table className="sc-minit"><tbody>{detail.payouts.map((p) => (
              <tr key={p.id}><td><div className="pc-mname">{money2(p.amount)}</div><div className="sc-kind">{METHOD[p.payout_method] ?? p.payout_method} · {fmtShort(p.created_at)}</div></td><td className="pc-r"><span className={`pc-chip ${PAY_STATUS[p.status]?.cls ?? ''}`}>{PAY_STATUS[p.status]?.label ?? p.status}</span></td></tr>
            ))}</tbody></table>
      )}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Affiliates({ overview }) {
  const toast = useToast();
  const o = overview ?? {};
  const list = useMemo(() => o.affiliates ?? [], [o.affiliates]);
  const payouts = useMemo(() => o.payouts ?? [], [o.payouts]);
  const s = o.stats ?? {};
  const sp = o.sparks ?? {};

  const canView = useHRMAC('affiliate-program.affiliates.view');
  const canCreate = useHRMAC('affiliate-program.affiliates.create');
  const canEdit = useHRMAC('affiliate-program.affiliates.update');
  const canDelete = useHRMAC('affiliate-program.affiliates.delete');
  const canApprove = useHRMAC('affiliate-program.affiliates.approve');
  const canSuspend = useHRMAC('affiliate-program.affiliates.suspend');
  const canPayout = useHRMAC('affiliate-program.payouts.create');
  const canProcess = useHRMAC('affiliate-program.payouts.process');
  const canComplete = useHRMAC('affiliate-program.payouts.complete');
  const canSettings = useHRMAC('affiliate-program.affiliate-settings.update');

  const [tab, setTab] = useState('affiliates');
  const [drawer, setDrawer] = useState(null);
  const [formAff, setFormAff] = useState(undefined);
  const [rejectAff, setRejectAff] = useState(null);
  const [suspendAff, setSuspendAff] = useState(null);
  const [completeP, setCompleteP] = useState(null);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const ctx = useCtxMenu();

  /* actions */
  const approve = (a) => api('POST', `/affiliates/${a.id}/approve`).then(() => { toast.success(`${a.name} approved.`); reload(); }).catch((e) => toast.error(e.message));
  const remove = (a) => { if (window.confirm(`Delete ${a.name}? This cannot be undone.`)) api('DELETE', `/affiliates/${a.id}`).then(() => { toast.success('Affiliate deleted.'); reload(); }).catch((e) => toast.error(e.message)); };
  const createPayout = (a) => api('POST', `/affiliates/${a.id}/payout`, {}).then(() => { toast.success(`Payout created for ${a.name}.`); reload(); }).catch((e) => toast.error(e.message));
  const doReject = (reason) => { const a = rejectAff; api('POST', `/affiliates/${a.id}/reject`, { reason }).then(() => { setRejectAff(null); toast.success('Affiliate rejected.'); reload(); }).catch((e) => { setRejectAff(null); toast.error(e.message); }); };
  const doSuspend = (reason) => { const a = suspendAff; api('POST', `/affiliates/${a.id}/suspend`, { reason }).then(() => { setSuspendAff(null); toast.success('Affiliate suspended.'); reload(); }).catch((e) => { setSuspendAff(null); toast.error(e.message); }); };
  const processPayout = (p) => api('POST', `/affiliates/payouts/${p.id}/process`).then(() => { toast.success('Payout processing.'); reload(); }).catch((e) => toast.error(e.message));
  const doComplete = (txn) => { const p = completeP; api('POST', `/affiliates/payouts/${p.id}/complete`, { transaction_id: txn }).then(() => { setCompleteP(null); toast.success('Payout completed.'); reload(); }).catch((e) => { setCompleteP(null); toast.error(e.message); }); };
  const runPayouts = () => {
    const ids = (o.queue ?? []).map((q) => q.id);
    if (ids.length === 0) { toast.info('No affiliates are eligible for payout right now.'); return; }
    if (!window.confirm(`Create payouts for ${ids.length} eligible affiliate(s)?`)) return;
    api('POST', '/affiliates/bulk', { action: 'create_payout', affiliate_ids: ids }).then((j) => { toast.success(j.message || 'Payouts created.'); reload(); }).catch((e) => toast.error(e.message));
  };
  const bulkAff = (action) => {
    const ids = wb.selectedRows.map((r) => r.id);
    api('POST', '/affiliates/bulk', { action, affiliate_ids: ids }).then((j) => { toast.success(j.message || 'Done.'); wb.clearSelection(); reload(); }).catch((e) => toast.error(e.message));
  };

  const actions = { canApprove, canSuspend, canEdit, approve, suspend: setSuspendAff, createPayout, edit: (a) => { setDrawer(null); setFormAff(a); }, toast: toast.success };

  /* affiliates workbench */
  const wb = useWorkbench({
    rows: list, getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.email} ${r.company ?? ''} ${r.referral_code}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'approved', label: 'Approved', test: (r) => r.status === 'approved' },
      { id: 'pending', label: 'Pending', test: (r) => r.status === 'pending' },
      { id: 'payable', label: 'Payable now', test: (r) => r.eligible },
      { id: 'top', label: 'Top earners', test: (r) => r.total_earnings > 0 },
      { id: 'suspended', label: 'Suspended', test: (r) => r.status === 'suspended' },
    ],
    facets: {
      status: { value: 'all', test: (r, v) => r.status === v },
      type: { value: 'all', test: (r, v) => r.commission_type === v },
      method: { value: 'all', test: (r, v) => r.payout_method === v },
    },
    sortKey: 'total_earnings', sortVal: (r, k) => (['total_earnings', 'pending_earnings', 'total_referrals', 'conversion_rate'].includes(k) ? (r[k] ?? -1) : String(r[k] ?? '')),
    perPage: 12, storageKey: 'platform.affiliates',
  });
  useEffect(() => { wb.toggleSort('total_earnings'); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* payouts workbench */
  const wbp = useWorkbench({
    rows: payouts, getId: (r) => r.id,
    searchText: (r) => `${r.affiliate ?? ''} ${r.transaction_reference ?? ''} ${r.status}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'pending', label: 'Pending', test: (r) => r.status === 'pending' },
      { id: 'processing', label: 'Processing', test: (r) => r.status === 'processing' },
      { id: 'completed', label: 'Completed', test: (r) => r.status === 'completed' },
    ],
    facets: { status: { value: 'all', test: (r, v) => r.status === v } },
    sortKey: 'created_at', sortVal: (r, k) => (k === 'amount' ? r.amount : new Date(r[k] ?? 0).getTime()),
    perPage: 12, storageKey: 'platform.affpayouts',
  });

  const rowMenu = (r) => [
    ...(canView ? [{ label: 'Open', onClick: () => setDrawer(r) }] : []),
    ...(canApprove && r.status === 'pending' ? [{ label: 'Approve', onClick: () => approve(r) }] : []),
    ...(canApprove && r.status === 'pending' ? [{ label: 'Reject…', onClick: () => setRejectAff(r) }] : []),
    ...(canSuspend && r.status === 'approved' ? [{ label: 'Suspend…', onClick: () => setSuspendAff(r) }] : []),
    ...(canPayout && r.eligible ? [{ label: `Create payout · ${money(r.pending_earnings)}`, onClick: () => createPayout(r) }] : []),
    ...(canEdit ? [{ label: 'Edit…', onClick: () => setFormAff(r) }] : []),
    ...(canDelete ? ['sep', { label: 'Delete', danger: true, onClick: () => remove(r) }] : []),
  ];

  const affColumns = [
    { key: 'name', label: 'Affiliate', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.name)}</div><div><div className="pc-mname">{r.name}</div><div className="sc-kind">{r.email}</div></div></div> },
    { key: 'referral_code', label: 'Code', hideSm: true, render: (r) => <span className="af-code">{r.referral_code}</span> },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${AFF_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: AFF_STATUS[r.status]?.color }} />{AFF_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'commission', label: 'Commission', hideSm: true, render: (r) => commissionLabel(r) },
    { key: 'total_referrals', label: 'Refs', align: 'r', sortable: true, render: (r) => <span className="num">{r.total_referrals}</span> },
    { key: 'conversion_rate', label: 'Conv.', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="num">{r.successful_referrals}</span> },
    { key: 'pending_earnings', label: 'Pending', align: 'r', sortable: true, render: (r) => (r.pending_earnings > 0 ? <span className="af-money">{money(r.pending_earnings)}</span> : <span className="pc-free">—</span>) },
    { key: 'total_earnings', label: 'Lifetime', align: 'r', sortable: true, render: (r) => <span className="num">{money(r.total_earnings)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button> },
  ];

  const payColumns = [
    { key: 'affiliate', label: 'Affiliate', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.affiliate)}</div><div className="pc-mname">{r.affiliate ?? '—'}</div></div> },
    { key: 'amount', label: 'Amount', align: 'r', sortable: true, render: (r) => <span className="af-money">{money2(r.amount)}</span> },
    { key: 'payout_method', label: 'Method', hideSm: true, render: (r) => METHOD[r.payout_method] ?? r.payout_method ?? '—' },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${PAY_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: PAY_STATUS[r.status]?.color }} />{PAY_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'transaction_reference', label: 'Reference', hideSm: true, render: (r) => (r.transaction_reference ? <span className="af-code">{r.transaction_reference}</span> : <span className="pc-free">—</span>) },
    { key: 'created_at', label: 'Created', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtShort(r.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 120, render: (r) => (
      <div className="af-payacts">
        {canProcess && r.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => processPayout(r)}>Process</button>}
        {canComplete && (r.status === 'processing' || r.status === 'pending') && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setCompleteP(r)}>Complete</button>}
        {r.status === 'completed' && <span className="sc-kind">{fmtShort(r.completed_at)}</span>}
      </div>
    ) },
  ];

  const kpis = [
    { label: 'Active affiliates', value: s.approved ?? 0, delta: `${s.total ?? 0} total`, up: true, spark: sp.affiliates, color: 'var(--aeos-success)' },
    { label: 'Pending approval', value: s.pending ?? 0, delta: 'awaiting review', spark: sp.affiliates, color: 'var(--aeos-warning)' },
    { label: 'Referrals', value: s.total_referrals ?? 0, delta: `${s.conversions ?? 0} converted`, up: true, spark: sp.referrals, color: 'var(--aeos-primary)' },
    { label: 'Conversion rate', value: `${s.conversion_rate ?? 0}%`, delta: 'clicks → paid', up: (s.conversion_rate ?? 0) > 0, spark: sp.conversions, color: '#22d3ee' },
    { label: 'Commission due', value: moneyK(s.pending_commission), delta: `${(o.queue ?? []).length} payable now`, spark: sp.conversions, color: 'var(--aeos-warning)' },
    { label: 'Paid out (LTD)', value: moneyK(s.paid_ltd), delta: 'lifetime', up: true, spark: sp.paid, color: 'var(--aeos-success)' },
  ];

  const statusMix = o.statusMix ?? [];
  const top = o.top ?? [];
  const topMax = Math.max(1, ...top.map((t) => t.earnings));
  const trend = o.trend ?? {};
  const queue = o.queue ?? [];

  const exportCsv = () => {
    const header = 'name,email,company,code,status,commission,referrals,conversions,pending,lifetime';
    const lines = list.map((r) => [r.name, r.email, r.company ?? '', r.referral_code, r.status, commissionLabel(r), r.total_referrals, r.successful_referrals, r.pending_earnings, r.total_earnings].map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `affiliates-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc af">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Growth &amp; Marketing · Partner Program</div>
          <h1 className="pc-title">Affiliates</h1>
          <div className="pc-sub">Run the whole referral program from one console — recruit and approve affiliates, track referrals, and process commission payouts end-to-end.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [{ label: 'Export CSV — affiliates', onClick: exportCsv }, { label: 'Print this view', onClick: () => window.print() }])}>{Glyph.export}<span>Export</span></button>
          {canSettings && <button type="button" className="pc-btn" onClick={() => setSettingsOpen(true)}>{Glyph.cog}<span>Settings</span></button>}
          {canPayout && <button type="button" className="pc-btn" onClick={runPayouts}>{Glyph.pay}<span>Run payouts</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setFormAff(null)}>{Glyph.plus}<span>New affiliate</span></button>}
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
              {Array.isArray(c.spark) && c.spark.length > 1 && <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color} /></div>}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* analytics band */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Commission — earned vs paid</h2><div className="pc-panel-h__sub">Last 6 months</div></div></div>
          <AreaTrend series={[{ key: 'earned', label: 'Earned', color: 'var(--aeos-primary)', values: trend.earned ?? [] }, { key: 'paid', label: 'Paid', color: 'var(--aeos-success)', values: trend.paid ?? [] }]} labels={trend.labels ?? []} height={190} ariaLabel="Commission earned vs paid" />
          <div className="tn-trend-foot">
            <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />Earned <b>{money(Math.round((trend.earned ?? []).reduce((a, b) => a + b, 0)))}</b></span>
            <span className="li"><span className="d" style={{ background: 'var(--aeos-success)' }} />Paid <b>{money(Math.round((trend.paid ?? []).reduce((a, b) => a + b, 0)))}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Program health</h2><div className="pc-panel-h__sub">{s.total ?? 0} affiliates</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={statusMix.map((m) => ({ color: AFF_STATUS[m.status]?.color ?? 'var(--aeos-text-muted)', value: m.count }))} centerValue={`${s.total ? Math.round((s.approved / s.total) * 100) : 0}%`} centerLabel="active" size={112} />
            <div className="sc-dl">
              {statusMix.filter((m) => m.count > 0).map((m) => (
                <button key={m.status} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === m.status ? 'all' : m.status)}>
                  <span className="d" style={{ background: AFF_STATUS[m.status]?.color ?? 'var(--aeos-text-muted)' }} />{m.label}<b>{m.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Top affiliates</h2><div className="pc-panel-h__sub">By commission earned</div></div></div>
          <div className="af-lb">
            {top.length === 0 && <div className="wb-empty">No earnings yet.</div>}
            {top.map((t, i) => (
              <button key={t.id} type="button" className="af-lbrow" onClick={() => { const a = list.find((x) => x.id === t.id); if (a) setDrawer(a); }}>
                <span className="af-lbrow__rk">{i + 1}</span>
                <span className="af-lbrow__who"><span className="af-lbrow__nm">{t.name}</span><span className="af-lbrow__track"><span className="af-lbrow__fill" style={{ width: `${Math.max((t.earnings / topMax) * 100, 4)}%` }} /></span></span>
                <span className="af-lbrow__amt">{money(t.earnings)}<small>{t.conversions} conv</small></span>
              </button>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* payout queue */}
      {queue.length > 0 && (
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Payouts due</h2><div className="pc-panel-h__sub">Eligible affiliates over their minimum</div></div><span className="sc-badge sc-badge--warn">{money(queue.reduce((a, b) => a + b.pending, 0))}</span></div>
          <div className="af-queue">
            {queue.map((q) => (
              <div key={q.id} className="af-qi">
                <div className="sc-av">{initials(q.name)}</div>
                <div className="af-qi__who"><b>{q.name}</b><span>{METHOD[q.payout_method] ?? q.payout_method} · min {money(q.minimum)}</span></div>
                <span className="af-money af-qi__amt">{money(q.pending)}</span>
                {canPayout && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => createPayout({ id: q.id, name: q.name })}>Pay</button>}
              </div>
            ))}
          </div>
        </CardBody></Card>
      )}

      {/* workbench */}
      <Card><CardBody>
        <div className="af-tabs">
          <button type="button" className={`af-tab${tab === 'affiliates' ? ' is-on' : ''}`} onClick={() => setTab('affiliates')}>Affiliates <span>{list.length}</span></button>
          <button type="button" className={`af-tab${tab === 'payouts' ? ' is-on' : ''}`} onClick={() => setTab('payouts')}>Payouts <span>{payouts.length}</span></button>
        </div>

        {tab === 'affiliates' ? (
          <>
            <WbToolbar>
              <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name, email, company or code…" ariaLabel="Search affiliates" />
              <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
                <option value="all">All statuses</option>{Object.keys(AFF_STATUS).map((k) => <option key={k} value={k}>{AFF_STATUS[k].label}</option>)}
              </select>
              <select className="pc-input sc-statusfilter" value={wb.facetValues.type} onChange={(e) => wb.setFacet('type', e.target.value)} aria-label="Commission filter">
                <option value="all">Any commission</option><option value="percentage">Percentage</option><option value="fixed">Fixed</option>
              </select>
              <select className="pc-input sc-statusfilter" value={wb.facetValues.method} onChange={(e) => wb.setFacet('method', e.target.value)} aria-label="Payout method filter">
                <option value="all">Any method</option>{Object.entries(METHOD).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
              <WbColumns wb={wb} columns={affColumns} />
            </WbToolbar>
            <WbViews wb={wb} />
            <WbBulkBar wb={wb}>
              {canApprove && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkAff('approve')}>Approve</button>}
              {canSuspend && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkAff('suspend')}>Suspend</button>}
              {canPayout && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkAff('create_payout')}>Create payouts</button>}
              <button type="button" className="pc-btn pc-btn--sm" onClick={exportCsv}>Export</button>
            </WbBulkBar>
            <WbTable wb={wb} columns={affColumns} selectable={canApprove || canSuspend || canPayout} onRowClick={setDrawer} rowAriaLabel={(r) => `${r.name}, ${AFF_STATUS[r.status]?.label}`}
              empty={<>No affiliates match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>} />
            <WbFooter wb={wb} perOptions={[12, 25, 50]} />
          </>
        ) : (
          <>
            <WbToolbar>
              <WbSearch value={wbp.q} onChange={wbp.setQ} placeholder="Search affiliate or reference…" ariaLabel="Search payouts" />
              <select className="pc-input sc-statusfilter" value={wbp.facetValues.status} onChange={(e) => wbp.setFacet('status', e.target.value)} aria-label="Payout status filter">
                <option value="all">All statuses</option>{Object.keys(PAY_STATUS).map((k) => <option key={k} value={k}>{PAY_STATUS[k].label}</option>)}
              </select>
              <WbColumns wb={wbp} columns={payColumns} />
            </WbToolbar>
            <WbViews wb={wbp} />
            <WbTable wb={wbp} columns={payColumns} onRowClick={(r) => { const a = list.find((x) => x.id === r.affiliate_id); if (a) setDrawer(a); }}
              empty="No payouts recorded yet." />
            <WbFooter wb={wbp} perOptions={[12, 25, 50]} />
          </>
        )}
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <AffiliateDrawer aff={list.find((a) => a.id === drawer.id) ?? drawer} onClose={() => setDrawer(null)} actions={{ ...actions, createPayout: (a) => { createPayout(a); }, approve: (a) => { approve(a); }, suspend: (a) => { setDrawer(null); setSuspendAff(a); }, edit: (a) => { setDrawer(null); setFormAff(a); } }} canPayout={canPayout} />}
      {formAff !== undefined && <AffiliateFormModal affiliate={formAff} onClose={() => setFormAff(undefined)} onDone={(m) => { setFormAff(undefined); toast.success(m); reload(); }} />}
      {rejectAff && <ReasonModal title={`Reject ${rejectAff.name}`} verb="Reject" danger onClose={() => setRejectAff(null)} onConfirm={doReject} />}
      {suspendAff && <ReasonModal title={`Suspend ${suspendAff.name}`} verb="Suspend" danger onClose={() => setSuspendAff(null)} onConfirm={doSuspend} />}
      {completeP && <CompletePayoutModal payout={completeP} onClose={() => setCompleteP(null)} onConfirm={doComplete} />}
      {settingsOpen && <SettingsModal settings={o.settings} onClose={() => setSettingsOpen(false)} onDone={(m) => { setSettingsOpen(false); toast.success(m); reload(); }} />}
    </div>
  );
}

Affiliates.layout = (page) => (
  <App title="Affiliates" railTitle="Growth &amp; Marketing" rail={<AffiliatesRail overview={page.props.overview} />}>
    {page}
  </App>
);
