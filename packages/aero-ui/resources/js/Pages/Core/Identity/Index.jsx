import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardContent,
  HStack, VStack,
  Text,
  Button,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const IDENTITY_TABS = [
  { key: 'saml',             label: 'SAML 2.0',        route: 'core.identity.saml.index',             perm: 'auth.sso_identity.sso_saml.view' },
  { key: 'oidc',             label: 'OIDC / OAuth',     route: 'core.identity.oidc.index',             perm: 'auth.sso_identity.sso_oidc.view' },
  { key: 'social',           label: 'Social Login',     route: 'core.identity.social.index',           perm: 'auth.sso_identity.social_login.view' },
  { key: 'scim',             label: 'SCIM',             route: 'core.identity.scim.index',             perm: 'auth.sso_identity.scim_provisioning.view' },
  { key: 'magic-link',       label: 'Magic Link',       route: 'core.identity.magic-link.index',       perm: 'auth.sso_identity.magic_link.view' },
  { key: 'passkeys',         label: 'Passkeys',         route: 'core.identity.passkeys.index',         perm: 'auth.sso_identity.passkeys.view' },
  { key: 'mfa-policies',     label: 'MFA Policies',     route: 'core.identity.mfa-policies.index',     perm: 'auth.sso_identity.mfa_policies.view' },
  { key: 'session-policies', label: 'Session Policies', route: 'core.identity.session-policies.index', perm: 'auth.sso_identity.session_policies.view' },
  { key: 'login-activity',   label: 'Login Activity',   route: 'core.identity.login-activity.index',   perm: 'auth.sso_identity.login_activity.view' },
];

function IdentityTabCard({ tab }) {
  const canView = useHRMAC(tab.perm);
  if (!canView) return null;

  return (
    <Card>
      <CardContent>
        <VStack gap={3}>
          <Text weight="semibold">{tab.label}</Text>
          <Button
            intent="soft"
            onClick={() => router.get(route(tab.route))}
          >
            Open
          </Button>
        </VStack>
      </CardContent>
    </Card>
  );
}

export default function IdentityIndex({ activeTab }) {
  const visibleTabs = IDENTITY_TABS.filter(() => true); // rendered individually with permission check

  return (
    <IndexPageLayout
      title="SSO & Identity"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'SSO & Identity' },
      ]}
      description="Manage single sign-on providers, authentication policies, and user provisioning."
    >
      <VStack gap={6}>
        <HStack gap={3} wrap>
          {IDENTITY_TABS.map(tab => (
            <TabButton key={tab.key} tab={tab} activeTab={activeTab} />
          ))}
        </HStack>
      </VStack>
    </IndexPageLayout>
  );
}

function TabButton({ tab, activeTab }) {
  const canView = useHRMAC(tab.perm);
  if (!canView) return null;

  const isActive = activeTab === tab.key;

  return (
    <Button
      intent={isActive ? 'primary' : 'soft'}
      onClick={() => router.get(route(tab.route))}
    >
      {tab.label}
    </Button>
  );
}

IdentityIndex.layout = page => (
  <App title="SSO & Identity">{page}</App>
);
