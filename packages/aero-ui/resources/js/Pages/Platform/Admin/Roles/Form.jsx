import { router, useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  Toggle,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function RolesForm({ role, availablePermissions }) {
  const toast     = useToast();
  const isEdit    = !!role;
  const canAssign = useHRMAC('platform-users.module-access.manage');

  // Group permissions by submodule prefix (first segment of path)
  const grouped = (availablePermissions ?? []).reduce((acc, perm) => {
    const [submodule] = perm.path.split('.');
    if (!acc[submodule]) acc[submodule] = [];
    acc[submodule].push(perm);
    return acc;
  }, {});

  const form = useForm({
    name:        role?.name        ?? '',
    description: role?.description ?? '',
    permissions: role?.permissions ?? [],
  });

  function handleSubmit(e) {
    e.preventDefault();
    if (isEdit) {
      form.put(route('platform.admin.roles.update', role.id), {
        onSuccess: () => toast.success('Role updated.'),
        onError:   () => toast.error('Failed to update role.'),
      });
    } else {
      form.post(route('platform.admin.roles.store'), {
        onSuccess: () => toast.success('Role created.'),
        onError:   () => toast.error('Failed to create role.'),
      });
    }
  }

  function togglePermission(path) {
    const current = form.data.permissions;
    form.setData(
      'permissions',
      current.includes(path)
        ? current.filter(p => p !== path)
        : [...current, path]
    );
  }

  function toggleGroup(perms) {
    const paths = perms.map(p => p.path);
    const allSelected = paths.every(p => form.data.permissions.includes(p));
    if (allSelected) {
      form.setData('permissions', form.data.permissions.filter(p => !paths.includes(p)));
    } else {
      const merged = [...new Set([...form.data.permissions, ...paths])];
      form.setData('permissions', merged);
    }
  }

  return (
    <FormPageLayout
      title={isEdit ? `Edit Role: ${role.name}` : 'New Role'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.p1.dashboard') },
        { label: 'Roles', href: route('platform.admin.roles.index') },
        { label: isEdit ? 'Edit' : 'New' },
      ]}
      description={isEdit ? 'Update role name, description and permissions.' : 'Create a new landlord role with a permission set.'}
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Role Details</Eyebrow>

              <Field label="Role Name" htmlFor="name" error={form.errors.name} required>
                <Input
                  id="name"
                  value={form.data.name}
                  onChange={e => form.setData('name', e.target.value)}
                  placeholder="e.g. Billing Manager"
                  error={form.errors.name}
                  disabled={role?.is_system}
                />
              </Field>

              <Field label="Description" htmlFor="description" error={form.errors.description} hint="Briefly describe what this role allows.">
                <Input
                  id="description"
                  value={form.data.description}
                  onChange={e => form.setData('description', e.target.value)}
                  placeholder="Manages billing and invoicing."
                  error={form.errors.description}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {canAssign && (availablePermissions ?? []).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Permissions</Eyebrow>
                <Text tone="secondary">
                  {form.data.permissions.length} of {availablePermissions.length} permissions selected.
                </Text>

                {Object.entries(grouped).map(([submodule, perms]) => {
                  const allSelected = perms.every(p => form.data.permissions.includes(p.path));
                  return (
                    <Card key={submodule}>
                      <CardBody>
                        <VStack gap={3}>
                          <HStack gap={2} align="center">
                            <Toggle
                              label={submodule.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}
                              checked={allSelected}
                              onChange={() => toggleGroup(perms)}
                            />
                          </HStack>
                          <VStack gap={2}>
                            {perms.map(perm => (
                              <Toggle
                                key={perm.path}
                                label={perm.label}
                                checked={form.data.permissions.includes(perm.path)}
                                onChange={() => togglePermission(perm.path)}
                              />
                            ))}
                          </VStack>
                        </VStack>
                      </CardBody>
                    </Card>
                  );
                })}

                {form.errors.permissions && (
                  <Text tone="secondary">{form.errors.permissions}</Text>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}

        <HStack gap={3}>
          <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
            {isEdit ? 'Update Role' : 'Create Role'}
          </Button>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.roles.index'))}
          >
            Cancel
          </Button>
        </HStack>

      </VStack>
    </FormPageLayout>
  );
}

RolesForm.layout = page => <App title={page.props?.role ? 'Edit Role' : 'New Role'}>{page}</App>;
