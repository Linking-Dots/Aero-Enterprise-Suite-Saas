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

export default function WorkflowTemplatesIndex({ templates, filters = {} }) {
  const toast     = useToast();
  const canCreate = useHRMAC('workflow.workflows.templates.create');
  const canUse    = useHRMAC('workflow.workflows.definitions.create');
  const canDelete = useHRMAC('workflow.workflows.templates.delete');

  const [search,   setSearch]   = useState(filters.search   || '');
  const [category, setCategory] = useState(filters.category || '');
  const [showCreate, setShowCreate] = useState(false);

  const form = useForm({ name: '', description: '', category: '' });

  const applyFilters = () => {
    router.get(route('workflow-templates.index'), { search, category }, {
      preserveState: true, preserveScroll: true, only: ['templates', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch(''); setCategory('');
    router.get(route('workflow-templates.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['templates', 'filters'],
    });
  };

  const handleUseTemplate = (template) => {
    if (!confirm(`Create a workflow from template "${template.name}"?`)) return;
    router.post(route('workflow-templates.use', template.id), {}, {
      onSuccess: () => toast.success('Workflow created from template.'),
      onError:   () => toast.error('Failed to create workflow from template.'),
    });
  };

  const handleDelete = (template) => {
    if (template.is_system) { toast.error('System templates cannot be deleted.'); return; }
    if (!confirm(`Delete template "${template.name}"?`)) return;
    router.delete(route('workflow-templates.destroy', template.id), {
      onSuccess: () => toast.success('Template deleted.'),
      onError:   () => toast.error('Failed to delete template.'),
    });
  };

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('workflow-templates.store'), {
      onSuccess: () => { setShowCreate(false); form.reset(); toast.success('Template created.'); },
      onError:   () => toast.error('Failed to create template.'),
    });
  };

  const categoryBadgeIntent = (cat) => {
    const map = { hr: 'success', finance: 'amber', operations: 'neutral', sales: 'primary' };
    return map[cat?.toLowerCase()] ?? 'neutral';
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '25%',
      render: (row) => (
        <HStack gap={2} align="center">
          <Text size="sm">{row.name}</Text>
          {row.is_system && <Badge intent="neutral" size="sm">System</Badge>}
        </HStack>
      ),
    },
    {
      key: 'description', label: 'Description', width: '30%',
      render: (row) => <Text tone="secondary" size="sm">{row.description || '—'}</Text>,
    },
    {
      key: 'category', label: 'Category', width: '15%',
      render: (row) => row.category
        ? <Badge intent={categoryBadgeIntent(row.category)}>{row.category}</Badge>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'actions', label: '', width: '25%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canUse && (
            <Button intent="soft" size="sm" leftIcon="plus" onClick={() => handleUseTemplate(row)}>
              Use Template
            </Button>
          )}
          {canDelete && !row.is_system && (
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
        title="Workflow Templates"
        breadcrumb={[
          { label: 'Dashboard',  href: route('core.dashboard') },
          { label: 'Workflows',  href: route('workflows.index') },
          { label: 'Templates' },
        ]}
        description="Reusable templates for quick workflow creation."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
              Create Template
            </Button>
          )
        }
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search templates…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()}
              leftIcon="search"
            />
            <Select
              value={category}
              onChange={e => setCategory(e.target.value)}
              options={[
                { value: '',           label: 'All Categories' },
                { value: 'hr',         label: 'HR' },
                { value: 'finance',    label: 'Finance' },
                { value: 'operations', label: 'Operations' },
                { value: 'sales',      label: 'Sales' },
              ]}
            />
            <Button intent="primary" onClick={applyFilters}>Filter</Button>
            <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={templates?.data || []}
            empty="No templates found."
          />
        }
        pagination={
          templates?.last_page > 1 && (
            <Pagination
              page={templates.current_page}
              total={templates.last_page}
              onChange={page => router.get(route('workflow-templates.index'), { page, search, category }, {
                preserveState: true, preserveScroll: true, only: ['templates'],
              })}
            />
          )
        }
      />

      <Modal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        title="Create Template"
        footer={
          <HStack gap={2} justify="end">
            <Button intent="ghost" onClick={() => setShowCreate(false)}>Cancel</Button>
            <Button intent="primary" loading={form.processing} onClick={handleCreate}>Create</Button>
          </HStack>
        }
      >
        <form onSubmit={handleCreate}>
          <VStack gap={4}>
            <Field label="Name" htmlFor="tpl-name" error={form.errors.name} required>
              <Input
                id="tpl-name"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                placeholder="Template name"
                error={!!form.errors.name}
              />
            </Field>
            <Field label="Category" htmlFor="tpl-cat" error={form.errors.category}>
              <Select
                id="tpl-cat"
                value={form.data.category}
                onChange={e => form.setData('category', e.target.value)}
                options={[
                  { value: '',           label: 'Select category…' },
                  { value: 'hr',         label: 'HR' },
                  { value: 'finance',    label: 'Finance' },
                  { value: 'operations', label: 'Operations' },
                  { value: 'sales',      label: 'Sales' },
                ]}
              />
            </Field>
            <Field label="Description" htmlFor="tpl-desc" error={form.errors.description}>
              <Input
                id="tpl-desc"
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

WorkflowTemplatesIndex.layout = page => <App title="Workflow Templates">{page}</App>;
