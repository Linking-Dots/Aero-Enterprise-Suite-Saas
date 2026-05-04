import { VStack, HStack, Text, Mono, Button, Link } from '@aero/ui';

export default function StepSuccess({ result = {}, baseDomain = '' }) {
  const { name = '', subdomain = '', trial_ends_at = null } = result;

  const workspaceUrl  = `https://${subdomain}.${baseDomain}`;
  const adminSetupUrl = `${workspaceUrl}/admin-setup`;
  const loginUrl      = `${workspaceUrl}/login`;

  function formatTrialDate(isoDate) {
    if (!isoDate) return null;
    try {
      return new Date(isoDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    } catch { return isoDate; }
  }

  const trialEndFormatted = formatTrialDate(trial_ends_at);

  return (
    <VStack gap={5} align="center">
      {/* Checkmark */}
      <div className="rl-success-icon">
        <svg width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <circle cx="32" cy="32" r="30" fill="rgba(34,197,94,.12)" stroke="rgba(34,197,94,.35)" strokeWidth="2" />
          <path d="M20 32l9 9 15-18" stroke="#22C55E" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </div>

      <VStack gap={2} align="center">
        <h1 className="rl-title">Welcome to aeos365, {name}!</h1>
        <Text tone="secondary" as="p">Your workspace has been provisioned and is ready to use.</Text>
      </VStack>

      {/* Workspace URL */}
      <Link
        href={workspaceUrl}
        external
        className="rl-success-url"
        aria-label={`Open workspace at ${workspaceUrl}`}
      >
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
          <circle cx="7" cy="7" r="6" stroke="currentColor" strokeWidth="1.2" />
          <path d="M7 1c-1.5 1.5-2.5 3.2-2.5 6s1 4.5 2.5 6M7 1c1.5 1.5 2.5 3.2 2.5 6s-1 4.5-2.5 6M1 7h12" stroke="currentColor" strokeWidth="1.2" />
        </svg>
        <Mono size="sm">{subdomain}.{baseDomain}</Mono>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true">
          <path d="M3 9L9 3M9 3H5M9 3v4" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
        </svg>
      </Link>

      {/* Trial info */}
      {trialEndFormatted && (
        <Text tone="secondary" as="p" className="rl-success-trial">
          Free trial active &mdash; ends on{' '}
          <strong>{trialEndFormatted}</strong>. No credit card required.
        </Text>
      )}

      {/* CTAs — cross-domain links use Button as="a" */}
      <Button as="a" href={adminSetupUrl} intent="primary" fullWidth size="lg" rightIcon="arrowRight">
        Complete Your Setup
      </Button>

      <Button as="a" href={loginUrl} intent="ghost">
        Sign in later
      </Button>
    </VStack>
  );
}
