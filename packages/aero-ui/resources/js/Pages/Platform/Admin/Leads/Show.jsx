import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  DetailPageLayout,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: 'new',       label: 'New' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'lost',      label: 'Lost' },
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

export default function LeadsShow({ lead, activities, users }) {
  const toast      = useToast();
  const canEdit    = useHRMAC('lead-management.lead-list.edit');
  const canConvert = useHRMAC('lead-management.lead-list.convert');
  const canDelete  = useHRMAC('lead-management.lead-list.delete');

  const [convertOpen, setConvertOpen] = useState(false);
  const [deleteOpen,  setDeleteOpen]  = useState(false);
  const [editOpen,    setEditOpen]    = useState(false);
  const [noteOpen,    setNoteOpen]    = useState(false);
  const [submitting, setSubmitting]   = useState(false);
  const [noteText,   setNoteText]     = useState('');

  const [editForm, setEditForm] = useState({
    name:    lead.name    ?? '',
    email:   lead.email   ?? '',
    company: lead.company ?? '',
    phone:   lead.phone   ?? '',
    source:  lead.source  ?? '',
    status:  lead.status  ?? 'new',
    notes:   lead.notes   ?? '',
    assigned_to: lead.assigned_to ? String(lead.assigned_to) : '',
  });

  const userOptions = [
    { value: '', label: 'Unassigned' },
    ...(users ?? []).map(u => ({ value: String(u.id), label: u.name })),
  ];

  function doEdit() {
    setSubmitting(true);
    router.put(
      route('platform.admin.leads.update', lead.id),
      editForm,
      {
        onSuccess: () => { setEditOpen(false); toast.success('Lead updated.'); },
        onError:   () => toast.error('Failed to update lead.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doAddNote() {
    setSubmitting(true);
    router.post(
      route('platform.admin.leads.note', lead.id),
      { note: noteText },
      {
        onSuccess: () => { setNoteOpen(false); setNoteText(''); toast.success('Note added.'); },
        onError:   () => toast.error('Failed to add note.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doConvert() {
    setSubmitting(true);
    router.post(
      route('platform.admin.leads.convert', lead.id),
      {},
      {
        onSuccess: () => {
          setConvertOpen(false);
          toast.success('Lead converted to tenant.');
        },
        onError:   () => toast.error('Failed to convert lead.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.leads.destroy', lead.id),
      {
        onSuccess: () => {
          setDeleteOpen(false);
          toast.success('Lead deleted.');
          router.get(route('platform.admin.leads.index'));
        },
        onError:   () => toast.error('Failed to delete lead.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  return (
    <DetailPageLayout
      title={lead.name}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Leads', href: route('platform.admin.leads.index') },
        { label: lead.name },
      ]}
      description={lead.company ?? ''}
      actions={
        <HStack gap={2}>
          <Badge intent={statusIntent(lead.status)}>{lead.status}</Badge>
          {canEdit && (
            <Button intent="soft" leftIcon="pencil" onClick={() => setEditOpen(true)}>
              Edit
            </Button>
          )}
          {canConvert && lead.status !== 'converted' && (
            <Button intent="primary" leftIcon="arrowRight" onClick={() => setConvertOpen(true)}>
              Convert to Tenant
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" onClick={() => setDeleteOpen(true)}>
              Delete
            </Button>
          )}
          <Button intent="ghost" onClick={() => router.get(route('platform.admin.leads.index'))}>
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Lead Details</Eyebrow>
              <HStack gap={4} wrap>
                <VStack gap={0}>
                  <Text tone="secondary">Email</Text>
                  <Mono>{lead.email}</Mono>
                </VStack>
                {lead.phone && (
                  <VStack gap={0}>
                    <Text tone="secondary">Phone</Text>
                    <Mono>{lead.phone}</Mono>
                  </VStack>
                )}
                <VStack gap={0}>
                  <Text tone="secondary">Source</Text>
                  <Text>{lead.source ?? '—'}</Text>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Assigned To</Text>
                  <Text>{lead.assignee?.name ?? 'Unassigned'}</Text>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Created</Text>
                  <Mono tone="secondary">{lead.created_at}</Mono>
                </VStack>
              </HStack>

              {lead.converted_tenant_id && (
                <HStack gap={2}>
                  <Text tone="secondary">Converted tenant:</Text>
                  <Button
                    intent="ghost"
                    size="sm"
                    onClick={() => router.get(route('platform.admin.tenants.show', lead.converted_tenant_id))}
                  >
                    Tenant #{lead.converted_tenant_id}
                  </Button>
                </HStack>
              )}

              {lead.notes && (
                <VStack gap={1}>
                  <Text tone="secondary">Notes</Text>
                  <Text>{lead.notes}</Text>
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Activity Timeline */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={3}>
                <Eyebrow>Activity Timeline</Eyebrow>
                <Button intent="soft" size="sm" leftIcon="plus" onClick={() => setNoteOpen(true)}>
                  Add Note
                </Button>
              </HStack>

              {(activities ?? []).length === 0 ? (
                <Text tone="secondary">No activity recorded yet.</Text>
              ) : (
                <VStack gap={3}>
                  {(activities ?? []).map((activity, idx) => (
                    <HStack key={idx} gap={3} align="start">
                      <VStack gap={0}>
                        <Mono tone="secondary">{activity.created_at}</Mono>
                        <Text tone="secondary">{activity.actor_name ?? 'System'}</Text>
                      </VStack>
                      <VStack gap={0}>
                        <Badge intent="neutral">{activity.type}</Badge>
                        <Text>{activity.description}</Text>
                      </VStack>
                    </HStack>
                  ))}
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

      </VStack>

      {/* Edit Modal */}
      <Modal
        open={editOpen}
        onClose={() => setEditOpen(false)}
        title="Edit Lead"
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setEditOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doEdit}>
              Save Changes
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <HStack gap={3}>
            <Field label="Name" htmlFor="edit_name" required>
              <Input
                id="edit_name"
                value={editForm.name}
                onChange={e => setEditForm({ ...editForm, name: e.target.value })}
              />
            </Field>
            <Field label="Email" htmlFor="edit_email" required>
              <Input
                id="edit_email"
                type="email"
                leftIcon="mail"
                value={editForm.email}
                onChange={e => setEditForm({ ...editForm, email: e.target.value })}
              />
            </Field>
          </HStack>
          <HStack gap={3}>
            <Field label="Company" htmlFor="edit_company">
              <Input
                id="edit_company"
                value={editForm.company}
                onChange={e => setEditForm({ ...editForm, company: e.target.value })}
              />
            </Field>
            <Field label="Phone" htmlFor="edit_phone">
              <Input
                id="edit_phone"
                leftIcon="phone"
                value={editForm.phone}
                onChange={e => setEditForm({ ...editForm, phone: e.target.value })}
              />
            </Field>
          </HStack>
          <HStack gap={3}>
            <Field label="Status" htmlFor="edit_status">
              <Select
                id="edit_status"
                value={editForm.status}
                onChange={e => setEditForm({ ...editForm, status: e.target.value })}
                options={STATUS_OPTIONS}
              />
            </Field>
            <Field label="Assigned To" htmlFor="edit_assigned">
              <Select
                id="edit_assigned"
                value={editForm.assigned_to}
                onChange={e => setEditForm({ ...editForm, assigned_to: e.target.value })}
                options={userOptions}
              />
            </Field>
          </HStack>
          <Field label="Notes" htmlFor="edit_notes">
            <Input
              id="edit_notes"
              value={editForm.notes}
              onChange={e => setEditForm({ ...editForm, notes: e.target.value })}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Add Note Modal */}
      <Modal
        open={noteOpen}
        onClose={() => setNoteOpen(false)}
        title="Add Activity Note"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setNoteOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doAddNote}>
              Add Note
            </Button>
          </HStack>
        }
      >
        <Field label="Note" htmlFor="note_text">
          <Input
            id="note_text"
            value={noteText}
            onChange={e => setNoteText(e.target.value)}
            placeholder="Enter your note…"
          />
        </Field>
      </Modal>

      {/* Convert Confirmation */}
      <Modal
        open={convertOpen}
        onClose={() => setConvertOpen(false)}
        title={`Convert "${lead.name}" to tenant?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setConvertOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doConvert}>
              Convert to Tenant
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will create a new tenant account for this lead and mark the lead as converted.
        </Text>
      </Modal>

      {/* Delete Confirmation */}
      <Modal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={`Delete lead "${lead.name}"?`}
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
          This permanently removes the lead and all associated activity. This action cannot be undone.
        </Text>
      </Modal>

    </DetailPageLayout>
  );
}

LeadsShow.layout = page => <App title="Lead Detail">{page}</App>;
