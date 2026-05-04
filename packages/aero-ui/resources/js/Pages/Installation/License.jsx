import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, Field, Input, Button, Alert, Badge } from '@aero/ui';

const STEPS = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

export default function License({ mode, savedLicense }) {
  const [licenseKey, setLicenseKey] = useState(savedLicense?.key ?? '');
  const [email, setEmail]           = useState(savedLicense?.email ?? '');
  const [status, setStatus]         = useState(null);
  const [loading, setLoading]       = useState(false);
  const [errors, setErrors]         = useState({});

  async function validate() {
    setErrors({});
    setStatus(null);
    setLoading(true);
    try {
      const { data } = await axios.post(IR.validateLicense, { license_key: licenseKey, email });
      setStatus({ type: 'success', message: data.message, data: data.data });
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors ?? {});
        setStatus({ type: 'error', message: err.response.data.message });
      } else {
        setStatus({ type: 'error', message: 'Validation failed. Please try again.' });
      }
    } finally {
      setLoading(false);
    }
  }

  const validated = status?.type === 'success';

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">License Validation</h1>
        <p className="il-desc">
          Enter your aeos365 license key to unlock all enterprise modules.
          Use an <code style={{ fontFamily: 'var(--aeos-font-mono)', color: 'var(--aeos-primary)' }}>AP-TEST-*</code> key for local development.
        </p>
      </div>

      {status && (
        <Alert intent={status.type === 'success' ? 'success' : 'danger'} title={status.type === 'success' ? 'License validated' : 'Validation failed'}>
          {status.message}
          {validated && status.data && (
            <div style={{ marginTop: 8, display: 'flex', gap: 8, flexWrap: 'wrap' }}>
              <Badge intent="success">{status.data.type} License</Badge>
              {status.data.valid_until && <Badge intent="neutral">Valid until {status.data.valid_until}</Badge>}
              {status.data.is_dev_license && <Badge intent="amber">Dev License</Badge>}
            </div>
          )}
        </Alert>
      )}

      <Field label="License Key" htmlFor="license_key" error={errors.license_key?.[0]} required>
        <Input
          id="license_key"
          value={licenseKey}
          onChange={e => setLicenseKey(e.target.value)}
          leftIcon="document"
          placeholder="AP-XXXX-XXXX-XXXX-XXXX"
          autoFocus
          error={!!errors.license_key}
          style={{ fontFamily: 'var(--aeos-font-mono)', letterSpacing: '.05em' }}
        />
      </Field>

      <Field label="Email address" htmlFor="lic_email" error={errors.email?.[0]} required>
        <Input
          id="lic_email"
          type="email"
          value={email}
          onChange={e => setEmail(e.target.value)}
          leftIcon="mail"
          placeholder="you@company.com"
          error={!!errors.email}
        />
      </Field>

      <div>
        <Button intent="soft" loading={loading} disabled={loading || !licenseKey || !email} onClick={validate}>
          Validate License
        </Button>
      </div>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.index)}>Back</Button>
        <Button intent="primary" rightIcon="arrowRight" disabled={!validated && !savedLicense} onClick={() => router.get(IR.requirements)}>
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
