import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Card,
  CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TENANT_STATUS_INTENT = {
  active:    'success',
  suspended: 'warning',
  trial:     'amber',
};

export default function Branding({ tenants, branding, selectedTenant }) {
  const toast     = useToast();
  const canManage = useHRMAC('white-label.tenant-branding.manage');
  const canCss    = useHRMAC('white-label.custom-css.edit');

  const { errors } = usePage().props;

  const [tenantId, setTenantId]       = useState(selectedTenant?.id ?? '');
  const [saving,   setSaving]         = useState(false);
  const [saved,    setSaved]          = useState(false);

  const [form, setForm] = useState({
    primary_color:    branding?.primary_color    ?? '#6366f1',
    secondary_color:  branding?.secondary_color  ?? '#8b5cf6',
    email_from_name:  branding?.email_from_name  ?? '',
    email_from_address: branding?.email_from_address ?? '',
    custom_css:       branding?.custom_css        ?? '',
  });

  const tenantOptions = [
    { value: '', label: 'Select a tenant...' },
    ...(tenants ?? []).map(t => ({ value: String(t.id), label: t.name ?? t.domain })),
  ];

  function loadTenant(id) {
    setTenantId(id);
    if (!id) return;
    router.get(
      route('platform.admin.white-label.branding.index'),
      { tenant_id: id },
      { preserveState: true, preserveScroll: true, only: ['branding', 'selectedTenant'] }
    );
  }

  function handleLogoUpload(e) {
    const file = e.target.files?.[0];
    if (!file || !tenantId) return;
    const fd = new FormData();
    fd.append('logo', file);
    fd.append('tenant_id', tenantId);
    router.post(route('platform.admin.white-label.branding.logo'), fd, {
      preserveState: true,
      onSuccess: () => toast.success('Logo uploaded.'),
      onError:   () => toast.error('Logo upload failed.'),
    });
  }

  function handleFaviconUpload(e) {
    const file = e.target.files?.[0];
    if (!file || !tenantId) return;
    const fd = new FormData();
    fd.append('favicon', file);
    fd.append('tenant_id', tenantId);
    router.post(route('platform.admin.white-label.branding.favicon'), fd, {
      preserveState: true,
      onSuccess: () => toast.success('Favicon uploaded.'),
      onError:   () => toast.error('Favicon upload failed.'),
    });
  }

  function save() {
    if (!tenantId) { toast.error('Select a tenant first.'); return; }
    setSaving(true);
    setSaved(false);
    router.post(
      route('platform.admin.white-label.branding.update'),
      { ...form, tenant_id: tenantId },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => setSaved(true),
        onFinish:  () => setSaving(false),
      }
    );
  }

  return (
    <IndexPageLayout
      title="Tenant Branding"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'White Label' },
        { label: 'Branding' },
      ]}
      description="Customise logo, colours, and CSS for individual tenants."
      actions={
        canManage && (
          <Button
            intent="primary"
            loading={saving}
            disabled={saving || !tenantId}
            onClick={save}
          >
            {saved ? 'Saved' : 'Save Branding'}
          </Button>
        )
      }
    >
      <VStack gap={5}>
        {/* Tenant Selector */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Select Tenant</Eyebrow>
              <Field label="Tenant" htmlFor="tenant_select" required>
                <Select
                  id="tenant_select"
                  value={tenantId}
                  onChange={e => loadTenant(e.target.value)}
                  options={tenantOptions}
                />
              </Field>
              {selectedTenant && (
                <HStack gap={3}>
                  <Text>{selectedTenant.name}</Text>
                  <Badge intent={TENANT_STATUS_INTENT[selectedTenant.status] ?? 'neutral'}>
                    {selectedTenant.status}
                  </Badge>
                  <Mono tone="secondary">{selectedTenant.domain}</Mono>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {tenantId && (
          <>
            {/* Logo & Favicon */}
            <Card>
              <CardBody>
                <VStack gap={4}>
                  <Eyebrow>Assets</Eyebrow>
                  <HStack gap={6} wrap>
                    <VStack gap={2}>
                      <Text tone="secondary">Current Logo</Text>
                      {branding?.logo_path ? (
                        <img
                          src={branding.logo_url ?? branding.logo_path}
                          alt="Tenant Logo"
                          className="aeos-branding-preview-img"
                        />
                      ) : (
                        <Text tone="secondary">No logo uploaded</Text>
                      )}
                      {canManage && (
                        <Field label="Upload Logo" htmlFor="logo_upload" error={errors.logo}>
                          <Input
                            id="logo_upload"
                            type="file"
                            accept="image/*"
                            onChange={handleLogoUpload}
                            disabled={!tenantId}
                          />
                        </Field>
                      )}
                    </VStack>

                    <VStack gap={2}>
                      <Text tone="secondary">Current Favicon</Text>
                      {branding?.favicon_path ? (
                        <img
                          src={branding.favicon_url ?? branding.favicon_path}
                          alt="Tenant Favicon"
                          className="aeos-branding-preview-favicon"
                        />
                      ) : (
                        <Text tone="secondary">No favicon uploaded</Text>
                      )}
                      {canManage && (
                        <Field label="Upload Favicon" htmlFor="favicon_upload" error={errors.favicon}>
                          <Input
                            id="favicon_upload"
                            type="file"
                            accept="image/*"
                            onChange={handleFaviconUpload}
                            disabled={!tenantId}
                          />
                        </Field>
                      )}
                    </VStack>
                  </HStack>
                </VStack>
              </CardBody>
            </Card>

            {/* Colours */}
            <Card>
              <CardBody>
                <VStack gap={4}>
                  <Eyebrow>Brand Colours</Eyebrow>
                  <HStack gap={4} wrap>
                    <Field label="Primary Colour" htmlFor="primary_color" error={errors.primary_color}>
                      <Input
                        id="primary_color"
                        type="color"
                        value={form.primary_color}
                        onChange={e => setForm({ ...form, primary_color: e.target.value })}
                        disabled={!canManage}
                      />
                    </Field>
                    <Field label="Secondary Colour" htmlFor="secondary_color" error={errors.secondary_color}>
                      <Input
                        id="secondary_color"
                        type="color"
                        value={form.secondary_color}
                        onChange={e => setForm({ ...form, secondary_color: e.target.value })}
                        disabled={!canManage}
                      />
                    </Field>
                  </HStack>
                </VStack>
              </CardBody>
            </Card>

            {/* Email Branding */}
            <Card>
              <CardBody>
                <VStack gap={4}>
                  <Eyebrow>Email Branding</Eyebrow>
                  <HStack gap={4} wrap>
                    <Field label="From Name" htmlFor="email_from_name" error={errors.email_from_name}>
                      <Input
                        id="email_from_name"
                        value={form.email_from_name}
                        onChange={e => setForm({ ...form, email_from_name: e.target.value })}
                        placeholder="Acme Inc."
                        disabled={!canManage}
                      />
                    </Field>
                    <Field label="From Address" htmlFor="email_from_address" error={errors.email_from_address}>
                      <Input
                        id="email_from_address"
                        type="email"
                        value={form.email_from_address}
                        onChange={e => setForm({ ...form, email_from_address: e.target.value })}
                        placeholder="noreply@client.com"
                        disabled={!canManage}
                      />
                    </Field>
                  </HStack>
                </VStack>
              </CardBody>
            </Card>

            {/* Custom CSS */}
            {canCss && (
              <Card>
                <CardBody>
                  <VStack gap={3}>
                    <Eyebrow>Custom CSS</Eyebrow>
                    <Text tone="secondary">
                      CSS injected into the tenant app. Scoped to the tenant context automatically.
                    </Text>
                    <Field label="Custom CSS" htmlFor="custom_css" error={errors.custom_css}>
                      <textarea
                        id="custom_css"
                        className="aeos-textarea aeos-text-sm font-mono"
                        rows={12}
                        value={form.custom_css}
                        onChange={e => setForm({ ...form, custom_css: e.target.value })}
                        placeholder=":root { --brand-accent: #f59e0b; }"
                      />
                    </Field>
                  </VStack>
                </CardBody>
              </Card>
            )}
          </>
        )}
      </VStack>
    </IndexPageLayout>
  );
}

Branding.layout = page => <App title="Tenant Branding">{page}</App>;
