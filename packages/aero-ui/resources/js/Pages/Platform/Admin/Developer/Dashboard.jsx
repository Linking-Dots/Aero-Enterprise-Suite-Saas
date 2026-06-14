import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  CardHeader,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  KPI,
  Modal,
  Select,
  useHRMAC,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function formatBytes(n) {
  if (n == null) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  let v = n;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(1) + ' ' + units[i];
}

export default function DeveloperDashboard({ cache_stats, queue_stats, recent_log }) {
  const toast        = useToast();
  const canClear     = useHRMAC('developer-tools.cache-management.clear');
  const canManage    = useHRMAC('developer-tools.queue-management.manage');

  const [clearTarget, setClearTarget]   = useState(null);
  const [submitting, setSubmitting]     = useState(false);

  function doClearCache() {
    setSubmitting(true);
    router.post(
      route('platform.admin.developer.cache.clear'),
      { store: clearTarget },
      {
        onSuccess: () => { setClearTarget(null); toast.success(`Cache store '${clearTarget}' cleared.`); },
        onError: () => toast.error('Failed to clear cache.'),
        onFinish: () => setSubmitting(false),
      }
    );
  }

  const cacheRows = Object.entries(cache_stats ?? {}).map(([store, info]) => ({
    store,
    driver: info.driver,
    size: info.size,
  }));

  const cacheColumns = [
    {
      key: 'store',
      label: 'Store',
      render: (row) => <Text>{row.store}</Text>,
    },
    {
      key: 'driver',
      label: 'Driver',
      render: (row) => <Badge intent="neutral">{row.driver}</Badge>,
    },
    {
      key: 'size',
      label: 'Size',
      render: (row) => <Mono>{formatBytes(row.size)}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        canClear && (
          <Button intent="soft" size="sm" onClick={() => setClearTarget(row.store)}>
            Clear
          </Button>
        )
      ),
    },
  ];

  const queueByQueueColumns = [
    {
      key: 'queue',
      label: 'Queue',
      render: (row) => <Text>{row.queue}</Text>,
    },
    {
      key: 'count',
      label: 'Pending',
      render: (row) => <Text>{row.count}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Developer Dashboard"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Developer Tools' },
      ]}
      description="Cache management, queue health, and recent application logs."
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('platform.admin.developer.logs.index'))}
        >
          View Log Files
        </Button>
      }
    >
      <VStack gap={4}>
        {/* Queue KPIs */}
        <HStack gap={4} wrap>
          <KPI label="Pending Jobs" value={queue_stats?.pending_total ?? 0} />
          <KPI label="Failed Jobs" value={queue_stats?.failed_total ?? 0} />
          <KPI label="Queue Channels" value={(queue_stats?.by_queue ?? []).length} />
        </HStack>

        {/* Cache stores */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Cache Stores</Eyebrow>
              <DataTable
                columns={cacheColumns}
                rows={cacheRows}
                empty="No cache stores configured."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Queue by channel */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Pending Jobs by Queue</Eyebrow>
              <DataTable
                columns={queueByQueueColumns}
                rows={queue_stats?.by_queue ?? []}
                empty="No queued jobs."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Recent log tail */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2}>
                <Eyebrow>Recent laravel.log (last 50 lines)</Eyebrow>
              </HStack>
              <pre className="overflow-auto max-h-96 text-xs font-mono bg-default-100 p-3 rounded">
                {(recent_log ?? []).join('\n') || 'No log output.'}
              </pre>
            </VStack>
          </CardBody>
        </Card>
      </VStack>

      {/* Clear cache confirmation */}
      <Modal
        open={!!clearTarget}
        onClose={() => setClearTarget(null)}
        title={`Clear cache store '${clearTarget}'?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setClearTarget(null)}>Cancel</Button>
            <Button intent="soft" loading={submitting} disabled={submitting} onClick={doClearCache}>
              Clear Cache
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will flush all entries in the <Mono>{clearTarget}</Mono> store. This action cannot be undone.
        </Text>
      </Modal>
    </IndexPageLayout>
  );
}

DeveloperDashboard.layout = page => <App title="Developer Dashboard">{page}</App>;
