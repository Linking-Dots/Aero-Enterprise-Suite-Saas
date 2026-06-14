import { Card, CardHeader, CardBody, Eyebrow, Text, Badge, Mono, VStack, HStack } from '@aero/ui';
import { CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/react/24/outline';

const SERVICES = [
  { key: 'database', label: 'Database' },
  { key: 'cache',    label: 'Cache'    },
  { key: 'queue',    label: 'Queue'    },
  { key: 'storage',  label: 'Storage'  },
  { key: 'mail',     label: 'Mail'     },
  { key: 'search',   label: 'Search'   },
];

function statusColor(pct) {
  if (pct >= 85) return 'var(--aeos-destructive)';
  if (pct >= 65) return 'var(--aeos-warning)';
  return 'var(--aeos-success)';
}

function svcColor(status) {
  if (status === 'ok' || status === 'healthy') return 'var(--aeos-success)';
  if (status === 'degraded' || status === 'warning') return 'var(--aeos-warning)';
  return 'var(--aeos-destructive)';
}

export default function SystemHealthWidget({ systemHealth }) {
  const h = systemHealth ?? {};

  const cpu     = Number(h.cpu    ?? h.cpuUsage    ?? 0);
  const memory  = Number(h.memory ?? h.memoryUsage ?? 0);
  const disk    = Number(h.disk   ?? h.diskUsage   ?? 0);

  const services = h.services ?? {};
  const overallOk = cpu < 85 && memory < 85 && disk < 85;

  const gauges = [
    { label: 'CPU',    value: cpu    },
    { label: 'Memory', value: memory },
    { label: 'Disk',   value: disk   },
  ];

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0, height: '100%' }}>
      <CardHeader>
        <HStack gap={2} style={{ minWidth: 0, flexWrap: 'wrap', rowGap: 4 }}>
          <Eyebrow style={{ flex: 1, minWidth: 0 }}>System health</Eyebrow>
          <Badge intent={overallOk ? 'success' : 'danger'} dot size="sm" style={{ flexShrink: 0 }}>
            {overallOk ? 'Operational' : 'Degraded'}
          </Badge>
        </HStack>
      </CardHeader>

      <CardBody style={{ flex: 1, minWidth: 0 }}>
        {/* Resource gauges */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(3, 1fr)',
            gap: 'var(--aeos-space-3)',
            marginBottom: 'var(--aeos-space-4)',
            minWidth: 0,
          }}
        >
          {gauges.map(({ label, value }) => (
            <VStack key={label} gap={1} style={{ minWidth: 0 }}>
              <Text as="span" size="xs" tone="secondary">{label}</Text>
              <Mono
                as="span"
                style={{
                  fontSize: 'var(--aeos-text-xl)',
                  fontWeight: 600,
                  color: statusColor(value),
                  lineHeight: 1.1,
                }}
              >
                {Math.round(value)}%
              </Mono>
              <div
                style={{
                  height: 5,
                  background: 'var(--aeos-bg-hover)',
                  borderRadius: 'var(--aeos-r-full)',
                  overflow: 'hidden',
                  width: '100%',
                }}
              >
                <div
                  style={{
                    height: '100%',
                    width: `${Math.min(100, value)}%`,
                    background: statusColor(value),
                    borderRadius: 'var(--aeos-r-full)',
                    transition: 'width var(--aeos-dur-slow) var(--aeos-ease-out)',
                  }}
                />
              </div>
            </VStack>
          ))}
        </div>

        {/* Divider */}
        <div
          style={{
            borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)',
            marginBottom: 'var(--aeos-space-3)',
          }}
        />

        <Text as="div" size="xs" tone="tertiary" weight={500} style={{ textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 'var(--aeos-space-3)' }}>
          Services
        </Text>

        {/* Service grid */}
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(2, 1fr)',
            gap: 'var(--aeos-space-2)',
            minWidth: 0,
          }}
        >
          {SERVICES.map(({ key, label }) => {
            const svc    = services[key] ?? {};
            const status = svc.status ?? (svc.ok === false ? 'down' : 'ok');
            const latency = svc.latency ?? svc.response_time ?? null;
            const color  = svcColor(status);

            return (
              <div
                key={key}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  gap: 'var(--aeos-space-2)',
                  padding: 'var(--aeos-space-2) var(--aeos-space-3)',
                  background: 'var(--aeos-bg-subtle)',
                  borderRadius: 'var(--aeos-r-md)',
                  minWidth: 0,
                  overflow: 'hidden',
                }}
              >
                <span
                  style={{
                    width: 7,
                    height: 7,
                    borderRadius: '50%',
                    background: color,
                    flexShrink: 0,
                  }}
                  aria-label={status}
                />
                <Text
                  as="span"
                  size="xs"
                  tone="secondary"
                  style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}
                >
                  {label}
                </Text>
                {latency != null && (
                  <Mono
                    as="span"
                    style={{ fontSize: 'var(--aeos-text-2xs)', color: 'var(--aeos-text-tertiary)', flexShrink: 0, whiteSpace: 'nowrap' }}
                  >
                    {latency}ms
                  </Mono>
                )}
              </div>
            );
          })}
        </div>
      </CardBody>
    </Card>
  );
}
