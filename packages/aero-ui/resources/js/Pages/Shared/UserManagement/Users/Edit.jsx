import { useForm, router } from '@inertiajs/react';
import {
  FormPageLayout,
  Input,
  Button,
  HStack,
  VStack,
  Text,
  Card,
  CardContent,
  useToast,
  Checkbox,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function UsersEdit({ user, roles, routePrefix, hrmacNamespace, scope, dashboardRoute }) {
  routePrefix    = routePrefix    ?? 'core.users';
  hrmacNamespace = hrmacNamespace ?? 'core.user_management';
  scope          = scope          ?? 'tenant';
  dashboardRoute = dashboardRoute ?? (scope === 'platform' ? 'platform.admin.dashboard' : 'core.dashboard');

  const toast   = useToast();
  const canEdit = useHRMAC(`${hrmacNamespace}.users.edit`);

  const form = useForm({
    name:                  user.name              || '',
    email:                 user.email             || '',
    user_name:             user.user_name         || '',
    phone:                 user.phone             || '',
    password:              '',
    password_confirmation: '',
    role_ids:              user.roles?.map(r => r.id) || [],
  });

  const handleSubmit = e => {
    e.preventDefault();
    form.put(route(`${routePrefix}.update`, user.id), {
      onSuccess: () => {
        toast.success('User updated successfully.');
        router.visit(route(`${routePrefix}.index`));
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to update user.');
      },
    });
  };

  const toggleRole = id => {
    const current = form.data.role_ids;
    form.setData('role_ids',
      current.includes(id) ? current.filter(r => r !== id) : [...current, id]
    );
  };

  return (
    <FormPageLayout
      title="Edit User"
      breadcrumb={[
        { label: 'Dashboard', href: route(dashboardRoute) },
        { label: 'Users',     href: route(`${routePrefix}.index`) },
        { label: user.name },
      ]}
      description="Update user account details and roles."
    >
      <form onSubmit={handleSubmit}>
        <VStack gap={4} className="max-w-2xl">
          <Input
            label="Full Name *"
            value={form.data.name}
            onChange={e => form.setData('name', e.target.value)}
            error={form.errors.name}
            required
          />

          <Input
            label="Email Address *"
            type="email"
            value={form.data.email}
            onChange={e => form.setData('email', e.target.value)}
            error={form.errors.email}
            required
          />

          <Input
            label="Username"
            value={form.data.user_name}
            onChange={e => form.setData('user_name', e.target.value)}
            error={form.errors.user_name}
          />

          <Input
            label="Phone"
            type="tel"
            value={form.data.phone}
            onChange={e => form.setData('phone', e.target.value)}
            error={form.errors.phone}
          />

          <Input
            label="New Password"
            type="password"
            value={form.data.password}
            onChange={e => form.setData('password', e.target.value)}
            error={form.errors.password}
            placeholder="Leave blank to keep current password"
          />

          {form.data.password && (
            <Input
              label="Confirm New Password"
              type="password"
              value={form.data.password_confirmation}
              onChange={e => form.setData('password_confirmation', e.target.value)}
              error={form.errors.password_confirmation}
            />
          )}

          <VStack gap={2}>
            <Text size="sm">Roles</Text>
            <Card>
              <CardContent>
                <HStack gap={3} wrap>
                  {(roles ?? []).map(r => (
                    <Checkbox
                      key={r.id ?? r.name}
                      label={r.name}
                      checked={form.data.role_ids.includes(r.id)}
                      onChange={() => toggleRole(r.id)}
                    />
                  ))}
                </HStack>
              </CardContent>
            </Card>
            {form.errors.role_ids && (
              <Text tone="secondary" size="sm">{form.errors.role_ids}</Text>
            )}
          </VStack>

          <HStack gap={3} className="pt-4">
            {canEdit && (
              <Button type="submit" intent="primary" disabled={form.processing}>
                {form.processing ? 'Saving…' : 'Save Changes'}
              </Button>
            )}
            <Button intent="soft" onClick={() => router.visit(route(`${routePrefix}.index`))}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

UsersEdit.layout = page => (
  <App title="Edit User">{page}</App>
);
