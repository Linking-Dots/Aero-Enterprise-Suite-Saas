/**
 * License Renewal — expiry date display with days-remaining countdown,
 * Renew Now button (opens renewal_url in new tab), Contact Sales link.
 */
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  HStack, VStack,
  Text, Eyebrow, Mono,
  Button, Badge, Alert,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function daysRemaining(expiresAt) {
  if (!expiresAt) return null;
  const expiry = new Date(expiresAt);
  const now    = new Date();
  const diff   = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
  return diff;
}

export default function LicenseRenewal({ license = {}, renewal_url = '' }) {
  const { expires_at } = license;
  const days = daysRemaining(expires_at);

  const isExpired  = days !== null && days <= 0;
  const isUrgent   = days !== null && days > 0 && days <= 30;
  const isWarning  = days !== null && days > 30 && days <= 90;

  const urgencyIntent = isExpired ? 'danger' : isUrgent ? 'danger' : isWarning ? 'warning' : 'success';

  const handleRenew = () => {
    if (renewal_url) {
      window.open(renewal_url, '_blank', 'noopener');
    }
  };

  return (
    <IndexPageLayout
      title="License Renewal"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'License',   href: route('core.license.index') },
        { label: 'Renewal' },
      ]}
      description="Renew your AEOS365 license to continue receiving updates and support."
    >
      <VStack gap={5}>

        {/* ── Expiry Status Alert ── */}
        {isExpired && (
          <Alert
            intent="danger"
            title="Your license has expired. Renew now to restore full functionality."
          />
        )}
        {isUrgent && !isExpired && (
          <Alert
            intent="warning"
            title={`Your license expires in ${days} day${days === 1 ? '' : 's'}. Renew soon to avoid interruption.`}
          />
        )}

        {/* ── Expiry Details Card ── */}
        <Card>
          <CardHeader>
            <Eyebrow>License Expiry</Eyebrow>
          </CardHeader>
          <CardBody>
            <VStack gap={4}>
              {expires_at ? (
                <HStack gap={4} align="center" wrap>
                  <VStack gap={1}>
                    <Text tone="secondary" size="sm">Expiry Date</Text>
                    <Mono>{new Date(expires_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })}</Mono>
                  </VStack>

                  {days !== null && (
                    <VStack gap={1}>
                      <Text tone="secondary" size="sm">Days Remaining</Text>
                      <Badge intent={urgencyIntent}>
                        {isExpired ? 'Expired' : `${days} day${days === 1 ? '' : 's'}`}
                      </Badge>
                    </VStack>
                  )}
                </HStack>
              ) : (
                <Text tone="secondary" size="sm">No expiry date on record.</Text>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* ── Renewal Actions ── */}
        <Card>
          <CardHeader>
            <Eyebrow>Renewal Options</Eyebrow>
          </CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Text tone="secondary" size="sm">
                Renewing your license ensures continued access to software updates, security patches, and priority support. Renewals are available through the AEOS licensing portal.
              </Text>

              <HStack gap={3} wrap>
                <Button intent="primary" onClick={handleRenew} rightIcon="arrowUpRight" disabled={!renewal_url}>
                  Renew Now
                </Button>
                <Button intent="ghost" onClick={() => window.open('mailto:sales@aeos365.com', '_blank', 'noopener')}>
                  Contact Sales
                </Button>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

      </VStack>
    </IndexPageLayout>
  );
}

LicenseRenewal.layout = page => (
  <App title="License Renewal">{page}</App>
);
