import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { VStack, HStack, Box, Card, Text, Mono, Alert, Button } from '@aero/ui';
import { SR } from '../signupRoutes.js';

const POLL_MS   = 1500;
const STEP_KEYS = ['creating_db', 'migrating', 'seeding', 'creating_admin'];
const STEP_LABELS = {
  creating_db:    'Creating database',
  migrating:      'Running migrations',
  seeding:        'Setting up roles & data',
  creating_admin: 'Creating admin account',
};

export default function StepProvisioning({ tenant = {}, baseDomain = '' }) {
  const tenantId = tenant?.id;

  const [pollData, setPollData] = useState(null);
  const [error,    setError]    = useState(null);
  const [retrying, setRetrying] = useState(false);

  const status   = pollData?.status     ?? tenant?.status             ?? 'pending';
  const stepKey  = pollData?.step       ?? tenant?.provisioning_step  ?? null;
  const isFailed = pollData?.has_failed ?? false;
  const isReady  = pollData?.is_ready   ?? false;

  useEffect(() => {
    let active = true;

    async function poll() {
      try {
        const { data } = await axios.get(SR.provisioningStatus(tenantId));
        if (!active) return;

        setPollData(data);

        if (data.is_ready) {
          if (data.needs_admin_setup) {
            window.location.href = `https://${tenant.subdomain}.${baseDomain}/admin-setup`;
          } else {
            router.get(SR.success);
          }
          return;
        }

        if (data.has_failed) {
          setError(data.error ?? 'Provisioning failed. Please retry.');
          return;
        }

        setTimeout(poll, POLL_MS);
      } catch {
        if (!active) return;
        setError('Lost connection to the server. Please retry.');
      }
    }

    poll();
    return () => { active = false; };
  }, [tenantId]);

  async function retry() {
    setRetrying(true);
    setError(null);
    try {
      await axios.post(SR.retryProvisioning(tenantId));
      setPollData(null);
      // Re-visit the same provisioning page via Inertia to restart polling
      router.get(SR.provisioning(tenantId), {}, { preserveState: false });
    } catch {
      setError('Retry request failed. Please try again.');
    } finally { setRetrying(false); }
  }

  function getStepStatus(key) {
    if (!stepKey) return 'pending';
    const cur = STEP_KEYS.indexOf(stepKey);
    const idx = STEP_KEYS.indexOf(key);
    if (isFailed && idx === cur) return 'failed';
    if (idx < cur)               return 'done';
    if (idx === cur)             return isReady ? 'done' : 'running';
    return 'pending';
  }

  const stepIndex  = stepKey ? STEP_KEYS.indexOf(stepKey) : 0;
  const percentage = isReady ? 100 : Math.round(((stepIndex + (isFailed ? 0 : 0.5)) / STEP_KEYS.length) * 100);
  const displayStatus = isReady ? 'completed' : isFailed ? 'failed' : 'running';

  return (
    <VStack gap={5} align="center">
      {/* Status icon */}
      <div className="rl-prov-icon-wrap">
        <div className={`rl-prov-icon-bg rl-prov-icon-bg--${displayStatus}`}>
          {displayStatus === 'completed' ? (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
              <path d="M8 16l6 6 10-12" stroke="#22C55E" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          ) : displayStatus === 'failed' ? (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
              <path d="M10 10l12 12M22 10L10 22" stroke="#FF6B6B" strokeWidth="2.5" strokeLinecap="round" />
            </svg>
          ) : (
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
              <rect width="28" height="28" rx="7" fill="url(#prov-grad)" />
              <path d="M8 20L14 9l6 11H8z" fill="white" fillOpacity=".9" />
              <defs>
                <linearGradient id="prov-grad" x1="0" y1="0" x2="28" y2="28">
                  <stop stopColor="var(--aeos-primary)" /><stop offset="1" stopColor="var(--aeos-tertiary)" />
                </linearGradient>
              </defs>
            </svg>
          )}
        </div>
        {displayStatus === 'running' && <div className="rl-prov-spinner" aria-label="Loading" />}
      </div>

      {/* Heading */}
      <VStack gap={1} align="center">
        <Text as="span">
          {displayStatus === 'completed' ? 'Workspace ready!' : displayStatus === 'failed' ? 'Provisioning failed' : 'Setting up your workspace…'}
        </Text>
        <Text tone="secondary" as="span">
          {displayStatus === 'running' && stepKey
            ? (STEP_LABELS[stepKey] ?? 'Working…')
            : displayStatus === 'completed'
            ? 'Redirecting you now…'
            : 'An error occurred during setup.'}
        </Text>
      </VStack>

      {/* Progress bar — width is a runtime value, style prop is an accepted exception */}
      <div className="rl-prov-bar-track" role="progressbar" aria-valuenow={percentage} aria-valuemin={0} aria-valuemax={100}>
        <div
          className={`rl-prov-bar-fill${isFailed ? ' rl-prov-bar-fill--failed' : ''}`}
          style={{ width: `${Math.max(percentage, displayStatus === 'running' ? 5 : 0)}%` }}
        />
      </div>

      {/* Step list */}
      <Card>
        {STEP_KEYS.map((key, i) => {
          const st = getStepStatus(key);
          return (
            <div key={key} className={`rl-prov-step rl-prov-step--${st}`}>
              <HStack gap={3} align="center">
                <div className="rl-prov-step-icon">
                  {st === 'done' && (
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                      <path d="M2 5l2 2 4-4" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" />
                    </svg>
                  )}
                  {st === 'running' && <div className="rl-prov-step-spinner" />}
                  {st === 'failed'  && (
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                      <path d="M3 3l4 4M7 3l-4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                    </svg>
                  )}
                  {st === 'pending' && <div className="rl-prov-step-dot" />}
                </div>
                <Mono size="sm">{STEP_LABELS[key]}</Mono>
                <Box grow />
                {st === 'done'    && <Text tone="secondary" as="span" size="xs">done</Text>}
                {st === 'running' && <Text as="span" size="xs">running…</Text>}
                {st === 'failed'  && <Text tone="secondary" as="span" size="xs">failed</Text>}
              </HStack>
            </div>
          );
        })}
      </Card>

      {error && (
        <Alert intent="danger" title="Provisioning error">
          {error}
          <Box>
            <Button intent="ghost" size="sm" loading={retrying} onClick={retry}>Retry</Button>
          </Box>
        </Alert>
      )}
    </VStack>
  );
}
