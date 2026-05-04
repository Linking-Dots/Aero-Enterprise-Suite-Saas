/**
 * Organization Profile — Tenant organization details page.
 *
 * Uses @aero/ui FormPageLayout with inline sections for
 * Company Identity, Contact, Address, and Fiscal settings.
 * Data is loaded via Inertia from OrganizationProfileController.
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Textarea, Select,
  Button, Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader>
        <Text weight="semibold">{title}</Text>
      </CardHeader>
      <CardBody>
        <VStack gap={4}>
          {children}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function OrganizationProfile({ organization }) {
  const toast = useToast();

  const { data, setData, put, processing, errors, reset } = useForm({
    company_name: organization?.company_name ?? '',
    legal_name: organization?.legal_name ?? '',
    tagline: organization?.tagline ?? '',
    tax_id: organization?.tax_id ?? '',
    vat_number: organization?.vat_number ?? '',
    registration_number: organization?.registration_number ?? '',
    industry: organization?.industry ?? '',
    website_url: organization?.website_url ?? '',
    email: organization?.email ?? '',
    support_email: organization?.support_email ?? '',
    phone: organization?.phone ?? '',
    mobile_number: organization?.mobile_number ?? '',
    fax: organization?.fax ?? '',
    contact_person: organization?.contact_person ?? '',
    address_line1: organization?.address_line1 ?? '',
    address_line2: organization?.address_line2 ?? '',
    city: organization?.city ?? '',
    state: organization?.state ?? '',
    postal_code: organization?.postal_code ?? '',
    country: organization?.country ?? '',
    timezone: organization?.timezone ?? '',
    fiscal_year_start: organization?.fiscal_year_start ?? '',
    fiscal_year_end: organization?.fiscal_year_end ?? '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(route('core.organization.profile.update'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Organization profile updated successfully.');
      },
      onError: () => {
        toast.error('Failed to update organization profile. Please check the form.');
      },
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <FormPageLayout
        title="Organization Profile"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Organization Profile' },
        ]}
        description="Manage your company identity, contact details, and fiscal settings."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button
                type="button"
                variant="secondary"
                onClick={() => reset()}
                disabled={processing}
              >
                Reset
              </Button>
              <Button
                type="submit"
                variant="primary"
                loading={processing}
              >
                Save Changes
              </Button>
            </HStack>
          )
        }
      >
        <VStack gap={6}>
          <Section title="Company Identity">
            <Field label="Company Name" error={errors.company_name} required>
              <Input
                value={data.company_name}
                onChange={e => setData('company_name', e.target.value)}
                placeholder="Enter company name"
              />
            </Field>
            <Field label="Legal Name" error={errors.legal_name}>
              <Input
                value={data.legal_name}
                onChange={e => setData('legal_name', e.target.value)}
                placeholder="Registered legal name"
              />
            </Field>
            <Field label="Tagline" error={errors.tagline}>
              <Input
                value={data.tagline}
                onChange={e => setData('tagline', e.target.value)}
                placeholder="Company tagline"
              />
            </Field>
            <HStack gap={4}>
              <Field label="Tax ID" error={errors.tax_id}>
                <Input
                  value={data.tax_id}
                  onChange={e => setData('tax_id', e.target.value)}
                  placeholder="Tax identification number"
                />
              </Field>
              <Field label="VAT Number" error={errors.vat_number}>
                <Input
                  value={data.vat_number}
                  onChange={e => setData('vat_number', e.target.value)}
                  placeholder="VAT registration number"
                />
              </Field>
            </HStack>
            <HStack gap={4}>
              <Field label="Registration Number" error={errors.registration_number}>
                <Input
                  value={data.registration_number}
                  onChange={e => setData('registration_number', e.target.value)}
                  placeholder="Company registration number"
                />
              </Field>
              <Field label="Industry" error={errors.industry}>
                <Input
                  value={data.industry}
                  onChange={e => setData('industry', e.target.value)}
                  placeholder="e.g. Technology, Manufacturing"
                />
              </Field>
            </HStack>
            <Field label="Website" error={errors.website_url}>
              <Input
                type="url"
                value={data.website_url}
                onChange={e => setData('website_url', e.target.value)}
                placeholder="https://example.com"
              />
            </Field>
          </Section>

          <Section title="Contact Information">
            <HStack gap={4}>
              <Field label="Email" error={errors.email} required>
                <Input
                  type="email"
                  value={data.email}
                  onChange={e => setData('email', e.target.value)}
                  placeholder="Primary contact email"
                />
              </Field>
              <Field label="Support Email" error={errors.support_email}>
                <Input
                  type="email"
                  value={data.support_email}
                  onChange={e => setData('support_email', e.target.value)}
                  placeholder="Support email address"
                />
              </Field>
            </HStack>
            <HStack gap={4}>
              <Field label="Phone" error={errors.phone}>
                <Input
                  value={data.phone}
                  onChange={e => setData('phone', e.target.value)}
                  placeholder="Primary phone number"
                />
              </Field>
              <Field label="Mobile" error={errors.mobile_number}>
                <Input
                  value={data.mobile_number}
                  onChange={e => setData('mobile_number', e.target.value)}
                  placeholder="Mobile number"
                />
              </Field>
            </HStack>
            <HStack gap={4}>
              <Field label="Fax" error={errors.fax}>
                <Input
                  value={data.fax}
                  onChange={e => setData('fax', e.target.value)}
                  placeholder="Fax number"
                />
              </Field>
              <Field label="Contact Person" error={errors.contact_person}>
                <Input
                  value={data.contact_person}
                  onChange={e => setData('contact_person', e.target.value)}
                  placeholder="Primary contact person"
                />
              </Field>
            </HStack>
          </Section>

          <Section title="Address">
            <Field label="Address Line 1" error={errors.address_line1}>
              <Textarea
                value={data.address_line1}
                onChange={e => setData('address_line1', e.target.value)}
                placeholder="Street address"
                rows={2}
              />
            </Field>
            <Field label="Address Line 2" error={errors.address_line2}>
              <Textarea
                value={data.address_line2}
                onChange={e => setData('address_line2', e.target.value)}
                placeholder="Apartment, suite, unit, etc."
                rows={2}
              />
            </Field>
            <HStack gap={4}>
              <Field label="City" error={errors.city}>
                <Input
                  value={data.city}
                  onChange={e => setData('city', e.target.value)}
                  placeholder="City"
                />
              </Field>
              <Field label="State / Province" error={errors.state}>
                <Input
                  value={data.state}
                  onChange={e => setData('state', e.target.value)}
                  placeholder="State or province"
                />
              </Field>
            </HStack>
            <HStack gap={4}>
              <Field label="Postal Code" error={errors.postal_code}>
                <Input
                  value={data.postal_code}
                  onChange={e => setData('postal_code', e.target.value)}
                  placeholder="Postal / ZIP code"
                />
              </Field>
              <Field label="Country" error={errors.country}>
                <Input
                  value={data.country}
                  onChange={e => setData('country', e.target.value)}
                  placeholder="Country"
                />
              </Field>
            </HStack>
          </Section>

          <Section title="Fiscal & Regional">
            <HStack gap={4}>
              <Field label="Timezone" error={errors.timezone}>
                <Input
                  value={data.timezone}
                  onChange={e => setData('timezone', e.target.value)}
                  placeholder="e.g. UTC, America/New_York"
                />
              </Field>
            </HStack>
            <HStack gap={4}>
              <Field label="Fiscal Year Start" error={errors.fiscal_year_start}>
                <Input
                  type="date"
                  value={data.fiscal_year_start}
                  onChange={e => setData('fiscal_year_start', e.target.value)}
                />
              </Field>
              <Field label="Fiscal Year End" error={errors.fiscal_year_end}>
                <Input
                  type="date"
                  value={data.fiscal_year_end}
                  onChange={e => setData('fiscal_year_end', e.target.value)}
                />
              </Field>
            </HStack>
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

OrganizationProfile.layout = page => (
  <App title="Organization Profile">{page}</App>
);
