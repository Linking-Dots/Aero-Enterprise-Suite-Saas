import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Pagination, Badge,
} from '@aero/ui';

const STATUS_INTENT = {
  issued:       'warning',
  acknowledged: 'success',
  escalated:    'danger',
};

function statusLabel(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
}

export default function WarningsIndex({ warnings, filters }) {
  const canIssue = useHRMAC('hrm.disciplinary.warnings.issue');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.disciplinary.warnings.index'),
      { ...filters, ...overrides, page: 1 },
      { preserveState: true, replace: true },
    );
  }

  const columns = [
    { key: 'employee',   label: 'Employee',    render: row => row.employee?.name ?? '—' },
    { key: 'subject',    label: 'Subject',      render: row => row.subject ?? '—' },
    { key: 'action_type', label: 'Action Type', render: row => row.actionType?.name ?? '—' },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
    { key: 'issued_at', label: 'Issued At', render: row => row.issued_at ?? row.created_at ?? '—' },
  ];

  return (
    <IndexPageLayout
      title="Warnings"
      breadcrumb={[{ label: 'HRM' }, { label: 'Disciplinary' }, { label: 'Warnings' }]}
      actions={
        canIssue && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.disciplinary.warnings.create'))}
          >
            Issue Warning
          </Button>
        )
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              placeholder="Search warnings..."
              defaultValue={filters?.search ?? ''}
              onBlur={e => applyFilters({ search: e.target.value })}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={warnings.data ?? []}
          empty="No warnings found."
        />
      }
      pagination={
        (warnings.last_page ?? 1) > 1 && (
          <Pagination
            total={warnings.last_page ?? 1}
            page={warnings.current_page ?? 1}
            onChange={p => applyFilters({ page: p })}
          />
        )
      }
    />
  );
}

WarningsIndex.layout = page => <App title="Warnings">{page}</App>;
