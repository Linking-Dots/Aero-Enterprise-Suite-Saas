import { Card, CardHeader, CardBody, Eyebrow, Text, Mono, VStack } from '@aero/ui';
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell,
} from 'recharts';

const BAR_COLORS = [
  'var(--aeos-primary)',
  'var(--aeos-tertiary)',
  'var(--aeos-success)',
  'var(--aeos-secondary)',
  'var(--aeos-warning)',
  '#D85A30',
  '#7F77DD',
  '#1D9E75',
  '#D4537E',
  '#0F6E56',
];

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
      }}
    >
      <div style={{ fontWeight: 600 }}>{d.payload.name}</div>
      <div style={{ color: 'var(--aeos-text-secondary)', marginTop: 2 }}>
        {d.value.toLocaleString()} tenants ({d.payload.pct}%)
      </div>
    </div>
  );
};

const TICK_STYLE = {
  fontSize: 11,
  fontFamily: 'var(--aeos-font-body)',
  fill: 'var(--aeos-text-secondary)',
};

const MONO_TICK = {
  fontSize: 10,
  fontFamily: 'var(--aeos-font-mono)',
  fill: 'var(--aeos-text-tertiary)',
};

export default function ModuleAdoptionWidget({ moduleUsage }) {
  const raw   = moduleUsage?.modules ?? moduleUsage ?? [];
  const total = moduleUsage?.totalTenants ?? moduleUsage?.total_tenants ?? 1;

  const data = [...raw]
    .sort((a, b) => (b.count ?? b.tenant_count ?? 0) - (a.count ?? a.tenant_count ?? 0))
    .slice(0, 10)
    .map((m, i) => {
      const count = m.count ?? m.tenant_count ?? 0;
      const pct   = total > 0 ? Math.round((count / total) * 100) : 0;
      return {
        name:  m.name ?? m.module_name ?? `Module ${i + 1}`,
        count,
        pct,
        color: BAR_COLORS[i % BAR_COLORS.length],
      };
    });

  return (
    <Card style={{ minWidth: 0 }}>
      <CardHeader>
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 'var(--aeos-space-3)',
            flexWrap: 'wrap',
            rowGap: 4,
            minWidth: 0,
          }}
        >
          <Eyebrow style={{ flex: 1, minWidth: 0 }}>Module adoption</Eyebrow>
          <Text as="span" size="xs" tone="tertiary" style={{ flexShrink: 0 }}>
            % of active tenants using each module
          </Text>
        </div>
      </CardHeader>

      <CardBody style={{ minWidth: 0 }}>
        {data.length === 0 ? (
          <div style={{ textAlign: 'center', padding: 'var(--aeos-space-6)' }}>
            <Text size="sm" tone="secondary">No module data available</Text>
          </div>
        ) : (
          <>
            {/* Responsive bar chart for larger screens */}
            <div
              className="dash-module-chart"
              style={{ width: '100%', height: 180, minWidth: 0 }}
            >
              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={data}
                  layout="vertical"
                  margin={{ top: 0, right: 40, left: 0, bottom: 0 }}
                  barCategoryGap="28%"
                >
                  <XAxis
                    type="number"
                    domain={[0, 100]}
                    tick={MONO_TICK}
                    axisLine={false}
                    tickLine={false}
                    tickFormatter={(v) => `${v}%`}
                    tickCount={6}
                  />
                  <YAxis
                    type="category"
                    dataKey="name"
                    tick={TICK_STYLE}
                    axisLine={false}
                    tickLine={false}
                    width={110}
                    tickFormatter={(v) =>
                      v.length > 14 ? `${v.slice(0, 13)}…` : v
                    }
                  />
                  <Tooltip
                    content={<CustomTooltip />}
                    cursor={{ fill: 'var(--aeos-bg-subtle)' }}
                  />
                  <Bar dataKey="pct" radius={[0, 3, 3, 0]} maxBarSize={14}>
                    {data.map((d, i) => (
                      <Cell key={i} fill={d.color} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>

            {/* Fallback linear-progress list for very narrow viewports */}
            <div className="dash-module-list" style={{ display: 'none', minWidth: 0 }}>
              <VStack gap={3} style={{ minWidth: 0 }}>
                {data.map((d) => (
                  <div
                    key={d.name}
                    style={{
                      display: 'grid',
                      gridTemplateColumns: '1fr auto',
                      gap: 'var(--aeos-space-2)',
                      alignItems: 'center',
                      minWidth: 0,
                    }}
                  >
                    <VStack gap={1} style={{ minWidth: 0 }}>
                      <Text
                        as="span"
                        size="xs"
                        tone="secondary"
                        style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
                      >
                        {d.name}
                      </Text>
                      <div
                        style={{
                          height: 6,
                          background: 'var(--aeos-bg-hover)',
                          borderRadius: 'var(--aeos-r-full)',
                          overflow: 'hidden',
                        }}
                      >
                        <div
                          style={{
                            height: '100%',
                            width: `${d.pct}%`,
                            background: d.color,
                            borderRadius: 'var(--aeos-r-full)',
                            transition: 'width var(--aeos-dur-slow) var(--aeos-ease-out)',
                          }}
                        />
                      </div>
                    </VStack>
                    <Mono
                      as="span"
                      style={{
                        fontSize: 'var(--aeos-text-xs)',
                        fontWeight: 600,
                        color: 'var(--aeos-text-primary)',
                        flexShrink: 0,
                      }}
                    >
                      {d.pct}%
                    </Mono>
                  </div>
                ))}
              </VStack>
            </div>
          </>
        )}
      </CardBody>
    </Card>
  );
}
