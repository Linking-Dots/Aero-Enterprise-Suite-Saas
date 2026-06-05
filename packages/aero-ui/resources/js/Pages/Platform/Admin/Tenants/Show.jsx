import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  DetailPageLayout,
  Tabs,
  Button,
  Badge,
  Field,
  Input,
  Toggle,
  Modal,
  Alert,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  active:      'success',
  trial:       'primary',
  pending:     'warning',
  suspended:   'danger',
  frozen:      'neutral',
  archived:    'neutral',
  provisioning:'amber',
};

const TABS = [
  { value: 'info',         label: 'Info' },
  { value: 'byoc',         label: 'BYOC Database' },
  { value: 'domains',      label: 'Domains' },
  { value: 'provisioning', label: 'Provisioning Logs' },
];

export default function TenantsShow({ tenant }) {
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('info');

  const canImpersonate = useHRMAC('platform.admin.tenants.impersonate');
  const canSuspend     = useHRMAC('platform.admin.tenants.suspend');
  const canActivate    = useHRMAC('platform.admin.tenants.activate');
  const canFreeze      = useHRMAC('platform.admin.tenants.freeze');
  const canUnfreeze    = useHRMAC('platform.admin.tenants.unfreeze');
  const canArchive     = useHRMAC('platform.admin.tenants.archive');
  const canPurge       = useHRMAC('platform.admin.tenants.destroy');

  const [suspendOpen, setSuspendOpen] = useState(false);
  const [purgeOpen,   setPurgeOpen]   = useState(false);
  const [suspendReason, setSuspendReason] = useState('');

  // BYOC form
  const byocForm = useForm({
    byoc_enabled:   tenant.byoc_enabled ?? false,
    byoc_db_host:   tenant.byoc_db_host ?? '',
    byoc_db_name:   tenant.byoc_db_name ?? '',
    byoc_db_user:   tenant.byoc_db_user ?? '',
    byoc_db_password: '',
    byoc_db_port:   tenant.byoc_db_port ?? '3306',
  });

  // Domain form
  const domainForm = useForm({ domain: '' });

  function saveByoc(e) {
    e.preventDefault();
    byocForm.post(route('platform.admin.tenants.update', tenant.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('BYOC settings saved.'),
    });
  }

  function addDomain(e) {
    e.preventDefault();
    domainForm.post(route('platform.admin.tenants.domains.store', tenant.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Domain added.');
        domainForm.reset();
      },
    });
  }

  function removeDomain(domainId) {
    router.delete(route('platform.admin.tenants.domains.destroy', [tenant.id, domainId]), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Domain removed.'),
    });
  }

  function verifyDomain(domainId) {
    router.post(route('platform.admin.tenants.domains.verify', [tenant.id, domainId]), {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Verification triggered.'),
    });
  }

  function doSuspend() {
    router.post(
      route('platform.admin.tenants.suspend', tenant.id),
      { reason: suspendReason },
      {
        onSuccess: () => {
          toast.success('Tenant suspended.');
          setSuspendOpen(false);
          setSuspendReason('');
        },
        onError: () => toast.error('Failed to suspend tenant.'),
      }
    );
  }

  function doAction(routeName, label) {
    router.post(route(routeName, tenant.id), {}, {
      onSuccess: () => toast.success(`${label} successful.`),
      onError:   () => toast.error(`${label} failed.`),
    });
  }

  function doPurge() {
    router.delete(route('platform.admin.tenants.destroy', tenant.id), {
      onSuccess: () => {
        toast.success('Tenant purged.');
        router.get(route('platform.admin.tenants.index'));
      },
      onError: () => toast.error('Purge failed.'),
    });
  }

  const LOG_INTENT = {
    success:  'success',
    failed:   'danger',
    running:  'warning',
    pending:  'neutral',
  };

  return (
    <DetailPageLayout
      title={tenant.name}
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenants', href: route('platform.admin.tenants.index') },
        { label: tenant.name },
      ]}
      description={`ID: ${tenant.id}`}
      actions={
        <HStack gap={2}>
          {canImpersonate && (
            <Button
              intent="soft"
              leftIcon="user"
              onClick={() =>
                router.post(route('platform.admin.tenants.impersonate', tenant.id))
              }
            >
              Impersonate
            </Button>
          )}
          {canActivate && tenant.status !== 'active' && (
            <Button intent="soft" onClick={() => doAction('platform.admin.tenants.activate', 'Activate')}>
              Activate
            </Button>
          )}
          {canSuspend && tenant.status === 'active' && (
            <Button intent="danger" onClick={() => setSuspendOpen(true)}>
              Suspend
            </Button>
          )}
          {canFreeze && tenant.status === 'active' && (
            <Button intent="soft" onClick={() => doAction('platform.admin.tenants.freeze', 'Freeze')}>
              Freeze
            </Button>
          )}
          {canUnfreeze && tenant.status === 'frozen' && (
            <Button intent="soft" onClick={() => doAction('platform.admin.tenants.unfreeze', 'Unfreeze')}>
              Unfreeze
            </Button>
          )}
          {canArchive && !['archived'].includes(tenant.status) && (
            <Button intent="ghost" onClick={() => doAction('platform.admin.tenants.archive', 'Archive')}>
              Archive
            </Button>
          )}
          {canPurge && (
            <Button intent="danger" onClick={() => setPurgeOpen(true)}>
              Purge
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>
        <HStack gap={2}>
          <Badge intent={STATUS_INTENT[tenant.status] ?? 'neutral'}>
            {tenant.status}
          </Badge>
          {tenant.plan && <Badge intent="neutral">{tenant.plan.name}</Badge>}
        </HStack>

        <Tabs tabs={TABS} value={activeTab} onChange={setActiveTab} />

        {activeTab === 'info' && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Tenant Details</Eyebrow>
                <dl className="pa1-dl-grid">
                  <dt><Text tone="secondary">Email</Text></dt>
                  <dd><Text>{tenant.email}</Text></dd>

                  <dt><Text tone="secondary">Plan</Text></dt>
                  <dd><Text>{tenant.plan?.name ?? '—'}</Text></dd>

                  <dt><Text tone="secondary">Timezone</Text></dt>
                  <dd><Text>{tenant.timezone ?? '—'}</Text></dd>

                  <dt><Text tone="secondary">Created</Text></dt>
                  <dd>
                    <Mono>{new Date(tenant.created_at).toLocaleString()}</Mono>
                  </dd>

                  {tenant.trial_ends_at && (
                    <>
                      <dt><Text tone="secondary">Trial Ends</Text></dt>
                      <dd>
                        <Mono>{new Date(tenant.trial_ends_at).toLocaleDateString()}</Mono>
                      </dd>
                    </>
                  )}

                  {tenant.stripe_trial_ends_at && (
                    <>
                      <dt><Text tone="secondary">Stripe Trial Ends</Text></dt>
                      <dd>
                        <Mono>{new Date(tenant.stripe_trial_ends_at).toLocaleDateString()}</Mono>
                      </dd>
                    </>
                  )}
                </dl>
              </VStack>
            </CardBody>
          </Card>
        )}

        {activeTab === 'byoc' && (
          <Card>
            <CardBody>
              <form onSubmit={saveByoc}>
                <VStack gap={4}>
                  <Eyebrow>Bring-Your-Own-Cloud Database</Eyebrow>

                  <Field label="Enable BYOC" htmlFor="byoc_enabled">
                    <Toggle
                      label="Use custom database connection"
                      checked={byocForm.data.byoc_enabled}
                      onChange={e => byocForm.setData('byoc_enabled', e.target.checked)}
                    />
                  </Field>

                  {byocForm.data.byoc_enabled && (
                    <>
                      <Field
                        label="Database Host"
                        htmlFor="byoc_db_host"
                        error={byocForm.errors.byoc_db_host}
                        required
                      >
                        <Input
                          id="byoc_db_host"
                          value={byocForm.data.byoc_db_host}
                          onChange={e => byocForm.setData('byoc_db_host', e.target.value)}
                          placeholder="db.example.com"
                          error={byocForm.errors.byoc_db_host}
                        />
                      </Field>

                      <Field
                        label="Database Name"
                        htmlFor="byoc_db_name"
                        error={byocForm.errors.byoc_db_name}
                        required
                      >
                        <Input
                          id="byoc_db_name"
                          value={byocForm.data.byoc_db_name}
                          onChange={e => byocForm.setData('byoc_db_name', e.target.value)}
                          placeholder="my_database"
                          error={byocForm.errors.byoc_db_name}
                        />
                      </Field>

                      <Field
                        label="Database User"
                        htmlFor="byoc_db_user"
                        error={byocForm.errors.byoc_db_user}
                        required
                      >
                        <Input
                          id="byoc_db_user"
                          value={byocForm.data.byoc_db_user}
                          onChange={e => byocForm.setData('byoc_db_user', e.target.value)}
                          placeholder="db_user"
                          error={byocForm.errors.byoc_db_user}
                        />
                      </Field>

                      <Field
                        label="Database Password"
                        htmlFor="byoc_db_password"
                        error={byocForm.errors.byoc_db_password}
                        hint="Leave blank to keep current password."
                      >
                        <Input
                          id="byoc_db_password"
                          type="password"
                          value={byocForm.data.byoc_db_password}
                          onChange={e => byocForm.setData('byoc_db_password', e.target.value)}
                          error={byocForm.errors.byoc_db_password}
                        />
                      </Field>

                      <Field
                        label="Port"
                        htmlFor="byoc_db_port"
                        error={byocForm.errors.byoc_db_port}
                      >
                        <Input
                          id="byoc_db_port"
                          value={byocForm.data.byoc_db_port}
                          onChange={e => byocForm.setData('byoc_db_port', e.target.value)}
                          placeholder="3306"
                          error={byocForm.errors.byoc_db_port}
                        />
                      </Field>
                    </>
                  )}

                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={byocForm.processing}>
                      Save BYOC Settings
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

        {activeTab === 'domains' && (
          <VStack gap={4}>
            <Card>
              <CardBody>
                <VStack gap={3}>
                  <Eyebrow>Domains</Eyebrow>
                  {(tenant.domains ?? []).length === 0 ? (
                    <Text tone="secondary">No domains configured.</Text>
                  ) : (
                    <VStack gap={2}>
                      {tenant.domains.map(d => (
                        <HStack key={d.id} gap={3}>
                          <Text>{d.domain}</Text>
                          {d.is_primary && <Badge intent="primary">Primary</Badge>}
                          <Badge intent={d.verified_at ? 'success' : 'warning'}>
                            {d.verified_at ? 'Verified' : 'Unverified'}
                          </Badge>
                          <HStack gap={1}>
                            {!d.verified_at && (
                              <Button
                                intent="ghost"
                                size="sm"
                                onClick={() => verifyDomain(d.id)}
                              >
                                Verify
                              </Button>
                            )}
                            <Button
                              intent="danger"
                              size="sm"
                              onClick={() => removeDomain(d.id)}
                            >
                              Remove
                            </Button>
                          </HStack>
                        </HStack>
                      ))}
                    </VStack>
                  )}
                </VStack>
              </CardBody>
            </Card>

            <Card>
              <CardBody>
                <form onSubmit={addDomain}>
                  <VStack gap={3}>
                    <Eyebrow>Add Domain</Eyebrow>
                    <Field
                      label="Domain"
                      htmlFor="new_domain"
                      error={domainForm.errors.domain}
                      required
                    >
                      <Input
                        id="new_domain"
                        value={domainForm.data.domain}
                        onChange={e => domainForm.setData('domain', e.target.value)}
                        placeholder="app.example.com"
                        error={domainForm.errors.domain}
                      />
                    </Field>
                    <Button type="submit" intent="primary" loading={domainForm.processing}>
                      Add Domain
                    </Button>
                  </VStack>
                </form>
              </CardBody>
            </Card>
          </VStack>
        )}

        {activeTab === 'provisioning' && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Provisioning Logs</Eyebrow>
                {(tenant.provisioningLogs ?? []).length === 0 ? (
                  <Text tone="secondary">No provisioning logs available.</Text>
                ) : (
                  <VStack gap={1}>
                    {tenant.provisioningLogs.map((log, i) => (
                      <HStack key={i} gap={2}>
                        <Badge intent={LOG_INTENT[log.status] ?? 'neutral'}>
                          {log.status}
                        </Badge>
                        <Mono tone="secondary">
                          {log.step}: {log.message}
                        </Mono>
                      </HStack>
                    ))}
                  </VStack>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}
      </VStack>

      {/* Suspend Modal */}
      <Modal
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        title="Suspend Tenant"
        description="Provide a reason for suspension. This will be logged and may be communicated to the tenant."
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doSuspend}>
              Suspend Tenant
            </Button>
            <Button intent="ghost" onClick={() => setSuspendOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="Reason" htmlFor="suspend_reason">
          <Input
            id="suspend_reason"
            value={suspendReason}
            onChange={e => setSuspendReason(e.target.value)}
            placeholder="Policy violation, non-payment, etc."
          />
        </Field>
      </Modal>

      {/* Purge Modal */}
      <Modal
        open={purgeOpen}
        onClose={() => setPurgeOpen(false)}
        title="Purge Tenant — Irreversible"
        description="This will permanently delete the tenant and ALL associated data. This action cannot be undone."
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doPurge}>
              I understand — Purge Permanently
            </Button>
            <Button intent="ghost" onClick={() => setPurgeOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Alert
          intent="danger"
          title="All tenant data, users, files, and billing records will be destroyed."
        />
      </Modal>
    </DetailPageLayout>
  );
}

TenantsShow.layout = page => (
  <App title={page.props.tenant?.name ?? 'Tenant Detail'}>{page}</App>
);
