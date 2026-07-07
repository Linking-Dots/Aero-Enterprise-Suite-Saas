import { useMemo, useState } from 'react';
import { Card, CardBody, Button } from '@aero/ui';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts';
import { fmtCurrency } from '../lib.jsx';

const TABS = [
  { key: 'mrr',   label: 'MRR' },
  { key: 'arr',   label: 'ARR' },
  { key: 'split', label: 'Plan vs Product' },
];

const TICK = { fontSize: 10, fontFamily: 'var(--aeos-font-mono)', fill: 'var(--aeos-text-tertiary)' };

function ChartTip({ active, payload, label }) {
  if (!active || !payload?.length) return null;
  return (
    <div style={{
      background: 'var(--aeos-bg-elevated)',
      border: 'var(--aeos-border-width) solid var(--aeos-border-subtle)',
      borderRadius: 'var(--aeos-r-md)', padding: '8px 12px', fontSize: 12,
      fontFamily: 'var(--aeos-font-mono)', color: 'var(--aeos-text-primary)',
      boxShadow: 'var(--aeos-shadow-card)',
    }}>
      <div style={{ color: 'var(--aeos-text-secondary)', marginBottom: 4, fontSize: 11 }}>{label}</div>
      {payload.map((p) => (
        <div key={p.dataKey} style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 2 }}>
          <span style={{ width: 8, height: 8, borderRadius: 2, background: p.fill }} />
          <span>{p.name}: {fmtCurrency(p.value)}</span>
        </div>
      ))}
    </div>
  );
}

export default function RevenueTrend({ revenue }) {
  const [tab, setTab] = useState('mrr');
  const trend = revenue?.trend ?? [];

  const data = useMemo(() => trend.map((t) => ({
    month: t.month,
    mrr: Number(t.mrr ?? 0),
    arr: Number(t.mrr ?? 0) * 12,
    planMrr: Number(t.planMrr ?? 0),
    productMrr: Number(t.productMrr ?? 0),
  })), [trend]);

  const footer = [
    { k: 'Plan MRR',    v: fmtCurrency(revenue?.planMrr) },
    { k: 'Product MRR', v: fmtCurrency(revenue?.productMrr) },
    { k: 'ARPT',        v: fmtCurrency(revenue?.arpt) },
    { k: 'MoM growth',  v: `${(revenue?.momGrowth ?? 0) >= 0 ? '+' : ''}${Number(revenue?.momGrowth ?? 0).toFixed(1)}%` },
  ];

  return (
    <Card>
      <CardBody>
        <div className="lcc-card-h">
          <span className="lcc-card-h__title">Revenue intelligence</span>
          <div style={{ display: 'flex', gap: 4 }}>
            {TABS.map((t) => (
              <Button key={t.key} intent={tab === t.key ? 'secondary' : 'ghost'} size="sm" onClick={() => setTab(t.key)}>
                {t.label}
              </Button>
            ))}
          </div>
        </div>

        <div className="lcc-chart">
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={data} margin={{ top: 4, right: 0, left: 0, bottom: 0 }} barCategoryGap="28%">
              <XAxis dataKey="month" tick={TICK} axisLine={false} tickLine={false} />
              <YAxis tick={TICK} axisLine={false} tickLine={false} tickFormatter={fmtCurrency} width={52} />
              <Tooltip content={<ChartTip />} cursor={{ fill: 'var(--aeos-bg-subtle)' }} />
              {tab === 'mrr' && <Bar dataKey="mrr" name="MRR" fill="var(--aeos-primary)" radius={[3, 3, 0, 0]} maxBarSize={34} isAnimationActive={false} />}
              {tab === 'arr' && <Bar dataKey="arr" name="ARR" fill="var(--aeos-secondary)" radius={[3, 3, 0, 0]} maxBarSize={34} isAnimationActive={false} />}
              {tab === 'split' && <>
                <Bar dataKey="planMrr" name="Plan MRR" stackId="a" fill="var(--aeos-primary)" radius={[0, 0, 0, 0]} maxBarSize={34} isAnimationActive={false} />
                <Bar dataKey="productMrr" name="Product MRR" stackId="a" fill="var(--aeos-secondary)" radius={[3, 3, 0, 0]} maxBarSize={34} isAnimationActive={false} />
              </>}
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="lcc-foot-metrics">
          {footer.map((f) => (
            <div key={f.k} className="lcc-foot-metrics__item">
              <span className="lcc-foot-metrics__k">{f.k}</span>
              <span className="lcc-foot-metrics__v">{f.v}</span>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
