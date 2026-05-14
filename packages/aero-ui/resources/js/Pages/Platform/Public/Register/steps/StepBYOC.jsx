import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import {
  VStack, HStack, Box, Field, Input, Select, Toggle, Button, Alert, Text, Card, CardContent, Badge,
} from '@aero/ui';
import { SR } from '../signupRoutes.js';

const PORT_MAP = { mysql: 3306, pgsql: 5432 };

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
      const res = await axios.post(SR.testByocConnection, {
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
      setTestMsg('Connection test failed. Please check your network.');
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

        {/* Toggle */}
        <Card>
          <CardContent>
            <HStack justify="between" align="start" gap={4}>
              <VStack gap={1}>
                <HStack gap={2} align="center">
                  <Text weight="semibold">Bring Your Own Database</Text>
                  <Badge intent="primary">Optional</Badge>
                </HStack>
                <Text tone="secondary" size="sm">
                  Connect your own AWS RDS, Google Cloud SQL, Azure, or self-hosted MySQL/PostgreSQL.
                  Your data stays in your cloud — we manage the application, you own the database.
                </Text>
              </VStack>
              <Toggle
                checked={data.byoc_enabled}
                onChange={e => setData('byoc_enabled', e.target.checked)}
              />
            </HStack>
          </CardContent>
        </Card>

        {/* Credentials — only when enabled */}
        {data.byoc_enabled && (
          <VStack gap={4}>
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

            <HStack gap={3} align="start">
              <Box grow>
                <Field label="Host" htmlFor="db_host" error={errors.db_host} required>
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
              </Box>
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
            </HStack>

            <Field label="Database Name" htmlFor="db_name" error={errors.db_name} required>
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

            <HStack gap={3} align="start">
              <Box grow>
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
              </Box>
              <Box grow>
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
              </Box>
            </HStack>

            <Field label="SSL Mode" htmlFor="db_ssl_mode">
              <Select
                id="db_ssl_mode"
                value={data.db_ssl_mode}
                onChange={e => setData('db_ssl_mode', e.target.value)}
              >
                <option value="">No SSL (not recommended for production)</option>
                <option value="require">Require SSL</option>
                <option value="verify-ca">Verify CA</option>
                <option value="verify-full">Verify Full (most secure)</option>
              </Select>
            </Field>

            <VStack gap={2}>
              <HStack gap={3} align="center">
                <Button
                  type="button"
                  intent="ghost"
                  onClick={testConnection}
                  loading={testStatus === 'testing'}
                  disabled={!canTest}
                >
                  Test Connection
                </Button>
              </HStack>
              {testStatus === 'ok'   && <Alert intent="success">{testMessage || 'Connected successfully.'}</Alert>}
              {testStatus === 'fail' && <Alert intent="danger">{testMessage || 'Connection failed.'}</Alert>}
            </VStack>
          </VStack>
        )}

        {!data.byoc_enabled && (
          <Text tone="secondary" size="sm">
            We'll provision a managed database for you. You can migrate to your own database later from the platform settings.
          </Text>
        )}

        <HStack gap={3} justify="end">
          <Button type="submit" intent="primary" loading={processing}>
            {data.byoc_enabled ? 'Save & Continue' : 'Skip — Use Managed Database'}
          </Button>
        </HStack>

      </VStack>
    </form>
  );
}
