import { useEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody, useCtxMenu, WbDrawer } from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './integrations.css';

const svg = (p) => (<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{p}</svg>);
const Glyph = {
  key: svg(<><path d="M21 2l-2 2m-7.6 7.6a5 5 0 1 1-7 7 5 5 0 0 1 7-7zm0 0L15 8m0 0l3 3m-3-3l2-2" /></>),
  hook: svg(<><path d="M18 8a6 6 0 0 0-6-6 6 6 0 0 0-6 6c0 7-3 9-3 9h18s-3-2-3-9" /></>),
  events: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};
const host = (u) => { try { return new URL(u).host; } catch { return (u || '').replace(/^https?:\/\//, ''); } };
const fmtDate = (s) => { if (!s) return 'never'; try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch { return s; } };
const ago = (s) => {
  if (!s) return '—';
  try { const d = (Date.now() - new Date(String(s).replace(' ', 'T'))) / 3600000; return d < 1 ? 'just now' : d < 24 ? `${Math.round(d)}h ago` : `${Math.round(d / 24)}d ago`; } catch { return s; }
};

/* ---------------- rail ---------------- */
function IntegrationsRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Connectivity</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>API keys</span><b>{k.active_keys ?? 0}/{k.total_keys ?? 0}</b></div>
          <div className="pc-rail__row"><span>Endpoints</span><b>{k.active_endpoints ?? 0}/{k.endpoints ?? 0}</b></div>
          <div className="pc-rail__row"><span>Deliveries 72h</span><b>{k.deliveries_72h ?? 0}</b></div>
          <div className="pc-rail__row"><span>Success rate</span><b>{k.success_rate ?? 0}%</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/dashboard')}>{Glyph.events}<span>Dashboard</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- create key modal ---------------- */
const SCOPES = ['read', 'write', 'admin'];
function CreateKeyModal({ csrf, onClose }) {
  const [name, setName] = useState('');
  const [scopes, setScopes] = useState(['read']);
  const [expires, setExpires] = useState('');
  const [busy, setBusy] = useState(false);
  const [raw, setRaw] = useState(null);
  const [err, setErr] = useState(null);

  const toggle = (s) => setScopes((cur) => (cur.includes(s) ? cur.filter((x) => x !== s) : [...cur, s]));
  const submit = (e) => {
    e.preventDefault(); setBusy(true); setErr(null);
    fetch(route('platform.admin.integrations.api-keys.store'), {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({ name, scopes, expires_at: expires || null }),
    }).then((r) => r.json().then((j) => ({ ok: r.ok, j }))).then(({ ok, j }) => {
      if (ok) setRaw(j.key); else setErr(j.message || 'Failed to create key.');
    }).finally(() => setBusy(false));
  };
  const done = () => { router.reload({ only: ['overview'], preserveScroll: true }); onClose(); };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && (raw ? done() : onClose())}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        {raw ? (
          <>
            <h2 className="pc-modal__title">API key created</h2>
            <div className="pc-modal__sub">Copy it now — for security this is the only time the full key is shown.</div>
            <div className="in-reveal">{raw}</div>
            <div className="pc-modal__actions"><span className="pc-spacer" />
              <button type="button" className="pc-btn" onClick={() => navigator.clipboard?.writeText(raw)}>Copy</button>
              <button type="button" className="pc-btn pc-btn--primary" onClick={done}>Done</button>
            </div>
          </>
        ) : (
          <form className="pc-form" onSubmit={submit}>
            <h2 className="pc-modal__title">New API key</h2>
            <div className="pc-modal__sub">Grant scoped programmatic access to the platform API.</div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="k-name">Name</label>
              <input id="k-name" className="pc-input" value={name} onChange={(e) => setName(e.target.value)} placeholder="e.g. Production integration" />
            </div>
            <div className="pc-field">
              <span className="pc-field__label">Scopes</span>
              <div className="in-checks">
                {SCOPES.map((s) => <span key={s} className="in-check" data-on={scopes.includes(s)} role="button" tabIndex={0} onClick={() => toggle(s)} onKeyDown={(e) => e.key === 'Enter' && toggle(s)}>{scopes.includes(s) ? '✓ ' : ''}{s}</span>)}
              </div>
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="k-exp">Expires (optional)</label>
              <input id="k-exp" type="date" className="pc-input" value={expires} onChange={(e) => setExpires(e.target.value)} />
            </div>
            {err && <span className="pc-field__err">{err}</span>}
            <div className="pc-modal__actions"><span className="pc-spacer" />
              <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
              <button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !name || scopes.length === 0}>{busy ? 'Creating…' : 'Create key'}</button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

/* ---------------- add webhook modal ---------------- */
function WebhookModal({ csrf, events, endpoint, onClose }) {
  const editing = !!endpoint;
  const [url, setUrl] = useState(endpoint?.url ?? '');
  const [desc, setDesc] = useState(endpoint?.description ?? '');
  const [sel, setSel] = useState(endpoint?.events ?? []);
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState(null);
  const toggle = (ev) => setSel((c) => (c.includes(ev) ? c.filter((x) => x !== ev) : [...c, ev]));
  const submit = (e) => {
    e.preventDefault(); setBusy(true); setErr(null);
    const url2 = editing ? route('platform.admin.integrations.webhooks.update', endpoint.id) : route('platform.admin.integrations.webhooks.store');
    fetch(url2, {
      method: editing ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      body: JSON.stringify({ url, description: desc, events: sel, is_active: endpoint?.is_active ?? true }),
    }).then((r) => r.json().then((j) => ({ ok: r.ok, j }))).then(({ ok, j }) => {
      if (ok) { router.reload({ only: ['overview'], preserveScroll: true }); onClose(); } else setErr(j.message || 'Failed.');
    }).finally(() => setBusy(false));
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? 'Edit endpoint' : 'Add webhook endpoint'}</h2>
        <div className="pc-modal__sub">Outbound events are signed and retried on failure.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="w-url">Payload URL</label>
            <input id="w-url" className="pc-input" value={url} onChange={(e) => setUrl(e.target.value)} placeholder="https://example.com/webhooks/aeos" />
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="w-desc">Description</label>
            <input id="w-desc" className="pc-input" value={desc} onChange={(e) => setDesc(e.target.value)} placeholder="What this endpoint is for" />
          </div>
          <div className="pc-field">
            <span className="pc-field__label">Events ({sel.length})</span>
            <div className="in-checks">
              {events.map((ev) => <span key={ev} className="in-check" data-on={sel.includes(ev)} role="button" tabIndex={0} onClick={() => toggle(ev)} onKeyDown={(e) => e.key === 'Enter' && toggle(ev)}>{ev}</span>)}
            </div>
          </div>
          {err && <span className="pc-field__err">{err}</span>}
          <div className="pc-modal__actions"><span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={busy || !url || sel.length === 0}>{busy ? 'Saving…' : editing ? 'Save endpoint' : 'Add endpoint'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- logs drawer ---------------- */
function LogsDrawer({ endpoint, csrf, onClose }) {
  const [logs, setLogs] = useState(null);
  const load = () => fetch(route('platform.admin.integrations.webhooks.logs.index', endpoint.id), { headers: { Accept: 'application/json' } })
    .then((r) => r.json()).then((j) => setLogs(j.data ?? j ?? [])).catch(() => setLogs([]));
  useEffect(() => { setLogs(null); load(); }, [endpoint.id]); // eslint-disable-line react-hooks/exhaustive-deps
  const replay = (id) => fetch(route('platform.admin.integrations.webhooks.logs.replay', id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } }).finally(load);
  return (
    <WbDrawer open onClose={onClose} ariaLabel={`Delivery logs — ${host(endpoint.url)}`}
      head={<>
        <div className="sc-dr-top"><div className="sc-av">{Glyph.hook}</div><div><div className="sc-dr-title">{host(endpoint.url)}</div><div className="sc-dr-code">delivery logs</div></div><button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button></div>
      </>}
    >
      {logs == null ? <div className="wb-empty">Loading…</div>
        : logs.length === 0 ? <div className="wb-empty">No deliveries recorded yet.</div>
          : logs.map((l) => {
            const ok = Number(l.response_status) === 200;
            return (
              <div className="in-log" key={l.id}>
                <span className={`in-log__dot ${ok ? 'in-log__dot--ok' : 'in-log__dot--err'}`} />
                <div className="in-log__mid"><div className="in-mono">{l.event_type}</div><div className="ob-mini" style={{ fontSize: 11, color: 'var(--aeos-text-muted)' }}>{ago(l.created_at)}</div></div>
                <span className="in-log__code" style={{ color: ok ? 'var(--aeos-success)' : 'var(--aeos-danger)' }}>{l.response_status || 'ERR'}</span>
                {!ok && <button type="button" className="pc-btn pc-btn--sm" onClick={() => replay(l.id)}>Replay</button>}
              </div>
            );
          })}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Integrations({ overview }) {
  const o = overview ?? {};
  const k = o.kpis ?? {};
  const keys = o.api_keys ?? [];
  const endpoints = o.endpoints ?? [];
  const logs = o.recent_logs ?? [];
  const events = o.events ?? [];
  const connectors = o.connectors ?? [];
  const csrf = usePage().props.csrfToken;
  const ctx = useCtxMenu();

  const [modal, setModal] = useState(null); // {type, endpoint?}
  const [logsFor, setLogsFor] = useState(null);
  const [connQ, setConnQ] = useState('');

  const api = (method, url, body) => fetch(url, {
    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: body ? JSON.stringify(body) : undefined,
  }).finally(() => router.reload({ only: ['overview'], preserveScroll: true }));

  const revokeKey = (id, name) => { if (window.confirm(`Revoke API key “${name}”? Any client using it will stop working.`)) api('POST', route('platform.admin.integrations.api-keys.revoke', id)); };
  const testHook = (id) => api('POST', route('platform.admin.integrations.webhooks.test', id));
  const rotate = (id) => { if (window.confirm('Rotate the signing secret? Existing consumers must update to the new secret.')) api('POST', route('platform.admin.integrations.webhooks.rotate-secret', id)); };
  const toggleHook = (e) => api('PUT', route('platform.admin.integrations.webhooks.update', e.id), { is_active: !e.is_active });
  const delHook = (e) => { if (window.confirm(`Delete endpoint ${host(e.url)}?`)) api('DELETE', route('platform.admin.integrations.webhooks.destroy', e.id)); };

  const kpis = [
    { label: 'Active API keys', value: `${k.active_keys ?? 0}`, unit: `/ ${k.total_keys ?? 0}`, delta: `${(k.total_keys ?? 0) - (k.active_keys ?? 0)} revoked` },
    { label: 'Webhook endpoints', value: `${k.endpoints ?? 0}`, delta: `${k.active_endpoints ?? 0} active · ${(k.endpoints ?? 0) - (k.active_endpoints ?? 0)} off` },
    { label: 'Deliveries (72h)', value: `${k.deliveries_72h ?? 0}`, delta: `${k.delivered ?? 0} ok · ${k.failed ?? 0} failed` },
    { label: 'Success rate', value: `${k.success_rate ?? 0}%`, delta: (k.success_rate ?? 100) >= 95 ? 'healthy' : 'watch failures', cls: (k.success_rate ?? 100) >= 95 ? 'up' : 'warn' },
    { label: 'Failing endpoints', value: `${k.failing ?? 0}`, delta: (k.failing ?? 0) > 0 ? 'need attention' : 'all healthy', cls: (k.failing ?? 0) > 0 ? 'down' : 'up' },
    { label: 'Event types', value: `${k.events ?? 0}`, delta: 'available to subscribe' },
  ];

  const filteredConns = useMemo(() => connectors.filter((c) => `${c.name} ${c.category}`.toLowerCase().includes(connQ.toLowerCase())), [connectors, connQ]);
  const rate = k.success_rate ?? 100;

  return (
    <div className="pc inx">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Integrations</div>
          <h1 className="pc-title">Integrations</h1>
          <div className="pc-sub">Everything that connects AEOS365 to the outside world — programmatic API keys, outbound webhooks with delivery monitoring, and one-click connectors to the tools your customers use.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => setModal({ type: 'webhook' })}>{Glyph.hook}<span>Add webhook</span></button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={() => setModal({ type: 'key' })}>{Glyph.plus}<span>New API key</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody><div className="pc-kpi">
            <div className="pc-kpi__label">{c.label}</div>
            <div className="pc-kpi__value">{c.value}{c.unit && <small>{c.unit}</small>}</div>
            <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'down' ? ' pc-kpi__delta--down' : ''}${c.cls === 'warn' ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
          </div></CardBody></Card>
        ))}
      </div>

      {/* API keys */}
      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">API keys</h2><div className="pc-panel-h__sub">Programmatic access to the platform API — scoped &amp; revocable</div></div>
          <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setModal({ type: 'key' })}>{Glyph.plus}New key</button></div>
        <div className="pc-tablewrap">
          <table className="in-tbl">
            <thead><tr><th>Name</th><th>Key</th><th>Scopes</th><th>Last used</th><th>Status</th><th /></tr></thead>
            <tbody>
              {keys.length === 0 && <tr><td colSpan={6}><div className="wb-empty">No API keys yet.</div></td></tr>}
              {keys.map((key) => (
                <tr key={key.id}>
                  <td><span className="in-name">{key.name}</span></td>
                  <td><span className="in-mono">{key.key_prefix}…</span></td>
                  <td>{key.scopes.map((s) => <span className="in-tag" key={s}>{s}</span>)}</td>
                  <td style={{ color: 'var(--aeos-text-muted)' }}>{fmtDate(key.last_used)}</td>
                  <td><span className={`in-st in-st--${key.is_active ? 'active' : 'revoked'}`}><span className="in-st__d" />{key.is_active ? 'Active' : 'Revoked'}</span></td>
                  <td><div className="in-rowacts">{key.is_active ? <button type="button" className="pc-btn pc-btn--sm pc-btn--danger" onClick={() => revokeKey(key.id, key.name)}>Revoke</button> : <span style={{ color: 'var(--aeos-text-muted)', fontSize: 11 }}>—</span>}</div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardBody></Card>

      {/* webhooks + delivery */}
      <div className="in-cols">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Webhook endpoints</h2><div className="pc-panel-h__sub">Outbound event delivery — signed &amp; retried</div></div>
            <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setModal({ type: 'webhook' })}>{Glyph.plus}Add endpoint</button></div>
          <div className="pc-tablewrap">
            <table className="in-tbl">
              <thead><tr><th>Endpoint</th><th>Events</th><th>Status</th><th /></tr></thead>
              <tbody>
                {endpoints.length === 0 && <tr><td colSpan={4}><div className="wb-empty">No webhook endpoints yet.</div></td></tr>}
                {endpoints.map((e) => (
                  <tr key={e.id}>
                    <td><div className="in-name" style={{ maxWidth: 220, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}><span className="in-mono">{host(e.url)}</span></div>
                      <div style={{ fontSize: 11, color: 'var(--aeos-text-muted)' }}>{e.description}{e.failure_count > 0 ? ` · ${e.failure_count} fails` : ''}</div></td>
                    <td>{e.events.slice(0, 2).map((ev) => <span className="in-tag" key={ev}>{ev}</span>)}{e.events.length > 2 ? <span className="in-tag">+{e.events.length - 2}</span> : null}</td>
                    <td><span className={`in-st in-st--${e.status}`}><span className="in-st__d" />{e.status === 'disabled' ? 'Disabled' : e.status === 'failing' ? 'Failing' : 'Active'}</span></td>
                    <td><div className="in-rowacts">
                      <button type="button" className="pc-btn pc-btn--sm" onClick={() => testHook(e.id)}>Test</button>
                      <button type="button" className="pc-btn pc-btn--sm" onClick={() => setLogsFor(e)}>Logs</button>
                      <button type="button" className="wb-kebab" aria-label={`More for ${host(e.url)}`} onClick={(ev) => ctx.open(ev.currentTarget, [
                        { label: 'Edit endpoint', onClick: () => setModal({ type: 'webhook', endpoint: e }) },
                        { label: e.is_active ? 'Disable' : 'Enable', onClick: () => toggleHook(e) },
                        { label: 'Rotate signing secret', onClick: () => rotate(e.id) },
                        'sep',
                        { label: 'Delete endpoint…', danger: true, onClick: () => delHook(e) },
                      ])}>⋯</button>
                    </div></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Delivery health</h2><div className="pc-panel-h__sub">Last 72 hours</div></div>
            <span className={`sc-badge ${rate >= 95 ? 'sc-badge--ok' : 'sc-badge--warn'}`}>{rate}%</span></div>
          <div className="in-delbar"><span style={{ width: `${rate}%`, background: 'var(--aeos-success)' }} /><span style={{ width: `${100 - rate}%`, background: 'var(--aeos-danger)' }} /></div>
          <div style={{ fontSize: 11.5, color: 'var(--aeos-text-secondary)', marginBottom: 'var(--aeos-space-3)' }}>{k.delivered ?? 0} delivered · {k.failed ?? 0} failed / retrying</div>
          {logs.length === 0 && <div className="wb-empty">No recent deliveries.</div>}
          {logs.map((l) => (
            <div className="in-log" key={l.id}>
              <span className={`in-log__dot ${l.ok ? 'in-log__dot--ok' : 'in-log__dot--err'}`} />
              <div className="in-log__mid"><div className="in-mono">{l.event}</div><div style={{ fontSize: 11, color: 'var(--aeos-text-muted)' }}>{l.host}</div></div>
              <span className="in-log__code" style={{ color: l.ok ? 'var(--aeos-success)' : 'var(--aeos-danger)' }}>{l.status || 'ERR'}</span>
              <span style={{ fontSize: 11, color: 'var(--aeos-text-muted)', whiteSpace: 'nowrap' }}>{ago(l.at)}</span>
            </div>
          ))}
        </CardBody></Card>
      </div>

      {/* connectors */}
      <Card><CardBody>
        <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Connectors</h2><div className="pc-panel-h__sub">One-click integrations to third-party tools</div></div>
          <input className="pc-input sc-statusfilter" placeholder="Search connectors…" value={connQ} onChange={(e) => setConnQ(e.target.value)} style={{ width: 200 }} /></div>
        <div className="in-conns">
          {filteredConns.map((c) => (
            <div className="in-conn" key={c.key}>
              <div className="in-conn__h"><div className="in-conn__ico" style={{ background: c.color }}>{c.name[0]}</div><div><b>{c.name}</b><div className="in-conn__cat">{c.category}</div></div></div>
              <div className="in-conn__desc">{c.desc}</div>
              <button type="button" className={`pc-btn pc-btn--sm in-conn__btn ${c.connected ? '' : 'pc-btn--primary'}`}>{c.connected ? '● Connected · Manage' : 'Connect'}</button>
            </div>
          ))}
          {filteredConns.length === 0 && <div className="wb-empty">No connectors match “{connQ}”.</div>}
        </div>
      </CardBody></Card>

      {/* overlays */}
      {ctx.element}
      {modal?.type === 'key' && <CreateKeyModal csrf={csrf} onClose={() => setModal(null)} />}
      {modal?.type === 'webhook' && <WebhookModal csrf={csrf} events={events} endpoint={modal.endpoint} onClose={() => setModal(null)} />}
      {logsFor && <LogsDrawer endpoint={logsFor} csrf={csrf} onClose={() => setLogsFor(null)} />}
    </div>
  );
}

Integrations.layout = (page) => (
  <App title="Integrations" railTitle="Integrations" rail={<IntegrationsRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
