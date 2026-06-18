import { useState, useCallback } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import InstallLayout from './InstallLayout.jsx';
import { IR } from './installRoutes.js';
import { VStack, HStack, Box, Field, Input, Select, Button, Alert, Badge, Text } from '@aero/ui';

const STEPS_STANDALONE = ['License', 'Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];
const STEPS_SAAS       = ['Requirements', 'Database', 'Settings', 'Admin', 'Review', 'Install', 'Complete'];

const PORT_MAP = { mysql: 3306, pgsql: 5432, sqlsrv: 1433, sqlite: '' };

export default function Database({ mode, savedDatabase, connections }) {
  const { errors: pageErrors } = usePage().props;

  const [form, setForm] = useState({
    connection: savedDatabase?.connection ?? 'mysql',
    host:       savedDatabase?.host       ?? '127.0.0.1',
    port:       savedDatabase?.port       ?? 3306,
    database:   savedDatabase?.database   ?? '',
    username:   savedDatabase?.username   ?? 'root',
    password:   savedDatabase?.password   ?? '',
  });

  // Connection verification
  const [connState, setConnState]     = useState(savedDatabase ? 'saved' : 'idle');
  // idle | testing | connected | failed | saving | saved
  const [connLatency, setConnLatency] = useState(null);
  const [connError,   setConnError]   = useState(null);

  // DB browser
  const [dbList,     setDbList]     = useState([]);
  const [browseOpen, setBrowseOpen] = useState(false);
  const [browsing,   setBrowsing]   = useState(false);

  const errors    = pageErrors ?? {};
  const isSqlite  = form.connection === 'sqlite';
  const isSaved   = connState === 'saved';

  const set = useCallback((key, val) => {
    setForm(f => ({
      ...f,
      [key]: val,
      ...(key === 'connection' ? { port: PORT_MAP[val] ?? 3306 } : {}),
    }));
    setConnState('idle');
    setConnError(null);
  }, []);

  // ── Verify & Save in one action ───────────────────────────────────
  async function verifyAndSave() {
    setConnState('testing');
    setConnError(null);
    setConnLatency(null);
    const t0 = performance.now();
    try {
      await axios.post(IR.testServer, form);
      const ms = Math.round(performance.now() - t0);
      setConnLatency(ms);
      setConnState('saving');
      await new Promise((res, rej) => {
        router.post(IR.saveDatabase, form, {
          preserveState: true,
          preserveScroll: true,
          onSuccess: res,
          onError:   rej,
        });
      });
      setConnState('saved');
    } catch (err) {
      setConnState('failed');
      setConnError(
        err?.response?.data?.message ?? 'Connection failed. Check your credentials and try again.'
      );
    }
  }

  // ── Browse databases ──────────────────────────────────────────────
  async function browseDBs() {
    setBrowsing(true);
    setBrowseOpen(false);
    try {
      const { data } = await axios.post(IR.saveDatabase.replace('save-database', 'list-databases'), form);
      setDbList(data.databases ?? []);
      setBrowseOpen(true);
    } catch (_) {
      setDbList([]);
    } finally {
      setBrowsing(false);
    }
  }

  function pickDB(name) {
    set('database', name);
    setBrowseOpen(false);
  }

  const connLabel = {
    idle:    null,
    testing: 'Testing connection…',
    saving:  'Saving configuration…',
    connected: null,
    failed:  null,
    saved:   `Connected · ${connLatency ?? '?'}ms`,
  }[connState];

  const connectionOptions = (connections ?? ['mysql', 'pgsql', 'sqlite', 'sqlsrv']).map(c => ({
    value: c,
    label: { mysql: 'MySQL', pgsql: 'PostgreSQL', sqlite: 'SQLite', sqlsrv: 'SQL Server' }[c] ?? c.toUpperCase(),
  }));

  return (
    <VStack gap={5}>
      <div>
        <h1 className="il-title">Database Configuration</h1>
        <p className="il-desc">
          Configure the database connection for your aeos365 installation.
          {mode === 'saas' && ' This configures the landlord (platform) database. Tenant databases are created automatically during provisioning.'}
        </p>
      </div>

      {errors.database && <Alert intent="danger">{errors.database}</Alert>}

      {/* SaaS tenant DB advisory */}
      {mode === 'saas' && (
        <div className="il-advisory">
          <div className="il-advisory-icon" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
            </svg>
          </div>
          <span>
            A separate database will be created per tenant at provisioning time.
            This page configures the <strong>landlord database</strong> only.
          </span>
        </div>
      )}

      {/* Driver */}
      <Field label="Database Driver" htmlFor="connection" required>
        <Select
          id="connection"
          value={form.connection}
          onChange={e => set('connection', e.target.value)}
          options={connectionOptions}
        />
      </Field>

      {/* SQLite shortcut */}
      {isSqlite ? (
        <Alert intent="info">
          SQLite will use{' '}
          <code style={{ fontFamily: 'var(--aeos-font-mono)' }}>database/database.sqlite</code>.
          No additional credentials needed.
        </Alert>
      ) : (
        <>
          {/* Host + Port — responsive row */}
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'minmax(0, 1fr) clamp(80px, 18%, 110px)',
            gap: '12px',
            alignItems: 'end',
          }}>
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
            <Field label="Port" htmlFor="port" required>
              <Input
                id="port"
                type="number"
                value={form.port}
                onChange={e => set('port', e.target.value)}
              />
            </Field>
          </div>

          {/* Database name + Browse */}
          <Field label="Database Name" htmlFor="database" error={errors.database} required>
            <div style={{ display: 'flex', gap: 8 }}>
              <Input
                id="database"
                value={form.database}
                onChange={e => set('database', e.target.value)}
                leftIcon="folder"
                placeholder="aeos365"
                error={!!errors.database}
                style={{ flex: 1, minWidth: 0 }}
              />
              <Button
                intent="ghost"
                size="sm"
                loading={browsing}
                onClick={browseDBs}
                style={{ flexShrink: 0, fontSize: '.78rem' }}
                title="Browse databases on server"
              >
                Browse
              </Button>
            </div>

            {/* DB picker dropdown */}
            {browseOpen && dbList.length > 0 && (
              <div style={{
                marginTop: 4,
                border: '1px solid var(--aeos-divider)',
                borderRadius: 'var(--aeos-r-md)',
                background: 'var(--aeos-bg-card)',
                maxHeight: 160,
                overflowY: 'auto',
                zIndex: 10,
                boxShadow: '0 8px 24px rgba(0,0,0,.15)',
              }}>
                {dbList.map(db => (
                  <button
                    key={db}
                    type="button"
                    onClick={() => pickDB(db)}
                    style={{
                      display: 'block',
                      width: '100%',
                      textAlign: 'left',
                      padding: '7px 12px',
                      background: 'transparent',
                      border: 'none',
                      borderBottom: '1px solid var(--aeos-divider)',
                      fontFamily: 'var(--aeos-font-mono)',
                      fontSize: '.82rem',
                      color: 'var(--aeos-text-primary)',
                      cursor: 'pointer',
                      transition: 'background .1s',
                    }}
                    onMouseEnter={e => e.currentTarget.style.background = 'rgba(0,163,184,.06)'}
                    onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                  >
                    {db}
                  </button>
                ))}
              </div>
            )}
          </Field>

          {/* Username + Password */}
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 180px), 1fr))',
            gap: 12,
          }}>
            <Field label="Username" htmlFor="db_user" error={errors.username} required>
              <Input
                id="db_user"
                value={form.username}
                onChange={e => set('username', e.target.value)}
                leftIcon="user"
                error={!!errors.username}
              />
            </Field>
            <Field label="Password" htmlFor="db_pass">
              <Input
                id="db_pass"
                type="password"
                value={form.password}
                onChange={e => set('password', e.target.value)}
                leftIcon="settings"
                placeholder="Leave blank if none"
              />
            </Field>
          </div>
        </>
      )}

      {/* Connection error */}
      {connState === 'failed' && connError && (
        <Alert intent="danger">{connError}</Alert>
      )}

      {/* Verify & Save action row */}
      <HStack gap={3} align="center" wrap="wrap">
        <Button
          intent={isSaved ? 'soft' : 'primary'}
          size="sm"
          loading={connState === 'testing' || connState === 'saving'}
          onClick={verifyAndSave}
        >
          {isSaved ? 'Re-verify & Save' : 'Verify & Save'}
        </Button>

        {connState === 'saved' && (
          <span className="il-conn-badge">
            {connLabel}
          </span>
        )}
      </HStack>

      {/* Nav */}
      <div className="il-nav">
        <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get(IR.requirements)}>Back</Button>
        <Button
          intent="primary"
          rightIcon="arrowRight"
          disabled={!isSaved}
          onClick={() => router.get(IR.settings)}
        >
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
