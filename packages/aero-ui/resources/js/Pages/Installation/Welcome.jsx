import { router } from '@inertiajs/react';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { Box, HStack, VStack, Stat, Badge, Button, Eyebrow, Text } from '@aero/ui';

export default function Welcome({ mode, version, phpVersion, laravelVersion, steps, installedModules = [] }) {
  const features = [
    { icon: 'users',    title: 'Multi-tenant', description: 'Full tenant isolation with per-tenant databases' },
    { icon: 'settings', title: 'Modular',       description: '27+ enterprise modules, enable only what you need' },
    { icon: 'sparkles', title: 'AEOS Design',   description: 'Stunning UI with 12 themes and full customizer' },
    { icon: 'chartBar', title: 'Analytics',     description: 'Built-in KPIs, dashboards, and audit logs' },
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

      <HStack gap={2} wrap="wrap">
        <Badge intent="cyan">v{version}</Badge>
        <Badge intent="neutral">PHP {phpVersion}</Badge>
        <Badge intent="neutral">Laravel {laravelVersion}</Badge>
        {mode === 'saas' && <Badge intent="indigo">SaaS Mode</Badge>}
      </HStack>

      {/* Feature highlights */}
      <Box grid cols={{ sm: 1, md: 2 }} gap={3} style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))' }}>
        {features.map(f => (
          <Stat key={f.title} icon={f.icon} iconTone="cyan" title={f.title} description={f.description} />
        ))}
      </Box>

      {/* Steps overview */}
      <Box style={{ background: 'rgba(0,0,0,.03)', border: '1px solid var(--aeos-divider)', borderRadius: 'var(--aeos-r-lg)', padding: '1rem 1.25rem' }}>
        <Text as="p" size="xs" tone="tertiary" style={{ textTransform: 'uppercase', letterSpacing: '.1em', marginBottom: 8 }}>
          Installation steps
        </Text>
        <HStack gap={2} wrap="wrap">
          {steps.map((s, i) => (
            <HStack key={i} gap={1} style={{ fontSize: '.82rem', color: 'var(--aeos-text-secondary)' }}>
              <span style={{
                width: 18, height: 18, borderRadius: '50%',
                background: 'rgba(0,163,184,.1)', border: '1px solid rgba(0,163,184,.2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: '.6rem', color: 'var(--aeos-primary)', fontFamily: 'var(--aeos-font-mono)',
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
  <InstallLayout title="Welcome" step={1} steps={[]} mode={page.props.mode}>
    {page}
  </InstallLayout>
);
