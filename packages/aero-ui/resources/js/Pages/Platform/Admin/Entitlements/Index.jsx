import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import '../Products/products.css';
import './entitlements.css';

const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  key: svg(<><path d="M15.5 7.5a4.5 4.5 0 1 1-4.9 4.48L4 19l-2-2 1-1 1 1 1.5-1.5 1 1 1.5-1.5-1-1L11 8.9A4.5 4.5 0 0 1 15.5 7.5zM16 10h.01" /></>),
  cube: svg(<><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" /></>),
  product: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
};

const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(s.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); }
  catch { return s; }
};

function EntitlementsRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Overrides</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Active</span><b>{k.active_overrides ?? 0}</b></div>
          <div className="pc-rail__row"><span>Tenants</span><b>{k.tenants ?? 0}</b></div>
          <div className="pc-rail__row"><span>Ledger events</span><b>{k.ledger_events ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/products')}>{Glyph.product}<span>Products</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/tenants')}>{Glyph.cube}<span>Tenants</span></button>
        </div>
      </div>
    </div>
  );
}

const SOURCE_LABEL = { subscription: 'Subscription', license: 'License', override: 'Override', baseline: 'Baseline' };

export default function Index({ kpis, overrides, ledger, tenantOptions, moduleOptions }) {
  const k = kpis ?? {};
  const rows = overrides ?? [];
  const feed = ledger ?? [];
  const tenants = tenantOptions ?? [];
  const modules = moduleOptions ?? [];
  const [busy, setBusy] = useState(false);

  const form = useForm({ tenant_id: '', module_code: '', reason: '' });
  const grant = (e) => {
    e.preventDefault();
    form.post('/entitlements', { preserveScroll: true, onSuccess: () => form.reset('module_code', 'reason') });
  };
  const revoke = (row) => {
    if (!window.confirm(`Revoke ${row.module_code} from ${row.tenant_name}?`)) return;
    setBusy(true);
    router.delete(`/entitlements/${row.id}`, { preserveScroll: true, onFinish: () => setBusy(false) });
  };

  const kpiCards = [
    { label: 'Active overrides', value: k.active_overrides ?? 0, delta: 'in effect now' },
    { label: 'Tenants affected', value: k.tenants ?? 0, delta: 'with a comped module' },
    { label: 'Ledger events', value: k.ledger_events ?? 0, delta: 'grant/revoke records' },
  ];

  return (
    <div className="pc">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Access</div>
          <h1 className="pc-title">Entitlements</h1>
          <div className="pc-sub">Grant a module to a tenant outside a purchase — comp, trial, grandfather or partner access — with a full audit ledger. Takes effect on the tenant's next page load.</div>
        </div>
      </div>

      <div className="pc-kpis ent-kpis3">
        {kpiCards.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className="pc-kpi__delta">{c.delta}</div>
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* grant form */}
      <Card>
        <CardBody>
          <div className="pc-panel-h">
            <div>
              <h2 className="pc-panel-h__title">Grant an override</h2>
              <div className="pc-panel-h__sub">Give a tenant a module without a subscription or license</div>
            </div>
          </div>
          <form className="ent-grant" onSubmit={grant}>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="ent-tenant">Tenant</label>
              <select id="ent-tenant" className="pc-input" value={form.data.tenant_id} onChange={(e) => form.setData('tenant_id', e.target.value)}>
                <option value="">Select a tenant…</option>
                {tenants.map((t) => <option key={t.id} value={t.id}>{t.name}</option>)}
              </select>
              {form.errors.tenant_id && <span className="pc-field__err">{form.errors.tenant_id}</span>}
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="ent-module">Module</label>
              <select id="ent-module" className="pc-input" value={form.data.module_code} onChange={(e) => form.setData('module_code', e.target.value)}>
                <option value="">Select a module…</option>
                {modules.map((m) => <option key={m.code} value={m.code}>{m.name} ({m.code})</option>)}
              </select>
              {form.errors.module_code && <span className="pc-field__err">{form.errors.module_code}</span>}
            </div>
            <div className="pc-field ent-grant__reason">
              <label className="pc-field__label" htmlFor="ent-reason">Reason (optional)</label>
              <input id="ent-reason" className="pc-input" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder="e.g. 30-day pilot" />
            </div>
            <button type="submit" className="pc-btn pc-btn--primary ent-grant__btn" disabled={form.processing}>{Glyph.key}<span>{form.processing ? 'Granting…' : 'Grant'}</span></button>
          </form>
        </CardBody>
      </Card>

      <div className="pc-split">
        {/* open overrides */}
        <Card>
          <CardBody>
            <div className="pc-panel-h">
              <div>
                <h2 className="pc-panel-h__title">Active overrides</h2>
                <div className="pc-panel-h__sub">Module grants currently in effect</div>
              </div>
            </div>
            <div className="pc-tablewrap">
              <table className="pc-table">
                <thead>
                  <tr>
                    <th>Tenant</th>
                    <th>Module</th>
                    <th className="pc-hide-sm">Reason</th>
                    <th className="pc-r">Since</th>
                    <th className="pc-r"></th>
                  </tr>
                </thead>
                <tbody>
                  {rows.length === 0 && (
                    <tr><td colSpan={5}><div className="pc-sub ent-empty">No active overrides. Grant one above.</div></td></tr>
                  )}
                  {rows.map((r) => (
                    <tr key={r.id}>
                      <td><span className="pc-mname">{r.tenant_name}</span></td>
                      <td><span className="pc-modtag">{r.module_code}</span></td>
                      <td className="pc-hide-sm"><span className="pc-sub">{r.reason || '—'}</span></td>
                      <td className="pc-r pc-mcode">{fmtDate(r.granted_at)}</td>
                      <td className="pc-r"><button type="button" className="pc-btn pc-btn--sm pc-btn--danger" disabled={busy} onClick={() => revoke(r)}>Revoke</button></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>

        {/* ledger feed */}
        <Card>
          <CardBody>
            <div className="pc-panel-h">
              <div>
                <h2 className="pc-panel-h__title">Entitlement ledger</h2>
                <div className="pc-panel-h__sub">Recent grant / revoke events, all sources</div>
              </div>
            </div>
            <div className="ent-feed">
              {feed.length === 0 && <div className="pc-sub">No ledger events yet.</div>}
              {feed.map((e, i) => (
                <div key={i} className="ent-feed__row">
                  <span className={`ent-dot ent-dot--${e.state}`} />
                  <div className="ent-feed__body">
                    <div className="ent-feed__main"><b>{e.tenant_name}</b> · <span className="pc-modtag">{e.module_code}</span></div>
                    <div className="ent-feed__meta">{e.state === 'revoked' ? 'Revoked' : 'Granted'} · {SOURCE_LABEL[e.source] ?? e.source} · {fmtDate(e.at)}</div>
                  </div>
                </div>
              ))}
            </div>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}

Index.layout = (page) => (
  <App title="Entitlements" railTitle="Access" rail={<EntitlementsRail kpis={page.props.kpis} />}>
    {page}
  </App>
);
