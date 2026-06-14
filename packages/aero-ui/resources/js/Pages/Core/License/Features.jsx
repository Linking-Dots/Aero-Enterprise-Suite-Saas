/**
 * License Features — edition chip at top, then a list of features with
 * included chip (yes/no) and optional limit display.
 */
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  DataTable,
  HStack, VStack,
  Text, Eyebrow,
  Badge,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function LicenseFeatures({ features = [], edition = '' }) {
  const rows = Array.isArray(features) ? features : [];

  const columns = [
    {
      key: 'name', label: 'Feature', width: '50%',
      render: r => <Text size="sm">{r.name}</Text>,
    },
    {
      key: 'included', label: 'Included', width: '20%',
      render: r => (
        <Badge intent={r.included ? 'success' : 'neutral'}>
          {r.included ? 'Yes' : 'No'}
        </Badge>
      ),
    },
    {
      key: 'limit', label: 'Limit', width: '30%',
      render: r => r.limit !== undefined && r.limit !== null
        ? <Text size="sm" tone="secondary">{r.limit}</Text>
        : <Text size="sm" tone="tertiary">—</Text>,
    },
  ];

  return (
    <IndexPageLayout
      title="License Features"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'License',   href: route('core.license.index') },
        { label: 'Features' },
      ]}
      description="Features included in your current license edition."
    >
      <VStack gap={5}>

        {/* ── Edition Header ── */}
        {edition && (
          <Card>
            <CardBody>
              <HStack gap={3} align="center">
                <Eyebrow>Current Edition</Eyebrow>
                <Badge intent="info">{edition}</Badge>
              </HStack>
            </CardBody>
          </Card>
        )}

        {/* ── Features Table ── */}
        <Card>
          <CardHeader>
            <Eyebrow>Feature List</Eyebrow>
          </CardHeader>
          <CardBody>
            <DataTable
              columns={columns}
              rows={rows}
              empty="No feature information available for this license."
            />
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

LicenseFeatures.layout = page => (
  <App title="License Features">{page}</App>
);
