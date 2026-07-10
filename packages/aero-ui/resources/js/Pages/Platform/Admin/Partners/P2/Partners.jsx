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
import './partners.css';

/* ---------------- glyphs ---------------- */
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  pay: svg(<><circle cx="12" cy="12" r="9" /><path d="M12 8v8M8 12h8" /></>),
};

/* ---------------- domain maps ---------------- */
const P_STATUS = {
  active: { label: 'Active', color: 'var(--aeos-success)', cls: 'pr-s-ok' },
  pending: { label: 'Pending', color: 'var(--aeos-warning)', cls: 'pr-s-pend' },
  suspended: { label: 'Suspended', color: 'var(--aeos-danger)', cls: 'pr-s-bad' },
};
const C_STATUS = {
  pending: { label: 'Pending', color: 'var(--aeos-warning)', cls: 'pr-s-pend' },
  approved: { label: 'Approved', color: 'var(--aeos-primary)', cls: 'pr-s-appr' },
  paid: { label: 'Paid', color: 'var(--aeos-success)', cls: 'pr-s-ok' },
};
const T_STATUS = { active: 'Active', trial: 'Trial', suspended: 'Suspended', pending: 'Pending', archived: 'Archived', cancelled: 'Cancelled' };

const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const money = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const money2 = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const moneyK = (n) => (Math.abs(n) >= 1000 ? `$${(n / 1000).toFixed(1)}k` : money(n));
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const fmtShort = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };

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
function PartnersRail({ overview }) {
  const o = overview ?? {};
  const s = o.stats ?? {};
  const queue = o.queue ?? [];
  const pending = (o.partners ?? []).filter((p) => p.status === 'pending').slice(0, 4);
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Channel pulse</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Active partners</span><b>{s.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Managed tenants</span><b>{s.managed_tenants ?? 0}</b></div>
          <div className="pc-rail__row"><span>Channel MRR</span><b>{moneyK(s.channel_mrr)}</b></div>
          <div className="pc-rail__row"><span>Commission owed</span><b>{moneyK(s.owed)}</b></div>
          <div className="pc-rail__row"><span>Paid (90d)</span><b>{moneyK(s.paid_90d)}</b></div>
        </div>
      </div>
      {pending.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Awaiting approval</div>
          <div className="pr-railq">
            {pending.map((p) => <div key={p.id} className="pr-railq__it"><span className="pr-railq__nm">{p.name}</span><span className="pr-railq__sub">{fmtShort(p.created_at)}</span></div>)}
          </div>
        </div>
      )}
      {queue.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Payouts due</div>
          <div className="pr-railq">
            {queue.slice(0, 5).map((q) => (
              <div key={q.id} className="pr-railq__it"><span className="pr-railq__nm">{q.name}</span><span className="pr-money">{money(q.owed)}</span></div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/* ---------------- partner form modal ---------------- */
function PartnerFormModal({ partner, onClose, onDone }) {
  const editing = !!partner;
  const [d, setD] = useState(() => ({
    name: partner?.name ?? '', email: partner?.email ?? '',
    rate_pct: partner?.rate_pct ?? 15, portal_slug: partner?.portal_slug ?? '',
  }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const slugify = (v) => v.toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
  const submit = (e) => {
    e.preventDefault();
    if (!d.name.trim() || !d.email.trim()) { setErr('Name and email are required.'); return; }
    const pct = Number(d.rate_pct);
    if (Number.isNaN(pct) || pct < 0 || pct > 100) { setErr('Commission rate must be between 0 and 100%.'); return; }
    setBusy(true); setErr(null);
    const body = { name: d.name.trim(), email: d.email.trim(), commission_rate: pct / 100, portal_slug: d.portal_slug ? slugify(d.portal_slug) : null };
    const req = editing ? api('PUT', `/partners/${partner.id}`, body) : api('POST', '/partners', body);
    req.then(() => onDone(editing ? 'Partner updated.' : 'Partner created — pending approval.')).catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? `Edit ${partner.name}` : 'New partner'}</h2>
        <div className="pc-modal__sub">{editing ? 'Update the company details and commission terms.' : 'Recruit a reseller. New partners start as Pending until approved.'}</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pr-form-grid">
            <div className="pc-field"><label className="pc-field__label">Company name *</label><input className="pc-input" value={d.name} onChange={(e) => set('name', e.target.value)} autoFocus /></div>
            <div className="pc-field"><label className="pc-field__label">Contact email *</label><input className="pc-input" type="email" value={d.email} onChange={(e) => set('email', e.target.value)} /></div>
            <div className="pc-field">
              <label className="pc-field__label">Commission rate — {Number(d.rate_pct) || 0}%</label>
              <div className="pr-raterow">
                <input type="range" min="0" max="40" step="1" value={d.rate_pct} onChange={(e) => set('rate_pct', e.target.value)} aria-label="Commission rate slider" />
                <input className="pc-input pr-ratenum" type="number" min="0" max="100" value={d.rate_pct} onChange={(e) => set('rate_pct', e.target.value)} aria-label="Commission rate percent" />
              </div>
            </div>
            <div className="pc-field">
              <label className="pc-field__label">Portal slug</label>
              <input className="pc-input" value={d.portal_slug} onChange={(e) => set('portal_slug', e.target.value)} placeholder="acme-cloud" />
              {d.portal_slug && <span className="pc-field__hint pr-hint">partners.aeos365.com/{slugify(d.portal_slug)}</span>}
            </div>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : (editing ? 'Save changes' : 'Create partner')}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- payout confirm modal (type-to-confirm) ---------------- */
function PayoutModal({ partner, onClose, onDone }) {
  const [typed, setTyped] = useState(''); const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const ok = typed.trim().toLowerCase() === partner.name.trim().toLowerCase();
  const submit = (e) => {
    e.preventDefault(); if (!ok) return;
    setBusy(true); setErr(null);
    api('POST', `/partners/${partner.id}/commissions/payout`)
      .then((j) => onDone(`Payout processed — ${j.paid_count} commission${j.paid_count === 1 ? '' : 's'} marked paid.`))
      .catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Pay commissions · {partner.name}</h2>
        <div className="pc-modal__sub">{partner.owed_entries} unpaid entr{partner.owed_entries === 1 ? 'y' : 'ies'} totalling <b className="pr-money">{money2(partner.owed)}</b>.</div>
        <div className="pr-note">Marks every pending and approved entry <b>paid</b> and stamps <code>paid_at</code>, in one transaction, fully audited. This is a ledger transition — no live money movement.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Type <b>{partner.name}</b> to confirm</label><input className="pc-input" value={typed} onChange={(e) => setTyped(e.target.value)} autoFocus /></div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !ok}>{busy ? 'Processing…' : `Pay ${money(partner.owed)}`}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- reassign tenant modal ---------------- */
function ReassignModal({ tenant, partners, currentId, onClose, onDone }) {
  const [pid, setPid] = useState(''); const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const options = partners.filter((p) => p.status === 'active' && p.id !== currentId);
  const submit = (e) => {
    e.preventDefault(); setBusy(true); setErr(null);
    api('POST', `/partners/tenants/${tenant.id}/reassign`, { partner_id: pid === 'detach' ? null : Number(pid) })
      .then(() => onDone(pid === 'detach' ? `${tenant.name} detached — now direct-managed.` : 'Tenant reassigned.'))
      .catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Reassign {tenant.name}</h2>
        <div className="pc-modal__sub">Move this tenant to another partner's book, or detach it to direct management.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label">New owner</label>
            <select className="pc-input" value={pid} onChange={(e) => setPid(e.target.value)} autoFocus>
              <option value="" disabled>Choose…</option>
              {options.map((p) => <option key={p.id} value={p.id}>{p.name} — {p.rate_pct}%</option>)}
              <option value="detach">— Detach (direct-managed) —</option>
            </select>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !pid}>{busy ? 'Moving…' : 'Reassign'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- drawer ---------------- */
function PartnerDrawer({ partner, ledger, onClose, actions, can }) {
  const [tab, setTab] = useState('ledger');
  const [tenants, setTenants] = useState(null);
  const [tenantsFailed, setTenantsFailed] = useState(false);
  const [portal, setPortal] = useState(() => ({ brand_color: '#4f46e5', welcome: '', support_email: '', ...(partner.portal_config ?? {}) }));
  const [portalBusy, setPortalBusy] = useState(false);
  useEffect(() => {
    setTab('ledger'); setTenants(null); setTenantsFailed(false);
    setPortal({ brand_color: '#4f46e5', welcome: '', support_email: '', ...(partner.portal_config ?? {}) });
    getJson(`/partners/${partner.id}/tenants`).then((j) => setTenants(j.tenants ?? [])).catch(() => setTenantsFailed(true));
  }, [partner.id]); // eslint-disable-line react-hooks/exhaustive-deps
  const st = P_STATUS[partner.status] ?? {};
  const rows = ledger.filter((c) => c.partner_id === partner.id).slice(0, 12);
  const tabs = [{ id: 'ledger', label: 'Ledger' }, { id: 'tenants', label: 'Tenants' }, { id: 'portal', label: 'Portal' }];
  const savePortal = () => {
    setPortalBusy(true);
    api('PUT', `/partners/${partner.id}/portal`, { config: portal })
      .then(() => { setPortalBusy(false); actions.toast('Portal configuration saved.'); reload(); })
      .catch((e) => { setPortalBusy(false); actions.error(e.message); });
  };
  const copyPortal = () => { if (partner.portal_url) { navigator.clipboard?.writeText(partner.portal_url); actions.toast('Portal link copied.'); } };
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Partner — ${partner.name}`} tabs={tabs} activeTab={tab} onTab={setTab}
      head={(
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(partner.name)}</div>
            <div><div className="sc-dr-title">{partner.name}</div><div className="sc-dr-code">{partner.email}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{st.label ?? partner.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">Rate</div><div className="v">{partner.rate_pct}%</div></div>
            <div className="sc-dr-kpi"><div className="l">Owed</div><div className="v">{money(partner.owed)}</div></div>
            <div className="sc-dr-kpi"><div className="l">Paid</div><div className="v">{money(partner.paid_ltd)}</div></div>
          </div>
          <div className="sc-dr-acts">
            {can.payout && partner.owed > 0 && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.payout(partner)}>Pay {money(partner.owed)}</button>}
            {can.approve && partner.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.approve(partner)}>Approve</button>}
            {can.approve && partner.status === 'suspended' && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.approve(partner)}>Reinstate</button>}
            {can.suspend && partner.status === 'active' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.suspend(partner)}>Suspend</button>}
            {can.edit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.edit(partner)}>Edit</button>}
            {partner.portal_url && <button type="button" className="pc-btn pc-btn--sm" onClick={copyPortal}>Copy portal link</button>}
          </div>
        </>
      )}
    >
      {tab === 'ledger' && (
        rows.length === 0 ? <div className="wb-empty">No commission entries yet.</div>
          : (
            <>
              <table className="sc-minit"><tbody>{rows.map((c) => (
                <tr key={c.id}>
                  <td><div className="pc-mname">{c.tenant}</div><div className="sc-kind">{c.invoice ?? 'no invoice'} · {fmtShort(c.created_at)}</div></td>
                  <td className="pc-r"><span className={`pc-chip ${C_STATUS[c.status]?.cls ?? ''}`}>{C_STATUS[c.status]?.label ?? c.status}</span></td>
                  <td className="pc-r"><span className="pr-money">{money2(c.amount)}</span></td>
                </tr>
              ))}</tbody></table>
              <div className="pr-drfoot">Latest 12 of {partner.entries} — full ledger in the Commissions tab.</div>
            </>
          )
      )}
      {tab === 'tenants' && (
        tenants == null ? <div className="wb-empty">{tenantsFailed ? 'Could not load tenants.' : 'Loading…'}</div>
          : tenants.length === 0 ? <div className="wb-empty">No tenants in this partner's book.</div>
            : <table className="sc-minit"><tbody>{tenants.map((t) => (
              <tr key={t.id}>
                <td><div className="pc-mname">{t.name}</div><div className="sc-kind">{t.subdomain ? `${t.subdomain}.aeos365.com` : t.id} · {T_STATUS[t.status] ?? t.status}</div></td>
                <td className="pc-r">{t.plan ? <><div className="pc-mname">{money(t.mrr)}<span className="sc-kind">/mo</span></div><div className="sc-kind">{t.plan}</div></> : <span className="pc-free">no plan</span>}</td>
                <td className="pc-r">{can.reassign && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.reassign(t, partner.id)}>Reassign…</button>}</td>
              </tr>
            ))}</tbody></table>
      )}
      {tab === 'portal' && (
        <div className="pr-portal">
          {partner.portal_url
            ? <div className="pr-linkrow"><span className="pc-drow__k">Portal URL</span><code className="pr-code">{partner.portal_url}</code></div>
            : <div className="pr-note">No portal slug set — add one via Edit to activate the white-label portal.</div>}
          <div className="pc-field"><label className="pc-field__label">Brand color</label>
            <div className="pr-raterow"><input type="color" value={portal.brand_color || '#4f46e5'} onChange={(e) => setPortal((p) => ({ ...p, brand_color: e.target.value }))} aria-label="Portal brand color" /><code className="pr-code">{portal.brand_color}</code></div>
          </div>
          <div className="pc-field"><label className="pc-field__label">Support email</label><input className="pc-input" type="email" value={portal.support_email || ''} onChange={(e) => setPortal((p) => ({ ...p, support_email: e.target.value }))} placeholder="support@partner.com" /></div>
          <div className="pc-field"><label className="pc-field__label">Welcome message</label><textarea className="pc-input" rows={3} value={portal.welcome || ''} onChange={(e) => setPortal((p) => ({ ...p, welcome: e.target.value }))} placeholder="Shown on the partner's client portal" /></div>
          {can.portal && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={savePortal} disabled={portalBusy}>{portalBusy ? 'Saving…' : 'Save portal config'}</button>}
        </div>
      )}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Partners({ overview }) {
  const toast = useToast();
  const o = overview ?? {};
  const list = useMemo(() => o.partners ?? [], [o.partners]);
  const ledger = useMemo(() => o.commissions ?? [], [o.commissions]);
  const s = o.stats ?? {};
  const sp = o.sparks ?? {};

  const canView = useHRMAC('reseller-partners.partners.view');
  const canCreate = useHRMAC('reseller-partners.partners.create');
  const canEdit = useHRMAC('reseller-partners.partners.update');
  const canApprove = useHRMAC('reseller-partners.partners.approve');
  const canSuspend = useHRMAC('reseller-partners.partners.suspend');
  const canCommManage = useHRMAC('reseller-partners.partner-commissions.manage');
  const canPayout = useHRMAC('reseller-partners.partner-commissions.payout');
  const canReassign = useHRMAC('reseller-partners.partner-tenants.reassign');
  const canPortal = useHRMAC('reseller-partners.partner-portal.configure');

  const [tab, setTab] = useState('partners');
  const [drawer, setDrawer] = useState(null);
  const [formP, setFormP] = useState(undefined);
  const [payoutP, setPayoutP] = useState(null);
  const [reassignT, setReassignT] = useState(null);
  const ctx = useCtxMenu();

  /* actions */
  const approve = (p) => api('POST', `/partners/${p.id}/approve`).then(() => { toast.success(`${p.name} ${p.status === 'suspended' ? 'reinstated' : 'approved'}.`); reload(); }).catch((e) => toast.error(e.message));
  const suspend = (p) => { if (window.confirm(`Suspend ${p.name}? Their portal stays configured but the partnership is paused.`)) api('POST', `/partners/${p.id}/suspend`).then(() => { toast.success(`${p.name} suspended.`); reload(); }).catch((e) => toast.error(e.message)); };
  const approveComm = (c) => api('POST', `/partners/commissions/${c.id}/approve`).then(() => { toast.success(`Commission ${money2(c.amount)} approved.`); reload(); }).catch((e) => toast.error(e.message));
  const payComm = (c) => api('POST', `/partners/commissions/${c.id}/pay`).then(() => { toast.success(`Commission ${money2(c.amount)} paid.`); reload(); }).catch((e) => toast.error(e.message));

  const drawerActions = {
    payout: (p) => setPayoutP(p),
    approve,
    suspend: (p) => { setDrawer(null); suspend(p); },
    edit: (p) => { setDrawer(null); setFormP(p); },
    reassign: (t, currentId) => setReassignT({ tenant: t, currentId }),
    toast: toast.success, error: toast.error,
  };

  /* partners workbench */
  const wb = useWorkbench({
    rows: list, getId: (r) => r.id,
    searchText: (r) => `${r.name} ${r.email} ${r.portal_slug ?? ''}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'active', label: 'Active', test: (r) => r.status === 'active' },
      { id: 'pending', label: 'Pending approval', test: (r) => r.status === 'pending' },
      { id: 'owed', label: 'Owes payout', test: (r) => r.owed > 0 },
      { id: 'suspended', label: 'Suspended', test: (r) => r.status === 'suspended' },
    ],
    facets: { status: { value: 'all', test: (r, v) => r.status === v } },
    sortKey: 'mrr', sortVal: (r, k) => (['mrr', 'owed', 'paid_ltd', 'tenants_count', 'rate_pct'].includes(k) ? (r[k] ?? -1) : String(r[k] ?? '')),
    perPage: 12, storageKey: 'platform.partners',
  });
  useEffect(() => { wb.toggleSort('mrr'); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* commissions workbench */
  const wbc = useWorkbench({
    rows: ledger, getId: (r) => r.id,
    searchText: (r) => `${r.partner} ${r.tenant} ${r.invoice ?? ''} ${r.status}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'pending', label: 'Pending', test: (r) => r.status === 'pending' },
      { id: 'approved', label: 'Approved', test: (r) => r.status === 'approved' },
      { id: 'paid', label: 'Paid', test: (r) => r.status === 'paid' },
    ],
    facets: {
      status: { value: 'all', test: (r, v) => r.status === v },
      partner: { value: 'all', test: (r, v) => String(r.partner_id) === v },
    },
    sortKey: 'created_at', sortVal: (r, k) => (k === 'amount' ? r.amount : new Date(r[k] ?? 0).getTime()),
    perPage: 12, storageKey: 'platform.partnercomm',
  });

  const openPartner = (id) => { const p = list.find((x) => x.id === id); if (p) setDrawer(p); };

  const rowMenu = (r) => [
    ...(canView ? [{ label: 'Open', onClick: () => setDrawer(r) }] : []),
    ...(canApprove && r.status === 'pending' ? [{ label: 'Approve', onClick: () => approve(r) }] : []),
    ...(canApprove && r.status === 'suspended' ? [{ label: 'Reinstate', onClick: () => approve(r) }] : []),
    ...(canSuspend && r.status === 'active' ? [{ label: 'Suspend…', onClick: () => suspend(r) }] : []),
    ...(canPayout && r.owed > 0 ? [{ label: `Pay commissions · ${money(r.owed)}`, onClick: () => setPayoutP(r) }] : []),
    ...(canEdit ? [{ label: 'Edit…', onClick: () => setFormP(r) }] : []),
    ...(r.portal_url ? [{ label: 'Copy portal link', onClick: () => { navigator.clipboard?.writeText(r.portal_url); toast.success('Portal link copied.'); } }] : []),
  ];

  const partnerColumns = [
    { key: 'name', label: 'Partner', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.name)}</div><div><div className="pc-mname">{r.name}</div><div className="sc-kind">{r.email}{r.portal_slug ? ` · ${r.portal_slug}` : ''}</div></div></div> },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${P_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: P_STATUS[r.status]?.color }} />{P_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'rate_pct', label: 'Rate', align: 'r', sortable: true, render: (r) => <span className="pr-rate"><span className="pr-rate__track"><span className="pr-rate__fill" style={{ width: `${Math.min(r.rate_pct / 40 * 100, 100)}%` }} /></span><span className="num">{r.rate_pct}%</span></span> },
    { key: 'tenants_count', label: 'Tenants', align: 'r', sortable: true, render: (r) => (r.tenants_count > 0 ? <span className="num">{r.tenants_count}</span> : <span className="pc-free">—</span>) },
    { key: 'mrr', label: 'MRR', align: 'r', sortable: true, render: (r) => (r.mrr > 0 ? <span className="num">{money(r.mrr)}</span> : <span className="pc-free">—</span>) },
    { key: 'owed', label: 'Owed', align: 'r', sortable: true, render: (r) => (r.owed > 0 ? <span className="pr-owed">{money(r.owed)}</span> : <span className="pc-free">—</span>) },
    { key: 'paid_ltd', label: 'Paid', align: 'r', hideSm: true, sortable: true, render: (r) => (r.paid_ltd > 0 ? <span className="pr-money">{money(r.paid_ltd)}</span> : <span className="pc-free">—</span>) },
    { key: 'created_at', label: 'Since', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtShort(r.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.name}`} onClick={(e) => ctx.open(e.currentTarget, rowMenu(r))}>⋯</button> },
  ];

  const commColumns = [
    { key: 'partner', label: 'Partner', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.partner)}</div><div className="pc-mname">{r.partner}</div></div> },
    { key: 'tenant', label: 'Tenant', render: (r) => <div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.invoice ?? 'no invoice'}</div></div> },
    { key: 'amount', label: 'Amount', align: 'r', sortable: true, render: (r) => <span className="pr-money">{money2(r.amount)}</span> },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${C_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: C_STATUS[r.status]?.color }} />{C_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'created_at', label: 'Earned', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtShort(r.created_at)}</span> },
    { key: 'paid_at', label: 'Paid', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{r.paid_at ? fmtShort(r.paid_at) : '—'}</span> },
    { key: 'actions', label: '', align: 'r', width: 150, render: (r) => (
      <div className="pr-commacts">
        {canCommManage && r.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => approveComm(r)}>Approve</button>}
        {canPayout && r.status !== 'paid' && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => payComm(r)}>Mark paid</button>}
      </div>
    ) },
  ];

  const kpis = [
    { label: 'Partners', value: s.total ?? 0, delta: `${s.pending ?? 0} pending approval`, up: (s.total ?? 0) > 0, spark: sp.partners, color: 'var(--aeos-primary)' },
    { label: 'Active', value: s.active ?? 0, delta: `${s.suspended ?? 0} suspended`, up: true, spark: sp.partners, color: 'var(--aeos-success)' },
    { label: 'Managed tenants', value: s.managed_tenants ?? 0, delta: 'partner-sourced', up: true, spark: sp.earned, color: '#22d3ee' },
    { label: 'Channel MRR', value: moneyK(s.channel_mrr), delta: 'live, plan-based', up: (s.channel_mrr ?? 0) > 0, spark: sp.earned, color: 'var(--aeos-primary)' },
    { label: 'Commission owed', value: moneyK(s.owed), delta: `${s.owed_entries ?? 0} unpaid entries`, spark: sp.earned, color: 'var(--aeos-warning)' },
    { label: 'Paid out', value: moneyK(s.paid_ltd), delta: `${moneyK(s.paid_90d)} in 90d`, up: true, spark: sp.paid, color: 'var(--aeos-success)' },
  ];

  const statusMix = o.statusMix ?? [];
  const top = o.top ?? [];
  const topMax = Math.max(1, ...top.map((t) => t.mrr));
  const trend = o.trend ?? {};
  const queue = o.queue ?? [];

  const exportPartnersCsv = () => {
    const header = 'name,email,status,rate_pct,portal_slug,tenants,mrr,owed,paid_lifetime,since';
    const lines = list.map((r) => [r.name, r.email, r.status, r.rate_pct, r.portal_slug ?? '', r.tenants_count, r.mrr, r.owed, r.paid_ltd, fmtDate(r.created_at)].map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `partners-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };
  const exportLedgerCsv = () => {
    const header = 'partner,tenant,invoice,amount,status,earned,paid_at';
    const lines = ledger.map((r) => [r.partner, r.tenant, r.invoice ?? '', r.amount, r.status, fmtDate(r.created_at), r.paid_at ? fmtDate(r.paid_at) : ''].map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `partner-commissions-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc pr">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Growth &amp; Marketing · Channel Program</div>
          <h1 className="pc-title">Reseller / Channel Partners</h1>
          <div className="pc-sub">Recruit, approve and manage channel partners — their commission terms, managed tenant books, white-label portals, and the payout ledger, in one console.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [{ label: 'Export CSV — partners', onClick: exportPartnersCsv }, { label: 'Export CSV — commission ledger', onClick: exportLedgerCsv }, { label: 'Print this view', onClick: () => window.print() }])}>{Glyph.export}<span>Export</span></button>
          {canPayout && queue.length > 0 && <button type="button" className="pc-btn" onClick={() => setPayoutP(list.find((p) => p.id === queue[0].id) ?? null)}>{Glyph.pay}<span>Run payouts</span></button>}
          {canCreate && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setFormP(null)}>{Glyph.plus}<span>New partner</span></button>}
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
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Commission flow</h2><div className="pc-panel-h__sub">Earned vs paid — last 6 months</div></div></div>
          <AreaTrend series={[{ key: 'earned', label: 'Earned', color: 'var(--aeos-primary)', values: trend.earned ?? [] }, { key: 'paid', label: 'Paid', color: 'var(--aeos-success)', values: trend.paid ?? [] }]} labels={trend.labels ?? []} height={190} ariaLabel="Commission earned vs paid" />
          <div className="tn-trend-foot">
            <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />Earned <b>{money((trend.earned ?? []).reduce((a, b) => a + b, 0))}</b></span>
            <span className="li"><span className="d" style={{ background: 'var(--aeos-success)' }} />Paid <b>{money((trend.paid ?? []).reduce((a, b) => a + b, 0))}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Program health</h2><div className="pc-panel-h__sub">{s.total ?? 0} partners</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={statusMix.map((m) => ({ color: P_STATUS[m.status]?.color ?? 'var(--aeos-text-muted)', value: m.count }))} centerValue={`${s.total ? Math.round((s.active / s.total) * 100) : 0}%`} centerLabel="active" size={112} />
            <div className="sc-dl">
              {statusMix.filter((m) => m.count > 0).map((m) => (
                <button key={m.status} type="button" className="li" onClick={() => wb.setFacet('status', wb.facetValues.status === m.status ? 'all' : m.status)}>
                  <span className="d" style={{ background: P_STATUS[m.status]?.color ?? 'var(--aeos-text-muted)' }} />{m.label}<b>{m.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Partner leaderboard</h2><div className="pc-panel-h__sub">By managed MRR</div></div></div>
          <div className="pr-lb">
            {top.length === 0 && <div className="wb-empty">No partner-managed MRR yet.</div>}
            {top.map((t, i) => (
              <button key={t.id} type="button" className="pr-lbrow" onClick={() => openPartner(t.id)}>
                <span className="pr-lbrow__rk">{i + 1}</span>
                <span className="pr-lbrow__who"><span className="pr-lbrow__nm">{t.name}</span><span className="pr-lbrow__track"><span className="pr-lbrow__fill" style={{ width: `${Math.max((t.mrr / topMax) * 100, 4)}%` }} /></span></span>
                <span className="pr-lbrow__amt">{money(t.mrr)}<small>{t.tenants} tenant{t.tenants === 1 ? '' : 's'}</small></span>
              </button>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* payout queue */}
      {queue.length > 0 && (
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Payouts due</h2><div className="pc-panel-h__sub">Unpaid commission by partner — ledger transitions, audited; no live money movement</div></div><span className="sc-badge sc-badge--warn">{money(queue.reduce((a, b) => a + b.owed, 0))}</span></div>
          <div className="pr-queue">
            {queue.map((q) => (
              <div key={q.id} className="pr-qi">
                <div className="sc-av">{initials(q.name)}</div>
                <div className="pr-qi__who"><b>{q.name}</b><span>{q.entries} unpaid entr{q.entries === 1 ? 'y' : 'ies'}</span></div>
                <span className="pr-owed pr-qi__amt">{money(q.owed)}</span>
                {canPayout && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => openPartnerPayout(q.id, list, setPayoutP)}>Pay</button>}
              </div>
            ))}
          </div>
        </CardBody></Card>
      )}

      {/* workbench */}
      <Card><CardBody>
        <div className="pr-tabs">
          <button type="button" className={`pr-tab${tab === 'partners' ? ' is-on' : ''}`} onClick={() => setTab('partners')}>Partners <span>{list.length}</span></button>
          <button type="button" className={`pr-tab${tab === 'commissions' ? ' is-on' : ''}`} onClick={() => setTab('commissions')}>Commissions <span>{ledger.length}</span></button>
        </div>

        {tab === 'partners' ? (
          <>
            <WbToolbar>
              <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search name, email or portal slug…" ariaLabel="Search partners" />
              <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
                <option value="all">All statuses</option>{Object.keys(P_STATUS).map((k) => <option key={k} value={k}>{P_STATUS[k].label}</option>)}
              </select>
              {canCreate && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setFormP(null)}>+ New partner</button>}
              <WbColumns wb={wb} columns={partnerColumns} />
            </WbToolbar>
            <WbViews wb={wb} />
            <WbBulkBar wb={wb}>
              <button type="button" className="pc-btn pc-btn--sm" onClick={exportPartnersCsv}>Export</button>
            </WbBulkBar>
            <WbTable wb={wb} columns={partnerColumns} onRowClick={setDrawer} rowAriaLabel={(r) => `${r.name}, ${P_STATUS[r.status]?.label}`}
              empty={<>No partners match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>} />
            <WbFooter wb={wb} perOptions={[12, 25, 50]} />
          </>
        ) : (
          <>
            <WbToolbar>
              <WbSearch value={wbc.q} onChange={wbc.setQ} placeholder="Search partner, tenant or invoice…" ariaLabel="Search commissions" />
              <select className="pc-input sc-statusfilter" value={wbc.facetValues.status} onChange={(e) => wbc.setFacet('status', e.target.value)} aria-label="Commission status filter">
                <option value="all">All statuses</option>{Object.keys(C_STATUS).map((k) => <option key={k} value={k}>{C_STATUS[k].label}</option>)}
              </select>
              <select className="pc-input sc-statusfilter" value={wbc.facetValues.partner} onChange={(e) => wbc.setFacet('partner', e.target.value)} aria-label="Partner filter">
                <option value="all">All partners</option>{list.map((p) => <option key={p.id} value={String(p.id)}>{p.name}</option>)}
              </select>
              <WbColumns wb={wbc} columns={commColumns} />
            </WbToolbar>
            <WbViews wb={wbc} />
            <WbTable wb={wbc} columns={commColumns} onRowClick={(r) => openPartner(r.partner_id)}
              empty="No commission entries match these filters." />
            <WbFooter wb={wbc} perOptions={[12, 25, 50]} />
          </>
        )}
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <PartnerDrawer partner={list.find((p) => p.id === drawer.id) ?? drawer} ledger={ledger} onClose={() => setDrawer(null)} actions={drawerActions}
        can={{ payout: canPayout, approve: canApprove, suspend: canSuspend, edit: canEdit, reassign: canReassign, portal: canPortal }} />}
      {formP !== undefined && <PartnerFormModal partner={formP} onClose={() => setFormP(undefined)} onDone={(m) => { setFormP(undefined); toast.success(m); reload(); }} />}
      {payoutP && <PayoutModal partner={payoutP} onClose={() => setPayoutP(null)} onDone={(m) => { setPayoutP(null); toast.success(m); reload(); }} />}
      {reassignT && <ReassignModal tenant={reassignT.tenant} partners={list} currentId={reassignT.currentId} onClose={() => setReassignT(null)} onDone={(m) => { setReassignT(null); setDrawer(null); toast.success(m); reload(); }} />}
    </div>
  );
}

/* queue "Pay" needs the full partner row (owed_entries etc.) for the modal */
function openPartnerPayout(id, list, setPayoutP) {
  const p = list.find((x) => x.id === id);
  if (p) setPayoutP(p);
}

Partners.layout = (page) => (
  <App title="Reseller / Channel Partners" railTitle="Growth &amp; Marketing" rail={<PartnersRail overview={page.props.overview} />}>
    {page}
  </App>
);
