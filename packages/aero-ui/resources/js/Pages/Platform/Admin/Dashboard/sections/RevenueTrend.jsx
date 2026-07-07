import { useMemo, useState } from 'react';
import { Card, CardBody, Button, AreaTrend } from '@aero/ui';
import { fmtCurrency } from '../lib.jsx';

const TABS = [
  { key: 'mrr',   label: 'MRR' },
  { key: 'arr',   label: 'ARR' },
  { key: 'split', label: 'Plan vs Product' },
];

/**
 * Revenue intelligence — robust dependency-free area trend (shared @aero/ui
 * AreaTrend) with MRR / ARR / Plan-vs-Product views + footer metrics.
 */
export default function RevenueTrend({ revenue }) {
  const [tab, setTab] = useState('mrr');
  const trend = revenue?.trend ?? [];

  const labels = useMemo(() => trend.map((t) => t.month), [trend]);

  const series = useMemo(() => {
    const mrr = trend.map((t) => Number(t.mrr ?? 0));
    if (tab === 'arr') {
      return [{ key: 'arr', label: 'ARR', color: 'var(--aeos-secondary)', values: mrr.map((v) => v * 12) }];
    }
    if (tab === 'split') {
      return [
        { key: 'plan', label: 'Plan MRR', color: 'var(--aeos-primary)', values: trend.map((t) => Number(t.planMrr ?? 0)) },
        { key: 'product', label: 'Product MRR', color: 'var(--aeos-success)', fill: false, values: trend.map((t) => Number(t.productMrr ?? 0)) },
      ];
    }
    return [{ key: 'mrr', label: 'MRR', color: 'var(--aeos-primary)', values: mrr }];
  }, [trend, tab]);

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
          <div className="lcc-tab-row">
            {TABS.map((t) => (
              <Button key={t.key} intent={tab === t.key ? 'secondary' : 'ghost'} size="sm" onClick={() => setTab(t.key)}>
                {t.label}
              </Button>
            ))}
          </div>
        </div>

        <AreaTrend series={series} labels={labels} height={160} ariaLabel="Revenue trend" empty="No revenue history yet." />

        {tab === 'split' && (
          <div className="lcc-legend">
            <span><i className="lcc-legend__dot lcc-legend__dot--primary" />Plan MRR</span>
            <span><i className="lcc-legend__dot lcc-legend__dot--success" />Product MRR</span>
          </div>
        )}

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
