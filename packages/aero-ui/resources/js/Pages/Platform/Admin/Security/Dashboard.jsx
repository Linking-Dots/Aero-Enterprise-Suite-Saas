import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Toggle,
  Modal,
  Alert,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SESSION_INTENT = {
  active:   'success',
  idle:     'warning',
  expired:  'neutral',
};

export default function SecurityDashboard({
  activeSessionsCount,
  recentLoginAttempts,
  ipAllowlist,
  mfaEnforced,
  ssoConfig,
  sessions,
}) {
  const toast             = useToast();
  const canViewSessions   = useHRMAC('platform-security.staff-sessions.view');
  const canForceLogout    = useHRMAC('platform-security.staff-sessions.force-logout');
  const canMfa            = useHRMAC('platform-security.staff-mfa.enforce');
  const canSso            = useHRMAC('platform-security.staff-sso.configure');
  const canIp             = useHRMAC('platform-security.ip-allowlist.manage');

  // IP allowlist
  const [showAddIp,    setShowAddIp]    = useState(false);
  const [removeIpTarget, setRemoveIpTarget] = useState(null);
  const ipForm = useForm({ ip_cidr: '', label: '' });

  // MFA enforcement
  const [mfaToggle,   setMfaToggle]   = useState(mfaEnforced ?? false);
  const [togglingMfa, setTogglingMfa] = useState(false);

  // Session force logout
  const [logoutTarget,  setLogoutTarget]  = useState(null);
  const [loggingOut,    setLoggingOut]    = useState(false);

  // SSO config modal
  const [showSso, setShowSso] = useState(false);
  const ssoForm = useForm({
    provider:      ssoConfig?.provider      ?? 'saml',
    entity_id:     ssoConfig?.entity_id     ?? '',
    sso_url:       ssoConfig?.sso_url       ?? '',
    x509_cert:     ssoConfig?.x509_cert     ?? '',
    is_enabled:    ssoConfig?.is_enabled    ?? false,
  });

  function handleAddIp(e) {
    e.preventDefault();
    ipForm.post(route('platform.admin.security.ip.add'), {
      onSuccess: () => {
        setShowAddIp(false);
        ipForm.reset();
        toast.success('IP rule added.');
      },
      onError: () => toast.error('Failed to add IP rule.'),
    });
  }

  function confirmRemoveIp() {
    if (!removeIpTarget) return;
    router.post(
      route('platform.admin.security.ip.remove', removeIpTarget.id),
      { _method: 'DELETE' },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`IP rule "${removeIpTarget.label}" removed.`);
          setRemoveIpTarget(null);
        },
        onError: () => toast.error('Remove failed.'),
      }
    );
  }

  function handleMfaToggle(checked) {
    setTogglingMfa(true);
    router.post(
      route('platform.admin.security.mfa.enforce'),
      { enforce: checked },
      {
        preserveState: true,
        onSuccess: () => {
          setMfaToggle(checked);
          toast.success(`MFA enforcement ${checked ? 'enabled' : 'disabled'}.`);
        },
        onError:  () => toast.error('MFA toggle failed.'),
        onFinish: () => setTogglingMfa(false),
      }
    );
  }

  function confirmForceLogout() {
    if (!logoutTarget) return;
    setLoggingOut(true);
    router.post(
      route('platform.admin.security.sessions.logout', logoutTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Session for ${logoutTarget.user_name ?? logoutTarget.user_id} terminated.`);
          setLogoutTarget(null);
        },
        onError:  () => toast.error('Force logout failed.'),
        onFinish: () => setLoggingOut(false),
      }
    );
  }

  function saveSso(e) {
    e.preventDefault();
    ssoForm.post(route('platform.admin.security.sso.configure'), {
      onSuccess: () => {
        setShowSso(false);
        toast.success('SSO configuration saved.');
      },
      onError: () => toast.error('SSO save failed.'),
    });
  }

  const ipColumns = [
    {
      key: 'ip_cidr',
      label: 'IP / CIDR',
      render: (row) => <Mono>{row.ip_cidr}</Mono>,
    },
    {
      key: 'label',
      label: 'Label',
      render: (row) => <Text>{row.label ?? '—'}</Text>,
    },
    {
      key: 'created_by',
      label: 'Added By',
      render: (row) => <Text tone="secondary">{row.created_by_name ?? row.created_by ?? '—'}</Text>,
    },
    {
      key: 'is_active',
      label: 'Active',
      render: (row) => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canIp ? (
          <Button intent="danger" size="sm" onClick={() => setRemoveIpTarget(row)}>
            Remove
          </Button>
        ) : null,
    },
  ];

  const sessionColumns = [
    {
      key: 'user',
      label: 'Staff Member',
      render: (row) => <Text>{row.user_name ?? row.user_id}</Text>,
    },
    {
      key: 'ip_address',
      label: 'IP',
      render: (row) => <Mono>{row.ip_address ?? '—'}</Mono>,
    },
    {
      key: 'user_agent',
      label: 'Device',
      render: (row) => <Text tone="secondary">{row.user_agent ?? '—'}</Text>,
    },
    {
      key: 'last_active_at',
      label: 'Last Active',
      render: (row) => <Mono>{row.last_active_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canForceLogout ? (
          <Button intent="danger" size="sm" onClick={() => setLogoutTarget(row)}>
            Force Logout
          </Button>
        ) : null,
    },
  ];

  const loginAttemptColumns = [
    {
      key: 'email',
      label: 'Email',
      render: (row) => <Text>{row.email ?? '—'}</Text>,
    },
    {
      key: 'ip_address',
      label: 'IP',
      render: (row) => <Mono>{row.ip_address ?? '—'}</Mono>,
    },
    {
      key: 'success',
      label: 'Result',
      render: (row) => (
        <Badge intent={row.success ? 'success' : 'danger'}>
          {row.success ? 'Success' : 'Failed'}
        </Badge>
      ),
    },
    {
      key: 'created_at',
      label: 'Time',
      render: (row) => <Mono>{row.created_at}</Mono>,
    },
  ];

  return (
    <IndexPageLayout
      title="Security Dashboard"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Platform Security' },
        { label: 'Dashboard' },
      ]}
      description="Overview of active sessions, login activity, MFA, SSO, and IP allowlist."
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('platform.admin.security.audit'))}
        >
          Security Audit Log
        </Button>
      }
    >
      <VStack gap={6}>
        {/* Stats Row */}
        <HStack gap={4} wrap>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>Active Sessions</Eyebrow>
                <Text size="xl">{activeSessionsCount ?? 0}</Text>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>MFA Enforcement</Eyebrow>
                <Badge intent={mfaToggle ? 'success' : 'danger'}>
                  {mfaToggle ? 'Enforced' : 'Not Enforced'}
                </Badge>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>SSO</Eyebrow>
                <Badge intent={ssoConfig?.is_enabled ? 'success' : 'neutral'}>
                  {ssoConfig?.is_enabled ? `Enabled (${ssoConfig.provider})` : 'Disabled'}
                </Badge>
              </VStack>
            </CardBody>
          </Card>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Eyebrow>IP Rules</Eyebrow>
                <Text size="xl">{(ipAllowlist ?? []).length}</Text>
              </VStack>
            </CardBody>
          </Card>
        </HStack>

        {/* MFA + SSO Controls */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Authentication Controls</Eyebrow>
              <HStack gap={6} wrap>
                {canMfa && (
                  <VStack gap={2}>
                    <Text>MFA Enforcement</Text>
                    <Text tone="secondary">Require all staff to use MFA.</Text>
                    <Toggle
                      label="Enforce MFA"
                      checked={mfaToggle}
                      onChange={handleMfaToggle}
                      disabled={togglingMfa}
                    />
                  </VStack>
                )}
                {canSso && (
                  <VStack gap={2}>
                    <Text>SSO Configuration</Text>
                    <Text tone="secondary">
                      {ssoConfig?.is_enabled
                        ? `SAML SSO enabled via ${ssoConfig.provider}.`
                        : 'SSO not configured.'}
                    </Text>
                    <Button intent="soft" onClick={() => setShowSso(true)}>
                      Configure SSO
                    </Button>
                  </VStack>
                )}
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        {/* Active Sessions */}
        {canViewSessions && (
          <VStack gap={3}>
            <HStack gap={2}>
              <Eyebrow>Active Staff Sessions</Eyebrow>
            </HStack>
            <DataTable
              columns={sessionColumns}
              rows={sessions ?? []}
              empty="No active sessions."
            />
          </VStack>
        )}

        {/* Recent Login Attempts */}
        <VStack gap={3}>
          <Eyebrow>Recent Login Attempts</Eyebrow>
          <DataTable
            columns={loginAttemptColumns}
            rows={recentLoginAttempts ?? []}
            empty="No recent login attempts."
          />
        </VStack>

        {/* IP Allowlist */}
        <VStack gap={3}>
          <HStack gap={2}>
            <Eyebrow>IP Allowlist</Eyebrow>
            {canIp && (
              <Button intent="soft" size="sm" leftIcon="plus" onClick={() => setShowAddIp(true)}>
                Add Rule
              </Button>
            )}
          </HStack>
          {(ipAllowlist ?? []).length === 0 && (
            <Alert intent="info" title="No IP restrictions active">
              Staff can log in from any IP address. Add allowlist rules to restrict access.
            </Alert>
          )}
          {(ipAllowlist ?? []).length > 0 && (
            <DataTable
              columns={ipColumns}
              rows={ipAllowlist}
              empty="No IP rules."
            />
          )}
        </VStack>
      </VStack>

      {/* Add IP Modal */}
      <Modal
        open={showAddIp}
        onClose={() => { setShowAddIp(false); ipForm.reset(); }}
        title="Add IP Rule"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={ipForm.processing}
              disabled={ipForm.processing}
              onClick={handleAddIp}
            >
              Add Rule
            </Button>
            <Button intent="ghost" onClick={() => { setShowAddIp(false); ipForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="IP / CIDR" htmlFor="ip_cidr" error={ipForm.errors.ip_cidr} required hint="e.g. 203.0.113.0/24">
            <Input
              id="ip_cidr"
              value={ipForm.data.ip_cidr}
              onChange={e => ipForm.setData('ip_cidr', e.target.value)}
              placeholder="203.0.113.0/24"
              error={ipForm.errors.ip_cidr}
            />
          </Field>
          <Field label="Label" htmlFor="ip_label" error={ipForm.errors.label}>
            <Input
              id="ip_label"
              value={ipForm.data.label}
              onChange={e => ipForm.setData('label', e.target.value)}
              placeholder="Office VPN"
              error={ipForm.errors.label}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Remove IP Confirm Modal */}
      <Modal
        open={!!removeIpTarget}
        onClose={() => setRemoveIpTarget(null)}
        title="Remove IP Rule"
        description={`Remove rule "${removeIpTarget?.label ?? removeIpTarget?.ip_cidr}"? Staff from this range may lose access.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRemoveIp}>
              Confirm Remove
            </Button>
            <Button intent="ghost" onClick={() => setRemoveIpTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Force Logout Confirm Modal */}
      <Modal
        open={!!logoutTarget}
        onClose={() => setLogoutTarget(null)}
        title="Force Logout"
        description={`Terminate session for ${logoutTarget?.user_name ?? logoutTarget?.user_id}? They will be logged out immediately.`}
        footer={
          <HStack gap={2}>
            <Button
              intent="danger"
              loading={loggingOut}
              disabled={loggingOut}
              onClick={confirmForceLogout}
            >
              Force Logout
            </Button>
            <Button intent="ghost" onClick={() => setLogoutTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* SSO Config Modal */}
      <Modal
        open={showSso}
        onClose={() => setShowSso(false)}
        title="Configure SSO"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={ssoForm.processing}
              disabled={ssoForm.processing}
              onClick={saveSso}
            >
              Save SSO Config
            </Button>
            <Button intent="ghost" onClick={() => setShowSso(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Entity ID" htmlFor="sso_entity" error={ssoForm.errors.entity_id} required>
            <Input
              id="sso_entity"
              value={ssoForm.data.entity_id}
              onChange={e => ssoForm.setData('entity_id', e.target.value)}
              placeholder="https://idp.company.com/saml/metadata"
              error={ssoForm.errors.entity_id}
            />
          </Field>
          <Field label="SSO URL" htmlFor="sso_url" error={ssoForm.errors.sso_url} required>
            <Input
              id="sso_url"
              type="url"
              value={ssoForm.data.sso_url}
              onChange={e => ssoForm.setData('sso_url', e.target.value)}
              placeholder="https://idp.company.com/saml/sso"
              error={ssoForm.errors.sso_url}
            />
          </Field>
          <Field label="x509 Certificate" htmlFor="sso_cert" error={ssoForm.errors.x509_cert} required>
            <textarea
              id="sso_cert"
              className="aeos-textarea aeos-text-sm font-mono"
              rows={6}
              value={ssoForm.data.x509_cert}
              onChange={e => ssoForm.setData('x509_cert', e.target.value)}
              placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
            />
          </Field>
          <Toggle
            label="Enable SSO"
            checked={ssoForm.data.is_enabled}
            onChange={v => ssoForm.setData('is_enabled', v)}
          />
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

SecurityDashboard.layout = page => <App title="Security Dashboard">{page}</App>;
