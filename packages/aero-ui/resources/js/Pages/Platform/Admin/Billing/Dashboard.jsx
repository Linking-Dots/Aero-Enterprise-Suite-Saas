import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody, Button, Badge, Avatar, DataTable, Text } from '@aero/ui';

import App from '@/Pages/App.jsx';
import { usePolling } from '../Dashboard/lib.jsx';
import '../Dashboard/dashboard.css';
import BillingRail from './BillingRail.jsx';

const INV = {
  paid:     { intent: 'success', label: 'Paid' },
  issued:   { intent: 'warning', label: 'Open' },
  overdue:  { intent: 'danger',  label: 'Overdue' },
  draft:    { intent: 'neutral', label: 'Draft' },
  void:     { intent: 'neutral', label: 'Void' },
  refunded: { intent: 'indigo',  label: 'Refunded' },
};
const money  = (v) => `$${Number(v ?? 0).toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
const moneyK = (v) => {
  const n = Number(v ?? 0);
  return n >= 1000 ? `$${(n / 1000).toLocaleString(undefined, { maximumFractionDigits: 1 })}k` : money(n);
};

/* Area sparkline for a hero card. */
function AreaSpark({ data = [], stroke = 'var(--aeos-primary)' }) {
  if (data.length < 2) return null;
  const w = 120, hh = 38, max = Math.max(...data), min = Math.min(...data);
  const pts = data.map((p, i) => [i / (data.length - 1) * w, hh - 3 - ((p - min) / ((max - min) || 1)) * (hh - 8)]);
  const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
  const id = 'bs' + Math.random().toString(36).slice(2, 7);
  return (
    <svg className="bill-hero-spark" viewBox={`0 0 ${w} ${hh}`} preserveAspectRatio="none" aria-hidden="true">
      <defs><linearGradient id={id} x1="0" x2="0" y1="0" y2="1">
        <stop offset="0" stopColor={stroke} stopOpacity="0.45" /><stop offset="1" stopColor={stroke} stopOpacity="0" />
      </linearGradient></defs>
      <path d={`${line} L${w} ${hh} L0 ${hh} Z`} fill={`url(#${id})`} />
      <path d={line} fill="none" stroke={stroke} strokeWidth="1.5" />
    </svg>
  );
}

/* Big revenue area chart (total MRR + product add-ons). */
function TrendChart({ trend }) {
  if (!trend || trend.length < 2) {
    return <Text size="sm" tone="tertiary">No revenue history yet.</Text>;
  }
  const W = 560, H = 150, pad = 26;
  const total = trend.map((t) => Number(t.mrr) || 0);
  const product = trend.map((t) => Number(t.product) || 0);
  const max = Math.max(...total, 1) * 1.14;
  const x = (i) => pad + i / (trend.length - 1) * (W - pad - 12);
  const y = (v) => H - 24 - (v / max) * (H - 42);
  const path = (arr) => arr.map((v, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(v).toFixed(1)).join(' ');
  const area = (arr) => `${path(arr)} L${x(trend.length - 1)} ${H - 24} L${x(0)} ${H - 24} Z`;
  return (
    <div className="bill-chart">
      <svg viewBox={`0 0 ${W} ${H}`} aria-hidden="true">
        <defs><linearGradient id="btA" x1="0" x2="0" y1="0" y2="1">
          <stop offset="0" stopColor="var(--aeos-primary)" stopOpacity="0.3" /><stop offset="1" stopColor="var(--aeos-primary)" stopOpacity="0" />
        </linearGradient></defs>
        {[0, 1, 2, 3].map((g) => <line key={g} x1={pad} x2={W - 12} y1={24 + g / 3 * (H - 48)} y2={24 + g / 3 * (H - 48)} stroke="var(--aeos-divider)" strokeWidth="1" />)}
        <path d={area(total)} fill="url(#btA)" />
        <path d={path(total)} fill="none" stroke="var(--aeos-primary)" strokeWidth="2" />
        <path d={path(product)} fill="none" stroke="var(--aeos-success)" strokeWidth="1.6" />
        <circle cx={x(trend.length - 1)} cy={y(total[total.length - 1])} r="3.5" fill="var(--aeos-primary)" stroke="var(--aeos-bg-card)" strokeWidth="2" />
        {trend.map((t, i) => <text key={i} x={x(i)} y={H - 6} fill="var(--aeos-text-tertiary)" fontSize="10" textAnchor="middle">{t.month}</text>)}
      </svg>
    </div>
  );
}

/* Invoice collections donut. */
function Donut({ inv }) {
  const total = inv.total || 1;
  const seg = [['var(--aeos-success)', inv.paid?.count ?? 0], ['var(--aeos-warning)', inv.open?.count ?? 0], ['var(--aeos-danger)', inv.overdue?.count ?? 0]];
  const r = 52, c = 2 * Math.PI * r; let off = 0;
  return (
    <div className="bill-donut">
      <svg viewBox="0 0 118 118" aria-hidden="true">
        <circle cx="59" cy="59" r={r} fill="none" stroke="var(--aeos-bg-active)" strokeWidth="13" />
        {seg.map(([col, v], i) => {
          const len = (v / total) * c;
          const el = <circle key={i} cx="59" cy="59" r={r} fill="none" stroke={col} strokeWidth="13"
            strokeDasharray={`${len.toFixed(1)} ${(c - len).toFixed(1)}`} strokeDashoffset={(-off).toFixed(1)}
            transform="rotate(-90 59 59)" />;
          off += len; return el;
        })}
      </svg>
      <div className="bill-donut-mid"><b>{inv.collectedPct ?? 0}%</b><small>collected</small></div>
    </div>
  );
}

export default function BillingDashboard({ overview }) {
  const o = overview ?? {};
  const h = o.heroes ?? {};
  const life = o.lifecycle ?? {};
  const inv = o.invoices ?? {};
  const trend = o.revenueTrend ?? [];
  const needs = o.needsAttention ?? [];
  const gateways = o.gateways ?? [];
  const recent = o.recent ?? [];

  const poll = useCallback(() => {
    router.reload({ only: ['live'], preserveScroll: true, preserveState: true });
  }, []);
  usePolling(poll, 30000);

  const spark = trend.map((t) => Number(t.mrr) || 0);
  const deltaUp = (h.mrrDeltaPct ?? 0) >= 0;
  const lifeMax = life.total || 1;
  const pct = (v) => `${Math.max(8, Math.round(((v ?? 0) / lifeMax) * 100))}%`;

  const recentColumns = [
    { key: 'tenant', label: 'Tenant', render: (r) => (
      <div className="bill-tcell"><Avatar name={r.tenant || '—'} size={24} /><Text size="sm">{r.tenant || '—'}</Text></div>) },
    { key: 'invoice', label: 'Invoice', render: (r) => <Text size="sm" tone="secondary">{r.invoice}</Text> },
    { key: 'amount', label: 'Amount', align: 'right', render: (r) => <Text size="sm">{money(r.amount)}</Text> },
    { key: 'status', label: 'Status', render: (r) => {
      const s = INV[r.status] ?? { intent: 'neutral', label: r.status };
      return <Badge intent={s.intent} dot>{s.label}</Badge>;
    } },
    { key: 'date', label: 'Date', render: (r) => <Text size="sm" tone="tertiary">{r.date ?? '—'}</Text> },
  ];

  return (
    <div className="lcc">
      {/* Revenue heroes */}
      <div className="bill-heroes">
        <div className="bill-hero bill-hero--accent">
          <div className="bill-hero-lab">Monthly recurring revenue</div>
          <div className="bill-hero-val">{moneyK(h.mrr)}</div>
          <div className="bill-hero-foot">
            <span className={`bill-delta bill-delta--${deltaUp ? 'up' : 'down'}`}>{deltaUp ? '↑' : '↓'} {Math.abs(h.mrrDeltaPct ?? 0)}%</span>
            <span>vs last month</span>
          </div>
          <AreaSpark data={spark} stroke="var(--aeos-primary)" />
        </div>
        <div className="bill-hero">
          <div className="bill-hero-lab">Annual run-rate</div>
          <div className="bill-hero-val">{moneyK(h.arr)}</div>
          <div className="bill-hero-foot"><span className="bill-delta bill-delta--flat">MRR × 12</span></div>
          <AreaSpark data={spark} stroke="var(--aeos-success)" />
        </div>
        <div className="bill-hero">
          <div className="bill-hero-lab">Active subscriptions</div>
          <div className="bill-hero-val">{h.activeSubs ?? 0}</div>
          <div className="bill-hero-foot"><span className="bill-delta bill-delta--flat">{h.trialingSubs ?? 0} trialing</span><span>{h.cancelledSubs ?? 0} cancelled</span></div>
        </div>
        <div className="bill-hero bill-hero--warn">
          <div className="bill-hero-lab">Overdue invoices {(h.overdueCount ?? 0) > 0 && <span className="bill-badge-warn">NEEDS ATTENTION</span>}</div>
          <div className="bill-hero-val">{h.overdueCount ?? 0}</div>
          <div className="bill-hero-foot"><span className="bill-delta bill-delta--down">{money(h.overdueAmount)} at risk</span><span>+{h.openCount ?? 0} open</span></div>
        </div>
      </div>

      {/* Revenue trend + subscription lifecycle */}
      <div className="lcc-row split-60">
        <Card><CardBody>
          <div className="lcc-card-h">
            <span className="lcc-card-h__title">Revenue trend</span>
            <Text size="lg" weight={700}>{moneyK(h.mrr)}</Text>
          </div>
          <TrendChart trend={trend} />
          <div className="bill-legend"><span><i style={{ background: 'var(--aeos-primary)' }} />Total MRR</span><span><i style={{ background: 'var(--aeos-success)' }} />Product add-ons</span></div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="lcc-card-h"><span className="lcc-card-h__title">Subscription lifecycle</span></div>
          <div className="bill-funnel">
            <div className="bill-frow"><div className="fl">Active</div><div className="bill-fbar bill-fbar--active"><span style={{ width: pct(life.active) }}>{life.active ?? 0}</span></div></div>
            <div className="bill-frow"><div className="fl">Trialing</div><div className="bill-fbar bill-fbar--trial"><span style={{ width: pct(life.trialing) }}>{life.trialing ?? 0}</span></div></div>
            <div className="bill-frow"><div className="fl">Cancelled</div><div className="bill-fbar bill-fbar--cancel"><span style={{ width: pct(life.cancelled) }}>{life.cancelled ?? 0}</span></div></div>
          </div>
          {life.churnPct != null && <Text size="xs" tone="tertiary">Churn (30d) · <Text as="span" size="xs" weight={600}>{life.churnPct}%</Text></Text>}
        </CardBody></Card>
      </div>

      {/* Invoice collections + needs attention */}
      <div className="lcc-row split-50">
        <Card><CardBody>
          <div className="lcc-card-h">
            <span className="lcc-card-h__title">Invoice collections</span>
            <Badge intent="success">{inv.collectedPct ?? 0}% collected</Badge>
          </div>
          <div className="bill-donut-wrap">
            <Donut inv={inv} />
            <div className="bill-ilist">
              <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-success)' }} />Paid</span><b>{inv.paid?.count ?? 0} · {moneyK(inv.paid?.amount)}</b></div>
              <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-warning)' }} />Open</span><b>{inv.open?.count ?? 0} · {moneyK(inv.open?.amount)}</b></div>
              <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-danger)' }} />Overdue</span><b>{inv.overdue?.count ?? 0} · {moneyK(inv.overdue?.amount)}</b></div>
            </div>
          </div>
        </CardBody></Card>

        <Card><CardBody>
          <div className="lcc-card-h">
            <span className="lcc-card-h__title">Needs attention</span>
            {(h.overdueCount ?? 0) > 0 && <Badge intent="danger" dot>{h.overdueCount} overdue</Badge>}
          </div>
          {needs.length === 0
            ? <Text size="sm" tone="tertiary">Nothing overdue — all invoices are on track.</Text>
            : (
              <div className="bill-risk">
                {needs.map((n) => (
                  <div className="bill-rrow" key={n.invoice}>
                    <Avatar name={n.tenant} size={28} />
                    <div className="bill-rn">
                      <Text size="sm" weight={600}>{n.tenant}</Text>
                      <Text size="xs" tone="tertiary">{n.invoice}{n.daysOverdue != null ? ` · ${n.daysOverdue} days overdue` : ''}</Text>
                    </div>
                    <Text size="sm" weight={600}>{money(n.amount)}</Text>
                  </div>
                ))}
              </div>
            )}
        </CardBody></Card>
      </div>

      {/* Payment gateways (full width) */}
      <Card><CardBody>
        <div className="lcc-card-h"><span className="lcc-card-h__title">Payment gateways</span>
          <Text size="xs" tone="tertiary">{gateways.length} configured</Text>
        </div>
        <div className="bill-gw">
          {gateways.map((g) => (
            <div className="bill-gcard" key={g.code}>
              <Avatar name={g.label} size={30} />
              <div className="bill-rn">
                <Text size="sm" weight={600}>{g.label}</Text>
                <Text size="xs" tone="tertiary">{g.isDefault ? 'Default' : g.code}</Text>
              </div>
              <span className={`bill-gdot bill-gdot--${g.enabled ? 'live' : 'off'}`} aria-hidden="true" />
            </div>
          ))}
        </div>
      </CardBody></Card>

      {/* Recent transactions (full width) */}
      <Card><CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Recent transactions</span>
          <Button intent="ghost" size="sm" onClick={() => router.visit(route('platform.admin.billing.invoices.index'))}>All invoices</Button>
        </div>
        <div className="aeos-table-wrap">
          <DataTable columns={recentColumns} rows={recent} empty="No transactions yet." />
        </div>
      </CardBody></Card>
    </div>
  );
}

BillingDashboard.layout = (page) => (
  <App title="Billing" railTitle="Billing" rail={<BillingRail />}>{page}</App>
);
