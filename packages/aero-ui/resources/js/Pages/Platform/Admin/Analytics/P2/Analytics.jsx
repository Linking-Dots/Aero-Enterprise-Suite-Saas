import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, Donut,
  useCtxMenu,
} from '@aero/ui';

import '../../Products/products.css';
import '../../Billing/P2/subscriptions.css';
import './analytics.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  billing: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  tenants: svg(<><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" /></>),
  plans: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
};

const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(Number(n ?? 0)) >= 1000 ? `$${(Number(n) / 1000).toFixed(1)}k` : fmtMoney(n));
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const PALETTE = ['var(--aeos-warning)', 'var(--aeos-tertiary, var(--aeos-primary))', 'var(--aeos-primary)', 'var(--aeos-success)', 'var(--aeos-text-muted)', 'var(--aeos-danger)'];

const RANGES = [['30d', '30d'], ['90d', '90d'], ['6m', '6M'], ['12m', '12M']];

/* ---------------- rail ---------------- */
function AnalyticsRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Recurring</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>MRR</span><b>{fmtMoney(k.mrr)}</b></div>
          <div className="pc-rail__row"><span>ARR</span><b>{fmtK(k.arr)}</b></div>
          <div className="pc-rail__row"><span>ARPA</span><b>{fmtMoney(k.arpa)}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Tenants</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Active</span><b>{k.active_tenants ?? 0}</b></div>
          <div className="pc-rail__row"><span>Trialing</span><b>{k.trial_tenants ?? 0}</b></div>
          <div className="pc-rail__row"><span>Churned</span><b>{k.churned_tenants ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing')}>{Glyph.billing}<span>Billing</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/tenants')}>{Glyph.tenants}<span>Tenants</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/plans')}>{Glyph.plans}<span>Plans</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- page ---------------- */
export default function Analytics({ overview }) {
  const o = overview ?? {};
  const k = o.kpis ?? {};
  const sp = o.sparks ?? {};
  const trend = o.trend ?? {};
  const byPlan = o.by_plan ?? [];
  const byProduct = o.by_product ?? [];
  const planDist = o.plan_dist ?? [];
  const top = o.top_tenants ?? [];
  const range = o.range ?? '6m';
  const ctx = useCtxMenu();

  const setRange = (r) => router.get(route('platform.admin.analytics.index'), { range: r }, { preserveState: true, preserveScroll: true, only: ['overview'] });

  const up = (k.mrr_delta_pct ?? 0) >= 0;
  const kpis = [
    { label: 'Monthly recurring', value: fmtMoney(k.mrr), delta: `${up ? '▲' : '▼'} ${Math.abs(k.mrr_delta_pct ?? 0)}% vs last month`, cls: up ? 'up' : 'down', spark: sp.mrr },
    { label: 'Annual run-rate', value: fmtK(k.arr), delta: `plan ${fmtMoney(k.plan_mrr)} · product ${fmtMoney(k.product_mrr)}`, spark: sp.arr },
    { label: 'Active tenants', value: k.active_tenants ?? 0, delta: `${k.trial_tenants ?? 0} trialing` },
    { label: 'Trial tenants', value: k.trial_tenants ?? 0, delta: 'in evaluation' },
    { label: 'New (period)', value: k.new_tenants ?? 0, delta: `${k.churned_tenants ?? 0} churned`, cls: (k.churned_tenants ?? 0) === 0 ? 'up' : '' },
    { label: 'ARPA', value: fmtMoney(k.arpa), delta: 'MRR ÷ active tenants' },
  ];

  const barItems = [
    ...byPlan.map((p) => ({ name: p.name, sub: '', mrr: p.mrr })),
    ...byProduct.map((p) => ({ name: p.name, sub: 'product', mrr: p.mrr })),
  ];
  const barMax = Math.max(1, ...barItems.map((b) => b.mrr));

  const distTotal = planDist.reduce((a, d) => a + d.count, 0);
  const newMax = Math.max(1, ...(trend.new ?? []));

  const exportCsv = () => {
    const rows = [['metric', 'value'], ['MRR', k.mrr], ['ARR', k.arr], ['Plan MRR', k.plan_mrr], ['Product MRR', k.product_mrr],
      ['Active tenants', k.active_tenants], ['Trial tenants', k.trial_tenants], ['New (period)', k.new_tenants], ['Churned', k.churned_tenants], ['ARPA', k.arpa],
      ['—', '—'], ['Plan', 'MRR'], ...byPlan.map((p) => [p.name, p.mrr]), ...byProduct.map((p) => [`${p.name} (product)`, p.mrr])];
    const csv = rows.map((r) => r.map((v) => `"${String(v ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    a.download = `platform-analytics-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc anx">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Platform · Analytics</div>
          <h1 className="pc-title">Analytics</h1>
          <div className="pc-sub">Recurring revenue, growth and tenant health across the whole platform — trends, plan mix, and the tenants driving MRR, in one board.</div>
        </div>
        <div className="pc-actions">
          <div className="an-seg" role="group" aria-label="Date range">
            {RANGES.map(([val, lab]) => (
              <button key={val} type="button" className="an-seg__b" aria-pressed={range === val} onClick={() => setRange(val)}>{lab}</button>
            ))}
          </div>
          <button
            type="button"
            className="pc-btn"
            onClick={(e) => ctx.open(e.currentTarget, [
              { label: 'Export CSV — analytics summary', onClick: exportCsv },
              { label: 'Print this view', onClick: () => window.print() },
            ])}
          >
            {Glyph.export}<span>Export</span>
          </button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'down' ? ' pc-kpi__delta--down' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color="var(--aeos-primary)" /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* revenue row */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Monthly recurring revenue</h2><div className="pc-panel-h__sub">Recorded MRR at each month-end</div></div>
            <span className="sc-badge sc-badge--ok">▲ {k.ytd_growth_pct ?? 0}% over range</span>
          </div>
          <AreaTrend
            series={[
              { key: 'mrr', label: 'MRR', color: 'var(--aeos-primary)', values: trend.mrr ?? [] },
            ]}
            labels={trend.labels ?? []}
            height={220}
            ariaLabel="Recurring revenue by month"
            empty="No revenue history for this range."
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-exp" />MRR at last close <b>{fmtK((trend.mrr ?? []).at(-1) ?? 0)}</b></span>
            <span className="li" style={{ color: 'var(--aeos-text-muted)' }}>Live now {fmtMoney(k.mrr)} · +{k.new_tenants ?? 0} tenants this period</span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">MRR by plan</h2><div className="pc-panel-h__sub">Plan subscriptions + product add-ons</div></div></div>
          <div className="an-hbars">
            {barItems.map((b, i) => (
              <div className="an-hbar" key={`${b.name}-${i}`}>
                <span className="an-hbar__nm">{b.name}{b.sub && <small> · {b.sub}</small>}</span>
                <span className="an-hbar__track"><span className="an-hbar__fill" style={{ width: `${Math.max(6, (b.mrr / barMax) * 100)}%`, background: PALETTE[i % PALETTE.length] }} /></span>
                <span className="an-hbar__amt">{fmtMoney(b.mrr)}<small>/mo</small></span>
              </div>
            ))}
            {barItems.length === 0 && <div className="wb-empty">No active subscriptions.</div>}
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Plan distribution</h2><div className="pc-panel-h__sub">{distTotal} active tenants</div></div></div>
          <div className="sc-donut-row">
            <Donut
              segments={planDist.map((d, i) => ({ color: PALETTE[i % PALETTE.length], value: d.count }))}
              centerValue={`${distTotal}`}
              centerLabel="tenants"
              size={116}
            />
            <div className="sc-dl">
              {planDist.map((d, i) => (
                <span key={d.plan_name} className="li"><span className="d" style={{ background: PALETTE[i % PALETTE.length] }} />{d.plan_name}<b>{d.count}</b></span>
              ))}
              {planDist.length === 0 && <span className="li" style={{ color: 'var(--aeos-text-muted)' }}>No active plans</span>}
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* growth row */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Tenant growth</h2><div className="pc-panel-h__sub">Active tenants — monthly</div></div></div>
          <AreaTrend
            series={[{ key: 'active', label: 'Active tenants', color: 'var(--aeos-tertiary, var(--aeos-primary))', values: trend.active ?? [] }]}
            labels={trend.labels ?? []}
            height={190}
            ariaLabel="Active tenants by month"
            empty="No tenant history for this range."
          />
          <div className="sc-dl sc-dl--row"><span className="li"><span className="d" style={{ background: 'var(--aeos-tertiary, var(--aeos-primary))' }} />Active tenants <b>{(trend.active ?? []).at(-1) ?? 0}</b></span></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Signups vs churn</h2><div className="pc-panel-h__sub">New tenants each month · {k.churned_tenants ?? 0} churned</div></div></div>
          <div className="an-vbars">
            {(trend.labels ?? []).map((lab, i) => (
              <div className="an-vbar" key={lab + i}>
                <div className="an-vbar__n">{(trend.new ?? [])[i] || ''}</div>
                <div className="an-vbar__col" style={{ height: `${((trend.new ?? [])[i] ?? 0) / newMax * 100}%` }} />
                <div className="an-vbar__lab">{lab}</div>
              </div>
            ))}
          </div>
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-new" />New <b>{(trend.new ?? []).reduce((a, b) => a + b, 0)}</b></span>
            <span className="li"><span className="d sc-d-churn" />Churned <b>{k.churned_tenants ?? 0}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h"><div><h2 className="pc-panel-h__title">Top tenants by MRR</h2><div className="pc-panel-h__sub">Highest recurring value</div></div></div>
          <div className="an-lb">
            {top.map((t, i) => (
              <div className="an-lbrow" key={`${t.tenant}-${i}`}>
                <span className="an-lb__rank">{i + 1}</span>
                <div className="sc-av">{initials(t.tenant)}</div>
                <div className="an-lb__n"><b>{t.tenant}</b><span>{t.plan}</span></div>
                <span className="an-lb__v">{fmtMoney(t.mrr)}<small>/mo</small></span>
              </div>
            ))}
            {top.length === 0 && <div className="wb-empty">No active subscriptions.</div>}
          </div>
        </CardBody></Card>
      </div>

      {ctx.element}
    </div>
  );
}

Analytics.layout = (page) => (
  <App title="Analytics" railTitle="Analytics" rail={<AnalyticsRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
