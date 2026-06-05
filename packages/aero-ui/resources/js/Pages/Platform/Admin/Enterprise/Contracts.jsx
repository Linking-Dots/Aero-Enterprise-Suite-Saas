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
  Tabs,
  Tab,
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

const STATUS_INTENT = {
  draft:     'neutral',
  sent:      'warning',
  signed:    'success',
  active:    'success',
  expired:   'neutral',
  cancelled: 'danger',
};

export default function Contracts({ orderForms, plans }) {
  const toast = useToast();
  const canCreate   = useHRMAC('contract-management.order-forms.create');
  const canSend     = useHRMAC('contract-management.order-forms.send');
  const canActivate = useHRMAC('contract-management.order-forms.create'); // re-use create perm for activate

  const [activeTab,      setActiveTab]      = useState('order-forms');
  const [createDrawer,   setCreateDrawer]   = useState(false);
  const [activateTarget, setActivateTarget] = useState(null);
  const [sendTarget,     setSendTarget]     = useState(null);
  const [productInput,   setProductInput]   = useState(PRODUCT_OPTIONS[0].value);

  const planOptions = [
    { value: '', label: '— Select Plan —' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const form = useForm({
    tenant_id:     '',
    plan_id:       '',
    product_ids:   [],
    contract_value: '',
    currency:      'USD',
    starts_at:     '',
    ends_at:       '',
    notes:         '',
  });

  function addProduct() {
    const id = productInput;
    if (!form.data.product_ids.includes(id)) {
      form.setData('product_ids', [...form.data.product_ids, id]);
    }
  }

  function removeProduct(id) {
    form.setData('product_ids', form.data.product_ids.filter(p => p !== id));
  }

  function handleCreate(e) {
    e.preventDefault();
    form.post(route('platform.admin.enterprise.contracts.order-forms.store'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Order form created.');
        setCreateDrawer(false);
        form.reset();
      },
      onError: () => toast.error('Create failed.'),
    });
  }

  function confirmSend() {
    if (!sendTarget) return;
    router.post(
      route('platform.admin.enterprise.contracts.order-forms.send', sendTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Order form sent.'); setSendTarget(null); },
        onError:   () => toast.error('Send failed.'),
      }
    );
  }

  function confirmActivate() {
    if (!activateTarget) return;
    router.post(
      route('platform.admin.enterprise.contracts.order-forms.activate', activateTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Contract activated. Subscription and product entitlements created.');
          setActivateTarget(null);
        },
        onError: () => toast.error('Activation failed.'),
      }
    );
  }

  function downloadPdf(row) {
    window.open(route('platform.admin.enterprise.contracts.order-forms.pdf', row.id), '_blank');
  }

  const orderFormColumns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: r => <Text>{r.tenant_name}</Text>,
    },
    {
      key: 'plan',
      label: 'Plan',
      render: r => <Badge intent="neutral">{r.plan_name ?? '—'}</Badge>,
    },
    {
      key: 'products',
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
      key: 'contract_value',
      label: 'Value',
      render: r => (
        <Mono>{r.currency} {Number(r.contract_value ?? 0).toLocaleString()}</Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: r => (
        <Badge intent={STATUS_INTENT[r.status] ?? 'neutral'}>
          {r.status}
        </Badge>
      ),
    },
    {
      key: 'starts_at',
      label: 'Start',
      render: r => <Mono>{r.starts_at ?? '—'}</Mono>,
    },
    {
      key: 'ends_at',
      label: 'End',
      render: r => <Mono>{r.ends_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          <Button intent="ghost" size="sm" onClick={() => downloadPdf(r)}>
            PDF
          </Button>
          {canSend && r.status === 'draft' && (
            <Button intent="soft" size="sm" onClick={() => setSendTarget(r)}>
              Send
            </Button>
          )}
          {canActivate && (r.status === 'signed' || r.status === 'sent') && (
            <Button intent="primary" size="sm" onClick={() => setActivateTarget(r)}>
              Activate
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Contracts"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Enterprise' },
        { label: 'Contracts' },
      ]}
      description="Manage MSAs and order forms. Activating an order form creates a Subscription and product entitlements."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setCreateDrawer(true)}>
            New Order Form
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={orderFormColumns}
          rows={orderForms?.data ?? []}
          empty="No order forms yet."
        />
        {(orderForms?.last_page ?? 1) > 1 && (
          <Pagination
            page={orderForms.current_page}
            total={orderForms.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.enterprise.contracts.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['orderForms'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Order Form Drawer */}
      <Drawer
        open={createDrawer}
        onClose={() => { setCreateDrawer(false); form.reset(); }}
        title="New Order Form"
      >
        <VStack gap={4}>
          <Field label="Tenant ID" htmlFor="of_tenant" error={form.errors.tenant_id} required>
            <Input
              id="of_tenant"
              value={form.data.tenant_id}
              onChange={e => form.setData('tenant_id', e.target.value)}
              placeholder="Tenant identifier"
              error={form.errors.tenant_id}
            />
          </Field>

          <Field label="Plan (pricing tier)" htmlFor="of_plan" error={form.errors.plan_id} required>
            <Select
              id="of_plan"
              value={form.data.plan_id}
              onChange={e => form.setData('plan_id', e.target.value)}
              options={planOptions}
            />
          </Field>

          <Field label="Entitled Products" htmlFor="of_product" error={form.errors.product_ids} required>
            <HStack gap={2}>
              <Select
                id="of_product"
                value={productInput}
                onChange={e => setProductInput(e.target.value)}
                options={PRODUCT_OPTIONS}
              />
              <Button intent="soft" size="sm" type="button" onClick={addProduct}>
                Add
              </Button>
            </HStack>
            {form.data.product_ids.length > 0 && (
              <HStack gap={1} wrap>
                {form.data.product_ids.map(code => (
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

          <Field label="Contract Value" htmlFor="of_value" error={form.errors.contract_value} required>
            <Input
              id="of_value"
              type="number"
              value={form.data.contract_value}
              onChange={e => form.setData('contract_value', e.target.value)}
              placeholder="50000"
              leftIcon="currencyDollar"
              error={form.errors.contract_value}
            />
          </Field>

          <Field label="Currency" htmlFor="of_currency" error={form.errors.currency}>
            <Select
              id="of_currency"
              value={form.data.currency}
              onChange={e => form.setData('currency', e.target.value)}
              options={[
                { value: 'USD', label: 'USD' },
                { value: 'EUR', label: 'EUR' },
                { value: 'GBP', label: 'GBP' },
              ]}
            />
          </Field>

          <Field label="Start Date" htmlFor="of_starts" error={form.errors.starts_at} required>
            <Input
              id="of_starts"
              type="date"
              value={form.data.starts_at}
              onChange={e => form.setData('starts_at', e.target.value)}
              error={form.errors.starts_at}
            />
          </Field>

          <Field label="End Date" htmlFor="of_ends" error={form.errors.ends_at}>
            <Input
              id="of_ends"
              type="date"
              value={form.data.ends_at}
              onChange={e => form.setData('ends_at', e.target.value)}
              error={form.errors.ends_at}
            />
          </Field>

          <Field label="Notes" htmlFor="of_notes" error={form.errors.notes}>
            <Input
              id="of_notes"
              value={form.data.notes}
              onChange={e => form.setData('notes', e.target.value)}
              placeholder="Internal notes (optional)"
              error={form.errors.notes}
            />
          </Field>

          <HStack gap={2}>
            <Button intent="primary" loading={form.processing} disabled={form.processing} onClick={handleCreate}>
              Create
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setCreateDrawer(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Send Confirm Modal */}
      <Modal
        open={!!sendTarget}
        onClose={() => setSendTarget(null)}
        title={`Send Order Form — ${sendTarget?.tenant_name}`}
        description="This will email the order form to the tenant contact for review and e-signature."
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmSend}>
              Send Now
            </Button>
            <Button intent="ghost" onClick={() => setSendTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Activate Confirm Modal */}
      <Modal
        open={!!activateTarget}
        onClose={() => setActivateTarget(null)}
        title={`Activate Contract — ${activateTarget?.tenant_name}`}
        footer={
          <HStack gap={2}>
            <Button intent="primary" onClick={confirmActivate}>
              Activate Contract
            </Button>
            <Button intent="ghost" onClick={() => setActivateTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Alert
            intent="warning"
            title="Activating this contract will create a Subscription (plan) and one ProductSubscription per entitled product, each with its own invoice. This action cannot be undone."
          />
          <VStack gap={1}>
            <Text tone="secondary">Tenant</Text>
            <Text>{activateTarget?.tenant_name}</Text>
          </VStack>
          <VStack gap={1}>
            <Text tone="secondary">Plan</Text>
            <Badge intent="neutral">{activateTarget?.plan_name ?? '—'}</Badge>
          </VStack>
          <VStack gap={1}>
            <Text tone="secondary">Products to entitle</Text>
            <HStack gap={1} wrap>
              {(activateTarget?.product_codes ?? []).map(code => (
                <Badge key={code} intent="neutral">{code.toUpperCase()}</Badge>
              ))}
            </HStack>
          </VStack>
          <VStack gap={1}>
            <Text tone="secondary">Contract Value</Text>
            <Mono>{activateTarget?.currency} {Number(activateTarget?.contract_value ?? 0).toLocaleString()}</Mono>
          </VStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

Contracts.layout = page => <App title="Contracts">{page}</App>;
