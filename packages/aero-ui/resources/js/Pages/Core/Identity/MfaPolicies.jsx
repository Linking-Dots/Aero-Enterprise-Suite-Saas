import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Card, CardContent,
  HStack, VStack,
  Text,
  Field,
  Input,
  Select,
  Checkbox,
  Toggle,
  Button,
  Badge,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const METHOD_OPTIONS = [
  { value: 'any',   label: 'Any method' },
  { value: 'totp',  label: 'TOTP (Authenticator app)' },
  { value: 'sms',   label: 'SMS code' },
  { value: 'email', label: 'Email code' },
];

function methodLabel(value) {
  return METHOD_OPTIONS.find(o => o.value === value)?.label ?? value;
}

export default function MfaPoliciesPage({ policies, roles }) {
  const toast     = useToast();
  const canCreate = useHRMAC('auth.sso_identity.mfa_policies.create');
  const canEdit   = useHRMAC('auth.sso_identity.mfa_policies.edit');
  const canDelete = useHRMAC('auth.sso_identity.mfa_policies.delete');

  const [modalOpen, setModalOpen] = useState(false);
  const [editingPolicy, setEditingPolicy] = useState(null);

  const emptyForm = {
    name:                  '',
    role_ids:              [],
    required_method:       'any',
    allow_remember_device: false,
    remember_device_days:  30,
    is_active:             true,
  };

  const form = useForm(emptyForm);

  function openCreate() {
    form.reset();
    form.setData(emptyForm);
    setEditingPolicy(null);
    setModalOpen(true);
  }

  function openEdit(policy) {
    setEditingPolicy(policy);
    form.setData({
      name:                  policy.name,
      role_ids:              policy.applies_to_roles?.map(r => r.id) ?? [],
      required_method:       policy.required_method ?? 'any',
      allow_remember_device: policy.allow_remember_device ?? false,
      remember_device_days:  policy.remember_device_days  ?? 30,
      is_active:             policy.is_active ?? true,
    });
    setModalOpen(true);
  }

  function closeModal() {
    setModalOpen(false);
    setEditingPolicy(null);
    form.reset();
  }

  function handleSubmit(e) {
    e.preventDefault();
    if (editingPolicy) {
      form.put(route('core.identity.mfa-policies.update', editingPolicy.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { toast.success('MFA policy updated.'); closeModal(); },
        onError:   () => toast.error('Failed to update MFA policy.'),
      });
    } else {
      form.post(route('core.identity.mfa-policies.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { toast.success('MFA policy created.'); closeModal(); },
        onError:   () => toast.error('Failed to create MFA policy.'),
      });
    }
  }

  function handleDelete(policy) {
    if (!confirm(`Delete MFA policy "${policy.name}"?`)) return;
    router.delete(route('core.identity.mfa-policies.destroy', policy.id), {
      preserveState: true,
      onSuccess: () => toast.success('MFA policy deleted.'),
      onError:   () => toast.error('Failed to delete MFA policy.'),
    });
  }

  function toggleRoleId(roleId) {
    const current = form.data.role_ids ?? [];
    const next = current.includes(roleId)
      ? current.filter(id => id !== roleId)
      : [...current, roleId];
    form.setData('role_ids', next);
  }

  const columns = [
    {
      key: 'name', label: 'Name', width: '22%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'applies_to_roles', label: 'Applies To', width: '28%',
      render: row => (
        <HStack gap={1} wrap>
          {row.applies_to_roles?.length
            ? row.applies_to_roles.map(r => (
                <Badge key={r.id} intent="neutral" size="sm">{r.name}</Badge>
              ))
            : <Text tone="secondary" size="sm">All roles</Text>
          }
        </HStack>
      ),
    },
    {
      key: 'required_method', label: 'Required Method', width: '18%',
      render: row => <Badge intent="neutral">{methodLabel(row.required_method)}</Badge>,
    },
    {
      key: 'allow_remember_device', label: 'Remember Device', width: '14%',
      render: row => (
        <Badge intent={row.allow_remember_device ? 'success' : 'neutral'}>
          {row.allow_remember_device ? `Yes (${row.remember_device_days}d)` : 'No'}
        </Badge>
      ),
    },
    {
      key: 'is_active', label: 'Status', width: '10%',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'warning'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '14%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="MFA Policies"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity', href: route('core.identity.index') },
        { label: 'MFA Policies' },
      ]}
      description="Define multi-factor authentication requirements per role or group."
      actions={
        canCreate && (
          <Button intent="primary" onClick={openCreate}>
            New Policy
          </Button>
        )
      }
      table={
        <DataTable
          columns={columns}
          rows={policies ?? []}
          empty="No MFA policies configured."
        />
      }
    >
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={editingPolicy ? 'Edit MFA Policy' : 'Create MFA Policy'}
        size="md"
      >
        <form onSubmit={handleSubmit}>
          <VStack gap={4}>
            <Field label="Policy name" htmlFor="policy_name" error={form.errors.name} required>
              <Input
                id="policy_name"
                value={form.data.name}
                onChange={e => form.setData('name', e.target.value)}
                error={form.errors.name}
                placeholder="e.g. Admins require TOTP"
                autoFocus
              />
            </Field>

            <Field label="Applies to roles" htmlFor="roles" hint="Leave all unchecked to apply to all roles." error={form.errors.role_ids}>
              <VStack gap={2}>
                {(roles ?? []).map(role => (
                  <Checkbox
                    key={role.id}
                    label={role.name}
                    checked={(form.data.role_ids ?? []).includes(role.id)}
                    onChange={() => toggleRoleId(role.id)}
                  />
                ))}
                {(!roles || roles.length === 0) && (
                  <Text tone="secondary" size="sm">No roles available.</Text>
                )}
              </VStack>
            </Field>

            <Field label="Required MFA method" htmlFor="required_method" error={form.errors.required_method} required>
              <Select
                id="required_method"
                value={form.data.required_method}
                onChange={e => form.setData('required_method', e.target.value)}
                options={METHOD_OPTIONS}
              />
            </Field>

            <Toggle
              label="Allow remember device"
              checked={form.data.allow_remember_device}
              onChange={e => form.setData('allow_remember_device', e.target.checked)}
            />

            {form.data.allow_remember_device && (
              <Field
                label="Remember device for (days)"
                htmlFor="remember_device_days"
                error={form.errors.remember_device_days}
                required
              >
                <Input
                  id="remember_device_days"
                  type="number"
                  value={form.data.remember_device_days}
                  onChange={e => form.setData('remember_device_days', parseInt(e.target.value, 10))}
                  error={form.errors.remember_device_days}
                  min={1}
                  max={365}
                />
              </Field>
            )}

            <Toggle
              label="Policy is active"
              checked={form.data.is_active}
              onChange={e => form.setData('is_active', e.target.checked)}
            />

            <HStack gap={2} justify="end">
              <Button type="button" intent="ghost" onClick={closeModal}>Cancel</Button>
              <Button type="submit" intent="primary" loading={form.processing}>
                {editingPolicy ? 'Save Changes' : 'Create Policy'}
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>
    </IndexPageLayout>
  );
}

MfaPoliciesPage.layout = page => (
  <App title="MFA Policies">{page}</App>
);
