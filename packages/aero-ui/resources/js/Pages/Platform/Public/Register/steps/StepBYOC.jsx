import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import {
  VStack, HStack, Box, Field, Input, Select, Toggle, Button, Alert, Text, Card, CardBody, Badge, Eyebrow,
} from '@aero/ui';
import { SR } from '../signupRoutes.js';

const PORT_MAP  = { mysql: 3306, pgsql: 5432 };
const SSL_MODES = [
  { value: '',             label: 'No SSL',           desc: 'Not recommended for production databases' },
  { value: 'require',      label: 'Require SSL',       desc: 'Encrypted connection required' },
  { value: 'verify-ca',   label: 'Verify CA',          desc: 'Verify server certificate against a trusted CA' },
  { value: 'verify-full', label: 'Verify Full',        desc: 'Most secure — verifies hostname too' },
];

const BYOC_BENEFITS = [
  { icon: '🔒', text: 'Your data stays in your own cloud' },
  { icon: '☁️', text: 'AWS RDS, Google Cloud SQL, Azure SQL, or self-hosted' },
  { icon: '⚙️', text: 'We manage the application — you own the database' },
  { icon: '🔄', text: 'Migrate from managed DB at any time in settings' },
];

export default function StepBYOC({ savedByoc }) {
  const saved = savedByoc ?? {};

  const { data, setData, post, processing, errors } = useForm({
    byoc_enabled: saved.enabled     ?? false,
    db_driver:    saved.db_driver   ?? 'mysql',
    db_host:      saved.db_host     ?? '',
    db_port:      saved.db_port     ?? 3306,
    db_name:      saved.db_name     ?? '',
    db_username:  saved.db_username ?? '',
    db_password:  '',
    db_ssl_mode:  saved.db_ssl_mode ?? '',
  });

  const [testStatus, setTestStatus] = useState(null);
  const [testMessage, setTestMsg]   = useState('');

  function setDriver(driver) {
    setData(d => ({ ...d, db_driver: driver, db_port: PORT_MAP[driver] ?? 3306 }));
  }

  async function testConnection() {
    setTestStatus('testing');
    setTestMsg('');
    try {
      const res  = await axios.post(SR.testByocConnection, {
        db_driver:   data.db_driver,
        db_host:     data.db_host,
        db_port:     data.db_port,
        db_name:     data.db_name,
        db_username: data.db_username,
        db_password: data.db_password,
      });
      const json = res.data;
      setTestStatus(json.success ? 'ok' : 'fail');
      setTestMsg(json.message ?? '');
    } catch {
      setTestStatus('fail');
      setTestMsg('Connection test failed. Please check your credentials and network.');
    }
  }

  function submit(e) {
    e.preventDefault();
    post(SR.storeByoc);
  }

  const canTest = data.byoc_enabled && data.db_host && data.db_name && data.db_username;

  return (
    <form onSubmit={submit} noValidate>
      <VStack gap={5}>

        {/* ── BYOC toggle card ── */}
        <Card>
          <CardBody>
            <HStack gap={4} align="flex-start">
              <VStack gap={2} align="stretch" style={{ flex: 1, minWidth: 0 }}>
                <HStack gap={2} align="center">
                  <Text weight="semibold" size="lg" as="span">Bring Your Own Database</Text>
                  <Badge intent="neutral">Optional</Badge>
                </HStack>
                <Text tone="secondary">
                  Connect your own managed or self-hosted database. aeos365 manages the application layer — your data never leaves your infrastructure.
                </Text>

                {/* Benefits — always visible */}
                <VStack gap={2} align="stretch" style={{ marginTop: '.25rem' }}>
                  {BYOC_BENEFITS.map((b, i) => (
                    <HStack key={i} gap={2} align="center">
                      <span style={{ fontSize: '1rem', flexShrink: 0 }}>{b.icon}</span>
                      <Text tone="secondary" as="span" size="sm">{b.text}</Text>
                    </HStack>
                  ))}
                </VStack>
              </VStack>

              {/* Toggle — stays right on all breakpoints */}
              <div style={{ flexShrink: 0, paddingTop: 2 }}>
                <Toggle
                  checked={data.byoc_enabled}
                  onChange={e => setData('byoc_enabled', e.target.checked)}
                  aria-label="Enable Bring Your Own Database"
                />
              </div>
            </HStack>
          </CardBody>
        </Card>

        {/* ── Managed DB notice (when BYOC off) ── */}
        {!data.byoc_enabled && (
          <Alert intent="info">
            <Text>
              aeos365 will provision and manage a secure database for you at no extra cost.
              You can migrate to your own database at any time from{' '}
              <strong>Platform Settings → Database</strong>.
            </Text>
          </Alert>
        )}

        {/* ── BYOC credentials form (when BYOC on) ── */}
        {data.byoc_enabled && (
          <Card>
            <CardBody>
              <VStack gap={4} align="stretch">
                <Eyebrow tone="primary">Database credentials</Eyebrow>

                {/* Engine */}
                <Field label="Database Engine" htmlFor="db_driver" error={errors.db_driver} required>
                  <Select
                    id="db_driver"
                    value={data.db_driver}
                    onChange={e => setDriver(e.target.value)}
                    error={!!errors.db_driver}
                  >
                    <option value="mysql">MySQL 8.0+</option>
                    <option value="pgsql">PostgreSQL 14+</option>
                  </Select>
                </Field>

                {/* Host + Port — side-by-side on ≥ 480px, stacked on mobile */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '1rem' }}>
                  <Field label="Host" htmlFor="db_host" error={errors.db_host} required hint="e.g. db.mycompany.rds.amazonaws.com">
                    <Input
                      id="db_host"
                      type="text"
                      placeholder="db.mycompany.rds.amazonaws.com"
                      value={data.db_host}
                      onChange={e => setData('db_host', e.target.value)}
                      autoComplete="off"
                      error={!!errors.db_host}
                    />
                  </Field>
                  <Field label="Port" htmlFor="db_port" error={errors.db_port} required>
                    <Input
                      id="db_port"
                      type="number"
                      min={1}
                      max={65535}
                      value={data.db_port}
                      onChange={e => setData('db_port', parseInt(e.target.value, 10))}
                      error={!!errors.db_port}
                    />
                  </Field>
                </div>

                {/* Database name */}
                <Field label="Database Name" htmlFor="db_name" error={errors.db_name} required hint="The database must already exist and be empty">
                  <Input
                    id="db_name"
                    type="text"
                    placeholder="aeos365_production"
                    value={data.db_name}
                    onChange={e => setData('db_name', e.target.value)}
                    autoComplete="off"
                    error={!!errors.db_name}
                  />
                </Field>

                {/* Username + Password — side-by-side on ≥ 480px */}
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '1rem' }}>
                  <Field label="Username" htmlFor="db_username" error={errors.db_username} required>
                    <Input
                      id="db_username"
                      type="text"
                      placeholder="aeos_user"
                      value={data.db_username}
                      onChange={e => setData('db_username', e.target.value)}
                      autoComplete="off"
                      error={!!errors.db_username}
                    />
                  </Field>
                  <Field label="Password" htmlFor="db_password" error={errors.db_password}>
                    <Input
                      id="db_password"
                      type="password"
                      placeholder="••••••••"
                      value={data.db_password}
                      onChange={e => setData('db_password', e.target.value)}
                      autoComplete="new-password"
                      error={!!errors.db_password}
                    />
                  </Field>
                </div>

                {/* SSL mode — all options with descriptions */}
                <Field
                  label="SSL Mode"
                  htmlFor="db_ssl_mode"
                  hint="Select the SSL/TLS mode for your database connection"
                >
                  <Select
                    id="db_ssl_mode"
                    value={data.db_ssl_mode}
                    onChange={e => setData('db_ssl_mode', e.target.value)}
                  >
                    {SSL_MODES.map(m => (
                      <option key={m.value} value={m.value}>
                        {m.label} — {m.desc}
                      </option>
                    ))}
                  </Select>
                </Field>

                {/* SSL mode details panel */}
                {(() => {
                  const mode = SSL_MODES.find(m => m.value === data.db_ssl_mode);
                  return mode ? (
                    <Alert intent="info">
                      <strong>{mode.label}</strong> — {mode.desc}
                    </Alert>
                  ) : null;
                })()}

                {/* Test connection */}
                <VStack gap={2} align="stretch">
                  <Button
                    type="button"
                    intent="soft"
                    onClick={testConnection}
                    loading={testStatus === 'testing'}
                    disabled={!canTest}
                    fullWidth
                  >
                    {testStatus === 'testing' ? 'Testing…' : 'Test Connection'}
                  </Button>
                  {!canTest && (
                    <Text tone="tertiary" size="sm" as="p" style={{ textAlign: 'center' }}>
                      Fill in host, database name, and username to test the connection.
                    </Text>
                  )}
                  {testStatus === 'ok'   && <Alert intent="success">{testMessage || 'Connection successful.'}</Alert>}
                  {testStatus === 'fail' && <Alert intent="danger">{testMessage || 'Connection failed. Please check your credentials.'}</Alert>}
                </VStack>

              </VStack>
            </CardBody>
          </Card>
        )}

        {/* ── Nav ── */}
        <div className="rl-nav">
          <Button type="button" intent="ghost" leftIcon="arrowLeft" onClick={() => window.history.back()}>
            Back
          </Button>
          <Button type="submit" intent="primary" loading={processing} rightIcon="arrowRight">
            {data.byoc_enabled ? 'Save & Continue' : 'Skip — Use Managed Database'}
          </Button>
        </div>

      </VStack>
    </form>
  );
}
