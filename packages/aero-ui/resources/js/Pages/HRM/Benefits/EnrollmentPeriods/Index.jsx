import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Badge, Pagination,
} from '@aero/ui';

const STATUS_INTENT = { draft: 'neutral', active: 'success', closed: 'danger' };

export default function EnrollmentPeriodsIndex({ periods }) {
  const canCreate   = useHRMAC('hrm.benefits.enrollment-periods.edit');
  const canActivate = useHRMAC('hrm.benefits.enrollment-periods.activate');

  const columns = [
    { key: 'name',           label: 'Period Name' },
    { key: 'starts_at',      label: 'Start' },
    { key: 'ends_at',        label: 'End' },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    { key: 'benefits_count', label: 'Benefits' },
    {
      key: 'actions',
      label: '',
      render: row => (
        <HStack gap={2}>
          <Button
            size="sm"
            intent="ghost"
            onClick={() => router.get(route('hrm.benefits.enrollment-periods.show', row.id))}
          >
            View
          </Button>
          {canActivate && row.status === 'draft' && (
            <Button
              size="sm"
              intent="primary"
              onClick={() => router.post(route('hrm.benefits.enrollment-periods.activate', row.id))}
            >
              Activate
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Enrollment Periods"
      breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Enrollment Periods' }]}
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.benefits.enrollment-periods.create'))}
          >
            New Period
          </Button>
        )
      }
      table={
        <DataTable
          columns={columns}
          rows={periods.data ?? []}
          empty="No enrollment periods found."
        />
      }
      pagination={
        (periods.last_page ?? 1) > 1 && (
          <Pagination
            total={periods.last_page ?? 1}
            page={periods.current_page ?? 1}
            onChange={p => router.get(route('hrm.benefits.enrollment-periods.index'), { page: p })}
          />
        )
      }
    />
  );
}

EnrollmentPeriodsIndex.layout = page => <App title="Enrollment Periods">{page}</App>;
