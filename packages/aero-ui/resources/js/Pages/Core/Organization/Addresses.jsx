/**
 * Organization Addresses — Dynamic list of org addresses.
 *
 * Props: { addresses: [{ type, line1, line2, city, state, postal_code, country, is_primary }] }
 *
 * Add/remove address rows. Save all → POST core.organization.addresses.update
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Select, Checkbox, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ADDRESS_TYPE_OPTIONS = [
  { value: 'billing',  label: 'Billing'  },
  { value: 'shipping', label: 'Shipping' },
  { value: 'office',   label: 'Office'   },
  { value: 'other',    label: 'Other'    },
];

function emptyAddress() {
  return { type: 'office', line1: '', line2: '', city: '', state: '', postal_code: '', country: '', is_primary: false };
}

export default function OrgAddresses({ addresses = [] }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_addresses.manage');

  const { data, setData, post, processing, errors } = useForm({
    addresses: addresses.length > 0 ? addresses : [emptyAddress()],
  });

  const updateAddress = (idx, field, value) => {
    const next = [...data.addresses];
    next[idx] = { ...next[idx], [field]: value };
    setData('addresses', next);
  };

  const addRow    = () => setData('addresses', [...data.addresses, emptyAddress()]);
  const removeRow = (idx) => setData('addresses', data.addresses.filter((_, i) => i !== idx));

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.organization.addresses.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Addresses updated.'),
      onError:   () => toast.error('Failed to save addresses.'),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Organization Addresses"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Organization', href: route('core.organization.profile') },
          { label: 'Addresses' },
        ]}
        description="Manage billing, shipping, and office addresses for this tenant."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={addRow}>
                Add Address
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                Save All
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={4}>
          {data.addresses.length === 0 && (
            <Card>
              <CardBody>
                <Text tone="secondary">No addresses yet. Click "Add Address" to add one.</Text>
              </CardBody>
            </Card>
          )}

          {data.addresses.map((addr, idx) => (
            <Card key={idx}>
              <CardHeader>
                <HStack justify="between" align="center">
                  <Text weight="semibold">Address #{idx + 1}</Text>
                  <Button
                    type="button"
                    intent="danger"
                    size="sm"
                    onClick={() => removeRow(idx)}
                  >
                    Remove
                  </Button>
                </HStack>
              </CardHeader>
              <CardBody>
                <VStack gap={4}>
                  <HStack gap={4}>
                    <Field label="Type" error={errors[`addresses.${idx}.type`]}>
                      <Select
                        value={addr.type}
                        onChange={e => updateAddress(idx, 'type', e.target.value)}
                        options={ADDRESS_TYPE_OPTIONS}
                      />
                    </Field>

                    <Field label="Country (ISO 2)" error={errors[`addresses.${idx}.country`]}>
                      <Input
                        value={addr.country}
                        onChange={e => updateAddress(idx, 'country', e.target.value.toUpperCase())}
                        maxLength={2}
                        placeholder="US"
                      />
                    </Field>
                  </HStack>

                  <Field label="Address Line 1" error={errors[`addresses.${idx}.line1`]} required>
                    <Input
                      value={addr.line1}
                      onChange={e => updateAddress(idx, 'line1', e.target.value)}
                      placeholder="Street address"
                    />
                  </Field>

                  <Field label="Address Line 2" error={errors[`addresses.${idx}.line2`]}>
                    <Input
                      value={addr.line2}
                      onChange={e => updateAddress(idx, 'line2', e.target.value)}
                      placeholder="Apartment, suite, unit, etc."
                    />
                  </Field>

                  <HStack gap={4}>
                    <Field label="City" error={errors[`addresses.${idx}.city`]} required>
                      <Input
                        value={addr.city}
                        onChange={e => updateAddress(idx, 'city', e.target.value)}
                        placeholder="City"
                      />
                    </Field>

                    <Field label="State / Province" error={errors[`addresses.${idx}.state`]}>
                      <Input
                        value={addr.state}
                        onChange={e => updateAddress(idx, 'state', e.target.value)}
                        placeholder="State or province"
                      />
                    </Field>
                  </HStack>

                  <Field label="Postal Code" error={errors[`addresses.${idx}.postal_code`]}>
                    <Input
                      value={addr.postal_code}
                      onChange={e => updateAddress(idx, 'postal_code', e.target.value)}
                      placeholder="Postal / ZIP code"
                    />
                  </Field>

                  <Checkbox
                    label="Primary address"
                    checked={!!addr.is_primary}
                    onChange={e => updateAddress(idx, 'is_primary', e.target.checked)}
                  />
                </VStack>
              </CardBody>
            </Card>
          ))}
        </VStack>
      </FormPageLayout>
    </form>
  );
}

OrgAddresses.layout = page => <App title="Organization Addresses">{page}</App>;
