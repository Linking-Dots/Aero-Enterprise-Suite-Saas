import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Input,
  Select,
  Field,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function WorkflowsIndex({ workflows, filters = {} }) {
  const toast         = useToast();
  const canCreate     = useHRMAC('workflow.workflows.definitions.create');
  const canActivate   = useHRMAC('workflow.workflows.definitions.activate');
  const canDeactivate = useHRMAC('workflow.workflows.definitions.deactivate');
  const canDelete     = useHRMAC('workflow.workflows.definitions.delete');

  const [search,      setSearch]     = useState(filters.search       || '');
  const [triggerType, setTriggerType]= useState(filters.trigger_type || '');
  const [showCreate,  setShowCreate] = useState(false);

  const form = useForm({ name: '', description: '' });

  const applyFilters = () => {
    router.get(route('workflows.index'), { search, trigger_type: triggerType }, {
      preserveState: true, preserveScroll: true, only: ['workflows', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setTriggerType('');
    router.get(route('workflows.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['workflows', 'filters'],
    });
  };

  const handleActivate = (workflow) => {
    router.post(route('workflows.activate', workflow.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success(`"${workflow.name}" activated.`),
      onError:   () => toast.error('Failed to activate workflow.'),
    });
  };

  const handleDeactivate = (workflow) => {
    router.post(route('workflows.deactivate', workflow.id), {}, {
      preserveState: true,
      onSuccess: () => toast.success(`"${workflow.name}" deactivated.`),
      onError:   () => toast.error('Failed to deactivate workflow.'),
    });
  };

  const handleDelete = (workflow) => {
    if (!confirm(`Delete workflow "${workflow.name}"?`)) return;
    router.delete(route('workflows.destroy', workflow.id), {
      onSuccess: () => toast.success('Workflow deleted.'),
      onError:   () => toast.error('Failed to delete workflow.'),
    });
  };

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('workflows.store'), {
      onSuccess: () => { setShowCreate(false); form.reset(); toast.success('Workflow created.'); },
      onError:   () => toast.error('Failed to create workflow.'),
    });
  };

  const triggerBadgeIntent = (type) => {
    const map = { manual: 'neutral', automatic: 'success', scheduled: 'warning', event: 'amber' };
    return map[type] ?? 'neutral';
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '25%',
      render: (row) => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'trigger_type', label: 'Trigger', width: '15%',
      render: (row) => (
        <Badge intent={triggerBadgeIntent(row.trigger_type)}>
          {row.trigger_type || 'manual'}
        </Badge>
      ),
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'warning'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'steps_count', label: 'Steps', width: '10%',
      render: (row) => row.steps_count ?? row.steps?.length ?? 0,
    },
    {
      key: 'actions', label: '', width: '28%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {row.is_active ? (
            canDeactivate && (
              <Button intent="ghost" size="sm" onClick={() => handleDeactivate(row)}>
                Deactivate
              </Button>
            )
          ) : (
            canActivate && (
              <Button intent="soft" size="sm" onClick={() => handleActivate(row)}>
                Activate
              </Button>
            )
          )}
          {canDelete && !row.is_active && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Workflows"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Workflows' },
        ]}
        description="Manage approval workflows and automation rules."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
              Create Workflow
            </Button>
          )
        }
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search workflows…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()}
              leftIcon="search"
            />
            <Select
              value={triggerType}
              onChange={e => setTriggerType(e.target.value)}
              options={[
                { value: '',          label: 'All Trigger Types' },
                { value: 'manual',    label: 'Manual' },
                { value: 'automatic', label: 'Automatic' },
                { value: 'scheduled', label: 'Scheduled' },
                { value: 'event',     label: 'Event' },
              ]}
            />
            <Button intent="primary" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={workflows?.data || []}
            empty="No workflows found."
          />
        }
        pagination={
          workflows?.last_page > 1 && (
            <Pagination
              page={workflows.current_page}
              total={workflows.last_page}
              onChange={page => router.get(route('workflows.index'), { page, search, trigger_type: triggerType }, {
                preserveState: true, preserveScroll: true, only: ['workflows'],
              })}
            />
          )
        }
      />

      <Modal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        title="Create Workflow"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowCreate(false)}>Cancel</Button>
            <Button intent="primary" loading={form.processing} onClick={handleCreate}>
              Create
            </Button>
          </HStack>
        }
      >
        <form onSubmit={handleCreate}>
          <VStack gap={4}>
            <Field label="Name" htmlFor="wf-name" error={form.errors.name} required>
              <Input
                id="wf-name"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                placeholder="e.g. Leave Approval"
                error={!!form.errors.name}
              />
            </Field>
            <Field label="Description" htmlFor="wf-desc" error={form.errors.description}>
              <Input
                id="wf-desc"
                value={form.data.description}
                onChange={e => form.setData('description', e.target.value)}
                placeholder="Optional description"
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

WorkflowsIndex.layout = page => <App title="Workflows">{page}</App>;
