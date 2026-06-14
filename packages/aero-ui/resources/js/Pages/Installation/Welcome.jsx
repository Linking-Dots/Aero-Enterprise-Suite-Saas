import { router } from '@inertiajs/react';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { Box, HStack, VStack, Stat, Badge, Button, Eyebrow, Text } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

export default function Welcome({ mode, version, phpVersion, laravelVersion, steps, installedModules = [] }) {
  const features = [
    { icon: 'users',    title: 'Multi-tenant',  description: 'Full tenant isolation with per-tenant databases' },
    { icon: 'settings', title: 'Modular',        description: '27+ enterprise modules — enable only what you need' },
    { icon: 'sparkles', title: 'AEOS Design',    description: 'Stunning UI with 12 themes and full customizer' },
    { icon: 'chartBar', title: 'Analytics',      description: 'Built-in KPIs, dashboards, and audit logs' },
  ];

  const next = mode === 'saas' ? IR.requirements : IR.license;

  return (
    <VStack gap={6}>
      <Eyebrow tone="primary">
        {mode === 'saas' ? 'SaaS Platform Setup' : 'Standalone Installation'}
      </Eyebrow>

      <div>
        <h1 className="il-title">Welcome to aeos365</h1>
        <p className="il-desc">
          This wizard will guide you through setting up your{' '}
          {mode === 'saas' ? 'multi-tenant SaaS platform' : 'enterprise installation'}.
          The process takes about 5 minutes.
        </p>
      </div>

      {/* Version badges */}
      <HStack gap={2} wrap="wrap">
        <Badge intent="cyan">v{version}</Badge>
        <Badge intent="neutral">PHP {phpVersion}</Badge>
        <Badge intent="neutral">Laravel {laravelVersion}</Badge>
        {mode === 'saas' && <Badge intent="indigo">SaaS Mode</Badge>}
      </HStack>

      {/* Feature highlights */}
      <Box
        grid
        style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 190px), 1fr))', gap: '.75rem' }}
      >
        {features.map(f => (
          <Stat key={f.title} icon={f.icon} iconTone="cyan" title={f.title} description={f.description} />
        ))}
      </Box>

      {/* What you'll need checklist */}
      <div style={{
        background: 'rgba(0,0,0,.02)',
        border: '1px solid var(--aeos-divider)',
        borderRadius: 'var(--aeos-r-lg)',
        padding: 'clamp(.75rem, 3vw, 1.25rem)',
      }}>
        <p style={{
          fontSize: '.65rem',
          textTransform: 'uppercase',
          letterSpacing: '.1em',
          color: 'var(--aeos-text-tertiary)',
          marginBottom: '.6rem',
        }}>
          What you'll need before starting
        </p>
        <VStack gap={2}>
          {[
            mode === 'standalone' && 'A valid aeos365 license key and registered email',
            'Database credentials (MySQL, PostgreSQL, or SQLite)',
            'Application domain / URL',
            'SMTP mail configuration (or use log driver for local dev)',
            'Admin account email and password',
          ].filter(Boolean).map((item, i) => (
            <HStack key={i} gap={2} align="flex-start">
              <span style={{
                width: 16, height: 16, borderRadius: '50%',
                background: 'rgba(0,163,184,.1)',
                border: '1px solid rgba(0,163,184,.2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                flexShrink: 0, marginTop: 2,
              }}>
                <svg width="8" height="8" viewBox="0 0 8 8" fill="none" aria-hidden="true">
                  <path d="M1.5 4l1.5 1.5 3.5-3.5" stroke="var(--aeos-primary)" strokeWidth="1.4" strokeLinecap="round" />
                </svg>
              </span>
              <Text size="sm" tone="secondary" style={{ lineHeight: 1.5 }}>{item}</Text>
            </HStack>
          ))}
        </VStack>
      </div>

      {/* Steps overview strip */}
      <Box style={{
        background: 'rgba(0,0,0,.02)',
        border: '1px solid var(--aeos-divider)',
        borderRadius: 'var(--aeos-r-lg)',
        padding: 'clamp(.6rem, 2vw, 1rem) clamp(.75rem, 3vw, 1.25rem)',
      }}>
        <p style={{
          fontSize: '.65rem', textTransform: 'uppercase',
          letterSpacing: '.1em', color: 'var(--aeos-text-tertiary)',
          marginBottom: 8,
        }}>
          Installation steps
        </p>
        <HStack gap={2} wrap="wrap">
          {steps.map((s, i) => (
            <HStack key={i} gap={1} style={{ fontSize: 'clamp(.78rem, 2vw, .82rem)', color: 'var(--aeos-text-secondary)' }}>
              <span style={{
                width: 18, height: 18, borderRadius: '50%',
                background: 'rgba(0,163,184,.1)', border: '1px solid rgba(0,163,184,.2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: '.6rem', color: 'var(--aeos-primary)', fontFamily: 'var(--aeos-font-mono)',
                flexShrink: 0,
              }}>{i + 1}</span>
              {s.charAt(0).toUpperCase() + s.slice(1)}
            </HStack>
          ))}
        </HStack>
      </Box>

      <div className="il-nav" style={{ borderTop: 'none', paddingTop: 0, marginTop: 0, justifyContent: 'flex-end' }}>
        <Button intent="primary" size="lg" rightIcon="arrowRight" onClick={() => router.get(next)}>
          Begin Setup
        </Button>
      </div>
    </VStack>
  );
}

Welcome.layout = page => (
  <InstallLayout
    title="Welcome"
    step={1}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
