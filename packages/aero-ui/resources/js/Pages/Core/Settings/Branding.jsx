/**
 * Branding & Appearance — Tenant branding settings page.
 *
 * Uses @aero/ui FormPageLayout with sections for logos,
 * colors, and display preferences. Handles image uploads
 * via Inertia useForm with file inputs.
 */
import { useForm } from '@inertiajs/react';
import {
  FormPageLayout,
  Field, Input, Button, Card, CardHeader, CardBody,
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

function FilePreview({ url, label }) {
  if (!url) return null;
  return (
    <div style={{ marginTop: 8 }}>
      <Text size="sm" color="muted">{label}</Text>
      <img
        src={url}
        alt={label}
        style={{
          maxWidth: 160,
          maxHeight: 80,
          marginTop: 4,
          borderRadius: 4,
          border: '1px solid var(--border)',
        }}
      />
    </div>
  );
}

export default function BrandingSettings({ branding }) {
  const toast = useToast();
  const canEdit = useHRMAC('core.settings.branding.update');

  const { data, setData, post, processing, errors, reset, progress } = useForm({
    logo_light: null,
    logo_dark: null,
    favicon: null,
    login_background: null,
    remove_logo_light: false,
    remove_logo_dark: false,
    remove_favicon: false,
    remove_login_background: false,
    primary_color: branding?.primary_color ?? '#0f172a',
    accent_color: branding?.accent_color ?? '#6366f1',
    'branding.font_family': branding?.font_family ?? 'system-ui',
    'branding.button_radius': branding?.button_radius ?? 'md',
    'branding.show_company_name_header': branding?.show_company_name_header ?? true,
    'branding.show_logo_on_login': branding?.show_logo_on_login ?? true,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('core.settings.branding.update'), {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        toast.success('Branding settings updated successfully.');
      },
      onError: () => {
        toast.error('Failed to update branding. Please check the form.');
      },
    });
  };

  const handleFileChange = (field) => (e) => {
    const file = e.target.files[0] || null;
    setData(field, file);
  };

  const handleRemoveToggle = (field) => (e) => {
    setData(field, e.target.checked);
  };

  return (
    <form onSubmit={handleSubmit} encType="multipart/form-data">
      <FormPageLayout
        title="Branding & Appearance"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Branding & Appearance' },
        ]}
        description="Customize your tenant's visual identity including logos, colors, and display preferences."
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
          <Section title="Logos & Icons">
            <Field label="Light Logo" error={errors.logo_light}>
              <input
                type="file"
                accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                onChange={handleFileChange('logo_light')}
                style={{ display: 'block', marginTop: 4 }}
              />
              {branding?.logo_light && (
                <FilePreview url={branding.logo_light} label="Current light logo" />
              )}
              {branding?.logo_light && (
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                  <input
                    type="checkbox"
                    checked={data.remove_logo_light}
                    onChange={handleRemoveToggle('remove_logo_light')}
                  />
                  <Text size="sm">Remove current light logo</Text>
                </label>
              )}
            </Field>

            <Field label="Dark Logo" error={errors.logo_dark}>
              <input
                type="file"
                accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                onChange={handleFileChange('logo_dark')}
                style={{ display: 'block', marginTop: 4 }}
              />
              {branding?.logo_dark && (
                <FilePreview url={branding.logo_dark} label="Current dark logo" />
              )}
              {branding?.logo_dark && (
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                  <input
                    type="checkbox"
                    checked={data.remove_logo_dark}
                    onChange={handleRemoveToggle('remove_logo_dark')}
                  />
                  <Text size="sm">Remove current dark logo</Text>
                </label>
              )}
            </Field>

            <Field label="Favicon" error={errors.favicon}>
              <input
                type="file"
                accept="image/x-icon,image/png,image/webp"
                onChange={handleFileChange('favicon')}
                style={{ display: 'block', marginTop: 4 }}
              />
              {branding?.favicon && (
                <FilePreview url={branding.favicon} label="Current favicon" />
              )}
              {branding?.favicon && (
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                  <input
                    type="checkbox"
                    checked={data.remove_favicon}
                    onChange={handleRemoveToggle('remove_favicon')}
                  />
                  <Text size="sm">Remove current favicon</Text>
                </label>
              )}
            </Field>

            <Field label="Login Background" error={errors.login_background}>
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={handleFileChange('login_background')}
                style={{ display: 'block', marginTop: 4 }}
              />
              {branding?.login_background && (
                <FilePreview url={branding.login_background} label="Current login background" />
              )}
              {branding?.login_background && (
                <label style={{ display: 'flex', alignItems: 'center', gap: 6, marginTop: 8 }}>
                  <input
                    type="checkbox"
                    checked={data.remove_login_background}
                    onChange={handleRemoveToggle('remove_login_background')}
                  />
                  <Text size="sm">Remove current login background</Text>
                </label>
              )}
            </Field>

            {progress && (
              <div style={{ width: '100%', background: 'var(--border)', borderRadius: 4, overflow: 'hidden' }}>
                <div
                  style={{
                    width: `${progress.percentage}%`,
                    height: 4,
                    background: 'var(--primary)',
                    transition: 'width 0.2s ease',
                  }}
                />
              </div>
            )}
          </Section>

          <Section title="Brand Colors">
            <HStack gap={4}>
              <Field label="Primary Color" error={errors.primary_color}>
                <HStack gap={2} align="center">
                  <input
                    type="color"
                    value={data.primary_color}
                    onChange={e => setData('primary_color', e.target.value)}
                    style={{ width: 40, height: 36, padding: 2, border: '1px solid var(--border)', borderRadius: 4, cursor: 'pointer' }}
                  />
                  <Input
                    value={data.primary_color}
                    onChange={e => setData('primary_color', e.target.value)}
                    placeholder="#0f172a"
                    maxLength={7}
                    style={{ flex: 1 }}
                  />
                </HStack>
              </Field>

              <Field label="Accent Color" error={errors.accent_color}>
                <HStack gap={2} align="center">
                  <input
                    type="color"
                    value={data.accent_color}
                    onChange={e => setData('accent_color', e.target.value)}
                    style={{ width: 40, height: 36, padding: 2, border: '1px solid var(--border)', borderRadius: 4, cursor: 'pointer' }}
                  />
                  <Input
                    value={data.accent_color}
                    onChange={e => setData('accent_color', e.target.value)}
                    placeholder="#6366f1"
                    maxLength={7}
                    style={{ flex: 1 }}
                  />
                </HStack>
              </Field>
            </HStack>
          </Section>

          <Section title="Display Preferences">
            <Field label="Font Family" error={errors['branding.font_family']}>
              <Input
                value={data['branding.font_family']}
                onChange={e => setData('branding.font_family', e.target.value)}
                placeholder="e.g. system-ui, Inter, sans-serif"
              />
            </Field>

            <Field label="Button Radius" error={errors['branding.button_radius']}>
              <select
                value={data['branding.button_radius']}
                onChange={e => setData('branding.button_radius', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                }}
              >
                <option value="none">None</option>
                <option value="sm">Small</option>
                <option value="md">Medium</option>
                <option value="lg">Large</option>
                <option value="full">Full (Pill)</option>
              </select>
            </Field>

            <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <input
                type="checkbox"
                checked={data['branding.show_company_name_header']}
                onChange={e => setData('branding.show_company_name_header', e.target.checked)}
              />
              <Text size="sm">Show company name in header</Text>
            </label>

            <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <input
                type="checkbox"
                checked={data['branding.show_logo_on_login']}
                onChange={e => setData('branding.show_logo_on_login', e.target.checked)}
              />
              <Text size="sm">Show logo on login page</Text>
            </label>
          </Section>
        </VStack>
      </FormPageLayout>
    </form>
  );
}

BrandingSettings.layout = page => (
  <App title="Branding & Appearance">{page}</App>
);
