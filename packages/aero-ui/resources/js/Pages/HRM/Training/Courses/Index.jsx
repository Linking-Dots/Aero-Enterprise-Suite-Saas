import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Field, Input, Select, Toggle,
  Pagination, Badge, Text,
} from '@aero/ui';

const DELIVERY_MODE_LABELS = {
  in_person:  'In Person',
  virtual:    'Virtual',
  self_paced: 'Self-Paced',
};

const MODE_OPTIONS = [
  { value: '',           label: 'All Modes' },
  { value: 'in_person',  label: 'In Person' },
  { value: 'virtual',    label: 'Virtual' },
  { value: 'self_paced', label: 'Self-Paced' },
];

function modeIntent(mode) {
  switch (mode) {
    case 'in_person':  return 'success';
    case 'virtual':    return 'info';
    case 'self_paced': return 'warning';
    default:           return 'neutral';
  }
}

export default function CoursesIndex({ courses, filters, categories }) {
  const canCreate = useHRMAC('hrm.training.courses.create');

  const categoryOptions = [
    { value: '',  label: 'All Categories' },
    ...(categories ?? []).map(c => ({ value: String(c.id), label: c.name })),
  ];

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.training.courses.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = courses.last_page    ?? 1;
  const currentPage = courses.current_page ?? 1;

  const columns = [
    {
      key: 'title', label: 'Title',
      render: row => (
        <HStack gap={2} align="center">
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('hrm.training.courses.show', row.id))}
          >
            {row.title}
          </Button>
          {row.is_mandatory && (
            <Badge intent="warning">Mandatory</Badge>
          )}
        </HStack>
      ),
    },
    {
      key: 'category', label: 'Category',
      render: row => <Text>{row.category?.name ?? '—'}</Text>,
    },
    {
      key: 'delivery_mode', label: 'Mode',
      render: row => (
        <Badge intent={modeIntent(row.delivery_mode)}>
          {DELIVERY_MODE_LABELS[row.delivery_mode] ?? row.delivery_mode}
        </Badge>
      ),
    },
    {
      key: 'sessions_count', label: 'Sessions',
      render: row => <Text>{row.sessions_count ?? 0}</Text>,
    },
    {
      key: 'upcoming_sessions_count', label: 'Upcoming',
      render: row => <Text>{row.upcoming_sessions_count ?? 0}</Text>,
    },
    {
      key: 'is_active', label: 'Status',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Training Courses"
      breadcrumb={[{ label: 'HRM' }, { label: 'Training' }, { label: 'Courses' }]}
      actions={
        <HStack gap={3} align="center" wrap>
          <Input
            placeholder="Search courses..."
            defaultValue={filters?.search ?? ''}
            onBlur={e => applyFilters({ search: e.target.value, page: 1 })}
          />
          <Select
            options={categoryOptions}
            value={filters?.category_id ? String(filters.category_id) : ''}
            onChange={e => applyFilters({ category_id: e.target.value, page: 1 })}
          />
          <Select
            options={MODE_OPTIONS}
            value={filters?.mode ?? ''}
            onChange={e => applyFilters({ mode: e.target.value, page: 1 })}
          />
          <Field label="Mandatory only">
            <Toggle
              label=""
              checked={!!filters?.mandatory}
              onChange={e => applyFilters({ mandatory: e.target.checked ? '1' : '', page: 1 })}
            />
          </Field>
          {canCreate && (
            <Button
              intent="primary"
              onClick={() => router.get(route('hrm.training.courses.create'))}
            >
              New Course
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={courses.data ?? []}
          empty="No courses found."
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

CoursesIndex.layout = page => <App title="Training Courses">{page}</App>;
