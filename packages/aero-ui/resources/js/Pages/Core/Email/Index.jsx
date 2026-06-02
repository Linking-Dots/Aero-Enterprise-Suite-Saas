/**
 * Email Engine — Tabbed Hub
 *
 * Tabs navigate to sub-routes:
 *   Email Logs        → core.email.logs.index
 *   Deliverability    → core.email.deliverability.index
 *   Suppression       → core.email.suppression.index
 *   Bounces           → core.email.bounces.index
 *
 * Active tab is derived from the current URL pathname.
 */
import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  HStack, VStack,
  Button,
  Text,
  Card, CardBody,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const EMAIL_TABS = [
  {
    key: 'logs',
    label: 'Email Logs',
    route: 'core.email.logs.index',
    description: 'View all outgoing email activity, status, and delivery attempts.',
  },
  {
    key: 'deliverability',
    label: 'Deliverability',
    route: 'core.email.deliverability.index',
    description: 'Check SPF, DMARC, DKIM, and MX records for your sending domain.',
  },
  {
    key: 'suppression',
    label: 'Suppression',
    route: 'core.email.suppression.index',
    description: 'Manage addresses suppressed from receiving emails.',
  },
  {
    key: 'bounces',
    label: 'Bounces',
    route: 'core.email.bounces.index',
    description: 'Track bounced messages and identify problem domains.',
  },
];

function getActiveTab(url) {
  if (!url) return '';
  if (url.includes('/email/deliverability')) return 'deliverability';
  if (url.includes('/email/suppression'))    return 'suppression';
  if (url.includes('/email/bounces'))        return 'bounces';
  if (url.includes('/email/logs'))           return 'logs';
  return '';
}

export default function EmailIndex() {
  const { url } = usePage();
  const activeTab = getActiveTab(url);

  return (
    <IndexPageLayout
      title="Email Engine"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Email Engine' },
      ]}
      description="Monitor email delivery, check domain health, and manage suppression lists."
      filters={
        <HStack gap={2} wrap>
          {EMAIL_TABS.map(tab => (
            <Button
              key={tab.key}
              intent={activeTab === tab.key ? 'primary' : 'soft'}
              onClick={() => router.get(route(tab.route))}
            >
              {tab.label}
            </Button>
          ))}
        </HStack>
      }
      table={
        <VStack gap={4}>
          <HStack gap={4} wrap>
            {EMAIL_TABS.map(tab => (
              <Card key={tab.key}>
                <CardBody>
                  <VStack gap={2}>
                    <Text size="sm" tone="secondary">{tab.label}</Text>
                    <Text size="sm">{tab.description}</Text>
                    <Button
                      intent="soft"
                      size="sm"
                      onClick={() => router.get(route(tab.route))}
                    >
                      Open
                    </Button>
                  </VStack>
                </CardBody>
              </Card>
            ))}
          </HStack>
        </VStack>
      }
    />
  );
}

EmailIndex.layout = page => (
  <App title="Email Engine">{page}</App>
);
