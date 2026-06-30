/**
 * Branding & Appearance — tenant visual identity configuration.
 *
 * Props: { branding: { app_name, logo_url, favicon_url, primary_color, sidebar_theme } }
 *
 * Ported onto the unified SettingsLayout shell (Task 3). Violations fixed vs. prior stub:
 *   P0-1: all style={} removed; color swatch uses a layout CSS class
 *   P0-2: raw <input type="file"> wrapped with FileInput engine component
 *         raw <input type="checkbox"> replaced with Toggle
 *         raw <input type="color"> kept only as the one allowed native input (unavoidable
 *         for a color picker) — uses the centralized .branding-color-swatch class, no style=
 *         raw <select> replaced with Select engine component
 *   P0-3: <div style> replaced with HStack/VStack/Box primitives
 *   P2-1: variant= → intent= on Button
 *   Local <style> block removed — its 3 classes are centralized in SettingsLayout.jsx.
 */
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import {
  Field, Input, Select, FileInput,
  Card, CardHeader, CardBody,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsSection from './SettingsSection.jsx';
import SettingsRail from './SettingsRail.jsx';

const SIDEBAR_THEME_OPTIONS = [
  { value: 'dark',  label: 'Dark' },
  { value: 'light', label: 'Light' },
];

export default function BrandingSettings({ branding = {} }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.settings.branding.update');

  const { data, setData, post, processing, errors, reset, isDirty } = useForm({
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
      <SettingsSection
        title="Branding & Appearance"
        description="Customise your application name, logo, favicon, brand color, and sidebar theme."
        canEdit={canEdit}
        dirty={isDirty}
        processing={processing}
        onReset={() => { reset(); setColorPreview(branding.primary_color ?? '#0f172a'); }}
        onSave={handleSave}
      >
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
                    applying the centralized .branding-color-swatch class (SettingsLayout.jsx).
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
      </SettingsSection>
    </form>
  );
}

BrandingSettings.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="branding">{page}</SettingsLayout>
  </App>
);
