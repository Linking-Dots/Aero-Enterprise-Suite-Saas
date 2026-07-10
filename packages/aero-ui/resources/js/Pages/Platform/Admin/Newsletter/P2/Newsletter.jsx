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
import './newsletter.css';

/* ---------------- glyphs ---------------- */
const svg = (p) => <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>;
const Glyph = {
  import: svg(<><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16" /></>),
  export: svg(<><path d="M12 21V9m0 0l-4 4m4-4l4 4M4 3h16" /></>),
  mail: svg(<><path d="M3 6l9 7 9-7M3 6v12h18V6z" /></>),
  cog: svg(<><circle cx="12" cy="12" r="3" /><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 2h-4l-.3 2.9a7 7 0 0 0-1.7 1l-2.4-1-2 3.4L6.1 11a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.9h4l.3-2.9a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6a7 7 0 0 0 .1-1z" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};

/* ---------------- domain maps ---------------- */
const SUB_STATUS = {
  pending: { label: 'Pending', color: 'var(--aeos-warning)', cls: 'nl-s-pend' },
  confirmed: { label: 'Confirmed', color: 'var(--aeos-success)', cls: 'nl-s-conf' },
  unsubscribed: { label: 'Unsubscribed', color: 'var(--aeos-danger)', cls: 'nl-s-unsub' },
};
const CAMP_STATUS = {
  draft: { label: 'Draft', color: 'var(--aeos-text-muted)', cls: 'nl-c-draft' },
  scheduled: { label: 'Scheduled', color: '#38bdf8', cls: 'nl-c-sched' },
  sending: { label: 'Sending', color: 'var(--aeos-warning)', cls: 'nl-c-sending' },
  sent: { label: 'Sent', color: 'var(--aeos-success)', cls: 'nl-c-sent' },
  cancelled: { label: 'Cancelled', color: 'var(--aeos-text-muted)', cls: 'nl-c-draft' },
};
const SRC_C = ['var(--aeos-primary)', 'var(--aeos-success)', '#38bdf8', '#a78bfa', 'var(--aeos-warning)', 'var(--aeos-text-muted)'];

const initials = (n, e) => { const s = (n || e || '—').replace(/@.*/, ''); return s.split(/[\s.]+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase(); };
const num = (n) => Number(n ?? 0).toLocaleString('en-US');
const fmtDate = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const fmtShort = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; } };
const fmtDT = (s) => { if (!s) return '—'; try { return new Date(s).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }); } catch { return s; } };

const xsrf = () => decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
const api = (method, url, body) => fetch(url, {
  method, credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
  body: body ? JSON.stringify(body) : undefined,
}).then((r) => (r.ok ? r.json().catch(() => ({})) : r.json().then((j) => Promise.reject(new Error(j.message || `HTTP ${r.status}`)), () => Promise.reject(new Error(`HTTP ${r.status}`)))));
const reload = () => router.reload({ only: ['overview'] });

/* ---------------- rail ---------------- */
function NewsletterRail({ overview }) {
  const o = overview ?? {};
  const s = o.stats ?? {};
  const drafts = (o.campaigns ?? []).filter((c) => c.status === 'draft' || c.status === 'scheduled').slice(0, 4);
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Audience</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Confirmed</span><b>{num(s.confirmed)}</b></div>
          <div className="pc-rail__row"><span>Pending opt-in</span><b>{num(s.pending)}</b></div>
          <div className="pc-rail__row"><span>Net growth (30d)</span><b>{s.growth_30d >= 0 ? '+' : ''}{num(s.growth_30d)}</b></div>
          <div className="pc-rail__row"><span>Avg open rate</span><b>{s.avg_open_rate ?? 0}%</b></div>
        </div>
      </div>
      {drafts.length > 0 && (
        <div>
          <div className="pc-panel-h__title">In the pipeline</div>
          <div className="nl-railq">
            {drafts.map((c) => (
              <div key={c.id} className="nl-railq__it"><span className="nl-railq__nm">{c.subject}</span><span className={`pc-chip ${CAMP_STATUS[c.status]?.cls}`} style={{ fontSize: 10 }}>{CAMP_STATUS[c.status]?.label}</span></div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/* ---------------- subscriber form modal ---------------- */
function SubscriberModal({ subscriber, sources, onClose, onDone }) {
  const editing = !!subscriber;
  const [d, setD] = useState(() => ({ email: subscriber?.email ?? '', name: subscriber?.name ?? '', source: subscriber?.source ?? 'website', status: subscriber?.status ?? 'confirmed' }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const submit = (e) => {
    e.preventDefault();
    if (!d.email.trim()) { setErr('Email is required.'); return; }
    setBusy(true); setErr(null);
    const req = editing ? api('PUT', `/newsletter/${subscriber.id}`, { name: d.name, source: d.source, status: d.status }) : api('POST', '/newsletter', d);
    req.then(() => onDone(editing ? 'Subscriber updated.' : 'Subscriber added.')).catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal pc-modal--sm" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? `Edit ${subscriber.email}` : 'Add subscriber'}</h2>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><label className="pc-field__label">Email *</label><input className="pc-input" type="email" value={d.email} onChange={(e) => set('email', e.target.value)} disabled={editing} autoFocus /></div>
          <div className="pc-field"><label className="pc-field__label">Name</label><input className="pc-input" value={d.name} onChange={(e) => set('name', e.target.value)} /></div>
          <div className="nl-form2">
            <div className="pc-field"><label className="pc-field__label">Source</label>
              <input className="pc-input" value={d.source} onChange={(e) => set('source', e.target.value)} list="nl-src" />
              <datalist id="nl-src">{(sources ?? []).map((s) => <option key={s.source} value={s.source} />)}</datalist>
            </div>
            {editing && <div className="pc-field"><label className="pc-field__label">Status</label>
              <select className="pc-input" value={d.status} onChange={(e) => set('status', e.target.value)}>{Object.keys(SUB_STATUS).map((k) => <option key={k} value={k}>{SUB_STATUS[k].label}</option>)}</select>
            </div>}
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : (editing ? 'Save' : 'Add')}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- import modal ---------------- */
function ImportModal({ onClose, onDone }) {
  const [text, setText] = useState(''); const [skip, setSkip] = useState(true); const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const submit = (e) => {
    e.preventDefault();
    const rows = text.split('\n').map((l) => l.trim()).filter(Boolean).map((l) => { const [email, name] = l.split(/[,\t]/).map((x) => x?.trim()); return { email, name }; });
    if (rows.length === 0) { setErr('Paste at least one email.'); return; }
    setBusy(true); setErr(null);
    api('POST', '/newsletter/import', { subscribers: rows, skip_confirmation: skip })
      .then((j) => onDone(`Imported ${j.result?.imported ?? rows.length} · skipped ${j.result?.skipped ?? 0}.`))
      .catch((e2) => { setErr(e2.message); setBusy(false); });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Import subscribers</h2>
        <div className="pc-modal__sub">One per line — <span className="nl-mono">email, name</span> (name optional).</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field"><textarea className="pc-input" style={{ minHeight: 140, fontFamily: 'var(--aeos-font-mono)', fontSize: 12 }} value={text} onChange={(e) => setText(e.target.value)} placeholder={'jane@example.com, Jane Doe\nravi@acme.io'} autoFocus /></div>
          <label className="nl-check"><input type="checkbox" checked={skip} onChange={(e) => setSkip(e.target.checked)} /> Mark imported subscribers confirmed (skip double opt-in)</label>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Importing…' : 'Import'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- settings modal ---------------- */
function SettingsModal({ settings, onClose, onDone }) {
  const [d, setD] = useState(() => ({
    require_confirmation: settings?.require_confirmation ?? true,
    welcome_email_enabled: settings?.welcome_email_enabled ?? true,
    from_name: settings?.from_name ?? 'AEOS365',
    from_email: settings?.from_email ?? 'hello@aeos365.com',
  }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null);
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const submit = (e) => { e.preventDefault(); setBusy(true); setErr(null); api('PUT', '/newsletter/settings', d).then(() => onDone('Settings saved.')).catch((e2) => { setErr(e2.message); setBusy(false); }); };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Newsletter settings</h2>
        <form className="pc-form" onSubmit={submit}>
          <label className="nl-check"><input type="checkbox" checked={d.require_confirmation} onChange={(e) => set('require_confirmation', e.target.checked)} /> Require double opt-in (email confirmation)</label>
          <label className="nl-check"><input type="checkbox" checked={d.welcome_email_enabled} onChange={(e) => set('welcome_email_enabled', e.target.checked)} /> Send a welcome email on confirm</label>
          <div className="nl-form2">
            <div className="pc-field"><label className="pc-field__label">Default from name</label><input className="pc-input" value={d.from_name} onChange={(e) => set('from_name', e.target.value)} /></div>
            <div className="pc-field"><label className="pc-field__label">Default from email</label><input className="pc-input" type="email" value={d.from_email} onChange={(e) => set('from_email', e.target.value)} /></div>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" /><button type="button" className="pc-btn" onClick={onClose}>Cancel</button><button type="submit" className="pc-btn pc-btn--primary" disabled={busy}>{busy ? 'Saving…' : 'Save settings'}</button></div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- campaign composer ---------------- */
function Composer({ campaign, segments, settings, onClose, onDone }) {
  const editing = !!campaign?.id;
  const [d, setD] = useState(() => ({
    subject: campaign?.subject ?? '', preheader: campaign?.preheader ?? '',
    from_name: campaign?.from_name ?? settings?.from_name ?? 'AEOS365',
    from_email: campaign?.from_email ?? settings?.from_email ?? 'hello@aeos365.com',
    body: campaign?.body ?? 'Hi {{name}},\n\n\n\n— The AEOS365 team',
    audience_type: campaign?.audience_type ?? 'all_confirmed',
    audience_source: campaign?.audience_source ?? null,
  }));
  const [busy, setBusy] = useState(false); const [err, setErr] = useState(null); const [when, setWhen] = useState('');
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));
  const segKey = d.audience_type === 'all_confirmed' ? 'all_confirmed' : `source:${d.audience_source}`;
  const reach = (segments ?? []).find((s) => s.key === segKey)?.count ?? 0;

  const save = (status) => {
    if (!d.subject.trim()) { setErr('Subject is required.'); return null; }
    setBusy(true); setErr(null);
    const payload = { ...d, status };
    return (editing ? api('PUT', `/newsletter/campaigns/${campaign.id}`, payload) : api('POST', '/newsletter/campaigns', payload));
  };
  const saveDraft = (e) => { e.preventDefault(); const r = save('draft'); if (r) r.then(() => onDone('Draft saved.')).catch((e2) => { setErr(e2.message); setBusy(false); }); };
  const doSend = (schedule) => {
    const r = save('draft');
    if (!r) return;
    r.then((res) => {
      const id = editing ? campaign.id : res.data?.id;
      return api('POST', `/newsletter/campaigns/${id}/send`, schedule ? { scheduled_at: when } : {});
    }).then((res) => onDone(res.message || 'Sent.')).catch((e2) => { setErr(e2.message); setBusy(false); });
  };

  const pickSeg = (seg) => { if (seg.key === 'all_confirmed') setD((p) => ({ ...p, audience_type: 'all_confirmed', audience_source: null })); else setD((p) => ({ ...p, audience_type: 'source', audience_source: seg.source })); };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal nl-composer" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? 'Edit campaign' : 'New campaign'}</h2>
        <form className="pc-form" onSubmit={saveDraft}>
          <div className="nl-comp-body">
            <div className="nl-comp-l">
              <div className="pc-field"><label className="pc-field__label">Subject *</label><input className="pc-input" value={d.subject} onChange={(e) => set('subject', e.target.value)} placeholder="Your best subject line" autoFocus /></div>
              <div className="nl-form2">
                <div className="pc-field"><label className="pc-field__label">From name</label><input className="pc-input" value={d.from_name} onChange={(e) => set('from_name', e.target.value)} /></div>
                <div className="pc-field"><label className="pc-field__label">From email</label><input className="pc-input" type="email" value={d.from_email} onChange={(e) => set('from_email', e.target.value)} /></div>
              </div>
              <div className="pc-field"><label className="pc-field__label">Preheader</label><input className="pc-input" value={d.preheader} onChange={(e) => set('preheader', e.target.value)} placeholder="Preview text shown in the inbox" /></div>
              <div className="pc-field"><label className="pc-field__label">Body</label><textarea className="pc-input nl-editor" value={d.body} onChange={(e) => set('body', e.target.value)} /></div>
            </div>
            <div className="nl-comp-r">
              <div className="pc-field"><label className="pc-field__label">Audience</label>
                <div className="nl-segs">
                  {(segments ?? []).map((seg) => (
                    <button type="button" key={seg.key} className={`nl-seg${seg.key === segKey ? ' is-on' : ''}`} onClick={() => pickSeg(seg)}>
                      <span>{seg.label}</span><span className="nl-seg__n">{num(seg.count)}</span>
                    </button>
                  ))}
                </div>
              </div>
              <div className="nl-reach"><div className="nl-reach__l">Estimated reach</div><div className="nl-reach__v">{num(reach)} <span>recipients</span></div></div>
              <div className="pc-field"><label className="pc-field__label">Schedule (optional)</label><input className="pc-input" type="datetime-local" value={when} onChange={(e) => setWhen(e.target.value)} /></div>
              <div className="nl-sim-note">Outbound mail is simulated in this environment — recipients are counted and engagement metrics recorded, no live blast is sent.</div>
            </div>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions">
            <button type="submit" className="pc-btn" disabled={busy}>Save draft</button>
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            {when ? <button type="button" className="pc-btn pc-btn--primary" disabled={busy} onClick={() => doSend(true)}>{busy ? '…' : 'Schedule'}</button>
              : <button type="button" className="pc-btn pc-btn--primary" disabled={busy} onClick={() => doSend(false)}>{busy ? 'Sending…' : `Send to ${num(reach)}`}</button>}
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- subscriber drawer ---------------- */
function SubscriberDrawer({ sub, onClose, actions }) {
  if (!sub) return null;
  const st = SUB_STATUS[sub.status] ?? {};
  const timeline = [
    sub.unsubscribed_at && { t: 'Unsubscribed', at: sub.unsubscribed_at, sub: sub.unsubscribe_reason },
    sub.confirmed_at && { t: 'Confirmed opt-in', at: sub.confirmed_at },
    sub.created_at && { t: 'Subscribed', at: sub.created_at, sub: sub.source },
  ].filter(Boolean);
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Subscriber — ${sub.email}`}
      head={(
        <>
          <div className="sc-dr-top">
            <div className="sc-av">{initials(sub.name, sub.email)}</div>
            <div><div className="sc-dr-title">{sub.name || sub.email}</div><div className="sc-dr-code">{sub.email}</div></div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{st.label ?? sub.status}</div></div>
            <div className="sc-dr-kpi"><div className="l">Source</div><div className="v" style={{ textTransform: 'capitalize' }}>{sub.source ?? '—'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Since</div><div className="v">{fmtShort(sub.created_at)}</div></div>
          </div>
          <div className="sc-dr-acts">
            {actions.canUpdate && sub.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => actions.confirm(sub)}>Confirm</button>}
            {actions.canUpdate && sub.status === 'pending' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.resend(sub)}>Resend opt-in</button>}
            {actions.canUpdate && sub.status !== 'unsubscribed' && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.unsubscribe(sub)}>Unsubscribe</button>}
            {actions.canUpdate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => actions.edit(sub)}>Edit</button>}
          </div>
        </>
      )}
    >
      <div>
        {Array.isArray(sub.preferences) && sub.preferences.length > 0 && (
          <div className="pc-drow"><span className="pc-drow__k">Preferences</span><span className="pc-drow__v"><span className="nl-tags">{sub.preferences.map((p) => <span key={p} className="nl-tag">{p}</span>)}</span></span></div>
        )}
        {sub.unsubscribe_reason && <div className="tn-danger-note" style={{ marginTop: 'var(--aeos-space-2)' }}>Unsubscribed — {sub.unsubscribe_reason}</div>}
        <div className="sc-dr-sec">Opt-in timeline</div>
        <ul className="sc-tl">{timeline.map((e, i) => <li key={i}>{e.t}{e.sub ? <span style={{ textTransform: 'capitalize' }}> · {e.sub}</span> : ''}<span className="when">{fmtDate(e.at)}</span></li>)}</ul>
      </div>
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Newsletter({ overview }) {
  const toast = useToast();
  const o = overview ?? {};
  const subs = useMemo(() => o.subscribers ?? [], [o.subscribers]);
  const camps = useMemo(() => o.campaigns ?? [], [o.campaigns]);
  const s = o.stats ?? {};
  const sp = o.sparks ?? {};

  const canView = useHRMAC('newsletter-management.subscribers.view');
  const canCreate = useHRMAC('newsletter-management.subscribers.create');
  const canUpdate = useHRMAC('newsletter-management.subscribers.update');
  const canDelete = useHRMAC('newsletter-management.subscribers.delete');
  const canImport = useHRMAC('newsletter-management.subscribers.import');
  const canCampaign = useHRMAC('newsletter-management.campaigns.create');
  const canSend = useHRMAC('newsletter-management.campaigns.send');
  const canCampDelete = useHRMAC('newsletter-management.campaigns.delete');
  const canSettings = useHRMAC('newsletter-management.newsletter-settings.update');

  const [tab, setTab] = useState('subscribers');
  const [drawer, setDrawer] = useState(null);
  const [subForm, setSubForm] = useState(undefined);
  const [composer, setComposer] = useState(undefined); // undefined closed, null new, obj edit
  const [importOpen, setImportOpen] = useState(false);
  const [settingsOpen, setSettingsOpen] = useState(false);
  const ctx = useCtxMenu();

  /* subscriber actions */
  const confirm = (r) => api('POST', `/newsletter/${r.id}/confirm`).then(() => { toast.success('Confirmed.'); reload(); }).catch((e) => toast.error(e.message));
  const resend = (r) => api('POST', `/newsletter/${r.id}/resend-confirmation`).then(() => { toast.success('Opt-in email resent.'); reload(); }).catch((e) => toast.error(e.message));
  const unsubscribe = (r) => { if (window.confirm(`Unsubscribe ${r.email}?`)) api('POST', `/newsletter/${r.id}/unsubscribe`, { reason: 'Admin action' }).then(() => { toast.success('Unsubscribed.'); reload(); }).catch((e) => toast.error(e.message)); };
  const removeSub = (r) => { if (window.confirm(`Delete ${r.email}? This cannot be undone.`)) api('DELETE', `/newsletter/${r.id}`).then(() => { toast.success('Deleted.'); reload(); }).catch((e) => toast.error(e.message)); };
  const bulkSub = (action) => {
    const ids = wb.selectedRows.map((r) => r.id);
    if (action === 'delete' && !window.confirm(`Delete ${ids.length} subscriber(s)?`)) return;
    api('POST', '/newsletter/bulk', { action, subscriber_ids: ids }).then((j) => { toast.success(j.message || 'Done.'); wb.clearSelection(); reload(); }).catch((e) => toast.error(e.message));
  };

  /* campaign actions */
  const sendCampaign = (c) => { if (window.confirm(`Send "${c.subject}" now to ${num(c.recipients_count || 0)} subscribers?`)) api('POST', `/newsletter/campaigns/${c.id}/send`, {}).then((j) => { toast.success(j.message || 'Sent.'); reload(); }).catch((e) => toast.error(e.message)); };
  const deleteCampaign = (c) => { if (window.confirm(`Delete "${c.subject}"?`)) api('DELETE', `/newsletter/campaigns/${c.id}`).then(() => { toast.success('Campaign deleted.'); reload(); }).catch((e) => toast.error(e.message)); };

  const subActions = { canUpdate, confirm, resend, unsubscribe, edit: (r) => { setDrawer(null); setSubForm(r); } };

  /* subscriber workbench */
  const wb = useWorkbench({
    rows: subs, getId: (r) => r.id,
    searchText: (r) => `${r.email} ${r.name ?? ''} ${r.source ?? ''}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'confirmed', label: 'Confirmed', test: (r) => r.status === 'confirmed' },
      { id: 'pending', label: 'Pending', test: (r) => r.status === 'pending' },
      { id: 'unsubscribed', label: 'Unsubscribed', test: (r) => r.status === 'unsubscribed' },
    ],
    facets: {
      status: { value: 'all', test: (r, v) => r.status === v },
      source: { value: 'all', test: (r, v) => r.source === v },
    },
    sortKey: 'created_at', sortVal: (r, k) => (k === 'created_at' ? new Date(r[k] ?? 0).getTime() : String(r[k] ?? '')),
    perPage: 12, storageKey: 'platform.newsletter',
  });

  /* campaign workbench */
  const wbc = useWorkbench({
    rows: camps, getId: (r) => r.id,
    searchText: (r) => `${r.subject} ${r.status}`,
    views: [
      { id: 'all', label: 'All' },
      { id: 'sent', label: 'Sent', test: (r) => r.status === 'sent' },
      { id: 'scheduled', label: 'Scheduled', test: (r) => r.status === 'scheduled' },
      { id: 'draft', label: 'Drafts', test: (r) => r.status === 'draft' },
    ],
    facets: { status: { value: 'all', test: (r, v) => r.status === v } },
    sortKey: 'created_at', sortVal: (r, k) => (['open_rate', 'click_rate', 'sent_count'].includes(k) ? r[k] : new Date(r[k] ?? 0).getTime()),
    perPage: 12, storageKey: 'platform.newscamps',
  });

  const subMenu = (r) => [
    ...(canView ? [{ label: 'Open', onClick: () => setDrawer(r) }] : []),
    ...(canUpdate && r.status === 'pending' ? [{ label: 'Confirm', onClick: () => confirm(r) }, { label: 'Resend opt-in', onClick: () => resend(r) }] : []),
    ...(canUpdate && r.status !== 'unsubscribed' ? [{ label: 'Unsubscribe', onClick: () => unsubscribe(r) }] : []),
    ...(canUpdate ? [{ label: 'Edit…', onClick: () => setSubForm(r) }] : []),
    ...(canDelete ? ['sep', { label: 'Delete', danger: true, onClick: () => removeSub(r) }] : []),
  ];

  const subColumns = [
    { key: 'email', label: 'Subscriber', sortable: true, render: (r) => <div className="pc-mrow"><div className="sc-av">{initials(r.name, r.email)}</div><div><div className="pc-mname">{r.name || '—'}</div><div className="sc-kind">{r.email}</div></div></div> },
    { key: 'source', label: 'Source', hideSm: true, render: (r) => <span className="nl-src">{r.source ?? '—'}</span> },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${SUB_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: SUB_STATUS[r.status]?.color }} />{SUB_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'preferences', label: 'Preferences', hideSm: true, render: (r) => (Array.isArray(r.preferences) && r.preferences.length ? <span className="nl-src">{r.preferences.join(', ')}</span> : <span className="pc-free">—</span>) },
    { key: 'created_at', label: 'Subscribed', align: 'r', sortable: true, render: (r) => <span className="sc-kind">{fmtShort(r.created_at)}</span> },
    { key: 'actions', label: '', align: 'r', width: 44, render: (r) => <button type="button" className="wb-kebab" aria-label={`Actions for ${r.email}`} onClick={(e) => ctx.open(e.currentTarget, subMenu(r))}>⋯</button> },
  ];

  const campMenu = (r) => [
    ...(r.status !== 'sent' && canCampaign ? [{ label: 'Edit…', onClick: () => setComposer(r) }] : []),
    ...(r.status !== 'sent' && canSend ? [{ label: 'Send now', onClick: () => sendCampaign(r) }] : []),
    ...(canCampDelete ? ['sep', { label: 'Delete', danger: true, onClick: () => deleteCampaign(r) }] : []),
  ];

  const rate = (v, color) => (
    <div className="nl-rate"><span className="nl-rate__t"><span className="nl-rate__f" style={{ width: `${Math.min(v, 100)}%`, background: color }} /></span><span className="num">{v}%</span></div>
  );

  const campColumns = [
    { key: 'subject', label: 'Campaign', sortable: true, render: (r) => <div><div className="pc-mname">{r.subject}</div><div className="sc-kind">{r.audience_type === 'all_confirmed' ? 'All confirmed' : `${r.audience_source} signups`}{r.sent_at ? ` · sent ${fmtShort(r.sent_at)}` : r.scheduled_at ? ` · ${fmtDT(r.scheduled_at)}` : ''}</div></div> },
    { key: 'status', label: 'Status', sortable: true, render: (r) => <span className={`pc-chip ${CAMP_STATUS[r.status]?.cls ?? ''}`}><span className="pc-chip__dot" style={{ background: CAMP_STATUS[r.status]?.color }} />{CAMP_STATUS[r.status]?.label ?? r.status}</span> },
    { key: 'sent_count', label: 'Recipients', align: 'r', sortable: true, render: (r) => <span className="num">{num(r.sent_count || r.recipients_count)}</span> },
    { key: 'open_rate', label: 'Open', sortable: true, render: (r) => (r.status === 'sent' ? rate(r.open_rate, 'var(--aeos-primary)') : <span className="pc-free">—</span>) },
    { key: 'click_rate', label: 'Click', sortable: true, render: (r) => (r.status === 'sent' ? rate(r.click_rate, '#a78bfa') : <span className="pc-free">—</span>) },
    { key: 'actions', label: '', align: 'r', width: 120, render: (r) => (
      <div className="nl-campacts">
        {r.status !== 'sent' && canSend && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => sendCampaign(r)}>Send</button>}
        {r.status !== 'sent' && canCampaign && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setComposer(r)}>Edit</button>}
        <button type="button" className="wb-kebab" aria-label="Campaign actions" onClick={(e) => ctx.open(e.currentTarget, campMenu(r))}>⋯</button>
      </div>
    ) },
  ];

  const kpis = [
    { label: 'Total subscribers', value: num(s.total), delta: `${s.growth_30d >= 0 ? '+' : ''}${num(s.growth_30d)} (30d)`, up: (s.growth_30d ?? 0) >= 0, spark: sp.total, color: 'var(--aeos-primary)' },
    { label: 'Confirmed', value: num(s.confirmed), delta: `${s.confirm_rate ?? 0}% of active`, up: true, spark: sp.confirmed, color: 'var(--aeos-success)' },
    { label: 'Pending opt-in', value: num(s.pending), delta: 'awaiting confirm', spark: sp.total, color: 'var(--aeos-warning)' },
    { label: 'Avg open rate', value: `${s.avg_open_rate ?? 0}%`, delta: `${s.campaigns_sent ?? 0} sent`, up: true, spark: sp.confirmed, color: '#38bdf8' },
    { label: 'Avg click rate', value: `${s.avg_click_rate ?? 0}%`, delta: 'engagement', up: true, spark: sp.confirmed, color: '#a78bfa' },
    { label: 'Unsub rate', value: `${s.unsub_rate ?? 0}%`, delta: `${num(s.unsubscribed)} total`, up: (s.unsub_rate ?? 0) < 2, spark: sp.unsubscribed, color: 'var(--aeos-danger)' },
  ];

  const sources = o.sources ?? [];
  const funnel = o.funnel ?? [];
  const funMax = Math.max(1, ...funnel.map((f) => f.count));
  const trend = o.trend ?? {};
  const recentCamps = useMemo(() => camps.filter((c) => c.status === 'sent').slice(0, 4), [camps]);

  const exportCsv = () => { window.location.href = '/newsletter/export'; };

  return (
    <div className="pc nl">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Growth &amp; Marketing · Email Marketing</div>
          <h1 className="pc-title">Newsletter</h1>
          <div className="pc-sub">Grow and keep a healthy audience, then reach it — manage subscribers and opt-in, segment, and compose &amp; send broadcasts with open/click analytics, all in one console.</div>
        </div>
        <div className="pc-actions">
          {canImport && <button type="button" className="pc-btn" onClick={() => setImportOpen(true)}>{Glyph.import}<span>Import</span></button>}
          <button type="button" className="pc-btn" onClick={exportCsv}>{Glyph.export}<span>Export</span></button>
          {canSettings && <button type="button" className="pc-btn" onClick={() => setSettingsOpen(true)}>{Glyph.cog}<span>Settings</span></button>}
          {canCampaign && <button type="button" className="pc-btn pc-btn--primary" onClick={() => setComposer(null)}>{Glyph.mail}<span>New campaign</span></button>}
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
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Audience growth</h2><div className="pc-panel-h__sub">New vs confirmed — 6 months</div></div></div>
          <AreaTrend series={[{ key: 'new', label: 'New', color: 'var(--aeos-primary)', values: trend.new ?? [] }, { key: 'confirmed', label: 'Confirmed', color: 'var(--aeos-success)', values: trend.confirmed ?? [] }]} labels={trend.labels ?? []} height={190} ariaLabel="Audience growth" />
          <div className="tn-trend-foot">
            <span className="li"><span className="d" style={{ background: 'var(--aeos-primary)' }} />New <b>{num((trend.new ?? []).reduce((a, b) => a + b, 0))}</b></span>
            <span className="li"><span className="d" style={{ background: 'var(--aeos-success)' }} />Confirmed <b>{num((trend.confirmed ?? []).reduce((a, b) => a + b, 0))}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Signup sources</h2><div className="pc-panel-h__sub">Where they joined</div></div></div>
          <div className="sc-donut-row">
            <Donut segments={sources.map((src, i) => ({ color: SRC_C[i % SRC_C.length], value: src.count }))} centerValue={num(s.total)} centerLabel="subs" size={112} />
            <div className="sc-dl">
              {sources.map((src, i) => (
                <button key={src.source} type="button" className="li" onClick={() => wb.setFacet('source', wb.facetValues.source === src.source ? 'all' : src.source)}>
                  <span className="d" style={{ background: SRC_C[i % SRC_C.length] }} />{src.label}<b>{src.count}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Opt-in funnel</h2><div className="pc-panel-h__sub">Double opt-in health</div></div></div>
          <div className="nl-funnel">
            {funnel.map((f) => (
              <div key={f.status} className="nl-funnel__row">
                <span className="nl-funnel__cap">{f.label}</span>
                <span className="nl-funnel__bar" style={{ width: `${Math.max((f.count / funMax) * 100, 6)}%`, background: f.status === 'unsubscribed' ? 'var(--aeos-danger)' : f.status === 'pending' ? 'var(--aeos-warning)' : f.status === 'confirmed' ? 'var(--aeos-success)' : 'var(--aeos-primary)' }}>{num(f.count)}</span>
              </div>
            ))}
          </div>
        </CardBody></Card>
      </div>

      {/* recent campaigns */}
      {recentCamps.length > 0 && (
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Recent campaigns</h2><div className="pc-panel-h__sub">Opens &amp; clicks per broadcast</div></div><span className="sc-badge sc-badge--ok">{s.campaigns_sent ?? 0} sent</span></div>
          <div className="nl-camps">
            {recentCamps.map((c) => (
              <button key={c.id} type="button" className="nl-camp" onClick={() => setComposer(c.status === 'sent' ? undefined : c)}>
                <div className="nl-camp__l"><div className="nl-camp__subj">{c.subject}</div><div className="nl-camp__meta">Sent {fmtShort(c.sent_at)} · to {c.audience_type === 'all_confirmed' ? 'all confirmed' : `${c.audience_source} signups`} · {num(c.sent_count)}</div></div>
                <div className="nl-camp__stats">
                  <div className="nl-cst">{rate(c.open_rate, 'var(--aeos-primary)')}<div className="nl-cst__l">Open</div></div>
                  <div className="nl-cst">{rate(c.click_rate, '#a78bfa')}<div className="nl-cst__l">Click</div></div>
                </div>
              </button>
            ))}
          </div>
        </CardBody></Card>
      )}

      {/* workbench */}
      <Card><CardBody>
        <div className="nl-tabs">
          <button type="button" className={`nl-tab${tab === 'subscribers' ? ' is-on' : ''}`} onClick={() => setTab('subscribers')}>Subscribers <span>{num(subs.length)}</span></button>
          <button type="button" className={`nl-tab${tab === 'campaigns' ? ' is-on' : ''}`} onClick={() => setTab('campaigns')}>Campaigns <span>{num(camps.length)}</span></button>
        </div>

        {tab === 'subscribers' ? (
          <>
            <WbToolbar>
              <WbSearch value={wb.q} onChange={wb.setQ} placeholder="Search email or name…" ariaLabel="Search subscribers" />
              <select className="pc-input sc-statusfilter" value={wb.facetValues.status} onChange={(e) => wb.setFacet('status', e.target.value)} aria-label="Status filter">
                <option value="all">All statuses</option>{Object.keys(SUB_STATUS).map((k) => <option key={k} value={k}>{SUB_STATUS[k].label}</option>)}
              </select>
              <select className="pc-input sc-statusfilter" value={wb.facetValues.source} onChange={(e) => wb.setFacet('source', e.target.value)} aria-label="Source filter">
                <option value="all">Any source</option>{sources.map((src) => <option key={src.source} value={src.source}>{src.label}</option>)}
              </select>
              {canCreate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setSubForm(null)}>+ Add</button>}
              <WbColumns wb={wb} columns={subColumns} />
            </WbToolbar>
            <WbViews wb={wb} />
            <WbBulkBar wb={wb}>
              {canUpdate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkSub('confirm')}>Confirm</button>}
              {canUpdate && <button type="button" className="pc-btn pc-btn--sm" onClick={() => bulkSub('unsubscribe')}>Unsubscribe</button>}
              <button type="button" className="pc-btn pc-btn--sm" onClick={exportCsv}>Export</button>
              {canDelete && <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => bulkSub('delete')}>Delete</button>}
            </WbBulkBar>
            <WbTable wb={wb} columns={subColumns} selectable={canUpdate || canDelete} onRowClick={setDrawer} rowAriaLabel={(r) => `${r.email}, ${SUB_STATUS[r.status]?.label}`}
              empty={<>No subscribers match these filters.<br /><button type="button" className="pc-btn pc-btn--sm" onClick={wb.resetFilters}>Clear filters</button></>} />
            <WbFooter wb={wb} perOptions={[12, 25, 50]} />
          </>
        ) : (
          <>
            <WbToolbar>
              <WbSearch value={wbc.q} onChange={wbc.setQ} placeholder="Search campaign subject…" ariaLabel="Search campaigns" />
              <select className="pc-input sc-statusfilter" value={wbc.facetValues.status} onChange={(e) => wbc.setFacet('status', e.target.value)} aria-label="Campaign status filter">
                <option value="all">All statuses</option>{Object.keys(CAMP_STATUS).map((k) => <option key={k} value={k}>{CAMP_STATUS[k].label}</option>)}
              </select>
              {canCampaign && <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setComposer(null)}>+ New campaign</button>}
              <WbColumns wb={wbc} columns={campColumns} />
            </WbToolbar>
            <WbViews wb={wbc} />
            <WbTable wb={wbc} columns={campColumns} onRowClick={(r) => setComposer(r.status === 'sent' ? undefined : r)}
              empty={<>No campaigns yet.<br />{canCampaign && <button type="button" className="pc-btn pc-btn--sm" onClick={() => setComposer(null)}>Compose your first</button>}</>} />
            <WbFooter wb={wbc} perOptions={[12, 25, 50]} />
          </>
        )}
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {drawer && <SubscriberDrawer sub={subs.find((x) => x.id === drawer.id) ?? drawer} onClose={() => setDrawer(null)} actions={{ ...subActions, unsubscribe: (r) => { setDrawer(null); unsubscribe(r); }, edit: (r) => { setDrawer(null); setSubForm(r); } }} />}
      {subForm !== undefined && <SubscriberModal subscriber={subForm} sources={sources} onClose={() => setSubForm(undefined)} onDone={(m) => { setSubForm(undefined); toast.success(m); reload(); }} />}
      {composer !== undefined && <Composer campaign={composer} segments={o.segments} settings={o.settings} onClose={() => setComposer(undefined)} onDone={(m) => { setComposer(undefined); toast.success(m); reload(); }} />}
      {importOpen && <ImportModal onClose={() => setImportOpen(false)} onDone={(m) => { setImportOpen(false); toast.success(m); reload(); }} />}
      {settingsOpen && <SettingsModal settings={o.settings} onClose={() => setSettingsOpen(false)} onDone={(m) => { setSettingsOpen(false); toast.success(m); reload(); }} />}
    </div>
  );
}

Newsletter.layout = (page) => (
  <App title="Newsletter" railTitle="Growth &amp; Marketing" rail={<NewsletterRail overview={page.props.overview} />}>
    {page}
  </App>
);
