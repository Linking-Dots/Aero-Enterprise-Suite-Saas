import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge,
  Button, Card, CardBody, Field, Input, Select, Textarea, Alert,
  DataTable, Pagination,
} from '@aero/ui';

const TYPE_OPTIONS = [
  { value: '',           label: 'Select type' },
  { value: 'internal',   label: 'Internal Transfer' },
  { value: 'promotion',  label: 'Promotion' },
  { value: 'project',    label: 'Project Assignment' },
  { value: 'rotation',   label: 'Job Rotation' },
];

const TYPE_INTENT = {
  internal:  'primary',
  promotion: 'success',
  project:   'warning',
  rotation:  'neutral',
};

function typeLabel(v) {
  return TYPE_OPTIONS.find(o => o.value === v)?.label ?? v;
}

function statusIntent(status) {
  switch (status) {
    case 'open':   return 'success';
    case 'closed': return 'neutral';
    case 'draft':  return 'warning';
    default:       return 'neutral';
  }
}

function statusLabel(status) {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';
}

export default function MobilityIndex({ postings, departments, roles, filters }) {
  const canManage = useHRMAC('hrm.workforce-planning.talent-marketplace.manage');

  const [showForm, setShowForm] = useState(false);

  const departmentOptions = [
    { value: '', label: 'Select department' },
    ...(departments ?? []).map(d => ({ value: String(d.id), label: d.name })),
  ];

  const roleOptions = [
    { value: '', label: 'Select role' },
    ...(roles ?? []).map(r => ({ value: String(r.id), label: r.name })),
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    title:         '',
    description:   '',
    type:          '',
    department_id: '',
    role_id:       '',
    closes_at:     '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.talent-marketplace.store'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        reset();
        setShowForm(false);
      },
    });
  }

  const hasFormErrors = Object.keys(errors).length > 0;

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: row => <Text>{row.title}</Text>,
    },
    {
      key: 'type',
      label: 'Type',
      render: row => (
        <Badge intent={TYPE_INTENT[row.type] ?? 'neutral'}>
          {typeLabel(row.type)}
        </Badge>
      ),
    },
    {
      key: 'department',
      label: 'Department',
      render: row => row.department?.name ?? '—',
    },
    {
      key: 'role',
      label: 'Role',
      render: row => row.role?.name ?? '—',
    },
    {
      key: 'closes_at',
      label: 'Closes At',
      render: row => row.closes_at ?? '—',
    },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
  ];

  const totalPages  = postings.last_page    ?? 1;
  const currentPage = postings.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Talent Marketplace"
      breadcrumb={[{ label: 'HRM' }, { label: 'Succession' }, { label: 'Talent Marketplace' }]}
      actions={
        canManage && (
          <Button
            intent="primary"
            onClick={() => setShowForm(v => !v)}
          >
            {showForm ? 'Cancel' : 'New Posting'}
          </Button>
        )
      }
      table={
        <VStack gap={5}>

          {/* Create Posting Form */}
          {canManage && showForm && (
            <Card>
              <CardBody>
                <form onSubmit={submit}>
                  <VStack gap={4}>
                    <Eyebrow>Create Posting</Eyebrow>
                    {hasFormErrors && (
                      <Alert intent="danger" title="Please fix the errors below." />
                    )}

                    <Field label="Title" error={errors.title} required>
                      <Input
                        value={data.title}
                        onChange={e => setData('title', e.target.value)}
                        placeholder="e.g. Senior Engineer — Product Team"
                      />
                    </Field>

                    <Field label="Description" error={errors.description}>
                      <Textarea
                        value={data.description}
                        onChange={e => setData('description', e.target.value)}
                        placeholder="Describe the opportunity, requirements, and expectations"
                        rows={3}
                      />
                    </Field>

                    <HStack gap={3} wrap>
                      <Field label="Type" error={errors.type} required>
                        <Select
                          options={TYPE_OPTIONS}
                          value={data.type}
                          onChange={e => setData('type', e.target.value)}
                        />
                      </Field>
                      <Field label="Department" error={errors.department_id}>
                        <Select
                          options={departmentOptions}
                          value={data.department_id}
                          onChange={e => setData('department_id', e.target.value)}
                        />
                      </Field>
                      <Field label="Role" error={errors.role_id}>
                        <Select
                          options={roleOptions}
                          value={data.role_id}
                          onChange={e => setData('role_id', e.target.value)}
                        />
                      </Field>
                      <Field label="Closes At" error={errors.closes_at}>
                        <Input
                          type="date"
                          value={data.closes_at}
                          onChange={e => setData('closes_at', e.target.value)}
                        />
                      </Field>
                    </HStack>

                    <Box>
                      <Button type="submit" intent="primary" loading={processing}>
                        Create Posting
                      </Button>
                    </Box>
                  </VStack>
                </form>
              </CardBody>
            </Card>
          )}

          {/* Postings Table */}
          <DataTable
            columns={columns}
            rows={postings.data ?? []}
            empty="No talent marketplace postings yet."
          />
        </VStack>
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page =>
              router.get(
                route('hrm.talent-marketplace.index'),
                { ...filters, page },
                { preserveState: true }
              )
            }
          />
        )
      }
    />
  );
}

MobilityIndex.layout = page => <App title="Talent Marketplace">{page}</App>;
