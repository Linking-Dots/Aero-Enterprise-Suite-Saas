import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Select,
  Modal,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:   'success',
  archived: 'neutral',
  draft:    'warning',
};

export default function PlansIndex({ plans, filters }) {
  const toast = useToast();
  const canCreate  = useHRMAC('plan-management.plan-list.create');
  const canClone   = useHRMAC('plan-management.plan-list.clone');
  const canArchive = useHRMAC('plan-management.plan-list.archive');

  const [status, setStatus]         = useState(filters?.status ?? '');
  const [archiveTarget, setArchiveTarget] = useState(null);

  function applyFilters() {
    router.get(
      route('platform.admin.plans.index'),
      { status },
      { preserveState: true, preserveScroll: true, only: ['plans', 'filters'] }
    );
  }

  function resetFilters() {
    setStatus('');
    router.get(route('platform.admin.plans.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['plans', 'filters'],
    });
  }

  function clonePlan(plan) {
    router.post(
      route('platform.admin.plans.clone', plan.id),
      {},
      {
        onSuccess: () => toast.success(`"${plan.name}" cloned.`),
        onError:   () => toast.error('Clone failed.'),
      }
    );
  }

  function confirmArchive() {
    if (!archiveTarget) return;
    router.post(
      route('platform.admin.plans.archive', archiveTarget.id),
      {},
      {
        onSuccess: () => {
          toast.success(`"${archiveTarget.name}" archived.`);
          setArchiveTarget(null);
        },
        onError: () => toast.error('Archive failed.'),
      }
    );
  }

  const statusOptions = [
    { value: '',         label: 'All Statuses' },
    { value: 'active',   label: 'Active' },
    { value: 'draft',    label: 'Draft' },
    { value: 'archived', label: 'Archived' },
  ];

  const columns = [
    {
      key: 'name',
      label: 'Plan Name',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.plans.show', row.id))}
        >
          {row.name}
        </Button>
      ),
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
      key: 'price',
      label: 'Price / mo',
      render: (row) => (
        <Mono>{row.price_monthly != null ? `$${Number(row.price_monthly).toFixed(2)}` : '—'}</Mono>
      ),
    },
    {
      key: 'subscribers',
      label: 'Subscribers',
      render: (row) => (
        <Text tone="secondary">{row.active_subscribers_count ?? 0}</Text>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canClone && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => clonePlan(row)}
            >
              Clone
            </Button>
          )}
          {canArchive && row.status !== 'archived' && (
            <Button
              intent="danger"
              size="sm"
              onClick={() => setArchiveTarget(row)}
            >
              Archive
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Plans"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Plans' },
      ]}
      description="Manage subscription plans available to tenants."
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('platform.admin.plans.create'))}
          >
            New Plan
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={statusOptions}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={plans?.data ?? []}
          empty="No plans found."
        />

        {plans?.last_page > 1 && (
          <Pagination
            page={plans.current_page}
            total={plans.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.plans.index'),
                { page, status },
                { preserveState: true, preserveScroll: true, only: ['plans'] }
              )
            }
          />
        )}
      </VStack>

      <Modal
        open={!!archiveTarget}
        onClose={() => setArchiveTarget(null)}
        title="Archive Plan"
        description={`Archive "${archiveTarget?.name}"? Existing subscribers will not be affected, but no new sign-ups will be allowed.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmArchive}>
              Confirm Archive
            </Button>
            <Button intent="ghost" onClick={() => setArchiveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

PlansIndex.layout = page => <App title="Plans">{page}</App>;
