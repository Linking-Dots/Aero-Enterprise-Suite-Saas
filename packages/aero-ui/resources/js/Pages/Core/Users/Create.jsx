import { useForm } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  FormPageLayout,
  Input,
  Select,
  Button,
  HStack,
  VStack,
  useToast,
  Checkbox,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

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
    roles:                 [],
    active:                true,
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

  const roleOptions = (roles ?? []).map(r => ({ value: r.name, label: r.name }));

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

          <Select
            label="Roles"
            multiple
            value={form.data.roles}
            onChange={e => {
              const opts = Array.from(e.target.selectedOptions).map(o => o.value);
              form.setData('roles', opts);
            }}
            options={roleOptions}
            error={form.errors.roles}
            placeholder="Select roles…"
          />

          <Checkbox
            label="Active"
            checked={form.data.active}
            onChange={e => form.setData('active', e.target.checked)}
          />

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
