import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { Button, Alert } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

/**
 * Backend shape:
 *   checks.php          → { name, required, current, passed }
 *   checks.extensions   → [{ name, required, installed }, ...]
 *   checks.directories  → [{ path, writable }, ...]
 */
function CheckRow({ label, passed, optional, note }) {
  const icon  = passed ? '✓' : optional ? '!' : '✗';
  const cls   = passed ? 'il-check-pass' : optional ? 'il-check-warn' : 'il-check-fail';
  const tone  = passed ? 'aeos-text-success' : optional ? 'aeos-text-warning' : 'aeos-text-danger';
  return (
    <div className="il-check">
      <div className={`il-check-icon ${cls}`}>{icon}</div>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div className="aeos-text-sm aeos-text-primary" style={{ fontWeight: 500 }}>{label}</div>
        {note && <div className="aeos-text-xs aeos-text-secondary" style={{ marginTop: 2 }}>{note}</div>}
      </div>
      <span className={`aeos-text-xs ${tone}`} style={{ fontFamily: 'var(--aeos-font-mono)', flexShrink: 0 }}>
        {passed ? 'Pass' : optional ? 'Optional' : 'Fail'}
      </span>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <div className="aeos-mb-3">
      <p className="aeos-eyebrow aeos-eyebrow-primary aeos-mb-1">{title}</p>
      <div style={{ border: '1px solid var(--aeos-divider)', borderRadius: 'var(--aeos-r-lg)', overflow: 'hidden' }}>
        {children}
      </div>
    </div>
  );
}

export default function Requirements({ mode, checks: initialChecks, canProceed: initialCanProceed }) {
  const [checks, setChecks]         = useState(initialChecks ?? {});
  const [canProceed, setCanProceed] = useState(initialCanProceed);
  const [rechecking, setRechecking] = useState(false);

  const back = mode === 'saas' ? IR.index : IR.license;

  async function recheck() {
    setRechecking(true);
    try {
      const { data } = await axios.post(IR.recheckRequirements);
      setChecks(data.checks ?? {});
      setCanProceed(data.canProceed);
    } catch (_) {}
    setRechecking(false);
  }

  const php        = checks.php        ?? null;
  const extensions = checks.extensions ?? [];
  const directories = checks.directories ?? [];

  return (
    <div>
      <h1 className="il-title">System Requirements</h1>
      <p className="il-desc">Checking that your server meets the minimum requirements to run aeos365.</p>

      {canProceed
        ? <Alert intent="success" title="All requirements met" className="aeos-mb-3">Your server is ready to proceed with the installation.</Alert>
        : <Alert intent="danger"  title="Requirements not met" className="aeos-mb-3">Fix the failing items below, then click Re-check.</Alert>
      }

      {php && (
        <Section title="PHP Version">
          <CheckRow
            label={php.name}
            passed={php.passed}
            optional={false}
            note={`Required: ${php.required}  ·  Current: ${php.current}`}
          />
        </Section>
      )}

      {extensions.length > 0 && (
        <Section title="PHP Extensions">
          {extensions.map((ext, i) => (
            <CheckRow key={i} label={ext.name} passed={ext.installed} optional={!ext.required} />
          ))}
        </Section>
      )}

      {directories.length > 0 && (
        <Section title="Directory Permissions">
          {directories.map((dir, i) => (
            <CheckRow
              key={i}
              label={dir.path}
              passed={dir.writable}
              optional={false}
              note={dir.writable ? 'Writable' : 'Not writable — run: chmod -R 775 ' + dir.path}
            />
          ))}
        </Section>
      )}

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(back)}>Back</Button>
        <div style={{ display: 'flex', gap: 8 }}>
          <Button intent="ghost" loading={rechecking} onClick={recheck}>Re-check</Button>
          <Button intent="primary" rightIcon="arrowRight" disabled={!canProceed} onClick={() => router.get(IR.database)}>
            Continue
          </Button>
        </div>
      </div>
    </div>
  );
}

Requirements.layout = page => (
  <InstallLayout
    title="Requirements"
    step={page.props.mode === 'saas' ? 1 : 2}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
