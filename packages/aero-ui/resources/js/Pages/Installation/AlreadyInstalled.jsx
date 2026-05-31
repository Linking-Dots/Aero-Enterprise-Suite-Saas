import InstallLayout from './InstallLayout.jsx';
import { VStack, HStack, Box, Badge, Text } from '@aero/ui';

export default function AlreadyInstalled({ mode, appUrl, installedAt, version }) {
  return (
    <VStack gap={5} align="center" className="aeos-text-center">
      <Box style={{
        width: 72, height: 72, borderRadius: '50%',
        background: 'rgba(99,102,241,.10)', border: '1px solid rgba(99,102,241,.25)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
      }}>
        <svg width="30" height="30" viewBox="0 0 30 30" fill="none" aria-hidden="true">
          <rect width="30" height="30" rx="8" fill="url(#ai-grad)" />
          <path d="M9 21L15 10l6 11H9z" fill="white" fillOpacity=".9" />
          <defs>
            <linearGradient id="ai-grad" x1="0" y1="0" x2="30" y2="30">
              <stop stopColor="#6366F1" /><stop offset="1" stopColor="var(--aeos-primary)" />
            </linearGradient>
          </defs>
        </svg>
      </Box>

      <div>
        <h1 className="il-title">Already Installed</h1>
        <Text tone="secondary">aeos365 is already installed on this server. The setup wizard is no longer available.</Text>
      </div>

      <Box style={{
        width: '100%', textAlign: 'left',
        background: 'rgba(0,0,0,.02)', border: '1px solid var(--aeos-divider)',
        borderRadius: 'var(--aeos-r-xl)', padding: '1.5rem',
      }}>
        {version     && <div className="il-review-row"><span className="il-review-key">Version</span><Badge intent="cyan">v{version}</Badge></div>}
        {mode        && <div className="il-review-row"><span className="il-review-key">Mode</span><Badge intent={mode === 'saas' ? 'indigo' : 'neutral'}>{mode === 'saas' ? 'SaaS Platform' : 'Standalone'}</Badge></div>}
        {installedAt && <div className="il-review-row"><span className="il-review-key">Installed At</span><span className="il-review-val">{new Date(installedAt).toLocaleString()}</span></div>}
      </Box>

      <a href={appUrl ?? '/login'} className="aeos-btn aeos-btn-primary" style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
        Go to Application
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round">
          <path d="M4.5 12h15m0 0l-6.75 6.75M19.5 12l-6.75-6.75"/>
        </svg>
      </a>
    </VStack>
  );
}

AlreadyInstalled.layout = page => (
  <InstallLayout title="Already Installed" step={0} steps={[]} mode={page.props.mode}>
    {page}
  </InstallLayout>
);
