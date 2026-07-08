import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import '../Products/products.css';
import './modules.css';

/* ---------- inline glyphs (SVG markup, not style) ---------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  cube: svg(<><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16zM3.3 7 12 12l8.7-5M12 22V12" /></>),
  refresh: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  check: svg(<><path d="M20 6 9 17l-5-5" /></>),
  shield: svg(<><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6z" /><path d="m9 12 2 2 4-4" /></>),
  product: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
};

const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(s.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }
  catch { return s; }
};

/* ---------------- rail ---------------- */
function ModulesRail({ kpis, sync }) {
  const k = kpis ?? {};
  const rows = [
    ['Modules', k.total ?? 0],
    ['Components', k.components ?? 0],
    ['Actions', k.actions ?? 0],
    ['Last synced', fmtDate(sync?.last_synced)],
  ];
  const links = [
    ['Products', '/products', Glyph.product],
    ['Roles & access', '/roles', Glyph.shield],
  ];
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Registry</div>
        <div className="pc-rail__rows">
          {rows.map(([label, val]) => (
            <div key={label} className="pc-rail__row"><span>{label}</span><b>{val}</b></div>
          ))}
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          {links.map(([label, href, icon]) => (
            <button key={href} type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit(href)}>
              {icon}<span>{label}</span>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}

/* ---------------- detail ---------------- */
function Detail({ module }) {
  if (!module) {
    return <Card><CardBody><div className="pc-sub">Select a module to inspect its hierarchy &amp; dependencies.</div></CardBody></Card>;
  }
  const typeChip = module.is_core
    ? <span className="pc-chip pc-chip--foundation"><span className="pc-chip__dot" />Foundation</span>
    : <span className="pc-chip pc-chip--live"><span className="pc-chip__dot" />Product module</span>;
  const rows = [
    ['Type', typeChip],
    ['Category', <span className="pc-modtag">{module.category}</span>],
    ['Scope', module.scope],
    ['Version', module.version || '—'],
    ['Sub-modules', module.sub_modules],
    ['Components', module.components],
    ['Actions', module.actions],
    ['Sellable', module.is_sellable ? 'Yes — via a product' : 'No — bundled'],
    ['State', module.is_active ? 'Active' : 'Inactive'],
  ];
  return (
    <Card>
      <CardBody>
        <div className="pc-detail__head">
          <div className="pc-detail__ico">{module.is_core ? Glyph.shield : Glyph.cube}</div>
          <div>
            <div className="pc-detail__title">{module.name}</div>
            <div className="pc-detail__code">{module.code}</div>
          </div>
        </div>
        {rows.map(([k, v]) => (
          <div key={k} className="pc-drow"><span className="pc-drow__k">{k}</span><span className="pc-drow__v">{v}</span></div>
        ))}
        {module.dependencies?.length > 0 && (
          <div className="pc-ent">
            <div className="pc-ent__l">Depends on</div>
            <div className="pc-modtags">{module.dependencies.map((d) => <span key={d} className="pc-modtag">{d}</span>)}</div>
          </div>
        )}
        <div className="pc-detail__actions">
          {module.is_core ? (
            <span className="pc-hint">Foundation module — always active.</span>
          ) : (
            <button type="button" className={`pc-btn${module.is_active ? ' pc-btn--danger' : ' pc-btn--primary'}`}
              onClick={() => router.post(`/modules/${module.id}/toggle`, {}, { preserveScroll: true })}>
              {module.is_active ? 'Deactivate module' : 'Activate module'}
            </button>
          )}
        </div>
      </CardBody>
    </Card>
  );
}

/* ---------------- page ---------------- */
export default function Index({ kpis, modules, sync }) {
  const list = modules ?? [];
  const [selectedId, setSelectedId] = useState(list[0]?.id ?? null);
  const [syncing, setSyncing] = useState(false);
  const selected = useMemo(() => list.find((m) => m.id === selectedId) ?? list[0] ?? null, [list, selectedId]);
  const k = kpis ?? {};

  const kpiCards = [
    { label: 'Modules shipped', value: k.total ?? 0, delta: `${k.core ?? 0} foundation · ${k.sellable ?? 0} sellable` },
    { label: 'HRMAC components', value: k.components ?? 0, delta: `${k.actions ?? 0} permissioned actions` },
    { label: 'Sellable', value: k.sellable ?? 0, delta: 'exposed as products' },
    { label: 'Active', value: k.active ?? 0, delta: `of ${k.total ?? 0} registered` },
    { label: 'Sync health', value: (sync?.drift ?? 0) === 0 ? 'OK' : `${sync.drift} drift`, delta: `synced ${fmtDate(sync?.last_synced)}`, up: (sync?.drift ?? 0) === 0 },
  ];

  return (
    <div className="pc">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Module registry</div>
          <h1 className="pc-title">Modules</h1>
          <div className="pc-sub">The technical registry of shipped capabilities — HRMAC hierarchy, dependencies and sync health. Pricing &amp; productisation live on the Products page.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => router.visit('/products')}>{Glyph.product}<span>Products</span></button>
          <button type="button" className="pc-btn pc-btn--primary" disabled={syncing}
            onClick={() => router.post('/modules/resync', {}, { preserveScroll: true, onStart: () => setSyncing(true), onFinish: () => setSyncing(false) })}>
            {Glyph.refresh}<span>{syncing ? 'Syncing…' : 'Re-sync registry'}</span>
          </button>
        </div>
      </div>

      <div className="pc-kpis">
        {kpiCards.map((c) => (
          <Card key={c.label}>
            <CardBody>
              <div className="pc-kpi">
                <div className="pc-kpi__label">{c.label}</div>
                <div className="pc-kpi__value">{c.value}</div>
                <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
              </div>
            </CardBody>
          </Card>
        ))}
      </div>

      <div className="pc-split">
        <Card>
          <CardBody>
            <div className="pc-panel-h">
              <div>
                <h2 className="pc-panel-h__title">Module registry</h2>
                <div className="pc-panel-h__sub">Every shipped module — foundation &amp; sellable, with HRMAC depth</div>
              </div>
            </div>
            <div className="pc-tablewrap">
              <table className="pc-table">
                <thead>
                  <tr>
                    <th>Module</th>
                    <th className="pc-hide-sm">Category</th>
                    <th>Type</th>
                    <th className="pc-r">Hierarchy</th>
                    <th className="pc-r">State</th>
                  </tr>
                </thead>
                <tbody>
                  {list.map((m) => (
                    <tr key={m.id} aria-selected={m.id === selected?.id} onClick={() => setSelectedId(m.id)}>
                      <td>
                        <div className="pc-mrow">
                          <div className="pc-mico">{m.is_core ? Glyph.shield : Glyph.cube}</div>
                          <div><div className="pc-mname">{m.name}</div><div className="pc-mcode">{m.code}</div></div>
                        </div>
                      </td>
                      <td className="pc-hide-sm"><span className="pc-modtag">{m.category}</span></td>
                      <td>{m.is_core
                        ? <span className="pc-chip pc-chip--foundation"><span className="pc-chip__dot" />Foundation</span>
                        : <span className="pc-chip pc-chip--live"><span className="pc-chip__dot" />Product</span>}</td>
                      <td className="pc-r mr-hier">{m.sub_modules} sub · {m.components} comp · {m.actions} act</td>
                      <td className="pc-r">
                        <span className={`pc-chip ${m.is_active ? 'pc-chip--live' : 'pc-chip--off'}`}><span className="pc-chip__dot" />{m.is_active ? 'Active' : 'Off'}</span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardBody>
        </Card>

        <Detail module={selected} />
      </div>
    </div>
  );
}

Index.layout = (page) => (
  <App
    title="Modules"
    railTitle="Registry"
    rail={<ModulesRail kpis={page.props.kpis} sync={page.props.sync} />}
  >
    {page}
  </App>
);
