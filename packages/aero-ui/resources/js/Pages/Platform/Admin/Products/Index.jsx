import { useMemo, useState } from 'react';
import { router, useForm } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import { Card, CardBody } from '@aero/ui';

import './products.css';

/* ---------- tiny inline glyphs (SVG markup, not style) ---------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  refresh: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  plus: svg(<><path d="M12 5v14M5 12h14" /></>),
  product: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8" /></>),
  dev: svg(<><path d="m8 6-6 6 6 6M16 6l6 6-6 6" /></>),
  catalog: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  sell: svg(<><path d="M3 3h18v13H3zM8 21h8M12 16v5" /></>),
  check: svg(<><path d="M20 6 9 17l-5-5" /></>),
  clock: svg(<><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>),
  shield: svg(<><path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6z" /><path d="m9 12 2 2 4-4" /></>),
  chevron: svg(<><path d="m6 9 6 6 6-6" /></>),
  cube: svg(<><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16zM3.3 7 12 12l8.7-5M12 22V12" /></>),
};

const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;

/* ---------------- command-shell context rail ---------------- */
function ProductsRail({ kpis }) {
  const k = kpis ?? {};
  const rows = [
    ['Live products', k.live_products ?? 0],
    ['Adoption', `${k.adoption_pct ?? 0}%`],
    ['Module MRR', fmtMoney(k.module_mrr)],
  ];
  const links = [
    ['Module registry', '/modules', Glyph.cube],
    ['Subscriptions', '/billing/subscriptions', Glyph.product],
    ['Tenants', '/tenants', Glyph.check],
  ];
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Catalog</div>
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

/* ---------------- lifecycle band ---------------- */
function Lifecycle({ lifecycle }) {
  const l = lifecycle ?? {};
  const total = Math.max(1, l.developed ?? 1);
  const tenants = Math.max(1, l.tenants_total ?? 1);
  const stages = [
    { ico: Glyph.dev, title: 'Developed', desc: 'Package + config/module.php defines features & HRMAC actions.', n: l.developed ?? 0, pct: 100 },
    { ico: Glyph.catalog, title: 'Cataloged', desc: 'Synced into the module registry with category & core flag.', n: l.cataloged ?? 0, pct: 100 },
    { ico: Glyph.sell, title: 'Sellable', desc: 'Promoted to a Product with a price & marketplace listing.', n: l.sellable ?? 0, sub: `/ ${l.developed ?? 0}`, pct: Math.round(((l.sellable ?? 0) / total) * 100) },
    { ico: Glyph.check, title: 'Entitled', desc: 'SaaS subscription or standalone license grants access.', n: l.entitled_tenants ?? 0, sub: 'tenants', pct: Math.round(((l.entitled_tenants ?? 0) / tenants) * 100) },
    { ico: Glyph.clock, title: 'Active', desc: 'Nav-filtered & enforced live from the unified resolver.', n: l.active ?? 0, pct: Math.round(((l.active ?? 0) / tenants) * 100) },
  ];
  return (
    <Card>
      <CardBody>
        <div className="pc-panel-h">
          <h2 className="pc-panel-h__title">Module lifecycle</h2>
          <span className="pc-hint">development → customer · counts show modules/tenants at each stage</span>
        </div>
        <div className="pc-flow">
          {stages.map((s) => (
            <div key={s.title} className="pc-stage">
              <div className="pc-stage__ico">{s.ico}</div>
              <div className="pc-stage__title">{s.title}</div>
              <div className="pc-stage__desc">{s.desc}</div>
              <div className="pc-stage__n">{s.n}{s.sub && <small> {s.sub}</small>}</div>
              <div className="pc-stage__bar"><i style={{ width: `${Math.min(100, s.pct)}%` }} /></div>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}

/* ---------------- product detail panel ---------------- */
function Detail({ product, onEdit }) {
  if (!product) {
    return <Card><CardBody><div className="pc-sub">Select a product to inspect its bundle &amp; entitlement path.</div></CardBody></Card>;
  }
  const rows = [
    ['State', product.is_active
      ? <span className="pc-chip pc-chip--live"><span className="pc-chip__dot" />Live product</span>
      : <span className="pc-chip pc-chip--off"><span className="pc-chip__dot" />Inactive</span>],
    ['Price', `${fmtMoney(product.monthly_price)} / month`],
    ['Bundled modules', product.modules.join(', ') || '—'],
    ['Subscriptions', `${product.subscriptions} active`],
    ['Adoption', `${product.subscriptions} / ${product.tenants_total} tenants`],
    ['Module MRR', fmtMoney(product.mrr)],
    ['Marketplace', product.is_marketplace_visible ? 'Visible' : 'Hidden'],
  ];
  const path = [
    <>Promoted to a <b>Product</b></>,
    <>Bundles <b>{product.modules.length} module{product.modules.length === 1 ? '' : 's'}</b></>,
    <>Granted by an active <b>subscription</b></>,
    <>Resolved &amp; <b>nav-gated</b> live</>,
  ];
  return (
    <Card>
      <CardBody>
        <div className="pc-detail__head">
          <div className="pc-detail__ico">{Glyph.product}</div>
          <div>
            <div className="pc-detail__title">{product.name}</div>
            <div className="pc-detail__code">{product.code}</div>
          </div>
        </div>
        {rows.map(([k, v]) => (
          <div key={k} className="pc-drow"><span className="pc-drow__k">{k}</span><span className="pc-drow__v">{v}</span></div>
        ))}
        <div className="pc-ent">
          <div className="pc-ent__l">Entitlement path</div>
          <div className="pc-ent__path">
            {path.map((step, i) => (
              <div key={i} className="pc-estep"><span className="pc-estep__i">{Glyph.check}</span>{step}</div>
            ))}
          </div>
        </div>
        <div className="pc-detail__actions">
          <button type="button" className="pc-btn" onClick={() => onEdit?.(product)}>Edit product</button>
          <button type="button" className="pc-btn" onClick={() => router.visit('/billing/subscriptions')}>View adoption</button>
        </div>
      </CardBody>
    </Card>
  );
}

/* ---------------- create / edit modal ---------------- */
function ProductModal({ product, moduleOptions, onClose }) {
  const editing = Boolean(product);
  const form = useForm({
    name: product?.name ?? '',
    code: product?.code ?? '',
    description: product?.description ?? '',
    monthly_price: product?.monthly_price ?? 0,
    yearly_price: product?.yearly_price ?? 0,
    is_active: product?.is_active ?? true,
    is_marketplace_visible: product?.is_marketplace_visible ?? true,
    modules: product?.modules ?? [],
  });
  const { data, setData, errors, processing } = form;

  const toggleModule = (code) => setData('modules', data.modules.includes(code)
    ? data.modules.filter((c) => c !== code)
    : [...data.modules, code]);

  const submit = (e) => {
    e.preventDefault();
    const opts = { preserveScroll: true, onSuccess: onClose };
    if (editing) form.put(`/products/${product.id}`, opts);
    else form.post('/products', opts);
  };

  const remove = () => {
    if (!window.confirm(`Delete "${product.name}"? This can't be undone. (Blocked if it has active subscriptions.)`)) return;
    router.delete(`/products/${product.id}`, { preserveScroll: true, onSuccess: onClose });
  };

  return (
    <div className="pc-modal-overlay" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
      <div className="pc-modal" role="dialog" aria-modal="true">
        <h2 className="pc-modal__title">{editing ? 'Edit product' : 'New product'}</h2>
        <div className="pc-modal__sub">A product bundles one or more modules and is what customers subscribe to.</div>
        <form className="pc-form" onSubmit={submit}>
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="pf-name">Name</label>
            <input id="pf-name" className="pc-input" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="HRM Suite" />
            {errors.name && <span className="pc-field__err">{errors.name}</span>}
          </div>
          {!editing && (
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="pf-code">Code (slug)</label>
              <input id="pf-code" className="pc-input" value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="hrm-suite" />
              {errors.code && <span className="pc-field__err">{errors.code}</span>}
            </div>
          )}
          <div className="pc-field">
            <label className="pc-field__label" htmlFor="pf-desc">Description</label>
            <textarea id="pf-desc" className="pc-input" value={data.description ?? ''} onChange={(e) => setData('description', e.target.value)} />
          </div>
          <div className="pc-row2">
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="pf-mp">Monthly price ($)</label>
              <input id="pf-mp" type="number" min="0" step="0.01" className="pc-input" value={data.monthly_price} onChange={(e) => setData('monthly_price', e.target.value)} />
              {errors.monthly_price && <span className="pc-field__err">{errors.monthly_price}</span>}
            </div>
            <div className="pc-field">
              <label className="pc-field__label" htmlFor="pf-yp">Yearly price ($)</label>
              <input id="pf-yp" type="number" min="0" step="0.01" className="pc-input" value={data.yearly_price} onChange={(e) => setData('yearly_price', e.target.value)} />
            </div>
          </div>
          <div className="pc-field">
            <span className="pc-field__label">Bundled modules</span>
            <div className="pc-modtag-pick">
              {moduleOptions.length === 0 && <span className="pc-modal__sub">No sellable modules yet — develop a non-core module to bundle.</span>}
              {moduleOptions.map((m) => (
                <button type="button" key={m.code} className="pc-modpick" data-on={data.modules.includes(m.code)} onClick={() => toggleModule(m.code)}>
                  {data.modules.includes(m.code) ? '✓ ' : ''}{m.name} <i className="mono">{m.code}</i>
                </button>
              ))}
            </div>
            {errors.modules && <span className="pc-field__err">{errors.modules}</span>}
          </div>
          <div className="pc-checks">
            <label className="pc-check"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} /> Active (available to sell)</label>
            <label className="pc-check"><input type="checkbox" checked={data.is_marketplace_visible} onChange={(e) => setData('is_marketplace_visible', e.target.checked)} /> Marketplace-visible</label>
          </div>
          <div className="pc-modal__actions">
            {editing && <button type="button" className="pc-btn pc-btn--danger" onClick={remove}>Delete</button>}
            <span className="pc-spacer" />
            <button type="button" className="pc-btn" onClick={onClose}>Cancel</button>
            <button type="submit" className="pc-btn pc-btn--primary" disabled={processing}>{processing ? 'Saving…' : (editing ? 'Save changes' : 'Create product')}</button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ---------------- page ---------------- */
export default function Index({ kpis, lifecycle, products, systemModules, moduleOptions }) {
  const list = products ?? [];
  const [selectedId, setSelectedId] = useState(list[0]?.id ?? null);
  const [view, setView] = useState('products');
  const [modalOpen, setModalOpen] = useState(false);
  const [editProduct, setEditProduct] = useState(null);
  const selected = useMemo(() => list.find((p) => p.id === selectedId) ?? list[0] ?? null, [list, selectedId]);
  const k = kpis ?? {};
  const sys = systemModules ?? [];
  const opts = moduleOptions ?? [];

  const openNew = () => { setEditProduct(null); setModalOpen(true); };
  const openEdit = (p) => { setEditProduct(p); setModalOpen(true); };

  const kpiCards = [
    { label: 'Products in catalog', value: k.products_total ?? 0, delta: `${sys.length} foundation · ${k.live_products ?? 0} sellable` },
    { label: 'Live products', value: k.live_products ?? 0, delta: 'marketplace-visible' },
    { label: 'Adoption', value: k.adoption_pct ?? 0, unit: '%', delta: `${k.entitled_tenants ?? 0} of ${k.tenants_total ?? 0} tenants`, up: true },
    { label: 'Module MRR', value: fmtMoney(k.module_mrr), delta: `${k.entitled_tenants ?? 0} paying tenants`, up: true },
    { label: 'Catalog health', value: k.catalog_health ?? 0, unit: '%', delta: (k.catalog_health ?? 0) === 100 ? '✓ all products mapped' : 'products missing modules', up: (k.catalog_health ?? 0) === 100 },
  ];

  return (
    <div className="pc">
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Catalog governance</div>
          <h1 className="pc-title">Products</h1>
          <div className="pc-sub">Every sellable capability from development to customer — one lifecycle, one entitlement source across SaaS subscriptions and standalone licenses.</div>
        </div>
        <div className="pc-actions">
          <button type="button" className="pc-btn" onClick={() => router.visit('/modules')}>{Glyph.cube}<span>Module registry</span></button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={openNew}>{Glyph.plus}<span>New product</span></button>
        </div>
      </div>

      {/* KPI strip */}
      <div className="pc-kpis">
        {kpiCards.map((c) => (
          <Card key={c.label}>
            <CardBody>
              <div className="pc-kpi">
                <div className="pc-kpi__label">{c.label}</div>
                <div className="pc-kpi__value">{c.value}{c.unit && <small>{c.unit}</small>}</div>
                <div className={`pc-kpi__delta${c.up ? ' pc-kpi__delta--up' : ''}`}>{c.delta}</div>
              </div>
            </CardBody>
          </Card>
        ))}
      </div>

      <Lifecycle lifecycle={lifecycle} />

      <div className="pc-split">
        {/* catalog */}
        <Card>
          <CardBody>
            <div className="pc-panel-h">
              <div>
                <h2 className="pc-panel-h__title">Products</h2>
                <div className="pc-panel-h__sub">Sellable modules — what customers subscribe to or license</div>
              </div>
              <div className="pc-seg">
                <button type="button" className="pc-seg__b" aria-pressed={view === 'products'} onClick={() => setView('products')}>Products · {list.length}</button>
                <button type="button" className="pc-seg__b" aria-pressed={view === 'system'} onClick={() => setView('system')}>System · {sys.length}</button>
              </div>
            </div>

            {view === 'products' ? (
              <div className="pc-tablewrap">
                <table className="pc-table">
                  <thead>
                    <tr>
                      <th>Product</th>
                      <th className="pc-hide-sm">Bundled modules</th>
                      <th>State</th>
                      <th className="pc-r">Price</th>
                      <th className="pc-r">Adoption</th>
                    </tr>
                  </thead>
                  <tbody>
                    {list.map((p) => (
                      <tr key={p.id} aria-selected={p.id === selected?.id} onClick={() => setSelectedId(p.id)}>
                        <td>
                          <div className="pc-mrow">
                            <div className="pc-mico">{Glyph.product}</div>
                            <div><div className="pc-mname">{p.name}</div><div className="pc-mcode">{p.code}</div></div>
                          </div>
                        </td>
                        <td className="pc-hide-sm"><div className="pc-modtags">{p.modules.map((m) => <span key={m} className="pc-modtag">{m}</span>)}</div></td>
                        <td>{p.is_active
                          ? <span className="pc-chip pc-chip--live"><span className="pc-chip__dot" />Live</span>
                          : <span className="pc-chip pc-chip--off"><span className="pc-chip__dot" />Off</span>}</td>
                        <td className="pc-r pc-price">{fmtMoney(p.monthly_price)}<small>/mo</small></td>
                        <td className="pc-r">
                          <div className="pc-adopt">
                            <span className="pc-adopt__bar"><i style={{ width: `${Math.min(100, p.adoption_pct)}%` }} /></span>
                            <span className="pc-adopt__n">{p.subscriptions} / {p.tenants_total}</span>
                          </div>
                        </td>
                      </tr>
                    ))}
                    <tr className="pc-addrow">
                      <td colSpan={5}>
                        <div className="pc-addcta" onClick={openNew}><span className="pc-plus">{Glyph.plus}</span>Promote a module to a sellable product — bundle one or more modules with a price</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="pc-tablewrap">
                <table className="pc-table">
                  <thead>
                    <tr>
                      <th>Module</th>
                      <th className="pc-hide-sm">Category</th>
                      <th>Type</th>
                      <th className="pc-r">Billing</th>
                    </tr>
                  </thead>
                  <tbody>
                    {sys.map((m) => (
                      <tr key={m.code}>
                        <td>
                          <div className="pc-mrow">
                            <div className="pc-mico">{Glyph.shield}</div>
                            <div><div className="pc-mname">{m.name}</div><div className="pc-mcode">{m.code}</div></div>
                          </div>
                        </td>
                        <td className="pc-hide-sm"><span className="pc-modtag">{m.category}</span></td>
                        <td><span className="pc-chip pc-chip--foundation"><span className="pc-chip__dot" />Foundation</span></td>
                        <td className="pc-r pc-free">Bundled</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* system / foundation tray (demoted) — only in products view */}
            {view === 'products' && (
              <div className="pc-sys">
                <div className="pc-sys-h">
                  <div className="pc-sys-t">
                    {Glyph.shield}
                    <span><b>{sys.length} system &amp; infrastructure modules</b> · bundled with every tenant</span>
                  </div>
                  <button type="button" className="pc-sys-toggle" onClick={() => setView('system')}>
                    View{Glyph.chevron}
                  </button>
                </div>
              </div>
            )}
          </CardBody>
        </Card>

        <Detail product={selected} onEdit={openEdit} />
      </div>

      {modalOpen && (
        <ProductModal
          product={editProduct}
          moduleOptions={opts}
          onClose={() => setModalOpen(false)}
        />
      )}
    </div>
  );
}

Index.layout = (page) => (
  <App
    title="Products"
    railTitle="Catalog"
    rail={<ProductsRail kpis={page.props.kpis} />}
  >
    {page}
  </App>
);
