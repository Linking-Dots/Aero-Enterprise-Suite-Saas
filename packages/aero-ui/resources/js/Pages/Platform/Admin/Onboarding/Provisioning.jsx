import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Badge,
  Button,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  success: 'success',
  failed:  'danger',
  running: 'amber',
  pending: 'warning',
  queued:  'neutral',
};

export default function OnboardingProvisioning({ logs }) {
  const toast = useToast();
  const canRetry = useHRMAC('platform.admin.onboarding.retry');

  function retryLog(id) {
    router.post(
      route('platform.admin.onboarding.retry', id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Provisioning retry queued.'),
        onError:   () => toast.error('Failed to queue retry.'),
      }
    );
  }

  const columns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => (
        <VStack gap={0}>
          <Text>{row.tenant?.name ?? row.tenant_id}</Text>
          {row.tenant?.email && (
            <Text tone="secondary">{row.tenant.email}</Text>
          )}
        </VStack>
      ),
    },
    {
      key: 'step',
      label: 'Step',
      render: (row) => <Mono>{row.step}</Mono>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'message',
      label: 'Message',
      render: (row) => (
        <Text tone="secondary">{row.message ?? '—'}</Text>
      ),
    },
    {
      key: 'created_at',
      label: 'Logged',
      render: (row) => (
        <Mono tone="secondary">{new Date(row.created_at).toLocaleString()}</Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) =>
        row.status === 'failed' && canRetry ? (
          <Button
            intent="soft"
            size="sm"
            leftIcon="refresh"
            onClick={() => retryLog(row.id)}
          >
            Retry
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Provisioning Queue"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Onboarding' },
        { label: 'Provisioning' },
      ]}
      description="Monitor tenant provisioning steps and retry failed operations."
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={logs?.data ?? []}
          empty="No provisioning log entries found."
        />

        {logs?.last_page > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.onboarding.provisioning'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['logs'] }
              )
            }
          />
        )}
      </VStack>
    </IndexPageLayout>
  );
}

OnboardingProvisioning.layout = page => <App title="Provisioning Queue">{page}</App>;
