import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge, Button,
  Card, CardBody, DataTable, Field, Select, Textarea, Alert,
} from '@aero/ui';

const CONDITION_OPTIONS = [
  { value: 'good', label: 'Good' },
  { value: 'fair', label: 'Fair' },
  { value: 'poor', label: 'Poor' },
];

function statusIntent(status) {
  switch (status) {
    case 'available':   return 'success';
    case 'allocated':   return 'info';
    case 'maintenance': return 'warning';
    case 'retired':     return 'neutral';
    default:            return 'neutral';
  }
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function AssetsShow({ asset, employees }) {
  const canAllocate = useHRMAC('hrm.assets.asset-allocations.assign');
  const canReturn   = useHRMAC('hrm.assets.asset-allocations.return');

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({ value: String(e.id), label: e.user?.name ?? `Employee #${e.id}` })),
  ];

  const allocateForm = useForm({
    employee_id: '',
    condition:   'good',
    notes:       '',
  });

  const returnForm = useForm({
    condition:    'good',
    return_notes: '',
  });

  const [showAllocate, setShowAllocate] = useState(false);
  const [showReturn,   setShowReturn]   = useState(false);

  function submitAllocate(e) {
    e.preventDefault();
    allocateForm.post(route('hrm.assets.allocations.store', asset.id), {
      onSuccess: () => {
        setShowAllocate(false);
        allocateForm.reset();
      },
    });
  }

  function submitReturn(e) {
    e.preventDefault();
    returnForm.post(route('hrm.assets.allocations.return', asset.currentAllocation?.id), {
      onSuccess: () => {
        setShowReturn(false);
        returnForm.reset();
      },
    });
  }

  const allocationColumns = [
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'allocated_at', label: 'Allocated At',
      render: row => <Text>{fmtDate(row.allocated_at)}</Text>,
    },
    {
      key: 'returned_at', label: 'Returned At',
      render: row => row.returned_at
        ? <Text>{fmtDate(row.returned_at)}</Text>
        : <Badge intent="info">Active</Badge>,
    },
    {
      key: 'condition', label: 'Condition',
      render: row => <Text>{row.condition ?? '—'}</Text>,
    },
  ];

  return (
    <DetailPageLayout
      title={asset.name}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Assets' },
        { label: 'Inventory', href: route('hrm.assets.index') },
        { label: asset.name },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.assets.index'))}
          >
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={5}>

        {/* Meta strip */}
        <HStack gap={3} wrap>
          <Badge intent={statusIntent(asset.status)}>{asset.status ?? '—'}</Badge>
          {asset.category && <Badge intent="neutral">{asset.category.name}</Badge>}
        </HStack>

        {/* Asset details */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Asset Details</Eyebrow>
              <HStack gap={6} wrap>
                <VStack gap={1}>
                  <Text tone="secondary">Tag</Text>
                  <Text>{asset.tag}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Name</Text>
                  <Text>{asset.name}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Category</Text>
                  <Text>{asset.category?.name ?? '—'}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Serial Number</Text>
                  <Text>{asset.serial_number ?? '—'}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Vendor</Text>
                  <Text>{asset.vendor ?? '—'}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Purchased On</Text>
                  <Text>{fmtDate(asset.purchased_on)}</Text>
                </VStack>
                <VStack gap={1}>
                  <Text tone="secondary">Purchase Cost</Text>
                  <Text>{asset.purchase_cost != null ? `$${asset.purchase_cost}` : '—'}</Text>
                </VStack>
              </HStack>
              {asset.notes && (
                <VStack gap={1}>
                  <Text tone="secondary">Notes</Text>
                  <Text>{asset.notes}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Current holder */}
        {asset.currentAllocation && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Current Holder</Eyebrow>
                <HStack gap={6}>
                  <VStack gap={1}>
                    <Text tone="secondary">Employee</Text>
                    <Text>{asset.currentAllocation.employee?.user?.name ?? '—'}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Allocated At</Text>
                    <Text>{fmtDate(asset.currentAllocation.allocated_at)}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Condition</Text>
                    <Text>{asset.currentAllocation.condition ?? '—'}</Text>
                  </VStack>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Allocate form */}
        {canAllocate && asset.status === 'available' && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={3} align="center">
                  <Eyebrow>Allocate Asset</Eyebrow>
                  <Box grow />
                  {!showAllocate && (
                    <Button intent="primary" size="sm" onClick={() => setShowAllocate(true)}>
                      Allocate
                    </Button>
                  )}
                </HStack>

                {showAllocate && (
                  <form onSubmit={submitAllocate}>
                    <VStack gap={4}>
                      <Field label="Employee" error={allocateForm.errors.employee_id} required>
                        <Select
                          options={employeeOptions}
                          value={allocateForm.data.employee_id}
                          onChange={e => allocateForm.setData('employee_id', e.target.value)}
                        />
                      </Field>
                      <Field label="Condition" error={allocateForm.errors.condition}>
                        <Select
                          options={CONDITION_OPTIONS}
                          value={allocateForm.data.condition}
                          onChange={e => allocateForm.setData('condition', e.target.value)}
                        />
                      </Field>
                      <Field label="Notes" error={allocateForm.errors.notes} hint="Optional">
                        <Textarea
                          value={allocateForm.data.notes}
                          onChange={e => allocateForm.setData('notes', e.target.value)}
                          placeholder="Any notes about this allocation..."
                          rows={2}
                        />
                      </Field>
                      <HStack gap={2}>
                        <Button type="submit" intent="primary" loading={allocateForm.processing}>
                          Confirm Allocation
                        </Button>
                        <Button type="button" intent="ghost" onClick={() => { setShowAllocate(false); allocateForm.reset(); }}>
                          Cancel
                        </Button>
                      </HStack>
                    </VStack>
                  </form>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Return form */}
        {canReturn && asset.status === 'allocated' && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={3} align="center">
                  <Eyebrow>Return Asset</Eyebrow>
                  <Box grow />
                  {!showReturn && (
                    <Button intent="soft" size="sm" onClick={() => setShowReturn(true)}>
                      Return
                    </Button>
                  )}
                </HStack>

                {showReturn && (
                  <form onSubmit={submitReturn}>
                    <VStack gap={4}>
                      <Field label="Condition on Return" error={returnForm.errors.condition}>
                        <Select
                          options={CONDITION_OPTIONS}
                          value={returnForm.data.condition}
                          onChange={e => returnForm.setData('condition', e.target.value)}
                        />
                      </Field>
                      <Field label="Return Notes" error={returnForm.errors.return_notes} hint="Optional">
                        <Textarea
                          value={returnForm.data.return_notes}
                          onChange={e => returnForm.setData('return_notes', e.target.value)}
                          placeholder="Any notes about the return..."
                          rows={2}
                        />
                      </Field>
                      <HStack gap={2}>
                        <Button type="submit" intent="primary" loading={returnForm.processing}>
                          Confirm Return
                        </Button>
                        <Button type="button" intent="ghost" onClick={() => { setShowReturn(false); returnForm.reset(); }}>
                          Cancel
                        </Button>
                      </HStack>
                    </VStack>
                  </form>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Allocation history */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Allocation History</Eyebrow>
              <DataTable
                columns={allocationColumns}
                rows={asset.allocations ?? []}
                empty="No allocation history."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </DetailPageLayout>
  );
}

AssetsShow.layout = page => <App title="Asset Detail">{page}</App>;
