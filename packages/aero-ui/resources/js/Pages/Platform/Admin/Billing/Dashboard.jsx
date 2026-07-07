import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardBody, Button, Badge, Avatar, DataTable, Text, AreaTrend, AreaSpark, Donut } from '@aero/ui';

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

export default function BillingDashboard({ overview }) {
  const o = overview ?? {};
  const h = o.heroes ?? {};
  const life = o.lifecycle ?? {};
  const inv = o.invoices ?? {};
  const trend = o.revenueTrend ?? [];
  const needs = o.needsAttention ?? [];
  const gateways = o.gateways ?? [];
  const recent = o.recent ?? [];

  // Refresh the whole overview (the only prop the page reads) so a poll can never
  // leave the page data-less.
  const poll = useCallback(() => {
    router.reload({ only: ['overview'], preserveScroll: true, preserveState: true });
  }, []);
  usePolling(poll, 30000);

  const spark = trend.map((t) => Number(t.mrr) || 0);
  const trendSeries = [
    { key: 'total', label: 'Total MRR', color: 'var(--aeos-primary)', values: trend.map((t) => Number(t.mrr) || 0) },
    { key: 'product', label: 'Product add-ons', color: 'var(--aeos-success)', fill: false, values: trend.map((t) => Number(t.product) || 0) },
  ];
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
          <AreaSpark data={spark} color="var(--aeos-primary)" className="bill-hero-spark" />
        </div>
        <div className="bill-hero">
          <div className="bill-hero-lab">Annual run-rate</div>
          <div className="bill-hero-val">{moneyK(h.arr)}</div>
          <div className="bill-hero-foot"><span className="bill-delta bill-delta--flat">MRR × 12</span></div>
          <AreaSpark data={spark} color="var(--aeos-success)" className="bill-hero-spark" />
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
          <AreaTrend series={trendSeries} labels={trend.map((t) => t.month)} height={150} ariaLabel="Revenue trend" empty="No revenue history yet." />
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
            <Donut
              segments={[
                { color: 'var(--aeos-success)', value: inv.paid?.count ?? 0 },
                { color: 'var(--aeos-warning)', value: inv.open?.count ?? 0 },
                { color: 'var(--aeos-danger)',  value: inv.overdue?.count ?? 0 },
              ]}
              centerValue={`${inv.collectedPct ?? 0}%`}
              centerLabel="collected"
            />
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
