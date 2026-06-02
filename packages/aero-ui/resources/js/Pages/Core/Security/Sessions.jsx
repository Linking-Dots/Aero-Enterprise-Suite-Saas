import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  HStack, VStack,
  Text, Mono,
  Button,
  Badge,
  Alert,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function deviceTypeIntent(type) {
  switch ((type ?? '').toLowerCase()) {
    case 'mobile':  return 'amber';
    case 'tablet':  return 'info';
    case 'desktop': return 'neutral';
    default:        return 'neutral';
  }
}

export default function SessionsPage({ sessions }) {
  const toast = useToast();
  const [terminatingId, setTerminatingId] = useState(null);
  const [terminatingAll, setTerminatingAll] = useState(false);

  function terminateSession(session) {
    if (!confirm('Terminate this session?')) return;
    setTerminatingId(session.id);
    router.delete(`/security/sessions/${session.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Session terminated.'),
      onError: () => toast.error('Failed to terminate session.'),
      onFinish: () => setTerminatingId(null),
    });
  }

  function terminateAllOthers() {
    if (!confirm('Terminate all other sessions? You will remain logged in on this device only.')) return;
    setTerminatingAll(true);
    router.delete('/security/sessions', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('All other sessions terminated.'),
      onError: () => toast.error('Failed to terminate sessions.'),
      onFinish: () => setTerminatingAll(false),
    });
  }

  const otherSessionCount = (sessions ?? []).filter(s => !s.is_current).length;

  const columns = [
    {
      key: 'device_type',
      label: 'Device',
      width: '14%',
      render: row => (
        <HStack gap={2} align="center">
          <Badge intent={deviceTypeIntent(row.device_type)}>
            {row.device_type || 'Unknown'}
          </Badge>
          {row.is_current && (
            <Badge intent="success">This device</Badge>
          )}
        </HStack>
      ),
    },
    {
      key: 'ip_address',
      label: 'IP Address',
      width: '14%',
      render: row => row.ip_address
        ? <Mono size="sm">{row.ip_address}</Mono>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'browser',
      label: 'Browser',
      width: '18%',
      render: row => (
        <VStack gap={0}>
          <Text size="sm">{row.browser || '—'}</Text>
          {row.user_agent && (
            <Text tone="tertiary" size="xs">{row.user_agent.slice(0, 60)}{row.user_agent.length > 60 ? '…' : ''}</Text>
          )}
        </VStack>
      ),
    },
    {
      key: 'location',
      label: 'Location',
      width: '16%',
      render: row => row.location
        ? <Text size="sm">{row.location}</Text>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'last_activity',
      label: 'Last Active',
      width: '16%',
      render: row => row.last_activity
        ? <Text size="sm">{new Date(row.last_activity * 1000).toLocaleString()}</Text>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'actions',
      label: '',
      width: '10%',
      align: 'right',
      render: row => {
        if (row.is_current) return null;
        return (
          <Button
            intent="danger"
            size="sm"
            loading={terminatingId === row.id}
            disabled={!!terminatingId || terminatingAll}
            onClick={() => terminateSession(row)}
          >
            Terminate
          </Button>
        );
      },
    },
  ];

  return (
    <IndexPageLayout
      title="Active Sessions"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Security', href: route('core.profile.security') },
        { label: 'Sessions' },
      ]}
      description="View and manage all sessions currently signed in to your account."
      actions={
        otherSessionCount > 0 && (
          <Button
            intent="danger"
            leftIcon="xCircle"
            loading={terminatingAll}
            disabled={terminatingAll || !!terminatingId}
            onClick={terminateAllOthers}
          >
            Terminate All Other Sessions
          </Button>
        )
      }
      table={
        <VStack gap={3}>
          {otherSessionCount === 0 && (sessions ?? []).length > 0 && (
            <Alert intent="info" title="Only one active session">
              You have no other active sessions. Only this device is currently signed in.
            </Alert>
          )}
          <DataTable
            columns={columns}
            rows={sessions ?? []}
            empty="No active sessions found."
          />
        </VStack>
      }
    />
  );
}

SessionsPage.layout = page => (
  <App title="Active Sessions">{page}</App>
);
