import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card,
  CardBody,
  DataTable,
  Button,
  HStack, VStack,
  Text,
  Eyebrow,
  Mono,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function UsageBar({ percent }) {
  const pct   = Math.min(100, Math.max(0, percent ?? 0));
  const intent = pct >= 90 ? 'var(--aeos-destructive)' : pct >= 70 ? 'var(--aeos-warning)' : 'var(--aeos-primary)';
  return (
    <VStack gap={2}>
      <HStack gap={2}>
        <Text size="sm" tone="secondary">Used</Text>
        <Text size="sm">{pct}%</Text>
      </HStack>
      <div className="storage-bar-track">
        <div
          className="storage-bar-fill"
          data-pct={pct}
          style={{ '--fill-color': intent, '--fill-pct': `${pct}%` }}
        />
      </div>
    </VStack>
  );
}

export default function SystemHealthStorage({ storage, directories }) {
  const toast     = useToast();
  const [clearing, setClearing] = useState(false);

  const s    = storage ?? {};
  const dirs = Array.isArray(directories) ? directories : [];

  const clearCache = () => {
    if (!confirm('Clear application cache to free up temporary storage?')) return;
    setClearing(true);
    router.post(route('core.system-health.cache.clear'), {}, {
      preserveState: false,
      onSuccess: () => toast.success('Cache cleared successfully.'),
      onError:   () => toast.error('Failed to clear cache.'),
      onFinish:  () => setClearing(false),
    });
  };

  const columns = [
    {
      key: 'name', label: 'Directory', width: '25%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'path', label: 'Path', width: '55%',
      render: row => <Mono size="sm" tone="secondary">{row.path}</Mono>,
    },
    {
      key: 'size', label: 'Size', width: '20%', align: 'right',
      render: row => <Text size="sm">{row.size ?? '—'}</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="Storage"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'System Health', href: route('core.system-health.index') },
        { label: 'Storage' },
      ]}
      description="Disk usage overview and directory breakdown."
      actions={
        <Button
          intent="ghost"
          loading={clearing}
          onClick={clearCache}
          leftIcon="trash"
        >
          Clear Cache
        </Button>
      }
      table={
        <VStack gap={5}>

          {/* Disk Overview */}
          <VStack gap={3}>
            <Eyebrow>Disk Usage</Eyebrow>
            <Card>
              <CardBody>
                <VStack gap={4}>
                  <UsageBar percent={s.used_percent} />
                  <div className="storage-stats-grid">
                    <VStack gap={1}>
                      <Text size="sm" tone="secondary">Total</Text>
                      <Mono size="sm">{s.total_human ?? '—'}</Mono>
                    </VStack>
                    <VStack gap={1}>
                      <Text size="sm" tone="secondary">Used</Text>
                      <Mono size="sm">{s.used_human ?? '—'}</Mono>
                    </VStack>
                    <VStack gap={1}>
                      <Text size="sm" tone="secondary">Free</Text>
                      <Mono size="sm">{s.free_human ?? '—'}</Mono>
                    </VStack>
                  </div>
                </VStack>
              </CardBody>
            </Card>
          </VStack>

          {/* Directory breakdown */}
          <VStack gap={3}>
            <Eyebrow>Directories</Eyebrow>
            <DataTable
              columns={columns}
              rows={dirs}
              empty="No directory data available."
            />
          </VStack>

        </VStack>
      }
    />
  );
}

SystemHealthStorage.layout = page => (
  <App title="Storage">
    <style>{`
      .storage-bar-track {
        height: 10px;
        border-radius: var(--aeos-r-sm);
        background: var(--aeos-divider);
        overflow: hidden;
      }
      .storage-bar-fill {
        height: 100%;
        width: var(--fill-pct);
        background: var(--fill-color);
        border-radius: var(--aeos-r-sm);
        transition: width 0.4s ease;
      }
      .storage-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
      }
    `}</style>
    {page}
  </App>
);
