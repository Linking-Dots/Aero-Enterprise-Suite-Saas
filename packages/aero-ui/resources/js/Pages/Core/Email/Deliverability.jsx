/**
 * Email Deliverability — domain DNS health checks.
 *
 * Props:
 *   domain  string
 *   checks  { spf: {pass,value,guide}, dmarc: {pass,value,guide},
 *             dkim: {pass,value,guide}, mx: {pass,value,guide} }
 *   score   number  (0–100)
 */
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  HStack, VStack,
  Text, Mono, Eyebrow,
  Badge,
  Button,
  Card, CardBody,
  Alert,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const CHECK_LABELS = {
  spf:   'SPF Record',
  dmarc: 'DMARC Policy',
  dkim:  'DKIM Signature',
  mx:    'MX Records',
};

const CHECK_GUIDES = {
  spf:   'Add a TXT record: v=spf1 include:your-provider.com ~all',
  dmarc: 'Add a TXT record at _dmarc: v=DMARC1; p=quarantine; rua=mailto:dmarc@example.com',
  dkim:  'Configure DKIM signing keys in your mail provider and publish the DNS TXT selector record.',
  mx:    'Ensure MX records point to your mail provider and have appropriate priority values.',
};

function checkIntent(pass) {
  if (pass === true)  return 'success';
  if (pass === false) return 'danger';
  return 'warning';
}

function checkLabel(pass) {
  if (pass === true)  return 'Pass';
  if (pass === false) return 'Fail';
  return 'Warn';
}

function scoreIntent(score) {
  if (score >= 80) return 'success';
  if (score >= 50) return 'warning';
  return 'danger';
}

function ScoreDisplay({ score }) {
  const intent = scoreIntent(score ?? 0);
  const cls = `email-score email-score--${intent}`;
  return (
    <div className={cls}>
      <Text size="sm" tone="secondary">Deliverability Score</Text>
      <span className="email-score__value">{score ?? 0}</span>
      <Text size="sm" tone="secondary">out of 100</Text>
    </div>
  );
}

function CheckCard({ name, check }) {
  const pass   = check?.pass;
  const value  = check?.value;
  const guide  = check?.guide || CHECK_GUIDES[name];
  const intent = checkIntent(pass);

  return (
    <Card>
      <CardBody>
        <VStack gap={3}>
          <HStack gap={3} align="center">
            <Text size="sm">{CHECK_LABELS[name] || name.toUpperCase()}</Text>
            <Badge intent={intent} size="sm">{checkLabel(pass)}</Badge>
          </HStack>

          {value && (
            <Mono size="sm" className="aeos-text-truncate">{value}</Mono>
          )}

          {pass === false && guide && (
            <Alert intent="warning" title={guide} />
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function Deliverability({ domain, checks = {}, score }) {
  const canRecheck = useHRMAC('core.email_engine.deliverability.view');

  return (
    <>
      <style>{`
        .email-score {
          display: flex;
          flex-direction: column;
          align-items: center;
          gap: var(--aeos-space-1);
          padding: var(--aeos-space-6);
          border-radius: var(--aeos-r-xl);
          border: 2px solid var(--aeos-divider);
          width: fit-content;
        }
        .email-score--success { border-color: var(--aeos-success); }
        .email-score--warning { border-color: var(--aeos-warning); }
        .email-score--danger  { border-color: var(--aeos-destructive); }
        .email-score__value {
          font-family: var(--aeos-font-display);
          font-size: 3rem;
          font-weight: 700;
          line-height: 1;
        }
        .email-score--success .email-score__value { color: var(--aeos-success); }
        .email-score--warning .email-score__value { color: var(--aeos-warning); }
        .email-score--danger  .email-score__value { color: var(--aeos-destructive); }
        .email-checks-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: var(--aeos-space-4);
        }
      `}</style>
      <IndexPageLayout
        title="Deliverability"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Email Engine', href: route('core.email.logs.index') },
          { label: 'Deliverability' },
        ]}
        description="DNS health checks for your sending domain."
        actions={
          canRecheck && (
            <Button
              intent="soft"
              leftIcon="refresh"
              onClick={() => router.reload()}
            >
              Re-check
            </Button>
          )
        }
        table={
          <VStack gap={6}>
            <HStack gap={6} align="center" wrap>
              <ScoreDisplay score={score} />
              <VStack gap={1}>
                <Eyebrow>Sending Domain</Eyebrow>
                <Mono size="sm">{domain || '—'}</Mono>
                <Text tone="secondary" size="sm">
                  All DNS records should resolve correctly for reliable delivery.
                </Text>
              </VStack>
            </HStack>

            <div className="email-checks-grid">
              {['spf', 'dmarc', 'dkim', 'mx'].map(name => (
                <CheckCard
                  key={name}
                  name={name}
                  check={checks[name]}
                />
              ))}
            </div>
          </VStack>
        }
      />
    </>
  );
}

Deliverability.layout = page => (
  <App title="Deliverability">{page}</App>
);
