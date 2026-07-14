import { useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  BrandStudio,
  useWorkbench, useCtxMenu, useHRMAC, useToast,
  WbToolbar, WbSearch, WbViews, WbColumns, WbTable, WbFooter, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './whitelabel.css';

/* ---------------- glyphs ---------------- */
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  globe: svg(<><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" /></>),
};

/* ---------------- maps / fmt ---------------- */
const D_STATUS = {
  verified: { label: 'Verified', color: 'var(--aeos-success)', cls: 'pr-s-ok' },
  pending: { label: 'Pending DNS', color: 'var(--aeos-warning)', cls: 'pr-s-pend' },
  failed: { label: 'Failed', color: 'var(--aeos-danger)', cls: 'pr-s-bad' },
};
const SSL_STATUS = {
  active: { label: 'Active', color: 'var(--aeos-success)', cls: 'pr-s-ok' },
  provisioning: { label: 'Provisioning', color: 'var(--aeos-warning)', cls: 'pr-s-pend' },
  expired: { label: 'Expired', color: 'var(--aeos-danger)', cls: 'pr-s-bad' },
  none: { label: 'No SSL', color: 'var(--aeos-text-muted)', cls: '' },
};

const initials = (n) => (n || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const fmtShort = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };
const fmtBytes = (n) => { if (!n) return '0 B'; if (n < 1024) return `${n} B`; if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`; return `${(n / 1024 / 1024).toFixed(2)} MB`; };

const getJson = (url) => fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))));
const reload = () => router.reload({ only: ['overview', 'platformBranding'] });
const post = (url, body, toast, ok) => router.post(url, body ?? {}, {
  preserveScroll: true,
  onSuccess: () => { if (ok) toast.success(ok); },
  onError: (errs) => toast.error(Object.values(errs)[0] ?? 'Action failed.'),
});
const destroy = (url, toast, ok) => router.delete(url, {
  preserveScroll: true,
  onSuccess: () => { if (ok) toast.success(ok); },
  onError: (errs) => toast.error(Object.values(errs)[0] ?? 'Action failed.'),
});

/* ---------------- rail ---------------- */
function WhiteLabelRail({ overview }) {
  const o = overview ?? {};
  const s = o.stats ?? {};
  const expiring = (o.domains ?? []).filter((d) => d.ssl_days_left !== null && d.ssl_days_left >= 0 && d.ssl_days_left <= 30).slice(0, 5);
  const pending = (o.domains ?? []).filter((d) => d.status === 'pending').slice(0, 5);
  const unverifiedDkim = (o.brandings ?? []).filter((b) => b.dkim_configured && !b.dkim_verified_at).slice(0, 5);
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Fleet brand pulse</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Branded tenants</span><b>{s.branded ?? 0} / {s.tenants_total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Custom domains</span><b>{s.domains_verified ?? 0} / {s.domains_total ?? 0}</b></div>
          <div className="pc-rail__row"><span>SSL active</span><b>{s.ssl_active ?? 0}</b></div>
          <div className="pc-rail__row"><span>DKIM verified</span><b>{s.dkim_verified ?? 0} / {s.dkim_configured ?? 0}</b></div>
          <div className="pc-rail__row"><span>Custom CSS live</span><b>{s.css_active ?? 0}</b></div>
        </div>
      </div>
      {expiring.length > 0 && (
        <div>
          <div className="pc-panel-h__title">SSL expiring ≤30d</div>
          <div className="wl-railq">
            {expiring.map((d) => <div key={d.id} className="wl-railq__it"><span className="wl-railq__nm">{d.domain}</span><span className="wl-railq__sub">{d.ssl_days_left}d</span></div>)}
          </div>
        </div>
      )}
      {pending.length > 0 && (
        <div>
          <div className="pc-panel-h__title">Awaiting DNS</div>
          <div className="wl-railq">
            {pending.map((d) => <div key={d.id} className="wl-railq__it"><span className="wl-railq__nm">{d.domain}</span><span className="wl-railq__sub">{fmtShort(d.created_at)}</span></div>)}
          </div>
        </div>
      )}
      {unverifiedDkim.length > 0 && (
        <div>
          <div className="pc-panel-h__title">DKIM unverified</div>
          <div className="wl-railq">
            {unverifiedDkim.map((b) => <div key={b.id} className="wl-railq__it"><span className="wl-railq__nm">{b.tenant}</span><span className="wl-railq__sub">{b.dkim_selector}</span></div>)}
          </div>
        </div>
      )}
    </div>
  );
}

/* ---------------- add-domain modal ---------------- */
function AddDomainModal({ tenants, onClose, toast }) {
  const [tenantId, setTenantId] = useState('');
  const [domain, setDomain] = useState('');
  const [busy, setBusy] = useState(false);
  const submit = (e) => {
    e.preventDefault();
    if (!tenantId || !domain.trim()) return;
    setBusy(true);
    router.post('/white-label/domains', { tenant_id: tenantId, domain: domain.trim().toLowerCase() }, {
      preserveScroll: true,
      onSuccess: () => { toast.success('Domain added — publish the TXT record, then verify.'); onClose(); },
      onError: (errs) => { toast.error(Object.values(errs)[0] ?? 'Could not add domain.'); setBusy(false); },
    });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Add custom domain</h2>
        <div className="pc-modal__sub">Attach a vanity domain to a tenant. A DNS TXT challenge is generated for ownership verification.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label">Tenant *</label>
            <select className="pc-input" value={tenantId} onChange={(e) => setTenantId(e.target.value)} autoFocus>
              <option value="" disabled>Choose tenant…</option>
              {tenants.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
            </select>
          </div>
          <div className="pc-field">
            <label className="pc-field__label">Domain *</label>
            <input className="pc-input" value={domain} onChange={(e) => setDomain(e.target.value)} placeholder="app.customer.com" />
          </div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !tenantId || !domain.trim()}>{busy ? 'Adding…' : 'Add domain'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- domain DNS modal ---------------- */
function DnsModal({ domain, onClose, toast }) {
  const copy = (v) => { navigator.clipboard?.writeText(v); toast.success('Copied.'); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">DNS verification · {domain.domain}</h2>
        <div className="pc-modal__sub">Publish this TXT record at the domain apex, then run Verify. Records may take a few minutes to propagate.</div>
        <div className="pc-field">
          <label className="pc-field__label">TXT record value</label>
          <div className="wl-txt"><code>{domain.dns_txt_record ?? '—'}</code>
            {domain.dns_txt_record && <button type="button" className="pc-btn pc-btn--sm" onClick={() => copy(domain.dns_txt_record)}>Copy</button>}
          </div>
        </div>
        <div className="pc-field">
          <label className="pc-field__label">Then point the domain</label>
          <div className="wl-txt"><code>{domain.domain}.  CNAME  edge.aeos365.com.</code>
            <button type="button" className="pc-btn pc-btn--sm" onClick={() => copy(`${domain.domain}. CNAME edge.aeos365.com.`)}>Copy</button>
          </div>
        </div>
        <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Close</button></div>
      </div>
    </div>
  );
}

/* ---------------- CSS editor modal ---------------- */
const CSS_LIMIT = 100000;
function CssModal({ row, onClose, toast, canEdit }) {
  const [css, setCss] = useState(null); // null = loading
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    getJson(`/white-label/css/${encodeURIComponent(row.tenant_id)}/content`)
      .then((j) => setCss(j.css ?? ''))
      .catch(() => setCss(''));
  }, [row.tenant_id]);
  const over = (css?.length ?? 0) > CSS_LIMIT;
  const submit = (e) => {
    e.preventDefault();
    if (over || css === null) return;
    setBusy(true);
    router.post('/white-label/css', { tenant_id: row.tenant_id, css }, {
      preserveScroll: true,
      onSuccess: () => { toast.success('Custom CSS saved.'); onClose(); },
      onError: (errs) => { toast.error(Object.values(errs)[0] ?? 'Save failed.'); setBusy(false); },
    });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal wl-modal--wide" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Custom CSS · {row.tenant}</h2>
        <div className="pc-modal__sub">Injected after the design system on every page of this tenant. The kill switch disables it instantly without deleting.</div>
        <form className="pc-form" onSubmit={submit}>
          {css === null
            ? <div className="wb-empty">Loading stylesheet…</div>
            : <textarea className="wl-cssed" value={css} onChange={(e) => setCss(e.target.value)} spellCheck={false} readOnly={!canEdit} aria-label="Custom CSS editor" />}
          <div className="wl-cssmeta">
            <span className={over ? 'over' : ''}>{(css?.length ?? 0).toLocaleString()} / {CSS_LIMIT.toLocaleString()} chars</span>
            {row.css_disabled && <span className="wl-s-warn">Currently disabled by kill switch</span>}
          </div>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Close</button>
            {canEdit && <button type="submit" className="pc-btn pc-btn--primary" disabled={busy || over || css === null}>{busy ? 'Saving…' : 'Save CSS'}</button>}
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- new-CSS tenant picker ---------------- */
function NewCssModal({ tenants, existing, onPick, onClose }) {
  const [tenantId, setTenantId] = useState('');
  const options = tenants.filter((t) => !existing.has(t.id));
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Add custom CSS</h2>
        <div className="pc-modal__sub">Pick the tenant to write a stylesheet for.</div>
        <div className="pc-field">
          <label className="pc-field__label">Tenant *</label>
          <select className="pc-input" value={tenantId} onChange={(e) => setTenantId(e.target.value)} autoFocus>
            <option value="" disabled>Choose tenant…</option>
            {options.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
          </select>
        </div>
        <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="button" className="pc-btn pc-btn--primary" disabled={!tenantId} onClick={() => onPick(tenantId)}>Open editor</button></div>
      </div>
    </div>
  );
}

/* ---------------- DKIM configure modal ---------------- */
function DkimModal({ row, tenants, onClose, toast }) {
  const isNew = !row;
  const [d, setD] = useState(() => ({
    tenant_id: row?.tenant_id ?? '',
    email_from_name: row?.email_from_name ?? '',
    email_from_address: row?.email_from_address ?? '',
    dkim_selector: row?.dkim_selector ?? 'aeos',
    dkim_private_key: '',
  }));
  const [busy, setBusy] = useState(false);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const domainPart = d.email_from_address.includes('@') ? d.email_from_address.split('@')[1] : 'example.com';
  const submit = (e) => {
    e.preventDefault();
    setBusy(true);
    router.post('/white-label/email-branding/dkim', d, {
      preserveScroll: true,
      onSuccess: () => { toast.success('DKIM configured — publish the DNS record, then verify.'); onClose(); },
      onError: (errs) => { toast.error(Object.values(errs)[0] ?? 'Configuration failed.'); setBusy(false); },
    });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{isNew ? 'Configure email sender' : `Email sender · ${row.tenant}`}</h2>
        <div className="pc-modal__sub">Set the tenant's outbound identity and DKIM signing key. The private key is stored encrypted; only the DNS record is public.</div>
        <form className="pc-form" onSubmit={submit}>
          {isNew && (
            <div className="pc-field">
              <label className="pc-field__label">Tenant *</label>
              <select className="pc-input" value={d.tenant_id} onChange={(e) => set('tenant_id', e.target.value)} autoFocus>
                <option value="" disabled>Choose tenant…</option>
                {tenants.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
            </div>
          )}
          <div className="pr-form-grid">
            <div className="pc-field"><label className="pc-field__label">From name</label><input className="pc-input" value={d.email_from_name} onChange={(e) => set('email_from_name', e.target.value)} placeholder="Acme Corp" /></div>
            <div className="pc-field"><label className="pc-field__label">From address</label><input className="pc-input" type="email" value={d.email_from_address} onChange={(e) => set('email_from_address', e.target.value)} placeholder="no-reply@acme.com" /></div>
          </div>
          <div className="pc-field"><label className="pc-field__label">DKIM selector *</label><input className="pc-input" value={d.dkim_selector} onChange={(e) => set('dkim_selector', e.target.value)} placeholder="aeos" /></div>
          <div className="pc-field">
            <label className="pc-field__label">DKIM private key (PEM) *</label>
            <textarea className="wl-key" value={d.dkim_private_key} onChange={(e) => set('dkim_private_key', e.target.value)} placeholder="-----BEGIN RSA PRIVATE KEY-----" spellCheck={false} />
          </div>
          <div className="pc-field">
            <label className="pc-field__label">DNS record to publish</label>
            <div className="wl-txt"><code>{d.dkim_selector || 'selector'}._domainkey.{domainPart}.  TXT  {'"v=DKIM1; k=rsa; p=<public-key>"'}</code></div>
          </div>
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !d.dkim_selector || d.dkim_private_key.length < 100 || (isNew && !d.tenant_id)}>{busy ? 'Saving…' : 'Save DKIM'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- per-tenant Brand Studio drawer ---------------- */
function BrandDrawer({ row, canManage, onClose }) {
  const [payload, setPayload] = useState(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    setPayload(null); setFailed(false);
    getJson(`/white-label/branding/${encodeURIComponent(row.tenant_id)}/studio`)
      .then(setPayload)
      .catch(() => setFailed(true));
  }, [row.tenant_id]);
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Brand Studio — ${row.tenant}`} width="min(760px, 94vw)"
      head={(
        <div className="sc-dr-top">
          <div className="sc-av">{initials(row.tenant)}</div>
          <div><div className="sc-dr-title">{row.tenant}</div><div className="sc-dr-code">Platform-managed brand overrides</div></div>
          <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
        </div>
      )}
    >
      <div className="wl-drawer-studio">
        <div className="wl-drawer-note">
          Edits here live in the CENTRAL per-tenant layer — they sit between the tenant's own Brand Studio and the platform brand in the fallback chain, and apply even before the tenant configures anything.
        </div>
        {failed && <div className="wb-empty">Could not load this tenant's branding.</div>}
        {!failed && !payload && <div className="wb-empty">Loading Brand Studio…</div>}
        {payload && (
          <BrandStudio
            branding={payload}
            updateUrl={`/white-label/branding/${encodeURIComponent(row.tenant_id)}`}
            resetUrl={`/white-label/branding/${encodeURIComponent(row.tenant_id)}/reset`}
            canEdit={canManage}
            scopeLabel={row.tenant}
          />
        )}
      </div>
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
const TABS = [
  { id: 'domains', label: 'Domains & SSL' },
  { id: 'branding', label: 'Branding' },
  { id: 'css', label: 'Custom CSS' },
  { id: 'email', label: 'Email & DKIM' },
  { id: 'defaults', label: 'Platform defaults' },
];

export default function WhiteLabel({ overview, platformBranding }) {
  const toast = useToast();
  const o = overview ?? {};
  const s = o.stats ?? {};
  const domains = useMemo(() => o.domains ?? [], [o.domains]);
  const brandings = useMemo(() => o.brandings ?? [], [o.brandings]);
  const tenants = useMemo(() => o.tenants ?? [], [o.tenants]);

  /* branding tab rows = EVERY tenant, joined with its central override row */
  const brandRows = useMemo(() => {
    const byTenant = new Map(brandings.map((b) => [b.tenant_id, b]));
    return tenants.map((t) => {
      const b = byTenant.get(t.id);
      return b ?? {
        id: `t-${t.id}`, tenant_id: t.id, tenant: t.name, name: null,
        logo_light: null, logo_dark: null, logo_icon: null, favicon: null, login_background: null,
        primary_color: null, accent_color: null, customized: false, assets_count: 0,
        has_css: false, css_disabled: false, css_size: 0,
        email_from_name: null, email_from_address: null,
        dkim_selector: null, dkim_configured: false, dkim_verified_at: null, updated_at: null,
      };
    });
  }, [tenants, brandings]);

  const cssRows = useMemo(() => brandings.filter((b) => b.has_css), [brandings]);
  const emailRows = useMemo(() => brandings.filter((b) => b.email_from_address || b.email_from_name || b.dkim_configured), [brandings]);

  const canDomainAdd = useHRMAC('white-label.custom-domains.add');
  const canDomainVerify = useHRMAC('white-label.custom-domains.verify');
  const canDomainRemove = useHRMAC('white-label.custom-domains.remove');
  const canSslProvision = useHRMAC('white-label.ssl-provisioning.provision');
  const canSslRenew = useHRMAC('white-label.ssl-provisioning.renew');
  const canBrandManage = useHRMAC('white-label.tenant-branding.manage');
  const canCssEdit = useHRMAC('white-label.custom-css.edit');
  const canDkimConfigure = useHRMAC('white-label.tenant-email-branding.configure');
  const canDkimVerify = useHRMAC('white-label.tenant-email-branding.verify');
  const canDefaultsEdit = useHRMAC('system-settings.branding-settings.edit');

  /* deep-link: /white-label?tab=css&tenant=… */
  const [tab, setTab] = useState(() => {
    const p = new URLSearchParams(window.location.search);
    return TABS.some((t) => t.id === p.get('tab')) ? p.get('tab') : 'domains';
  });
  const [drawer, setDrawer] = useState(null);
  const [addDomain, setAddDomain] = useState(false);
  const [dnsModal, setDnsModal] = useState(null);
  const [cssModal, setCssModal] = useState(null);
  const [newCss, setNewCss] = useState(false);
  const [dkimModal, setDkimModal] = useState(undefined); // undefined=closed, null=new, row=edit
  const ctx = useCtxMenu();

  useEffect(() => {
    const p = new URLSearchParams(window.location.search);
    const tid = p.get('tenant');
    if (!tid) return;
    const row = brandRows.find((r) => r.tenant_id === tid);
    if (row && tab === 'branding') setDrawer(row);
    if (row && tab === 'css') setCssModal(row);
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  /* actions */
  const verifyDomain = (d) => post(`/white-label/domains/${d.id}/verify`, {}, toast);
  const provisionSsl = (d) => post(`/white-label/domains/${d.id}/ssl/provision`, {}, toast);
  const renewSsl = (d) => post(`/white-label/domains/${d.id}/ssl/renew`, {}, toast);
  const removeDomain = (d) => { if (window.confirm(`Remove ${d.domain}? The tenant loses this vanity domain immediately.`)) destroy(`/white-label/domains/${d.id}`, toast); };
  const resetBrand = (r) => { if (window.confirm(`Reset ${r.tenant}'s platform-managed brand overrides? Their own tenant-side Brand Studio settings are untouched.`)) post(`/white-label/branding/${encodeURIComponent(r.tenant_id)}/reset`, {}, toast); };
  const toggleCss = (r) => post(`/white-label/css/${encodeURIComponent(r.tenant_id)}/toggle`, {}, toast);
  const removeCss = (r) => { if (window.confirm(`Delete ${r.tenant}'s custom CSS? This cannot be undone.`)) destroy(`/white-label/css/${encodeURIComponent(r.tenant_id)}`, toast); };
  const verifyDkim = (r) => post(`/white-label/email-branding/dkim/${r.id}/verify`, {}, toast);
  const removeDkim = (r) => { if (window.confirm(`Remove DKIM signing for ${r.tenant}? Outbound mail falls back to the platform sender.`)) destroy(`/white-label/email-branding/dkim/${r.id}`, toast); };

  /* workbenches */
  const wbd = useWorkbench({
    rows: domains, getId: (r) => r.id,
    searchText: (r) => `${r.domain} ${r.tenant}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'verified', label: 'Verified', test: (r) => r.status === 'verified' },
      { id: 'pending', label: 'Pending DNS', test: (r) => r.status === 'pending' },
      { id: 'expiring', label: 'SSL ≤30d', test: (r) => r.ssl_days_left !== null && r.ssl_days_left >= 0 && r.ssl_days_left <= 30 },
      { id: 'failed', label: 'Failed', test: (r) => r.status === 'failed' },
    ],
    facets: { status: { value: 'all', test: (r, v) => r.status === v } },
    sortKey: 'created_at', sortVal: (r, k) => (k === 'ssl_days_left' ? (r.ssl_days_left ?? 9e9) : new Date(r[k] ?? 0).getTime()),
    perPage: 12, storageKey: 'platform.wl.domains',
  });

  const wbb = useWorkbench({
    rows: brandRows, getId: (r) => r.tenant_id,
    searchText: (r) => `${r.tenant} ${r.name ?? ''}`,
    views: [
      { id: 'all', label: 'All tenants' },
      { id: 'branded', label: 'Branded', test: (r) => r.customized },
      { id: 'inheriting', label: 'Inheriting', test: (r) => !r.customized },
    ],
    facets: {},
    sortKey: 'tenant', sortVal: (r, k) => (k === 'assets_count' ? r.assets_count : String(r[k] ?? '')),
    perPage: 12, storageKey: 'platform.wl.branding',
  });

  const wbc = useWorkbench({
    rows: cssRows, getId: (r) => r.tenant_id,
    searchText: (r) => r.tenant,
    views: [
      { id: 'all', label: 'All' },
      { id: 'live', label: 'Live', test: (r) => !r.css_disabled },
      { id: 'disabled', label: 'Disabled', test: (r) => r.css_disabled },
    ],
    facets: {},
    sortKey: 'css_size', sortVal: (r, k) => (k === 'css_size' ? r.css_size : String(r[k] ?? '')),
    perPage: 12, storageKey: 'platform.wl.css',
  });

  const wbe = useWorkbench({
    rows: emailRows, getId: (r) => r.tenant_id,
    searchText: (r) => `${r.tenant} ${r.email_from_address ?? ''} ${r.dkim_selector ?? ''}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'verified', label: 'DKIM verified', test: (r) => !!r.dkim_verified_at },
      { id: 'unverified', label: 'Configured, unverified', test: (r) => r.dkim_configured && !r.dkim_verified_at },
      { id: 'senderonly', label: 'Sender only', test: (r) => !r.dkim_configured },
    ],
    facets: {},
    sortKey: 'tenant', sortVal: (r, k) => String(r[k] ?? ''),
    perPage: 12, storageKey: 'platform.wl.email',
  });

  /* columns */
  const domainCols = [
    { key: 'domain', label: 'Domain', sortable: true, render: (r) => <div><div className="pc-mname">{r.domain}</div><div className="sc-kind">{r.tenant}</div></div> },
    { key: 'status', label: 'DNS', sortable: true, render: (r) => <span className={`pc-chip ${D_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: D_STATUS[r.status]?.color }} />{D_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'ssl_status', label: 'SSL', render: (r) => <span className={`pc-chip ${SSL_STATUS[r.ssl_status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: SSL_STATUS[r.ssl_status]?.color }} />{SSL_STATUS[r.ssl_status]?.label ?? r.ssl_status}</span> },
    { key: 'ssl_days_left', label: 'Cert expiry', align: 'r', sortable: true, render: (r) => (r.ssl_expires_at ? <span className={r.ssl_days_left <= 30 ? 'wl-s-warn' : 'sc-kind'}>{fmtDate(r.ssl_expires_at)}{r.ssl_days_left !== null && r.ssl_days_left <= 30 && ` · ${r.ssl_days_left}d`}</span> : <span className="pc-free">—</span>) },
    { key: 'verified_at', label: 'Verified', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{r.verified_at ? fmtShort(r.verified_at) : '—'}</span> },
    { key: 'created_at', label: 'Added', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{fmtShort(r.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.domain}`} onClick={(e) => ctx.open(e.currentTarget, domainMenu(r))}>⋯</button> },
  ];
  const domainMenu = (r) => [
    { label: 'DNS records…', onClick: () => setDnsModal(r) },
    ...(canDomainVerify && r.status !== 'verified' ? [{ label: 'Verify DNS now', onClick: () => verifyDomain(r) }] : []),
    ...(canSslProvision && r.status === 'verified' && r.ssl_status !== 'active' ? [{ label: 'Provision SSL', onClick: () => provisionSsl(r) }] : []),
    ...(canSslRenew && r.ssl_status === 'active' ? [{ label: 'Renew SSL', onClick: () => renewSsl(r) }] : []),
    ...(canDomainRemove ? [{ label: 'Remove domain…', onClick: () => removeDomain(r), danger: true }] : []),
  ];

  const brandCols = [
    { key: 'tenant', label: 'Tenant', sortable: true, render: (r) => (
      <div className="pc-mrow">
        {r.logo_icon || r.favicon ? <img className="wl-tlogo" src={r.logo_icon || r.favicon} alt="" /> : <div className="sc-av">{initials(r.tenant)}</div>}
        <div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.name ? `renders as "${r.name}"` : 'platform name'}</div></div>
      </div>
    ) },
    { key: 'customized', label: 'Brand', render: (r) => (r.customized
      ? <span className="pc-chip pr-s-ok"><span className="pc-chip__dot" style={{ background: 'var(--aeos-success)' }} />Custom</span>
      : <span className="pc-chip"><span className="pc-chip__dot" style={{ background: 'var(--aeos-text-muted)' }} />Inheriting</span>) },
    { key: 'colors', label: 'Colors', render: (r) => (
      <span className="wl-swatches">
        {r.primary_color ? <span className="wl-sw" style={{ background: r.primary_color }} title={r.primary_color} /> : <span className="wl-sw wl-sw--empty" title="inherits" />}
        {r.accent_color ? <span className="wl-sw" style={{ background: r.accent_color }} title={r.accent_color} /> : <span className="wl-sw wl-sw--empty" title="inherits" />}
      </span>
    ) },
    { key: 'assets_count', label: 'Assets', align: 'r', sortable: true, render: (r) => (
      <span className="wl-slots" title={`${r.assets_count} of 5 asset slots set`}>
        {['logo_light', 'logo_dark', 'logo_icon', 'favicon', 'login_background'].map((k) => <span key={k} className={`wl-slot${r[k] ? ' on' : ''}`} />)}
      </span>
    ) },
    { key: 'updated_at', label: 'Updated', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{r.updated_at ? fmtShort(r.updated_at) : '—'}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.tenant}`} onClick={(e) => ctx.open(e.currentTarget, brandMenu(r))}>⋯</button> },
  ];
  const brandMenu = (r) => [
    { label: 'Open Brand Studio', onClick: () => setDrawer(r) },
    ...(canBrandManage && r.customized ? [{ label: 'Reset to platform defaults…', onClick: () => resetBrand(r), danger: true }] : []),
  ];

  const cssCols = [
    { key: 'tenant', label: 'Tenant', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.tenant)}</div><div className="pc-mname">{r.tenant}</div></div> },
    { key: 'state', label: 'State', render: (r) => (r.css_disabled
      ? <span className="pc-chip pr-s-bad"><span className="pc-chip__dot" style={{ background: 'var(--aeos-danger)' }} />Disabled</span>
      : <span className="pc-chip pr-s-ok"><span className="pc-chip__dot" style={{ background: 'var(--aeos-success)' }} />Live</span>) },
    { key: 'css_size', label: 'Size', align: 'r', sortable: true, render: (r) => <span className="num">{fmtBytes(r.css_size)}</span> },
    { key: 'updated_at', label: 'Updated', align: 'r', hideSm: true, sortable: true, render: (r) => <span className="sc-kind">{r.updated_at ? fmtShort(r.updated_at) : '—'}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.tenant} CSS`} onClick={(e) => ctx.open(e.currentTarget, cssMenu(r))}>⋯</button> },
  ];
  const cssMenu = (r) => [
    { label: canCssEdit ? 'Edit CSS…' : 'View CSS…', onClick: () => setCssModal(r) },
    ...(canCssEdit ? [{ label: r.css_disabled ? 'Enable (kill switch off)' : 'Disable (kill switch)', onClick: () => toggleCss(r) }] : []),
    ...(canCssEdit ? [{ label: 'Delete CSS…', onClick: () => removeCss(r), danger: true }] : []),
  ];

  const emailCols = [
    { key: 'tenant', label: 'Tenant', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.tenant)}</div><div><div className="pc-mname">{r.tenant}</div><div className="sc-kind">{r.email_from_name ?? '—'}</div></div></div> },
    { key: 'email_from_address', label: 'From address', sortable: true, render: (r) => (r.email_from_address ? <code className="pr-code">{r.email_from_address}</code> : <span className="pc-free">—</span>) },
    { key: 'dkim_selector', label: 'Selector', hideSm: true, render: (r) => (r.dkim_selector ? <code className="pr-code">{r.dkim_selector}</code> : <span className="pc-free">—</span>) },
    { key: 'dkim', label: 'DKIM', render: (r) => (r.dkim_verified_at
      ? <span className="pc-chip pr-s-ok"><span className="pc-chip__dot" style={{ background: 'var(--aeos-success)' }} />Verified {fmtShort(r.dkim_verified_at)}</span>
      : r.dkim_configured
        ? <span className="pc-chip pr-s-pend"><span className="pc-chip__dot" style={{ background: 'var(--aeos-warning)' }} />Unverified</span>
        : <span className="pc-chip"><span className="pc-chip__dot" style={{ background: 'var(--aeos-text-muted)' }} />Not signed</span>) },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.tenant} email`} onClick={(e) => ctx.open(e.currentTarget, emailMenu(r))}>⋯</button> },
  ];
  const emailMenu = (r) => [
    ...(canDkimConfigure ? [{ label: r.dkim_configured ? 'Rotate DKIM key…' : 'Configure DKIM…', onClick: () => setDkimModal(r) }] : []),
    ...(canDkimVerify && r.dkim_configured ? [{ label: 'Verify DNS now', onClick: () => verifyDkim(r) }] : []),
    ...(canDkimConfigure && r.dkim_configured ? [{ label: 'Remove DKIM…', onClick: () => removeDkim(r), danger: true }] : []),
  ];

  /* KPIs */
  const kpis = [
    { label: 'Branded tenants', value: `${s.branded ?? 0} / ${s.tenants_total ?? 0}`, delta: 'carry their own brand', up: (s.branded ?? 0) > 0 },
    { label: 'Custom domains', value: s.domains_total ?? 0, delta: `${s.domains_verified ?? 0} verified · ${s.domains_pending ?? 0} pending`, up: (s.domains_verified ?? 0) > 0 },
    { label: 'SSL active', value: s.ssl_active ?? 0, delta: `${s.ssl_expiring_30d ?? 0} expiring ≤30d`, up: (s.ssl_expiring_30d ?? 0) === 0 },
    { label: 'DKIM verified', value: `${s.dkim_verified ?? 0} / ${s.dkim_configured ?? 0}`, delta: 'signed senders', up: (s.dkim_verified ?? 0) === (s.dkim_configured ?? 0) },
    { label: 'Custom CSS live', value: s.css_active ?? 0, delta: 'kill switch available', up: true },
    { label: 'Pending DNS', value: s.domains_pending ?? 0, delta: 'awaiting TXT verification', up: (s.domains_pending ?? 0) === 0 },
  ];

  /* CSV exports */
  const csv = (name, header, lines) => {
    const blob = new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${name}-${new Date().toISOString().slice(0, 10)}.csv`; a.click(); URL.revokeObjectURL(a.href);
  };
  const q = (v) => `"${String(v ?? '').replaceAll('"', '""')}"`;
  const exportDomains = () => csv('white-label-domains', 'domain,tenant,dns_status,ssl_status,ssl_expires,verified_at,added',
    domains.map((r) => [r.domain, r.tenant, r.status, r.ssl_status, fmtDate(r.ssl_expires_at), fmtDate(r.verified_at), fmtDate(r.created_at)].map(q).join(',')));
  const exportBrandings = () => csv('white-label-brandings', 'tenant,brand_name,customized,assets,primary,accent,css,css_state,from_address,dkim,updated',
    brandRows.map((r) => [r.tenant, r.name ?? '', r.customized ? 'yes' : 'no', `${r.assets_count}/5`, r.primary_color ?? '', r.accent_color ?? '', r.has_css ? fmtBytes(r.css_size) : '', r.has_css ? (r.css_disabled ? 'disabled' : 'live') : '', r.email_from_address ?? '', r.dkim_verified_at ? 'verified' : (r.dkim_configured ? 'configured' : ''), fmtDate(r.updated_at)].map(q).join(',')));

  return (
    <div className="pc pr wl">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform Infrastructure · White-Label</div>
          <h1 className="pc-title">White-Label Command Center</h1>
          <div className="pc-sub">Every tenant's brand, vanity domain, SSL certificate, custom CSS and email signing — managed, verified and audited from one console. Platform defaults included.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={(e) => ctx.open(e.currentTarget, [{ label: 'Export CSV — domains', onClick: exportDomains }, { label: 'Export CSV — tenant brandings', onClick: exportBrandings }, { label: 'Print this view', onClick: () => window.print() }])}>{Glyph.export}<span>Export</span></button>
          {canDomainAdd && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setAddDomain(true)}>{Glyph.plus}<span>Add domain</span></button>}
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
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* workbench */}
      <Card><CardBody>
        <div className="wl-tabs" role="tablist">
          <button type="button" role="tab" aria-selected={tab === 'domains'} className={`wl-tab${tab === 'domains' ? ' is-on' : ''}`} onClick={() => setTab('domains')}>Domains &amp; SSL <span className="ct">{domains.length}</span></button>
          <button type="button" role="tab" aria-selected={tab === 'branding'} className={`wl-tab${tab === 'branding' ? ' is-on' : ''}`} onClick={() => setTab('branding')}>Branding <span className="ct">{s.branded ?? 0}</span></button>
          <button type="button" role="tab" aria-selected={tab === 'css'} className={`wl-tab${tab === 'css' ? ' is-on' : ''}`} onClick={() => setTab('css')}>Custom CSS <span className="ct">{cssRows.length}</span></button>
          <button type="button" role="tab" aria-selected={tab === 'email'} className={`wl-tab${tab === 'email' ? ' is-on' : ''}`} onClick={() => setTab('email')}>Email &amp; DKIM <span className="ct">{emailRows.length}</span></button>
          <button type="button" role="tab" aria-selected={tab === 'defaults'} className={`wl-tab${tab === 'defaults' ? ' is-on' : ''}`} onClick={() => setTab('defaults')}>Platform defaults</button>
        </div>

        {tab === 'domains' && (
          <>
            <WbToolbar>
              <WbSearch value={wbd.q} onChange={wbd.setQ} placeholder="Search domain or tenant…" ariaLabel="Search domains" />
              {canDomainAdd && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setAddDomain(true)}>+ Add domain</button>}
              <WbColumns wb={wbd} columns={domainCols} />
            </WbToolbar>
            <WbViews wb={wbd} />
            <WbTable wb={wbd} columns={domainCols} onRowClick={setDnsModal} rowAriaLabel={(r) => `${r.domain}, ${D_STATUS[r.status]?.label}`}
              empty={<>No custom domains yet.<br />{canDomainAdd && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setAddDomain(true)}>Add the first domain</button>}</>} />
            <WbFooter wb={wbd} perOptions={[12, 25, 50]} />
          </>
        )}

        {tab === 'branding' && (
          <>
            <WbToolbar>
              <WbSearch value={wbb.q} onChange={wbb.setQ} placeholder="Search tenant or brand name…" ariaLabel="Search brandings" />
              <WbColumns wb={wbb} columns={brandCols} />
            </WbToolbar>
            <WbViews wb={wbb} />
            <WbTable wb={wbb} columns={brandCols} onRowClick={setDrawer} rowAriaLabel={(r) => `${r.tenant}, ${r.customized ? 'custom brand' : 'inheriting'}`}
              empty="No tenants." />
            <WbFooter wb={wbb} perOptions={[12, 25, 50]} />
          </>
        )}

        {tab === 'css' && (
          <>
            <WbToolbar>
              <WbSearch value={wbc.q} onChange={wbc.setQ} placeholder="Search tenant…" ariaLabel="Search custom CSS" />
              {canCssEdit && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setNewCss(true)}>+ Add CSS</button>}
              <WbColumns wb={wbc} columns={cssCols} />
            </WbToolbar>
            <WbViews wb={wbc} />
            <WbTable wb={wbc} columns={cssCols} onRowClick={setCssModal} rowAriaLabel={(r) => `${r.tenant} custom CSS, ${r.css_disabled ? 'disabled' : 'live'}`}
              empty={<>No tenant carries custom CSS.<br />{canCssEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setNewCss(true)}>Write the first stylesheet</button>}</>} />
            <WbFooter wb={wbc} perOptions={[12, 25, 50]} />
          </>
        )}

        {tab === 'email' && (
          <>
            <WbToolbar>
              <WbSearch value={wbe.q} onChange={wbe.setQ} placeholder="Search tenant, address or selector…" ariaLabel="Search email branding" />
              {canDkimConfigure && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setDkimModal(null)}>+ Configure sender</button>}
              <WbColumns wb={wbe} columns={emailCols} />
            </WbToolbar>
            <WbViews wb={wbe} />
            <WbTable wb={wbe} columns={emailCols} onRowClick={(r) => canDkimConfigure && setDkimModal(r)} rowAriaLabel={(r) => `${r.tenant} email sender`}
              empty={<>No tenant has a custom email sender yet.<br />{canDkimConfigure && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setDkimModal(null)}>Configure the first sender</button>}</>} />
            <WbFooter wb={wbe} perOptions={[12, 25, 50]} />
          </>
        )}

        {tab === 'defaults' && (
          <div className="wl-defaults">
            <BrandStudio
              branding={platformBranding ?? {}}
              updateUrl="/settings/branding"
              resetUrl="/settings/branding/reset"
              canEdit={canDefaultsEdit}
              scopeLabel="platform"
            />
          </div>
        )}
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <BrandDrawer row={drawer} canManage={canBrandManage} onClose={() => { setDrawer(null); reload(); }} />}
      {addDomain && <AddDomainModal tenants={tenants} onClose={() => setAddDomain(false)} toast={toast} />}
      {dnsModal && <DnsModal domain={dnsModal} onClose={() => setDnsModal(null)} toast={toast} />}
      {cssModal && <CssModal row={cssModal} onClose={() => setCssModal(null)} toast={toast} canEdit={canCssEdit} />}
      {newCss && <NewCssModal tenants={tenants} existing={new Set(cssRows.map((r) => r.tenant_id))} onClose={() => setNewCss(false)}
        onPick={(tid) => { setNewCss(false); const t = tenants.find((x) => x.id === tid); setCssModal({ tenant_id: tid, tenant: t?.name ?? tid, css_disabled: false }); }} />}
      {dkimModal !== undefined && <DkimModal row={dkimModal} tenants={tenants} onClose={() => setDkimModal(undefined)} toast={toast} />}
    </div>
  );
}

WhiteLabel.layout = (page) => (
  <App title="White-Label Command Center" railTitle="Platform Infrastructure" rail={<WhiteLabelRail overview={page.props.overview} />}>
    {page}
  </App>
);
