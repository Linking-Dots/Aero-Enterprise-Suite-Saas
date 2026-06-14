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
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const AGGREGATION_OPTIONS = [
  { value: 'count', label: 'Count' },
  { value: 'sum',   label: 'Sum' },
  { value: 'max',   label: 'Max' },
];

const RESET_PERIOD_OPTIONS = [
  { value: 'monthly',   label: 'Monthly' },
  { value: 'yearly',    label: 'Yearly' },
  { value: 'never',     label: 'Never' },
];

export default function Meters({ meters, filters }) {
  const toast     = useToast();
  const canCreate = useHRMAC('addons-metered.metered-meters.create');
  const canConfig = useHRMAC('addons-metered.metered-meters.configure');

  const [drawerOpen,   setDrawerOpen]   = useState(false);
  const [editTarget,   setEditTarget]   = useState(null);
  const [toggleTarget, setToggleTarget] = useState(null);
  const [toggling,     setToggling]     = useState(false);

  const form = useForm({
    code:           '',
    name:           '',
    event_code:     '',
    aggregation:    'count',
    price_per_unit: '',
    unit_label:     'unit',
    reset_period:   'monthly',
    is_active:      true,
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(meter) {
    setEditTarget(meter);
    form.setData({
      code:           meter.code,
      name:           meter.name,
      event_code:     meter.event_code,
      aggregation:    meter.aggregation,
      price_per_unit: String(meter.price_per_unit),
      unit_label:     meter.unit_label,
      reset_period:   meter.reset_period ?? 'monthly',
      is_active:      meter.is_active,
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.usage.meters.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Meter updated.'); setDrawerOpen(false); },
        onError: () => toast.error('Failed to update meter.'),
      });
    } else {
      form.post(route('platform.admin.usage.meters.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Meter created.'); setDrawerOpen(false); form.reset(); },
        onError: () => toast.error('Failed to create meter.'),
      });
    }
  }

  function confirmToggle() {
    if (!toggleTarget) return;
    setToggling(true);
    router.put(
      route('platform.admin.usage.meters.update', toggleTarget.id),
      { ...toggleTarget, is_active: !toggleTarget.is_active },
      {
        preserveState: true,
        onSuccess: () => {
          toast.success(`Meter ${toggleTarget.is_active ? 'deactivated' : 'activated'}.`);
          setToggleTarget(null);
        },
        onError: () => toast.error('Failed to toggle meter.'),
        onFinish: () => setToggling(false),
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
      key: 'event_code',
      label: 'Event',
      render: (row) => <Mono>{row.event_code}</Mono>,
    },
    {
      key: 'aggregation',
      label: 'Aggregation',
      render: (row) => <Badge intent="neutral">{row.aggregation}</Badge>,
    },
    {
      key: 'price_per_unit',
      label: 'Price / Unit',
      render: (row) => <Mono>${Number(row.price_per_unit).toFixed(6)} / {row.unit_label}</Mono>,
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
      render: (row) => (
        <HStack gap={1}>
          {canConfig && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>
              Configure
            </Button>
          )}
          {canConfig && (
            <Button
              intent={row.is_active ? 'danger' : 'soft'}
              size="sm"
              onClick={() => setToggleTarget(row)}
            >
              {row.is_active ? 'Deactivate' : 'Activate'}
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Usage Meters"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Add-ons', href: route('platform.admin.addons.index') },
        { label: 'Usage Meters' },
      ]}
      description="Define and configure usage meters for metered billing."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Meter
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={meters?.data ?? []}
          empty="No usage meters defined."
        />

        {(meters?.last_page ?? 1) > 1 && (
          <Pagination
            page={meters.current_page}
            total={meters.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.usage.meters.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['meters'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create / Configure Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Configure Meter' : 'New Usage Meter'}
      >
        <VStack gap={4}>
          <Field label="Code" htmlFor="mt_code" error={form.errors.code} required hint="Unique slug, e.g. api-calls">
            <Input
              id="mt_code"
              value={form.data.code}
              onChange={e => form.setData('code', e.target.value)}
              placeholder="api-calls"
              error={form.errors.code}
            />
          </Field>

          <Field label="Name" htmlFor="mt_name" error={form.errors.name} required>
            <Input
              id="mt_name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="API Calls"
              error={form.errors.name}
            />
          </Field>

          <Field label="Event Code" htmlFor="mt_event" error={form.errors.event_code} required hint="The event name this meter listens for.">
            <Input
              id="mt_event"
              value={form.data.event_code}
              onChange={e => form.setData('event_code', e.target.value)}
              placeholder="api.request"
              error={form.errors.event_code}
            />
          </Field>

          <Field label="Aggregation" htmlFor="mt_agg" error={form.errors.aggregation} required>
            <Select
              id="mt_agg"
              value={form.data.aggregation}
              onChange={e => form.setData('aggregation', e.target.value)}
              options={AGGREGATION_OPTIONS}
            />
          </Field>

          <Field label="Price Per Unit" htmlFor="mt_price" error={form.errors.price_per_unit} required>
            <Input
              id="mt_price"
              type="number"
              value={form.data.price_per_unit}
              onChange={e => form.setData('price_per_unit', e.target.value)}
              placeholder="0.001"
              leftIcon="dollar"
              error={form.errors.price_per_unit}
            />
          </Field>

          <Field label="Unit Label" htmlFor="mt_unit" error={form.errors.unit_label} required>
            <Input
              id="mt_unit"
              value={form.data.unit_label}
              onChange={e => form.setData('unit_label', e.target.value)}
              placeholder="request"
              error={form.errors.unit_label}
            />
          </Field>

          <Field label="Reset Period" htmlFor="mt_reset" error={form.errors.reset_period} required>
            <Select
              id="mt_reset"
              value={form.data.reset_period}
              onChange={e => form.setData('reset_period', e.target.value)}
              options={RESET_PERIOD_OPTIONS}
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
              {editTarget ? 'Save Changes' : 'Create Meter'}
            </Button>
            <Button intent="ghost" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Toggle Active Confirm Modal */}
      <Modal
        open={!!toggleTarget}
        onClose={() => setToggleTarget(null)}
        title={toggleTarget?.is_active ? 'Deactivate Meter' : 'Activate Meter'}
        description={
          toggleTarget?.is_active
            ? `Deactivate meter "${toggleTarget?.name}"? New usage events for this meter will no longer be recorded.`
            : `Activate meter "${toggleTarget?.name}"? It will start recording usage events again.`
        }
        footer={
          <HStack gap={2}>
            <Button
              intent={toggleTarget?.is_active ? 'danger' : 'primary'}
              loading={toggling}
              disabled={toggling}
              onClick={confirmToggle}
            >
              {toggleTarget?.is_active ? 'Deactivate' : 'Activate'}
            </Button>
            <Button intent="ghost" onClick={() => setToggleTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Meters.layout = page => <App title="Usage Meters">{page}</App>;
