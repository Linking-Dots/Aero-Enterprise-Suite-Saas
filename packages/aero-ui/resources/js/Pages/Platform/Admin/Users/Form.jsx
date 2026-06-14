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
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function UsersForm({ user, roles }) {
  const toast  = useToast();
  const isEdit = !!user;

  const form = useForm({
    name:     user?.name     ?? '',
    email:    user?.email    ?? '',
    password: '',
    role_ids: user?.landlord_roles?.map(r => r.id) ?? [],
  });

  function handleSubmit(e) {
    e.preventDefault();
    if (isEdit) {
      form.put(route('platform.admin.users.update', user.id), {
        onSuccess: () => toast.success('User updated.'),
        onError:   () => toast.error('Failed to update user.'),
      });
    } else {
      form.post(route('platform.admin.users.store'), {
        onSuccess: () => toast.success('User created.'),
        onError:   () => toast.error('Failed to create user.'),
      });
    }
  }

  function toggleRole(id) {
    const current = form.data.role_ids;
    form.setData(
      'role_ids',
      current.includes(id) ? current.filter(r => r !== id) : [...current, id]
    );
  }

  return (
    <FormPageLayout
      title={isEdit ? 'Edit User' : 'Invite User'}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Users', href: route('platform.admin.users.index') },
        { label: isEdit ? 'Edit' : 'Invite' },
      ]}
      description={isEdit ? 'Update user details and role assignments.' : 'Create a new landlord user account.'}
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Account Details</Eyebrow>

              <Field label="Full Name" htmlFor="name" error={form.errors.name} required>
                <Input
                  id="name"
                  value={form.data.name}
                  onChange={e => form.setData('name', e.target.value)}
                  placeholder="Jane Smith"
                  error={form.errors.name}
                />
              </Field>

              <Field label="Email Address" htmlFor="email" error={form.errors.email} required>
                <Input
                  id="email"
                  type="email"
                  value={form.data.email}
                  onChange={e => form.setData('email', e.target.value)}
                  placeholder="jane@example.com"
                  leftIcon="mail"
                  error={form.errors.email}
                />
              </Field>

              <Field
                label={isEdit ? 'New Password' : 'Password'}
                htmlFor="password"
                error={form.errors.password}
                hint={isEdit ? 'Leave blank to keep the current password.' : undefined}
                required={!isEdit}
              >
                <Input
                  id="password"
                  type="password"
                  value={form.data.password}
                  onChange={e => form.setData('password', e.target.value)}
                  placeholder={isEdit ? '••••••••' : 'Minimum 8 characters'}
                  error={form.errors.password}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Role Assignment</Eyebrow>

              {(roles ?? []).length === 0 ? (
                <Text tone="secondary">No roles available. Create roles first.</Text>
              ) : (
                <VStack gap={2}>
                  {(roles ?? []).map(role => (
                    <Toggle
                      key={role.id}
                      label={role.name}
                      checked={form.data.role_ids.includes(role.id)}
                      onChange={() => toggleRole(role.id)}
                    />
                  ))}
                </VStack>
              )}

              {form.errors.role_ids && (
                <Text tone="secondary">{form.errors.role_ids}</Text>
              )}
            </VStack>
          </CardBody>
        </Card>

        <HStack gap={3}>
          <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
            {isEdit ? 'Update User' : 'Create User'}
          </Button>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('platform.admin.users.index'))}
          >
            Cancel
          </Button>
        </HStack>

      </VStack>
    </FormPageLayout>
  );
}

UsersForm.layout = page => <App title={page.props?.user ? 'Edit User' : 'Invite User'}>{page}</App>;
