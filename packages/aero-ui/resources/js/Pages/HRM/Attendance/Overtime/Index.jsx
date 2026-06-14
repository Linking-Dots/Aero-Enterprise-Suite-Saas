import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Select,
  Pagination, Badge, Text, Mono, Modal, Textarea, Alert,
} from '@aero/ui';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const STATUS_INTENT = {
  pending:  'warning',
  approved: 'success',
  rejected: 'danger',
};

export default function OvertimeIndex({ requests, filters }) {
  const canApprove = useHRMAC('hrm.overtime.overtime-records.approve');

  const [statusFilter, setStatusFilter] = useState(filters?.status ?? '');
  const [rejectModal, setRejectModal]   = useState(false);
  const [rejectId,    setRejectId]      = useState(null);
  const [rejectNote,  setRejectNote]    = useState('');
  const [rejecting,   setRejecting]     = useState(false);

  const statusOptions = [
    { value: '',         label: 'All' },
    { value: 'pending',  label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
  ];

  function applyStatus(val) {
    setStatusFilter(val);
    router.get(
      route('hrm.attendance.overtime.index'),
      { status: val || undefined },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function approve(id) {
    router.post(route('hrm.attendance.overtime.approve', id), {}, { preserveScroll: true });
  }

  function openReject(id) {
    setRejectId(id);
    setRejectNote('');
    setRejectModal(true);
  }

  function submitReject() {
    if (!rejectId) return;
    setRejecting(true);
    router.post(
      route('hrm.attendance.overtime.reject', rejectId),
      { rejection_note: rejectNote },
      {
        preserveScroll: true,
        onFinish: () => {
          setRejecting(false);
          setRejectModal(false);
          setRejectId(null);
        },
      },
    );
  }

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'work_date', label: 'Work Date',
      render: row => <Mono>{row.work_date}</Mono>,
    },
    {
      key: 'hours', label: 'Hours',
      render: row => <Mono>{row.hours}</Mono>,
    },
    {
      key: 'reason', label: 'Reason',
      render: row => <Text tone="secondary">{row.reason ?? '—'}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : '—'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => {
        if (row.status !== 'pending' || !canApprove) return null;
        return (
          <HStack gap={1}>
            <Button
              intent="soft"
              size="sm"
              onClick={() => approve(row.id)}
            >
              Approve
            </Button>
            <Button
              intent="danger"
              size="sm"
              onClick={() => openReject(row.id)}
            >
              Reject
            </Button>
          </HStack>
        );
      },
    },
  ];

  const totalPages  = requests?.last_page    ?? 1;
  const currentPage = requests?.current_page ?? 1;

  return (
    <>
      <IndexPageLayout
        title="Overtime Requests"
        breadcrumb={[{ label: 'HRM' }, { label: 'Attendance' }, { label: 'Overtime' }]}
        actions={
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.attendance.overtime.create'))}
            leftIcon="plus"
          >
            Request OT
          </Button>
        }
        filters={
          <HStack gap={3}>
            <Field label="Status">
              <Select
                value={statusFilter}
                onChange={e => applyStatus(e.target.value)}
              >
                {statusOptions.map(o => (
                  <option key={o.value} value={o.value}>{o.label}</option>
                ))}
              </Select>
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={requests?.data ?? []}
            empty="No overtime requests found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => router.get(route('hrm.attendance.overtime.index'), { status: statusFilter || undefined, page }, { preserveState: true })}
            />
          )
        }
      />

      <Modal
        open={rejectModal}
        onClose={() => setRejectModal(false)}
        title="Reject Overtime Request"
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={rejecting}
              onClick={submitReject}
            >
              Confirm Reject
            </Button>
            <Button
              intent="ghost"
              type="button"
              onClick={() => setRejectModal(false)}
            >
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">Provide a reason for rejection (optional).</Text>
          <Field label="Rejection Note">
            <Textarea
              value={rejectNote}
              onChange={e => setRejectNote(e.target.value)}
              placeholder="Enter reason for rejection"
              rows={3}
            />
          </Field>
        </VStack>
      </Modal>
    </>
  );
}

OvertimeIndex.layout = page => <App title="Overtime Requests">{page}</App>;
