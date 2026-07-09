import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './onboarding.css';

const svg = (p) => (<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>);
const Glyph = {
  back: svg(<><path d="M19 12H5M12 19l-7-7 7-7" /></>),
  mail: svg(<><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 5L2 7" /></>),
};

/* ---------------- rail ---------------- */
function SettingsRail() {
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Onboarding</div>
        <div className="pc-rail__links" style={{ marginTop: 'var(--aeos-space-3)' }}>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/onboarding')}>{Glyph.back}<span>Back to console</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/email/templates')}>{Glyph.mail}<span>Email engine</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- toggle ---------------- */
function Toggle({ on, onChange, label }) {
  return (
    <button type="button" className="ob-sw" aria-pressed={on} aria-label={label} onClick={() => onChange(!on)} />
  );
}

/* ---------------- page ---------------- */
export default function Settings({ data }) {
  const d = data ?? {};
  const csrf = usePage().props.csrfToken;
  const [form, setForm] = useState(d.settings ?? {});
  const [rules, setRules] = useState(d.automation ?? []);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const templates = d.templates ?? [];

  const set = (k, v) => { setForm((f) => ({ ...f, [k]: v })); setSaved(false); };

  const post = (url, body) => fetch(url, {
    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: JSON.stringify(body),
  });

  const save = () => {
    setSaving(true);
    post(route('admin.onboarding.settings.update'), { settings: form })
      .then(() => { setSaved(true); })
      .finally(() => { setSaving(false); router.reload({ only: ['data'], preserveScroll: true }); });
  };

  const toggleRule = (id, active) => {
    setRules((rs) => rs.map((r) => (r.id === id ? { ...r, active } : r)));
    post(route('admin.onboarding.automation.toggle'), { rule_id: id, is_active: active });
  };

  return (
    <div className="pc obx">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Onboarding · Settings</div>
          <h1 className="pc-title">Onboarding settings</h1>
          <div className="pc-sub">Registration policy, hands-off automation and the lifecycle emails that guide every new tenant from signup to activation.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => router.visit('/onboarding')}>{Glyph.back}<span>Console</span></button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={save} disabled={saving}>{saving ? 'Saving…' : saved ? 'Saved ✓' : 'Save changes'}</button>
        </div>
      </div>

      <div className="ob-low">
        {/* registration policy */}
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Registration policy</h2><div className="pc-panel-h__sub">How new tenants sign up and get approved</div></div></div>
          <div className="pc-form">
            <div className="pc-row2">
              <div className="pc-field">
                <label className="pc-field__label" htmlFor="s-trial">Default trial length</label>
                <select id="s-trial" className="pc-input" value={form.default_trial_days ?? 14} onChange={(e) => set('default_trial_days', Number(e.target.value))}>
                  <option value={0}>No trial</option><option value={7}>7 days</option><option value={14}>14 days</option><option value={30}>30 days</option>
                </select>
              </div>
              <div className="pc-field">
                <label className="pc-field__label" htmlFor="s-ip">Max signups per IP / day</label>
                <input id="s-ip" type="number" min="1" max="100" className="pc-input" value={form.max_registrations_per_ip ?? 5} onChange={(e) => set('max_registrations_per_ip', Number(e.target.value))} />
              </div>
            </div>

            <div className="ob-rule"><div className="ob-rule__n"><b>Require email verification</b><span>Tenants must confirm their address before activation</span></div><Toggle on={!!form.require_email_verification} onChange={(v) => set('require_email_verification', v)} label="Email verification" /></div>
            <div className="ob-rule"><div className="ob-rule__n"><b>Require phone verification</b><span>OTP to a phone number during signup</span></div><Toggle on={!!form.require_phone_verification} onChange={(v) => set('require_phone_verification', v)} label="Phone verification" /></div>
            <div className="ob-rule"><div className="ob-rule__n"><b>Manual approval</b><span>An admin must approve each registration before provisioning</span></div><Toggle on={!!form.require_manual_approval} onChange={(v) => set('require_manual_approval', v)} label="Manual approval" /></div>
            <div className="ob-rule"><div className="ob-rule__n"><b>CAPTCHA on signup</b><span>Block automated / bot registrations</span></div><Toggle on={!!form.enable_captcha} onChange={(v) => set('enable_captcha', v)} label="CAPTCHA" /></div>

            <div className="pc-field" style={{ marginTop: 'var(--aeos-space-3)' }}>
              <label className="pc-field__label" htmlFor="s-blocked">Blocked email domains</label>
              <textarea id="s-blocked" className="pc-input" value={form.blocked_domains ?? ''} onChange={(e) => set('blocked_domains', e.target.value)} placeholder="tempmail.com, throwaway.com" />
              <span className="gw-keyhint">Comma-separated. Signups from these domains are rejected.</span>
            </div>
          </div>
        </CardBody></Card>

        {/* automation + templates */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--aeos-space-3)' }}>
          <Card><CardBody>
            <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Automation rules</h2><div className="pc-panel-h__sub">Hands-off onboarding</div></div><span className="sc-badge sc-badge--ok">{rules.filter((r) => r.active).length} active</span></div>
            {rules.map((r) => (
              <div className="ob-rule" key={r.id}>
                <div className="ob-rule__n"><b>{r.name}</b><span>{r.desc}</span></div>
                <Toggle on={r.active} onChange={(v) => toggleRule(r.id, v)} label={r.name} />
              </div>
            ))}
          </CardBody></Card>

          <Card><CardBody>
            <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Lifecycle emails</h2><div className="pc-panel-h__sub">Sent automatically across onboarding</div></div>
              <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/email/templates')}>Manage in Email engine</button></div>
            {templates.map((t) => (
              <div className="ob-tmpl" key={t.id}>
                <div className="ob-tmpl__ic">✉</div>
                <div className="ob-tmpl__n"><b>{t.name}</b><span>{t.desc}</span></div>
                <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/email/templates')}>Edit</button>
              </div>
            ))}
          </CardBody></Card>
        </div>
      </div>
    </div>
  );
}

Settings.layout = (page) => (
  <App title="Onboarding settings" railTitle="Settings" rail={<SettingsRail />}>
    {page}
  </App>
);
