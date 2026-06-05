import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Modal,
  Card,
  CardBody,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: 'investigating', label: 'Investigating' },
  { value: 'identified',    label: 'Identified' },
  { value: 'monitoring',    label: 'Monitoring' },
  { value: 'resolved',      label: 'Resolved' },
];

const IMPACT_OPTIONS = [
  { value: 'minor',    label: 'Minor' },
  { value: 'major',    label: 'Major' },
  { value: 'critical', label: 'Critical' },
];

const STATUS_INTENT = {
  investigating: 'danger',
  identified:    'warning',
  monitoring:    'amber',
  resolved:      'success',
};

const IMPACT_INTENT = {
  minor:    'neutral',
  major:    'warning',
  critical: 'danger',
};

function UpdateTimeline({ updates }) {
  if (!updates || updates.length === 0) {
    return <Text tone="secondary">No updates yet.</Text>;
  }
  return (
    <VStack gap={3}>
      {updates.map((u, i) => (
        <Card key={i}>
          <CardBody>
            <VStack gap={1}>
              <HStack gap={2}>
                <Badge intent={STATUS_INTENT[u.status] ?? 'neutral'}>{u.status}</Badge>
                <Mono tone="secondary">{u.created_at}</Mono>
              </HStack>
              <Text>{u.message}</Text>
            </VStack>
          </CardBody>
        </Card>
      ))}
    </VStack>
  );
}

export default function Incidents({ incidents, components }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('status-incidents.incidents.create');
  const canUpdate   = useHRMAC('status-incidents.incidents.update');
  const canResolve  = useHRMAC('status-incidents.incidents.resolve');

  const [detail,         setDetail]         = useState(null);
  const [showCreate,     setShowCreate]     = useState(false);
  const [updateTarget,   setUpdateTarget]   = useState(null);
  const [resolveTarget,  setResolveTarget]  = useState(null);
  const [resolving,      setResolving]      = useState(false);

  const createForm = useForm({
    title:        '',
    status:       'investigating',
    impact:       'minor',
    component_ids: [],
    message:      '',
  });

  const updateForm = useForm({
    status:  'monitoring',
    message: '',
  });

  const componentOptions = (components ?? []).map(c => ({ value: String(c.id), label: c.name }));

  function handleCreate(e) {
    e.preventDefault();
    createForm.post(route('platform.admin.status.incidents.store'), {
      onSuccess: () => {
        setShowCreate(false);
        createForm.reset();
        toast.success('Incident created.');
      },
      onError: () => toast.error('Failed to create incident.'),
    });
  }

  function handleAddUpdate(e) {
    e.preventDefault();
    if (!updateTarget) return;
    updateForm.post(
      route('platform.admin.status.incidents.update', updateTarget.id),
      {
        onSuccess: () => {
          toast.success('Update posted.');
          setUpdateTarget(null);
          updateForm.reset();
        },
        onError: () => toast.error('Update failed.'),
      }
    );
  }

  function handleResolve() {
    if (!resolveTarget) return;
    setResolving(true);
    router.post(
      route('platform.admin.status.incidents.resolve', resolveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Incident "${resolveTarget.title}" resolved.`);
          setResolveTarget(null);
        },
        onError:  () => toast.error('Resolve failed.'),
        onFinish: () => setResolving(false),
      }
    );
  }

  function toggleComponent(id) {
    const ids = createForm.data.component_ids;
    createForm.setData(
      'component_ids',
      ids.includes(id) ? ids.filter(x => x !== id) : [...ids, id]
    );
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: (row) => <Text>{row.title}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'impact',
      label: 'Impact',
      render: (row) => (
        <Badge intent={IMPACT_INTENT[row.impact] ?? 'neutral'}>
          {row.impact}
        </Badge>
      ),
    },
    {
      key: 'started_at',
      label: 'Started',
      render: (row) => <Mono>{row.started_at ?? '—'}</Mono>,
    },
    {
      key: 'resolved_at',
      label: 'Resolved',
      render: (row) => <Mono>{row.resolved_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          <Button intent="ghost" size="sm" onClick={() => setDetail(row)}>
            Timeline
          </Button>
          {canUpdate && row.status !== 'resolved' && (
            <Button intent="soft" size="sm" onClick={() => { setUpdateTarget(row); updateForm.setData('status', row.status); }}>
              Update
            </Button>
          )}
          {canResolve && row.status !== 'resolved' && (
            <Button intent="danger" size="sm" onClick={() => setResolveTarget(row)}>
              Resolve
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Incidents"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Status & Incidents', href: route('platform.admin.status.index') },
        { label: 'Incidents' },
      ]}
      description="Track and manage platform incidents with update timelines."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
            New Incident
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={incidents?.data ?? []}
          empty="No incidents on record."
        />

        {(incidents?.last_page ?? 1) > 1 && (
          <Pagination
            page={incidents.current_page}
            total={incidents.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.status.incidents.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['incidents'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Incident Modal */}
      <Modal
        open={showCreate}
        onClose={() => { setShowCreate(false); createForm.reset(); }}
        title="Create Incident"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={createForm.processing}
              disabled={createForm.processing}
              onClick={handleCreate}
            >
              Declare Incident
            </Button>
            <Button intent="ghost" onClick={() => { setShowCreate(false); createForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Title" htmlFor="inc_title" error={createForm.errors.title} required>
            <Input
              id="inc_title"
              value={createForm.data.title}
              onChange={e => createForm.setData('title', e.target.value)}
              placeholder="API degraded performance"
              error={createForm.errors.title}
            />
          </Field>
          <HStack gap={4}>
            <Field label="Status" htmlFor="inc_status" error={createForm.errors.status}>
              <Select
                id="inc_status"
                value={createForm.data.status}
                onChange={e => createForm.setData('status', e.target.value)}
                options={STATUS_OPTIONS}
              />
            </Field>
            <Field label="Impact" htmlFor="inc_impact" error={createForm.errors.impact}>
              <Select
                id="inc_impact"
                value={createForm.data.impact}
                onChange={e => createForm.setData('impact', e.target.value)}
                options={IMPACT_OPTIONS}
              />
            </Field>
          </HStack>
          {componentOptions.length > 0 && (
            <Field label="Affected Components" error={createForm.errors.component_ids}>
              <VStack gap={1}>
                {componentOptions.map(opt => (
                  <HStack key={opt.value} gap={2}>
                    <input
                      type="checkbox"
                      id={`comp_${opt.value}`}
                      checked={createForm.data.component_ids.includes(opt.value)}
                      onChange={() => toggleComponent(opt.value)}
                    />
                    <Text>{opt.label}</Text>
                  </HStack>
                ))}
              </VStack>
            </Field>
          )}
          <Field label="Initial Update Message" htmlFor="inc_message" error={createForm.errors.message} required>
            <Input
              id="inc_message"
              value={createForm.data.message}
              onChange={e => createForm.setData('message', e.target.value)}
              placeholder="We are investigating reports of degraded performance..."
              error={createForm.errors.message}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Post Update Modal */}
      <Modal
        open={!!updateTarget}
        onClose={() => { setUpdateTarget(null); updateForm.reset(); }}
        title={`Post Update: ${updateTarget?.title}`}
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={updateForm.processing}
              disabled={updateForm.processing}
              onClick={handleAddUpdate}
            >
              Post Update
            </Button>
            <Button intent="ghost" onClick={() => { setUpdateTarget(null); updateForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="New Status" htmlFor="upd_status" error={updateForm.errors.status}>
            <Select
              id="upd_status"
              value={updateForm.data.status}
              onChange={e => updateForm.setData('status', e.target.value)}
              options={STATUS_OPTIONS}
            />
          </Field>
          <Field label="Update Message" htmlFor="upd_message" error={updateForm.errors.message} required>
            <Input
              id="upd_message"
              value={updateForm.data.message}
              onChange={e => updateForm.setData('message', e.target.value)}
              placeholder="We have identified the root cause..."
              error={updateForm.errors.message}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Resolve Confirm Modal */}
      <Modal
        open={!!resolveTarget}
        onClose={() => setResolveTarget(null)}
        title="Resolve Incident"
        description={`Mark "${resolveTarget?.title}" as resolved? This will close the incident and notify subscribers.`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={resolving}
              disabled={resolving}
              onClick={handleResolve}
            >
              Mark Resolved
            </Button>
            <Button intent="ghost" onClick={() => setResolveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Incident Timeline Modal */}
      <Modal
        open={!!detail}
        onClose={() => setDetail(null)}
        title={detail?.title ?? 'Incident Timeline'}
        size="lg"
        footer={
          <HStack gap={2}>
            {detail && canUpdate && detail.status !== 'resolved' && (
              <Button
                intent="soft"
                onClick={() => { setUpdateTarget(detail); updateForm.setData('status', detail.status); setDetail(null); }}
              >
                Post Update
              </Button>
            )}
            <Button intent="ghost" onClick={() => setDetail(null)}>Close</Button>
          </HStack>
        }
      >
        {detail && (
          <VStack gap={4}>
            <HStack gap={3} wrap>
              <Badge intent={STATUS_INTENT[detail.status] ?? 'neutral'}>{detail.status}</Badge>
              <Badge intent={IMPACT_INTENT[detail.impact] ?? 'neutral'}>{detail.impact}</Badge>
              <Mono tone="secondary">Started: {detail.started_at}</Mono>
              {detail.resolved_at && <Mono tone="secondary">Resolved: {detail.resolved_at}</Mono>}
            </HStack>
            <Eyebrow>Update Timeline</Eyebrow>
            <UpdateTimeline updates={detail.updates} />
          </VStack>
        )}
      </Modal>
    </IndexPageLayout>
  );
}

Incidents.layout = page => <App title="Incidents">{page}</App>;
