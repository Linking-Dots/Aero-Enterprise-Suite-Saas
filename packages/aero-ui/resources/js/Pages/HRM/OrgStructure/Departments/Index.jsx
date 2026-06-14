import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, Field, Input, Pagination, Text,
} from '@aero/ui';

export default function DepartmentsIndex({ departments, filters }) {
  const [search, setSearch] = useState(filters?.search ?? '');

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.org.departments.index'),
      { ...filters, search: search || undefined, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const columns = [
    { key: 'name',           label: 'Name' },
    { key: 'parent_name',    label: 'Parent',   render: row => row.parent?.name ?? <Text tone="secondary">—</Text> },
    { key: 'head_name',      label: 'Head',     render: row => row.head?.user?.name ?? <Text tone="secondary">—</Text> },
    { key: 'children_count', label: 'Sub-Depts', align: 'right', render: row => row.children_count ?? 0 },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.org.departments.edit', row.id))}
        >
          Edit
        </Button>
      ),
    },
  ];

  const totalPages  = departments.last_page    ?? 1;
  const currentPage = departments.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Departments"
      breadcrumb={[{ label: 'HRM' }, { label: 'Org Structure' }, { label: 'Departments' }]}
      actions={
        <HStack gap={2}>
          <Button
            intent="soft"
            onClick={() => router.get(route('hrm.org.departments.chart'))}
          >
            Org Chart
          </Button>
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.org.departments.create'))}
          >
            New Department
          </Button>
        </HStack>
      }
      filters={
        <HStack gap={3} wrap>
          <Field label="Search">
            <Input
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applyFilters()}
              placeholder="Department name"
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={departments.data ?? []}
          empty="No departments found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => applyFilters({ page })}
          />
        )
      }
    />
  );
}

DepartmentsIndex.layout = page => <App title="Departments">{page}</App>;
