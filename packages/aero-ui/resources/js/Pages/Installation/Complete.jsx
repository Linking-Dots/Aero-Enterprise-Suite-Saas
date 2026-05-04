import InstallLayout from './InstallLayout.jsx';
import { VStack, HStack, Box, Badge, Text, Eyebrow } from '@aero/ui';

export default function Complete({ mode, appUrl, adminEmail, licensedModules = [], installationKey }) {
  return (
    <VStack gap={5} align="center" style={{ textAlign: 'center' }}>
      {/* Success icon */}
      <Box style={{
        width: 80, height: 80, borderRadius: '50%',
        background: 'rgba(34,197,94,.10)', border: '1px solid rgba(34,197,94,.25)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        boxShadow: '0 0 32px rgba(34,197,94,.15)',
      }}>
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" aria-hidden="true">
          <path d="M10 18l6 6 10-12" stroke="#22C55E" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
      </Box>

      <div>
        <h1 className="il-title">Installation Complete!</h1>
        <Text tone="secondary">aeos365 has been successfully installed. Your platform is ready.</Text>
      </div>

      {/* Summary */}
      <Box style={{
        width: '100%', textAlign: 'left',
        background: 'rgba(0,0,0,.02)', border: '1px solid var(--aeos-divider)',
        borderRadius: 'var(--aeos-r-xl)', padding: '1.5rem',
      }}>
        {[
          ['Admin Email',       adminEmail],
          ['Application URL',   appUrl],
          ['Installation Key',  installationKey],
          ['Mode',              mode === 'saas' ? 'SaaS Platform' : 'Standalone'],
        ].filter(([, v]) => v).map(([key, val]) => (
          <div key={key} className="il-review-row">
            <span className="il-review-key">{key}</span>
            {key === 'Application URL'
              ? <a href={val} className="il-review-val" style={{ color: 'var(--aeos-primary)' }}>{val}</a>
              : <span className="il-review-val">{val}</span>
            }
          </div>
        ))}
      </Box>

      {/* Licensed modules */}
      {licensedModules.length > 0 && !licensedModules.includes('all') && (
        <Box style={{ width: '100%', textAlign: 'left' }}>
          <Eyebrow tone="primary" style={{ marginBottom: 8 }}>Licensed Modules</Eyebrow>
          <HStack gap={2} wrap="wrap">
            {licensedModules.map(m => <Badge key={m} intent="success">{m}</Badge>)}
          </HStack>
        </Box>
      )}

      {/* Next steps */}
      <Box style={{
        width: '100%', textAlign: 'left',
        background: 'rgba(0,163,184,.04)', border: '1px solid rgba(0,163,184,.12)',
        borderRadius: 'var(--aeos-r-lg)', padding: '1rem 1.25rem',
      }}>
        <Eyebrow tone="primary" style={{ marginBottom: 8 }}>Next steps</Eyebrow>
        <VStack gap={2}>
          {['Sign in with your admin account', 'Configure tenant domains', 'Enable required modules', 'Set up email templates'].map((s, i) => (
            <HStack key={i} gap={2}>
              <span style={{ color: 'var(--aeos-primary)', flexShrink: 0 }}>→</span>
              <Text size="sm" tone="secondary">{s}</Text>
            </HStack>
          ))}
        </VStack>
      </Box>

      <a
        href={appUrl ?? '/'}
        className="aeos-btn aeos-btn-primary"
        style={{ display: 'inline-flex', alignItems: 'center', gap: 8, fontSize: '1rem', padding: '0.875rem 2rem' }}
      >
        Go to aeos365
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
          <path d="M4.5 12h15m0 0l-6.75 6.75M19.5 12l-6.75-6.75"/>
        </svg>
      </a>
    </VStack>
  );
}

Complete.layout = page => (
  <InstallLayout title="Complete" step={page.props.mode === 'saas' ? 7 : 8} steps={[]} mode={page.props.mode}>
    {page}
  </InstallLayout>
);
