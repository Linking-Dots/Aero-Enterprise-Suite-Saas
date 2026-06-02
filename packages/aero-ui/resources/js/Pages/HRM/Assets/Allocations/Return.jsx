import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  FormPageLayout, VStack, HStack, Field, Select, Textarea, Button,
  Card, CardBody, Eyebrow, Text, Alert,
} from '@aero/ui';
import { useHRMAC } from '@/hooks/useHRMAC';

const CONDITION_OPTIONS = [
  { value: 'good', label: 'Good' },
  { value: 'fair', label: 'Fair' },
  { value: 'poor', label: 'Poor' },
];

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function AllocationsReturn({ allocation }) {
  const canReturn = useHRMAC('hrm.assets.asset-allocations.return');
  const { data, setData, post, processing, errors } = useForm({
    condition:    'good',
    return_notes: '',
  });

  function submit(e) {
    e.preventDefault();
    post(route('hrm.assets.allocations.return', allocation.id));
  }

  return (
    <FormPageLayout
      title="Return Asset"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Assets' },
        { label: 'Allocations', href: route('hrm.assets.allocations.index') },
        { label: 'Return' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.assets.show', allocation.asset?.id))}
          >
            Cancel
          </Button>
          <Button type="submit" intent="primary" loading={processing} disabled={!canReturn || processing} onClick={submit}>
            Confirm Return
          </Button>
        </HStack>
      }
    >
      {!canReturn && (
        <Alert intent="warning" title="You do not have permission to return assets." />
      )}
      <form onSubmit={submit}>
        <VStack gap={6}>

          {/* Allocation summary */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Allocation Details</Eyebrow>
                <HStack gap={6} wrap>
                  <VStack gap={1}>
                    <Text tone="secondary">Asset Tag</Text>
                    <Text>{allocation.asset?.tag ?? '—'}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Asset Name</Text>
                    <Text>{allocation.asset?.name ?? '—'}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Employee</Text>
                    <Text>{allocation.employee?.user?.name ?? '—'}</Text>
                  </VStack>
                  <VStack gap={1}>
                    <Text tone="secondary">Allocated At</Text>
                    <Text>{fmtDate(allocation.allocated_at)}</Text>
                  </VStack>
                </HStack>
              </VStack>
            </CardBody>
          </Card>

          {/* Return form */}
          <VStack gap={4}>
            <Eyebrow>Return Information</Eyebrow>

            <Field label="Condition on Return" error={errors.condition}>
              <Select
                options={CONDITION_OPTIONS}
                value={data.condition}
                onChange={e => setData('condition', e.target.value)}
              />
            </Field>

            <Field label="Return Notes" error={errors.return_notes} hint="Optional">
              <Textarea
                value={data.return_notes}
                onChange={e => setData('return_notes', e.target.value)}
                placeholder="Any notes about the condition or return circumstances..."
                rows={3}
              />
            </Field>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

AllocationsReturn.layout = page => <App title="Return Asset">{page}</App>;
