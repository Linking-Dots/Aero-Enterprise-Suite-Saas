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
  Toggle,
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

const PERIOD_OPTIONS = [
  { value: 'monthly',  label: 'Monthly' },
  { value: 'yearly',   label: 'Yearly' },
  { value: 'one_time', label: 'One-time' },
];

const STATUS_INTENT = {
  active:   'success',
  archived: 'neutral',
};

export default function AddonsIndex({ addons, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('addons-metered.addons.create');
  const canEdit    = useHRMAC('addons-metered.addons.update');
  const canArchive = useHRMAC('addons-metered.addons.archive');

  const [statusFilter,  setStatusFilter]  = useState(filters?.status ?? '');
  const [drawerOpen,    setDrawerOpen]    = useState(false);
  const [editTarget,    setEditTarget]    = useState(null);
  const [archiveTarget, setArchiveTarget] = useState(null);
  const [archiving,     setArchiving]     = useState(false);

  const form = useForm({
    code:           '',
    name:           '',
    description:    '',
    price:          '',
    billing_period: 'monthly',
  });

  function applyFilters() {
    router.get(
      route('platform.admin.addons.index'),
      { status: statusFilter },
      { preserveState: true, preserveScroll: true, only: ['addons', 'filters'] }
    );
  }

  function resetFilters() {
    setStatusFilter('');
    router.get(route('platform.admin.addons.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['addons', 'filters'],
    });
  }

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(addon) {
    setEditTarget(addon);
    form.setData({
      code:           addon.code,
      name:           addon.name,
      description:    addon.description ?? '',
      price:          String(addon.price),
      billing_period: addon.billing_period,
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.addons.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Add-on updated.'); setDrawerOpen(false); },
        onError: () => toast.error('Failed to update add-on.'),
      });
    } else {
      form.post(route('platform.admin.addons.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Add-on created.'); setDrawerOpen(false); form.reset(); },
        onError: () => toast.error('Failed to create add-on.'),
      });
    }
  }

  function confirmArchive() {
    if (!archiveTarget) return;
    setArchiving(true);
    router.post(
      route('platform.admin.addons.archive', archiveTarget.id),
      {},
      {
        preserveState: true,
        onSuccess: () => { toast.success('Add-on archived.'); setArchiveTarget(null); },
        onError: () => toast.error('Failed to archive add-on.'),
        onFinish: () => setArchiving(false),
      }
    );
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
      key: 'price',
      label: 'Price / Period',
      render: (row) => (
        <Mono>
          ${Number(row.price).toFixed(2)} / {row.billing_period}
        </Mono>
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
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canEdit && row.status === 'active' && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.usage.meters.index'))}
          >
            Meters
          </Button>
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
      title="Add-ons"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Add-ons' },
      ]}
      description="Manage billable add-ons that tenants can purchase on top of their subscription."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Add-on
          </Button>
        )
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
          rows={addons?.data ?? []}
          empty="No add-ons found."
        />

        {(addons?.last_page ?? 1) > 1 && (
          <Pagination
            page={addons.current_page}
            total={addons.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.addons.index'),
                { page, status: statusFilter },
                { preserveState: true, preserveScroll: true, only: ['addons'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Add-on' : 'New Add-on'}
      >
        <VStack gap={4}>
          <Field label="Code" htmlFor="ao_code" error={form.errors.code} required hint="Unique identifier, e.g. extra-storage">
            <Input
              id="ao_code"
              value={form.data.code}
              onChange={e => form.setData('code', e.target.value)}
              placeholder="extra-storage"
              error={form.errors.code}
            />
          </Field>

          <Field label="Name" htmlFor="ao_name" error={form.errors.name} required>
            <Input
              id="ao_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="Extra Storage (50 GB)"
              error={form.errors.name}
            />
          </Field>

          <Field label="Description" htmlFor="ao_desc" error={form.errors.description}>
            <Input
              id="ao_desc"
              value={form.data.description}
              onChange={e => form.setData('description', e.target.value)}
              placeholder="Optional description"
              error={form.errors.description}
            />
          </Field>

          <Field label="Price" htmlFor="ao_price" error={form.errors.price} required>
            <Input
              id="ao_price"
              type="number"
              value={form.data.price}
              onChange={e => form.setData('price', e.target.value)}
              placeholder="9.99"
              leftIcon="dollar"
              error={form.errors.price}
            />
          </Field>

          <Field label="Billing Period" htmlFor="ao_period" error={form.errors.billing_period} required>
            <Select
              id="ao_period"
              value={form.data.billing_period}
              onChange={e => form.setData('billing_period', e.target.value)}
              options={PERIOD_OPTIONS}
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
        title="Archive Add-on"
        description={`Archive add-on "${archiveTarget?.name}"? Existing subscriptions to this add-on will not be affected.`}
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
    </IndexPageLayout>
  );
}

AddonsIndex.layout = page => <App title="Add-ons">{page}</App>;
