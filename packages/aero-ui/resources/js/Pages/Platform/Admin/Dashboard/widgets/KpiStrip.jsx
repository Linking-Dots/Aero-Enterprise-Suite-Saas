import { KPI, Sparkline } from '@aero/ui';
import {
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
} from '@heroicons/react/24/outline';

function fmt(n) {
  if (n == null || Number.isNaN(n)) return '—';
  const v = Number(n);
  if (v >= 1_000_000) return `$${(v / 1_000_000).toFixed(2)}M`;
  if (v >= 1_000)     return `$${(v / 1_000).toFixed(1)}K`;
  return `$${v.toFixed(2)}`;
}

function pct(n) {
  if (n == null) return null;
  const v = Number(n);
  return `${v >= 0 ? '+' : ''}${v.toFixed(1)}%`;
}

function buildSpark(trends, key) {
  if (!Array.isArray(trends) || trends.length === 0) return [];
  return trends.map(t => Number(t[key] ?? 0));
}

export default function KpiStrip({ stats, billingOverview }) {
  const s = stats ?? {};
  const bo = billingOverview?.revenue ?? {};
  const trends = billingOverview?.trends ?? [];

  const mrrSpark = buildSpark(trends, 'mrr');
  const arrSpark = mrrSpark.map(v => v * 12);

  const mrrGrowth = bo.mrrGrowth ?? s.mrr_growth ?? 0;
  const mrrTrend = mrrGrowth >= 0 ? 'up' : 'down';

  const kpis = [
    {
      label: 'MRR',
      value: fmt(bo.mrr ?? s.mrr ?? s.plan_mrr),
      delta: pct(mrrGrowth),
      deltaTrend: mrrTrend,
      sparkline: mrrSpark,
    },
    {
      label: 'ARR',
      value: fmt(bo.arr ?? s.arr ?? s.plan_arr),
      delta: pct(mrrGrowth),
      deltaTrend: mrrTrend,
      sparkline: arrSpark,
    },
    {
      label: 'Active tenants',
      value: (s.activeTenants ?? s.active_tenants ?? 0).toLocaleString(),
      delta: s.newThisMonth != null ? `+${s.newThisMonth} this month` : null,
      deltaTrend: 'up',
    },
    {
      label: 'Churn rate (30d)',
      value: `${Number(s.churnRate ?? s.churn_rate_pct ?? 0).toFixed(1)}%`,
      delta: null,
      deltaTrend: Number(s.churnRate ?? s.churn_rate_pct ?? 0) > 5 ? 'down' : 'up',
    },
    {
      label: 'New this month',
      value: (s.newThisMonth ?? 0).toLocaleString(),
      delta: null,
      deltaTrend: 'up',
    },
    {
      label: 'On trial',
      value: (s.trialTenants ?? s.trial_tenants ?? 0).toLocaleString(),
      delta: null,
      deltaTrend: 'neutral',
    },
  ];

  return (
    <div
      style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(min(140px, 100%), 1fr))',
        gap: 'var(--aeos-space-3)',
        width: '100%',
        minWidth: 0,
      }}
    >
      {kpis.map((k) => (
        <div key={k.label} style={{ minWidth: 0 }}>
          <KPI
            label={k.label}
            value={k.value}
            delta={k.delta}
            deltaTrend={k.deltaTrend}
            sparkline={k.sparkline}
          />
        </div>
      ))}
    </div>
  );
}
