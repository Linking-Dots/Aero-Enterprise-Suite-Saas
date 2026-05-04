import { Link } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardContent,
  HStack, VStack,
  Text,
  Button,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function SecuritySettings() {
  const canViewPasswordPolicy = useHRMAC('core.settings.password_policy.view');
  const canViewIpWhitelist = useHRMAC('core.settings.ip_whitelist.view');

  return (
    <IndexPageLayout
      title="Security Settings"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Security Settings' },
      ]}
      description="Manage security-related configuration for your organization."
    >
      <VStack gap={4} className="w-full max-w-2xl">
        {canViewPasswordPolicy && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <Text variant="h4">Password Policy</Text>
                <Text size="sm" className="text-muted">
                  Configure password complexity rules, expiry settings, and restrictions.
                </Text>
                <HStack gap={2}>
                  <Button asChild variant="primary">
                    <Link href={route('core.settings.password-policy.index')}>
                      Manage Password Policy
                    </Link>
                  </Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}

        {canViewIpWhitelist && (
          <Card>
            <CardContent>
              <VStack gap={3}>
                <Text variant="h4">IP Access Control</Text>
                <Text size="sm" className="text-muted">
                  Manage IP-based access restrictions, whitelist, and blacklist entries.
                </Text>
                <HStack gap={2}>
                  <Button asChild variant="primary">
                    <Link href={route('core.settings.ip-whitelist.index')}>
                      Manage IP Access Control
                    </Link>
                  </Button>
                </HStack>
              </VStack>
            </CardContent>
          </Card>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

SecuritySettings.layout = page => (
  <App title="Security Settings">{page}</App>
);
