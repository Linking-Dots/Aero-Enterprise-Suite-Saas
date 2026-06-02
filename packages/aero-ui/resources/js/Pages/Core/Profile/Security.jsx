import { Link } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent,
  Button, Badge,
  HStack, VStack, Text, Mono, Avatar,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function SecurityLink({ title, description, value, badge, href }) {
  return (
    <Card interactive as={Link} href={href}>
      <CardContent>
        <HStack justify="between" align="center">
          <VStack gap={0}>
            <Text weight="semibold">{title}</Text>
            <Text tone="secondary" size="sm">{description}</Text>
          </VStack>
          <HStack gap={2} align="center">
            {value && <Mono size="sm" tone="tertiary">{value}</Mono>}
            {badge && <Badge intent={badge.intent}>{badge.label}</Badge>}
          </HStack>
        </HStack>
      </CardContent>
    </Card>
  );
}

export default function ProfileSecurity({ user }) {
  return (
    <App>
      <IndexPageLayout title="My Account">
        {/* Identity */}
        <Card style={{ marginBottom: 24 }}>
          <CardContent>
            <HStack gap={4} align="center">
              <Avatar name={user.name} src={user.avatar_url} size="lg" />
              <VStack gap={0} className="aeos-flex-1">
                <Text size="lg" weight="semibold">{user.name}</Text>
                <Mono tone="tertiary">{user.email}</Mono>
              </VStack>
              <Button intent="ghost" size="sm" as={Link} href={route('core.users.edit', user.id)}>
                Edit profile
              </Button>
            </HStack>
          </CardContent>
        </Card>

        {/* Security links */}
        <Text weight="semibold" className="aeos-mb-2">Security</Text>
        <VStack gap={3}>
          <SecurityLink
            title="Two-Factor Authentication"
            description="Add an extra layer of security to your account."
            badge={user.two_factor_enabled
              ? { label: 'Enabled', intent: 'success' }
              : { label: 'Disabled', intent: 'neutral' }}
            href={route('auth.two-factor.index')}
          />
          <SecurityLink
            title="Trusted Devices"
            description="Manage devices that are allowed to log in to your account."
            value={`${user.registered_devices} device${user.registered_devices !== 1 ? 's' : ''}`}
            href={route('core.devices.index')}
          />
          <SecurityLink
            title="Active Sessions"
            description="View and manage all sessions currently signed in to your account."
            value={`${user.active_sessions} active`}
            href={route('core.security.sessions.index')}
          />
          <SecurityLink
            title="Change Password"
            description="Update your account password."
            href={route('password.request')}
          />
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
