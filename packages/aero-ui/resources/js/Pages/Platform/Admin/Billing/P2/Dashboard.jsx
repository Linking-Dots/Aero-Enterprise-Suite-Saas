import { useCallback } from 'react';
import { router } from '@inertiajs/react';

import App from '@/Pages/App.jsx';
import {
  Card, CardBody,
  AreaTrend, AreaSpark, BarsDiverging, Donut,
  useCtxMenu,
} from '@aero/ui';

import { usePolling } from '../../Dashboard/lib.jsx';
import '../../Products/products.css';
import './subscriptions.css';
import './billing.css';

/* ---------------- shared bits ---------------- */
const svg = (paths) => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18" strokeLinecap="round" strokeLinejoin="round">{paths}</svg>
);
const Glyph = {
  subs: svg(<><path d="M21 12a9 9 0 1 1-3-6.7M21 3v6h-6" /></>),
  invoices: svg(<><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M8 13h8M8 17h5" /></>),
  plans: svg(<><path d="M4 7h16M4 12h16M4 17h10" /></>),
  gateways: svg(<><rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20" /></>),
  export: svg(<><path d="M12 3v12M7 10l5 5 5-5" /><path d="M4 21h16" /></>),
};

const CUR = { USD: '$', EUR: '€', GBP: '£', BDT: '৳', AUD: 'A$', CAD: 'C$' };
const sym = (c) => CUR[c] ?? '';
const fmtMoney = (n) => `$${Number(n ?? 0).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const fmtK = (n) => (Math.abs(Number(n ?? 0)) >= 1000 ? `$${(Number(n) / 1000).toFixed(1)}k` : fmtMoney(n));
const fmtCur = (n, c) => `${n < 0 ? '−' : ''}${sym(c) || '$'}${Math.abs(Number(n ?? 0)).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
const initials = (name) => (name || '—').split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
const fmtDateShort = (s) => {
  if (!s) return '—';
  try { return new Date(String(s).replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); } catch { return s; }
};

const post = (url, data = {}) => router.post(url, data, { preserveScroll: true });
const confirmPost = (msg, url, data = {}) => { if (window.confirm(msg)) post(url, data); };
const goSubs = () => router.visit('/billing/subscriptions');
const goInvoices = () => router.visit('/billing/invoices');

/* ---------------- rail ---------------- */
function BillingRail({ kpis }) {
  const k = kpis ?? {};
  return (
    <div className="pc-rail">
      <div>
        <div className="pc-panel-h__title">Recurring</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>MRR</span><b>{fmtMoney(k.mrr)}</b></div>
          <div className="pc-rail__row"><span>ARR</span><b>{fmtK(k.arr)}</b></div>
          <div className="pc-rail__row"><span>Active subs</span><b>{k.active ?? 0}</b></div>
          <div className="pc-rail__row"><span>Trialing</span><b>{k.trialing ?? 0}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Collections</div>
        <div className="pc-rail__rows">
          <div className="pc-rail__row"><span>Collected</span><b>{fmtMoney(k.collected)}</b></div>
          <div className="pc-rail__row"><span>Outstanding</span><b>{fmtMoney(k.outstanding)}</b></div>
          <div className="pc-rail__row"><span>Overdue</span><b>{fmtMoney(k.overdue_amt)}</b></div>
        </div>
      </div>
      <div>
        <div className="pc-panel-h__title">Go to</div>
        <div className="pc-rail__links">
          <button type="button" className="pc-btn pc-btn--sm" onClick={goSubs}>{Glyph.subs}<span>Subscriptions</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={goInvoices}>{Glyph.invoices}<span>Invoices</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/plans')}>{Glyph.plans}<span>Plans</span></button>
          <button type="button" className="pc-btn pc-btn--sm" onClick={() => router.visit('/billing/gateways')}>{Glyph.gateways}<span>Payment gateways</span></button>
        </div>
      </div>
    </div>
  );
}

/* ---------------- page ---------------- */
export default function Dashboard({ overview }) {
  const o = overview ?? {};
  const k = o.kpis ?? {};
  const sp = o.sparks ?? {};
  const health = o.health ?? {};
  const q = o.queues ?? {};
  const movement = o.movement ?? {};
  const trend = o.trend ?? {};
  const activity = o.activity ?? [];
  const gateways = o.gateways ?? [];
  const methodMix = o.methodMix ?? [];
  const ctx = useCtxMenu();

  // Refresh the whole roll-up (the only prop the page reads) so a poll can
  // never leave the hub data-less. Visibility-gated inside usePolling.
  const poll = useCallback(() => {
    router.reload({ only: ['overview'], preserveScroll: true, preserveState: true });
  }, []);
  usePolling(poll, 45000);

  const deltaUp = (k.mrr_delta_pct ?? 0) >= 0;
  const churn = Number(k.churn_pct ?? 0);
  const kpis = [
    { label: 'Monthly recurring', value: fmtMoney(k.mrr), delta: `${deltaUp ? '▲' : '▼'} ${Math.abs(k.mrr_delta_pct ?? 0)}% vs last month`, cls: deltaUp ? 'up' : 'down', spark: sp.mrr, color: 'var(--aeos-primary)' },
    { label: 'Annual run-rate', value: fmtK(k.arr), delta: `plan ${fmtMoney(k.plan_mrr)} · product ${fmtMoney(k.product_mrr)}`, spark: sp.arr, color: 'var(--aeos-primary)' },
    { label: 'Collected', value: fmtMoney(k.collected), delta: `${k.paid ?? 0} invoices paid · ${k.paid_rate ?? 0}% rate`, cls: 'up', spark: sp.collected, color: 'var(--aeos-success)' },
    { label: 'Outstanding', value: fmtMoney(k.outstanding), delta: `${k.open ?? 0} open · ${k.overdue ?? 0} overdue`, spark: sp.outstanding, color: 'var(--aeos-primary)' },
    { label: 'Overdue', value: fmtMoney(k.overdue_amt), delta: `${k.overdue ?? 0} invoices past due`, cls: (k.overdue ?? 0) > 0 ? 'down' : 'up', spark: sp.overdue, color: 'var(--aeos-danger)' },
    { label: 'MRR churn (30d)', value: `${churn}%`, delta: churn <= 2 ? 'healthy — low churn' : 'watch closely', cls: churn <= 2 ? 'up' : 'warn', spark: sp.churn, color: churn <= 2 ? 'var(--aeos-success)' : 'var(--aeos-warning)' },
  ];

  const mv = {
    labels: movement.labels ?? [],
    new: movement.new ?? [],
    expansion: movement.expansion ?? [],
    contraction: movement.contraction ?? [],
    churn: movement.churn ?? [],
  };
  const net = mv.labels.map((_, i) => (mv.new[i] ?? 0) + (mv.expansion[i] ?? 0) - (mv.contraction[i] ?? 0) - (mv.churn[i] ?? 0));
  const netNow = net.length ? net[net.length - 1] : 0;

  const donutStatuses = [
    ['active', 'Active', 'var(--aeos-success)'],
    ['trialing', 'Trialing', 'var(--aeos-primary)'],
    ['past_due', 'Past due', 'var(--aeos-warning)'],
    ['incomplete', 'Incomplete', 'var(--aeos-text-muted)'],
    ['cancelled', 'Cancelled', 'var(--aeos-danger)'],
  ];

  const exportSummary = () => {
    const rows = [
      ['metric', 'value'],
      ['MRR', k.mrr], ['MRR delta %', k.mrr_delta_pct], ['ARR', k.arr],
      ['Plan MRR', k.plan_mrr], ['Product MRR', k.product_mrr],
      ['Active subs', k.active], ['Trialing', k.trialing],
      ['Collected', k.collected], ['Invoices paid', k.paid], ['Collected rate %', k.paid_rate],
      ['Outstanding', k.outstanding], ['Open invoices', k.open],
      ['Overdue amount', k.overdue_amt], ['Overdue invoices', k.overdue],
      ['Dunning amount', k.dunning_amount], ['Dunning count', k.dunning_count],
      ['MRR churn %', k.churn_pct], ['Avg days to pay', k.avg_days_to_pay],
      ['Renewals ≤30d', (q.renewals ?? []).length], ['Trials ending', (q.trials ?? []).length],
    ];
    const csv = rows.map((r) => r.map((v) => `"${String(v ?? '').replaceAll('"', '""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    a.download = `billing-summary-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="pc bh">
      {/* masthead */}
      <div className="pc-head">
        <div>
          <div className="pc-eyebrow"><span className="pc-eyebrow__dot" /> Billing · Command center</div>
          <h1 className="pc-title">Billing</h1>
          <div className="pc-sub">The whole revenue vertical at a glance — recurring revenue and run-rate, collections, what needs chasing today, and where every dollar is moving. Drill into Subscriptions or Invoices for the full workbench.</div>
        </div>
        <div className="pc-actions">
          <button
            type="button"
            className="pc-btn"
            onClick={(e) => ctx.open(e.currentTarget, [
              { label: 'Export CSV — billing summary', onClick: exportSummary },
              { label: 'Print this view', onClick: () => window.print() },
            ])}
          >
            {Glyph.export}<span>Export</span>
          </button>
          <button type="button" className="pc-btn pc-btn--primary" onClick={goSubs}>Open Subscriptions →</button>
        </div>
      </div>

      {/* KPI band */}
      <div className="pc-kpis sc-kpis6">
        {kpis.map((c) => (
          <Card key={c.label}><CardBody>
            <div className="pc-kpi">
              <div className="pc-kpi__label">{c.label}</div>
              <div className="pc-kpi__value">{c.value}</div>
              <div className={`pc-kpi__delta${c.cls === 'up' ? ' pc-kpi__delta--up' : ''}${c.cls === 'down' ? ' pc-kpi__delta--down' : ''}${c.cls === 'warn' ? ' pc-kpi__delta--warn' : ''}`}>{c.delta}</div>
              {Array.isArray(c.spark) && c.spark.length > 1 && (
                <div className="sc-kpi-spark"><AreaSpark data={c.spark} color={c.color} /></div>
              )}
            </div>
          </CardBody></Card>
        ))}
      </div>

      {/* revenue command row */}
      <div className="sc-band">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">MRR movement</h2><div className="pc-panel-h__sub">New &amp; expansion vs contraction &amp; churn — 6 months</div></div>
            <span className={`sc-badge ${netNow >= 0 ? 'sc-badge--ok' : 'sc-badge--bad'}`}>Net {netNow >= 0 ? '+' : '−'}{fmtK(Math.abs(netNow))} this month</span>
          </div>
          <BarsDiverging
            labels={mv.labels}
            up={[
              { key: 'new', label: 'New', color: 'var(--aeos-success)', values: mv.new },
              { key: 'exp', label: 'Expansion', color: 'var(--aeos-primary)', values: mv.expansion },
            ]}
            down={[
              { key: 'con', label: 'Contraction', color: 'var(--aeos-warning)', values: mv.contraction },
              { key: 'churn', label: 'Churn', color: 'var(--aeos-danger)', values: mv.churn },
            ]}
            net={{ color: 'var(--aeos-text-primary)', values: net }}
            format={(v) => fmtK(v)}
            height={230}
            ariaLabel="MRR movement by month"
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-new" />New</span>
            <span className="li"><span className="d sc-d-exp" />Expansion</span>
            <span className="li"><span className="d sc-d-con" />Contraction</span>
            <span className="li"><span className="d sc-d-churn" />Churn</span>
            <span className="li"><span className="d d--dot sc-d-net" />Net</span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Collections trend</h2><div className="pc-panel-h__sub">Billed vs collected — last 6 months</div></div>
          </div>
          <AreaTrend
            series={[
              { key: 'billed', label: 'Billed', color: 'var(--aeos-primary)', values: trend.billed ?? [] },
              { key: 'collected', label: 'Collected', color: 'var(--aeos-success)', fill: false, values: trend.collected ?? [] },
            ]}
            labels={trend.labels ?? []}
            height={230}
            ariaLabel="Collections trend, billed versus collected"
            empty="No collections history yet."
          />
          <div className="sc-dl sc-dl--row">
            <span className="li"><span className="d sc-d-exp" />Billed <b>{fmtK((trend.billed ?? []).at(-1) ?? 0)}</b></span>
            <span className="li"><span className="d sc-d-new" />Collected <b>{fmtK((trend.collected ?? []).at(-1) ?? 0)}</b></span>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Subscription health</h2><div className="pc-panel-h__sub">{health.total ?? 0} records — open in Subscriptions</div></div>
          </div>
          <div className="sc-donut-row">
            <Donut
              segments={donutStatuses.map(([key, , color]) => ({ color, value: health[key] ?? 0 }))}
              centerValue={`${health.active_pct ?? 0}%`}
              centerLabel="active"
              size={116}
            />
            <div className="sc-dl">
              {donutStatuses.map(([key, label, color]) => (
                <button key={key} type="button" className="li" onClick={goSubs}>
                  <span className="d" style={{ background: color }} />{label}<b>{health[key] ?? 0}</b>
                </button>
              ))}
            </div>
          </div>
        </CardBody></Card>
      </div>

      {/* action queues (4-up) */}
      <div className="bh-queues4">
        {/* Renewals */}
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Renewals</h2><div className="pc-panel-h__sub">Next 30 days</div></div>
            <span className="sc-badge sc-badge--ok">{(q.renewals ?? []).length}</span>
          </div>
          {(q.renewals ?? []).length === 0 && <div className="wb-empty">No renewals inside 30 days.</div>}
          {(q.renewals ?? []).slice(0, 4).map((r) => (
            <div key={`${r.kind}-${r.id}`} className="sc-qitem">
              <div className="sc-av">{initials(r.tenant)}</div>
              <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label} · {r.billing_cycle}</span></div>
              <span className="sc-qitem__amt">{fmtMoney(r.amount)}</span>
              <span className="sc-qitem__when">{fmtDateShort(r.renews_at)}</span>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="bh-deep" onClick={goSubs}>Open in Subscriptions →</button></div>
        </CardBody></Card>

        {/* Trials ending */}
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Trials ending</h2><div className="pc-panel-h__sub">Convert before access lapses</div></div>
            <span className={`sc-badge ${(q.trials ?? []).length ? 'sc-badge--warn' : 'sc-badge--ok'}`}>{(q.trials ?? []).length}</span>
          </div>
          {(q.trials ?? []).length === 0 && <div className="wb-empty">No running trials.</div>}
          {(q.trials ?? []).slice(0, 3).map((r) => (
            <div key={`${r.kind}-${r.id}`}>
              <div className="sc-qitem">
                <div className="sc-av">{initials(r.tenant)}</div>
                <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label}</span></div>
                <span className="sc-qitem__when sc-qitem__when--soon">{r.days_left}d left</span>
              </div>
              {r.kind === 'plan' && (
                <div className="bh-qacts">
                  <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => confirmPost(`Convert ${r.tenant}'s trial to paid now?`, `/billing/subscriptions/${r.id}/trial/convert`)}>Convert</button>
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/trial/extend`, { days: 7 })}>+7d</button>
                </div>
              )}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="bh-deep" onClick={goSubs}>Open in Subscriptions →</button></div>
        </CardBody></Card>

        {/* Dunning */}
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Dunning</h2><div className="pc-panel-h__sub">Failed payments to recover</div></div>
            <span className={`sc-badge ${(q.dunning ?? []).length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{fmtMoney(k.dunning_amount)}</span>
          </div>
          {(q.dunning ?? []).length === 0 && <div className="wb-empty">Nothing in dunning — payments healthy.</div>}
          {(q.dunning ?? []).slice(0, 3).map((r) => (
            <div key={`${r.kind}-${r.id}`}>
              <div className="sc-qitem">
                <div className="sc-av">{initials(r.tenant)}</div>
                <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.label}</span></div>
                <span className="sc-qitem__amt">{fmtMoney(r.amount)}</span>
                <span className="sc-qitem__when sc-qitem__when--due">{r.days_overdue}d</span>
              </div>
              {r.kind === 'plan' && (
                <div className="bh-qacts">
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/retry-charge`)}>Retry</button>
                  <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/subscriptions/${r.id}/remind`)}>Remind</button>
                </div>
              )}
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="bh-deep" onClick={goSubs}>Open in Subscriptions →</button></div>
        </CardBody></Card>

        {/* Overdue invoices */}
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Overdue invoices</h2><div className="pc-panel-h__sub">Past due — chase now</div></div>
            <span className={`sc-badge ${(q.overdue ?? []).length ? 'sc-badge--bad' : 'sc-badge--ok'}`}>{fmtMoney(k.overdue_amt)}</span>
          </div>
          {(q.overdue ?? []).length === 0 && <div className="wb-empty">Nothing overdue — collections healthy.</div>}
          {(q.overdue ?? []).slice(0, 3).map((r) => (
            <div key={r.id}>
              <div className="sc-qitem">
                <div className="sc-av">{initials(r.tenant)}</div>
                <div className="sc-qitem__who"><b>{r.tenant}</b><span>{r.number}</span></div>
                <span className="sc-qitem__amt">{fmtMoney(r.amount)}</span>
                <span className="sc-qitem__when sc-qitem__when--due">{r.days_overdue}d</span>
              </div>
              <div className="bh-qacts">
                <button type="button" className="pc-btn pc-btn--sm pc-btn--primary" onClick={() => confirmPost(`Mark ${r.number} as paid?`, `/billing/invoices/${r.id}/mark-paid`)}>Mark paid</button>
                <button type="button" className="pc-btn pc-btn--sm" onClick={() => post(`/billing/invoices/${r.id}/remind`)}>Remind</button>
              </div>
            </div>
          ))}
          <div className="sc-qfoot"><button type="button" className="bh-deep" onClick={goInvoices}>Open in Invoices →</button></div>
        </CardBody></Card>
      </div>

      {/* activity + gateways */}
      <div className="bh-bottom">
        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Recent billing activity</h2><div className="pc-panel-h__sub">Lifecycle events, payments &amp; refunds across the platform</div></div>
          </div>
          {activity.length === 0
            ? <div className="wb-empty">No billing activity recorded yet.</div>
            : (
              <ul className="sc-tl bh-tl">
                {activity.map((a, i) => (
                  <li key={i} className={`bh-act--${a.kind}`}>
                    <b>{a.tenant}</b> {a.message}
                    {a.amount != null && a.amount !== 0 && (
                      <span className={`bh-amt ${a.amount < 0 ? 'bh-amt--neg' : 'bh-amt--pos'}`}>{a.amount < 0 ? '−' : '+'}{fmtCur(Math.abs(a.amount), a.currency)}</span>
                    )}
                    <span className="when">{fmtDateShort(a.at)}{a.note ? ` · ${a.note}` : ''}</span>
                  </li>
                ))}
              </ul>
            )}
        </CardBody></Card>

        <Card><CardBody>
          <div className="pc-panel-h">
            <div><h2 className="pc-panel-h__title">Payment gateways</h2><div className="pc-panel-h__sub">{gateways.filter((g) => g.enabled).length} of {gateways.length} live</div></div>
            <button type="button" className="bh-deep" onClick={() => router.visit('/billing/gateways')}>Configure →</button>
          </div>
          <div className="bh-gw">
            {gateways.map((g) => (
              <div className="bh-gcard" key={g.code}>
                <div className="sc-av">{initials(g.label)}</div>
                <div className="bh-gn"><b>{g.label}</b><span>{g.code}</span></div>
                {g.isDefault && <span className="bh-gchip">Default</span>}
                <span className={`bh-gdot bh-gdot--${g.enabled ? 'live' : 'off'}`} aria-hidden="true" />
              </div>
            ))}
          </div>
          <div className="bh-mix">
            <div className="bh-mix__lab">How collected revenue settled</div>
            <div className="bh-mixbar">
              {methodMix.map((m, i) => (
                <span key={m.method} className={`bh-mix-c${i % 4}`} style={{ width: `${m.pct}%` }} title={`${m.method} · ${m.count}`} />
              ))}
            </div>
            <div className="sc-dl sc-dl--row">
              {methodMix.map((m, i) => (
                <span key={m.method} className="li"><span className={`d bh-mix-c${i % 4}`} />{m.method}<b>{m.count} · {fmtK(m.amount)}</b></span>
              ))}
              {methodMix.length <= 1 && <span className="li" style={{ color: 'var(--aeos-text-muted)' }}>Other methods — none yet</span>}
            </div>
          </div>
        </CardBody></Card>
      </div>

      {ctx.element}
    </div>
  );
}

Dashboard.layout = (page) => (
  <App title="Billing" railTitle="Billing" rail={<BillingRail kpis={page.props.overview?.kpis} />}>
    {page}
  </App>
);
