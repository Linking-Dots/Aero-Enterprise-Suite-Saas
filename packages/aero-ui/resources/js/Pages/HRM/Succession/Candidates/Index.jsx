import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge,
  Button, Card, CardBody, Field, Input, Select, Alert, DataTable,
  Pagination, ConfirmDialog,
} from '@aero/ui';

const READINESS_OPTIONS = [
  { value: '',           label: 'Select readiness' },
  { value: 'ready_now',  label: 'Ready Now' },
  { value: '1_2_years',  label: '1-2 Years' },
  { value: '3_5_years',  label: '3-5 Years' },
];

const READINESS_INTENT = {
  ready_now:  'success',
  '1_2_years': 'warning',
  '3_5_years': 'neutral',
};

function readinessLabel(v) {
  return READINESS_OPTIONS.find(o => o.value === v)?.label ?? v;
}

export default function CandidatesIndex({ candidates, roles, employees, filters }) {
  const canManage = useHRMAC('hrm.succession-planning.succession-candidates.manage');

  const [deleteTarget, setDeleteTarget] = useState(null);

  const roleOptions = [
    { value: '', label: 'Select role' },
    ...(roles ?? []).map(r => ({ value: String(r.id), label: r.name })),
  ];

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({
      value: String(e.id),
      label: `${e.first_name} ${e.last_name}`,
    })),
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    role_id:     '',
    employee_id: '',
    readiness:   '',
    notes:       '',
  });

  function nominate(e) {
    e.preventDefault();
    post(route('hrm.succession-planning.candidates.store'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  }

  function executeDelete() {
    router.post(
      route('hrm.succession-planning.candidates.destroy', deleteTarget.id),
      { _method: 'DELETE' },
      { preserveState: true, preserveScroll: true, onFinish: () => setDeleteTarget(null) }
    );
  }

  const hasFormErrors = Object.keys(errors).length > 0;

  const columns = [
    {
      key: 'role',
      label: 'Role',
      render: row => row.role?.name ?? '—',
    },
    {
      key: 'employee',
      label: 'Employee',
      render: row => row.employee
        ? `${row.employee.first_name} ${row.employee.last_name}`
        : '—',
    },
    {
      key: 'readiness',
      label: 'Readiness',
      render: row => (
        <Badge intent={READINESS_INTENT[row.readiness] ?? 'neutral'}>
          {readinessLabel(row.readiness)}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: row => (
        canManage ? (
          <Button
            intent="danger"
            size="sm"
            onClick={() => setDeleteTarget(row)}
          >
            Delete
          </Button>
        ) : null
      ),
    },
  ];

  const totalPages  = candidates.last_page    ?? 1;
  const currentPage = candidates.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Succession Candidates"
        breadcrumb={[{ label: 'HRM' }, { label: 'Succession' }, { label: 'Candidates' }]}
        table={
          <VStack gap={5}>

            {/* Nomination Form */}
            {canManage && (
              <Card>
                <CardBody>
                  <form onSubmit={nominate}>
                    <VStack gap={4}>
                      <Eyebrow>Nominate Candidate</Eyebrow>
                      {hasFormErrors && (
                        <Alert intent="danger" title="Please fix the errors below." />
                      )}
                      <HStack gap={3} wrap>
                        <Field label="Role" error={errors.role_id} required>
                          <Select
                            options={roleOptions}
                            value={data.role_id}
                            onChange={e => setData('role_id', e.target.value)}
                          />
                        </Field>
                        <Field label="Employee" error={errors.employee_id} required>
                          <Select
                            options={employeeOptions}
                            value={data.employee_id}
                            onChange={e => setData('employee_id', e.target.value)}
                          />
                        </Field>
                        <Field label="Readiness" error={errors.readiness} required>
                          <Select
                            options={READINESS_OPTIONS}
                            value={data.readiness}
                            onChange={e => setData('readiness', e.target.value)}
                          />
                        </Field>
                      </HStack>
                      <Field label="Notes" error={errors.notes}>
                        <Input
                          value={data.notes}
                          onChange={e => setData('notes', e.target.value)}
                          placeholder="Optional notes about this nomination"
                        />
                      </Field>
                      <Box>
                        <Button type="submit" intent="primary" loading={processing}>
                          Nominate
                        </Button>
                      </Box>
                    </VStack>
                  </form>
                </CardBody>
              </Card>
            )}

            {/* Candidates Table */}
            <DataTable
              columns={columns}
              rows={candidates.data ?? []}
              empty="No succession candidates nominated yet."
            />
          </VStack>
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(
                  route('hrm.succession-planning.candidates.index'),
                  { ...filters, page },
                  { preserveState: true }
                )
              }
            />
          )
        }
      />

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={executeDelete}
        title="Delete Succession Candidate"
        description={
          deleteTarget
            ? `Remove ${deleteTarget.employee?.first_name} ${deleteTarget.employee?.last_name} as a succession candidate for ${deleteTarget.role?.name ?? 'this role'}?`
            : ''
        }
        confirmLabel="Delete"
        intent="danger"
      />
    </>
  );
}

CandidatesIndex.layout = page => <App title="Succession Candidates">{page}</App>;
