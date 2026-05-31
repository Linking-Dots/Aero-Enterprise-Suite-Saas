import { router, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Input,
  Checkbox,
  Button,
  HStack, VStack,
  Text,
  Card, CardContent,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function RolesEdit({ role, permissions }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.roles_permissions.roles.edit');

  // Group all permissions by first dotted segment (module)
  const grouped = (permissions ?? []).reduce((acc, p) => {
    const [module] = p.name.split('.');
    (acc[module] = acc[module] ?? []).push(p);
    return acc;
  }, {});

  const form = useForm({
    name:        role.name,
    permissions: role.permissions?.map(p => p.name) ?? [],
  });

  const handleSubmit = e => {
    e.preventDefault();
    form.put(route('core.roles.update', role.id), {
      onSuccess: () => {
        toast.success('Role updated successfully.');
        router.visit(route('core.roles.index'));
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to update role.');
      },
    });
  };

  const togglePerm = name => {
    const perms = form.data.permissions;
    form.setData('permissions',
      perms.includes(name) ? perms.filter(p => p !== name) : [...perms, name]
    );
  };

  const toggleModule = (module, perms) => {
    const names      = perms.map(p => p.name);
    const allChecked = names.every(n => form.data.permissions.includes(n));
    form.setData('permissions',
      allChecked
        ? form.data.permissions.filter(p => !names.includes(p))
        : [...new Set([...form.data.permissions, ...names])]
    );
  };

  const isSuperAdmin = role.name === 'super-admin';

  return (
    <FormPageLayout
      title={`Edit Role — ${role.name}`}
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Roles',     href: route('core.roles.index') },
        { label: role.name },
      ]}
      description="Update role name and assigned permissions."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={6} className="max-w-2xl">
          <Input
            label="Role Name *"
            value={form.data.name}
            onChange={e => form.setData('name', e.target.value)}
            error={form.errors.name}
            disabled={isSuperAdmin}
            required
          />

          <VStack gap={3}>
            <Text size="sm">Permissions</Text>
            {Object.entries(grouped).map(([module, perms]) => {
              const names      = perms.map(p => p.name);
              const allChecked = names.every(n => form.data.permissions.includes(n));
              return (
                <Card key={module}>
                  <CardContent>
                    <VStack gap={2}>
                      <HStack gap={2} align="center" justify="between">
                        <Text tone="secondary" size="sm">{module.toUpperCase()}</Text>
                        <Button
                          intent="ghost"
                          size="sm"
                          type="button"
                          onClick={() => toggleModule(module, perms)}
                        >
                          {allChecked ? 'Deselect All' : 'Select All'}
                        </Button>
                      </HStack>
                      <HStack gap={3} wrap>
                        {perms.map(p => (
                          <Checkbox
                            key={p.id}
                            label={p.name.split('.').slice(1).join('.')}
                            checked={form.data.permissions.includes(p.name)}
                            onChange={() => togglePerm(p.name)}
                          />
                        ))}
                      </HStack>
                    </VStack>
                  </CardContent>
                </Card>
              );
            })}
            {form.errors.permissions && (
              <Text tone="secondary" size="sm">{form.errors.permissions}</Text>
            )}
          </VStack>

          <HStack gap={3} className="pt-4">
            {canEdit && (
              <Button type="submit" intent="primary" disabled={form.processing}>
                {form.processing ? 'Saving…' : 'Save Changes'}
              </Button>
            )}
            <Button intent="soft" onClick={() => router.visit(route('core.roles.index'))}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

RolesEdit.layout = page => (
  <App title={`Edit Role — ${page.props.role?.name ?? ''}`}>{page}</App>
);
