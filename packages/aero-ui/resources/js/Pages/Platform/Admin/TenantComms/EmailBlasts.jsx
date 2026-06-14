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
  Textarea,
  Toggle,
  Modal,
  Pagination,
  Card,
  CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = {
  draft: 'neutral',
  sent:  'success',
};

const TENANT_STATUS_OPTIONS = [
  { value: '',         label: 'Any Status' },
  { value: 'active',   label: 'Active' },
  { value: 'trial',    label: 'Trial' },
  { value: 'suspended', label: 'Suspended' },
  { value: 'cancelled', label: 'Cancelled' },
];

export default function EmailBlasts({ blasts, plans, products, filters }) {
  const toast     = useToast();
  const canCreate = useHRMAC('tenant-communications.email-blasts.create');
  const canSend   = useHRMAC('tenant-communications.email-blasts.send');

  const [showCreate, setShowCreate] = useState(false);
  const [sendTarget, setSendTarget] = useState(null);
  const [viewTarget, setViewTarget] = useState(null);

  // Multi-select helpers for plans / products
  const [selectedPlan,    setSelectedPlan]    = useState('');
  const [selectedProduct, setSelectedProduct] = useState('');

  const form = useForm({
    subject:   '',
    body_html: '',
    target_filter: {
      plan_ids:    [],
      product_ids: [],
      statuses:    [],
    },
  });

  function addPlan() {
    if (selectedPlan && !form.data.target_filter.plan_ids.includes(selectedPlan)) {
      form.setData('target_filter', {
        ...form.data.target_filter,
        plan_ids: [...form.data.target_filter.plan_ids, selectedPlan],
      });
    }
    setSelectedPlan('');
  }

  function removePlan(id) {
    form.setData('target_filter', {
      ...form.data.target_filter,
      plan_ids: form.data.target_filter.plan_ids.filter(x => x !== id),
    });
  }

  function addProduct() {
    if (selectedProduct && !form.data.target_filter.product_ids.includes(selectedProduct)) {
      form.setData('target_filter', {
        ...form.data.target_filter,
        product_ids: [...form.data.target_filter.product_ids, selectedProduct],
      });
    }
    setSelectedProduct('');
  }

  function removeProduct(id) {
    form.setData('target_filter', {
      ...form.data.target_filter,
      product_ids: form.data.target_filter.product_ids.filter(x => x !== id),
    });
  }

  function toggleStatus(status) {
    const current = form.data.target_filter.statuses;
    const updated = current.includes(status)
      ? current.filter(s => s !== status)
      : [...current, status];
    form.setData('target_filter', { ...form.data.target_filter, statuses: updated });
  }

  function planName(id) {
    const p = (plans ?? []).find(p => String(p.id) === String(id));
    return p ? p.name : `Plan #${id}`;
  }

  function productName(id) {
    const p = (products ?? []).find(p => String(p.id) === String(id));
    return p ? p.name : `Product #${id}`;
  }

  const planOptions = [
    { value: '', label: 'Add plan filter…' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const productOptions = [
    { value: '', label: 'Add product filter…' },
    ...(products ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const statusOptions = TENANT_STATUS_OPTIONS.filter(o => o.value !== '');

  function handleCreate(e) {
    e.preventDefault();
    form.post(route('platform.admin.tenant-comms.email-blasts.store'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Email blast created as draft.');
        setShowCreate(false);
        form.reset();
      },
      onError: () => toast.error('Failed to create blast.'),
    });
  }

  function confirmSend() {
    if (!sendTarget) return;
    router.post(
      route('platform.admin.tenant-comms.email-blasts.send', sendTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Blast "${sendTarget.subject}" queued for delivery.`);
          setSendTarget(null);
        },
        onError: () => toast.error('Send failed.'),
      }
    );
  }

  const columns = [
    {
      key: 'subject',
      label: 'Subject',
      render: (row) => (
        <Button intent="ghost" size="sm" onClick={() => setViewTarget(row)}>
          {row.subject}
        </Button>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={row.sent_at ? 'success' : 'neutral'}>
          {row.sent_at ? 'Sent' : 'Draft'}
        </Badge>
      ),
    },
    {
      key: 'sent_count',
      label: 'Recipients',
      render: (row) => (
        <Text tone="secondary">{row.sent_count ?? '—'}</Text>
      ),
    },
    {
      key: 'sent_at',
      label: 'Sent At',
      render: (row) => <Mono>{row.sent_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canSend && !row.sent_at ? (
          <Button intent="soft" size="sm" onClick={() => setSendTarget(row)}>
            Send
          </Button>
        ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Email Blasts"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenant Comms' },
        { label: 'Email Blasts' },
      ]}
      description="Send targeted bulk emails to tenant users based on plan, product, and status."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={() => setShowCreate(true)}>
            New Email Blast
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={blasts?.data ?? []}
          empty="No email blasts found."
        />

        {(blasts?.last_page ?? 1) > 1 && (
          <Pagination
            page={blasts.current_page}
            total={blasts.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.tenant-comms.email-blasts.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['blasts'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create Modal */}
      <Modal
        open={showCreate}
        onClose={() => { setShowCreate(false); form.reset(); }}
        title="New Email Blast"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleCreate}
            >
              Save as Draft
            </Button>
            <Button intent="ghost" onClick={() => { setShowCreate(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Subject Line" htmlFor="eb_subject" error={form.errors.subject} required>
            <Input
              id="eb_subject"
              value={form.data.subject}
              onChange={e => form.setData('subject', e.target.value)}
              placeholder="Important update about your AEOS subscription"
              error={form.errors.subject}
            />
          </Field>

          <Field label="Email Body (HTML)" htmlFor="eb_body" error={form.errors.body_html} required>
            <Textarea
              id="eb_body"
              value={form.data.body_html}
              onChange={e => form.setData('body_html', e.target.value)}
              placeholder="<p>Dear tenant,</p><p>We wanted to let you know…</p>"
              rows={8}
              error={form.errors.body_html}
            />
          </Field>

          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Targeting Filters</Eyebrow>
                <Text tone="secondary">
                  Filters are combined with AND. Tenants matching ALL active filters receive the email. Leave all blank to target all tenants.
                </Text>

                {/* Plan filter */}
                <Field label="Plan Filter" htmlFor="eb_plan" hint="Target tenants with an active subscription to one of these plans.">
                  <HStack gap={2}>
                    <Select
                      id="eb_plan"
                      value={selectedPlan}
                      onChange={e => setSelectedPlan(e.target.value)}
                      options={planOptions}
                    />
                    <Button intent="soft" size="sm" onClick={addPlan}>
                      Add
                    </Button>
                  </HStack>
                  {form.data.target_filter.plan_ids.length > 0 && (
                    <HStack gap={1} wrap>
                      {form.data.target_filter.plan_ids.map(id => (
                        <HStack key={id} gap={1}>
                          <Badge intent="neutral">{planName(id)}</Badge>
                          <Button intent="ghost" size="sm" onClick={() => removePlan(id)}>
                            x
                          </Button>
                        </HStack>
                      ))}
                    </HStack>
                  )}
                </Field>

                {/* Product filter */}
                <Field label="Product Filter" htmlFor="eb_product" hint="Target tenants with an active ProductSubscription to one of these products.">
                  <HStack gap={2}>
                    <Select
                      id="eb_product"
                      value={selectedProduct}
                      onChange={e => setSelectedProduct(e.target.value)}
                      options={productOptions}
                    />
                    <Button intent="soft" size="sm" onClick={addProduct}>
                      Add
                    </Button>
                  </HStack>
                  {form.data.target_filter.product_ids.length > 0 && (
                    <HStack gap={1} wrap>
                      {form.data.target_filter.product_ids.map(id => (
                        <HStack key={id} gap={1}>
                          <Badge intent="neutral">{productName(id)}</Badge>
                          <Button intent="ghost" size="sm" onClick={() => removeProduct(id)}>
                            x
                          </Button>
                        </HStack>
                      ))}
                    </HStack>
                  )}
                </Field>

                {/* Status filter */}
                <Field label="Tenant Status Filter" hint="Leave all unchecked to include any status.">
                  <HStack gap={2} wrap>
                    {statusOptions.map(opt => (
                      <Toggle
                        key={opt.value}
                        label={opt.label}
                        checked={form.data.target_filter.statuses.includes(opt.value)}
                        onChange={() => toggleStatus(opt.value)}
                      />
                    ))}
                  </HStack>
                </Field>
              </VStack>
            </CardBody>
          </Card>

          {form.errors.target_filter && (
            <Alert intent="danger" title={form.errors.target_filter} />
          )}
        </VStack>
      </Modal>

      {/* Send Confirm */}
      <Modal
        open={!!sendTarget}
        onClose={() => setSendTarget(null)}
        title="Send Email Blast"
        description={`Send "${sendTarget?.subject}" to all matching tenants now? This action cannot be undone.`}
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

      {/* View Modal */}
      <Modal
        open={!!viewTarget}
        onClose={() => setViewTarget(null)}
        title={viewTarget?.subject}
        size="lg"
        footer={
          <Button intent="ghost" onClick={() => setViewTarget(null)}>Close</Button>
        }
      >
        {viewTarget && (
          <VStack gap={3}>
            <HStack gap={4} wrap>
              <VStack gap={0}>
                <Text tone="secondary">Status</Text>
                <Badge intent={viewTarget.sent_at ? 'success' : 'neutral'}>
                  {viewTarget.sent_at ? 'Sent' : 'Draft'}
                </Badge>
              </VStack>
              {viewTarget.sent_at && (
                <VStack gap={0}>
                  <Text tone="secondary">Sent At</Text>
                  <Mono>{viewTarget.sent_at}</Mono>
                </VStack>
              )}
              <VStack gap={0}>
                <Text tone="secondary">Recipients</Text>
                <Text>{viewTarget.sent_count ?? '—'}</Text>
              </VStack>
            </HStack>
            <VStack gap={1}>
              <Text tone="secondary">Body Preview</Text>
              <pre className="overflow-auto max-h-64 text-xs font-mono aeos-glass rounded p-3">
                {viewTarget.body_html}
              </pre>
            </VStack>
          </VStack>
        )}
      </Modal>
    </IndexPageLayout>
  );
}

EmailBlasts.layout = page => <App title="Email Blasts">{page}</App>;
