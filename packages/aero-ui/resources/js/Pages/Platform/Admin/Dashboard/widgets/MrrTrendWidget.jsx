import { useState } from 'react';
import { Card, CardHeader, CardBody, HStack, Button, Text, Eyebrow, Mono, VStack } from '@aero/ui';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Legend } from 'recharts';

const TABS = [
  { key: 'mrr',     label: 'MRR' },
  { key: 'arr',     label: 'ARR' },
  { key: 'split',   label: 'Plan vs Product' },
];

function fmt(v) {
  const n = Number(v ?? 0);
  if (n >= 1_000_000) return `$${(n / 1_000_000).toFixed(2)}M`;
  if (n >= 1_000)     return `$${(n / 1_000).toFixed(1)}K`;
  return `$${Math.round(n)}`;
}

const TICK_STYLE = {
  fontSize: 10,
  fontFamily: 'var(--aeos-font-mono)',
  fill: 'var(--aeos-text-tertiary)',
};

const CustomTooltip = ({ active, payload, label }) => {
  if (!active || !payload?.length) return null;
  return (
    <div
      style={{
        background: 'var(--aeos-bg-elevated)',
        border: 'var(--aeos-border-width) solid var(--aeos-border-subtle)',
        borderRadius: 'var(--aeos-r-md)',
        padding: '8px 12px',
        fontSize: 12,
        fontFamily: 'var(--aeos-font-mono)',
        color: 'var(--aeos-text-primary)',
        maxWidth: 160,
        boxShadow: 'var(--aeos-shadow-card)',
      }}
    >
      <div style={{ color: 'var(--aeos-text-secondary)', marginBottom: 4, fontSize: 11 }}>{label}</div>
      {payload.map((p) => (
        <div key={p.dataKey} style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 2 }}>
          <span style={{ width: 8, height: 8, borderRadius: 2, background: p.fill, flexShrink: 0 }} />
          <span>{p.name}: {fmt(p.value)}</span>
        </div>
      ))}
    </div>
  );
};

export default function MrrTrendWidget({ billingOverview, stats }) {
  const [tab, setTab] = useState('mrr');
  const trends = billingOverview?.trends ?? [];

  const data = trends.map((t) => ({
    month: t.month ?? '',
    mrr:   Number(t.mrr ?? 0),
    arr:   Number((t.mrr ?? 0) * 12),
    planMrr:    Number(stats?.plan_mrr ?? 0) / Math.max(trends.length, 1),
    productMrr: Number(stats?.product_mrr ?? 0) / Math.max(trends.length, 1),
  }));

  const planMrr    = Number(stats?.plan_mrr    ?? billingOverview?.revenue?.mrr ?? 0);
  const productMrr = Number(stats?.product_mrr ?? 0);
  const arpt       = Number(stats?.arpt ?? stats?.avgRevenuePerTenant ?? 0);
  const mrrGrowth  = Number(billingOverview?.revenue?.mrrGrowth ?? stats?.mrr_growth ?? 0);

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0 }}>
      <CardHeader>
        <HStack gap={3} style={{ flexWrap: 'wrap', rowGap: 8, alignItems: 'center', minWidth: 0 }}>
          <Eyebrow style={{ flex: '0 0 auto' }}>MRR / ARR trend</Eyebrow>
          <div style={{ flex: '1 1 auto', minWidth: 0 }} />
          <HStack gap={1} style={{ flexShrink: 0 }}>
            {TABS.map((t) => (
              <Button
                key={t.key}
                intent="ghost"
                size="sm"
                onClick={() => setTab(t.key)}
                style={{
                  fontWeight: tab === t.key ? 600 : 400,
                  background: tab === t.key ? 'var(--aeos-bg-hover)' : 'transparent',
                }}
              >
                {t.label}
              </Button>
            ))}
          </HStack>
        </HStack>
      </CardHeader>

      <CardBody style={{ flex: 1, minWidth: 0 }}>
        <div style={{ width: '100%', height: 160, minWidth: 0 }}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={data} margin={{ top: 4, right: 0, left: 0, bottom: 0 }} barCategoryGap="30%">
              <XAxis dataKey="month" tick={TICK_STYLE} axisLine={false} tickLine={false} />
              <YAxis
                tick={TICK_STYLE}
                axisLine={false}
                tickLine={false}
                tickFormatter={fmt}
                width={52}
              />
              <Tooltip content={<CustomTooltip />} cursor={{ fill: 'var(--aeos-bg-subtle)' }} />
              {tab === 'mrr' && (
                <Bar dataKey="mrr" name="MRR" fill="var(--aeos-primary)" radius={[3, 3, 0, 0]} maxBarSize={32} />
              )}
              {tab === 'arr' && (
                <Bar dataKey="arr" name="ARR" fill="var(--aeos-secondary)" radius={[3, 3, 0, 0]} maxBarSize={32} />
              )}
              {tab === 'split' && (
                <>
                  <Bar dataKey="planMrr" name="Plan MRR" fill="var(--aeos-primary)" radius={[3, 3, 0, 0]} stackId="a" maxBarSize={32} />
                  <Bar dataKey="productMrr" name="Product MRR" fill="var(--aeos-secondary)" radius={[3, 3, 0, 0]} stackId="a" maxBarSize={32} />
                </>
              )}
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Footer metrics */}
        <div
          style={{
            display: 'flex',
            gap: 'var(--aeos-space-4)',
            flexWrap: 'wrap',
            marginTop: 'var(--aeos-space-4)',
            paddingTop: 'var(--aeos-space-4)',
            borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)',
            rowGap: 'var(--aeos-space-2)',
          }}
        >
          {[
            { label: 'Plan MRR',    value: fmt(planMrr) },
            { label: 'Product MRR', value: fmt(productMrr) },
            { label: 'ARPT',        value: fmt(arpt) },
            { label: 'MoM growth',  value: `${mrrGrowth >= 0 ? '+' : ''}${mrrGrowth.toFixed(1)}%` },
          ].map(({ label, value }) => (
            <div key={label} style={{ minWidth: 0 }}>
              <Text as="span" size="xs" tone="secondary">{label} </Text>
              <Mono as="span" style={{ fontSize: 'var(--aeos-text-sm)', fontWeight: 600, color: 'var(--aeos-primary)' }}>
                {value}
              </Mono>
            </div>
          ))}
        </div>
      </CardBody>
    </Card>
  );
}
