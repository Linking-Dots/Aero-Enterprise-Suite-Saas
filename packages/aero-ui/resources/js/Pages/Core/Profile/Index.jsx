/**
 * My Profile — User self-service profile page.
 *
 * Props: { user: { name, email, profile_image, roles: [] } }
 *
 * Avatar: shows profile_image or initials via Avatar component.
 * Save Profile  → POST core.profile.update  (forceFormData: true)
 * Change Password → PUT core.profile.password
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, FileInput, Button,
  Avatar,
  Card, CardHeader, CardBody,
  HStack, VStack, Text, Divider,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader><Text weight="semibold">{title}</Text></CardHeader>
      <CardBody><VStack gap={4}>{children}</VStack></CardBody>
    </Card>
  );
}

export default function ProfileIndex({ user }) {
  const toast = useToast();

  const profileForm = useForm({
    name:   user?.name  ?? '',
    email:  user?.email ?? '',
    avatar: null,
  });

  const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
  });

  const submitProfile = (e) => {
    e.preventDefault();
    profileForm.post(route('core.profile.update'), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Profile updated.'),
      onError:   () => toast.error('Failed to update profile.'),
    });
  };

  const submitPassword = (e) => {
    e.preventDefault();
    passwordForm.put(route('core.profile.password'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Password updated.');
        passwordForm.reset();
      },
      onError: () => toast.error('Failed to update password.'),
    });
  };

  return (
    <FormPageLayout
      title="My Profile"
      breadcrumb={[{ label: 'My Profile' }]}
      description="Manage your account information, photo, and password."
    >
      <VStack gap={6}>
        <Section title="Profile">
          <form onSubmit={submitProfile}>
            <VStack gap={4}>
              <HStack gap={4} align="center">
                <Avatar
                  src={user?.profile_image ?? undefined}
                  name={user?.name ?? ''}
                  size={64}
                />
                <VStack gap={1}>
                  <Text size="sm" weight="medium">Profile Photo</Text>
                  <FileInput
                    accept="image/*"
                    label="Choose photo"
                    onChange={e => profileForm.setData('avatar', e.target.files[0] ?? null)}
                  />
                </VStack>
              </HStack>

              <Field label="Full Name" error={profileForm.errors.name} required>
                <Input
                  value={profileForm.data.name}
                  onChange={e => profileForm.setData('name', e.target.value)}
                  placeholder="Your full name"
                />
              </Field>

              <Field label="Email" error={profileForm.errors.email} required>
                <Input
                  type="email"
                  value={profileForm.data.email}
                  onChange={e => profileForm.setData('email', e.target.value)}
                  placeholder="you@example.com"
                />
              </Field>

              <HStack justify="end">
                <Button type="submit" intent="primary" loading={profileForm.processing}>
                  Save Profile
                </Button>
              </HStack>
            </VStack>
          </form>
        </Section>

        <Section title="Change Password">
          <form onSubmit={submitPassword}>
            <VStack gap={4}>
              <Field label="Current Password" error={passwordForm.errors.current_password} required>
                <Input
                  type="password"
                  value={passwordForm.data.current_password}
                  onChange={e => passwordForm.setData('current_password', e.target.value)}
                />
              </Field>

              <Field label="New Password" error={passwordForm.errors.password} required>
                <Input
                  type="password"
                  value={passwordForm.data.password}
                  onChange={e => passwordForm.setData('password', e.target.value)}
                />
              </Field>

              <Field label="Confirm New Password" error={passwordForm.errors.password_confirmation} required>
                <Input
                  type="password"
                  value={passwordForm.data.password_confirmation}
                  onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                />
              </Field>

              <HStack justify="end">
                <Button type="submit" intent="primary" loading={passwordForm.processing}>
                  Update Password
                </Button>
              </HStack>
            </VStack>
          </form>
        </Section>
      </VStack>
    </FormPageLayout>
  );
}

ProfileIndex.layout = page => <App title="My Profile">{page}</App>;
