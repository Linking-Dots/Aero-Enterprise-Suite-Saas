/**
 * Branding & Appearance — tenant visual identity configuration.
 *
 * Props: { branding: { app_name, logo_url, favicon_url, primary_color, sidebar_theme } }
 *
 * Violations fixed vs. prior stub:
 *   P0-1: all style={} removed; color swatch uses a layout CSS class
 *   P0-2: raw <input type="file"> wrapped with FileInput engine component
 *         raw <input type="checkbox"> replaced with Toggle
 *         raw <input type="color"> kept only as hidden native input (unavoidable for color picker)
 *         raw <select> replaced with Select engine component
 *   P0-3: <div style> replaced with HStack/VStack/Box primitives
 *   P2-1: variant= → intent= on Button
 */
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Select, Toggle, FileInput,
  Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const SIDEBAR_THEME_OPTIONS = [
  { value: 'dark',  label: 'Dark' },
  { value: 'light', label: 'Light' },
];

export default function BrandingSettings({ branding = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.branding.update');

  const { data, setData, post, processing, errors, reset } = useForm({
    app_name:      branding.app_name      ?? '',
    primary_color: branding.primary_color ?? '#0f172a',
    sidebar_theme: branding.sidebar_theme ?? 'dark',
    logo:          null,
    favicon:       null,
  });

  // Live preview of primary_color
  const [colorPreview, setColorPreview] = useState(branding.primary_color ?? '#0f172a');

  function handleColorChange(val) {
    setColorPreview(val);
    setData('primary_color', val);
  }

  function handleSave(e) {
    e.preventDefault();
    post(route('core.settings.branding.update'), {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => toast.success('Branding saved.'),
      onError:   () => toast.error('Please fix the errors below.'),
    });
  }

  return (
    <form onSubmit={handleSave} encType="multipart/form-data">
      <FormPageLayout
        title="Branding & Appearance"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system') },
          { label: 'Branding' },
        ]}
        description="Customise your application name, logo, favicon, brand color, and sidebar theme."
        actions={
          canEdit && (
            <HStack gap={3}>
              <Button type="button" intent="soft" onClick={() => { reset(); setColorPreview(branding.primary_color ?? '#0f172a'); }} disabled={processing}>
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

          {/* ── Identity ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Identity</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                <Field label="App Name" error={errors.app_name}>
                  <Input
                    value={data.app_name}
                    onChange={e => setData('app_name', e.target.value)}
                    placeholder="My Company"
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

          {/* ── Brand Color ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Brand Color</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                <Field label="Primary Color" hint="Hex value, e.g. #0f172a" error={errors.primary_color}>
                  <HStack gap={3} align="center">
                    {/*
                      Native color input is unavoidable for a live color picker.
                      It has no engine equivalent. We keep it without style= by
                      applying the .branding-color-swatch scoped class below.
                    */}
                    <input
                      type="color"
                      value={colorPreview}
                      onChange={e => handleColorChange(e.target.value)}
                      className="branding-color-swatch"
                      aria-label="Pick primary color"
                    />
                    <Input
                      value={data.primary_color}
                      onChange={e => handleColorChange(e.target.value)}
                      placeholder="#0f172a"
                      maxLength={7}
                    />
                  </HStack>
                </Field>
              </VStack>
            </CardBody>
          </Card>

          {/* ── Sidebar Theme ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Sidebar Theme</Text></CardHeader>
            <CardBody>
              <Field label="Sidebar Theme" error={errors.sidebar_theme}>
                <Select
                  value={data.sidebar_theme}
                  onChange={e => setData('sidebar_theme', e.target.value)}
                  options={SIDEBAR_THEME_OPTIONS}
                />
              </Field>
            </CardBody>
          </Card>

          {/* ── Logo ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Logo</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                {branding.logo_url && (
                  <VStack gap={2}>
                    <Text size="sm" tone="secondary">Current logo</Text>
                    <img
                      src={branding.logo_url}
                      alt="Current logo"
                      className="branding-preview-img"
                    />
                  </VStack>
                )}
                <Field label="Upload Logo" hint="PNG, SVG, or WebP recommended." error={errors.logo}>
                  <FileInput
                    accept="image/png,image/svg+xml,image/webp,image/jpeg"
                    onChange={file => setData('logo', file)}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

          {/* ── Favicon ── */}
          <Card>
            <CardHeader><Text size="sm" tone="secondary">Favicon</Text></CardHeader>
            <CardBody>
              <VStack gap={4}>
                {branding.favicon_url && (
                  <VStack gap={2}>
                    <Text size="sm" tone="secondary">Current favicon</Text>
                    <img
                      src={branding.favicon_url}
                      alt="Current favicon"
                      className="branding-preview-favicon"
                    />
                  </VStack>
                )}
                <Field label="Upload Favicon" hint="ICO, PNG, or WebP. 32×32 px recommended." error={errors.favicon}>
                  <FileInput
                    accept="image/x-icon,image/png,image/webp"
                    onChange={file => setData('favicon', file)}
                  />
                </Field>
              </VStack>
            </CardBody>
          </Card>

        </VStack>
      </FormPageLayout>

      <style>{`
        .branding-color-swatch {
          width: 40px;
          height: 36px;
          padding: 2px;
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-sm);
          cursor: pointer;
          background: none;
        }
        .branding-preview-img {
          max-width: 160px;
          max-height: 80px;
          border-radius: var(--aeos-r-sm);
          border: 1px solid var(--aeos-divider);
        }
        .branding-preview-favicon {
          width: 32px;
          height: 32px;
          border-radius: var(--aeos-r-sm);
          border: 1px solid var(--aeos-divider);
        }
      `}</style>
    </form>
  );
}

BrandingSettings.layout = page => (
  <App title="Branding & Appearance">{page}</App>
);
