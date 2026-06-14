import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { VStack, HStack, Box, Card, CardBody, Text, Mono, Alert, Button, Eyebrow } from '@aero/ui';
import { SR } from '../signupRoutes.js';

const POLL_MS   = 1500;
const STEP_KEYS = ['creating_db', 'migrating', 'seeding', 'creating_admin'];
const STEP_META = {
  creating_db:    { label: 'Creating database',          detail: 'Allocating storage and initialising schema' },
  migrating:      { label: 'Running migrations',          detail: 'Applying database structure and indexes' },
  seeding:        { label: 'Setting up roles & data',     detail: 'Loading default roles, permissions and config' },
  creating_admin: { label: 'Creating admin account',      detail: 'Configuring your admin credentials' },
};

function DoneIcon()    { return <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M2 5.5l2.5 2.5 4.5-5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round"/></svg>; }
function ErrorIcon()   { return <svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M2.5 2.5l6 6M8.5 2.5l-6 6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/></svg>; }

function Spinner({ size = 10, color = 'var(--aeos-primary)' }) {
  return (
    <div style={{
      width: size, height: size,
      border: `${Math.max(1.5, size * .15)}px solid transparent`,
      borderTopColor: color,
      borderRadius: '50%',
      animation: 'rl-step-spin .6s linear infinite',
    }} aria-hidden="true" />
  );
}

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

  const stepIndex     = stepKey ? STEP_KEYS.indexOf(stepKey) : 0;
  const percentage    = isReady ? 100 : Math.round(((stepIndex + (isFailed ? 0 : 0.5)) / STEP_KEYS.length) * 100);
  const displayStatus = isReady ? 'completed' : isFailed ? 'failed' : 'running';

  const headings = {
    running:   'Setting up your workspace…',
    completed: 'Workspace ready!',
    failed:    'Provisioning failed',
  };

  const subtitles = {
    running:   stepKey ? (STEP_META[stepKey]?.detail ?? 'Working…') : 'Starting up…',
    completed: 'Redirecting you to your workspace now…',
    failed:    'An error occurred. You can retry below — your data is safe.',
  };

  return (
    <VStack gap={5} align="center" style={{ maxWidth: 520, margin: '0 auto', width: '100%' }}>

      {/* ── Status icon ── */}
      <div className="rl-prov-icon-wrap">
        <div className={`rl-prov-icon-bg rl-prov-icon-bg--${displayStatus}`}>
          {displayStatus === 'completed' && (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-label="Completed" role="img">
              <path d="M8 16l6 6 10-12" stroke="#22C55E" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          )}
          {displayStatus === 'failed' && (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-label="Failed" role="img">
              <path d="M10 10l12 12M22 10L10 22" stroke="#FF6B6B" strokeWidth="2.5" strokeLinecap="round" />
            </svg>
          )}
          {displayStatus === 'running' && (
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
              <rect width="28" height="28" rx="7" fill="url(#prov-grad-r)" />
              <path d="M8 20L14 9l6 11H8z" fill="white" fillOpacity=".9" />
              <defs>
                <linearGradient id="prov-grad-r" x1="0" y1="0" x2="28" y2="28">
                  <stop stopColor="var(--aeos-primary)" /><stop offset="1" stopColor="var(--aeos-tertiary)" />
                </linearGradient>
              </defs>
            </svg>
          )}
        </div>
        {displayStatus === 'running' && (
          <div className="rl-prov-spinner" role="status" aria-label="Provisioning in progress" />
        )}
      </div>

      {/* ── Heading ── */}
      <VStack gap={1} align="center" style={{ textAlign: 'center' }}>
        <Text size="xl" weight="semibold" as="h2" style={{ fontFamily: 'var(--aeos-font-display)', letterSpacing: '-.01em' }}>
          {headings[displayStatus]}
        </Text>
        <Text tone="secondary">{subtitles[displayStatus]}</Text>
      </VStack>

      {/* ── Progress bar ── */}
      <div
        className="rl-prov-bar-track"
        role="progressbar"
        aria-valuenow={percentage}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label="Provisioning progress"
        style={{ width: '100%' }}
      >
        <div
          className={`rl-prov-bar-fill${isFailed ? ' rl-prov-bar-fill--failed' : ''}`}
          style={{ width: `${Math.max(percentage, displayStatus === 'running' ? 5 : 0)}%` }}
        />
      </div>
      <Text tone="tertiary" size="sm" as="span">
        {isReady ? '100%' : `${percentage}% complete`}
      </Text>

      {/* ── Step list — all steps always visible ── */}
      <Card style={{ width: '100%' }}>
        <CardBody style={{ padding: 0 }}>
          {STEP_KEYS.map((key, i) => {
            const st   = getStepStatus(key);
            const meta = STEP_META[key];
            return (
              <div
                key={key}
                className={`rl-prov-step rl-prov-step--${st}`}
              >
                <HStack gap={3} align="center">

                  {/* Status icon */}
                  <div className="rl-prov-step-icon">
                    {st === 'done'    && <span style={{ color: 'var(--aeos-success)' }}><DoneIcon /></span>}
                    {st === 'running' && <Spinner size={11} />}
                    {st === 'failed'  && <span style={{ color: 'var(--aeos-destructive)' }}><ErrorIcon /></span>}
                    {st === 'pending' && <div className="rl-prov-step-dot" />}
                  </div>

                  {/* Step content */}
                  <VStack gap={0} style={{ flex: 1, minWidth: 0 }}>
                    <HStack gap={2} align="center">
                      <Mono size="sm" style={{
                        color: st === 'pending' ? 'var(--aeos-text-tertiary)'
                             : st === 'failed'  ? 'var(--aeos-destructive)'
                             : st === 'running' ? 'var(--aeos-text-primary)'
                             : 'var(--aeos-text-secondary)',
                      }}>
                        {meta.label}
                      </Mono>
                    </HStack>
                    {/* Detail always shown — not hidden */}
                    <Text
                      tone="tertiary"
                      size="xs"
                      as="span"
                      style={{ opacity: st === 'pending' ? .45 : .8 }}
                    >
                      {meta.detail}
                    </Text>
                  </VStack>

                  {/* Step status badge */}
                  <Text
                    as="span"
                    size="xs"
                    style={{
                      flexShrink: 0,
                      color: st === 'done'    ? 'var(--aeos-success)'
                           : st === 'running' ? 'var(--aeos-primary)'
                           : st === 'failed'  ? 'var(--aeos-destructive)'
                           : 'var(--aeos-text-tertiary)',
                    }}
                  >
                    {st === 'done'    ? 'done'
                   : st === 'running' ? 'running…'
                   : st === 'failed'  ? 'failed'
                   : `step ${i + 1}`}
                  </Text>
                </HStack>
              </div>
            );
          })}
        </CardBody>
      </Card>

      {/* ── Error state + retry ── */}
      {error && (
        <Alert intent="danger" title="Provisioning error" style={{ width: '100%' }}>
          <VStack gap={3} align="stretch">
            <Text>{error}</Text>
            <Button intent="ghost" size="sm" loading={retrying} onClick={retry}>
              Retry provisioning
            </Button>
          </VStack>
        </Alert>
      )}

    </VStack>
  );
}
