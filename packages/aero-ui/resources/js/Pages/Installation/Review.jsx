import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, Box, Button, Alert, Text } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

/**
 * Collapsible config group with an inline "Edit" shortcut. Editing routes back
 * to the relevant step; other settings are preserved via the persisted config
 * file, so nothing is lost by jumping back to fix a typo.
 */
function AccordionSection({ title, editHref, rows, defaultOpen = false }) {
  const [open, setOpen] = useState(defaultOpen);
  const visible = rows.filter(([, v]) => v);
  if (visible.length === 0) return null;

  function toggle() { setOpen(o => !o); }

  return (
    <div className="il-accordion">
      <div
        className="il-accordion-head"
        role="button"
        tabIndex={0}
        aria-expanded={open}
        onClick={toggle}
        onKeyDown={e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } }}
      >
        <span className="il-accordion-head-left">{title}</span>
        <span className="il-accordion-head-right">
          {editHref && (
            <button
              type="button"
              className="il-copy-btn"
              onClick={e => { e.stopPropagation(); router.get(editHref); }}
            >
              Edit
            </button>
          )}
          <svg
            className={`il-accordion-chevron ${open ? 'open' : ''}`}
            width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d="M6 9l6 6 6-6" />
          </svg>
        </span>
      </div>
      {open && (
        <div className="il-accordion-body">
          {visible.map(([k, v]) => (
            <div key={k} className="il-review-row">
              <span className="il-review-key">{k}</span>
              <span className="il-review-val">{v}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default function Review({ mode, summary }) {
  const { errors } = usePage().props;
  const [starting, setStarting] = useState(false);

  function startInstall() {
    setStarting(true);
    router.post(IR.execute, {}, {
      onError: () => setStarting(false),
      // Inertia follows the redirect to /install/processing on success
    });
  }

  const db       = summary?.database ?? {};
  const settings = summary?.settings ?? {};
  const admin    = summary?.admin    ?? {};
  const license  = summary?.license  ?? {};
  const nameKey  = mode === 'saas' ? 'site_name' : 'company_name';

  // Config-health summary — surfaces any incomplete group before install.
  const checks = [
    { label: 'Database configured', ok: !!(db.database && db.host) },
    { label: mode === 'saas' ? 'Platform settings saved' : 'System settings saved', ok: !!(settings[nameKey] && settings.app_url) },
    { label: 'Administrator account', ok: !!admin.email },
    ...(mode === 'standalone' ? [{ label: 'License validated', ok: !!license.key }] : []),
  ];
  const passed = checks.filter(c => c.ok).length;
  const allOk  = passed === checks.length;

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">Review Configuration</h1>
        <p className="il-desc">Review your settings before starting the installation. Use &ldquo;Edit&rdquo; on any section to change it — your other settings are preserved.</p>
      </div>

      {errors.message && <Alert intent="danger">{errors.message}</Alert>}

      {/* Configuration health */}
      <Alert intent={allOk ? 'success' : 'warning'} title={`Configuration health: ${passed}/${checks.length} checks passed`}>
        <VStack gap={1} className="aeos-mt-1">
          {checks.map(c => (
            <div key={c.label} className="il-check" style={{ padding: '4px 0' }}>
              <span className={`il-check-icon ${c.ok ? 'il-check-pass' : 'il-check-warn'}`} aria-hidden="true">
                {c.ok ? (
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12l5 5L20 6" /></svg>
                ) : '!'}
              </span>
              <Text size="sm" tone="secondary">{c.label}{c.ok ? '' : ' — incomplete'}</Text>
            </div>
          ))}
        </VStack>
      </Alert>

      {/* Grouped, collapsible config with inline Edit shortcuts */}
      <Box>
        {mode === 'standalone' && (
          <AccordionSection
            title="License"
            editHref={IR.license}
            rows={[
              ['Key',         license.key ? `${String(license.key).slice(0, 8)}••••••••` : ''],
              ['Type',        license.type],
              ['Valid Until', license.valid_until],
            ]}
          />
        )}
        <AccordionSection
          title="Database"
          editHref={IR.database}
          defaultOpen
          rows={[
            ['Driver',   db.connection ? String(db.connection).toUpperCase() : ''],
            ['Host',     db.host && db.port ? `${db.host}:${db.port}` : db.host],
            ['Database', db.database],
            ['Username', db.username],
          ]}
        />
        <AccordionSection
          title={mode === 'saas' ? 'Platform' : 'System'}
          editHref={IR.settings}
          rows={[
            [mode === 'saas' ? 'Platform Name' : 'Company', settings[nameKey]],
            ['Support Email', settings.support_email],
            ['App URL',       settings.app_url],
            ['Timezone',      settings.timezone],
          ]}
        />
        <AccordionSection
          title="Administrator"
          editHref={IR.admin}
          rows={[
            ['Name',  `${admin.first_name ?? ''} ${admin.last_name ?? ''}`.trim()],
            ['Email', admin.email],
          ]}
        />
      </Box>

      <Alert intent="warning" title="This action cannot be undone">
        Clicking &ldquo;Install Now&rdquo; will run database migrations, seed initial data, and write the installation lock file.
      </Alert>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.admin)}>Back</Button>
        <Button intent="primary" size="lg" loading={starting} rightIcon="arrowRight" onClick={startInstall}>
          Install Now
        </Button>
      </div>
    </VStack>
  );
}

Review.layout = page => (
  <InstallLayout
    title="Review"
    step={page.props.mode === 'saas' ? 5 : 6}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
