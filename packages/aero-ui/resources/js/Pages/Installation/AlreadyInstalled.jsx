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
        {/* Meridian mark — branding/svg/mark.svg geometry */}
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="4.6" fill="currentColor" />
          <path
            d="M20.65 8.33A9.4 9.4 0 1 1 15.67 3.35"
            stroke="currentColor"
            strokeWidth="2.1"
            strokeLinecap="round"
          />
          <circle cx="18.65" cy="5.35" r="2.35" fill="#FF7A1F" />
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

      <a href={route('login')} className="aeos-btn aeos-btn-primary" style={{ display: 'inline-flex', alignItems: 'center', gap: 8 }}>
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
