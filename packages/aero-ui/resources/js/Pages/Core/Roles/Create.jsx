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
import App from '@/Pages/App.jsx';

export default function RolesCreate({ permissions }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.roles_permissions.roles.create');

  // Group permissions by first dotted segment (module)
  const grouped = (permissions ?? []).reduce((acc, p) => {
    const [module] = p.name.split('.');
    (acc[module] = acc[module] ?? []).push(p);
    return acc;
  }, {});

  const form = useForm({ name: '', permissions: [] });

  const handleSubmit = e => {
    e.preventDefault();
    form.post(route('core.roles.store'), {
      onSuccess: () => {
        toast.success('Role created successfully.');
        router.visit(route('core.roles.index'));
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to create role.');
      },
    });
  };

  const togglePerm = name => {
    const perms = form.data.permissions;
    form.setData('permissions',
      perms.includes(name) ? perms.filter(p => p !== name) : [...perms, name]
    );
  };

  return (
    <FormPageLayout
      title="Create Role"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Roles',     href: route('core.roles.index') },
        { label: 'Create' },
      ]}
      description="Define a new role and assign permissions."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={6} className="max-w-2xl">
          <Input
            label="Role Name *"
            value={form.data.name}
            onChange={e => form.setData('name', e.target.value)}
            error={form.errors.name}
            required
          />

          <VStack gap={3}>
            <Text size="sm">Permissions</Text>
            {Object.entries(grouped).map(([module, perms]) => (
              <Card key={module}>
                <CardContent>
                  <VStack gap={2}>
                    <Text tone="secondary" size="sm">{module.toUpperCase()}</Text>
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
            ))}
            {form.errors.permissions && (
              <Text tone="secondary" size="sm">{form.errors.permissions}</Text>
            )}
          </VStack>

          <HStack gap={3} className="pt-4">
            {canCreate && (
              <Button type="submit" intent="primary" disabled={form.processing}>
                {form.processing ? 'Creating…' : 'Create Role'}
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

RolesCreate.layout = page => (
  <App title="Create Role">{page}</App>
);
