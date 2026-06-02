import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Textarea, Button, Eyebrow, Alert,
} from '@aero/ui';
import { useHRMAC } from '@/hooks/useHRMAC';

const STATUS_OPTIONS = [
  { value: 'available',   label: 'Available' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'retired',     label: 'Retired' },
];

export default function AssetsCreate({ categories }) {
  const canCreate = useHRMAC('hrm.assets.asset-inventory.create');
  const categoryOptions = [
    { value: '', label: 'Select category' },
    ...(categories ?? []).map(c => ({ value: String(c.id), label: c.name })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    tag:            '',
    name:           '',
    category_id:    '',
    serial_number:  '',
    vendor:         '',
    purchased_on:   '',
    purchase_cost:  '',
    status:         'available',
    notes:          '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.assets.store'));
  }

  return (
    <FormPageLayout
      title="New Asset"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Assets' },
        { label: 'Inventory', href: route('hrm.assets.index') },
        { label: 'New Asset' },
      ]}
      actions={
        <HStack gap={2}>
          <Button type="button" intent="ghost" onClick={() => router.get(route('hrm.assets.index'))}>
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} disabled={!canCreate || processing} onClick={submit}>
            Create Asset
          </Button>
        </HStack>
      }
    >
      {!canCreate && (
        <Alert intent="warning" title="You do not have permission to create assets." />
      )}
      <form onSubmit={submit}>
        <VStack gap={6}>

          <VStack gap={4}>
            <Eyebrow>Identification</Eyebrow>

            <HStack gap={3}>
              <Field label="Asset Tag" error={errors.tag} required>
                <Input
                  value={data.tag}
                  onChange={e => setData('tag', e.target.value)}
                  placeholder="e.g. ASSET-001"
                />
              </Field>

              <Field label="Name" error={errors.name} required>
                <Input
                  value={data.name}
                  onChange={e => setData('name', e.target.value)}
                  placeholder="e.g. MacBook Pro 14-inch"
                />
              </Field>
            </HStack>

            <HStack gap={3}>
              <Field label="Category" error={errors.category_id} required>
                <Select
                  options={categoryOptions}
                  value={data.category_id}
                  onChange={e => setData('category_id', e.target.value)}
                />
              </Field>

              <Field label="Status" error={errors.status}>
                <Select
                  options={STATUS_OPTIONS}
                  value={data.status}
                  onChange={e => setData('status', e.target.value)}
                />
              </Field>
            </HStack>

            <Field label="Serial Number" error={errors.serial_number} hint="Optional">
              <Input
                value={data.serial_number}
                onChange={e => setData('serial_number', e.target.value)}
                placeholder="e.g. SN123456789"
              />
            </Field>
          </VStack>

          <VStack gap={4}>
            <Eyebrow>Purchase Details</Eyebrow>

            <HStack gap={3}>
              <Field label="Vendor" error={errors.vendor} hint="Optional">
                <Input
                  value={data.vendor}
                  onChange={e => setData('vendor', e.target.value)}
                  placeholder="e.g. Apple Inc."
                />
              </Field>

              <Field label="Purchased On" error={errors.purchased_on} hint="Optional">
                <Input
                  type="date"
                  value={data.purchased_on}
                  onChange={e => setData('purchased_on', e.target.value)}
                />
              </Field>

              <Field label="Purchase Cost" error={errors.purchase_cost} hint="Optional">
                <Input
                  type="number"
                  value={data.purchase_cost}
                  onChange={e => setData('purchase_cost', e.target.value)}
                  placeholder="0.00"
                />
              </Field>
            </HStack>
          </VStack>

          <VStack gap={4}>
            <Eyebrow>Notes</Eyebrow>

            <Field label="Notes" error={errors.notes} hint="Optional">
              <Textarea
                value={data.notes}
                onChange={e => setData('notes', e.target.value)}
                placeholder="Any additional information about this asset..."
                rows={3}
              />
            </Field>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

AssetsCreate.layout = page => <App title="New Asset">{page}</App>;
