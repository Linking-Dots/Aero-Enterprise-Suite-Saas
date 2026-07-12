/**
 * Settings command center — unified configuration control room.
 *
 * One page subsumes the nine former settings sub-pages (General, Localization,
 * Branding, Security, Password Policy, IP Access, SMTP, Email Templates, API &
 * Integrations). Every settings GET route renders this component with `section`
 * + that section's data + a shared health `summary`; the grouped left rail
 * navigates between sections (Inertia visit, so deep links + HRMAC gates hold).
 * All mutation endpoints are unchanged.
 */
import { useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Card, CardBody, useToast, useHRMAC, BrandStudio } from '@aero/ui';
import App from '@/Pages/App.jsx';

import '../../Platform/Admin/Products/products.css';
import './settings.css';

/* ---------------- icons ---------------- */
const ic = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const I = {
  cog: ic(<><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" /></>),
  globe: ic(<><circle cx="12" cy="12" r="10" /><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20" /></>),
  photo: ic(<><rect x="3" y="3" width="18" height="18" rx="2" /><circle cx="9" cy="9" r="2" /><path d="m21 15-5-5L5 21" /></>),
  shield: ic(<><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /></>),
  key: ic(<><circle cx="8" cy="15" r="4" /><path d="m10.85 12.15 8.15-8.15 2 2M17 5l2 2" /></>),
  lock: ic(<><rect x="3" y="11" width="18" height="11" rx="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /></>),
  mail: ic(<><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 6-10 7L2 6" /></>),
  doc: ic(<><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" /><path d="M14 2v6h6" /></>),
  plug: ic(<><path d="M9 2v6M15 2v6M6 8h12v3a6 6 0 0 1-12 0Z" /><path d="M12 17v5" /></>),
  export: ic(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  shieldOk: ic(<><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" /><path d="m9 12 2 2 4-4" /></>),
  dev: ic(<><path d="m18 16 4-4-4-4M6 8l-4 4 4 4M14.5 4l-5 16" /></>),
};

/* ---------------- section groups (mirror settingsSections.js + AccessDrawer nav) ---------------- */
const GROUPS = [
  { label: 'General', items: [
    { id: 'general', label: 'General', route: 'core.settings.system', icon: I.cog },
    { id: 'localization', label: 'Localization', route: 'core.settings.localization', icon: I.globe },
    { id: 'branding', label: 'Branding', route: 'core.settings.branding', icon: I.photo },
  ] },
  { label: 'Security', items: [
    { id: 'security', label: 'Security & 2FA', route: 'core.settings.security', icon: I.shield },
    { id: 'password', label: 'Password Policy', route: 'core.settings.password-policy', icon: I.key },
    { id: 'ip', label: 'IP Access', route: 'core.settings.ip-whitelist', icon: I.lock },
  ] },
  { label: 'Communications', items: [
    { id: 'mail', label: 'Email / SMTP', route: 'core.settings.mail', icon: I.mail },
    { id: 'templates', label: 'Email Templates', route: 'core.settings.email-templates.index', icon: I.doc, count: 'templates' },
    { id: 'integrations', label: 'Integrations', route: 'core.settings.integrations.index', icon: I.plug, count: 'integrations' },
  ] },
];

/* ---------------- primitives ---------------- */
function Field({ label, req, error, hint, span, children }) {
  return (
    <label className={`pc-field${span ? ' set-span2' : ''}`}>
      <span className="pc-field__label">{label}{req && <span style={{ color: 'var(--aeos-danger)' }}> *</span>}</span>
      {children}
      {hint && !error && <span className="pc-field__hint">{hint}</span>}
      {error && <span className="pc-field__err">{error}</span>}
    </label>
  );
}
const Txt = ({ v, on, ...p }) => <input className="pc-input" value={v ?? ''} onChange={(e) => on(e.target.value)} {...p} />;
const Num = ({ v, on, ...p }) => <input className="pc-input" type="number" value={v ?? 0} onChange={(e) => on(Number(e.target.value))} {...p} />;
const Sel = ({ v, on, opts }) => (
  <select className="pc-input" value={v ?? ''} onChange={(e) => on(e.target.value)}>
    {opts.map((o) => { const [val, lab] = Array.isArray(o) ? o : [o, o]; return <option key={val} value={val}>{lab}</option>; })}
  </select>
);
function ToggleRow({ label, desc, checked, onChange, disabled }) {
  return (
    <div className="set-toggle">
      <div className="set-toggle__m"><b>{label}</b>{desc && <span>{desc}</span>}</div>
      <button type="button" className={`set-sw${checked ? '' : ' is-off'}`} role="switch" aria-checked={checked} aria-label={label} disabled={disabled} onClick={() => onChange(!checked)} />
    </div>
  );
}
function Shell({ title, desc, canEdit, dirty, processing, onSave, onReset, extra, children }) {
  return (
    <Card><CardBody>
      <div className="set-sec__head">
        <div><div className="set-sec__title">{title}</div><div className="set-sec__desc">{desc}</div></div>
        <div className="set-sec__actions">
          {extra}
          {canEdit ? (<>
            {onReset && <button type="button" className="pc-btn pc-btn--sm" onClick={onReset} disabled={!dirty || processing}>Reset</button>}
            {onSave && <button type="submit" className="pc-btn pc-btn--sm pc-btn--primary" disabled={!dirty || processing} onClick={onSave}>{processing ? 'Saving…' : 'Save changes'}</button>}
          </>) : <span className="set-tag">Read-only</span>}
        </div>
      </div>
      {children}
    </CardBody></Card>
  );
}

/* ================= sections ================= */
function GeneralSection({ general }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.general.edit');
  const f = useForm({ ...general });
  const [email, setEmail] = useState(''); const [phone, setPhone] = useState(''); const [out, setOut] = useState(null);
  const save = (e) => { e.preventDefault(); f.put(route('core.settings.system.update'), { preserveScroll: true, onSuccess: () => toast.success('General settings saved.'), onError: () => toast.error('Fix the errors below.') }); };
  const test = async (kind) => {
    try {
      const url = kind === 'email' ? route('core.settings.system.test-email') : route('core.settings.system.test-sms');
      const { data } = await axios.post(url, kind === 'email' ? { email } : { phone });
      setOut({ ok: true, msg: data.message || 'Sent.' });
    } catch (err) { setOut({ ok: false, msg: err?.response?.data?.message || 'Failed to send.' }); }
  };
  return (
    <form onSubmit={save}>
      <Shell title="General" desc="Core application settings for this workspace." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <div className="set-grid">
          <Field label="Application name" error={f.errors.app_name} span><Txt v={f.data.app_name} on={(v) => f.setData('app_name', v)} /></Field>
          <Field label="Application URL" error={f.errors.app_url}><Txt v={f.data.app_url} on={(v) => f.setData('app_url', v)} type="url" /></Field>
          <Field label="Support email" error={f.errors.support_email}><Txt v={f.data.support_email} on={(v) => f.setData('support_email', v)} type="email" /></Field>
          <Field label="Timezone" error={f.errors.timezone}><Txt v={f.data.timezone} on={(v) => f.setData('timezone', v)} placeholder="UTC" /></Field>
          <Field label="Date format" error={f.errors.date_format}><Txt v={f.data.date_format} on={(v) => f.setData('date_format', v)} placeholder="Y-m-d" /></Field>
          <Field label="Time format" error={f.errors.time_format}><Txt v={f.data.time_format} on={(v) => f.setData('time_format', v)} placeholder="H:i" /></Field>
        </div>
      </Shell>
      {canEdit && (
        <div style={{ marginTop: 'var(--aeos-space-4)' }}>
          <Card><CardBody>
            <div className="set-sec__title" style={{ fontSize: 'var(--aeos-text-base)' }}>Delivery tests</div>
            <div className="set-sec__desc" style={{ marginBottom: 'var(--aeos-space-3)' }}>Send a test email or SMS using this workspace's configured providers.</div>
            <div className="set-test">
              <Field label="Test email to"><Txt v={email} on={setEmail} type="email" placeholder="you@example.com" /></Field>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => test('email')} disabled={!email}>Send test email</button>
              <Field label="Test SMS to"><Txt v={phone} on={setPhone} placeholder="+1 555-0100" /></Field>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => test('sms')} disabled={!phone}>Send test SMS</button>
            </div>
            {out && <div className={`set-testout ${out.ok ? 'set-testout--ok' : 'set-testout--bad'}`}>{out.msg}</div>}
          </CardBody></Card>
        </div>
      )}
    </form>
  );
}

function SecuritySection({ security }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.security.edit');
  const f = useForm({ ...security });
  const save = (e) => { e.preventDefault(); f.post(route('core.settings.security.update'), { preserveScroll: true, onSuccess: () => toast.success('Security settings saved.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <form onSubmit={save}>
      <Shell title="Security & 2FA" desc="Two-factor requirements, session lifetime and account lockout." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <ToggleRow label="Require 2FA for administrators" desc="Admins must enrol two-factor before they can sign in" checked={!!f.data.require_2fa_admins} onChange={(v) => f.setData('require_2fa_admins', v)} disabled={!canEdit} />
        <div className="set-grid" style={{ marginTop: 'var(--aeos-space-3)' }}>
          <Field label="Session lifetime (minutes)" error={f.errors.session_lifetime} hint="Idle sessions expire after this"><Num v={f.data.session_lifetime} on={(v) => f.setData('session_lifetime', v)} min={5} max={43200} /></Field>
          <Field label="Max failed login attempts" error={f.errors.max_failed_attempts} hint="Lock the account after this many failures"><Num v={f.data.max_failed_attempts} on={(v) => f.setData('max_failed_attempts', v)} min={1} max={20} /></Field>
          <Field label="Lockout duration (minutes)" error={f.errors.lockout_duration} hint="0 = manual unlock required"><Num v={f.data.lockout_duration} on={(v) => f.setData('lockout_duration', v)} min={0} max={1440} /></Field>
        </div>
      </Shell>
    </form>
  );
}

function LocalizationSection({ localization, timezones = [] }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.localization.edit');
  const f = useForm({
    timezone: localization?.timezone ?? 'UTC', currency: localization?.currency ?? 'USD', locale: localization?.locale ?? 'en',
    date_format: localization?.date_format ?? 'Y-m-d', time_format: localization?.time_format ?? 'H:i', first_day_of_week: localization?.first_day_of_week ?? 1,
  });
  const tzOpts = (timezones.length ? timezones : ['UTC', 'America/New_York', 'Europe/London', 'Asia/Dhaka']).map((t) => [t, t]);
  const save = (e) => { e.preventDefault(); f.put(route('core.settings.localization.update'), { preserveScroll: true, onSuccess: () => toast.success('Localization saved.'), onError: () => toast.error('Fix the errors below.') }); };
  return (
    <form onSubmit={save}>
      <Shell title="Localization" desc="Locale, currency, timezone and date/time formats." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <div className="set-grid">
          <Field label="Language / locale" error={f.errors.locale}><Sel v={f.data.locale} on={(v) => f.setData('locale', v)} opts={[['en', 'English'], ['es', 'Spanish'], ['fr', 'French'], ['de', 'German'], ['bn', 'Bengali'], ['ar', 'Arabic'], ['hi', 'Hindi']]} /></Field>
          <Field label="Currency" error={f.errors.currency}><Sel v={f.data.currency} on={(v) => f.setData('currency', v)} opts={['USD', 'EUR', 'GBP', 'BDT', 'INR', 'AED', 'SGD', 'JPY', 'AUD', 'CAD']} /></Field>
          <Field label="Timezone" error={f.errors.timezone} span><Sel v={f.data.timezone} on={(v) => f.setData('timezone', v)} opts={tzOpts} /></Field>
          <Field label="Date format" error={f.errors.date_format} hint="PHP format, e.g. Y-m-d"><Txt v={f.data.date_format} on={(v) => f.setData('date_format', v)} /></Field>
          <Field label="Time format" error={f.errors.time_format} hint="e.g. H:i (24h) or h:i A (12h)"><Txt v={f.data.time_format} on={(v) => f.setData('time_format', v)} /></Field>
          <Field label="First day of week" error={f.errors.first_day_of_week}><Sel v={String(f.data.first_day_of_week)} on={(v) => f.setData('first_day_of_week', Number(v))} opts={[['0', 'Sunday'], ['1', 'Monday'], ['6', 'Saturday']]} /></Field>
        </div>
      </Shell>
    </form>
  );
}

function BrandingSection({ branding }) {
  const canEdit = useHRMAC('core.settings.branding.update');
  // The shared white-label editor — same component the platform mounts.
  return (
    <BrandStudio
      branding={branding ?? {}}
      updateUrl={route('core.settings.branding.update')}
      resetUrl={route('core.settings.branding.reset')}
      canEdit={canEdit}
      scopeLabel="workspace"
      upsellHref="/subscription"
    />
  );
}

function MailSection({ mail, emailSettings }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.mail_settings.update');
  const canTest = useHRMAC('core.settings.mail_settings.test');
  const pwSet = !!emailSettings?.password_set;
  const f = useForm({ host: mail?.host ?? '', port: mail?.port ?? '587', encryption: mail?.encryption ?? 'tls', username: mail?.username ?? '', password: '', from_name: mail?.from_name ?? '', from_address: mail?.from_email ?? '' });
  const [email, setEmail] = useState(''); const [out, setOut] = useState(null);
  const save = (e) => { e.preventDefault(); f.post(route('core.settings.mail.update'), { preserveScroll: true, onSuccess: () => toast.success('Mail settings saved.'), onError: () => toast.error('Fix the errors below.') }); };
  const test = async () => { try { const { data } = await axios.post(route('core.settings.mail.test'), { email }); setOut({ ok: data.success !== false, msg: data.message || 'Test email sent.' }); } catch (err) { setOut({ ok: false, msg: err?.response?.data?.message || 'Failed to send.' }); } };
  return (
    <form onSubmit={save}>
      <Shell title="Email / SMTP" desc="Outbound mail server for this workspace's transactional email." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <div className="set-grid">
          <Field label="SMTP host" req error={f.errors.host}><Txt v={f.data.host} on={(v) => f.setData('host', v)} placeholder="smtp.mailgun.org" /></Field>
          <Field label="Port" req error={f.errors.port}><Txt v={f.data.port} on={(v) => f.setData('port', v)} placeholder="587" /></Field>
          <Field label="Encryption" error={f.errors.encryption}><Sel v={f.data.encryption} on={(v) => f.setData('encryption', v)} opts={[['tls', 'TLS'], ['ssl', 'SSL'], ['', 'None']]} /></Field>
          <Field label="Username" error={f.errors.username}><Txt v={f.data.username} on={(v) => f.setData('username', v)} /></Field>
          <Field label="Password" error={f.errors.password} hint={pwSet ? 'Saved — leave blank to keep' : undefined}><Txt v={f.data.password} on={(v) => f.setData('password', v)} type="password" placeholder={pwSet ? '••••••••' : ''} /></Field>
          <div className="set-subhead">From</div>
          <Field label="From name" error={f.errors.from_name}><Txt v={f.data.from_name} on={(v) => f.setData('from_name', v)} placeholder="Democorp" /></Field>
          <Field label="From address" req error={f.errors.from_address}><Txt v={f.data.from_address} on={(v) => f.setData('from_address', v)} type="email" placeholder="no-reply@example.com" /></Field>
        </div>
      </Shell>
      {canTest && (
        <div style={{ marginTop: 'var(--aeos-space-4)' }}>
          <Card><CardBody>
            <div className="set-sec__title" style={{ fontSize: 'var(--aeos-text-base)' }}>Send a test email</div>
            <div className="set-test"><Field label="Deliver to"><Txt v={email} on={setEmail} type="email" placeholder="you@example.com" /></Field>
              <button type="button" className="pc-btn pc-btn--sm" onClick={test} disabled={!email}>Send test</button></div>
            {out && <div className={`set-testout ${out.ok ? 'set-testout--ok' : 'set-testout--bad'}`}>{out.msg}</div>}
          </CardBody></Card>
        </div>
      )}
    </form>
  );
}

function IntegrationCard({ name, iconLabel, k, fields, initial, canEdit }) {
  const toast = useToast();
  const f = useForm({ enabled: !!initial.enabled, ...Object.fromEntries(fields.map((fl) => [fl.k, initial[fl.k] ?? ''])) });
  const save = () => router.post(route('core.settings.integrations.update', k), f.data, { preserveScroll: true, onSuccess: () => toast.success(`${name} saved.`), onError: () => toast.error('Could not save integration.') });
  return (
    <div className="set-intg__card">
      <div className="set-intg__top">
        <div className="set-intg__name">{iconLabel} {name}</div>
        <button type="button" className={`set-sw${f.data.enabled ? '' : ' is-off'}`} role="switch" aria-checked={f.data.enabled} aria-label={`Enable ${name}`} disabled={!canEdit} onClick={() => f.setData('enabled', !f.data.enabled)} />
      </div>
      <div className="set-grid" style={{ gridTemplateColumns: '1fr' }}>
        {fields.map((fl) => <Field key={fl.k} label={fl.label}><Txt v={f.data[fl.k]} on={(v) => f.setData(fl.k, v)} placeholder={fl.ph} disabled={!canEdit} /></Field>)}
      </div>
      {canEdit && <div style={{ marginTop: 'var(--aeos-space-2)', textAlign: 'right' }}><button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={save} disabled={f.processing}>Save</button></div>}
    </div>
  );
}

function IntegrationsSection({ integrations }) {
  const canEdit = useHRMAC('core.settings.integrations.configure');
  const g = integrations ?? {};
  return (
    <Card><CardBody>
      <div className="set-sec__head"><div><div className="set-sec__title">API & Integrations</div><div className="set-sec__desc">Connect Slack, Google Workspace, Microsoft 365 and Zapier.</div></div></div>
      <div className="set-intg">
        <IntegrationCard name="Slack" iconLabel="💬" k="slack" canEdit={canEdit} initial={g.slack ?? {}} fields={[{ k: 'webhook_url', label: 'Webhook URL', ph: 'https://hooks.slack.com/…' }, { k: 'channel', label: 'Channel', ph: '#general' }]} />
        <IntegrationCard name="Google Workspace" iconLabel="🅶" k="google_workspace" canEdit={canEdit} initial={g.google_workspace ?? {}} fields={[{ k: 'client_id', label: 'Client ID', ph: '' }, { k: 'domain', label: 'Domain', ph: 'company.com' }]} />
        <IntegrationCard name="Microsoft 365" iconLabel="Ⓜ" k="microsoft_365" canEdit={canEdit} initial={g.microsoft_365 ?? {}} fields={[{ k: 'tenant_id', label: 'Tenant ID', ph: '' }, { k: 'client_id', label: 'Client ID', ph: '' }]} />
        <IntegrationCard name="Zapier" iconLabel="⚡" k="zapier" canEdit={canEdit} initial={g.zapier ?? {}} fields={[{ k: 'api_key', label: 'API Key', ph: '' }]} />
      </div>
    </CardBody></Card>
  );
}

function PasswordPolicySection({ policy }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.password_policy.edit');
  const f = useForm({
    min_length: policy?.min_length ?? 8, max_length: policy?.max_length ?? 128,
    require_uppercase: policy?.require_uppercase ?? true, require_lowercase: policy?.require_lowercase ?? true,
    require_numbers: policy?.require_numbers ?? true, require_symbols: policy?.require_symbols ?? false,
    password_expiry_days: policy?.password_expiry_days ?? 0, password_history_count: policy?.password_history_count ?? 5,
    prevent_common_passwords: policy?.prevent_common_passwords ?? true, prevent_username_in_password: policy?.prevent_username_in_password ?? true,
    max_consecutive_chars: policy?.max_consecutive_chars ?? 3,
  });
  const [pw, setPw] = useState(''); const [res, setRes] = useState(null);
  const save = (e) => { e.preventDefault(); f.put(route('core.settings.password-policy.update'), { preserveScroll: true, onSuccess: () => toast.success('Password policy saved.'), onError: () => toast.error('Fix the errors below.') }); };
  const test = async () => { try { const { data } = await axios.post(route('core.settings.password-policy.test'), { password: pw }); setRes(data); } catch (err) { setRes({ valid: false, errors: [err?.response?.data?.message || 'Test failed.'] }); } };
  return (
    <form onSubmit={save}>
      <Shell title="Password Policy" desc="Complexity, expiry and reuse rules for member passwords." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <div className="set-grid">
          <Field label="Minimum length" error={f.errors.min_length}><Num v={f.data.min_length} on={(v) => f.setData('min_length', v)} min={6} max={128} /></Field>
          <Field label="Maximum length" error={f.errors.max_length}><Num v={f.data.max_length} on={(v) => f.setData('max_length', v)} min={8} max={256} /></Field>
          <Field label="Expiry (days, 0 = never)" error={f.errors.password_expiry_days}><Num v={f.data.password_expiry_days} on={(v) => f.setData('password_expiry_days', v)} min={0} max={3650} /></Field>
          <Field label="Password history" error={f.errors.password_history_count} hint="Block reuse of last N"><Num v={f.data.password_history_count} on={(v) => f.setData('password_history_count', v)} min={0} max={50} /></Field>
          <Field label="Max repeated chars" error={f.errors.max_consecutive_chars} hint="e.g. block 'aaaa'"><Num v={f.data.max_consecutive_chars} on={(v) => f.setData('max_consecutive_chars', v)} min={0} max={10} /></Field>
        </div>
        <div className="set-subhead" style={{ marginBottom: 'var(--aeos-space-1)' }}>Complexity</div>
        <ToggleRow label="Require uppercase" checked={!!f.data.require_uppercase} onChange={(v) => f.setData('require_uppercase', v)} disabled={!canEdit} />
        <ToggleRow label="Require lowercase" checked={!!f.data.require_lowercase} onChange={(v) => f.setData('require_lowercase', v)} disabled={!canEdit} />
        <ToggleRow label="Require numbers" checked={!!f.data.require_numbers} onChange={(v) => f.setData('require_numbers', v)} disabled={!canEdit} />
        <ToggleRow label="Require symbols" checked={!!f.data.require_symbols} onChange={(v) => f.setData('require_symbols', v)} disabled={!canEdit} />
        <ToggleRow label="Block common passwords" checked={!!f.data.prevent_common_passwords} onChange={(v) => f.setData('prevent_common_passwords', v)} disabled={!canEdit} />
        <ToggleRow label="Block username in password" checked={!!f.data.prevent_username_in_password} onChange={(v) => f.setData('prevent_username_in_password', v)} disabled={!canEdit} />
      </Shell>
      <div style={{ marginTop: 'var(--aeos-space-4)' }}>
        <Card><CardBody>
          <div className="set-sec__title" style={{ fontSize: 'var(--aeos-text-base)' }}>Test a password</div>
          <div className="set-test"><Field label="Password"><Txt v={pw} on={setPw} type="text" placeholder="Try a candidate password" /></Field>
            <button type="button" className="pc-btn pc-btn--sm" onClick={test} disabled={!pw}>Check</button></div>
          {res && <div className={`set-testout ${res.valid ? 'set-testout--ok' : 'set-testout--bad'}`}>{res.valid ? `Valid — strength: ${res.strength?.label ?? '—'} (${res.strength?.score ?? 0}/100)` : (res.errors || []).join(' · ')}</div>}
        </CardBody></Card>
      </div>
    </form>
  );
}

function IpRuleTable({ title, rows, onAdd, onRemove, canEdit }) {
  const [ip, setIp] = useState(''); const [label, setLabel] = useState('');
  return (
    <div>
      <div className="set-subhead" style={{ marginBottom: 'var(--aeos-space-2)' }}>{title}</div>
      {rows.length === 0 ? <div className="set-empty">No entries.</div> : (
        <table className="set-table"><thead><tr><th>IP / CIDR</th><th>Label</th><th></th></tr></thead>
          <tbody>{rows.map((r, i) => (
            <tr key={i}><td className="set-mono">{r.ip}</td><td>{r.label || '—'}</td>
              <td className="set-rowbtns">{canEdit && <button type="button" className="set-lnk set-lnk--danger" onClick={() => onRemove(i)}>Remove</button>}</td></tr>
          ))}</tbody>
        </table>
      )}
      {canEdit && (
        <div className="set-test" style={{ marginTop: 'var(--aeos-space-2)' }}>
          <Field label="IP / CIDR"><Txt v={ip} on={setIp} placeholder="203.0.113.4 or 10.0.0.0/8" /></Field>
          <Field label="Label"><Txt v={label} on={setLabel} placeholder="Office" /></Field>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => { if (ip.trim()) { onAdd({ ip: ip.trim(), label: label.trim() }); setIp(''); setLabel(''); } }}>Add</button>
        </div>
      )}
    </div>
  );
}

function IpAccessSection({ config }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.ip_whitelist.edit');
  const f = useForm({
    mode: config?.mode ?? 'disabled', log_blocked: config?.log_blocked ?? true, notify_on_blocked: config?.notify_on_blocked ?? false,
    whitelist: config?.whitelist ?? [], blacklist: config?.blacklist ?? [],
  });
  const [testIp, setTestIp] = useState(''); const [out, setOut] = useState(null);
  const save = (e) => { e.preventDefault(); f.put(route('core.settings.ip-whitelist.update'), { preserveScroll: true, onSuccess: () => toast.success('IP access saved.'), onError: () => toast.error('Fix the errors below.') }); };
  const doTest = async () => { try { const { data } = await axios.post(route('core.settings.ip-whitelist.test-ip'), { ip: testIp }); setOut({ ok: data.allowed !== false, msg: data.message || (data.allowed ? 'Allowed.' : 'Blocked.') }); } catch (err) { setOut({ ok: false, msg: err?.response?.data?.message || 'Test failed.' }); } };
  return (
    <form onSubmit={save}>
      <Shell title="IP Access Control" desc="Restrict sign-in by IP allow/deny lists." canEdit={canEdit} dirty={f.isDirty} processing={f.processing} onSave={save} onReset={() => f.reset()}>
        <div className="set-grid">
          <Field label="Mode" error={f.errors.mode} hint="Whitelist = only listed IPs; Blacklist = block listed"><Sel v={f.data.mode} on={(v) => f.setData('mode', v)} opts={[['disabled', 'Disabled'], ['whitelist', 'Whitelist (allow-list)'], ['blacklist', 'Blacklist (deny-list)']]} /></Field>
        </div>
        <div style={{ marginTop: 'var(--aeos-space-2)' }}>
          <ToggleRow label="Log blocked attempts" checked={!!f.data.log_blocked} onChange={(v) => f.setData('log_blocked', v)} disabled={!canEdit} />
          <ToggleRow label="Notify admins on block" checked={!!f.data.notify_on_blocked} onChange={(v) => f.setData('notify_on_blocked', v)} disabled={!canEdit} />
        </div>
        <div style={{ marginTop: 'var(--aeos-space-3)', display: 'grid', gap: 'var(--aeos-space-4)' }}>
          <IpRuleTable title="Allow-list" rows={f.data.whitelist} canEdit={canEdit} onAdd={(r) => f.setData('whitelist', [...f.data.whitelist, r])} onRemove={(i) => f.setData('whitelist', f.data.whitelist.filter((_, j) => j !== i))} />
          <IpRuleTable title="Deny-list" rows={f.data.blacklist} canEdit={canEdit} onAdd={(r) => f.setData('blacklist', [...f.data.blacklist, r])} onRemove={(i) => f.setData('blacklist', f.data.blacklist.filter((_, j) => j !== i))} />
        </div>
      </Shell>
      <div style={{ marginTop: 'var(--aeos-space-4)' }}>
        <Card><CardBody>
          <div className="set-sec__title" style={{ fontSize: 'var(--aeos-text-base)' }}>Test an IP</div>
          <div className="set-test"><Field label="IP address"><Txt v={testIp} on={setTestIp} placeholder="203.0.113.4" /></Field>
            <button type="button" className="pc-btn pc-btn--sm" onClick={doTest} disabled={!testIp}>Check access</button></div>
          {out && <div className={`set-testout ${out.ok ? 'set-testout--ok' : 'set-testout--bad'}`}>{out.msg}</div>}
        </CardBody></Card>
      </div>
    </form>
  );
}

function TemplateModal({ template, onClose }) {
  const toast = useToast();
  const isNew = !template;
  const f = useForm({
    name: template?.name ?? '', slug: template?.slug ?? '', subject: template?.subject ?? '', category: template?.category ?? 'transactional',
    body_html: template?.body_html ?? '', body_text: template?.body_text ?? '', is_active: template?.is_active ?? true,
  });
  const submit = (e) => {
    e.preventDefault();
    const opts = { preserveScroll: true, onSuccess: () => { toast.success(isNew ? 'Template created.' : 'Template saved.'); onClose(); }, onError: () => toast.error('Fix the errors below.') };
    if (isNew) f.post(route('core.settings.email-templates.store'), opts);
    else f.put(route('core.settings.email-templates.update', template.id), opts);
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true" style={{ maxWidth: 720 }}>
        <h2 className="pc-modal__title">{isNew ? 'New email template' : `Edit ${template.name}`}</h2>
        <form className="pc-form" onSubmit={submit}>
          <div className="set-grid">
            <Field label="Name" req error={f.errors.name}><Txt v={f.data.name} on={(v) => f.setData('name', v)} /></Field>
            <Field label="Slug / key" req error={f.errors.slug}><Txt v={f.data.slug} on={(v) => f.setData('slug', v)} placeholder="welcome" /></Field>
            <Field label="Subject" req error={f.errors.subject} span><Txt v={f.data.subject} on={(v) => f.setData('subject', v)} placeholder="Welcome to {{company}}" /></Field>
            <Field label="Category" error={f.errors.category}><Sel v={f.data.category} on={(v) => f.setData('category', v)} opts={[['transactional', 'Transactional'], ['marketing', 'Marketing'], ['system', 'System']]} /></Field>
          </div>
          <Field label="HTML body" req error={f.errors.body_html}><textarea className="pc-input" rows={6} value={f.data.body_html} onChange={(e) => f.setData('body_html', e.target.value)} placeholder="<p>Hello {{name}}…</p>" /></Field>
          <Field label="Plain-text body" error={f.errors.body_text}><textarea className="pc-input" rows={3} value={f.data.body_text} onChange={(e) => f.setData('body_text', e.target.value)} /></Field>
          <ToggleRow label="Active" desc="Inactive templates are not sent" checked={!!f.data.is_active} onChange={(v) => f.setData('is_active', v)} />
          <div className="pc-modal__actions"><span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={f.processing || !f.data.name.trim() || !f.data.subject.trim()}>{f.processing ? 'Saving…' : (isNew ? 'Create' : 'Save')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

function EmailTemplatesSection({ templates = [] }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.settings.email_templates.create');
  const canEdit = useHRMAC('core.settings.email_templates.edit');
  const canDelete = useHRMAC('core.settings.email_templates.delete');
  const [editor, setEditor] = useState(undefined);
  const del = (t) => { if (window.confirm(`Delete “${t.name}”?`)) router.delete(route('core.settings.email-templates.destroy', t.id), { preserveScroll: true, onSuccess: () => toast.success('Template deleted.') }); };
  return (
    <Card><CardBody>
      <div className="set-sec__head">
        <div><div className="set-sec__title">Email Templates</div><div className="set-sec__desc">Transactional and system emails members receive.</div></div>
        {canCreate && <div className="set-sec__actions"><button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setEditor(null)}>+ New template</button></div>}
      </div>
      {templates.length === 0 ? <div className="set-empty">No templates yet.{canCreate ? ' Create your first.' : ''}</div> : (
        <table className="set-table">
          <thead><tr><th>Template</th><th>Subject</th><th>Key</th><th>Status</th><th></th></tr></thead>
          <tbody>{templates.map((t) => (
            <tr key={t.id}>
              <td><b>{t.name}</b>{t.category && <div className="set-mono">{t.category}</div>}</td>
              <td>{t.subject}</td>
              <td className="set-mono">{t.slug}</td>
              <td><span className={`set-tag${t.is_active ? ' set-tag--on' : ''}`}>{t.is_active ? 'Active' : 'Draft'}</span></td>
              <td className="set-rowbtns">
                <a className="set-lnk" href={route('core.settings.email-templates.preview', t.id)} target="_blank" rel="noreferrer">Preview</a>
                {canEdit && <button type="button" className="set-lnk" onClick={() => setEditor(t)}>Edit</button>}
                {canDelete && !t.is_locked && <button type="button" className="set-lnk set-lnk--danger" onClick={() => del(t)}>Delete</button>}
              </td>
            </tr>
          ))}</tbody>
        </table>
      )}
      {editor !== undefined && <TemplateModal template={editor} onClose={() => setEditor(undefined)} />}
    </CardBody></Card>
  );
}

/* ---------------- rail + health band ---------------- */
function healthPill(ok, warn) { return ok ? 'set-pill--ok' : (warn ? 'set-pill--warn' : 'set-pill--bad'); }

function SectionRail({ active, summary }) {
  const counts = { templates: summary?.developer?.templates ?? 0, integrations: summary?.developer?.integrations ?? 0 };
  const go = (item) => router.get(route(item.route), {}, { preserveScroll: true });
  return (
    <div className="set-rail">
      {GROUPS.map((g) => (
        <div className="set-rgroup" key={g.label}>
          <div className="set-rglabel">{g.label}</div>
          {g.items.map((it) => (
            <button key={it.id} type="button" className={`set-ritem${active === it.id ? ' is-active' : ''}`} onClick={() => go(it)}>
              {it.icon}<span>{it.label}</span>{it.count && counts[it.count] > 0 && <span className="set-ritem__c">{counts[it.count]}</span>}
            </button>
          ))}
        </div>
      ))}
    </div>
  );
}

/* ================= page ================= */
export default function SettingsIndex(props) {
  const { section = 'general', summary } = props;
  const s = summary ?? {};

  const band = useMemo(() => ([
    { label: 'Security posture', icon: I.shieldOk, value: s.security?.require_2fa ? 'Enforced' : 'Standard',
      pill: s.security?.require_2fa ? ['2FA on', 'ok'] : ['2FA optional', 'warn'], desc: `lockout after ${s.security?.lockout ?? 5}` },
    { label: 'Email deliverability', icon: I.mail, value: s.email?.configured ? 'Configured' : 'Not set',
      pill: s.email?.configured ? ['SMTP live', 'ok'] : ['No SMTP', 'bad'], desc: s.email?.from || 'no from-address' },
    { label: 'Localization', icon: I.globe, value: s.localization?.locale ?? 'en',
      pill: null, desc: `${s.localization?.timezone ?? 'UTC'} · ${s.localization?.currency ?? '—'}` },
    { label: 'Developer', icon: I.dev, value: `${s.developer?.integrations ?? 0} live`,
      pill: null, desc: `${s.developer?.templates ?? 0} email templates` },
  ]), [s]);

  return (
    <div className="pc set">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Workspace · Configuration</div>
          <h1 className="pc-title">Settings</h1>
          <div className="pc-sub">Everything that configures this workspace — identity, security posture, email deliverability, localization and developer access — in one console. Every control HRMAC-gated and audit-logged.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => window.print()}>{I.export}<span>Export</span></button>
        </div>
      </div>

      <div className="set-band">
        {band.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="set-hl">{c.icon}{c.label}</div>
            <div className="set-hv">{c.value}</div>
            <div className="set-hd">{c.pill && <span className={`set-pill ${healthPill(c.pill[1] === 'ok', c.pill[1] === 'warn')}`}>{c.pill[0]}</span>}{c.desc}</div>
          </CardBody></Card>
        ))}
      </div>

      <div className="set-main">
        <SectionRail active={section} summary={s} />
        <div>
          {section === 'general' && <GeneralSection general={props.general ?? {}} />}
          {section === 'security' && <SecuritySection security={props.security ?? {}} />}
          {section === 'localization' && <LocalizationSection localization={props.localization} timezones={props.timezones} />}
          {section === 'branding' && <BrandingSection branding={props.branding ?? {}} />}
          {section === 'mail' && <MailSection mail={props.mail ?? {}} emailSettings={props.emailSettings ?? {}} />}
          {section === 'integrations' && <IntegrationsSection integrations={props.integrations} />}
          {section === 'password' && <PasswordPolicySection policy={props.policy ?? {}} />}
          {section === 'ip' && <IpAccessSection config={props.config ?? {}} />}
          {section === 'templates' && <EmailTemplatesSection templates={props.templates ?? []} />}
        </div>
      </div>
    </div>
  );
}

SettingsIndex.layout = (page) => (
  <App title="Settings" railTitle="Settings"
    rail={(() => {
      const s = page.props.summary ?? {};
      return (
        <div className="pc-rail set">
          <div>
            <div className="pc-panel-h__title">Settings</div>
            <div className="pc-rail__rows">
              <div className="pc-rail__row"><span>2FA</span><b>{s.security?.require_2fa ? 'Enforced' : 'Optional'}</b></div>
              <div className="pc-rail__row"><span>SMTP</span><b>{s.email?.configured ? 'Configured' : 'Not set'}</b></div>
              <div className="pc-rail__row"><span>Locale</span><b>{s.localization?.locale ?? 'en'}</b></div>
              <div className="pc-rail__row"><span>Timezone</span><b>{s.localization?.timezone ?? 'UTC'}</b></div>
              <div className="pc-rail__row"><span>Integrations</span><b>{s.developer?.integrations ?? 0}</b></div>
              <div className="pc-rail__row"><span>Templates</span><b>{s.developer?.templates ?? 0}</b></div>
            </div>
          </div>
        </div>
      );
    })()}>
    {page}
  </App>
);
