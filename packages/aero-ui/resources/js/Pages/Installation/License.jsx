import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, HStack, Field, Input, Button, Alert, Badge, Text } from '@aero/ui';

const STEPS = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

// AP-XXXX-XXXX-XXXX-XXXX — masks input into this pattern live
function formatKey(raw) {
  const clean = raw.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
  const parts  = [];
  let cursor   = 0;
  const lengths = [2, 4, 4, 4, 4]; // AP-XXXX-XXXX-XXXX-XXXX
  for (const len of lengths) {
    if (cursor >= clean.length) break;
    parts.push(clean.slice(cursor, cursor + len));
    cursor += len;
  }
  return parts.join('-');
}

const LICENSE_TYPE_INTENT = {
  Extended: 'cyan',
  Regular:  'indigo',
  Dev:      'amber',
};

export default function License({ mode, savedLicense }) {
  const [licenseKey, setLicenseKey] = useState(savedLicense?.key ?? '');
  const [email,      setEmail]      = useState(savedLicense?.email ?? '');
  const [status,     setStatus]     = useState(null);   // null | { type, message, data }
  const [loading,    setLoading]    = useState(false);
  const [errors,     setErrors]     = useState({});

  const validated = status?.type === 'success' || !!savedLicense;

  const handleKeyChange = useCallback(e => {
    const formatted = formatKey(e.target.value);
    setLicenseKey(formatted);
    setStatus(null);
    setErrors({});
  }, []);

  async function validate() {
    setErrors({});
    setStatus(null);
    setLoading(true);
    try {
      const { data } = await axios.post(IR.validateLicense, {
        license_key: licenseKey,
        email,
      });
      setStatus({ type: 'success', message: data.message, data: data.data });
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors ?? {});
        setStatus({ type: 'error', message: err.response.data.message ?? 'Validation failed.' });
      } else {
        setStatus({ type: 'error', message: 'Validation failed. Check your connection and try again.' });
      }
    } finally {
      setLoading(false);
    }
  }

  const licData     = status?.data ?? savedLicense ?? null;
  const licType     = licData?.type ?? null;
  const licExpiry   = licData?.valid_until ?? null;
  const isDevLic    = licData?.is_dev_license ?? false;

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">License Validation</h1>
        <p className="il-desc">
          Enter your aeos365 license key to unlock all enterprise modules.{' '}
          Use an{' '}
          <code style={{ fontFamily: 'var(--aeos-font-mono)', color: 'var(--aeos-primary)', fontSize: '.85em' }}>
            AP-TEST-*
          </code>{' '}
          key for local development.
        </p>
      </div>

      {/* Status banner */}
      {status && (
        <Alert
          intent={status.type === 'success' ? 'success' : 'danger'}
          title={status.type === 'success' ? 'License validated' : 'Validation failed'}
        >
          {status.message}
          {status.type === 'success' && licData && (
            <HStack gap={2} wrap="wrap" style={{ marginTop: 8 }}>
              {licType  && <Badge intent={LICENSE_TYPE_INTENT[licType] ?? 'neutral'}>{licType} License</Badge>}
              {licExpiry && <Badge intent="neutral">Valid until {licExpiry}</Badge>}
              {isDevLic  && <Badge intent="amber">Dev License</Badge>}
            </HStack>
          )}
        </Alert>
      )}

      {/* Saved license summary */}
      {!status && savedLicense && (
        <div style={{
          display: 'flex', alignItems: 'center', gap: 10,
          padding: '.65rem 1rem',
          border: '1px solid rgba(34,197,94,.25)',
          borderRadius: 'var(--aeos-r-lg)',
          background: 'rgba(34,197,94,.05)',
          flexWrap: 'wrap',
        }}>
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <circle cx="8" cy="8" r="7" stroke="var(--aeos-success)" strokeWidth="1.5" />
            <path d="M5 8l2 2 4-4" stroke="var(--aeos-success)" strokeWidth="1.4" strokeLinecap="round" />
          </svg>
          <Text size="sm" tone="secondary" style={{ flex: 1 }}>
            License saved —&nbsp;
            <span style={{ fontFamily: 'var(--aeos-font-mono)', fontSize: '.82em' }}>
              {savedLicense.key ? `${savedLicense.key.slice(0, 7)}•••` : '—'}
            </span>
          </Text>
          {savedLicense.type  && <Badge intent={LICENSE_TYPE_INTENT[savedLicense.type] ?? 'neutral'}>{savedLicense.type}</Badge>}
          {savedLicense.valid_until && <Badge intent="neutral">Valid until {savedLicense.valid_until}</Badge>}
        </div>
      )}

      {/* License key field */}
      <Field
        label="License Key"
        htmlFor="license_key"
        hint="Format: AP-XXXX-XXXX-XXXX-XXXX"
        error={errors.license_key?.[0]}
        required
      >
        <Input
          id="license_key"
          value={licenseKey}
          onChange={handleKeyChange}
          leftIcon="document"
          placeholder="AP-XXXX-XXXX-XXXX-XXXX"
          autoFocus
          spellCheck={false}
          autoCorrect="off"
          autoCapitalize="characters"
          error={!!errors.license_key}
          style={{ fontFamily: 'var(--aeos-font-mono)', letterSpacing: '.06em' }}
        />
      </Field>

      {/* Email field */}
      <Field
        label="Registered Email"
        htmlFor="lic_email"
        hint="The email address you used to purchase the license."
        error={errors.email?.[0]}
        required
      >
        <Input
          id="lic_email"
          type="email"
          value={email}
          onChange={e => { setEmail(e.target.value); setStatus(null); }}
          leftIcon="mail"
          placeholder="you@company.com"
          error={!!errors.email}
        />
      </Field>

      <div>
        <Button
          intent="soft"
          loading={loading}
          disabled={loading || licenseKey.length < 5 || !email}
          onClick={validate}
        >
          Validate License
        </Button>
      </div>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.index)}>Back</Button>
        <Button
          intent="primary"
          rightIcon="arrowRight"
          disabled={!validated}
          onClick={() => router.get(IR.requirements)}
        >
          Continue
        </Button>
      </div>
    </VStack>
  );
}

License.layout = page => (
  <InstallLayout title="License" step={2} steps={STEPS} mode={page.props.mode}>
    {page}
  </InstallLayout>
);
