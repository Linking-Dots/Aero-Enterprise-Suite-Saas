/**
 * Tax / Legal Identity — Organization shell section.
 *
 * Props: { org: { tax_id, vat_number, country, currency } }
 *
 * Tax ID is stored encrypted at rest via EncryptedField cast on
 * OrganizationProfile. Save → POST core.organization.identity.update
 */
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Card, CardHeader, CardBody, HStack, VStack, Text,
  useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import OrganizationLayout from './OrganizationLayout.jsx';
import OrganizationRail from './OrganizationRail.jsx';
import OrganizationSection from './OrganizationSection.jsx';

export default function OrgIdentity({ org }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_identity.update');

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    tax_id:     org?.tax_id     ?? '',
    vat_number: org?.vat_number ?? '',
    country:    org?.country    ?? '',
    currency:   org?.currency   ?? '',
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.organization.identity.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Tax / legal identity updated.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <OrganizationSection
        title="Tax / Legal Identity"
        description="Tax ID is stored encrypted at rest."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Identity</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
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
            </VStack>
          </CardBody>
        </Card>
      </OrganizationSection>
    </form>
  );
}

OrgIdentity.layout = page => (
  <App title="Organization" railTitle="Organization" rail={<OrganizationRail />}>
    <OrganizationLayout active="identity">{page}</OrganizationLayout>
  </App>
);
