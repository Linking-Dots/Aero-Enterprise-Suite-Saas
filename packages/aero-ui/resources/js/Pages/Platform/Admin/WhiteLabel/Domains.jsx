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
  Field,
  Input,
  Modal,
  Pagination,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SSL_INTENT = {
  active:        'success',
  provisioning:  'warning',
  expired:       'danger',
  none:          'neutral',
};

const DNS_INTENT = {
  verified: 'success',
  pending:  'warning',
  failed:   'danger',
};

export default function Domains({ domains }) {
  const toast        = useToast();
  const canAdd       = useHRMAC('white-label.custom-domains.add');
  const canVerify    = useHRMAC('white-label.custom-domains.verify');
  const canRemove    = useHRMAC('white-label.custom-domains.remove');
  const canProvision = useHRMAC('white-label.ssl-provisioning.provision');

  const [showAdd,       setShowAdd]       = useState(false);
  const [removeTarget,  setRemoveTarget]  = useState(null);
  const [txtRecord,     setTxtRecord]     = useState(null); // { domain, dns_txt_record }

  const form = useForm({ tenant_id: '', domain: '' });

  function handleAdd(e) {
    e.preventDefault();
    form.post(route('platform.admin.white-label.domains.store'), {
      onSuccess: (page) => {
        setShowAdd(false);
        form.reset();
        toast.success('Domain added successfully.');
        const rec = page.props.flash?.dns_txt_record;
        const dom = page.props.flash?.domain;
        if (rec) setTxtRecord({ domain: dom, dns_txt_record: rec });
      },
      onError: () => toast.error('Failed to add domain.'),
    });
  }

  function handleVerify(domain) {
    router.post(
      route('platform.admin.white-label.domains.verify', domain.id),
      {},
      {
        preserveState: true,
        onSuccess: () => toast.success(`Verification initiated for ${domain.domain}.`),
        onError:   () => toast.error('Verification failed.'),
      }
    );
  }

  function handleProvisionSsl(domain) {
    router.post(
      route('platform.admin.white-label.domains.ssl', domain.id),
      {},
      {
        preserveState: true,
        onSuccess: () => toast.success(`SSL provisioning started for ${domain.domain}.`),
        onError:   () => toast.error('SSL provisioning failed.'),
      }
    );
  }

  function confirmRemove() {
    if (!removeTarget) return;
    router.post(
      route('platform.admin.white-label.domains.remove', removeTarget.id),
      { _method: 'DELETE' },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Domain ${removeTarget.domain} removed.`);
          setRemoveTarget(null);
        },
        onError: () => toast.error('Remove failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'domain',
      label: 'Domain',
      render: (row) => <Mono>{row.domain}</Mono>,
    },
    {
      key: 'tenant',
      label: 'Tenant',
      render: (row) => <Text>{row.tenant_name ?? row.tenant_id ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'DNS Status',
      render: (row) => (
        <Badge intent={DNS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'ssl_status',
      label: 'SSL',
      render: (row) => (
        <Badge intent={SSL_INTENT[row.ssl_status] ?? 'neutral'}>
          {row.ssl_status}
        </Badge>
      ),
    },
    {
      key: 'ssl_expires_at',
      label: 'SSL Expires',
      render: (row) => <Mono>{row.ssl_expires_at ?? '—'}</Mono>,
    },
    {
      key: 'verified_at',
      label: 'Verified',
      render: (row) => <Mono>{row.verified_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canVerify && row.status !== 'verified' && (
            <Button intent="soft" size="sm" onClick={() => handleVerify(row)}>
              Verify
            </Button>
          )}
          {canProvision && row.status === 'verified' && row.ssl_status !== 'active' && (
            <Button intent="soft" size="sm" onClick={() => handleProvisionSsl(row)}>
              Provision SSL
            </Button>
          )}
          {canRemove && (
            <Button intent="danger" size="sm" onClick={() => setRemoveTarget(row)}>
              Remove
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Custom Domains"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'White Label' },
        { label: 'Custom Domains' },
      ]}
      description="Manage per-tenant custom domains and SSL certificates."
      actions={
        canAdd && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowAdd(true)}>
            Add Domain
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={domains?.data ?? []}
          empty="No custom domains configured."
        />

        {(domains?.last_page ?? 1) > 1 && (
          <Pagination
            page={domains.current_page}
            total={domains.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.white-label.domains.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['domains'] }
              )
            }
          />
        )}
      </VStack>

      {/* Add Domain Modal */}
      <Modal
        open={showAdd}
        onClose={() => { setShowAdd(false); form.reset(); }}
        title="Add Custom Domain"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleAdd}
            >
              Add Domain
            </Button>
            <Button intent="ghost" onClick={() => { setShowAdd(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Tenant ID" htmlFor="tenant_id" error={form.errors.tenant_id} required>
            <Input
              id="tenant_id"
              value={form.data.tenant_id}
              onChange={e => form.setData('tenant_id', e.target.value)}
              placeholder="tenant-uuid"
              error={form.errors.tenant_id}
            />
          </Field>
          <Field label="Domain" htmlFor="domain" error={form.errors.domain} required hint="e.g. app.client.com">
            <Input
              id="domain"
              value={form.data.domain}
              onChange={e => form.setData('domain', e.target.value)}
              placeholder="app.client.com"
              error={form.errors.domain}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Remove Confirm Modal */}
      <Modal
        open={!!removeTarget}
        onClose={() => setRemoveTarget(null)}
        title="Remove Domain"
        description={`Remove "${removeTarget?.domain}"? SSL provisioning will be revoked. This cannot be undone.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRemove}>
              Confirm Remove
            </Button>
            <Button intent="ghost" onClick={() => setRemoveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* DNS TXT Record Modal */}
      <Modal
        open={!!txtRecord}
        onClose={() => setTxtRecord(null)}
        title="DNS Verification Record"
        size="md"
        footer={
          <Button intent="primary" onClick={() => setTxtRecord(null)}>
            Done
          </Button>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Add the following TXT record to <Mono>{txtRecord?.domain}</Mono> DNS to verify ownership.
          </Text>
          <Card>
            <CardBody>
              <VStack gap={1}>
                <Text tone="secondary">TXT Record Value</Text>
                <Mono>{txtRecord?.dns_txt_record}</Mono>
              </VStack>
            </CardBody>
          </Card>
          <Text tone="secondary">
            Once added, click Verify on the domain row. DNS propagation may take up to 48 hours.
          </Text>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

Domains.layout = page => <App title="Custom Domains">{page}</App>;
