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
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TYPE_OPTIONS = [
  { value: 'vat',         label: 'VAT' },
  { value: 'gst',         label: 'GST' },
  { value: 'sales_tax',   label: 'Sales Tax' },
  { value: 'withholding', label: 'Withholding' },
];

const TYPE_INTENT = {
  vat:         'primary',
  gst:         'info',
  sales_tax:   'warning',
  withholding: 'neutral',
};

export default function TaxRates({ rates }) {
  const toast     = useToast();
  const canManage = useHRMAC('tax-engine.tax-rates.manage');

  const [drawerOpen,   setDrawerOpen]   = useState(false);
  const [editTarget,   setEditTarget]   = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleting,     setDeleting]     = useState(false);

  const form = useForm({
    country_code: '',
    region_code:  '',
    name:         '',
    rate:         '',
    type:         'vat',
    is_active:    true,
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(rate) {
    setEditTarget(rate);
    form.setData({
      country_code: rate.country_code,
      region_code:  rate.region_code ?? '',
      name:         rate.name,
      rate:         String(Number(rate.rate) * 100),
      type:         rate.type,
      is_active:    rate.is_active,
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    const payload = {
      ...form.data,
      rate: Number(form.data.rate) / 100,
    };
    if (editTarget) {
      router.put(
        route('platform.admin.tax.rates.update', editTarget.id),
        payload,
        {
          preserveState: true,
          onSuccess: () => { toast.success('Tax rate updated.'); setDrawerOpen(false); },
          onError:   () => toast.error('Failed to update tax rate.'),
        }
      );
    } else {
      router.post(
        route('platform.admin.tax.rates.store'),
        payload,
        {
          preserveState: true,
          onSuccess: () => { toast.success('Tax rate created.'); setDrawerOpen(false); form.reset(); },
          onError:   () => toast.error('Failed to create tax rate.'),
        }
      );
    }
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    setDeleting(true);
    router.delete(
      route('platform.admin.tax.rates.destroy', deleteTarget.id),
      {
        preserveState: true,
        onSuccess: () => { toast.success('Tax rate deleted.'); setDeleteTarget(null); },
        onError:   () => toast.error('Failed to delete tax rate.'),
        onFinish:  () => setDeleting(false),
      }
    );
  }

  const columns = [
    {
      key: 'country_code',
      label: 'Country',
      render: (row) => <Mono>{row.country_code}</Mono>,
    },
    {
      key: 'region_code',
      label: 'Region',
      render: (row) => <Mono tone="secondary">{row.region_code ?? '—'}</Mono>,
    },
    {
      key: 'name',
      label: 'Name',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'type',
      label: 'Type',
      render: (row) => (
        <Badge intent={TYPE_INTENT[row.type] ?? 'neutral'}>
          {TYPE_OPTIONS.find(o => o.value === row.type)?.label ?? row.type}
        </Badge>
      ),
    },
    {
      key: 'rate',
      label: 'Rate %',
      render: (row) => <Mono>{(Number(row.rate) * 100).toFixed(2)}%</Mono>,
    },
    {
      key: 'is_active',
      label: 'Status',
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
        canManage && (
          <HStack gap={1}>
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
            <Button intent="danger" size="sm" onClick={() => setDeleteTarget(row)}>
              Delete
            </Button>
          </HStack>
        ),
    },
  ];

  return (
    <IndexPageLayout
      title="Tax Rates"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tax Rates' },
      ]}
      description="Regional tax rates applied to invoices by country and type."
      actions={
        canManage && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            Add Rate
          </Button>
        )
      }
    >
      <DataTable
        columns={columns}
        rows={rates ?? []}
        empty="No tax rates configured. Add a rate to get started."
      />

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Tax Rate' : 'New Tax Rate'}
      >
        <VStack gap={4}>
          <HStack gap={3}>
            <Field label="Country Code" htmlFor="tr_country" error={form.errors.country_code} required hint="ISO 3166-1 alpha-2 (e.g. US, GB, DE)">
              <Input
                id="tr_country"
                value={form.data.country_code}
                onChange={e => form.setData('country_code', e.target.value.toUpperCase())}
                placeholder="US"
                error={form.errors.country_code}
              />
            </Field>
            <Field label="Region Code" htmlFor="tr_region" error={form.errors.region_code} hint="State/province code (optional)">
              <Input
                id="tr_region"
                value={form.data.region_code}
                onChange={e => form.setData('region_code', e.target.value.toUpperCase())}
                placeholder="CA"
                error={form.errors.region_code}
              />
            </Field>
          </HStack>

          <Field label="Name" htmlFor="tr_name" error={form.errors.name} required>
            <Input
              id="tr_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="VAT"
              error={form.errors.name}
            />
          </Field>

          <Field label="Type" htmlFor="tr_type" error={form.errors.type} required>
            <Select
              id="tr_type"
              value={form.data.type}
              onChange={e => form.setData('type', e.target.value)}
              options={TYPE_OPTIONS}
            />
          </Field>

          <Field label="Rate %" htmlFor="tr_rate" error={form.errors.rate} required hint="Enter as percentage (e.g. 20 for 20%)">
            <Input
              id="tr_rate"
              type="number"
              value={form.data.rate}
              onChange={e => form.setData('rate', e.target.value)}
              placeholder="20"
              error={form.errors.rate}
            />
          </Field>

          <Toggle
            label="Active"
            checked={form.data.is_active}
            onChange={e => form.setData('is_active', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update Rate' : 'Create Rate'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Tax Rate"
        description={`Delete the ${deleteTarget?.name} rate for ${deleteTarget?.country_code}${deleteTarget?.region_code ? `/${deleteTarget.region_code}` : ''}? This cannot be undone.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={deleting} disabled={deleting} onClick={confirmDelete}>
              Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

TaxRates.layout = page => <App title="Tax Rates">{page}</App>;
