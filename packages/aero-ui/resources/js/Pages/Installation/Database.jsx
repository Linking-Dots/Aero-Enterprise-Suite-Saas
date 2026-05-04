import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, HStack, Box, Field, Input, Select, Button, Alert, Badge } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

const PORT_MAP = { mysql: 3306, pgsql: 5432, sqlsrv: 1433, sqlite: '' };

export default function Database({ mode, savedDatabase, connections }) {
  const { errors: pageErrors } = usePage().props;

  const [form, setForm] = useState({
    connection: savedDatabase?.connection ?? 'mysql',
    host:       savedDatabase?.host       ?? '127.0.0.1',
    port:       savedDatabase?.port       ?? 3306,
    database:   savedDatabase?.database   ?? 'aeos365',
    username:   savedDatabase?.username   ?? 'root',
    password:   savedDatabase?.password   ?? '',
  });
  const [testStatus, setTestStatus] = useState(null);
  const [testing, setTesting]       = useState(false);
  const [saving, setSaving]         = useState(false);
  const [saved, setSaved]           = useState(!!savedDatabase);

  const errors = Object.keys(pageErrors ?? {}).length ? pageErrors : {};
  const isSqlite = form.connection === 'sqlite';

  function set(key, val) {
    setForm(f => ({ ...f, [key]: val, ...(key === 'connection' ? { port: PORT_MAP[val] ?? 3306 } : {}) }));
    setSaved(false);
  }

  async function testConnection() {
    setTestStatus(null);
    setTesting(true);
    try {
      await axios.post(IR.testServer, form);
      setTestStatus({ type: 'success', message: 'Database server connection successful.' });
    } catch (err) {
      setTestStatus({ type: 'error', message: err.response?.data?.message ?? 'Connection failed.' });
    } finally { setTesting(false); }
  }

  function save() {
    setSaving(true);
    router.post(IR.saveDatabase, form, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => { setSaved(true); setTestStatus(null); },
      onFinish:  () => setSaving(false),
    });
  }

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">Database Configuration</h1>
        <p className="il-desc">Configure the database connection for your aeos365 installation.</p>
      </div>

      {errors.database && <Alert intent="danger">{errors.database}</Alert>}
      {testStatus && <Alert intent={testStatus.type === 'success' ? 'success' : 'danger'}>{testStatus.message}</Alert>}

      <Field label="Database Driver" htmlFor="connection" required>
        <Select
          id="connection"
          value={form.connection}
          onChange={e => set('connection', e.target.value)}
          options={connections.map(c => ({ value: c, label: c.toUpperCase() }))}
        />
      </Field>

      {!isSqlite && (
        <>
          <HStack gap={3} align="flex-end">
            <Box grow style={{ flex: 1 }}>
              <Field label="Host" htmlFor="host" error={errors.host} required>
                <Input
                  id="host"
                  value={form.host}
                  onChange={e => set('host', e.target.value)}
                  leftIcon="link"
                  placeholder="127.0.0.1"
                  error={!!errors.host}
                />
              </Field>
            </Box>
            <Box style={{ width: 100 }}>
              <Field label="Port" htmlFor="port" required>
                <Input id="port" type="number" value={form.port} onChange={e => set('port', e.target.value)} />
              </Field>
            </Box>
          </HStack>

          <Field label="Database Name" htmlFor="database" error={errors.database} required>
            <Input
              id="database"
              value={form.database}
              onChange={e => set('database', e.target.value)}
              leftIcon="folder"
              placeholder="aeos365"
              error={!!errors.database}
            />
          </Field>

          <HStack gap={3}>
            <Box grow style={{ flex: 1 }}>
              <Field label="Username" htmlFor="db_user" error={errors.username} required>
                <Input id="db_user" value={form.username} onChange={e => set('username', e.target.value)} leftIcon="user" error={!!errors.username} />
              </Field>
            </Box>
            <Box grow style={{ flex: 1 }}>
              <Field label="Password" htmlFor="db_pass">
                <Input id="db_pass" type="password" value={form.password} onChange={e => set('password', e.target.value)} leftIcon="settings" placeholder="Optional" />
              </Field>
            </Box>
          </HStack>
        </>
      )}

      {isSqlite && (
        <Alert intent="info">
          SQLite will use <span style={{ fontFamily: 'var(--aeos-font-mono)' }}>database/database.sqlite</span> by default.
        </Alert>
      )}

      <HStack gap={2} wrap="wrap">
        <Button intent="ghost" loading={testing} onClick={testConnection}>Test Connection</Button>
        <Button intent="soft" loading={saving} onClick={save}>Save Configuration</Button>
        {saved && <Badge intent="success">Saved</Badge>}
      </HStack>

      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.requirements)}>Back</Button>
        <Button intent="primary" rightIcon="arrowRight" disabled={!saved} onClick={() => router.get(IR.settings)}>
          Continue
        </Button>
      </div>
    </VStack>
  );
}

Database.layout = page => (
  <InstallLayout
    title="Database"
    step={page.props.mode === 'saas' ? 2 : 3}
    steps={page.props.mode === 'saas' ? STEPS_SAAS : STEPS_STANDALONE}
    mode={page.props.mode}
  >
    {page}
  </InstallLayout>
);
