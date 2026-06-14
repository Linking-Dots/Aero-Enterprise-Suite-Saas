import InstallLayout from './InstallLayout.jsx';
import { VStack, HStack, Box, Badge, Text, Eyebrow } from '@aero/ui';

export default function Complete({ mode, appUrl, adminEmail, licensedModules = [], installationKey, actions = {} }) {
  const dashboardUrl = actions.dashboard ?? appUrl ?? '/';
  const settingsUrl  = actions.settings  ?? appUrl ?? '/';
  return (
    <VStack gap={5} align="center" className="aeos-text-center">
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
              ? <a href={val} className="il-review-val aeos-text-primary-color">{val}</a>
              : <span className="il-review-val">{val}</span>
            }
          </div>
        ))}
      </Box>

      {/* Licensed modules */}
      {licensedModules.length > 0 && !licensedModules.includes('all') && (
        <Box style={{ width: '100%', textAlign: 'left' }}>
          <Eyebrow tone="primary" className="aeos-mb-1">Licensed Modules</Eyebrow>
          <HStack gap={2} wrap="wrap">
            {licensedModules.map(m => <Badge key={m} intent="success">{m}</Badge>)}
          </HStack>
        </Box>
      )}

      {/* Quick actions — all open the app entry point (appUrl); the app routes
          you to the right place after sign-in. Deep links are intentionally
          avoided since post-install destinations are tenant/subdomain-scoped. */}
      <Box style={{ width: '100%', textAlign: 'left' }}>
        <Eyebrow tone="primary" className="aeos-mb-1">Quick actions</Eyebrow>
        <div className="il-quick-grid">
          <a href={dashboardUrl} className="il-quick-card">
            <span className="il-quick-card-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 11l9-8 9 8M5 10v10h14V10"/></svg>
            </span>
            <span className="il-quick-card-title">Go to dashboard</span>
            <span className="il-quick-card-sub">Sign in with your admin account to reach the {mode === 'saas' ? 'platform' : 'admin'} dashboard.</span>
          </a>
          <a href={settingsUrl} className="il-quick-card">
            <span className="il-quick-card-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            </span>
            <span className="il-quick-card-title">Enable modules</span>
            <span className="il-quick-card-sub">Turn on the modules your team needs from Settings &rsaquo; Modules.</span>
          </a>
          <a href={settingsUrl} className="il-quick-card">
            <span className="il-quick-card-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
            <span className="il-quick-card-title">Invite your team</span>
            <span className="il-quick-card-sub">Add users and assign roles once you&rsquo;re signed in.</span>
          </a>
        </div>

        {mode === 'saas' && (
          <div className="il-advisory" style={{ marginTop: '.75rem' }}>
            <span className="il-advisory-icon" aria-hidden="true">ⓘ</span>
            <div>
              Tenants are reached at their own subdomain — e.g. <code>tenant.yourdomain.com</code>.
              Make sure wildcard DNS (or your local <code>hosts</code> file) routes subdomains to this server.
            </div>
          </div>
        )}
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
