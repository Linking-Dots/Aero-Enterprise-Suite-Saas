import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  HStack, VStack,
  Text, Mono,
  Button,
  Badge,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function trustIntent(trusted) {
  return trusted ? 'success' : 'neutral';
}

export default function DevicesPage({ devices }) {
  const toast = useToast();
  const { auth } = usePage().props;
  const userId = auth?.user?.id;

  const [loadingId, setLoadingId] = useState(null);

  function toggleTrust(device) {
    setLoadingId(`trust-${device.id}`);
    router.post(
      `/users/${userId}/devices/toggle`,
      { device_id: device.id, trusted: !device.is_trusted },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () =>
          toast.success(device.is_trusted ? 'Device untrusted.' : 'Device trusted.'),
        onError: () => toast.error('Failed to update device trust.'),
        onFinish: () => setLoadingId(null),
      }
    );
  }

  function deactivate(device) {
    if (!confirm(`Remove device "${device.device_name}"?`)) return;
    setLoadingId(`deactivate-${device.id}`);
    router.delete(`/my-devices/${device.id}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Device removed.'),
      onError: () => toast.error('Failed to remove device.'),
      onFinish: () => setLoadingId(null),
    });
  }

  const columns = [
    {
      key: 'device_name',
      label: 'Device',
      width: '20%',
      render: row => (
        <VStack gap={0}>
          <HStack gap={2} align="center">
            <Text size="sm" weight={row.is_current ? 'semibold' : 'normal'}>
              {row.device_name || '—'}
            </Text>
            {row.is_current && (
              <Badge intent="amber">This device</Badge>
            )}
          </HStack>
          <Text tone="secondary" size="xs">{row.platform || '—'}</Text>
        </VStack>
      ),
    },
    {
      key: 'browser',
      label: 'Browser',
      width: '14%',
      render: row => <Text size="sm">{row.browser || '—'}</Text>,
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
      key: 'last_active_at',
      label: 'Last Active',
      width: '16%',
      render: row => row.last_active_at
        ? <Text size="sm">{new Date(row.last_active_at).toLocaleString()}</Text>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'trusted',
      label: 'Trusted',
      width: '10%',
      render: row => (
        <Badge intent={trustIntent(row.is_trusted)}>
          {row.is_trusted ? 'Trusted' : 'Untrusted'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '26%',
      align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          <Button
            intent={row.is_trusted ? 'ghost' : 'soft'}
            size="sm"
            loading={loadingId === `trust-${row.id}`}
            disabled={!!loadingId}
            onClick={() => toggleTrust(row)}
          >
            {row.is_trusted ? 'Untrust' : 'Trust'}
          </Button>
          {!row.is_current && (
            <Button
              intent="danger"
              size="sm"
              loading={loadingId === `deactivate-${row.id}`}
              disabled={!!loadingId}
              onClick={() => deactivate(row)}
            >
              Remove
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Trusted Devices"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Security', href: route('core.profile.security') },
        { label: 'Devices' },
      ]}
      description="Manage devices that are trusted to access your account."
      table={
        <DataTable
          columns={columns}
          rows={devices ?? []}
          empty="No devices registered."
        />
      }
    />
  );
}

DevicesPage.layout = page => (
  <App title="Trusted Devices">{page}</App>
);
