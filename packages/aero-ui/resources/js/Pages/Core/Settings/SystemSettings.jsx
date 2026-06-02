/**
 * System Settings — tabbed navigation hub for all settings sub-routes.
 *
 * Each tab navigates to its own route. Active tab is derived from the
 * current URL. Below the tabs the passed `settings` object is rendered
 * as a read-only key→value list.
 */
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  HStack, VStack,
  Text, Mono, Eyebrow,
  Button,
  Badge,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TABS = [
  { label: 'General',          routeName: 'core.settings.system' },
  { label: 'Security',         routeName: 'core.settings.security' },
  { label: 'Localization',     routeName: 'core.settings.localization' },
  { label: 'Branding',         routeName: 'core.settings.branding' },
  { label: 'Email / SMTP',     routeName: 'core.settings.mail' },
  { label: 'Password Policy',  routeName: 'core.settings.password-policy' },
  { label: 'IP Access',        routeName: 'core.settings.ip-whitelist' },
  { label: 'Email Templates',  routeName: 'core.settings.email-templates.index' },
];

function resolveHref(routeName) {
  try { return route(routeName); } catch { return '#'; }
}

function isActiveTab(href, currentUrl) {
  if (!href || href === '#') return false;
  return currentUrl.startsWith(href);
}

export default function SystemSettings({ settings = {} }) {
  const { url } = usePage().props;
  const currentUrl = url ?? (typeof window !== 'undefined' ? window.location.pathname : '');
  const canEdit = useHRMAC('core.settings.general.edit');

  const settingEntries = Object.entries(settings);

  return (
    <IndexPageLayout
      title="System Settings"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Settings' },
      ]}
      description="Navigate to any settings area using the tabs below."
    >
      <VStack gap={6}>

        {/* ── Tab Navigation ── */}
        <Card>
          <CardHeader>
            <Eyebrow>Settings Areas</Eyebrow>
          </CardHeader>
          <CardBody>
            <HStack gap={2} wrap>
              {TABS.map(tab => {
                const href = resolveHref(tab.routeName);
                const active = isActiveTab(href, currentUrl);
                return (
                  <Button
                    key={tab.routeName}
                    intent={active ? 'primary' : 'soft'}
                    size="sm"
                    onClick={() => router.get(href)}
                  >
                    {tab.label}
                  </Button>
                );
              })}
            </HStack>
          </CardBody>
        </Card>

        {/* ── Settings Key-Value List ── */}
        {settingEntries.length > 0 && (
          <Card>
            <CardHeader>
              <Text size="sm" tone="secondary">Current Settings</Text>
            </CardHeader>
            <CardBody>
              <VStack gap={3}>
                {settingEntries.map(([key, value]) => (
                  <HStack key={key} gap={4} align="start">
                    <Mono size="sm" tone="secondary">{key}</Mono>
                    <Text size="sm">
                      {value === null || value === undefined
                        ? <Text tone="tertiary" size="sm">—</Text>
                        : typeof value === 'boolean'
                          ? <Badge intent={value ? 'success' : 'neutral'}>{value ? 'true' : 'false'}</Badge>
                          : String(value)}
                    </Text>
                  </HStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {settingEntries.length === 0 && (
          <Card>
            <CardBody>
              <Text tone="secondary" size="sm">No settings data passed. Select a tab above to manage a specific settings area.</Text>
            </CardBody>
          </Card>
        )}

      </VStack>
    </IndexPageLayout>
  );
}

SystemSettings.layout = page => (
  <App title="System Settings">{page}</App>
);
