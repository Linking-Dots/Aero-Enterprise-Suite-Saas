import { Card, CardHeader, CardBody, Eyebrow, Text, ProgressRow, VStack, HStack, Mono } from '@aero/ui';

const STATUS_META = {
  active:       { color: 'var(--aeos-success)',     label: 'Active',       intent: 'success' },
  onTrial:      { color: 'var(--aeos-warning)',      label: 'On trial',     intent: 'amber'   },
  pending:      { color: 'var(--aeos-info)',         label: 'Pending',      intent: 'cyan'    },
  provisioning: { color: 'var(--aeos-tertiary)',     label: 'Provisioning', intent: 'neutral' },
  suspended:    { color: 'var(--aeos-destructive)',  label: 'Suspended',    intent: 'cyan'    },
  failed:       { color: '#b91c1c',                  label: 'Failed',       intent: 'cyan'    },
  archived:     { color: 'var(--aeos-text-tertiary)',label: 'Archived',     intent: 'neutral' },
};

export default function TenantPipelineWidget({ stats }) {
  const s = stats ?? {};

  const total = s.totalTenants ?? 0;
  const rows = [
    { key: 'active',       count: s.activeTenants      ?? s.active_tenants       ?? 0 },
    { key: 'onTrial',      count: s.trialTenants        ?? s.trial_tenants        ?? 0 },
    { key: 'pending',      count: s.pendingTenants      ?? 0 },
    { key: 'provisioning', count: s.provisioningTenants ?? 0 },
    { key: 'suspended',    count: s.suspendedTenants    ?? 0 },
    { key: 'failed',       count: s.failedTenants       ?? 0 },
    { key: 'archived',     count: s.archivedTenants     ?? 0 },
  ];

  const maxCount = Math.max(...rows.map(r => r.count), 1);
  const activeRate = total > 0 ? Math.round(((s.activeTenants ?? 0) / total) * 100) : 0;

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0, height: '100%' }}>
      <CardHeader>
        <Eyebrow>Tenant pipeline</Eyebrow>
      </CardHeader>
      <CardBody style={{ flex: 1, minWidth: 0 }}>
        <VStack gap={3} style={{ minWidth: 0 }}>
          {rows.map(({ key, count }) => {
            const meta = STATUS_META[key];
            const pct  = Math.round((count / maxCount) * 100);
            return (
              <div
                key={key}
                style={{
                  display: 'grid',
                  gridTemplateColumns: '90px 1fr 36px',
                  alignItems: 'center',
                  gap: 'var(--aeos-space-2)',
                  minWidth: 0,
                }}
              >
                <Text as="span" size="xs" tone="secondary" style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                  {meta.label}
                </Text>
                <div
                  style={{
                    height: 8,
                    background: 'var(--aeos-bg-hover)',
                    borderRadius: 'var(--aeos-r-full)',
                    overflow: 'hidden',
                    minWidth: 0,
                  }}
                >
                  <div
                    style={{
                      height: '100%',
                      width: `${pct}%`,
                      background: meta.color,
                      borderRadius: 'var(--aeos-r-full)',
                      transition: 'width var(--aeos-dur-slow) var(--aeos-ease-out)',
                      minWidth: count > 0 ? 4 : 0,
                    }}
                  />
                </div>
                <Mono
                  as="span"
                  style={{
                    fontSize: 'var(--aeos-text-xs)',
                    fontWeight: 600,
                    color: 'var(--aeos-text-primary)',
                    textAlign: 'right',
                  }}
                >
                  {count.toLocaleString()}
                </Mono>
              </div>
            );
          })}
        </VStack>

        {/* Footer */}
        <HStack
          gap={3}
          style={{
            marginTop: 'var(--aeos-space-4)',
            paddingTop: 'var(--aeos-space-4)',
            borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)',
            flexWrap: 'wrap',
            rowGap: 'var(--aeos-space-1)',
            minWidth: 0,
          }}
        >
          <Text as="span" size="xs" tone="secondary">
            Total registered:{' '}
            <Mono as="span" style={{ fontSize: 'var(--aeos-text-xs)', fontWeight: 600, color: 'var(--aeos-text-primary)' }}>
              {total.toLocaleString()}
            </Mono>
          </Text>
          <Text as="span" size="xs" tone="secondary">
            Active rate:{' '}
            <Mono as="span" style={{ fontSize: 'var(--aeos-text-xs)', fontWeight: 600, color: 'var(--aeos-success)' }}>
              {activeRate}%
            </Mono>
          </Text>
        </HStack>
      </CardBody>
    </Card>
  );
}
