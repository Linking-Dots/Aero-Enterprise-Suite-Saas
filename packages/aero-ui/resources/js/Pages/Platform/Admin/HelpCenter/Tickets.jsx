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
  Drawer,
  Card,
  CardBody,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PRIORITY_INTENT = {
  urgent:  'danger',
  high:    'warning',
  normal:  'neutral',
  low:     'neutral',
};

const STATUS_INTENT = {
  open:     'warning',
  pending:  'neutral',
  resolved: 'success',
  closed:   'neutral',
};

const STATUS_OPTIONS = [
  { value: 'open',     label: 'Open' },
  { value: 'pending',  label: 'Pending' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'closed',   label: 'Closed' },
];

export default function Tickets({ tickets, agents, filters }) {
  const toast = useToast();
  const canAssign   = useHRMAC('help-center.tenant-tickets.assign');
  const canReply    = useHRMAC('help-center.tenant-tickets.reply');
  const canEscalate = useHRMAC('help-center.tenant-tickets.escalate');
  const canClose    = useHRMAC('help-center.tenant-tickets.close');

  const [viewTarget,      setViewTarget]      = useState(null);
  const [assignTarget,    setAssignTarget]    = useState(null);
  const [escalateTarget,  setEscalateTarget]  = useState(null);
  const [closeTarget,     setCloseTarget]     = useState(null);

  const replyForm  = useForm({ body: '' });
  const assignForm = useForm({ assignee_id: '' });

  const agentOptions = [
    { value: '', label: '— Unassigned —' },
    ...(agents ?? []).map(a => ({ value: String(a.id), label: a.name })),
  ];

  function openView(row) {
    setViewTarget(row);
    replyForm.reset();
  }

  function submitReply(e) {
    e.preventDefault();
    replyForm.post(route('platform.admin.help-center.tickets.reply', viewTarget.id), {
      preserveState: true,
      onSuccess: () => { toast.success('Reply sent.'); replyForm.reset(); },
      onError:   () => toast.error('Reply failed.'),
    });
  }

  function openAssign(row) {
    setAssignTarget(row);
    assignForm.setData('assignee_id', row.assignee_id ? String(row.assignee_id) : '');
  }

  function submitAssign(e) {
    e.preventDefault();
    assignForm.post(route('platform.admin.help-center.tickets.assign', assignTarget.id), {
      preserveState: true,
      onSuccess: () => { toast.success('Ticket assigned.'); setAssignTarget(null); },
      onError:   () => toast.error('Assignment failed.'),
    });
  }

  function confirmEscalate() {
    if (!escalateTarget) return;
    router.post(
      route('platform.admin.help-center.tickets.escalate', escalateTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Ticket escalated.'); setEscalateTarget(null); },
        onError:   () => toast.error('Escalation failed.'),
      }
    );
  }

  function confirmClose() {
    if (!closeTarget) return;
    router.post(
      route('platform.admin.help-center.tickets.close', closeTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Ticket closed.'); setCloseTarget(null); },
        onError:   () => toast.error('Close failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'subject',
      label: 'Subject',
      render: r => <Text>{r.subject}</Text>,
    },
    {
      key: 'tenant',
      label: 'Tenant',
      render: r => <Text tone="secondary">{r.tenant_name}</Text>,
    },
    {
      key: 'priority',
      label: 'Priority',
      render: r => (
        <Badge intent={PRIORITY_INTENT[r.priority] ?? 'neutral'}>
          {r.priority}
        </Badge>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: r => (
        <Badge intent={STATUS_INTENT[r.status] ?? 'neutral'}>
          {r.status}
        </Badge>
      ),
    },
    {
      key: 'assignee',
      label: 'Assignee',
      render: r => <Text tone="secondary">{r.assignee_name ?? '—'}</Text>,
    },
    {
      key: 'created_at',
      label: 'Opened',
      render: r => <Mono>{r.created_at}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          <Button intent="ghost" size="sm" onClick={() => openView(r)}>
            View / Reply
          </Button>
          {canAssign && (
            <Button intent="ghost" size="sm" onClick={() => openAssign(r)}>
              Assign
            </Button>
          )}
          {canEscalate && r.status !== 'closed' && (
            <Button intent="soft" size="sm" onClick={() => setEscalateTarget(r)}>
              Escalate
            </Button>
          )}
          {canClose && r.status !== 'closed' && (
            <Button intent="danger" size="sm" onClick={() => setCloseTarget(r)}>
              Close
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Support Tickets"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Help Center' },
        { label: 'Tickets' },
      ]}
      description="Manage tenant support tickets — assign, reply, escalate, and close."
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={tickets?.data ?? []}
          empty="No tickets in queue."
        />

        {(tickets?.last_page ?? 1) > 1 && (
          <Pagination
            page={tickets.current_page}
            total={tickets.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.help-center.tickets.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['tickets'] }
              )
            }
          />
        )}
      </VStack>

      {/* View / Reply Drawer */}
      <Drawer
        open={!!viewTarget}
        onClose={() => setViewTarget(null)}
        title={viewTarget ? `Ticket #${viewTarget.id} — ${viewTarget.subject}` : ''}
      >
        {viewTarget && (
          <VStack gap={4}>
            <HStack gap={2}>
              <Badge intent={PRIORITY_INTENT[viewTarget.priority] ?? 'neutral'}>
                {viewTarget.priority}
              </Badge>
              <Badge intent={STATUS_INTENT[viewTarget.status] ?? 'neutral'}>
                {viewTarget.status}
              </Badge>
            </HStack>

            <VStack gap={1}>
              <Text tone="secondary">From</Text>
              <Text>{viewTarget.user_email}</Text>
            </VStack>

            {/* Thread */}
            <VStack gap={2}>
              <Eyebrow>Thread</Eyebrow>
              {(viewTarget.messages ?? []).length === 0 ? (
                <Text tone="secondary">No messages yet.</Text>
              ) : (
                (viewTarget.messages ?? []).map(msg => (
                  <Card key={msg.id}>
                    <CardBody>
                      <VStack gap={1}>
                        <HStack gap={2}>
                          <Badge intent={msg.author_type === 'staff' ? 'success' : 'neutral'}>
                            {msg.author_type}
                          </Badge>
                          <Mono>{msg.created_at}</Mono>
                        </HStack>
                        <Text>{msg.body}</Text>
                      </VStack>
                    </CardBody>
                  </Card>
                ))
              )}
            </VStack>

            {/* Reply */}
            {canReply && viewTarget.status !== 'closed' && (
              <VStack gap={2}>
                <Eyebrow>Reply</Eyebrow>
                <Field label="Message" htmlFor="reply_body" error={replyForm.errors.body} required>
                  <Input
                    id="reply_body"
                    value={replyForm.data.body}
                    onChange={e => replyForm.setData('body', e.target.value)}
                    placeholder="Write your reply..."
                    error={replyForm.errors.body}
                  />
                </Field>
                <Button
                  intent="primary"
                  loading={replyForm.processing}
                  disabled={replyForm.processing}
                  onClick={submitReply}
                >
                  Send Reply
                </Button>
              </VStack>
            )}
          </VStack>
        )}
      </Drawer>

      {/* Assign Modal */}
      <Modal
        open={!!assignTarget}
        onClose={() => setAssignTarget(null)}
        title={`Assign Ticket #${assignTarget?.id}`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={assignForm.processing}
              disabled={assignForm.processing}
              onClick={submitAssign}
            >
              Save
            </Button>
            <Button intent="ghost" onClick={() => setAssignTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="Assign To" htmlFor="ticket_assignee" error={assignForm.errors.assignee_id}>
          <Select
            id="ticket_assignee"
            value={assignForm.data.assignee_id}
            onChange={e => assignForm.setData('assignee_id', e.target.value)}
            options={agentOptions}
          />
        </Field>
      </Modal>

      {/* Escalate Confirm Modal */}
      <Modal
        open={!!escalateTarget}
        onClose={() => setEscalateTarget(null)}
        title="Escalate Ticket"
        description={`Escalate ticket #${escalateTarget?.id} to senior support? The tenant will be notified.`}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={confirmEscalate}>
              Escalate
            </Button>
            <Button intent="ghost" onClick={() => setEscalateTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Close Confirm Modal */}
      <Modal
        open={!!closeTarget}
        onClose={() => setCloseTarget(null)}
        title="Close Ticket"
        description={`Close ticket #${closeTarget?.id}? The tenant will receive a closure notification.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmClose}>
              Close Ticket
            </Button>
            <Button intent="ghost" onClick={() => setCloseTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Tickets.layout = page => <App title="Support Tickets">{page}</App>;
