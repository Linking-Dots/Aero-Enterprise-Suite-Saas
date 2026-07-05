import { VStack, HStack, Box, Card, CardBody, Text, Mono, Button, Badge, Eyebrow, Divider } from '@aero/ui';

function CheckIcon() {
  return (
    <svg width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
      <path d="M2 6.5l3 3 6-6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function WorldIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
      <circle cx="7" cy="7" r="6" stroke="currentColor" strokeWidth="1.2" />
      <path d="M7 1c-1.5 1.5-2.5 3.2-2.5 6s1 4.5 2.5 6M7 1c1.5 1.5 2.5 3.2 2.5 6s-1 4.5-2.5 6M1 7h12" stroke="currentColor" strokeWidth="1.2" />
    </svg>
  );
}

function ExternalIcon() {
  return (
    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
      <path d="M3 9L9 3M9 3H5M9 3v4" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  );
}

const NEXT_STEPS = [
  { icon: '🔑', title: 'Set your password',        desc: 'We emailed a secure, single-use link to your admin email — use it to set your password, then sign in.' },
  { icon: '👥', title: 'Invite your team',         desc: 'Add team members and assign roles. They\'ll get an email invite.' },
  { icon: '📦', title: 'Explore your add-ons',     desc: 'Your selected products are ready. Tour them from the dashboard.' },
  { icon: '⚙️', title: 'Configure integrations',  desc: 'Connect email, Slack, or your own tools from the platform settings.' },
];

export default function StepSuccess({ result = {}, baseDomain = '' }) {
  const {
    name           = '',
    subdomain      = '',
    trial_ends_at  = null,
    plan_name      = '',
    modules        = [],
  } = result;

  const workspaceUrl  = `https://${subdomain}.${baseDomain}`;
  const loginUrl      = `${workspaceUrl}/login`;

  function formatDate(iso) {
    if (!iso) return null;
    try {
      return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch { return iso; }
  }

  const trialEnd = formatDate(trial_ends_at);

  return (
    <VStack gap={5} align="center">

      {/* ── Celebration icon ── */}
      <div className="rl-success-icon" aria-hidden="true">
        <svg width="72" height="72" viewBox="0 0 72 72" fill="none">
          <circle cx="36" cy="36" r="34" fill="rgba(34,197,94,.10)" stroke="rgba(34,197,94,.30)" strokeWidth="2" />
          <circle cx="36" cy="36" r="24" fill="rgba(34,197,94,.08)" />
          <path d="M22 36l10 10 18-20" stroke="#22C55E" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </div>

      {/* ── Heading ── */}
      <VStack gap={2} align="center" style={{ textAlign: 'center' }}>
        <Text
          as="h1"
          style={{
            fontFamily: 'var(--aeos-font-display)',
            fontSize: 'clamp(1.4rem, 4vw, 1.9rem)',
            fontWeight: 800,
            letterSpacing: '-.025em',
            color: 'var(--aeos-text-primary)',
            margin: 0,
          }}
        >
          Welcome to aeos365{name ? `, ${name}` : ''}!
        </Text>
        <Text tone="secondary" as="p" style={{ maxWidth: 400, textAlign: 'center' }}>
          Your workspace has been provisioned and is ready. Everything below has been configured for you.
        </Text>
      </VStack>

      {/* ── Workspace URL pill ── */}
      <a
        href={workspaceUrl}
        target="_blank"
        rel="noopener noreferrer"
        className="rl-success-url"
        aria-label={`Open workspace at ${workspaceUrl}`}
      >
        <WorldIcon />
        <Mono size="sm">{subdomain}.{baseDomain}</Mono>
        <ExternalIcon />
      </a>

      {/* ── Trial & plan summary card ── */}
      <Card style={{ width: '100%' }}>
        <CardBody>
          <VStack gap={0} align="stretch">
            <Eyebrow tone="primary" style={{ marginBottom: '.75rem' }}>Your Subscription</Eyebrow>

            {plan_name && (
              <HStack gap={3} align="center" style={{ paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
                <Text tone="secondary" as="span">Plan</Text>
                <Box grow />
                <Badge intent="primary">{plan_name}</Badge>
              </HStack>
            )}

            {modules?.length > 0 && (
              <HStack gap={3} align="flex-start" style={{ paddingTop: '.5rem', paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
                <Text tone="secondary" as="span" style={{ flexShrink: 0 }}>Add-ons</Text>
                <Box grow />
                <HStack gap={1} style={{ flexWrap: 'wrap', justifyContent: 'flex-end' }}>
                  {modules.map(m => (
                    <Badge key={m} intent="neutral">{m}</Badge>
                  ))}
                </HStack>
              </HStack>
            )}

            {trialEnd && (
              <>
                <HStack gap={3} align="center" style={{ paddingTop: '.5rem', paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
                  <Text tone="secondary" as="span">Trial status</Text>
                  <Box grow />
                  <Badge intent="success">Active</Badge>
                </HStack>
                <HStack gap={3} align="center" style={{ paddingTop: '.5rem', paddingBottom: '.5rem', borderBottom: '1px solid var(--aeos-divider)' }}>
                  <Text tone="secondary" as="span">Trial ends</Text>
                  <Box grow />
                  <Text as="span" weight="medium">{trialEnd}</Text>
                </HStack>
              </>
            )}

            <HStack gap={3} align="center" style={{ paddingTop: '.5rem' }}>
              <Text tone="secondary" as="span">First charge</Text>
              <Box grow />
              <Text tone="tertiary" as="span" size="sm">
                {trialEnd ? `After ${trialEnd}` : 'After trial ends'}
              </Text>
            </HStack>
          </VStack>
        </CardBody>
      </Card>

      {/* ── What to do next ── */}
      <Card style={{ width: '100%' }}>
        <CardBody>
          <Eyebrow tone="primary" style={{ marginBottom: '.75rem' }}>What to do next</Eyebrow>
          <VStack gap={0} align="stretch">
            {NEXT_STEPS.map((step, i) => (
              <HStack
                key={i}
                gap={3}
                align="flex-start"
                style={{
                  paddingTop: '.75rem',
                  paddingBottom: '.75rem',
                  borderBottom: i < NEXT_STEPS.length - 1 ? '1px solid var(--aeos-divider)' : 'none',
                }}
              >
                <span style={{ fontSize: '1.25rem', flexShrink: 0, lineHeight: 1 }}>{step.icon}</span>
                <VStack gap={0} style={{ flex: 1, minWidth: 0 }}>
                  <Text weight="semibold" as="span">{step.title}</Text>
                  <Text tone="secondary" as="span" size="sm">{step.desc}</Text>
                </VStack>
                {i === 0 && (
                  <Badge intent="amber" style={{ flexShrink: 0 }}>Check email</Badge>
                )}
              </HStack>
            ))}
          </VStack>
        </CardBody>
      </Card>

      {/* ── Primary CTA ── */}
      <Button
        as="a"
        href={loginUrl}
        intent="primary"
        fullWidth
        size="lg"
        rightIcon="arrowRight"
      >
        Go to Sign In →
      </Button>

    </VStack>
  );
}
