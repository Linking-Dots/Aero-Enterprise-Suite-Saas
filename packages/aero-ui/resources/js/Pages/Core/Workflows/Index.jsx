import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
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
import App from '../../App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC';

export default function WorkflowsIndex({ workflows, templates, filters }) {
  const canCreate = useHRMAC('workflow.workflows.definitions.create');
  const canActivate = useHRMAC('workflow.workflows.definitions.activate');
  const canDeactivate = useHRMAC('workflow.workflows.definitions.deactivate');
  const canDelete = useHRMAC('workflow.workflows.definitions.delete');
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState(null);

  const form = useForm({
    name: '',
    code: '',
    description: '',
    entity_type: 'leave_request',
    trigger_config: { type: 'manual' },
    template_id: '',
    steps: [],
  });

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('workflows.store'), {
      onSuccess: () => {
        setShowCreateModal(false);
        form.reset();
        setSelectedTemplate(null);
      },
    });
  };

  const handleActivate = (workflow) => {
    router.post(route('workflows.activate', workflow.id), {}, {
      onSuccess: () => router.reload(),
    });
  };

  const handleDeactivate = (workflow) => {
    router.post(route('workflows.deactivate', workflow.id), {}, {
      onSuccess: () => router.reload(),
    });
  };

  const handleDelete = (workflow) => {
    if (!confirm(`Delete workflow "${workflow.name}"?`)) return;
    router.delete(route('workflows.destroy', workflow.id), {
      onSuccess: () => router.reload(),
    });
  };

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <Text weight="semibold">{row.name}</Text>
      ),
    },
    {
      key: 'code',
      label: 'Code',
      render: (row) => row.code,
    },
    {
      key: 'entity_type',
      label: 'Entity Type',
      render: (row) => (
        <Badge variant="secondary">{row.entity_type}</Badge>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge variant={row.is_active ? 'success' : 'warning'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'steps',
      label: 'Steps',
      render: (row) => row.steps?.length || 0,
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {row.is_active ? (
            canDeactivate && (
              <Button variant="warning" size="sm" onClick={() => handleDeactivate(row)}>
                Deactivate
              </Button>
            )
          ) : (
            canActivate && (
              <Button variant="success" size="sm" onClick={() => handleActivate(row)}>
                Activate
              </Button>
            )
          )}
          {canDelete && (
            <Button variant="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Workflows"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Workflows' },
      ]}
      description="Manage approval workflows and automation rules."
      actions={
        canCreate && (
          <Button variant="primary" onClick={() => setShowCreateModal(true)}>
            Create Workflow
          </Button>
        )
      }
    >
      <VStack gap={4}>
        {/* Filters */}
        <HStack gap={3} align="end">
          <Input
            placeholder="Search workflows..."
            value={filters.search || ''}
            onChange={(e) => router.get(route('workflows.index'), { search: e.target.value }, {
              preserveState: true,
              preserveScroll: true,
            })}
            className="flex-1"
          />
          <Select
            placeholder="All Entity Types"
            value={filters.entity_type || ''}
            onChange={(e) => router.get(route('workflows.index'), { entity_type: e.target.value }, {
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
          rows={workflows?.data || []}
          empty="No workflows found."
        />

        {/* Pagination */}
        {workflows?.last_page > 1 && (
          <div className="flex justify-center">
            <Text size="sm">
              Page {workflows?.current_page} of {workflows?.last_page}
            </Text>
          </div>
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        title="Create Workflow"
        footer={
          <>
            <Button variant="ghost" onClick={() => setShowCreateModal(false)}>
              Cancel
            </Button>
            <Button variant="primary" onClick={handleCreate} disabled={form.processing}>
              {form.processing ? 'Creating...' : 'Create'}
            </Button>
          </>
        }
      >
        <form onSubmit={handleCreate}>
          <VStack gap={4}>
            <div>
              <label className="block text-sm font-medium mb-1">Name</label>
              <Input
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                placeholder="Workflow name"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Code</label>
              <Input
                value={form.data.code}
                onChange={(e) => form.setData('code', e.target.value)}
                placeholder="unique_workflow_code"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Entity Type</label>
              <Select
                value={form.data.entity_type}
                onChange={(e) => form.setData('entity_type', e.target.value)}
                options={[
                  { value: 'leave_request', label: 'Leave Request' },
                  { value: 'expense_claim', label: 'Expense Claim' },
                  { value: 'purchase_requisition', label: 'Purchase Requisition' },
                ]}
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Description (optional)</label>
              <Input
                value={form.data.description}
                onChange={(e) => form.setData('description', e.target.value)}
                placeholder="Workflow description"
              />
            </div>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

WorkflowsIndex.layout = page => <App title="Workflows">{page}</App>;
