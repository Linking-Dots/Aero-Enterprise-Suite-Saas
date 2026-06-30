/**
 * Organization Profile — first section of the unified Organization shell.
 *
 * Props: { org: { company_name, legal_name, registration_number,
 *                 industry, company_size, website, phone, email } }
 *
 * Save → POST core.organization.profile.update
 */
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Select, Card, CardHeader, CardBody, HStack, VStack, Text,
  useToast, useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import OrganizationLayout from './OrganizationLayout.jsx';
import OrganizationRail from './OrganizationRail.jsx';
import OrganizationSection from './OrganizationSection.jsx';

const INDUSTRIES = [
  'Technology', 'Finance', 'Healthcare', 'Manufacturing',
  'Retail', 'Education', 'Government', 'Non-profit', 'Other',
];

const COMPANY_SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];

export default function OrganizationProfile({ org }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.organization.org_profile.update');

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
    company_name:        org?.company_name        ?? '',
    legal_name:          org?.legal_name          ?? '',
    registration_number: org?.registration_number ?? '',
    industry:            org?.industry            ?? '',
    company_size:        org?.company_size        ?? '',
    website:             org?.website             ?? '',
    phone:               org?.phone               ?? '',
    email:               org?.email               ?? '',
  });

  function handleSave(e) {
    e.preventDefault();
    post(route('core.organization.profile.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Organization profile updated.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave}>
      <OrganizationSection
        title="Profile"
        description="Your company identity and contact details."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => reset()}
        onSave={handleSave}
      >
        <Card>
          <CardHeader><Text size="sm" tone="secondary">Company Identity</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Company Name" error={errors.company_name} required>
                <Input value={data.company_name} onChange={e => setData('company_name', e.target.value)} placeholder="Enter company name" />
              </Field>

              <Field label="Legal Name" error={errors.legal_name}>
                <Input value={data.legal_name} onChange={e => setData('legal_name', e.target.value)} placeholder="Registered legal name" />
              </Field>

              <Field label="Registration Number" error={errors.registration_number}>
                <Input value={data.registration_number} onChange={e => setData('registration_number', e.target.value)} placeholder="Company registration number" />
              </Field>

              <HStack gap={4}>
                <Field label="Industry" error={errors.industry}>
                  <Select
                    value={data.industry}
                    onChange={e => setData('industry', e.target.value)}
                    options={[{ value: '', label: '— Select industry —', disabled: true }, ...INDUSTRIES.map(i => ({ value: i, label: i }))]}
                  />
                </Field>

                <Field label="Company Size" error={errors.company_size}>
                  <Select
                    value={data.company_size}
                    onChange={e => setData('company_size', e.target.value)}
                    options={[{ value: '', label: '— Select size —', disabled: true }, ...COMPANY_SIZES.map(s => ({ value: s, label: s }))]}
                  />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardHeader><Text size="sm" tone="secondary">Contact Information</Text></CardHeader>
          <CardBody>
            <VStack gap={4}>
              <Field label="Website" error={errors.website}>
                <Input type="url" value={data.website} onChange={e => setData('website', e.target.value)} placeholder="https://example.com" />
              </Field>

              <HStack gap={4}>
                <Field label="Phone" error={errors.phone}>
                  <Input type="tel" value={data.phone} onChange={e => setData('phone', e.target.value)} placeholder="+1 555-0123" />
                </Field>

                <Field label="Email" error={errors.email}>
                  <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} placeholder="hello@example.com" />
                </Field>
              </HStack>
            </VStack>
          </CardBody>
        </Card>
      </OrganizationSection>
    </form>
  );
}

OrganizationProfile.layout = page => (
  <App title="Organization" railTitle="Organization" rail={<OrganizationRail />}>
    <OrganizationLayout active="profile">{page}</OrganizationLayout>
  </App>
);
