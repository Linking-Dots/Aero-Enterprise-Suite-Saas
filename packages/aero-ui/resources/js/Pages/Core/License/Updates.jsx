/**
 * License Updates — current vs latest version comparison, update-available alert,
 * and a changelog timeline with version, date, and highlights list.
 */
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  HStack, VStack,
  Text, Eyebrow, Mono,
  Badge, Alert,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function LicenseUpdates({
  current_version = '',
  latest_version  = '',
  has_update      = false,
  changelog       = [],
}) {
  const entries = Array.isArray(changelog) ? changelog : [];

  return (
    <IndexPageLayout
      title="Software Updates"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'License',   href: route('core.license.index') },
        { label: 'Updates' },
      ]}
      description="Track the current software version and available updates."
    >
      <VStack gap={5}>

        {/* ── Update Available Alert ── */}
        {has_update && (
          <Alert
            intent="info"
            title={`Update available: v${latest_version} — visit your hosting environment to apply the update.`}
          />
        )}

        {/* ── Version Comparison Card ── */}
        <Card>
          <CardHeader>
            <Eyebrow>Version Status</Eyebrow>
          </CardHeader>
          <CardBody>
            <HStack gap={6} wrap>
              <VStack gap={1}>
                <Text tone="secondary" size="sm">Current Version</Text>
                <HStack gap={2} align="center">
                  <Mono>{current_version || '—'}</Mono>
                  {!has_update && current_version && (
                    <Badge intent="success">Up to date</Badge>
                  )}
                </HStack>
              </VStack>

              <VStack gap={1}>
                <Text tone="secondary" size="sm">Latest Version</Text>
                <HStack gap={2} align="center">
                  <Mono>{latest_version || '—'}</Mono>
                  {has_update && (
                    <Badge intent="info">New</Badge>
                  )}
                </HStack>
              </VStack>
            </HStack>
          </CardBody>
        </Card>

        {/* ── Changelog Timeline ── */}
        {entries.length > 0 && (
          <Card>
            <CardHeader>
              <Eyebrow>Changelog</Eyebrow>
            </CardHeader>
            <CardBody>
              <VStack gap={5}>
                {entries.map((entry, idx) => (
                  <VStack key={entry.version ?? idx} gap={2}>
                    <HStack gap={3} align="center">
                      <Mono>{entry.version ?? '—'}</Mono>
                      {entry.date && (
                        <Text tone="secondary" size="sm">
                          {new Date(entry.date).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })}
                        </Text>
                      )}
                      {idx === 0 && has_update && (
                        <Badge intent="info">Latest</Badge>
                      )}
                      {entry.version === current_version && (
                        <Badge intent="neutral">Installed</Badge>
                      )}
                    </HStack>

                    {Array.isArray(entry.highlights) && entry.highlights.length > 0 && (
                      <VStack gap={1}>
                        {entry.highlights.map((highlight, hIdx) => (
                          <HStack key={hIdx} gap={2} align="start">
                            <Text tone="tertiary" size="sm">•</Text>
                            <Text size="sm" tone="secondary">{highlight}</Text>
                          </HStack>
                        ))}
                      </VStack>
                    )}
                  </VStack>
                ))}
              </VStack>
            </CardBody>
          </Card>
        )}

        {entries.length === 0 && (
          <Card>
            <CardBody>
              <Text tone="secondary" size="sm">No changelog entries available.</Text>
            </CardBody>
          </Card>
        )}

      </VStack>
    </IndexPageLayout>
  );
}

LicenseUpdates.layout = page => (
  <App title="Software Updates">{page}</App>
);
