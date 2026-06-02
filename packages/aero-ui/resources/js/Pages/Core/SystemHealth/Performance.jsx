import {
  IndexPageLayout,
  Card,
  CardBody,
  Badge,
  HStack, VStack,
  Text,
  Eyebrow,
  Mono,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const DRIVER_INTENT = {
  redis:    'success',
  database: 'neutral',
  sync:     'warning',
  file:     'neutral',
  array:    'warning',
  memcached: 'success',
  cookie:   'neutral',
};

function MetricCard({ label, value, children }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={2}>
          <Text size="sm" tone="secondary">{label}</Text>
          {children ?? <Mono size="sm">{value ?? '—'}</Mono>}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function SystemHealthPerformance({ metrics }) {
  const m = metrics ?? {};

  const opcacheIntent = m.opcache_enabled === true
    ? 'success'
    : m.opcache_enabled === false ? 'danger' : 'neutral';

  const queueIntent   = DRIVER_INTENT[m.queue_driver?.toLowerCase()]   ?? 'neutral';
  const cacheIntent   = DRIVER_INTENT[m.cache_driver?.toLowerCase()]   ?? 'neutral';
  const sessionIntent = DRIVER_INTENT[m.session_driver?.toLowerCase()] ?? 'neutral';

  const dbConnections = Array.isArray(m.db_connections) ? m.db_connections : [];

  return (
    <IndexPageLayout
      title="Performance"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Health', href: route('core.system-health.index') },
        { label: 'Performance' },
      ]}
      description="Runtime metrics, driver configuration, and memory usage."
      table={
        <VStack gap={5}>

          {/* Runtime */}
          <VStack gap={3}>
            <Eyebrow>Runtime</Eyebrow>
            <div className="perf-grid">
              <MetricCard label="PHP Version"     value={m.php_version}     />
              <MetricCard label="Laravel Version" value={m.laravel_version} />
              <MetricCard label="Memory Limit"    value={m.memory_limit}    />
              <MetricCard label="Memory Used"     value={m.memory_used}     />
              <MetricCard label="Memory Peak"     value={m.memory_peak}     />
            </div>
          </VStack>

          {/* Feature Flags */}
          <VStack gap={3}>
            <Eyebrow>Feature Flags</Eyebrow>
            <div className="perf-grid">
              <MetricCard label="OPcache">
                <Badge intent={opcacheIntent}>
                  {m.opcache_enabled === true ? 'Enabled' : m.opcache_enabled === false ? 'Disabled' : '—'}
                </Badge>
              </MetricCard>

              <MetricCard label="Debug Mode">
                {m.debug_mode === true ? (
                  <Badge intent="warning">Enabled — disable in production</Badge>
                ) : (
                  <Badge intent="success">Disabled</Badge>
                )}
              </MetricCard>
            </div>
          </VStack>

          {/* Drivers */}
          <VStack gap={3}>
            <Eyebrow>Drivers</Eyebrow>
            <div className="perf-grid">
              <MetricCard label="Queue Driver">
                <Badge intent={queueIntent}>{m.queue_driver ?? '—'}</Badge>
              </MetricCard>
              <MetricCard label="Cache Driver">
                <Badge intent={cacheIntent}>{m.cache_driver ?? '—'}</Badge>
              </MetricCard>
              <MetricCard label="Session Driver">
                <Badge intent={sessionIntent}>{m.session_driver ?? '—'}</Badge>
              </MetricCard>
            </div>
          </VStack>

          {/* DB Connections */}
          <VStack gap={3}>
            <Eyebrow>Database Connections</Eyebrow>
            <Card>
              <CardBody>
                {dbConnections.length > 0 ? (
                  <HStack gap={2} wrap>
                    {dbConnections.map((conn, i) => (
                      <Badge key={i} intent="neutral">{conn}</Badge>
                    ))}
                  </HStack>
                ) : (
                  <Text tone="secondary">No connection data available.</Text>
                )}
              </CardBody>
            </Card>
          </VStack>

        </VStack>
      }
    />
  );
}

SystemHealthPerformance.layout = page => (
  <App title="Performance">
    <style>{`
      .perf-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: var(--aeos-r-md, 12px);
      }
    `}</style>
    {page}
  </App>
);
