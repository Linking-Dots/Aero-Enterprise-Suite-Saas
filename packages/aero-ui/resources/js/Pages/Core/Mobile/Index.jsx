/**
 * Mobile & PWA Settings — three-tab configuration page.
 *
 * Props:
 *   config {
 *     pwa_enabled, display_name, short_name, theme_color, background_color,
 *     display_mode, icon_path, push_enabled, vapid_public_key, mobile_app_enabled,
 *     android_package, ios_bundle_id, deep_link_schemes
 *   }
 *   manifest {}  — current generated manifest JSON
 *
 * Tabs:
 *   PWA            → POST core.mobile.pwa.update (forceFormData for icon upload)
 *   Push Notifications → POST core.mobile.push.update
 *   Mobile App     → POST core.mobile.mobile-app.update
 */
import { useState, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  FormPageLayout,
  HStack, VStack,
  Text, Mono, Eyebrow,
  Field, Input, Select, Toggle,
  Button,
  Card, CardBody,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const DISPLAY_MODE_OPTIONS = [
  { value: 'standalone',  label: 'Standalone' },
  { value: 'fullscreen',  label: 'Fullscreen' },
  { value: 'minimal-ui',  label: 'Minimal UI' },
  { value: 'browser',     label: 'Browser' },
];

const TABS = [
  { key: 'pwa',        label: 'PWA' },
  { key: 'push',       label: 'Push Notifications' },
  { key: 'mobile-app', label: 'Mobile App' },
];

/* ── PWA Tab ──────────────────────────────────────────────────────────────── */
function PwaTab({ config, manifest }) {
  const toast  = useToast();
  const canEdit = useHRMAC('core.mobile.pwa.update');
  const fileRef = useRef(null);

  const { data, setData, post, processing, errors } = useForm({
    pwa_enabled:      config?.pwa_enabled      ?? false,
    display_name:     config?.display_name      ?? '',
    short_name:       config?.short_name        ?? '',
    theme_color:      config?.theme_color       ?? '#000000',
    background_color: config?.background_color  ?? '#ffffff',
    display_mode:     config?.display_mode      ?? 'standalone',
    icon:             null,
  });

  const handleSave = (e) => {
    e.preventDefault();
    post(route('core.mobile.pwa.update'), {
      forceFormData: true,
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('PWA settings saved.'),
      onError:   () => toast.error('Failed to save PWA settings.'),
    });
  };

  return (
    <form onSubmit={handleSave}>
      <VStack gap={5}>
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Toggle
                label="Enable PWA"
                checked={data.pwa_enabled}
                onChange={v => setData('pwa_enabled', v)}
              />

              <HStack gap={4} wrap>
                <Field label="Display Name" error={errors.display_name}>
                  <Input
                    value={data.display_name}
                    onChange={e => setData('display_name', e.target.value)}
                    placeholder="My Application"
                  />
                </Field>
                <Field label="Short Name" error={errors.short_name} hint="Max 12 characters.">
                  <Input
                    value={data.short_name}
                    onChange={e => setData('short_name', e.target.value)}
                    placeholder="MyApp"
                  />
                </Field>
              </HStack>

              <HStack gap={4} wrap>
                <Field label="Theme Color" error={errors.theme_color}>
                  <HStack gap={2} align="center">
                    <input
                      type="color"
                      className="mobile-color-swatch"
                      value={data.theme_color}
                      onChange={e => setData('theme_color', e.target.value)}
                    />
                    <Input
                      value={data.theme_color}
                      onChange={e => setData('theme_color', e.target.value)}
                      placeholder="#000000"
                    />
                  </HStack>
                </Field>
                <Field label="Background Color" error={errors.background_color}>
                  <HStack gap={2} align="center">
                    <input
                      type="color"
                      className="mobile-color-swatch"
                      value={data.background_color}
                      onChange={e => setData('background_color', e.target.value)}
                    />
                    <Input
                      value={data.background_color}
                      onChange={e => setData('background_color', e.target.value)}
                      placeholder="#ffffff"
                    />
                  </HStack>
                </Field>
              </HStack>

              <Field label="Display Mode" error={errors.display_mode}>
                <Select
                  value={data.display_mode}
                  onChange={e => setData('display_mode', e.target.value)}
                  options={DISPLAY_MODE_OPTIONS}
                />
              </Field>

              <Field label="App Icon" hint="Recommended: 512×512 PNG." error={errors.icon}>
                <VStack gap={2}>
                  {config?.icon_path && (
                    <img
                      src={config.icon_path}
                      alt="Current icon"
                      className="mobile-icon-preview"
                    />
                  )}
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/svg+xml"
                    ref={fileRef}
                    className="mobile-file-input"
                    onChange={e => setData('icon', e.target.files[0] ?? null)}
                  />
                </VStack>
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {manifest && Object.keys(manifest).length > 0 && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Manifest Preview</Eyebrow>
                <Mono size="sm" className="mobile-json-preview">
                  {JSON.stringify(manifest, null, 2)}
                </Mono>
              </VStack>
            </CardBody>
          </Card>
        )}

        {canEdit && (
          <HStack gap={3} justify="end">
            <Button type="submit" intent="primary" loading={processing}>
              Save PWA Settings
            </Button>
          </HStack>
        )}
      </VStack>
    </form>
  );
}

/* ── Push Notifications Tab ───────────────────────────────────────────────── */
function PushTab({ config }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.mobile.push.update');

  const { data, setData, post, processing, errors } = useForm({
    push_enabled:       config?.push_enabled       ?? false,
    vapid_public_key:   config?.vapid_public_key   ?? '',
    vapid_private_key:  '',
  });

  const handleSave = (e) => {
    e.preventDefault();
    post(route('core.mobile.push.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Push settings saved.'),
      onError:   () => toast.error('Failed to save push settings.'),
    });
  };

  return (
    <form onSubmit={handleSave}>
      <VStack gap={5}>
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Toggle
                label="Enable Push Notifications"
                checked={data.push_enabled}
                onChange={v => setData('push_enabled', v)}
              />

              <Field label="VAPID Public Key" error={errors.vapid_public_key}>
                <Input
                  value={data.vapid_public_key}
                  onChange={e => setData('vapid_public_key', e.target.value)}
                  placeholder="BNIa..."
                  leftIcon="key"
                />
              </Field>

              <Field
                label="VAPID Private Key"
                hint="Leave blank to keep the existing key."
                error={errors.vapid_private_key}
              >
                <Input
                  type="password"
                  value={data.vapid_private_key}
                  onChange={e => setData('vapid_private_key', e.target.value)}
                  placeholder="Leave blank to keep existing"
                  leftIcon="lock"
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {canEdit && (
          <HStack gap={3} justify="end">
            <Button type="submit" intent="primary" loading={processing}>
              Save Push Settings
            </Button>
          </HStack>
        )}
      </VStack>
    </form>
  );
}

/* ── Mobile App Tab ───────────────────────────────────────────────────────── */
function MobileAppTab({ config }) {
  const toast   = useToast();
  const canEdit = useHRMAC('core.mobile.mobile-app.update');

  const { data, setData, post, processing, errors } = useForm({
    mobile_app_enabled: config?.mobile_app_enabled ?? false,
    android_package:    config?.android_package    ?? '',
    ios_bundle_id:      config?.ios_bundle_id      ?? '',
    deep_link_schemes:  Array.isArray(config?.deep_link_schemes)
      ? config.deep_link_schemes.join('\n')
      : (config?.deep_link_schemes ?? ''),
  });

  const handleSave = (e) => {
    e.preventDefault();
    post(route('core.mobile.mobile-app.update'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Mobile app settings saved.'),
      onError:   () => toast.error('Failed to save mobile app settings.'),
    });
  };

  return (
    <form onSubmit={handleSave}>
      <VStack gap={5}>
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Toggle
                label="Enable Mobile App Integration"
                checked={data.mobile_app_enabled}
                onChange={v => setData('mobile_app_enabled', v)}
              />

              <HStack gap={4} wrap>
                <Field label="Android Package" hint="e.g. com.yourapp" error={errors.android_package}>
                  <Input
                    value={data.android_package}
                    onChange={e => setData('android_package', e.target.value)}
                    placeholder="com.yourapp"
                  />
                </Field>
                <Field label="iOS Bundle ID" error={errors.ios_bundle_id}>
                  <Input
                    value={data.ios_bundle_id}
                    onChange={e => setData('ios_bundle_id', e.target.value)}
                    placeholder="com.yourapp.ios"
                  />
                </Field>
              </HStack>

              <Field
                label="Deep Link Schemes"
                hint="One scheme per line, e.g. myapp://"
                error={errors.deep_link_schemes}
              >
                <textarea
                  className="mobile-textarea"
                  rows={4}
                  value={data.deep_link_schemes}
                  onChange={e => setData('deep_link_schemes', e.target.value)}
                  placeholder={'myapp://\nhttps://app.example.com'}
                />
              </Field>
            </VStack>
          </CardBody>
        </Card>

        {canEdit && (
          <HStack gap={3} justify="end">
            <Button type="submit" intent="primary" loading={processing}>
              Save Mobile App Settings
            </Button>
          </HStack>
        )}
      </VStack>
    </form>
  );
}

/* ── Main Page ───────────────────────────────────────────────────────────── */
export default function MobileIndex({ config = {}, manifest = {} }) {
  const [activeTab, setActiveTab] = useState('pwa');

  return (
    <>
      <style>{`
        .mobile-color-swatch {
          width: 40px;
          height: 36px;
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-sm);
          cursor: pointer;
          padding: 2px;
          flex-shrink: 0;
        }
        .mobile-icon-preview {
          width: 80px;
          height: 80px;
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
          object-fit: contain;
        }
        .mobile-file-input {
          font-family: var(--aeos-font-body);
          font-size: 0.875rem;
          color: var(--aeos-text-secondary);
        }
        .mobile-json-preview {
          white-space: pre-wrap;
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-3);
          font-size: 0.75rem;
          overflow: auto;
          max-height: 320px;
        }
        .mobile-textarea {
          width: 100%;
          font-family: var(--aeos-font-mono);
          font-size: 0.875rem;
          color: var(--aeos-text-primary);
          background: var(--aeos-bg-surface);
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-md);
          padding: var(--aeos-space-2) var(--aeos-space-3);
          resize: vertical;
        }
        .mobile-textarea:focus {
          outline: none;
          border-color: var(--aeos-primary);
        }
      `}</style>
      <IndexPageLayout
        title="Mobile & PWA"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Mobile & PWA' },
        ]}
        description="Configure Progressive Web App, push notifications, and native mobile app settings."
        filters={
          <HStack gap={2} wrap>
            {TABS.map(tab => (
              <Button
                key={tab.key}
                intent={activeTab === tab.key ? 'primary' : 'soft'}
                onClick={() => setActiveTab(tab.key)}
              >
                {tab.label}
              </Button>
            ))}
          </HStack>
        }
        table={
          <>
            {activeTab === 'pwa'        && <PwaTab       config={config} manifest={manifest} />}
            {activeTab === 'push'       && <PushTab      config={config} />}
            {activeTab === 'mobile-app' && <MobileAppTab config={config} />}
          </>
        }
      />
    </>
  );
}

MobileIndex.layout = page => (
  <App title="Mobile & PWA">{page}</App>
);
