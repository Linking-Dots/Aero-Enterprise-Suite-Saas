/**
 * Organization Profile — Tenant organization details page.
 *
 * Props: { org: { company_name, legal_name, registration_number,
 *                 industry, company_size, website, phone, email } }
 *
 * Save → POST core.organization.profile.update
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Select, Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const INDUSTRIES = [
  'Technology', 'Finance', 'Healthcare', 'Manufacturing',
  'Retail', 'Education', 'Government', 'Non-profit', 'Other',
];

const COMPANY_SIZES = ['1-10', '11-50', '51-200', '201-500', '500+'];

function Section({ title, children }) {
  return (
    <Card>
      <CardHeader><Text weight="semibold">{title}</Text></CardHeader>
      <CardBody><VStack gap={4}>{children}</VStack></CardBody>
    </Card>
  );
}

export default function OrganizationProfile({ org }) {
  const toast    = useToast();
  const canEdit  = useHRMAC('core.organization.org_profile.update');

  const { data, setData, post, processing, errors, reset } = useForm({
    company_name:        org?.company_name        ?? '',
    legal_name:          org?.legal_name          ?? '',
    registration_number: org?.registration_number ?? '',
    industry:            org?.industry            ?? '',
    company_size:        org?.company_size        ?? '',
    website:             org?.website             ?? '',
    phone:               org?.phone               ?? '',
    email:               org?.email               ?? '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.organization.profile.update'), {
      preserveScroll: true,
      onSuccess: () => toast.success('Organization profile updated.'),
      onError:   () => toast.error('Failed to update organization profile.'),
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
        description="Manage your company identity and contact details."
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

            <Field label="Registration Number" error={errors.registration_number}>
              <Input
                value={data.registration_number}
                onChange={e => setData('registration_number', e.target.value)}
                placeholder="Company registration number"
              />
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
          </Section>

          <Section title="Contact Information">
            <Field label="Website" error={errors.website}>
              <Input
                type="url"
                value={data.website}
                onChange={e => setData('website', e.target.value)}
                placeholder="https://example.com"
              />
            </Field>

            <HStack gap={4}>
              <Field label="Phone" error={errors.phone}>
                <Input
                  type="tel"
                  value={data.phone}
                  onChange={e => setData('phone', e.target.value)}
                  placeholder="+1 555-0123"
                />
              </Field>

              <Field label="Email" error={errors.email}>
                <Input
                  type="email"
                  value={data.email}
                  onChange={e => setData('email', e.target.value)}
                  placeholder="hello@example.com"
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
