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

export default function UsersCreate({ roles }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.user_management.users.create');

  const form = useForm({
    name:                  '',
    email:                 '',
    user_name:             '',
    phone:                 '',
    password:              '',
    password_confirmation: '',
    role_ids:              [],
  });

  const handleSubmit = e => {
    e.preventDefault();
    form.post(route('core.users.store'), {
      onSuccess: () => {
        toast.success('User created successfully.');
        router.visit(route('core.users.index'));
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to create user.');
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
      title="Create User"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Users',     href: route('core.users.index') },
        { label: 'Create' },
      ]}
      description="Add a new user account."
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
            label="Password *"
            type="password"
            value={form.data.password}
            onChange={e => form.setData('password', e.target.value)}
            error={form.errors.password}
            required
          />

          <Input
            label="Confirm Password *"
            type="password"
            value={form.data.password_confirmation}
            onChange={e => form.setData('password_confirmation', e.target.value)}
            error={form.errors.password_confirmation}
            required
          />

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
            {canCreate && (
              <Button type="submit" intent="primary" disabled={form.processing}>
                {form.processing ? 'Creating…' : 'Create User'}
              </Button>
            )}
            <Button intent="soft" onClick={() => router.visit(route('core.users.index'))}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </form>
    </FormPageLayout>
  );
}

UsersCreate.layout = page => (
  <App title="Create User">{page}</App>
);
