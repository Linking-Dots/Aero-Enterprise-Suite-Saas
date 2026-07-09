/**
 * Organization command center — unified company control room.
 *
 * One page subsumes the five former settings sub-pages (Profile, Tax/Legal,
 * Addresses, Contacts, Fiscal). All five `core.organization.*` GET routes render
 * this; `section` selects the initial tab. Each section keeps its own useForm +
 * independent save to its existing POST endpoint (HRMAC-gated, audit-logged).
 *
 * Props: { org:{...scalars, tax_id}, addresses:[], contacts:[], section }
 */
import { useEffect, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Card, CardBody, Donut, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';

import '../../Platform/Admin/Products/products.css';
import './organization.css';

/* ---------------- options ---------------- */
const INDUSTRIES = ['Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail', 'Education', 'Government', 'Non-profit', 'Other'];
const COMPANY_SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];
const ADDRESS_TYPES = [['office', 'Office'], ['billing', 'Billing'], ['shipping', 'Shipping'], ['other', 'Other']];
const DATE_FORMATS = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD', 'D MMM YYYY'];
const COMMON_TZ = ['UTC', 'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Dhaka', 'Asia/Kolkata', 'Asia/Dubai', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney'];
const COMMON_CCY = ['USD', 'EUR', 'GBP', 'BDT', 'INR', 'AED', 'SGD', 'JPY', 'AUD', 'CAD'];

/* ---------------- icons ---------------- */
const ic = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const ICON = {
  profile: ic(<><path d="M3 21h18" /><path d="M5 21V7l7-4 7 4v14" /><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01" /></>),
  identity: ic(<><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /></>),
  address: ic(<><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" /></>),
  contact: ic(<><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></>),
  fiscal: ic(<><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></>),
  export: ic(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
};

/* ---------------- helpers ---------------- */
const initials = (n) => (n || 'Co').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const filled = (v) => v !== undefined && v !== null && String(v).trim() !== '';

/* ---------------- field primitives ---------------- */
function Field({ label, req, error, hint, span, children }) {
  return (
    <label className={`pc-field${span ? ' org-span2' : ''}`}>
      <span className="pc-field__label">{label}{req && <span style={{ color: 'var(--aeos-danger)' }}> *</span>}</span>
      {children}
      {hint && !error && <span className="pc-field__hint">{hint}</span>}
      {error && <span className="pc-field__err">{error}</span>}
    </label>
  );
}
const Text = ({ v, on, ...p }) => <input className="pc-input" value={v ?? ''} onChange={(e) => on(e.target.value)} {...p} />;
const Sel = ({ v, on, opts, placeholder }) => (
  <select className="pc-input" value={v ?? ''} onChange={(e) => on(e.target.value)}>
    {placeholder && <option value="">{placeholder}</option>}
    {opts.map((o) => { const [val, lab] = Array.isArray(o) ? o : [o, o]; return <option key={val} value={val}>{lab}</option>; })}
  </select>
);

/* ---------------- section shell ---------------- */
function Section({ id, active, title, desc, canEdit, dirty, processing, onSave, onReset, extra, children }) {
  return (
    <form onSubmit={onSave} style={{ display: active === id ? 'block' : 'none' }}>
      <Card><CardBody>
        <div className="org-sec__head">
          <div><div className="org-sec__title">{title}</div><div className="org-sec__desc">{desc}</div></div>
          {canEdit ? (
            <div className="org-sec__actions">
              {extra}
              {onReset && <button type="button" className="pc-btn pc-btn--sm" onClick={onReset} disabled={!dirty || processing}>Reset</button>}
              <button type="submit" className="pc-btn pc-btn--sm pc-btn--primary" disabled={!dirty || processing}>{processing ? 'Saving…' : 'Save changes'}</button>
            </div>
          ) : <span className="org-chip">Read-only</span>}
        </div>
        {children}
      </CardBody></Card>
    </form>
  );
}

/* dirty reporter — lifts each form's isDirty to the parent for the tab dots */
function useReportDirty(id, isDirty, report) { useEffect(() => report(id, isDirty), [id, isDirty, report]); }

/* ================= section forms ================= */
function ProfileForm({ org, active, report }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.organization.org_profile.update');
  const f = useForm({
    company_name: org.company_name ?? '', legal_name: org.legal_name ?? '', registration_number: org.registration_number ?? '',
    industry: org.industry ?? '', company_size: org.company_size ?? '', logo_path: org.logo_path ?? '',
    website: org.website ?? '', phone: org.phone ?? '', email: org.email ?? '',
  });
  useReportDirty('profile', f.isDirty, report);
  const save = (e) => { e.preventDefault(); f.post(route('core.organization.profile.update'), { preserveScroll: true, onSuccess: () => toast.success('Profile updated.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <Section id="profile" active={active} title="Profile" desc="Company identity, branding and contact details." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
      <div className="org-grid">
        <Field label="Company name" req error={f.errors.company_name} span><Text v={f.data.company_name} on={(v) => f.setData('company_name', v)} placeholder="Enter company name" /></Field>
        <Field label="Legal name" error={f.errors.legal_name}><Text v={f.data.legal_name} on={(v) => f.setData('legal_name', v)} placeholder="Registered legal name" /></Field>
        <Field label="Registration number" error={f.errors.registration_number}><Text v={f.data.registration_number} on={(v) => f.setData('registration_number', v)} placeholder="Company registration #" /></Field>
        <Field label="Industry" error={f.errors.industry}><Sel v={f.data.industry} on={(v) => f.setData('industry', v)} opts={INDUSTRIES} placeholder="— Select industry —" /></Field>
        <Field label="Company size" error={f.errors.company_size}><Sel v={f.data.company_size} on={(v) => f.setData('company_size', v)} opts={COMPANY_SIZES} placeholder="— Select size —" /></Field>
        <div className="org-subhead">Branding</div>
        <Field label="Logo URL" error={f.errors.logo_path} hint="Displayed as the company avatar" span>
          <div style={{ display: 'flex', gap: 'var(--aeos-space-3)', alignItems: 'center' }}>
            <span className="org-logo" style={{ width: 44, height: 44 }}>{f.data.logo_path ? <img src={f.data.logo_path} alt="" onError={(e) => { e.currentTarget.style.display = 'none'; }} /> : initials(f.data.company_name)}</span>
            <Text v={f.data.logo_path} on={(v) => f.setData('logo_path', v)} placeholder="https://…/logo.png" />
          </div>
        </Field>
        <div className="org-subhead">Contact information</div>
        <Field label="Website" error={f.errors.website} span><Text v={f.data.website} on={(v) => f.setData('website', v)} type="url" placeholder="https://example.com" /></Field>
        <Field label="Phone" error={f.errors.phone}><Text v={f.data.phone} on={(v) => f.setData('phone', v)} type="tel" placeholder="+1 555-0123" /></Field>
        <Field label="Email" error={f.errors.email}><Text v={f.data.email} on={(v) => f.setData('email', v)} type="email" placeholder="hello@example.com" /></Field>
      </div>
    </Section>
  );
}

function IdentityForm({ org, active, report }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.organization.org_identity.update');
  const [reveal, setReveal] = useState(false);
  const f = useForm({ tax_id: org.tax_id ?? '', vat_number: org.vat_number ?? '', country: org.country ?? '', currency: org.currency ?? '' });
  useReportDirty('identity', f.isDirty, report);
  const save = (e) => { e.preventDefault(); f.post(route('core.organization.identity.update'), { preserveScroll: true, onSuccess: () => toast.success('Tax / legal identity updated.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <Section id="identity" active={active} title="Tax &amp; Legal identity" desc="Tax ID is stored encrypted at rest." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
      <div className="org-grid">
        <Field label="Tax ID / EIN" error={f.errors.tax_id} hint="Stored encrypted" span>
          <div className="org-reveal">
            <Text v={f.data.tax_id} on={(v) => f.setData('tax_id', v)} type={reveal ? 'text' : 'password'} placeholder="Encrypted storage" />
            <button type="button" onClick={() => setReveal((r) => !r)}>{reveal ? 'Hide' : 'Show'}</button>
          </div>
        </Field>
        <Field label="VAT number" error={f.errors.vat_number}><Text v={f.data.vat_number} on={(v) => f.setData('vat_number', v)} maxLength={50} placeholder="VAT registration" /></Field>
        <Field label="Country (ISO 2)" error={f.errors.country}><Text v={f.data.country} on={(v) => f.setData('country', v.toUpperCase())} maxLength={2} placeholder="US" /></Field>
        <Field label="Currency (ISO 3)" error={f.errors.currency}>
          <><input className="pc-input" list="org-ccy" value={f.data.currency ?? ''} onChange={(e) => f.setData('currency', e.target.value.toUpperCase())} maxLength={3} placeholder="USD" />
            <datalist id="org-ccy">{COMMON_CCY.map((c) => <option key={c} value={c} />)}</datalist></>
        </Field>
      </div>
    </Section>
  );
}

function AddressesForm({ addresses, active, report }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.organization.org_addresses.manage');
  const empty = { type: 'office', line1: '', line2: '', city: '', state: '', postal_code: '', country: '', is_primary: false };
  const f = useForm({ addresses: (addresses ?? []).length ? addresses : [] });
  useReportDirty('addresses', f.isDirty, report);
  const set = (i, k, v) => { const n = f.data.addresses.map((a) => ({ ...a })); n[i][k] = v; if (k === 'is_primary' && v) n.forEach((a, j) => { if (j !== i) a.is_primary = false; }); f.setData('addresses', n); };
  const add = () => f.setData('addresses', [...f.data.addresses, { ...empty, is_primary: f.data.addresses.length === 0 }]);
  const del = (i) => f.setData('addresses', f.data.addresses.filter((_, j) => j !== i));
  const save = (e) => { e.preventDefault(); f.post(route('core.organization.addresses.update'), { preserveScroll: true, onSuccess: () => toast.success('Addresses updated.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <Section id="addresses" active={active} title="Addresses &amp; locations" desc="Billing, shipping and office locations." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save}
      extra={canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={add}>+ Add address</button>}>
      {f.data.addresses.length === 0 && <div className="org-empty">No addresses yet.{canEdit ? ' Click “Add address”.' : ''}</div>}
      <div className="org-rows">
        {f.data.addresses.map((a, i) => (
          <div className="org-row" key={i}>
            <div className="org-row__top">
              <div className="org-row__title">{ICON.address} Address #{i + 1} {a.is_primary && <span className="org-badge-primary">PRIMARY</span>}</div>
              {canEdit && <div className="org-row__acts">
                <label className="org-primary"><input type="radio" name="addr-primary" checked={!!a.is_primary} onChange={() => set(i, 'is_primary', true)} /> Primary</label>
                <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => del(i)}>Remove</button>
              </div>}
            </div>
            <div className="org-grid">
              <Field label="Type"><Sel v={a.type} on={(v) => set(i, 'type', v)} opts={ADDRESS_TYPES} /></Field>
              <Field label="Country (ISO 2)" req error={f.errors[`addresses.${i}.country`]}><Text v={a.country} on={(v) => set(i, 'country', v.toUpperCase())} maxLength={2} placeholder="US" /></Field>
              <Field label="Address line 1" req error={f.errors[`addresses.${i}.line1`]} span><Text v={a.line1} on={(v) => set(i, 'line1', v)} placeholder="Street address" /></Field>
              <Field label="Address line 2" span><Text v={a.line2} on={(v) => set(i, 'line2', v)} placeholder="Apartment, suite, unit" /></Field>
              <Field label="City" req error={f.errors[`addresses.${i}.city`]}><Text v={a.city} on={(v) => set(i, 'city', v)} placeholder="City" /></Field>
              <Field label="State / Province"><Text v={a.state} on={(v) => set(i, 'state', v)} placeholder="State or province" /></Field>
              <Field label="Postal code"><Text v={a.postal_code} on={(v) => set(i, 'postal_code', v)} placeholder="ZIP / postal" /></Field>
            </div>
          </div>
        ))}
      </div>
    </Section>
  );
}

function ContactsForm({ contacts, active, report }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.organization.org_contacts.manage');
  const empty = { name: '', email: '', role: '', phone: '', is_primary: false };
  const f = useForm({ contacts: (contacts ?? []).length ? contacts : [] });
  useReportDirty('contacts', f.isDirty, report);
  const set = (i, k, v) => { const n = f.data.contacts.map((c) => ({ ...c })); n[i][k] = v; if (k === 'is_primary' && v) n.forEach((c, j) => { if (j !== i) c.is_primary = false; }); f.setData('contacts', n); };
  const add = () => f.setData('contacts', [...f.data.contacts, { ...empty, is_primary: f.data.contacts.length === 0 }]);
  const del = (i) => f.setData('contacts', f.data.contacts.filter((_, j) => j !== i));
  const save = (e) => { e.preventDefault(); f.post(route('core.organization.contacts.update'), { preserveScroll: true, onSuccess: () => toast.success('Contacts updated.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <Section id="contacts" active={active} title="Key contacts" desc="Primary people for this workspace." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save}
      extra={canEdit && <button type="button" className="pc-btn pc-btn--sm" onClick={add}>+ Add contact</button>}>
      {f.data.contacts.length === 0 && <div className="org-empty">No contacts yet.{canEdit ? ' Click “Add contact”.' : ''}</div>}
      <div className="org-rows">
        {f.data.contacts.map((c, i) => (
          <div className="org-row" key={i}>
            <div className="org-row__top">
              <div className="org-row__title">{ICON.contact} {c.name || `Contact #${i + 1}`} {c.is_primary && <span className="org-badge-primary">PRIMARY</span>}</div>
              {canEdit && <div className="org-row__acts">
                <label className="org-primary"><input type="radio" name="contact-primary" checked={!!c.is_primary} onChange={() => set(i, 'is_primary', true)} /> Primary</label>
                <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => del(i)}>Remove</button>
              </div>}
            </div>
            <div className="org-grid">
              <Field label="Name" req error={f.errors[`contacts.${i}.name`]}><Text v={c.name} on={(v) => set(i, 'name', v)} placeholder="Full name" /></Field>
              <Field label="Role / Title" error={f.errors[`contacts.${i}.role`]}><Text v={c.role} on={(v) => set(i, 'role', v)} placeholder="CFO, CTO, etc." /></Field>
              <Field label="Email" req error={f.errors[`contacts.${i}.email`]}><Text v={c.email} on={(v) => set(i, 'email', v)} type="email" placeholder="contact@example.com" /></Field>
              <Field label="Phone" error={f.errors[`contacts.${i}.phone`]}><Text v={c.phone} on={(v) => set(i, 'phone', v)} type="tel" placeholder="+1 555-0123" /></Field>
            </div>
          </div>
        ))}
      </div>
    </Section>
  );
}

function FiscalForm({ org, active, report }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.organization.fiscal_year.manage');
  const f = useForm({ fiscal_year_start: org.fiscal_year_start ?? '01-01', fiscal_year_end: org.fiscal_year_end ?? '12-31', timezone: org.timezone ?? 'UTC', date_format: org.date_format ?? 'DD/MM/YYYY' });
  useReportDirty('fiscal', f.isDirty, report);
  const save = (e) => { e.preventDefault(); f.post(route('core.organization.fiscal-year.update'), { preserveScroll: true, onSuccess: () => toast.success('Fiscal year updated.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <Section id="fiscal" active={active} title="Fiscal &amp; locale" desc="Fiscal calendar, timezone and date format." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
      <div className="org-grid">
        <Field label="Fiscal year start (MM-DD)" req error={f.errors.fiscal_year_start}><Text v={f.data.fiscal_year_start} on={(v) => f.setData('fiscal_year_start', v)} placeholder="01-01" /></Field>
        <Field label="Fiscal year end (MM-DD)" req error={f.errors.fiscal_year_end}><Text v={f.data.fiscal_year_end} on={(v) => f.setData('fiscal_year_end', v)} placeholder="12-31" /></Field>
        <div className="org-subhead">Regional</div>
        <Field label="Timezone" req error={f.errors.timezone} hint="IANA name (e.g. America/New_York)">
          <><input className="pc-input" list="org-tz" value={f.data.timezone ?? ''} onChange={(e) => f.setData('timezone', e.target.value)} placeholder="UTC" />
            <datalist id="org-tz">{COMMON_TZ.map((t) => <option key={t} value={t} />)}</datalist></>
        </Field>
        <Field label="Date format" req error={f.errors.date_format}><Sel v={f.data.date_format} on={(v) => f.setData('date_format', v)} opts={DATE_FORMATS} /></Field>
      </div>
    </Section>
  );
}

/* ---------------- rail ---------------- */
function Rail({ org, pct, addresses, contacts }) {
  const primAddr = (addresses ?? []).find((a) => a.is_primary) ?? (addresses ?? [])[0];
  const primContact = (contacts ?? []).find((c) => c.is_primary) ?? (contacts ?? [])[0];
  return (
    <div className="pc-rail org">
      <div>
        <div className="pc-panel-h__title">Organization</div>
        <div className="pc-rail__rows">
          <div className={`pc-rail__row${pct < 100 ? ' is-warn' : ''}`}><span>Completeness</span><b>{pct}%</b></div>
          <div className="pc-rail__row"><span>Industry</span><b>{org.industry || '—'}</b></div>
          <div className="pc-rail__row"><span>Country · Currency</span><b>{[org.country, org.currency].filter(Boolean).join(' · ') || '—'}</b></div>
          <div className="pc-rail__row"><span>Fiscal year</span><b>{org.fiscal_year_start || '—'} → {org.fiscal_year_end || '—'}</b></div>
          <div className="pc-rail__row"><span>Primary address</span><b>{primAddr ? [primAddr.city, primAddr.country].filter(Boolean).join(', ') : '—'}</b></div>
          <div className="pc-rail__row"><span>Primary contact</span><b>{primContact?.name || '—'}</b></div>
        </div>
      </div>
    </div>
  );
}

/* ================= page ================= */
export default function OrganizationIndex({ org = {}, addresses = [], contacts = [], section = 'profile' }) {
  const [active, setActive] = useState(section);
  const [dirty, setDirty] = useState({});
  const report = useMemo(() => (id, val) => setDirty((d) => (d[id] === val ? d : { ...d, [id]: val })), []);

  // profile completeness — the actual saved record
  const CHECKS = [
    ['company_name', 'Company name', filled(org.company_name)],
    ['legal_name', 'Legal name', filled(org.legal_name)],
    ['registration_number', 'Registration #', filled(org.registration_number)],
    ['industry', 'Industry', filled(org.industry)],
    ['company_size', 'Company size', filled(org.company_size)],
    ['logo_path', 'Logo', filled(org.logo_path)],
    ['website', 'Website', filled(org.website)],
    ['phone', 'Phone', filled(org.phone)],
    ['email', 'Email', filled(org.email)],
    ['tax_id', 'Tax ID', filled(org.tax_id)],
    ['vat_number', 'VAT number', filled(org.vat_number)],
    ['country', 'Country', filled(org.country)],
    ['currency', 'Currency', filled(org.currency)],
    ['timezone', 'Timezone', filled(org.timezone)],
    ['address', 'An address', (addresses ?? []).length > 0],
    ['contact', 'A contact', (contacts ?? []).length > 0],
  ];
  const done = CHECKS.filter((c) => c[2]).length;
  const pct = Math.round(done / CHECKS.length * 100);
  const missing = CHECKS.filter((c) => !c[2]);

  const tabs = [
    { id: 'profile', label: 'Profile', icon: ICON.profile },
    { id: 'identity', label: 'Tax & Legal', icon: ICON.identity },
    { id: 'addresses', label: 'Addresses', icon: ICON.address, badge: (addresses ?? []).length },
    { id: 'contacts', label: 'Contacts', icon: ICON.contact, badge: (contacts ?? []).length },
    { id: 'fiscal', label: 'Fiscal & Locale', icon: ICON.fiscal },
  ];

  return (
    <div className="pc org">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Workspace · Organization</div>
          <h1 className="pc-title">{org.company_name || 'Organization'}</h1>
          <div className="pc-sub">Company identity, tax &amp; legal registration, locations, key contacts and fiscal calendar — one operating record, every field editable and audit-logged.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => window.print()}>{ICON.export}<span>Export</span></button>
        </div>
      </div>

      <div className="org-band">
        <Card><CardBody>
          <div className="phrow" style={{ marginBottom: 0 }} />
          <div className="org-snap">
            <span className="org-logo">{org.logo_path ? <img src={org.logo_path} alt="" onError={(e) => { e.currentTarget.style.display = 'none'; }} /> : initials(org.company_name)}</span>
            <div>
              <div className="org-snap__name">{org.company_name || '—'}</div>
              <div className="org-snap__legal">{[org.legal_name, org.registration_number && `Reg. ${org.registration_number}`].filter(Boolean).join(' · ') || 'No legal name set'}</div>
              <div className="org-snap__chips">
                {org.industry && <span className="org-chip org-chip--accent">{org.industry}</span>}
                {org.company_size && <span className="org-chip">{org.company_size} employees</span>}
                {(org.country || org.currency) && <span className="org-chip">{[org.country, org.currency].filter(Boolean).join(' · ')}</span>}
              </div>
              <div className="org-snap__meta">
                {org.website && <a href={org.website} target="_blank" rel="noreferrer">🌐 {org.website.replace(/^https?:\/\//, '')}</a>}
                {(org.email || org.phone) && <span>{[org.email && `✉ ${org.email}`, org.phone && `☎ ${org.phone}`].filter(Boolean).join(' · ')}</span>}
              </div>
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Completeness</h2><div className="pc-panel-h__sub">Fill the gaps</div></div><span className="org-count">{done} / {CHECKS.length}</span></div>
          <div className="org-comp">
            <Donut segments={[{ color: 'var(--aeos-primary)', value: done }, { color: 'var(--aeos-border)', value: CHECKS.length - done }]} centerValue={`${pct}%`} centerLabel="complete" size={104} />
            <div className="org-comp__missing">
              {missing.slice(0, 5).map((m) => <div key={m[0]} className="org-comp__row"><span className="org-comp__dot" style={{ background: 'var(--aeos-warning)' }} />{m[1]}</div>)}
              {missing.length === 0 && <div className="org-comp__row org-comp__done"><span className="org-comp__dot" style={{ background: 'var(--aeos-success)' }} />All set — nice.</div>}
              {missing.length > 5 && <div className="org-comp__row org-comp__done">+{missing.length - 5} more</div>}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Key facts</h2><div className="pc-panel-h__sub">Live from the record</div></div></div>
          <div className="org-facts">
            {[
              ['Registration', org.registration_number],
              ['Tax ID', filled(org.tax_id) ? '•••• set' : ''],
              ['Fiscal year', (org.fiscal_year_start || org.fiscal_year_end) ? `${org.fiscal_year_start || '—'} → ${org.fiscal_year_end || '—'}` : ''],
              ['Timezone', org.timezone],
              ['Date format', org.date_format],
              ['VAT number', org.vat_number],
              ['Addresses', String((addresses ?? []).length)],
              ['Contacts', String((contacts ?? []).length)],
            ].map(([k, v]) => (
              <div className="org-fact" key={k}><span className="org-fact__k">{k}</span><span className={`org-fact__v${filled(v) ? '' : ' is-empty'}`}>{filled(v) ? v : 'not set'}</span></div>
            ))}
          </div>
        </CardBody></Card>
      </div>

      <div className="org-tabs" role="tablist">
        {tabs.map((t) => (
          <button key={t.id} type="button" role="tab" aria-selected={active === t.id} className={`org-tab${active === t.id ? ' is-active' : ''}${dirty[t.id] ? ' is-dirty' : ''}`} onClick={() => setActive(t.id)}>
            {t.icon}<span>{t.label}</span>{t.badge > 0 && <span className="org-tab__badge">{t.badge}</span>}
          </button>
        ))}
      </div>

      <ProfileForm org={org} active={active} report={report} />
      <IdentityForm org={org} active={active} report={report} />
      <AddressesForm addresses={addresses} active={active} report={report} />
      <ContactsForm contacts={contacts} active={active} report={report} />
      <FiscalForm org={org} active={active} report={report} />
    </div>
  );
}

OrganizationIndex.layout = (page) => (
  <App title="Organization" railTitle="Organization"
    rail={<Rail org={page.props.org ?? {}} addresses={page.props.addresses ?? []} contacts={page.props.contacts ?? []}
      pct={(() => { const o = page.props.org ?? {}; const a = page.props.addresses ?? []; const c = page.props.contacts ?? [];
        const checks = [o.company_name, o.legal_name, o.registration_number, o.industry, o.company_size, o.logo_path, o.website, o.phone, o.email, o.tax_id, o.vat_number, o.country, o.currency, o.timezone, a.length ? 'y' : '', c.length ? 'y' : ''];
        return Math.round(checks.filter((v) => v !== undefined && v !== null && String(v).trim() !== '').length / checks.length * 100); })()} />}>
    {page}
  </App>
);
