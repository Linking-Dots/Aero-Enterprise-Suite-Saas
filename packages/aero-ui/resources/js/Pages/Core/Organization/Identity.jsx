/**
 * Tax / Legal Identity — Tenant tax & registration details.
 *
 * Props: { org: { tax_id, vat_number, country, currency } }
 *
 * Tax ID is stored encrypted at rest via EncryptedField cast on
 * OrganizationProfile. Save → POST core.organization.identity.update
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader><Text weight="semibold">{title}</Text></CardHeader>
      <CardBody><VStack gap={4}>{children}</VStack></CardBody>
    </Card>
  );
}

export default function OrgIdentity({ org }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_identity.update');

  const { data, setData, post, processing, errors, reset } = useForm({
    tax_id:     org?.tax_id     ?? '',
    vat_number: org?.vat_number ?? '',
    country:    org?.country    ?? '',
    currency:   org?.currency   ?? '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.organization.identity.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Tax / legal identity updated.'),
      onError:   () => toast.error('Failed to update identity.'),
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Tax / Legal Identity"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Organization', href: route('core.organization.profile') },
          { label: 'Tax / Legal Identity' },
        ]}
        description="Tax ID is stored encrypted at rest."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={() => reset()} disabled={processing}>
                Reset
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                Save Changes
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={6}>
          <Section title="Identity">
            <Field label="Tax ID / EIN" error={errors.tax_id} hint="Stored encrypted">
              <Input
                type="password"
                value={data.tax_id}
                onChange={e => setData('tax_id', e.target.value)}
                placeholder="Encrypted storage"
              />
            </Field>

            <Field label="VAT Number" error={errors.vat_number}>
              <Input
                value={data.vat_number}
                onChange={e => setData('vat_number', e.target.value)}
                placeholder="VAT registration"
                maxLength={50}
              />
            </Field>

            <HStack gap={4}>
              <Field label="Country (ISO 2)" error={errors.country}>
                <Input
                  value={data.country}
                  onChange={e => setData('country', e.target.value.toUpperCase())}
                  placeholder="US"
                  maxLength={2}
                />
              </Field>

              <Field label="Currency (ISO 3)" error={errors.currency}>
                <Input
                  value={data.currency}
                  onChange={e => setData('currency', e.target.value.toUpperCase())}
                  placeholder="USD"
                  maxLength={3}
                />
              </Field>
            </HStack>
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

OrgIdentity.layout = page => <App title="Tax / Legal Identity">{page}</App>;
