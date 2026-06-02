import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Field, Select, Pagination, Badge, Text, Alert,
} from '@aero/ui';

const TYPE_OPTIONS = [
  { value: '',           label: 'All Types' },
  { value: 'induction',  label: 'Induction' },
  { value: 'refresher',  label: 'Refresher' },
  { value: 'emergency',  label: 'Emergency' },
  { value: 'toolbox',    label: 'Toolbox Talk' },
  { value: 'specialist', label: 'Specialist' },
];

function typeIntent(type) {
  switch (type) {
    case 'induction':  return 'info';
    case 'refresher':  return 'neutral';
    case 'emergency':  return 'danger';
    case 'toolbox':    return 'warning';
    case 'specialist': return 'success';
    default:           return 'neutral';
  }
}

function typeLabel(type) {
  switch (type) {
    case 'induction':  return 'Induction';
    case 'refresher':  return 'Refresher';
    case 'emergency':  return 'Emergency';
    case 'toolbox':    return 'Toolbox Talk';
    case 'specialist': return 'Specialist';
    default:           return type ?? '—';
  }
}

function AssignForm({ training, employees, canAssign }) {
  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({
      value: String(e.id),
      label: e.name ?? e.user?.name ?? `#${e.id}`,
    })),
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    training_id:  String(training.id),
    employee_id:  '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.safety.training.store'), {
      preserveScroll: true,
      onSuccess: () => reset('employee_id'),
    });
  }

  if (!canAssign) return null;

  return (
    <form onSubmit={submit}>
      <HStack gap={2} align="center">
        <Field error={errors.employee_id}>
          <Select
            options={employeeOptions}
            value={data.employee_id}
            onChange={e => setData('employee_id', e.target.value)}
          />
        </Field>
        <Button type="submit" intent="soft" size="sm" loading={processing}>
          Assign
        </Button>
      </HStack>
    </form>
  );
}

export default function SafetyTrainingIndex({ trainings, employees, filters }) {
  const canAssign = useHRMAC('hrm.safety.safety-training.create');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.safety.training.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = trainings.last_page    ?? 1;
  const currentPage = trainings.current_page ?? 1;

  const columns = [
    {
      key: 'title', label: 'Title',
      render: row => <Text>{row.title ?? '—'}</Text>,
    },
    {
      key: 'type', label: 'Type',
      render: row => (
        <Badge intent={typeIntent(row.type)}>
          {typeLabel(row.type)}
        </Badge>
      ),
    },
    {
      key: 'duration_minutes', label: 'Duration',
      render: row => <Text>{row.duration_minutes ? `${row.duration_minutes} min` : '—'}</Text>,
    },
    {
      key: 'is_mandatory', label: 'Mandatory',
      render: row => (
        <Badge intent={row.is_mandatory ? 'warning' : 'neutral'}>
          {row.is_mandatory ? 'Mandatory' : 'Optional'}
        </Badge>
      ),
    },
    {
      key: 'is_active', label: 'Status',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'assign', label: 'Assign',
      render: row => (
        <AssignForm
          training={row}
          employees={employees}
          canAssign={canAssign}
        />
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Safety Training"
      breadcrumb={[{ label: 'HRM' }, { label: 'Safety' }, { label: 'Training' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Select
            options={TYPE_OPTIONS}
            value={filters?.type ?? ''}
            onChange={e => applyFilters({ type: e.target.value, page: 1 })}
          />
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={trainings.data ?? []}
          empty="No safety training programs found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => applyFilters({ page })}
          />
        )
      }
    />
  );
}

SafetyTrainingIndex.layout = page => <App title="Safety Training">{page}</App>;
