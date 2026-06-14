import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text, Mono,
  Input,
  Select,
  Field,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function WorkflowApprovalsIndex({ approvals, filters = {} }) {
  const toast       = useToast();
  const canApprove  = useHRMAC('workflow.workflows.approvals.approve');
  const canReject   = useHRMAC('workflow.workflows.approvals.reject');
  const canEscalate = useHRMAC('workflow.workflows.approvals.escalate');

  const [search,      setSearch]      = useState(filters.search      || '');
  const [entityType,  setEntityType]  = useState(filters.entity_type || '');
  const [approveRow,  setApproveRow]  = useState(null);
  const [rejectRow,   setRejectRow]   = useState(null);

  const approveForm = useForm({ comment: '' });
  const rejectForm  = useForm({ reason:  '' });

  const applyFilters = () => {
    router.get(route('workflow-instances.approvals'), { search, entity_type: entityType }, {
      preserveState: true, preserveScroll: true, only: ['approvals', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setEntityType('');
    router.get(route('workflow-instances.approvals'), {}, {
      preserveState: true, preserveScroll: true, only: ['approvals', 'filters'],
    });
  };

  const handleApprove = (e) => {
    e.preventDefault();
    approveForm.post(route('workflow-instances.approve', approveRow.id), {
      onSuccess: () => { setApproveRow(null); approveForm.reset(); toast.success('Approved.'); },
      onError:   () => toast.error('Failed to approve.'),
    });
  };

  const handleReject = (e) => {
    e.preventDefault();
    rejectForm.post(route('workflow-instances.reject', rejectRow.id), {
      onSuccess: () => { setRejectRow(null); rejectForm.reset(); toast.success('Rejected.'); },
      onError:   () => toast.error('Failed to reject.'),
    });
  };

  const handleEscalate = (row) => {
    if (!confirm('Escalate this approval?')) return;
    router.post(route('workflow-instances.escalate', row.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Escalated.'),
      onError:   () => toast.error('Failed to escalate.'),
    });
  };

  const waitingSince = (dateStr) => {
    if (!dateStr) return '—';
    const ms   = Date.now() - new Date(dateStr).getTime();
    const days = Math.floor(ms / 86400000);
    return days === 0 ? 'Today' : `${days}d ago`;
  };

  const columns = [
    {
      key: 'workflow', label: 'Workflow', width: '22%',
      render: (row) => <Text size="sm">{row.workflow?.name || '—'}</Text>,
    },
    {
      key: 'requested_by', label: 'Requested By', width: '18%',
      render: (row) => <Text size="sm">{row.initiated_by?.name || row.requested_by?.name || '—'}</Text>,
    },
    {
      key: 'current_step', label: 'Current Step', width: '18%',
      render: (row) => <Text size="sm">{row.current_step?.name || '—'}</Text>,
    },
    {
      key: 'waiting_since', label: 'Waiting Since', width: '14%',
      render: (row) => (
        <Mono size="sm" tone="secondary">{waitingSince(row.started_at || row.created_at)}</Mono>
      ),
    },
    {
      key: 'actions', label: '', width: '28%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canApprove && (
            <Button intent="soft" size="sm" leftIcon="check" onClick={() => setApproveRow(row)}>
              Approve
            </Button>
          )}
          {canEscalate && (
            <Button intent="ghost" size="sm" leftIcon="arrowUp" onClick={() => handleEscalate(row)}>
              Escalate
            </Button>
          )}
          {canReject && (
            <Button intent="danger" size="sm" leftIcon="xMark" onClick={() => setRejectRow(row)}>
              Reject
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="My Approvals"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Workflows', href: route('workflows.index') },
          { label: 'Approvals' },
        ]}
        description="Review and manage pending workflow approvals."
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search approvals…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()}
              leftIcon="search"
            />
            <Select
              value={entityType}
              onChange={e => setEntityType(e.target.value)}
              options={[
                { value: '',                    label: 'All Entity Types' },
                { value: 'leave_request',       label: 'Leave Request' },
                { value: 'expense_claim',       label: 'Expense Claim' },
                { value: 'purchase_requisition',label: 'Purchase Requisition' },
              ]}
            />
            <Button intent="primary" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={approvals?.data || []}
            empty="No pending approvals found."
          />
        }
        pagination={
          approvals?.last_page > 1 && (
            <Pagination
              page={approvals.current_page}
              total={approvals.last_page}
              onChange={page => router.get(route('workflow-instances.approvals'), { page, search, entity_type: entityType }, {
                preserveState: true, preserveScroll: true, only: ['approvals'],
              })}
            />
          )
        }
      />

      {/* Approve Modal */}
      <Modal
        open={!!approveRow}
        onClose={() => setApproveRow(null)}
        title="Approve"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setApproveRow(null)}>Cancel</Button>
            <Button intent="primary" loading={approveForm.processing} onClick={handleApprove}>
              Confirm Approval
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleApprove}>
          <Field label="Comment (optional)" htmlFor="approve-comment" error={approveForm.errors.comment}>
            <Input
              id="approve-comment"
              value={approveForm.data.comment}
              onChange={e => approveForm.setData('comment', e.target.value)}
              placeholder="Add a comment"
            />
          </Field>
        </form>
      </Modal>

      {/* Reject Modal */}
      <Modal
        open={!!rejectRow}
        onClose={() => setRejectRow(null)}
        title="Reject"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setRejectRow(null)}>Cancel</Button>
            <Button intent="danger" loading={rejectForm.processing} onClick={handleReject}>
              Confirm Rejection
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleReject}>
          <Field label="Reason" htmlFor="reject-reason" error={rejectForm.errors.reason} required>
            <Input
              id="reject-reason"
              value={rejectForm.data.reason}
              onChange={e => rejectForm.setData('reason', e.target.value)}
              placeholder="Reason for rejection"
              error={!!rejectForm.errors.reason}
            />
          </Field>
        </form>
      </Modal>
    </>
  );
}

WorkflowApprovalsIndex.layout = page => <App title="My Approvals">{page}</App>;
