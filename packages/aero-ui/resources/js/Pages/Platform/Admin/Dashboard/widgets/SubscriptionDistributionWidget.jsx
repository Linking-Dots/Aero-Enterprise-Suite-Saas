import { Card, CardHeader, CardBody, Eyebrow, Text, Mono, VStack, HStack } from '@aero/ui';
import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer } from 'recharts';

const COLORS = [
  'var(--aeos-tertiary)',
  'var(--aeos-primary)',
  'var(--aeos-success)',
  'var(--aeos-secondary)',
  'var(--aeos-text-tertiary)',
];

function fmt(n) {
  const v = Number(n ?? 0);
  if (v >= 1_000_000) return `$${(v / 1_000_000).toFixed(2)}M`;
  if (v >= 1_000)     return `$${(v / 1_000).toFixed(1)}K`;
  return `$${Math.round(v)}`;
}

const CustomTooltip = ({ active, payload }) => {
  if (!active || !payload?.length) return null;
  const d = payload[0];
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
        boxShadow: 'var(--aeos-shadow-card)',
        maxWidth: 180,
      }}
    >
      <div style={{ fontWeight: 600 }}>{d.name}</div>
      <div style={{ color: 'var(--aeos-text-secondary)', marginTop: 2 }}>
        Tenants: {d.value}
      </div>
      {d.payload?.mrr != null && (
        <div style={{ color: 'var(--aeos-text-secondary)' }}>
          MRR: {fmt(d.payload.mrr)}
        </div>
      )}
    </div>
  );
};

export default function SubscriptionDistributionWidget({ subscriptionDistribution }) {
  const plans = (subscriptionDistribution?.plans ?? []).map((p, i) => ({
    name:  p.name  ?? p.plan_name ?? `Plan ${i + 1}`,
    value: p.count ?? p.tenant_count ?? 0,
    mrr:   p.mrr   ?? p.total_mrr   ?? null,
    color: COLORS[i % COLORS.length],
  }));

  const total = plans.reduce((sum, p) => sum + p.value, 0);

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0, height: '100%' }}>
      <CardHeader>
        <Eyebrow>Subscription distribution</Eyebrow>
      </CardHeader>
      <CardBody style={{ flex: 1, minWidth: 0 }}>
        {plans.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 'var(--aeos-space-6)' }}>
            <Text size="sm" tone="secondary">No subscription data</Text>
          </div>
        ) : (
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 'var(--aeos-space-5)',
              minWidth: 0,
              flexWrap: 'wrap',
            }}
          >
            {/* Donut */}
            <div style={{ flexShrink: 0, width: 100, height: 100, position: 'relative' }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={plans}
                    cx="50%"
                    cy="50%"
                    innerRadius={32}
                    outerRadius={48}
                    dataKey="value"
                    strokeWidth={0}
                    paddingAngle={2}
                  >
                    {plans.map((p, i) => (
                      <Cell key={i} fill={p.color} />
                    ))}
                  </Pie>
                  <Tooltip content={<CustomTooltip />} />
                </PieChart>
              </ResponsiveContainer>
              {/* Center label */}
              <div
                style={{
                  position: 'absolute',
                  inset: 0,
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  justifyContent: 'center',
                  pointerEvents: 'none',
                }}
              >
                <Mono
                  as="span"
                  style={{ fontSize: 'var(--aeos-text-lg)', fontWeight: 600, color: 'var(--aeos-text-primary)', lineHeight: 1 }}
                >
                  {total}
                </Mono>
                <Text as="span" size="xs" tone="tertiary" style={{ lineHeight: 1, marginTop: 2 }}>
                  active
                </Text>
              </div>
            </div>

            {/* Legend */}
            <VStack gap={2} style={{ flex: 1, minWidth: 0 }}>
              {plans.map((p) => (
                <div
                  key={p.name}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 'var(--aeos-space-2)',
                    minWidth: 0,
                  }}
                >
                  <span
                    style={{
                      width: 8,
                      height: 8,
                      borderRadius: 2,
                      background: p.color,
                      flexShrink: 0,
                    }}
                  />
                  <Text
                    as="span"
                    size="xs"
                    tone="secondary"
                    style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
                  >
                    {p.name}
                  </Text>
                  <Mono
                    as="span"
                    style={{ fontSize: 'var(--aeos-text-xs)', fontWeight: 600, color: 'var(--aeos-text-primary)', flexShrink: 0 }}
                  >
                    {p.value.toLocaleString()}
                  </Mono>
                  {p.mrr != null && (
                    <Mono
                      as="span"
                      style={{ fontSize: 'var(--aeos-text-xs)', color: 'var(--aeos-text-tertiary)', flexShrink: 0, marginLeft: 4 }}
                    >
                      {fmt(p.mrr)}
                    </Mono>
                  )}
                </div>
              ))}
            </VStack>
          </div>
        )}
      </CardBody>
    </Card>
  );
}
