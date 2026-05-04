import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, Box, Alert, Button, Text, Mono, HStack } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const POLL_MS = 1200;

export default function Processing({ mode }) {
  const [percentage, setPercentage] = useState(0);
  const [message, setMessage]       = useState('Initialising…');
  const [status, setStatus]         = useState('running');
  const [error, setError]           = useState(null);
  const [steps, setSteps]           = useState([]);
  const [currentStep, setCurrentStep] = useState(null);
  const [completedSteps, setCompletedSteps] = useState(0);

  useEffect(() => {
    let active = true;

    async function poll() {
      try {
        const { data } = await axios.get(IR.progress);
        if (!active) return;

        setPercentage(data.percentage ?? 0);
        setMessage(data.message ?? data.currentStep ?? 'Running…');
        setStatus(data.status ?? 'running');
        setCurrentStep(data.currentStep ?? null);
        setCompletedSteps(data.completedSteps ?? 0);

        if (data.steps?.length) setSteps(data.steps);
        if (data.error) setError(data.error);

        if (data.status === 'completed') {
          setTimeout(() => router.get(IR.complete), 1000);
          return;
        }
        if (data.status === 'failed') return;

        setTimeout(poll, POLL_MS);
      } catch (err) {
        if (!active) return;
        setError('Lost connection to the server. Please click Retry.');
        setStatus('failed');
      }
    }

    poll();
    return () => { active = false; };
  }, []);

  async function retry() {
    setError(null);
    setStatus('running');
    setPercentage(0);
    setMessage('Retrying…');
    try { await axios.post(IR.retry); } catch (_) {}
  }

  const stepStatuses = steps.map(s => {
    const idx    = steps.findIndex(x => x.key === s.key);
    const curIdx = steps.findIndex(x => x.key === currentStep);
    if (idx < curIdx)  return 'done';
    if (s.key === currentStep && status !== 'failed') return 'running';
    if (s.key === currentStep && status === 'failed')  return 'failed';
    return 'pending';
  });

  return (
    <VStack gap={5} align="center" style={{ textAlign: 'center' }}>
      <Box style={{ position: 'relative', width: 80, height: 80 }}>
        <Box style={{
          position: 'absolute', inset: 0, borderRadius: '50%',
          background: status === 'completed'
            ? 'rgba(34,197,94,.10)' : status === 'failed'
            ? 'rgba(255,107,107,.10)' : 'rgba(0,163,184,.08)',
          border: `2px solid ${status === 'completed' ? 'rgba(34,197,94,.25)' : status === 'failed' ? 'rgba(255,107,107,.25)' : 'rgba(0,163,184,.15)'}`,
          display: 'flex', alignItems: 'center', justifyContent: 'center',
        }}>
          {status === 'completed' ? (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M8 16l6 6 10-12" stroke="#22C55E" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
          ) : status === 'failed' ? (
            <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
              <path d="M10 10l12 12M22 10L10 22" stroke="#FF6B6B" strokeWidth="2.5" strokeLinecap="round"/>
            </svg>
          ) : (
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
              <rect width="28" height="28" rx="7" fill="url(#proc-grad)" />
              <path d="M8 20L14 9l6 11H8z" fill="white" fillOpacity=".9" />
              <defs>
                <linearGradient id="proc-grad" x1="0" y1="0" x2="28" y2="28">
                  <stop stopColor="var(--aeos-primary)" /><stop offset="1" stopColor="var(--aeos-tertiary)" />
                </linearGradient>
              </defs>
            </svg>
          )}
        </Box>
        {status === 'running' && (
          <div style={{
            position: 'absolute', inset: -2, borderRadius: '50%',
            border: '2px solid transparent', borderTopColor: 'var(--aeos-primary)',
            animation: 'il-spin 0.8s linear infinite',
          }} />
        )}
      </Box>

      <div>
        <h1 className="il-title">
          {status === 'completed' ? 'Installation Complete!' : status === 'failed' ? 'Installation Failed' : 'Installing aeos365'}
        </h1>
        <Text tone="secondary">{message}</Text>
      </div>

      <Box style={{ width: '100%', background: 'var(--aeos-divider)', borderRadius: 4, height: 6, overflow: 'hidden' }}>
        <div style={{
          height: '100%', borderRadius: 4,
          background: status === 'failed' ? 'rgba(255,107,107,.6)' : 'var(--aeos-grad-cyan)',
          width: `${Math.max(percentage, status === 'running' ? 5 : 0)}%`,
          transition: 'width .5s ease',
        }} />
      </Box>

      {steps.length > 0 && (
        <Text size="xs" tone="tertiary">
          Step {completedSteps} of {steps.length}
        </Text>
      )}

      {steps.length > 0 && (
        <Box style={{
          width: '100%', textAlign: 'left',
          background: 'rgba(0,0,0,.02)', border: '1px solid var(--aeos-divider)',
          borderRadius: 'var(--aeos-r-lg)', padding: '0.75rem 1rem',
          maxHeight: 220, overflowY: 'auto',
        }}>
          {steps.map((s, i) => {
            const st = stepStatuses[i] ?? 'pending';
            const color = st === 'done' ? 'var(--aeos-success)'
              : st === 'running' ? 'var(--aeos-primary)'
              : st === 'failed' ? 'var(--aeos-destructive)'
              : 'var(--aeos-text-tertiary)';
            const icon = st === 'done' ? '✓' : st === 'running' ? '→' : st === 'failed' ? '✗' : '·';
            return (
              <HStack key={s.key} gap={3} style={{ padding: '5px 0', borderBottom: i < steps.length - 1 ? '1px solid var(--aeos-divider)' : 'none' }}>
                <span style={{ color, fontFamily: 'var(--aeos-font-mono)', fontSize: '.8rem', flexShrink: 0, width: 16 }}>{icon}</span>
                <Mono style={{ fontSize: '.82rem', color: st === 'pending' ? 'var(--aeos-text-tertiary)' : 'var(--aeos-text-primary)', flex: 1 }}>
                  {s.label}
                </Mono>
                {st === 'done'    && <span style={{ fontSize: '.7rem', color: 'var(--aeos-success)',      fontFamily: 'var(--aeos-font-mono)', flexShrink: 0 }}>done</span>}
                {st === 'running' && <span style={{ fontSize: '.7rem', color: 'var(--aeos-primary)',      fontFamily: 'var(--aeos-font-mono)', flexShrink: 0 }}>running…</span>}
                {st === 'failed'  && <span style={{ fontSize: '.7rem', color: 'var(--aeos-destructive)',  fontFamily: 'var(--aeos-font-mono)', flexShrink: 0 }}>failed</span>}
              </HStack>
            );
          })}
        </Box>
      )}

      {error && (
        <Alert intent="danger" title="Installation error" style={{ textAlign: 'left', width: '100%' }}>
          {error}
          <Box style={{ marginTop: 12 }}>
            <Button intent="ghost" size="sm" onClick={retry}>Retry</Button>
          </Box>
        </Alert>
      )}

      <style>{`@keyframes il-spin { to { transform: rotate(360deg); } }`}</style>
    </VStack>
  );
}

Processing.layout = page => (
  <InstallLayout
    title="Installing…"
    step={page.props.mode === 'saas' ? 6 : 7}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
