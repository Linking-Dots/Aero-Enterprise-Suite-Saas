import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field,
  Input,
  FileInput,
  Button,
  HStack,
  VStack,
  Eyebrow,
  Card,
  CardBody,
  Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function Branding({ branding }) {
  const toast   = useToast();
  const canEdit = useHRMAC('system-settings.branding-settings.edit');

  const [logoPreview,    setLogoPreview]    = useState(branding?.logo_url    ?? null);
  const [faviconPreview, setFaviconPreview] = useState(branding?.favicon_url ?? null);

  const form = useForm({
    primary_color: branding?.primary_color ?? '#0f172a',
    accent_color:  branding?.accent_color  ?? '#818cf8',
    logo:          null,
    favicon:       null,
  });

  function handleLogoChange(e) {
    const file = e.target.files?.[0] ?? null;
    form.setData('logo', file);
    if (file) setLogoPreview(URL.createObjectURL(file));
  }

  function handleFaviconChange(e) {
    const file = e.target.files?.[0] ?? null;
    form.setData('favicon', file);
    if (file) setFaviconPreview(URL.createObjectURL(file));
  }

  function handleSubmit(e) {
    e.preventDefault();
    form.put(route('platform.admin.settings.branding.update'), {
      forceFormData: true,
      onSuccess: () => toast.success('Branding saved.'),
      onError:   () => toast.error('Failed to save branding.'),
    });
  }

  return (
    <FormPageLayout
      title="Branding"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Settings' },
        { label: 'Branding' },
      ]}
      description="Logo, favicon and brand colour configuration."
      onSubmit={handleSubmit}
    >
      <VStack gap={6}>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Brand Colours</Eyebrow>

              <HStack gap={4}>
                <Field label="Primary Colour" htmlFor="primary_color" error={form.errors.primary_color} hint="Hex value, e.g. #0f172a">
                  <Input
                    id="primary_color"
                    value={form.data.primary_color}
                    onChange={e => form.setData('primary_color', e.target.value)}
                    placeholder="#0f172a"
                    error={form.errors.primary_color}
                  />
                </Field>

                <Field label="Accent Colour" htmlFor="accent_color" error={form.errors.accent_color} hint="Hex value, e.g. #818cf8">
                  <Input
                    id="accent_color"
                    value={form.data.accent_color}
                    onChange={e => form.setData('accent_color', e.target.value)}
                    placeholder="#818cf8"
                    error={form.errors.accent_color}
                  />
                </Field>
              </HStack>

              <HStack gap={4}>
                <HStack gap={2} align="center">
                  <Text tone="secondary">Primary</Text>
                  <div
                    className="aeos-color-swatch"
                    data-color={form.data.primary_color}
                    aria-label={`Primary colour: ${form.data.primary_color}`}
                  />
                </HStack>
                <HStack gap={2} align="center">
                  <Text tone="secondary">Accent</Text>
                  <div
                    className="aeos-color-swatch"
                    data-color={form.data.accent_color}
                    aria-label={`Accent colour: ${form.data.accent_color}`}
                  />
                </HStack>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Logo</Eyebrow>
              <Text tone="secondary">Recommended: SVG or PNG, transparent background, min 200 × 60 px. Max 2 MB.</Text>

              {logoPreview && (
                <img
                  src={logoPreview}
                  alt="Logo preview"
                  className="aeos-branding-preview"
                />
              )}

              <Field label="Upload Logo" htmlFor="logo" error={form.errors.logo}>
                <FileInput
                  id="logo"
                  accept="image/*"
                  onChange={handleLogoChange}
                  error={form.errors.logo}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Favicon</Eyebrow>
              <Text tone="secondary">Recommended: ICO or PNG, 32 × 32 px. Max 512 KB.</Text>

              {faviconPreview && (
                <img
                  src={faviconPreview}
                  alt="Favicon preview"
                  className="aeos-favicon-preview"
                />
              )}

              <Field label="Upload Favicon" htmlFor="favicon" error={form.errors.favicon}>
                <FileInput
                  id="favicon"
                  accept="image/*"
                  onChange={handleFaviconChange}
                  error={form.errors.favicon}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {canEdit && (
          <HStack gap={3}>
            <Button type="submit" intent="primary" loading={form.processing} disabled={form.processing}>
              Save Branding
            </Button>
          </HStack>
        )}

      </VStack>
    </FormPageLayout>
  );
}

Branding.layout = page => <App title="Branding">{page}</App>;
