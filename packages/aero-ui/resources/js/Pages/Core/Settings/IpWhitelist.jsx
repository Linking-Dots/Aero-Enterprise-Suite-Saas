import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Button,
  Card, CardContent,
  HStack, VStack,
  Text,
  Input,
  Checkbox,
  Select,
  Badge,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function IpWhitelist({ config }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.ip_whitelist.edit');
  const [newIp, setNewIp] = useState({ ip: '', label: '', list: 'whitelist' });

  const form = useForm({
    mode: config?.mode ?? 'disabled',
    log_blocked: config?.log_blocked ?? false,
    notify_on_blocked: config?.notify_on_blocked ?? false,
    whitelist: config?.whitelist ?? [],
    blacklist: config?.blacklist ?? [],
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    form.put(route('core.settings.ip-whitelist.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('IP access control updated successfully.'),
      onError: () => toast.error('Failed to update IP access control.'),
    });
  };

  const addIp = () => {
    if (!newIp.ip.trim()) return;
    const list = newIp.list;
    const current = [...form.data[list]];
    current.push({ ip: newIp.ip.trim(), label: newIp.label.trim() || null });
    form.setData(list, current);
    setNewIp({ ip: '', label: '', list });
  };

  const removeIp = (list, index) => {
    const current = [...form.data[list]];
    current.splice(index, 1);
    form.setData(list, current);
  };

  const modeOptions = [
    { value: 'disabled', label: 'Disabled' },
    { value: 'whitelist', label: 'Whitelist Only' },
    { value: 'blacklist', label: 'Blacklist Only' },
  ];

  const renderIpList = (listName, title, badgeColor) => (
    <VStack gap={2} className="w-full">
      <HStack gap={2} align="center">
        <Text weight="medium">{title}</Text>
        <Badge variant={badgeColor} size="sm">{form.data[listName].length}</Badge>
      </HStack>
      {form.data[listName].length === 0 && (
        <Text size="sm" className="text-muted">No entries</Text>
      )}
      {form.data[listName].map((entry, idx) => (
        <HStack key={idx} gap={2} align="center" className="w-full">
          <Text size="sm" className="font-mono flex-1">{entry.ip}</Text>
          {entry.label && (
            <Text size="sm" className="text-muted">{entry.label}</Text>
          )}
          {canEdit && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => removeIp(listName, idx)}
            >
              Remove
            </Button>
          )}
        </HStack>
      ))}
    </VStack>
  );

  return (
    <FormPageLayout
      title="IP Access Control"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Settings', href: route('core.settings.security.index') },
        { label: 'IP Whitelist' },
      ]}
      description="Manage IP-based access restrictions and blocking rules."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4} className="w-full max-w-2xl">
          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Access Mode</Text>
                <Select
                  value={form.data.mode}
                  onChange={e => form.setData('mode', e.target.value)}
                  options={modeOptions}
                />
                <VStack gap={2} align="start">
                  <Checkbox
                    label="Log Blocked Attempts"
                    checked={form.data.log_blocked}
                    onChange={e => form.setData('log_blocked', e.target.checked)}
                  />
                  <Checkbox
                    label="Notify on Blocked Access"
                    checked={form.data.notify_on_blocked}
                    onChange={e => form.setData('notify_on_blocked', e.target.checked)}
                  />
                </VStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                <Text weight="semibold">Add IP Entry</Text>
                <HStack gap={2} className="w-full">
                  <Input
                    placeholder="IP Address (e.g., 192.168.1.1)"
                    value={newIp.ip}
                    onChange={e => setNewIp(prev => ({ ...prev, ip: e.target.value }))}
                    className="flex-1"
                  />
                  <Input
                    placeholder="Label (optional)"
                    value={newIp.label}
                    onChange={e => setNewIp(prev => ({ ...prev, label: e.target.value }))}
                    className="flex-1"
                  />
                  <Select
                    value={newIp.list}
                    onChange={e => setNewIp(prev => ({ ...prev, list: e.target.value }))}
                    options={[
                      { value: 'whitelist', label: 'Whitelist' },
                      { value: 'blacklist', label: 'Blacklist' },
                    ]}
                  />
                  {canEdit && (
                    <Button type="button" variant="secondary" onClick={addIp}>
                      Add
                    </Button>
                  )}
                </HStack>
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                {renderIpList('whitelist', 'Allowed IPs', 'success')}
              </VStack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <VStack gap={4}>
                {renderIpList('blacklist', 'Blocked IPs', 'destructive')}
              </VStack>
            </CardContent>
          </Card>

          {canEdit && (
            <HStack gap={2} justify="end" className="w-full">
              <Button
                type="button"
                variant="secondary"
                onClick={() => form.reset()}
                disabled={form.processing}
              >
                Reset
              </Button>
              <Button
                type="submit"
                variant="primary"
                disabled={form.processing}
              >
                {form.processing ? 'Saving...' : 'Save Changes'}
              </Button>
            </HStack>
          )}
        </VStack>
      </form>
    </FormPageLayout>
  );
}

IpWhitelist.layout = page => (
  <App title="IP Access Control">{page}</App>
);
