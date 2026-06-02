import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Input,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function InvitationsIndex({ invitations }) {
  const toast      = useToast();
  const canInvite  = useHRMAC('core.user_management.users.invite');
  const canResend  = useHRMAC('core.user_management.user_invitations.resend');
  const canCancel  = useHRMAC('core.user_management.user_invitations.cancel');

  const [showModal, setShowModal] = useState(false);

  const form = useForm({ email: '' });

  const submit = e => {
    e.preventDefault();
    form.post(route('core.users.invitations.store'), {
      onSuccess: () => {
        toast.success('Invitation sent.');
        form.reset();
        setShowModal(false);
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to send invitation.');
      },
    });
  };

  const resend = id => {
    router.post(route('core.users.invitations.resend', id), {}, {
      onSuccess: () => toast.success('Invitation resent.'),
      onError:   () => toast.error('Failed to resend invitation'),
    });
  };

  const cancel = id => {
    if (!confirm('Cancel this invitation?')) return;
    router.delete(route('core.users.invitations.cancel', id), {
      onSuccess: () => toast.success('Invitation cancelled.'),
      onError:   () => toast.error('Failed to cancel invitation'),
    });
  };

  const items = Array.isArray(invitations)
    ? invitations
    : (invitations?.data ?? []);

  const columns = [
    { key: 'email',  label: 'Email',  render: row => row.email },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={row.accepted_at ? 'success' : 'warning'}>
          {row.accepted_at ? 'Accepted' : 'Pending'}
        </Badge>
      ),
    },
    {
      key: 'sent', label: 'Sent',
      render: row => new Date(row.created_at).toLocaleDateString(),
    },
    {
      key: 'actions', label: '', align: 'right',
      render: row => (
        !row.accepted_at ? (
          <HStack gap={2} justify="end">
            {canResend && (
              <Button intent="soft"   size="sm" onClick={() => resend(row.id)}>Resend</Button>
            )}
            {canCancel && (
              <Button intent="danger" size="sm" onClick={() => cancel(row.id)}>Cancel</Button>
            )}
          </HStack>
        ) : null
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Invitations"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Users',     href: route('core.users.index') },
          { label: 'Invitations' },
        ]}
        description="Manage pending and accepted user invitations."
        actions={
          canInvite && (
            <Button intent="primary" onClick={() => setShowModal(true)}>
              Invite User
            </Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={items}
            empty="No invitations found."
          />
        }
      />

      <Modal
        open={showModal}
        title="Invite User"
        onClose={() => setShowModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submit} loading={form.processing}>
              Send Invitation
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={3}>
            <Input
              label="Email Address *"
              type="email"
              value={form.data.email}
              onChange={e => form.setData('email', e.target.value)}
              error={form.errors.email}
              required
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

InvitationsIndex.layout = page => (
  <App title="Invitations">{page}</App>
);
