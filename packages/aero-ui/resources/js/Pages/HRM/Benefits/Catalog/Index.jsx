import { useState } from 'react';
import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Pagination, Badge, Modal, Text,
} from '@aero/ui';

const CATEGORY_OPTIONS = [
  { value: '', label: 'All Categories' },
  ...['health', 'dental', 'vision', 'life', 'disability', 'pension', 'wellness', 'other'].map(c => ({
    value: c,
    label: c.charAt(0).toUpperCase() + c.slice(1),
  })),
];

export default function CatalogIndex({ benefits, filters }) {
  const canEdit   = useHRMAC('hrm.benefits.benefit-catalog.edit');
  const [modalOpen, setModalOpen] = useState(false);
  const [toDelete,  setToDelete]  = useState(null);

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.benefits.catalog.index'),
      { ...filters, ...overrides },
      { preserveState: true, replace: true },
    );
  }

  function confirmDelete(benefit) {
    setToDelete(benefit);
    setModalOpen(true);
  }

  function handleDelete() {
    router.delete(route('hrm.benefits.catalog.destroy', toDelete.id), {
      onSuccess: () => setModalOpen(false),
    });
  }

  const columns = [
    { key: 'name',          label: 'Name' },
    { key: 'category',      label: 'Category',      render: row => <Badge intent="neutral">{row.category}</Badge> },
    { key: 'employee_cost', label: 'Employee Cost',  render: row => `$${Number(row.employee_cost).toFixed(2)}` },
    { key: 'employer_cost', label: 'Employer Cost',  render: row => `$${Number(row.employer_cost).toFixed(2)}` },
    { key: 'frequency',     label: 'Frequency' },
    { key: 'active',        label: 'Status',         render: row => <Badge intent={row.active ? 'success' : 'danger'}>{row.active ? 'Active' : 'Inactive'}</Badge> },
    ...(canEdit ? [{
      key: 'actions',
      label: '',
      render: row => (
        <HStack gap={2}>
          <Button
            size="sm"
            intent="danger"
            onClick={() => confirmDelete(row)}
          >
            Delete
          </Button>
        </HStack>
      ),
    }] : []),
  ];

  return (
    <>
      <IndexPageLayout
        title="Benefits Catalog"
        breadcrumb={[{ label: 'HRM' }, { label: 'Benefits' }, { label: 'Catalog' }]}
        actions={
          canEdit && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.benefits.catalog.create'))}
            >
              New Benefit
            </Button>
          )
        }
        filters={
          <HStack gap={3} wrap>
            <Field label="Search">
              <Input
                placeholder="Search benefits..."
                defaultValue={filters.search ?? ''}
                onBlur={e => applyFilters({ search: e.target.value })}
              />
            </Field>
            <Field label="Category">
              <Select
                options={CATEGORY_OPTIONS}
                value={filters.category ?? ''}
                onChange={e => applyFilters({ category: e.target.value })}
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={benefits.data ?? []}
            empty="No benefits found."
          />
        }
        pagination={
          (benefits.last_page ?? 1) > 1 && (
            <Pagination
              total={benefits.last_page ?? 1}
              page={benefits.current_page ?? 1}
              onChange={p => applyFilters({ page: p })}
            />
          )
        }
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title="Delete Benefit"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setModalOpen(false)}>Cancel</Button>
            <Button intent="danger" onClick={handleDelete}>Delete</Button>
          </HStack>
        }
      >
        <Text>Are you sure you want to delete "{toDelete?.name}"? This cannot be undone.</Text>
      </Modal>
    </>
  );
}

CatalogIndex.layout = page => <App title="Benefits Catalog">{page}</App>;
