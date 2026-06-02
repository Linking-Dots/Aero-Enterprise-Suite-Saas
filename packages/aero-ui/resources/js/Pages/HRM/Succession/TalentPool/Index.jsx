import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge,
  Button, Card, CardBody, Field, Select, Alert, DataTable, Pagination,
  ConfirmDialog,
} from '@aero/ui';

const TIER_OPTIONS = [
  { value: '',               label: 'Select tier' },
  { value: 'high_potential', label: 'High Potential' },
  { value: 'key_talent',    label: 'Key Talent' },
  { value: 'emerging',      label: 'Emerging' },
];

const TIER_INTENT = {
  high_potential: 'success',
  key_talent:     'primary',
  emerging:       'warning',
};

function tierLabel(tier) {
  return TIER_OPTIONS.find(o => o.value === tier)?.label ?? tier;
}

export default function TalentPoolIndex({ members, employees }) {
  const canManage = useHRMAC('hrm.succession-planning.succession-candidates.manage');

  const [removeTarget, setRemoveTarget] = useState(null);

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({
      value: String(e.id),
      label: `${e.first_name} ${e.last_name}`,
    })),
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    employee_id: '',
    tier:        '',
  });

  function addMember(e) {
    e.preventDefault();
    post(route('hrm.succession-planning.talent-pool.add'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  }

  function executeRemove() {
    router.post(
      route('hrm.succession-planning.talent-pool.remove'),
      { employee_id: removeTarget.employee.id },
      { preserveState: true, preserveScroll: true, onFinish: () => setRemoveTarget(null) }
    );
  }

  const columns = [
    {
      key: 'employee',
      label: 'Employee',
      render: row => (
        <Text>{row.employee?.first_name} {row.employee?.last_name}</Text>
      ),
    },
    {
      key: 'tier',
      label: 'Tier',
      render: row => (
        <Badge intent={TIER_INTENT[row.tier] ?? 'neutral'}>
          {tierLabel(row.tier)}
        </Badge>
      ),
    },
    {
      key: 'created_at',
      label: 'Added',
      render: row => row.created_at ?? '—',
    },
    {
      key: 'actions',
      label: '',
      render: row => (
        canManage ? (
          <Button
            intent="danger"
            size="sm"
            onClick={() => setRemoveTarget(row)}
          >
            Remove
          </Button>
        ) : null
      ),
    },
  ];

  const totalPages  = members.last_page    ?? 1;
  const currentPage = members.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Talent Pool"
        breadcrumb={[{ label: 'HRM' }, { label: 'Succession' }, { label: 'Talent Pool' }]}
        table={
          <VStack gap={5}>
            {canManage && (
              <Card>
                <CardBody>
                  <form onSubmit={addMember}>
                    <VStack gap={4}>
                      <Eyebrow>Add to Talent Pool</Eyebrow>
                      {(errors.employee_id || errors.tier) && (
                        <Alert intent="danger" title="Please fix the errors below." />
                      )}
                      <HStack gap={3} wrap>
                        <Field label="Employee" error={errors.employee_id} required>
                          <Select
                            options={employeeOptions}
                            value={data.employee_id}
                            onChange={e => setData('employee_id', e.target.value)}
                          />
                        </Field>
                        <Field label="Tier" error={errors.tier} required>
                          <Select
                            options={TIER_OPTIONS}
                            value={data.tier}
                            onChange={e => setData('tier', e.target.value)}
                          />
                        </Field>
                        <Box>
                          <Button type="submit" intent="primary" loading={processing}>
                            Add Member
                          </Button>
                        </Box>
                      </HStack>
                    </VStack>
                  </form>
                </CardBody>
              </Card>
            )}

            <DataTable
              columns={columns}
              rows={members.data ?? []}
              empty="No members in the talent pool yet."
            />
          </VStack>
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(route('hrm.succession-planning.talent-pool.index'), { page }, { preserveState: true })
              }
            />
          )
        }
      />

      <ConfirmDialog
        open={!!removeTarget}
        onClose={() => setRemoveTarget(null)}
        onConfirm={executeRemove}
        title="Remove from Talent Pool"
        description={
          removeTarget
            ? `Remove ${removeTarget.employee?.first_name} ${removeTarget.employee?.last_name} from the talent pool?`
            : ''
        }
        confirmLabel="Remove"
        intent="danger"
      />
    </>
  );
}

TalentPoolIndex.layout = page => <App title="Talent Pool">{page}</App>;
