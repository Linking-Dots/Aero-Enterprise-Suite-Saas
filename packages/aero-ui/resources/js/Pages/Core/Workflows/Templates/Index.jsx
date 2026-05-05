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
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC';

export default function WorkflowTemplatesIndex({ templates, filters }) {
  const canCreate = useHRMAC('workflow.workflows.templates.create');
  const canDelete = useHRMAC('workflow.workflows.templates.delete');
  const [showCreateModal, setShowCreateModal] = useState(false);

  const form = useForm({
    name: '',
    code: '',
    description: '',
    entity_type: 'leave_request',
    steps_config: [],
    is_active: true,
  });

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('workflow-templates.store'), {
      onSuccess: () => {
        setShowCreateModal(false);
        form.reset();
      },
    });
  };

  const handleDelete = (template) => {
    if (template.is_system) {
      alert('System templates cannot be deleted');
      return;
    }
    if (!confirm(`Delete template "${template.name}"?`)) return;
    router.delete(route('workflow-templates.destroy', template.id), {
      onSuccess: () => router.reload(),
    });
  };

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <HStack gap={2} align="center">
          <Text weight="semibold">{row.name}</Text>
          {row.is_system && <Badge variant="info" size="sm">System</Badge>}
        </HStack>
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
      key: 'workflows',
      label: 'Used By',
      render: (row) => row.workflows?.length || 0,
    },
    {
      key: 'actions',
      label: '',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {!row.is_system && canDelete && (
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
      title="Workflow Templates"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Workflows', href: route('workflows.index') },
        { label: 'Templates' },
      ]}
      description="Manage reusable workflow templates for quick workflow creation."
      actions={
        canCreate && (
          <Button variant="primary" onClick={() => setShowCreateModal(true)}>
            Create Template
          </Button>
        )
      }
    >
      <VStack gap={4}>
        {/* Filters */}
        <HStack gap={3} align="end">
          <Input
            placeholder="Search templates..."
            value={filters.search || ''}
            onChange={(e) => router.get(route('workflow-templates.index'), { search: e.target.value }, {
              preserveState: true,
              preserveScroll: true,
            })}
            className="flex-1"
          />
          <Select
            placeholder="All Entity Types"
            value={filters.entity_type || ''}
            onChange={(e) => router.get(route('workflow-templates.index'), { entity_type: e.target.value }, {
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
          rows={templates?.data || []}
          empty="No templates found."
        />

        {/* Pagination */}
        {templates?.last_page > 1 && (
          <div className="flex justify-center">
            <Text size="sm">
              Page {templates?.current_page} of {templates?.last_page}
            </Text>
          </div>
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreateModal}
        onClose={() => setShowCreateModal(false)}
        title="Create Workflow Template"
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
                placeholder="Template name"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Code</label>
              <Input
                value={form.data.code}
                onChange={(e) => form.setData('code', e.target.value)}
                placeholder="unique_template_code"
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
                placeholder="Template description"
              />
            </div>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

WorkflowTemplatesIndex.layout = page => <App title="Workflow Templates">{page}</App>;
