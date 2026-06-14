import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Card,
  CardBody,
  Input,
  Select,
  Field,
  Modal,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',           label: 'All Statuses' },
  { value: 'new',        label: 'New' },
  { value: 'contacted',  label: 'Contacted' },
  { value: 'qualified',  label: 'Qualified' },
  { value: 'converted',  label: 'Converted' },
  { value: 'lost',       label: 'Lost' },
];

const STATUS_INTENT = {
  new:       'neutral',
  contacted: 'primary',
  qualified: 'success',
  converted: 'success',
  lost:      'danger',
};

function statusIntent(s) {
  return STATUS_INTENT[s] ?? 'neutral';
}

export default function LeadsIndex({ leads, filters, users }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('lead-management.lead-list.create');
  const canAssign   = useHRMAC('lead-management.lead-list.assign');
  const canConvert  = useHRMAC('lead-management.lead-list.convert');
  const canDelete   = useHRMAC('lead-management.lead-list.delete');

  const [form, setForm]           = useState(filters ?? {});
  const [createOpen, setCreateOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [target, setTarget]         = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const [newLead, setNewLead] = useState({
    name: '', email: '', company: '', phone: '', source: '', notes: '',
  });
  const [assignUserId, setAssignUserId] = useState('');

  const userOptions = [
    { value: '', label: 'Unassigned' },
    ...(users ?? []).map(u => ({ value: String(u.id), label: u.name })),
  ];

  function applyFilters() {
    router.get(
      route('platform.admin.leads.index'),
      form,
      { preserveState: true, preserveScroll: true, only: ['leads', 'filters'] }
    );
  }

  function resetFilters() {
    setForm({});
    router.get(
      route('platform.admin.leads.index'),
      {},
      { preserveState: true, preserveScroll: true, only: ['leads', 'filters'] }
    );
  }

  function doCreate() {
    setSubmitting(true);
    router.post(
      route('platform.admin.leads.store'),
      newLead,
      {
        onSuccess: () => {
          setCreateOpen(false);
          setNewLead({ name: '', email: '', company: '', phone: '', source: '', notes: '' });
          toast.success('Lead created.');
        },
        onError: () => toast.error('Failed to create lead.'),
        onFinish: () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function openAssign(lead) {
    setTarget(lead);
    setAssignUserId(lead.assigned_to ? String(lead.assigned_to) : '');
    setAssignOpen(true);
  }

  function doAssign() {
    setSubmitting(true);
    router.post(
      route('platform.admin.leads.assign', target.id),
      { user_id: assignUserId },
      {
        onSuccess: () => { setAssignOpen(false); toast.success('Lead assigned.'); },
        onError:   () => toast.error('Failed to assign lead.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function openDelete(lead) {
    setTarget(lead);
    setDeleteOpen(true);
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.leads.destroy', target.id),
      {
        onSuccess: () => { setDeleteOpen(false); toast.success('Lead deleted.'); },
        onError:   () => toast.error('Failed to delete lead.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Name',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.leads.show', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'company',
      label: 'Company',
      render: (row) => <Text tone="secondary">{row.company ?? '—'}</Text>,
    },
    {
      key: 'email',
      label: 'Email',
      render: (row) => <Mono>{row.email}</Mono>,
    },
    {
      key: 'source',
      label: 'Source',
      render: (row) => <Text tone="secondary">{row.source ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={statusIntent(row.status)}>{row.status}</Badge>
      ),
    },
    {
      key: 'assigned_to',
      label: 'Assigned',
      render: (row) => <Text tone="secondary">{row.assignee?.name ?? '—'}</Text>,
    },
    {
      key: 'created_at',
      label: 'Created',
      render: (row) => <Mono tone="secondary">{row.created_at}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.leads.show', row.id))}
          >
            View
          </Button>
          {canAssign && (
            <Button intent="ghost" size="sm" onClick={() => openAssign(row)}>
              Assign
            </Button>
          )}
          {canDelete && (
            <Button intent="ghost" size="sm" onClick={() => openDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Lead Pipeline"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Leads' },
      ]}
      description="Track and manage prospects through the sales pipeline."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setCreateOpen(true)}>
            New Lead
          </Button>
        )
      }
    >
      <VStack gap={4}>

        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3} wrap>
                <Select
                  value={form.status ?? ''}
                  onChange={e => setForm({ ...form, status: e.target.value })}
                  options={STATUS_OPTIONS}
                />
                <Input
                  placeholder="Search name or email"
                  value={form.search ?? ''}
                  onChange={e => setForm({ ...form, search: e.target.value })}
                  leftIcon="magnifyingGlass"
                />
                <Input
                  placeholder="Source"
                  value={form.source ?? ''}
                  onChange={e => setForm({ ...form, source: e.target.value })}
                />
              </HStack>
              <HStack gap={2}>
                <Button intent="soft" onClick={applyFilters}>Apply</Button>
                <Button intent="ghost" onClick={resetFilters}>Reset</Button>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <DataTable
          columns={columns}
          rows={leads?.data ?? []}
          empty="No leads found."
        />

        {(leads?.last_page ?? 1) > 1 && (
          <Pagination
            page={leads.current_page}
            total={leads.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.leads.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['leads'] }
              )
            }
          />
        )}

      </VStack>

      {/* Create Lead Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Lead"
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setCreateOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doCreate}>
              Create Lead
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <HStack gap={3}>
            <Field label="Name" htmlFor="lead_name" required>
              <Input
                id="lead_name"
                value={newLead.name}
                onChange={e => setNewLead({ ...newLead, name: e.target.value })}
                placeholder="Jane Doe"
              />
            </Field>
            <Field label="Email" htmlFor="lead_email" required>
              <Input
                id="lead_email"
                type="email"
                leftIcon="mail"
                value={newLead.email}
                onChange={e => setNewLead({ ...newLead, email: e.target.value })}
                placeholder="jane@example.com"
              />
            </Field>
          </HStack>
          <HStack gap={3}>
            <Field label="Company" htmlFor="lead_company">
              <Input
                id="lead_company"
                value={newLead.company}
                onChange={e => setNewLead({ ...newLead, company: e.target.value })}
                placeholder="Acme Corp"
              />
            </Field>
            <Field label="Phone" htmlFor="lead_phone">
              <Input
                id="lead_phone"
                value={newLead.phone}
                onChange={e => setNewLead({ ...newLead, phone: e.target.value })}
                placeholder="+1-800-000-0000"
                leftIcon="phone"
              />
            </Field>
          </HStack>
          <Field label="Source" htmlFor="lead_source">
            <Input
              id="lead_source"
              value={newLead.source}
              onChange={e => setNewLead({ ...newLead, source: e.target.value })}
              placeholder="e.g. organic, referral, paid"
            />
          </Field>
          <Field label="Notes" htmlFor="lead_notes">
            <Input
              id="lead_notes"
              value={newLead.notes}
              onChange={e => setNewLead({ ...newLead, notes: e.target.value })}
              placeholder="Initial contact notes…"
            />
          </Field>
        </VStack>
      </Modal>

      {/* Assign Modal */}
      <Modal
        open={assignOpen}
        onClose={() => setAssignOpen(false)}
        title={`Assign lead — ${target?.name}`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setAssignOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doAssign}>
              Save Assignment
            </Button>
          </HStack>
        }
      >
        <Field label="Assign to" htmlFor="assign_user">
          <Select
            id="assign_user"
            value={assignUserId}
            onChange={e => setAssignUserId(e.target.value)}
            options={userOptions}
          />
        </Field>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={`Delete lead — ${target?.name}?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setDeleteOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doDelete}>
              Delete
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This permanently removes the lead record. This action cannot be undone.
        </Text>
      </Modal>

    </IndexPageLayout>
  );
}

LeadsIndex.layout = page => <App title="Lead Pipeline">{page}</App>;
