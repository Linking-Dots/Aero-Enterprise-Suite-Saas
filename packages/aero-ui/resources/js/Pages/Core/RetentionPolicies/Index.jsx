import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Select,
  HStack, VStack, Stack,
  Text, Heading,
  Badge,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function RetentionPoliciesIndex({ policies, entity_types, actions, schedules }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.retention_policies.policies.create');
  const canUpdate = useHRMAC('core.retention_policies.policies.update');
  const canDelete = useHRMAC('core.retention_policies.policies.delete');
  const canExecute = useHRMAC('core.retention_policies.policies.execute');

  const [showCreate, setShowCreate] = useState(false);
  const [editingPolicy, setEditingPolicy] = useState(null);

  const form = useForm({
    entity_type: 'audit_logs',
    action: 'delete',
    retention_days: 30,
    filters: {},
    is_active: true,
    schedule: 'daily',
    notes: '',
  });

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('core.retention-policies.store'), {
      onSuccess: () => {
        toast.success('Retention policy created successfully');
        form.reset();
        setShowCreate(false);
        router.reload();
      },
      onError: () => {
        toast.error('Failed to create retention policy');
      },
    });
  };

  const handleUpdate = (policy, data) => {
    router.put(route('core.retention-policies.update', { id: policy.id }), data, {
      onSuccess: () => {
        toast.success('Retention policy updated successfully');
        setEditingPolicy(null);
        router.reload();
      },
      onError: () => {
        toast.error('Failed to update retention policy');
      },
    });
  };

  const handleDelete = (policy) => {
    if (confirm('Are you sure you want to delete this retention policy?')) {
      router.delete(route('core.retention-policies.destroy', { id: policy.id }), {
        onSuccess: () => {
          toast.success('Retention policy deleted successfully');
          router.reload();
        },
      });
    }
  };

  const handleExecute = (policy) => {
    if (confirm('Are you sure you want to execute this retention policy? This will permanently delete data.')) {
      router.post(route('core.retention-policies.execute', { id: policy.id }), {}, {
        onSuccess: () => {
          toast.success('Retention policy executed successfully');
          router.reload();
        },
        onError: () => {
          toast.error('Failed to execute retention policy');
        },
      });
    }
  };

  const getActionColor = (action) => {
    return action === 'delete' ? 'error' : 'warning';
  };

  return (
    <App title="Retention Policies">
      <IndexPageLayout
        title="Retention Policies"
        breadcrumbs={[
          { label: 'Core', href: route('core.dashboard') },
          { label: 'Retention Policies' },
        ]}
      >
        <VStack spacing={6}>
          {canCreate && (
            <Card>
              <CardHeader>
                <Heading size="md">Create New Policy</Heading>
              </CardHeader>
              <CardBody>
                <form onSubmit={handleCreate}>
                  <VStack spacing={4}>
                    <Select
                      label="Entity Type"
                      value={form.data.entity_type}
                      onChange={(e) => form.setData('entity_type', e.target.value)}
                      options={Object.entries(entity_types).map(([key, label]) => ({ value: key, label }))}
                    />
                    <Select
                      label="Action"
                      value={form.data.action}
                      onChange={(e) => form.setData('action', e.target.value)}
                      options={Object.entries(actions).map(([key, label]) => ({ value: key, label }))}
                    />
                    <Select
                      label="Schedule"
                      value={form.data.schedule}
                      onChange={(e) => form.setData('schedule', e.target.value)}
                      options={Object.entries(schedules).map(([key, label]) => ({ value: key, label }))}
                    />
                    <Button type="submit" isLoading={form.processing}>
                      Create Policy
                    </Button>
                  </VStack>
                </form>
              </CardBody>
            </Card>
          )}

          <Card>
            <CardHeader>
              <Heading size="md">Active Policies</Heading>
            </CardHeader>
            <CardBody>
              {policies.length === 0 ? (
                <Text color="muted">No retention policies found</Text>
              ) : (
                <Stack spacing={3}>
                  {policies.map((policy) => (
                    <HStack key={policy.id} justify="space-between" align="center">
                      <VStack align="start" spacing={1}>
                        <HStack>
                          <Text fontWeight="medium">{entity_types[policy.entity_type]}</Text>
                          <Badge color={getActionColor(policy.action)}>{policy.action}</Badge>
                          {policy.is_active && <Badge color="success">Active</Badge>}
                        </HStack>
                        <Text size="sm" color="muted">
                          Retention: {policy.retention_days} days • Schedule: {policy.schedule} • Records processed: {policy.records_processed}
                        </Text>
                        {policy.next_run_at && (
                          <Text size="sm" color="muted">
                            Next run: {policy.next_run_at}
                          </Text>
                        )}
                      </VStack>
                      <HStack>
                        {canExecute && (
                          <Button size="sm" onClick={() => handleExecute(policy)}>
                            Execute
                          </Button>
                        )}
                        {canDelete && (
                          <Button size="sm" variant="ghost" onClick={() => handleDelete(policy)}>
                            Delete
                          </Button>
                        )}
                      </HStack>
                    </HStack>
                  ))}
                </Stack>
              )}
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
