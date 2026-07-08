import { useEffect, useMemo, useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaSpark,
  useCtxMenu, WbDrawer,
} from '@aero/ui';

import '../../Products/products.css';
import './subscriptions.css';
import './gateways.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  invoices: svg(<><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M8 13h8M8 17h5" /></>),
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
};

const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(Number(n ?? 0)) >= 1000 ? `$${(Number(n) / 1000).toFixed(1)}k` : fmtMoney(n));
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};
const markClass = (code) => (['stripe', 'paypal', 'bank_transfer', 'sslcommerz'].includes(code) ? `gw-mark--${code}` : 'gw-mark--default');

const post = (url, data = {}) => router.post(url, data, { preserveScroll: true });
const rte = (name, param) => route(`platform.admin.billing.gateways.${name}`, param);

/* ---------------- rail ---------------- */
function GatewaysRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Processing</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Live gateways</span><b>{k.live ?? 0} / {k.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Default</span><b>{k.default_label ?? '—'}</b></div>
          <div className="pc-rail__row"><span>Keys set</span><b>{k.configured ?? 0} / {k.total ?? 0}</b></div>
          <div className="pc-rail__row"><span>Settled</span><b>{fmtMoney(k.settled)}</b></div>
          <div className="pc-rail__row"><span>Refunds</span><b>{fmtMoney(k.refunds_amount)}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing')}>{Glyph.billing}<span>Billing</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/subscriptions')}>{Glyph.subs}<span>Subscriptions</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/invoices')}>{Glyph.invoices}<span>Invoices</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- add gateway modal ---------------- */
function AddModal({ onClose }) {
  const form = useForm({ code: '', label: '', is_enabled: true });
  const submit = (e) => {
    e.preventDefault();
    form.transform((d) => ({ ...d, code: d.code.trim().toLowerCase().replace(/\s+/g, '_') }))
      .post(rte('store'), { preserveScroll: true, onSuccess: onClose });
  };
  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">Add payment gateway</h2>
        <div className="pc-modal__sub">Register a new provider. You can drop in its API keys and test the connection right after.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="g-label">Display name</label>
            <input id="g-label" className="pc-input" value={form.data.label} onChange={(e) => form.setData('label', e.target.value)} placeholder="e.g. Razorpay" />
            {form.errors.label && <span className="pc-field__err">{form.errors.label}</span>}
          </div>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="g-code">Code</label>
            <input id="g-code" className="pc-input" value={form.data.code} onChange={(e) => form.setData('code', e.target.value)} placeholder="razorpay" />
            <span className="gw-keyhint">Lower-case identifier, used in config and webhooks. Letters, numbers, _ and -.</span>
            {form.errors.code && <span className="pc-field__err">{form.errors.code}</span>}
          </div>
          <label className="pc-check"><input type="checkbox" checked={form.data.is_enabled} onChange={(e) => form.setData('is_enabled', e.target.checked)} /> Enable immediately</label>
          <div className="pc-modal__actions">
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={form.processing}>{form.processing ? 'Adding…' : 'Add gateway'}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- config drawer ---------------- */
function ConfigDrawer({ gateway, testResult, onClose }) {
  const [tab, setTab] = useState('config');
  const form = useForm(Object.fromEntries((gateway.keys ?? []).map((k) => [k.key, ''])));

  useEffect(() => { setTab('config'); form.reset(); form.clearErrors(); }, [gateway.code]); // eslint-disable-line react-hooks/exhaustive-deps

  const save = (e) => {
    e.preventDefault();
    // Only send non-empty values; the server merges & preserves untouched secrets.
    const config = Object.fromEntries(Object.entries(form.data).filter(([, v]) => v !== '' && v != null));
    router.put(rte('config', gateway.code), { config }, { preserveScroll: true });
  };
  const runTest = () => post(rte('test', gateway.code));

  const tabs = [
    { id: 'config', label: 'Configuration' },
    { id: 'capabilities', label: 'Capabilities' },
    { id: 'activity', label: 'Activity' },
  ];
  const feats = [
    ['cards', 'Card payments'], ['wallets', 'Wallets (Apple / Google / PayPal)'], ['bank', 'Bank transfer'],
    ['recurring', 'Recurring / subscriptions'], ['refunds', 'Refunds'], ['multi_currency', 'Multi-currency'],
  ];
  const test = testResult && testResult.code === gateway.code ? testResult : null;

  return (
    <WbDrawer
      open
      onClose={onClose}
      ariaLabel={`Configure ${gateway.label}`}
      tabs={tabs}
      activeTab={tab}
      onTab={setTab}
      head={
        <>
          <div className="gw-dr-top">
            <div className={`gw-mark ${markClass(gateway.code)}`}>{initials(gateway.label)}</div>
            <div>
              <div className="gw-dr-title">{gateway.label}</div>
              <div className="gw-dr-code">{gateway.code}{gateway.is_default ? ' · default' : ''}</div>
            </div>
            <button type="button" className="wb-drawer__x" onClick={onClose} aria-label="Close">✕</button>
          </div>
          <div className="sc-dr-kpis">
            <div className="sc-dr-kpi"><div className="l">Status</div><div className="v">{gateway.enabled ? 'Live' : 'Disabled'}</div></div>
            <div className="sc-dr-kpi"><div className="l">Settled</div><div className="v">{fmtK(gateway.settled)}</div></div>
            <div className="sc-dr-kpi"><div className="l">Keys</div><div className="v">{gateway.configured ? 'Set' : 'Not set'}</div></div>
          </div>
        </>
      }
    >
      {tab === 'config' && (
        <form className="pc-form" onSubmit={save} autoComplete="off">
          {/* Decoy fields absorb Chrome's aggressive username/password autofill so
              the real key inputs below are never populated with login creds. */}
          <input type="text" name="username" autoComplete="username" tabIndex={-1} aria-hidden="true" style={{ display: 'none' }} />
          <input type="password" name="password" autoComplete="current-password" tabIndex={-1} aria-hidden="true" style={{ display: 'none' }} />
          {(gateway.keys ?? []).map((k) => (
            <div className="pc-field" key={k.key}>
              <label className="pc-field__label" htmlFor={`k-${k.key}`}>
                {k.label} {k.set && <span className="gw-keyset">· set</span>}
              </label>
              <input
                id={`k-${k.key}`}
                className="pc-input"
                type={k.secret ? 'password' : 'text'}
                value={form.data[k.key] ?? ''}
                onChange={(e) => form.setData(k.key, e.target.value)}
                placeholder={k.set ? 'Leave blank to keep current' : k.placeholder}
                autoComplete="new-password"
                data-lpignore="true"
                data-1p-ignore=""
                data-form-type="other"
              />
            </div>
          ))}
          {(gateway.keys ?? []).length === 0 && <div className="pc-sub">This gateway needs no keys — it settles offline.</div>}
          {test && <div className={`gw-testbox ${test.ok ? 'gw-testbox--ok' : 'gw-testbox--bad'}`}>{test.ok ? '✓ ' : '⚠ '}{test.message}</div>}
          <div className="sc-dr-acts">
            <button type="submit" className="pc-btn pc-btn--sm pc-btn--primary" disabled={form.processing}>{form.processing ? 'Saving…' : 'Save configuration'}</button>
            <button type="button" className="pc-btn pc-btn--sm" onClick={runTest}>Test connection</button>
          </div>
          <div className="gw-keyhint" style={{ marginTop: 'var(--aeos-space-2)' }}>Secrets are encrypted at rest. The test validates keys are present &amp; well-formed — it never charges anything.</div>
        </form>
      )}
      {tab === 'capabilities' && (
        <div>
          {feats.map(([key, label]) => (
            <div className="pc-drow" key={key}>
              <span className="pc-drow__k">{label}</span>
              <span className="pc-drow__v">{gateway.features?.[key] ? <span className="gw-yes">✓ Supported</span> : <span className="gw-no">Not supported</span>}</span>
            </div>
          ))}
          <div className="pc-sub" style={{ marginTop: 'var(--aeos-space-3)' }}>{gateway.summary}</div>
        </div>
      )}
      {tab === 'activity' && (
        <div className="pc-sub">Configuration changes for this gateway appear in the platform audit log. Set as default, enable/disable and key updates are all recorded.</div>
      )}
    </WbDrawer>
  );
}

/* ---------------- page ---------------- */
export default function Gateways({ overview }) {
  const o = overview ?? {};
  const gateways = useMemo(() => o.gateways ?? [], [o.gateways]);
  const k = o.kpis ?? {};
  const featCols = o.features ?? {};
  const settlement = o.settlement ?? [];
  const activity = o.activity ?? [];
  const ctx = useCtxMenu();
  const [drawerCode, setDrawerCode] = useState(null);
  const [adding, setAdding] = useState(false);

  const flash = usePage().props.flash ?? {};
  const testResult = flash.gateway_test ?? null;

  const drawerGw = gateways.find((g) => g.code === drawerCode) ?? null;

  const currencies = k.currencies ?? [];
  const kpis = [
    { label: 'Live gateways', value: `${k.live ?? 0}`, unit: `/ ${k.total ?? 0}`, delta: `${(k.total ?? 0) - (k.live ?? 0)} disabled`, cls: '' },
    { label: 'Default gateway', value: k.default_label ?? '—', sm: true, delta: 'processes new charges', cls: '' },
    { label: 'Settled volume', value: fmtMoney(k.settled), delta: `${k.payments ?? 0} payments · card`, cls: 'up', spark: k.settled_spark },
    { label: 'Refunds', value: fmtMoney(k.refunds_amount), delta: `${k.refunds_count ?? 0} processed`, cls: '' },
    { label: 'Keys configured', value: `${k.configured ?? 0}`, unit: `/ ${k.total ?? 0}`, delta: (k.configured ?? 0) < (k.total ?? 0) ? 'add keys to go live' : 'all set', cls: (k.configured ?? 0) < (k.total ?? 0) ? 'warn' : 'up' },
    { label: 'Currencies in use', value: `${currencies.length}`, sm: false, delta: currencies.slice(0, 4).join(' · ') || '—', cls: '' },
  ];

  const exportCsv = () => {
    const header = 'code,label,enabled,default,configured,settled,payments';
    const lines = gateways.map((g) => [g.code, g.label, g.enabled ? 'yes' : 'no', g.is_default ? 'yes' : 'no', g.configured ? 'yes' : 'no', g.settled, g.payments]
      .map((v) => `"${String(v).replaceAll('"', '""')}"`).join(','));
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([`${header}\n${lines.join('\n')}`], { type: 'text/csv' }));
    a.download = `payment-gateways-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  const featKeys = Object.keys(featCols);

  return (
    <div className="pc gwc">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Payment gateways</div>
          <h1 className="pc-title">Payment gateways</h1>
          <div className="pc-sub">Every way the platform takes money — enable providers, set the default, drop in API keys, test the connection, and see what each has settled. Keys are encrypted at rest.</div>
        </div>
        <div className="pc-actions">
          <button
            type="button"
            className="pc-btn"
            onClick={(e) => ctx.open(e.currentTarget, [
              { label: 'Export CSV — gateway roster', onClick: exportCsv },
              { label: 'Print this view', onClick: () => window.print() },
            ])}
          >
            {Glyph.export}<span>Export</span>
          </button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={() => setAdding(true)}>{Glyph.plus}<span>Add gateway</span></button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}{c.unit && <small>{c.unit}</small>}</div>
              <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'warn' ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color="var(--aeos-success)" /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* roster */}
      <div className="gw-roster">
        {gateways.map((g) => {
          const test = testResult && testResult.code === g.code ? testResult : null;
          const menu = [
            { label: 'Configure…', onClick: () => setDrawerCode(g.code) },
            { label: 'Test connection', onClick: () => post(rte('test', g.code)) },
            ...(g.is_default ? [] : [{ label: 'Set as default', onClick: () => post(rte('default', g.code)) }]),
            'sep',
            ...(g.is_default ? [] : [{ label: 'Remove gateway…', danger: true, onClick: () => { if (window.confirm(`Remove ${g.label}? This deletes its configuration.`)) router.delete(rte('destroy', g.code), { preserveScroll: true }); } }]),
          ];
          return (
            <Card key={g.code}><CardBody className={`gw-card ${g.enabled ? '' : 'gw-card--off'}`}>
              <div className="gw-top">
                <div className={`gw-mark ${markClass(g.code)}`}>{initials(g.label)}</div>
                <div className="gw-name">
                  <b>{g.label}{g.is_default && <span className="pc-chip pc-chip--live"><span className="pc-chip__dot" />Default</span>}</b>
                  <span>{g.code}</span>
                </div>
                <span className={`gw-status ${g.enabled ? 'gw-status--live' : 'gw-status--off'}`}><span className="gw-sdot" />{g.enabled ? 'Live' : 'Disabled'}</span>
              </div>
              <div className="gw-cap">{g.summary}</div>
              <div className="gw-meta">
                <div><div className="k">Settled</div><div className="v">{fmtK(g.settled)}</div></div>
                <div><div className="k">Payments</div><div className="v">{g.payments}</div></div>
                <div><div className="k">API keys</div><div className={`v ${g.configured ? 'v--ok' : 'v--warn'}`}>{g.configured ? 'Configured' : 'Not set'}</div></div>
              </div>
              {test && <div className={`gw-testbox ${test.ok ? 'gw-testbox--ok' : 'gw-testbox--bad'}`}>{test.ok ? '✓ ' : '⚠ '}{test.message}</div>}
              <div className="gw-acts">
                <span className="gw-swrap">
                  <button
                    type="button"
                    className="gw-sw"
                    aria-pressed={g.enabled}
                    aria-label={`${g.enabled ? 'Disable' : 'Enable'} ${g.label}`}
                    onClick={() => post(rte('toggle', g.code))}
                  />
                  {g.enabled ? 'Enabled' : 'Disabled'}
                </span>
                <span className="gw-spacer" />
                {!g.is_default && <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(rte('default', g.code))}>Set default</button>}
                <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => setDrawerCode(g.code)}>Configure</button>
                <button type="button" className="wb-kebab" aria-label={`More actions for ${g.label}`} onClick={(e) => ctx.open(e.currentTarget, menu)}>⋯</button>
              </div>
            </CardBody></Card>
          );
        })}
      </div>

      {/* capabilities + settlement/activity */}
      <div className="gw-cols">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Capabilities</h2><div className="pc-panel-h__sub">What each gateway can process — pick the right default</div></div>
          </div>
          <div className="pc-tablewrap">
            <table className="gw-matrix">
              <thead>
                <tr>
                  <th>Gateway</th>
                  {featKeys.map((f) => <th key={f}>{featCols[f]}</th>)}
                </tr>
              </thead>
              <tbody>
                {gateways.map((g) => (
                  <tr key={g.code}>
                    <td><span className="gw-mrow"><span className={`gw-mm ${markClass(g.code)}`}>{initials(g.label)}</span>{g.label}</span></td>
                    {featKeys.map((f) => <td key={f}>{g.features?.[f] ? <span className="gw-yes">✓</span> : <span className="gw-no">–</span>}</td>)}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Settlement mix</h2><div className="pc-panel-h__sub">How collected revenue settled</div></div>
            <span className="sc-badge sc-badge--ok">{fmtK(k.settled)}</span>
          </div>
          <div className="gw-mixbar">
            {settlement.map((m, i) => <span key={m.method} className={`gw-mix-c${i % 4}`} style={{ width: `${m.pct}%` }} title={`${m.method} · ${m.count}`} />)}
          </div>
          <div className="sc-dl sc-dl--row">
            {settlement.map((m, i) => (
              <span key={m.method} className="li"><span className={`d gw-mix-c${i % 4}`} />{m.method}<b>{m.count} · {fmtK(m.amount)}</b></span>
            ))}
            {settlement.length <= 1 && <span className="li" style={{ color: 'var(--aeos-text-muted)' }}>Wallet / bank — none yet</span>}
          </div>

          <div className="pc-panel-h" style={{ marginTop: 'var(--aeos-space-5)' }}>
            <div><h2 className="pc-panel-h__title">Recent activity</h2><div className="pc-panel-h__sub">Gateway configuration changes</div></div>
          </div>
          {activity.length === 0
            ? <div className="wb-empty">No gateway activity recorded.</div>
            : (
              <ul className="sc-tl">
                {activity.map((a, i) => (
                  <li key={i}><b>{a.gateway}</b> {a.message}<span className="when">{fmtDateShort(a.at)}</span></li>
                ))}
              </ul>
            )}
        </CardBody></Card>
      </div>

      {/* overlays */}
      {ctx.element}
      {drawerGw && <ConfigDrawer gateway={drawerGw} testResult={testResult} onClose={() => setDrawerCode(null)} />}
      {adding && <AddModal onClose={() => setAdding(false)} />}
    </div>
  );
}

Gateways.layout = (page) => (
  <App title="Payment gateways" railTitle="Gateways" rail={<GatewaysRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
