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
  Mono,
  Input,
  Textarea,
  Select,
  Field,
  Modal,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PRIORITY_INTENT = { low: 'neutral', normal: 'primary', high: 'warning', urgent: 'danger' };
const STATUS_INTENT   = { open: 'primary', pending: 'warning', resolved: 'success', closed: 'neutral' };

export default function Tickets({ tickets = {}, filters = {} }) {
  const toast = useToast();
  const [showModal, setShowModal] = useState(false);

  const form = useForm({
    subject:  '',
    body:     '',
    priority: 'normal',
  });

  const submit = e => {
    e.preventDefault();
    form.post('/help/tickets', {
      onSuccess: () => {
        toast.success('Ticket created successfully.');
        form.reset();
        setShowModal(false);
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to create ticket.');
      },
    });
  };

  const columns = [
    {
      key: 'subject', label: 'Subject', width: '35%',
      render: row => (
        <Text size="sm">
          {row.subject?.length > 60 ? row.subject.slice(0, 60) + '…' : row.subject}
        </Text>
      ),
    },
    {
      key: 'priority', label: 'Priority', width: '12%',
      render: row => (
        <Badge intent={PRIORITY_INTENT[row.priority] ?? 'neutral'} size="sm">
          {row.priority}
        </Badge>
      ),
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'} size="sm">
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'created_at', label: 'Created', width: '16%',
      render: row => (
        <Mono size="sm" tone="secondary">
          {row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Support Tickets"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Help Center', href: '/help' },
          { label: 'Support Tickets' },
        ]}
        description="Submit and track support requests."
        actions={
          <HStack gap={2}>
            <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get('/help')}>
              Back to Help Center
            </Button>
            <Button intent="primary" leftIcon="plus" onClick={() => setShowModal(true)}>
              Create Ticket
            </Button>
          </HStack>
        }
        filters={
          <HStack gap={3}>
            <Select
              value={filters?.status ?? ''}
              onChange={e => router.get('/help/tickets', { status: e.target.value }, {
                preserveState: true, preserveScroll: true,
              })}
              options={[
                { value: '',         label: 'All Statuses' },
                { value: 'open',     label: 'Open' },
                { value: 'pending',  label: 'Pending' },
                { value: 'resolved', label: 'Resolved' },
                { value: 'closed',   label: 'Closed' },
              ]}
            />
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={tickets?.data ?? []}
            empty="No tickets found."
          />
        }
        pagination={
          tickets?.last_page > 1 && (
            <Pagination
              page={tickets.current_page}
              total={tickets.last_page}
              onChange={page => router.get('/help/tickets', { page, status: filters?.status }, {
                preserveState: true, preserveScroll: true, only: ['tickets'],
              })}
            />
          )
        }
      />

      <Modal
        open={showModal}
        title="Create Support Ticket"
        size="lg"
        onClose={() => setShowModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submit} loading={form.processing}>Submit Ticket</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={3}>
            <Field label="Subject" htmlFor="ticket-subject" error={form.errors.subject} required>
              <Input
                id="ticket-subject"
                placeholder="Briefly describe your issue"
                value={form.data.subject}
                onChange={e => form.setData('subject', e.target.value)}
                error={form.errors.subject}
              />
            </Field>

            <Field label="Description" htmlFor="ticket-body" error={form.errors.body} required>
              <Textarea
                id="ticket-body"
                rows={5}
                placeholder="Provide full details about your issue…"
                value={form.data.body}
                onChange={e => form.setData('body', e.target.value)}
                error={form.errors.body}
              />
            </Field>

            <Field label="Priority" htmlFor="ticket-priority" error={form.errors.priority}>
              <Select
                id="ticket-priority"
                value={form.data.priority}
                onChange={e => form.setData('priority', e.target.value)}
                options={[
                  { value: 'low',    label: 'Low' },
                  { value: 'normal', label: 'Normal' },
                  { value: 'high',   label: 'High' },
                  { value: 'urgent', label: 'Urgent' },
                ]}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

Tickets.layout = page => (
  <App title="Support Tickets">{page}</App>
);
