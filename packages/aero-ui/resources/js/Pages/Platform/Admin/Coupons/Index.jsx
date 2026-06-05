import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Modal,
  Drawer,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',         label: 'All Statuses' },
  { value: 'active',   label: 'Active' },
  { value: 'archived', label: 'Archived' },
];

const TYPE_OPTIONS = [
  { value: 'percent', label: 'Percent (%)' },
  { value: 'fixed',   label: 'Fixed Amount' },
];

const DURATION_OPTIONS = [
  { value: 'once',       label: 'Once' },
  { value: 'repeating',  label: 'Repeating' },
  { value: 'forever',    label: 'Forever' },
];

const STATUS_INTENT = {
  active:   'success',
  archived: 'neutral',
};

export default function CouponsIndex({ coupons, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('coupons-promotions.coupons.create');
  const canEdit    = useHRMAC('coupons-promotions.coupons.update');
  const canArchive = useHRMAC('coupons-promotions.coupons.archive');
  const canBulk    = useHRMAC('coupons-promotions.coupons.bulk-generate');

  const [statusFilter,  setStatusFilter]  = useState(filters?.status ?? '');
  const [drawerOpen,    setDrawerOpen]    = useState(false);
  const [editTarget,    setEditTarget]    = useState(null);
  const [archiveTarget, setArchiveTarget] = useState(null);
  const [archiving,     setArchiving]     = useState(false);
  const [bulkOpen,      setBulkOpen]      = useState(false);

  const form = useForm({
    code:       '',
    name:       '',
    type:       'percent',
    value:      '',
    currency:   'USD',
    duration:   'once',
    duration_months: '',
    max_redemptions: '',
    expires_at: '',
    applies_to: '',
  });

  const bulkForm = useForm({
    prefix:   '',
    count:    10,
    type:     'percent',
    value:    '',
    max_redemptions: '',
    expires_at: '',
  });

  function applyFilters() {
    router.get(
      route('platform.admin.coupons.index'),
      { status: statusFilter },
      { preserveState: true, preserveScroll: true, only: ['coupons', 'filters'] }
    );
  }

  function resetFilters() {
    setStatusFilter('');
    router.get(route('platform.admin.coupons.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['coupons', 'filters'],
    });
  }

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(coupon) {
    setEditTarget(coupon);
    form.setData({
      code:       coupon.code,
      name:       coupon.name,
      type:       coupon.type,
      value:      String(coupon.value),
      currency:   coupon.currency ?? 'USD',
      duration:   coupon.duration,
      duration_months: coupon.duration_months ? String(coupon.duration_months) : '',
      max_redemptions: coupon.max_redemptions ? String(coupon.max_redemptions) : '',
      expires_at: coupon.expires_at ?? '',
      applies_to: coupon.applies_to ?? '',
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.coupons.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Coupon updated.'); setDrawerOpen(false); },
        onError: () => toast.error('Failed to update coupon.'),
      });
    } else {
      form.post(route('platform.admin.coupons.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Coupon created.'); setDrawerOpen(false); form.reset(); },
        onError: () => toast.error('Failed to create coupon.'),
      });
    }
  }

  function confirmArchive() {
    if (!archiveTarget) return;
    setArchiving(true);
    router.post(
      route('platform.admin.coupons.archive', archiveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Coupon archived.'); setArchiveTarget(null); },
        onError: () => toast.error('Failed to archive coupon.'),
        onFinish: () => setArchiving(false),
      }
    );
  }

  function handleBulkGenerate(e) {
    e.preventDefault();
    bulkForm.post(route('platform.admin.coupons.bulk-generate'), {
      preserveState: true,
      onSuccess: () => { toast.success('Bulk codes generated.'); setBulkOpen(false); bulkForm.reset(); },
      onError: () => toast.error('Failed to generate codes.'),
    });
  }

  const columns = [
    {
      key: 'code',
      label: 'Code',
      render: (row) => <Mono>{row.code}</Mono>,
    },
    {
      key: 'name',
      label: 'Name',
      render: (row) => <Text>{row.name}</Text>,
    },
    {
      key: 'type',
      label: 'Type / Value',
      render: (row) => (
        <Mono>
          {row.type === 'percent'
            ? `${row.value}%`
            : `$${Number(row.value).toFixed(2)} ${row.currency ?? 'USD'}`}
        </Mono>
      ),
    },
    {
      key: 'redemptions',
      label: 'Redemptions',
      render: (row) => (
        <Text>
          {row.redemption_count ?? 0}
          {row.max_redemptions ? ` / ${row.max_redemptions}` : ' / ∞'}
        </Text>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'}>{row.status}</Badge>
      ),
    },
    {
      key: 'expires_at',
      label: 'Expires',
      render: (row) => (
        <Mono>{row.expires_at ? new Date(row.expires_at).toLocaleDateString() : '—'}</Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canEdit && row.status === 'active' && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {canArchive && row.status === 'active' && (
            <Button intent="danger" size="sm" onClick={() => setArchiveTarget(row)}>
              Archive
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Coupons"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Coupons' },
      ]}
      description="Manage discount coupons and promotional codes."
      actions={
        <HStack gap={2}>
          {canBulk && (
            <Button intent="soft" leftIcon="zap" onClick={() => setBulkOpen(true)}>
              Bulk Generate
            </Button>
          )}
          {canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={openCreate}>
              New Coupon
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>
        <HStack gap={3}>
          <Select
            value={statusFilter}
            onChange={e => setStatusFilter(e.target.value)}
            options={STATUS_OPTIONS}
          />
          <Button intent="soft" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost" onClick={resetFilters}>Reset</Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={coupons?.data ?? []}
          empty="No coupons found."
        />

        {(coupons?.last_page ?? 1) > 1 && (
          <Pagination
            page={coupons.current_page}
            total={coupons.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.coupons.index'),
                { page, status: statusFilter },
                { preserveState: true, preserveScroll: true, only: ['coupons'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Coupon' : 'New Coupon'}
      >
        <VStack gap={4}>
          <Field label="Coupon Code" htmlFor="cp_code" error={form.errors.code} required>
            <Input
              id="cp_code"
              value={form.data.code}
              onChange={e => form.setData('code', e.target.value)}
              placeholder="SUMMER25"
              error={form.errors.code}
            />
          </Field>

          <Field label="Name" htmlFor="cp_name" error={form.errors.name} required>
            <Input
              id="cp_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Summer Sale 25%"
              error={form.errors.name}
            />
          </Field>

          <Field label="Discount Type" htmlFor="cp_type" error={form.errors.type} required>
            <Select
              id="cp_type"
              value={form.data.type}
              onChange={e => form.setData('type', e.target.value)}
              options={TYPE_OPTIONS}
            />
          </Field>

          <Field label="Value" htmlFor="cp_value" error={form.errors.value} required>
            <Input
              id="cp_value"
              type="number"
              value={form.data.value}
              onChange={e => form.setData('value', e.target.value)}
              placeholder={form.data.type === 'percent' ? '25' : '10.00'}
              error={form.errors.value}
            />
          </Field>

          {form.data.type === 'fixed' && (
            <Field label="Currency" htmlFor="cp_currency" error={form.errors.currency}>
              <Input
                id="cp_currency"
                value={form.data.currency}
                onChange={e => form.setData('currency', e.target.value)}
                placeholder="USD"
                error={form.errors.currency}
              />
            </Field>
          )}

          <Field label="Duration" htmlFor="cp_duration" error={form.errors.duration} required>
            <Select
              id="cp_duration"
              value={form.data.duration}
              onChange={e => form.setData('duration', e.target.value)}
              options={DURATION_OPTIONS}
            />
          </Field>

          {form.data.duration === 'repeating' && (
            <Field label="Duration (months)" htmlFor="cp_dur_months" error={form.errors.duration_months}>
              <Input
                id="cp_dur_months"
                type="number"
                value={form.data.duration_months}
                onChange={e => form.setData('duration_months', e.target.value)}
                placeholder="3"
                error={form.errors.duration_months}
              />
            </Field>
          )}

          <Field label="Max Redemptions" htmlFor="cp_max" error={form.errors.max_redemptions} hint="Leave blank for unlimited.">
            <Input
              id="cp_max"
              type="number"
              value={form.data.max_redemptions}
              onChange={e => form.setData('max_redemptions', e.target.value)}
              placeholder="Unlimited"
              error={form.errors.max_redemptions}
            />
          </Field>

          <Field label="Expires At" htmlFor="cp_expires" error={form.errors.expires_at}>
            <Input
              id="cp_expires"
              type="datetime-local"
              value={form.data.expires_at}
              onChange={e => form.setData('expires_at', e.target.value)}
              error={form.errors.expires_at}
            />
          </Field>

          <Field label="Applies To" htmlFor="cp_applies" error={form.errors.applies_to} hint="Plan code or product code to restrict coupon scope. Leave blank for any.">
            <Input
              id="cp_applies"
              value={form.data.applies_to}
              onChange={e => form.setData('applies_to', e.target.value)}
              placeholder="all"
              error={form.errors.applies_to}
            />
          </Field>

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update' : 'Create'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Archive Confirm Modal */}
      <Modal
        open={!!archiveTarget}
        onClose={() => setArchiveTarget(null)}
        title="Archive Coupon"
        description={`Archive coupon "${archiveTarget?.code}"? It will no longer be redeemable.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={archiving} disabled={archiving} onClick={confirmArchive}>
              Archive
            </Button>
            <Button intent="ghost" onClick={() => setArchiveTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />

      {/* Bulk Generate Modal */}
      <Modal
        open={bulkOpen}
        onClose={() => { setBulkOpen(false); bulkForm.reset(); }}
        title="Bulk Generate Coupon Codes"
        size="md"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={bulkForm.processing}
              disabled={bulkForm.processing}
              onClick={handleBulkGenerate}
            >
              Generate
            </Button>
            <Button intent="ghost" onClick={() => { setBulkOpen(false); bulkForm.reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Code Prefix" htmlFor="bk_prefix" error={bulkForm.errors.prefix} required>
            <Input
              id="bk_prefix"
              value={bulkForm.data.prefix}
              onChange={e => bulkForm.setData('prefix', e.target.value)}
              placeholder="PROMO"
              error={bulkForm.errors.prefix}
            />
          </Field>

          <Field label="Count" htmlFor="bk_count" error={bulkForm.errors.count} required>
            <Input
              id="bk_count"
              type="number"
              value={bulkForm.data.count}
              onChange={e => bulkForm.setData('count', Number(e.target.value))}
              placeholder="10"
              error={bulkForm.errors.count}
            />
          </Field>

          <Field label="Discount Type" htmlFor="bk_type" error={bulkForm.errors.type} required>
            <Select
              id="bk_type"
              value={bulkForm.data.type}
              onChange={e => bulkForm.setData('type', e.target.value)}
              options={TYPE_OPTIONS}
            />
          </Field>

          <Field label="Value" htmlFor="bk_value" error={bulkForm.errors.value} required>
            <Input
              id="bk_value"
              type="number"
              value={bulkForm.data.value}
              onChange={e => bulkForm.setData('value', e.target.value)}
              placeholder={bulkForm.data.type === 'percent' ? '10' : '5.00'}
              error={bulkForm.errors.value}
            />
          </Field>

          <Field label="Max Redemptions per Code" htmlFor="bk_max" error={bulkForm.errors.max_redemptions} hint="Leave blank for unlimited.">
            <Input
              id="bk_max"
              type="number"
              value={bulkForm.data.max_redemptions}
              onChange={e => bulkForm.setData('max_redemptions', e.target.value)}
              placeholder="Unlimited"
              error={bulkForm.errors.max_redemptions}
            />
          </Field>

          <Field label="Expires At" htmlFor="bk_expires" error={bulkForm.errors.expires_at}>
            <Input
              id="bk_expires"
              type="datetime-local"
              value={bulkForm.data.expires_at}
              onChange={e => bulkForm.setData('expires_at', e.target.value)}
              error={bulkForm.errors.expires_at}
            />
          </Field>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

CouponsIndex.layout = page => <App title="Coupons">{page}</App>;
