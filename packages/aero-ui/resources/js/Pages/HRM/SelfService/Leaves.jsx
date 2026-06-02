import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button,
  Card, CardBody, Field, Input, Select, DataTable, Pagination, Modal, Alert,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function statusIntent(status) {
  switch (status) {
    case 'approved':  return 'success';
    case 'rejected':  return 'danger';
    case 'pending':   return 'warning';
    case 'cancelled': return 'neutral';
    default:          return 'neutral';
  }
}

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function SelfServiceLeaves({ leaves, leaveBalances, leaveTypes }) {
  const [applyOpen, setApplyOpen] = useState(false);
  const [cancelling, setCancelling] = useState(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    leave_type_id: '',
    start_date:    '',
    end_date:      '',
    total_days:    '',
    reason:        '',
  });

  const typeOptions = [
    { value: '', label: 'Select leave type' },
    ...(leaveTypes ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  function submitApplication(e) {
    e.preventDefault();
    post(route('hrm.self-service.leaves.store'), {
      onSuccess: () => { setApplyOpen(false); reset(); },
    });
  }

  function cancelLeave(id) {
    setCancelling(id);
    router.post(
      route('hrm.self-service.leaves.cancel', id),
      {},
      {
        preserveState:  true,
        preserveScroll: true,
        onFinish: () => setCancelling(null),
      },
    );
  }

  const totalPages  = leaves.last_page    ?? 1;
  const currentPage = leaves.current_page ?? 1;

  const columns = [
    {
      key: 'type', label: 'Type',
      render: row => <Text>{row.leaveType?.name ?? '—'}</Text>,
    },
    {
      key: 'start_date', label: 'From',
      render: row => <Text>{fmt(row.start_date)}</Text>,
    },
    {
      key: 'end_date', label: 'To',
      render: row => <Text>{fmt(row.end_date)}</Text>,
    },
    {
      key: 'total_days', label: 'Days',
      render: row => <Text>{row.total_days}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {row.status.charAt(0).toUpperCase() + row.status.slice(1)}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        row.status === 'pending' ? (
          <Button
            intent="danger"
            size="sm"
            loading={cancelling === row.id}
            onClick={() => cancelLeave(row.id)}
          >
            Cancel
          </Button>
        ) : null
      ),
    },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <HStack gap={2} align="center">
          <Box grow>
            <VStack gap={1}>
              <Eyebrow>Self-Service Portal</Eyebrow>
              <Text size="lg">My Leaves</Text>
            </VStack>
          </Box>
          <Button intent="primary" onClick={() => setApplyOpen(true)}>
            Apply for Leave
          </Button>
        </HStack>

        {/* Balance summary */}
        {(leaveBalances ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={2}>
                <Eyebrow>Leave Balances</Eyebrow>
                <div className="flex flex-wrap gap-6">
                  {(leaveBalances ?? []).map((b, i) => (
                    <div key={i} className="flex flex-col gap-1">
                      <Text tone="secondary" size="sm">{b.leaveType?.name ?? '—'}</Text>
                      <HStack gap={1} align="center">
                        <Text size="lg">{b.remaining ?? 0}</Text>
                        <Text tone="tertiary">/ {b.entitled ?? 0}</Text>
                      </HStack>
                      <Text tone="tertiary" size="sm">{b.used ?? 0} used</Text>
                    </div>
                  ))}
                </div>
              </VStack>
            </CardBody>
          </Card>
        )}

        {/* Leave table */}
        <DataTable
          columns={columns}
          rows={leaves.data ?? []}
          empty="No leave applications found."
        />

        {totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.self-service.leaves'), { page }, { preserveState: true })}
          />
        )}
      </VStack>

      {/* Apply modal */}
      <Modal
        open={applyOpen}
        onClose={() => { setApplyOpen(false); reset(); }}
        title="Apply for Leave"
        footer={
          <HStack gap={2}>
            <Button type="submit" form="leave-apply-form" intent="primary" loading={processing}>
              Submit Application
            </Button>
            <Button intent="ghost" onClick={() => { setApplyOpen(false); reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form id="leave-apply-form" onSubmit={submitApplication}>
          <VStack gap={4}>
            {Object.keys(errors).length > 0 && (
              <Alert intent="danger" title="Please fix the errors below." />
            )}

            <Field label="Leave Type" htmlFor="leave_type_id" error={errors.leave_type_id} required>
              <Select
                options={typeOptions}
                value={data.leave_type_id}
                onChange={e => setData('leave_type_id', e.target.value)}
              />
            </Field>

            <HStack gap={3}>
              <Field label="Start Date" htmlFor="start_date" error={errors.start_date} required>
                <Input
                  id="start_date"
                  type="date"
                  value={data.start_date}
                  onChange={e => setData('start_date', e.target.value)}
                />
              </Field>
              <Field label="End Date" htmlFor="end_date" error={errors.end_date} required>
                <Input
                  id="end_date"
                  type="date"
                  value={data.end_date}
                  onChange={e => setData('end_date', e.target.value)}
                />
              </Field>
            </HStack>

            <Field label="Total Days" htmlFor="total_days" error={errors.total_days} required>
              <Input
                id="total_days"
                type="number"
                min="0.5"
                step="0.5"
                value={data.total_days}
                onChange={e => setData('total_days', e.target.value)}
                placeholder="1"
              />
            </Field>

            <Field label="Reason" htmlFor="reason" error={errors.reason}>
              <Input
                id="reason"
                value={data.reason}
                onChange={e => setData('reason', e.target.value)}
                placeholder="Brief reason for leave"
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </div>
  );
}

SelfServiceLeaves.layout = page => <App title="My Leaves">{page}</App>;
