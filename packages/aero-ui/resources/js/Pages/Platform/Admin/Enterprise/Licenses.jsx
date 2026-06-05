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
  Select,
  Toggle,
  Modal,
  Drawer,
  Card,
  CardBody,
  Alert,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const PRODUCT_OPTIONS = [
  { value: 'hrm',     label: 'HRM' },
  { value: 'finance', label: 'Finance' },
  { value: 'crm',     label: 'CRM' },
  { value: 'project', label: 'Project' },
  { value: 'payroll', label: 'Payroll' },
  { value: 'bi',      label: 'BI & Analytics' },
];

const PLAN_OPTIONS = [
  { value: '',         label: '— None —' },
  { value: 'starter',  label: 'Starter' },
  { value: 'growth',   label: 'Growth' },
  { value: 'business', label: 'Business' },
  { value: 'enterprise', label: 'Enterprise' },
];

export default function Licenses({ licenses, plans }) {
  const toast = useToast();
  const canGenerate = useHRMAC('license-management.license-keys.generate');
  const canRevoke   = useHRMAC('license-management.license-keys.revoke');
  const canExtend   = useHRMAC('license-management.license-keys.extend');
  const canView     = useHRMAC('license-management.activations.view');

  const [generateDrawer,  setGenerateDrawer]  = useState(false);
  const [revokeTarget,    setRevokeTarget]    = useState(null);
  const [extendTarget,    setExtendTarget]    = useState(null);
  const [activationsTarget, setActivationsTarget] = useState(null);
  const [activations,     setActivations]     = useState([]);
  const [loadingActs,     setLoadingActs]     = useState(false);
  const [productInput,    setProductInput]    = useState(PRODUCT_OPTIONS[0].value);

  const form = useForm({
    issued_to_name:   '',
    issued_to_email:  '',
    product_codes:    [],
    plan_code:        '',
    max_activations:  '3',
    expires_at:       '',
  });

  const extendForm = useForm({ expires_at: '' });

  function addProduct() {
    if (!form.data.product_codes.includes(productInput)) {
      form.setData('product_codes', [...form.data.product_codes, productInput]);
    }
  }

  function removeProduct(code) {
    form.setData('product_codes', form.data.product_codes.filter(c => c !== code));
  }

  function handleGenerate(e) {
    e.preventDefault();
    form.post(route('platform.admin.enterprise.licenses.generate'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('License key generated.');
        setGenerateDrawer(false);
        form.reset();
      },
      onError: () => toast.error('Generate failed.'),
    });
  }

  function confirmRevoke() {
    if (!revokeTarget) return;
    router.post(
      route('platform.admin.enterprise.licenses.revoke', revokeTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('License revoked.'); setRevokeTarget(null); },
        onError:   () => toast.error('Revoke failed.'),
      }
    );
  }

  function openExtend(row) {
    setExtendTarget(row);
    extendForm.setData('expires_at', row.expires_at ?? '');
  }

  function submitExtend(e) {
    e.preventDefault();
    extendForm.post(route('platform.admin.enterprise.licenses.extend', extendTarget.id), {
      preserveState: true,
      onSuccess: () => { toast.success('License extended.'); setExtendTarget(null); },
      onError:   () => toast.error('Extend failed.'),
    });
  }

  function openActivations(row) {
    setActivationsTarget(row);
    setLoadingActs(true);
    router.get(
      route('platform.admin.enterprise.licenses.activations', row.id),
      {},
      {
        preserveState: true,
        only: ['licenseActivations'],
        onSuccess: page => {
          setActivations(page.props.licenseActivations ?? []);
          setLoadingActs(false);
        },
        onError: () => setLoadingActs(false),
      }
    );
  }

  const columns = [
    {
      key: 'issued_to',
      label: 'Issued To',
      render: r => (
        <VStack gap={0}>
          <Text>{r.issued_to_name}</Text>
          <Text tone="secondary">{r.issued_to_email}</Text>
        </VStack>
      ),
    },
    {
      key: 'product_codes',
      label: 'Products',
      render: r => (
        <HStack gap={1} wrap>
          {(r.product_codes ?? []).map(code => (
            <Badge key={code} intent="neutral">{code.toUpperCase()}</Badge>
          ))}
        </HStack>
      ),
    },
    {
      key: 'plan_code',
      label: 'Plan Tier',
      render: r => r.plan_code
        ? <Badge intent="neutral">{r.plan_code}</Badge>
        : <Text tone="secondary">—</Text>,
    },
    {
      key: 'activations',
      label: 'Activations',
      render: r => (
        <Badge intent={r.activation_count >= r.max_activations ? 'warning' : 'success'}>
          {r.activation_count} / {r.max_activations}
        </Badge>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: r => r.revoked_at
        ? <Badge intent="danger">Revoked</Badge>
        : r.expires_at && new Date(r.expires_at) < new Date()
          ? <Badge intent="warning">Expired</Badge>
          : <Badge intent="success">Active</Badge>,
    },
    {
      key: 'expires_at',
      label: 'Expires',
      render: r => <Mono>{r.expires_at ?? 'Never'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canView && (
            <Button intent="ghost" size="sm" onClick={() => openActivations(r)}>
              Activations
            </Button>
          )}
          {canExtend && !r.revoked_at && (
            <Button intent="ghost" size="sm" onClick={() => openExtend(r)}>
              Extend
            </Button>
          )}
          {canRevoke && !r.revoked_at && (
            <Button intent="danger" size="sm" onClick={() => setRevokeTarget(r)}>
              Revoke
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const activationColumns = [
    { key: 'domain', label: 'Domain', render: r => <Mono>{r.domain}</Mono> },
    { key: 'ip', label: 'IP', render: r => <Mono>{r.ip_address}</Mono> },
    { key: 'activated_at', label: 'Activated', render: r => <Mono>{r.activated_at}</Mono> },
    { key: 'last_ping', label: 'Last Ping', render: r => <Mono>{r.last_ping_at ?? '—'}</Mono> },
    {
      key: 'status',
      label: 'Status',
      render: r => (
        <Badge intent={r.deactivated_at ? 'neutral' : 'success'}>
          {r.deactivated_at ? 'Deactivated' : 'Active'}
        </Badge>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="License Keys"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Enterprise' },
        { label: 'Licenses' },
      ]}
      description="Generate and manage standalone license keys for product entitlements."
      actions={
        canGenerate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setGenerateDrawer(true)}>
            Generate License
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={licenses?.data ?? []}
          empty="No license keys generated."
        />
        {(licenses?.last_page ?? 1) > 1 && (
          <Pagination
            page={licenses.current_page}
            total={licenses.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.enterprise.licenses.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['licenses'] }
              )
            }
          />
        )}
      </VStack>

      {/* Generate Drawer */}
      <Drawer
        open={generateDrawer}
        onClose={() => { setGenerateDrawer(false); form.reset(); }}
        title="Generate License Key"
      >
        <VStack gap={4}>
          <Field label="Issued To — Name" htmlFor="lic_name" error={form.errors.issued_to_name} required>
            <Input
              id="lic_name"
              value={form.data.issued_to_name}
              onChange={e => form.setData('issued_to_name', e.target.value)}
              placeholder="Customer name"
              error={form.errors.issued_to_name}
            />
          </Field>

          <Field label="Issued To — Email" htmlFor="lic_email" error={form.errors.issued_to_email} required>
            <Input
              id="lic_email"
              type="email"
              value={form.data.issued_to_email}
              onChange={e => form.setData('issued_to_email', e.target.value)}
              placeholder="customer@company.com"
              leftIcon="mail"
              error={form.errors.issued_to_email}
            />
          </Field>

          <Field label="Plan Tier (optional — metadata only)" htmlFor="lic_plan" error={form.errors.plan_code}>
            <Select
              id="lic_plan"
              value={form.data.plan_code}
              onChange={e => form.setData('plan_code', e.target.value)}
              options={PLAN_OPTIONS}
            />
          </Field>

          <Field label="Products" htmlFor="lic_product" error={form.errors.product_codes} required>
            <HStack gap={2}>
              <Select
                id="lic_product"
                value={productInput}
                onChange={e => setProductInput(e.target.value)}
                options={PRODUCT_OPTIONS}
              />
              <Button intent="soft" size="sm" type="button" onClick={addProduct}>
                Add
              </Button>
            </HStack>
            {form.data.product_codes.length > 0 && (
              <HStack gap={1} wrap>
                {form.data.product_codes.map(code => (
                  <HStack key={code} gap={1}>
                    <Badge intent="neutral">{code.toUpperCase()}</Badge>
                    <Button intent="ghost" size="sm" type="button" onClick={() => removeProduct(code)}>
                      x
                    </Button>
                  </HStack>
                ))}
              </HStack>
            )}
          </Field>

          <Field label="Max Activations" htmlFor="lic_max" error={form.errors.max_activations} required>
            <Input
              id="lic_max"
              type="number"
              value={form.data.max_activations}
              onChange={e => form.setData('max_activations', e.target.value)}
              placeholder="3"
              error={form.errors.max_activations}
            />
          </Field>

          <Field label="Expires At (optional)" htmlFor="lic_exp" error={form.errors.expires_at}>
            <Input
              id="lic_exp"
              type="date"
              value={form.data.expires_at}
              onChange={e => form.setData('expires_at', e.target.value)}
              error={form.errors.expires_at}
            />
          </Field>

          <HStack gap={2}>
            <Button intent="primary" loading={form.processing} disabled={form.processing} onClick={handleGenerate}>
              Generate
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setGenerateDrawer(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Revoke Confirm Modal */}
      <Modal
        open={!!revokeTarget}
        onClose={() => setRevokeTarget(null)}
        title="Revoke License"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmRevoke}>
              Revoke License
            </Button>
            <Button intent="ghost" onClick={() => setRevokeTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Alert
            intent="danger"
            title="This action is irreversible. Revoking this license will immediately deactivate all installations using it."
          />
          <Text tone="secondary">
            License issued to <Text>{revokeTarget?.issued_to_name}</Text> ({revokeTarget?.issued_to_email}).
            Products: {(revokeTarget?.product_codes ?? []).map(c => c.toUpperCase()).join(', ')}.
          </Text>
        </VStack>
      </Modal>

      {/* Extend Modal */}
      <Modal
        open={!!extendTarget}
        onClose={() => setExtendTarget(null)}
        title={`Extend License — ${extendTarget?.issued_to_name}`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" loading={extendForm.processing} disabled={extendForm.processing} onClick={submitExtend}>
              Save
            </Button>
            <Button intent="ghost" onClick={() => setExtendTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <Field label="New Expiry Date" htmlFor="ext_exp" error={extendForm.errors.expires_at} required>
          <Input
            id="ext_exp"
            type="date"
            value={extendForm.data.expires_at}
            onChange={e => extendForm.setData('expires_at', e.target.value)}
            error={extendForm.errors.expires_at}
          />
        </Field>
      </Modal>

      {/* Activations Modal */}
      <Modal
        open={!!activationsTarget}
        onClose={() => { setActivationsTarget(null); setActivations([]); }}
        title={`Activations — ${activationsTarget?.issued_to_name}`}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => { setActivationsTarget(null); setActivations([]); }}>
            Close
          </Button>
        }
      >
        {loadingActs ? (
          <Text tone="secondary">Loading activations...</Text>
        ) : (
          <DataTable
            columns={activationColumns}
            rows={activations}
            empty="No activations yet."
          />
        )}
      </Modal>
    </IndexPageLayout>
  );
}

Licenses.layout = page => <App title="License Keys">{page}</App>;
