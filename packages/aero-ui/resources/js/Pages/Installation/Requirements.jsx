import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { Button, Alert, HStack, VStack, Text } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

/* ── Sub-components ─────────────────────────────────────────────── */

function CheckRow({ label, passed, optional, note, fixCmd }) {
  const state = passed ? 'pass' : optional ? 'warn' : 'fail';
  const icons = { pass: '✓', warn: '!', fail: '✗' };
  const labels = { pass: 'Pass', warn: 'Optional', fail: 'Fail' };
  const statusColors = {
    pass: 'var(--aeos-success)',
    warn: 'var(--aeos-secondary, #FFB347)',
    fail: 'var(--aeos-destructive)',
  };

  return (
    <div className="il-check">
      <div className={`il-check-icon il-check-${state}`} aria-label={labels[state]}>
        {icons[state]}
      </div>
      <div style={{ flex: 1, minWidth: 0 }}>
        <div style={{
          fontSize: 'clamp(.82rem, 2.5vw, .88rem)',
          fontWeight: 500,
          color: 'var(--aeos-text-primary)',
          fontFamily: label.startsWith('/') || label.includes('\\') ? 'var(--aeos-font-mono)' : undefined,
        }}>
          {label}
        </div>
        {note && (
          <div style={{ fontSize: '.78rem', color: 'var(--aeos-text-secondary)', marginTop: 3, lineHeight: 1.5 }}>
            {note}
          </div>
        )}
        {/* Copyable chmod command for directory failures */}
        {fixCmd && !passed && (
          <CopyBlock cmd={fixCmd} />
        )}
      </div>
      <span style={{
        fontSize: '.75rem',
        fontFamily: 'var(--aeos-font-mono)',
        color: statusColors[state],
        flexShrink: 0,
        paddingLeft: 4,
      }}>
        {labels[state]}
      </span>
    </div>
  );
}

function CopyBlock({ cmd }) {
  const [copied, setCopied] = useState(false);
  function copy() {
    navigator.clipboard?.writeText(cmd).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  }
  return (
    <div className="il-copy-block" style={{ marginTop: 6 }}>
      <code style={{ flex: 1, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
        {cmd}
      </code>
      <button type="button" className="il-copy-btn" onClick={copy} aria-label="Copy command">
        {copied ? '✓ Copied' : 'Copy'}
      </button>
    </div>
  );
}

function Section({ title, children, allPassed, collapsePassed }) {
  const [collapsed, setCollapsed] = useState(false);

  return (
    <div className="il-accordion" style={{ marginBottom: '.5rem' }}>
      <div
        className="il-accordion-head"
        role="button"
        tabIndex={0}
        aria-expanded={!collapsed}
        onClick={() => setCollapsed(c => !c)}
        onKeyDown={e => e.key === 'Enter' && setCollapsed(c => !c)}
      >
        <div className="il-accordion-head-left">
          <span style={{ fontSize: '.65rem', letterSpacing: '.1em', textTransform: 'uppercase' }}>
            {title}
          </span>
          {allPassed && (
            <span style={{
              fontSize: '.68rem', fontFamily: 'var(--aeos-font-mono)',
              color: 'var(--aeos-success)', background: 'rgba(34,197,94,.1)',
              border: '1px solid rgba(34,197,94,.2)',
              padding: '1px 6px', borderRadius: 99,
            }}>all pass</span>
          )}
        </div>
        <svg
          className={`il-accordion-chevron ${collapsed ? '' : 'open'}`}
          width="14" height="14" viewBox="0 0 24 24"
          fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round"
          aria-hidden="true"
        >
          <path d="M6 9l6 6 6-6" />
        </svg>
      </div>
      {!collapsed && (
        <div className="il-accordion-body" style={{ padding: 0 }}>
          {children}
        </div>
      )}
    </div>
  );
}

/* ── Page ───────────────────────────────────────────────────────── */

export default function Requirements({ mode, checks: initialChecks, canProceed: initialCanProceed }) {
  const [checks,      setChecks]      = useState(initialChecks ?? {});
  const [canProceed,  setCanProceed]  = useState(initialCanProceed);
  const [rechecking,  setRechecking]  = useState(false);

  const back = mode === 'saas' ? IR.index : IR.license;

  const php         = checks.php         ?? null;
  const extensions  = checks.extensions  ?? [];
  const directories = checks.directories ?? [];

  const phpPassed     = !!php?.passed;
  const extAllPassed  = extensions.every(e => e.installed || !e.required);
  const dirAllPassed  = directories.every(d => d.writable);

  async function recheck() {
    setRechecking(true);
    try {
      const { data } = await axios.post(IR.recheckRequirements);
      setChecks(data.checks ?? {});
      setCanProceed(data.canProceed);
    } catch (_) {}
    finally { setRechecking(false); }
  }

  return (
    <div>
      <h1 className="il-title">System Requirements</h1>
      <p className="il-desc">
        Checking that your server meets the minimum requirements to run aeos365.
      </p>

      {/* Status banner */}
      {canProceed
        ? <Alert intent="success" title="All requirements met" style={{ marginBottom: '1rem' }}>
            Your server is ready — you can proceed with the installation.
          </Alert>
        : <Alert intent="danger" title="Some requirements failed" style={{ marginBottom: '1rem' }}>
            Fix the items marked <strong>Fail</strong> below, then click Re-check.
          </Alert>
      }

      {/* SaaS mode extra check */}
      {mode === 'saas' && (
        <div className="il-advisory" style={{ marginBottom: '1rem' }}>
          <div className="il-advisory-icon" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
            </svg>
          </div>
          <span>
            SaaS mode requires <code>SAAS_MODE=true</code> in your <code>.env</code> and{' '}
            <code>AeroPlatformServiceProvider</code> registered. These are verified automatically.
          </span>
        </div>
      )}

      {/* PHP section */}
      {php && (
        <Section title="PHP Version" allPassed={phpPassed}>
          <CheckRow
            label={`PHP ≥ ${php.required}`}
            passed={php.passed}
            note={`Required: ${php.required}  ·  Detected: ${php.current}`}
          />
        </Section>
      )}

      {/* Extensions section */}
      {extensions.length > 0 && (
        <Section title="PHP Extensions" allPassed={extAllPassed}>
          {extensions.map((ext, i) => (
            <CheckRow
              key={i}
              label={ext.name}
              passed={ext.installed}
              optional={!ext.required}
              note={!ext.installed && !ext.required ? 'Recommended for best performance' : undefined}
            />
          ))}
        </Section>
      )}

      {/* Directory permissions section */}
      {directories.length > 0 && (
        <Section title="Directory Permissions" allPassed={dirAllPassed}>
          {directories.map((dir, i) => (
            <CheckRow
              key={i}
              label={dir.path}
              passed={dir.writable}
              note={dir.writable ? 'Writable' : 'Not writable — run the command below:'}
              fixCmd={!dir.writable ? `chmod -R 775 ${dir.path}` : undefined}
            />
          ))}
        </Section>
      )}

      {/* Nav */}
      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(back)}>Back</Button>
        <HStack gap={2}>
          <Button intent="ghost" loading={rechecking} onClick={recheck}>Re-check</Button>
          <Button
            intent="primary"
            rightIcon="arrowRight"
            disabled={!canProceed}
            onClick={() => router.get(IR.database)}
          >
            Continue
          </Button>
        </HStack>
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
