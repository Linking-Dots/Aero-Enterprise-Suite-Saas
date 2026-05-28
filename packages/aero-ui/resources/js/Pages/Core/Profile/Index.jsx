/**
 * My Profile — User self-service profile page.
 *
 * Lets the user edit their name, email, and avatar, plus
 * change password. Avatar upload uses Inertia useForm with
 * forceFormData. Password change is a separate form.
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Button, Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
} from '@aero/ui';
import App from '../../App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader><Text weight="semibold">{title}</Text></CardHeader>
      <CardBody><VStack gap={4}>{children}</VStack></CardBody>
    </Card>
  );
}

function initials(name) {
  if (!name) return '?';
  return name.split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase();
}

export default function ProfileIndex({ user }) {
  const toast = useToast();

  const profileForm = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    avatar: null,
  });

  const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const submitProfile = (e) => {
    e.preventDefault();
    profileForm.post(route('core.profile.update'), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Profile updated.'),
      onError: () => toast.error('Failed to update profile.'),
    });
  };

  const submitPassword = (e) => {
    e.preventDefault();
    passwordForm.post(route('core.profile.password'), {
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
                {user?.avatar_url ? (
                  <img
                    src={user.avatar_url}
                    alt={user.name}
                    style={{ width: 64, height: 64, borderRadius: '50%', objectFit: 'cover' }}
                  />
                ) : (
                  <div
                    style={{
                      width: 64,
                      height: 64,
                      borderRadius: '50%',
                      background: 'var(--primary)',
                      color: 'white',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      fontWeight: 'bold',
                      fontSize: 20,
                    }}
                  >
                    {initials(user?.name)}
                  </div>
                )}
                <div>
                  <Text size="sm" weight="medium">Profile Photo</Text>
                  <input
                    type="file"
                    accept="image/*"
                    onChange={(e) => profileForm.setData('avatar', e.target.files[0] ?? null)}
                    style={{ display: 'block', marginTop: 4 }}
                  />
                </div>
              </HStack>
              <Field label="Full Name" error={profileForm.errors.name} required>
                <Input
                  value={profileForm.data.name}
                  onChange={(e) => profileForm.setData('name', e.target.value)}
                />
              </Field>
              <Field label="Email" error={profileForm.errors.email} required>
                <Input
                  type="email"
                  value={profileForm.data.email}
                  onChange={(e) => profileForm.setData('email', e.target.value)}
                />
              </Field>
              <HStack justify="end">
                <Button type="submit" variant="primary" loading={profileForm.processing}>
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
                  onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                />
              </Field>
              <Field label="New Password" error={passwordForm.errors.password} required>
                <Input
                  type="password"
                  value={passwordForm.data.password}
                  onChange={(e) => passwordForm.setData('password', e.target.value)}
                />
              </Field>
              <Field label="Confirm New Password" required>
                <Input
                  type="password"
                  value={passwordForm.data.password_confirmation}
                  onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                />
              </Field>
              <HStack justify="end">
                <Button type="submit" variant="primary" loading={passwordForm.processing}>
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

ProfileIndex.layout = (page) => <App title="My Profile">{page}</App>;
