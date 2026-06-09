import { useEffect, useState, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, Box, Alert, Button, Text, Mono, HStack } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const POLL_BASE_MS = 1200;   // base polling interval while the server is healthy
const POLL_MAX_MS  = 10000;  // backoff cap applied to consecutive transient failures
const MAX_FAILURES = 5;      // consecutive failures tolerated before surfacing an error

export default function Processing({ mode }) {
  const [percentage, setPercentage] = useState(0);
  const [message, setMessage]       = useState('Initialising…');
  const [status, setStatus]         = useState('running');
  const [error, setError]           = useState(null);
  const [steps, setSteps]           = useState([]);
  const [currentStep, setCurrentStep] = useState(null);
  const [completedSteps, setCompletedSteps] = useState(0);

  // Polling lives in a ref so a single in-flight request / timer / abort
  // controller is tracked across renders and can be cancelled cleanly on
  // unmount or restarted by retry().
  const pollState = useRef({ active: false, failures: 0, timer: null, abort: null });

  const poll = useCallback(async function poll() {
    const s = pollState.current;
    if (!s.active) return;

    // Abort any request still in flight before issuing a new one, so a slow
    // response can never overlap with the next poll.
    s.abort?.abort();
    s.abort = new AbortController();

    try {
      const { data } = await axios.get(IR.progress, { signal: s.abort.signal });
      if (!s.active) return;

      s.failures = 0; // healthy response — reset the backoff

      setPercentage(data.percentage ?? 0);
      setMessage(data.message ?? data.currentStep ?? 'Running…');
      setStatus(data.status ?? 'running');
      setCurrentStep(data.currentStep ?? null);
      setCompletedSteps(data.completedSteps ?? 0);

      if (data.steps?.length) setSteps(data.steps);
      if (data.error) setError(data.error);

      if (data.status === 'completed') {
        s.timer = setTimeout(() => router.get(IR.complete), 1000);
        return;
      }
      if (data.status === 'failed') return;

      s.timer = setTimeout(poll, POLL_BASE_MS);
    } catch (err) {
      // A cancelled request (unmount / superseded poll) is not a real failure.
      if (!s.active || axios.isCancel(err)) return;

      s.failures += 1;
      if (s.failures >= MAX_FAILURES) {
        setError('Lost connection to the server. Please click Retry.');
        setStatus('failed');
        return;
      }

      // Transient failure (server busy mid-migration, brief network blip):
      // retry with exponential backoff (1.2s → 2.4s → 4.8s …, capped) rather
      // than giving up on the first hiccup.
      const delay = Math.min(POLL_BASE_MS * 2 ** s.failures, POLL_MAX_MS);
      setMessage(`Connection issue — retrying in ${Math.round(delay / 1000)}s (attempt ${s.failures}/${MAX_FAILURES})…`);
      s.timer = setTimeout(poll, delay);
    }
  }, []);

  useEffect(() => {
    const s = pollState.current;
    s.active = true;
    s.failures = 0;
    poll();
    return () => {
      s.active = false;
      s.abort?.abort();
      if (s.timer) clearTimeout(s.timer);
    };
  }, [poll]);

  const retry = useCallback(async () => {
    setError(null);
    setStatus('running');
    setMessage('Retrying…');
    const s = pollState.current;
    s.failures = 0;
    s.active = true;
    if (s.timer) clearTimeout(s.timer);
    try { await axios.post(IR.retry); } catch (_) {}
    poll(); // resume progress polling after a retry (previously stalled here)
  }, [poll]);

  const stepStatuses = steps.map(s => {
    const idx    = steps.findIndex(x => x.key === s.key);
    const curIdx = steps.findIndex(x => x.key === currentStep);
    if (idx < curIdx)  return 'done';
    if (s.key === currentStep && status !== 'failed') return 'running';
    if (s.key === currentStep && status === 'failed')  return 'failed';
    return 'pending';
  });

  return (
    <VStack gap={5} align="center" className="aeos-text-center">
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
          <Box className="aeos-mt-2">
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
