import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  DashboardLayout,
  DataTable,
  Button,
  Badge,
  HStack, VStack, Box,
  Text,
  Card, CardBody,
  Avatar,
  EmptyState,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import BillingRail from './BillingRail.jsx';

/* Invoice status → Badge intent + label (real enum: paid/issued/overdue/draft/void/refunded). */
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

/* Area sparkline for a hero card (SVG geometry, theme-token stroke). */
function AreaSpark({ data = [], stroke = 'var(--aeos-primary)' }) {
  if (data.length < 2) return null;
  const w = 120, h = 38, max = Math.max(...data), min = Math.min(...data);
  const pts = data.map((p, i) => [i / (data.length - 1) * w, h - 3 - ((p - min) / ((max - min) || 1)) * (h - 8)]);
  const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
  const id = 'bs' + Math.random().toString(36).slice(2, 7);
  return (
    <svg className="bill-hero-spark" viewBox={`0 0 ${w} ${h}`} preserveAspectRatio="none" aria-hidden="true">
      <defs><linearGradient id={id} x1="0" x2="0" y1="0" y2="1">
        <stop offset="0" stopColor={stroke} stopOpacity="0.45" /><stop offset="1" stopColor={stroke} stopOpacity="0" />
      </linearGradient></defs>
      <path d={`${line} L${w} ${h} L0 ${h} Z`} fill={`url(#${id})`} />
      <path d={line} fill="none" stroke={stroke} strokeWidth="1.5" />
    </svg>
  );
}

/* Big revenue area chart: total MRR + product add-ons over the returned months. */
function TrendChart({ trend }) {
  if (!trend || trend.length < 2) return <EmptyState icon="chartBar" title="No revenue history yet" />;
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
        <defs>
          <linearGradient id="btA" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stopColor="var(--aeos-primary)" stopOpacity="0.3" /><stop offset="1" stopColor="var(--aeos-primary)" stopOpacity="0" /></linearGradient>
        </defs>
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

/* Invoice collections donut (paid / open / overdue). */
function Donut({ inv }) {
  const total = inv.total || 1;
  const seg = [['var(--aeos-success)', inv.paid?.count ?? 0], ['var(--aeos-warning)', inv.open?.count ?? 0], ['var(--aeos-danger)', inv.overdue?.count ?? 0]];
  const r = 52, c = 2 * Math.PI * r;
  let off = 0;
  return (
    <div className="bill-donut">
      <svg viewBox="0 0 118 118" aria-hidden="true">
        <circle cx="59" cy="59" r={r} fill="none" stroke="var(--aeos-bg-active)" strokeWidth="13" />
        {seg.map(([col, v], i) => {
          const len = (v / total) * c;
          const el = <circle key={i} cx="59" cy="59" r={r} fill="none" stroke={col} strokeWidth="13"
            strokeDasharray={`${len.toFixed(1)} ${(c - len).toFixed(1)}`} strokeDashoffset={(-off).toFixed(1)}
            transform="rotate(-90 59 59)" />;
          off += len;
          return el;
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

  useEffect(() => {
    const id = setInterval(() => {
      router.reload({ only: ['live'], preserveScroll: true, preserveState: true });
    }, 30000);
    return () => clearInterval(id);
  }, []);

  const spark = trend.map((t) => Number(t.mrr) || 0);
  const deltaUp = (h.mrrDeltaPct ?? 0) >= 0;
  const lifeMax = life.total || 1;
  const pct = (v) => `${Math.max(8, Math.round(((v ?? 0) / lifeMax) * 100))}%`;

  const recentColumns = [
    {
      key: 'tenant', label: 'Tenant',
      render: (r) => (
        <HStack gap={2} align="center">
          <Avatar name={r.tenant || '—'} size={24} />
          <Text size="sm">{r.tenant || '—'}</Text>
        </HStack>
      ),
    },
    { key: 'invoice', label: 'Invoice', render: (r) => <Text size="sm" tone="secondary">{r.invoice}</Text> },
    { key: 'amount', label: 'Amount', align: 'right', render: (r) => <Text size="sm">{money(r.amount)}</Text> },
    {
      key: 'status', label: 'Status',
      render: (r) => {
        const s = INV[r.status] ?? { intent: 'neutral', label: r.status };
        return <Badge intent={s.intent} dot>{s.label}</Badge>;
      },
    },
    { key: 'date', label: 'Date', render: (r) => <Text size="sm" tone="tertiary">{r.date ?? '—'}</Text> },
  ];

  return (
    <DashboardLayout
      title="Billing"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Billing' },
      ]}
      description="Recurring revenue, collections, and payment health at a glance."
      actions={
        <Button intent="primary" onClick={() => router.visit(route('platform.admin.billing.invoices.index'))}>
          View invoices
        </Button>
      }
    >
      <VStack gap={5}>

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
        <div className="bill-2col">
          <Card><CardBody><VStack gap={3}>
            <HStack justify="between" align="center">
              <VStack gap={0}><Text weight={600}>Revenue trend</Text><Text size="xs" tone="tertiary">Recurring revenue · last {trend.length} months</Text></VStack>
              <Text size="lg" weight={700}>{moneyK(h.mrr)}</Text>
            </HStack>
            <TrendChart trend={trend} />
            <div className="bill-legend"><span><i style={{ background: 'var(--aeos-primary)' }} />Total MRR</span><span><i style={{ background: 'var(--aeos-success)' }} />Product add-ons</span></div>
          </VStack></CardBody></Card>

          <Card><CardBody><VStack gap={3}>
            <VStack gap={0}><Text weight={600}>Subscription lifecycle</Text><Text size="xs" tone="tertiary">Where accounts sit today</Text></VStack>
            <div className="bill-funnel">
              <div className="bill-frow"><div className="fl">Active</div><div className="bill-fbar bill-fbar--active"><span style={{ width: pct(life.active) }}>{life.active ?? 0}</span></div></div>
              <div className="bill-frow"><div className="fl">Trialing</div><div className="bill-fbar bill-fbar--trial"><span style={{ width: pct(life.trialing) }}>{life.trialing ?? 0}</span></div></div>
              <div className="bill-frow"><div className="fl">Cancelled</div><div className="bill-fbar bill-fbar--cancel"><span style={{ width: pct(life.cancelled) }}>{life.cancelled ?? 0}</span></div></div>
            </div>
            {life.churnPct != null && <Text size="xs" tone="tertiary">Churn (30d) · <Text as="span" size="xs" weight={600}>{life.churnPct}%</Text></Text>}
          </VStack></CardBody></Card>
        </div>

        {/* Invoice collections + needs attention */}
        <div className="bill-2col">
          <Card><CardBody><VStack gap={3}>
            <HStack justify="between" align="center">
              <VStack gap={0}><Text weight={600}>Invoice collections</Text><Text size="xs" tone="tertiary">{inv.total ?? 0} invoices this cycle</Text></VStack>
              <Badge intent="success">{inv.collectedPct ?? 0}% collected</Badge>
            </HStack>
            <div className="bill-donut-wrap">
              <Donut inv={inv} />
              <div className="bill-ilist">
                <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-success)' }} />Paid</span><b>{inv.paid?.count ?? 0} · {moneyK(inv.paid?.amount)}</b></div>
                <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-warning)' }} />Open</span><b>{inv.open?.count ?? 0} · {moneyK(inv.open?.amount)}</b></div>
                <div className="bill-irow"><span className="k"><i style={{ background: 'var(--aeos-danger)' }} />Overdue</span><b>{inv.overdue?.count ?? 0} · {moneyK(inv.overdue?.amount)}</b></div>
              </div>
            </div>
          </VStack></CardBody></Card>

          <Card><CardBody><VStack gap={3}>
            <HStack justify="between" align="center">
              <VStack gap={0}><Text weight={600}>Needs attention</Text><Text size="xs" tone="tertiary">Overdue · oldest first</Text></VStack>
              {(h.overdueCount ?? 0) > 0 && <Badge intent="danger" dot>{h.overdueCount} overdue</Badge>}
            </HStack>
            {needs.length === 0
              ? <EmptyState icon="checkCircle" title="Nothing overdue" description="All invoices are on track." />
              : (
                <div className="bill-risk">
                  {needs.map((n) => (
                    <div className="bill-rrow" key={n.invoice}>
                      <Avatar name={n.tenant} size={28} />
                      <Box grow>
                        <Text size="sm" weight={600}>{n.tenant}</Text>
                        <Text size="xs" tone="tertiary">{n.invoice}{n.daysOverdue != null ? ` · ${n.daysOverdue} days overdue` : ''}</Text>
                      </Box>
                      <Text size="sm" weight={600}>{money(n.amount)}</Text>
                    </div>
                  ))}
                </div>
              )}
          </VStack></CardBody></Card>
        </div>

        {/* Payment gateways */}
        <Card><CardBody><VStack gap={3}>
          <VStack gap={0}><Text weight={600}>Payment gateways</Text><Text size="xs" tone="tertiary">{gateways.length} configured</Text></VStack>
          <div className="bill-gw">
            {gateways.map((g) => (
              <div className="bill-gcard" key={g.code}>
                <Avatar name={g.label} size={30} />
                <Box grow>
                  <Text size="sm" weight={600}>{g.label}</Text>
                  <Text size="xs" tone="tertiary">{g.isDefault ? 'Default' : g.code}</Text>
                </Box>
                <span className={`bill-gdot bill-gdot--${g.enabled ? 'live' : 'off'}`} aria-hidden="true" />
              </div>
            ))}
          </div>
        </VStack></CardBody></Card>

        {/* Recent transactions */}
        <Card><CardBody><VStack gap={3}>
          <HStack justify="between" align="center">
            <VStack gap={0}><Text weight={600}>Recent transactions</Text><Text size="xs" tone="tertiary">Latest invoices &amp; payments</Text></VStack>
            <Button intent="ghost" size="sm" onClick={() => router.visit(route('platform.admin.billing.invoices.index'))}>All invoices</Button>
          </HStack>
          <Box className="aeos-table-wrap">
            <DataTable columns={recentColumns} rows={recent} empty="No transactions yet." />
          </Box>
        </VStack></CardBody></Card>

      </VStack>
    </DashboardLayout>
  );
}

BillingDashboard.layout = (page) => (
  <App title="Billing" railTitle="Billing" rail={<BillingRail />}>{page}</App>
);
