import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Input,
  Select,
  Modal,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC';

export default function WorkflowApprovalsIndex({ approvals, filters }) {
  const canApprove = useHRMAC('workflow.workflows.approvals.approve');
  const canReject = useHRMAC('workflow.workflows.approvals.reject');
  const canEscalate = useHRMAC('workflow.workflows.approvals.escalate');
  const [showApproveDialog, setShowApproveDialog] = useState(null);
  const [showRejectDialog, setShowRejectDialog] = useState(null);

  const approveForm = useForm({ comment: '' });
  const rejectForm = useForm({ reason: '' });

  const handleApprove = (approval) => {
    approveForm.post(route('workflow-instances.approve', approval.id), {
      onSuccess: () => {
        setShowApproveDialog(null);
        approveForm.reset();
      },
    });
  };

  const handleReject = (approval) => {
    rejectForm.post(route('workflow-instances.reject', approval.id), {
      onSuccess: () => {
        setShowRejectDialog(null);
        rejectForm.reset();
      },
    });
  };

  const handleEscalate = (approval) => {
    router.post(route('workflow-instances.escalate', approval.id), {}, {
      onSuccess: () => router.reload(),
    });
  };

  const columns = [
    {
      key: 'workflow',
      label: 'Workflow',
      render: (row) => (
        <Text weight="semibold">{row.workflow?.name}</Text>
      ),
    },
    {
      key: 'entity',
      label: 'Entity',
      render: (row) => (
        <Badge variant="secondary">{row.entity_type}</Badge>
      ),
    },
    {
      key: 'current_step',
      label: 'Current Step',
      render: (row) => row.current_step?.name || '—',
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge variant="warning">Pending</Badge>
      ),
    },
    {
      key: 'initiated_by',
      label: 'Initiated By',
      render: (row) => row.initiated_by?.name || '—',
    },
    {
      key: 'created_at',
      label: 'Started',
      render: (row) => new Date(row.started_at).toLocaleDateString(),
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canApprove && (
            <Button variant="success" size="sm" onClick={() => setShowApproveDialog(row)}>
              Approve
            </Button>
          )}
          {canEscalate && (
            <Button variant="warning" size="sm" onClick={() => handleEscalate(row)}>
              Escalate
            </Button>
          )}
          {canReject && (
            <Button variant="danger" size="sm" onClick={() => setShowRejectDialog(row)}>
              Reject
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="My Approvals"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Workflows', href: route('workflows.index') },
        { label: 'Approvals' },
      ]}
      description="Review and manage pending workflow approvals."
    >
      <VStack gap={4}>
        {/* Filters */}
        <HStack gap={3} align="end">
          <Input
            placeholder="Search approvals..."
            value={filters.search || ''}
            onChange={(e) => router.get(route('workflow-instances.approvals'), { search: e.target.value }, {
              preserveState: true,
              preserveScroll: true,
            })}
            className="flex-1"
          />
          <Select
            placeholder="All Entity Types"
            value={filters.entity_type || ''}
            onChange={(e) => router.get(route('workflow-instances.approvals'), { entity_type: e.target.value }, {
              preserveState: true,
              preserveScroll: true,
            })}
            options={[
              { value: '', label: 'All Entity Types' },
              { value: 'leave_request', label: 'Leave Request' },
              { value: 'expense_claim', label: 'Expense Claim' },
              { value: 'purchase_requisition', label: 'Purchase Requisition' },
            ]}
          />
        </HStack>

        {/* Table */}
        <DataTable
          columns={columns}
          rows={approvals?.data || []}
          empty="No pending approvals found."
        />

        {/* Pagination */}
        {approvals?.last_page > 1 && (
          <div className="flex justify-center">
            <Text size="sm">
              Page {approvals?.current_page} of {approvals?.last_page}
            </Text>
          </div>
        )}
      </VStack>

      {/* Approve Dialog */}
      <Modal
        open={!!showApproveDialog}
        onClose={() => setShowApproveDialog(null)}
        title="Approve Workflow"
        footer={
          <>
            <Button variant="ghost" onClick={() => setShowApproveDialog(null)}>
              Cancel
            </Button>
            <Button variant="primary" onClick={() => handleApprove(showApproveDialog)} disabled={approveForm.processing}>
              {approveForm.processing ? 'Approving...' : 'Approve'}
            </Button>
          </>
        }
      >
        <form onSubmit={(e) => { e.preventDefault(); handleApprove(showApproveDialog); }}>
          <VStack gap={4}>
            <div>
              <label className="block text-sm font-medium mb-1">Comment (optional)</label>
              <Input
                value={approveForm.data.comment}
                onChange={(e) => approveForm.setData('comment', e.target.value)}
                placeholder="Add a comment"
              />
            </div>
          </VStack>
        </form>
      </Modal>

      {/* Reject Dialog */}
      <Modal
        open={!!showRejectDialog}
        onClose={() => setShowRejectDialog(null)}
        title="Reject Workflow"
        footer={
          <>
            <Button variant="ghost" onClick={() => setShowRejectDialog(null)}>
              Cancel
            </Button>
            <Button variant="danger" onClick={() => handleReject(showRejectDialog)} disabled={rejectForm.processing}>
              {rejectForm.processing ? 'Rejecting...' : 'Reject'}
            </Button>
          </>
        }
      >
        <form onSubmit={(e) => { e.preventDefault(); handleReject(showRejectDialog); }}>
          <VStack gap={4}>
            <div>
              <label className="block text-sm font-medium mb-1">Reason *</label>
              <Input
                value={rejectForm.data.reason}
                onChange={(e) => rejectForm.setData('reason', e.target.value)}
                placeholder="Reason for rejection"
                required
              />
            </div>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

WorkflowApprovalsIndex.layout = page => <App title="My Approvals">{page}</App>;
