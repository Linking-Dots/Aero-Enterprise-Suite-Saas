import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Modal,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function InvoicingSettings({ settings, templates }) {
  const toast           = useToast();
  const canManageNum    = useHRMAC('invoicing.invoice-numbering.manage');
  const canManageTpl    = useHRMAC('invoicing.invoice-templates.manage');
  const canManageBrand  = useHRMAC('invoicing.invoice-branding.manage');

  const [saving,         setSaving]         = useState(false);
  const [savingBrand,    setSavingBrand]    = useState(false);
  const [savingTpl,      setSavingTpl]      = useState(false);
  const [tplDrawerOpen,  setTplDrawerOpen]  = useState(false);
  const [editTpl,        setEditTpl]        = useState(null);

  const form = useForm({
    prefix:          settings?.prefix ?? 'INV',
    next_number:     settings?.next_number ?? 1,
    digit_padding:   settings?.digit_padding ?? 6,
    active_template_id: settings?.active_template_id ? String(settings.active_template_id) : '',
    due_days:        settings?.due_days ?? 30,
  });

  const brandForm = useForm({
    company_name: settings?.company_name ?? '',
    footer_text:  settings?.footer_text  ?? '',
    logo_path:    settings?.logo_path    ?? '',
  });

  const tplForm = useForm({
    name:       '',
    html_body:  '',
    is_default: false,
  });

  function saveNumbering(e) {
    e.preventDefault();
    setSaving(true);
    router.put(
      route('platform.admin.invoicing.settings.update'),
      {
        prefix:        form.data.prefix,
        next_number:   Number(form.data.next_number),
        digit_padding: Number(form.data.digit_padding),
        active_template_id: form.data.active_template_id || null,
        due_days:      Number(form.data.due_days),
      },
      {
        preserveState: true,
        onSuccess: () => toast.success('Invoice settings saved.'),
        onError:   () => toast.error('Failed to save invoice settings.'),
        onFinish:  () => setSaving(false),
      }
    );
  }

  function saveBranding(e) {
    e.preventDefault();
    setSavingBrand(true);
    router.put(
      route('platform.admin.invoicing.branding.update'),
      brandForm.data,
      {
        preserveState: true,
        onSuccess: () => toast.success('Branding saved.'),
        onError:   () => toast.error('Failed to save branding.'),
        onFinish:  () => setSavingBrand(false),
      }
    );
  }

  function openCreateTpl() {
    setEditTpl(null);
    tplForm.reset();
    setTplDrawerOpen(true);
  }

  function openEditTpl(tpl) {
    setEditTpl(tpl);
    tplForm.setData({
      name:       tpl.name,
      html_body:  tpl.html_body ?? '',
      is_default: tpl.is_default,
    });
    setTplDrawerOpen(true);
  }

  function saveTpl(e) {
    e.preventDefault();
    setSavingTpl(true);
    if (editTpl) {
      router.put(
        route('platform.admin.invoicing.templates.update', editTpl.id),
        tplForm.data,
        {
          preserveState: true,
          onSuccess: () => { toast.success('Template updated.'); setTplDrawerOpen(false); },
          onError:   () => toast.error('Failed to update template.'),
          onFinish:  () => setSavingTpl(false),
        }
      );
    } else {
      router.post(
        route('platform.admin.invoicing.templates.store'),
        tplForm.data,
        {
          preserveState: true,
          onSuccess: () => { toast.success('Template created.'); setTplDrawerOpen(false); tplForm.reset(); },
          onError:   () => toast.error('Failed to create template.'),
          onFinish:  () => setSavingTpl(false),
        }
      );
    }
  }

  const templateOptions = [
    { value: '', label: 'No template (default)' },
    ...(templates ?? []).map(t => ({ value: String(t.id), label: t.name })),
  ];

  const tplColumns = [
    {
      key: 'name',
      label: 'Template Name',
      render: (row) => (
        <HStack gap={2}>
          <Text>{row.name}</Text>
          {row.is_default && <Badge intent="success">Default</Badge>}
        </HStack>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canManageTpl && (
          <Button intent="ghost" size="sm" onClick={() => openEditTpl(row)}>
            Edit
          </Button>
        ),
    },
  ];

  return (
    <IndexPageLayout
      title="Invoice Settings"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Invoicing', href: route('platform.admin.invoicing.index') },
        { label: 'Settings' },
      ]}
      description="Configure invoice numbering, templates, and company branding."
    >
      <VStack gap={6}>

        {/* Numbering & Defaults */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Numbering Scheme</Eyebrow>

              <HStack gap={3} wrap>
                <Field label="Invoice Prefix" htmlFor="inv_prefix" error={form.errors.prefix} required>
                  <Input
                    id="inv_prefix"
                    value={form.data.prefix}
                    onChange={e => form.setData('prefix', e.target.value)}
                    placeholder="INV"
                    error={form.errors.prefix}
                  />
                </Field>

                <Field label="Next Number" htmlFor="inv_next" error={form.errors.next_number} required hint="Next invoice will use this number">
                  <Input
                    id="inv_next"
                    type="number"
                    value={form.data.next_number}
                    onChange={e => form.setData('next_number', e.target.value)}
                    placeholder="1"
                    error={form.errors.next_number}
                  />
                </Field>

                <Field label="Digit Padding" htmlFor="inv_padding" error={form.errors.digit_padding} required hint="e.g. 6 → INV-000001">
                  <Input
                    id="inv_padding"
                    type="number"
                    value={form.data.digit_padding}
                    onChange={e => form.setData('digit_padding', e.target.value)}
                    placeholder="6"
                    error={form.errors.digit_padding}
                  />
                </Field>

                <Field label="Payment Due Days" htmlFor="inv_due" error={form.errors.due_days} required hint="Days after issue date">
                  <Input
                    id="inv_due"
                    type="number"
                    value={form.data.due_days}
                    onChange={e => form.setData('due_days', e.target.value)}
                    placeholder="30"
                    error={form.errors.due_days}
                  />
                </Field>
              </HStack>

              <Field label="Active Template" htmlFor="inv_template">
                <Select
                  id="inv_template"
                  value={form.data.active_template_id}
                  onChange={e => form.setData('active_template_id', e.target.value)}
                  options={templateOptions}
                />
              </Field>

              {canManageNum && (
                <HStack gap={2}>
                  <Button
                    intent="primary"
                    loading={saving}
                    disabled={saving}
                    onClick={saveNumbering}
                  >
                    Save Settings
                  </Button>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Branding */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Company Branding</Eyebrow>

              <Field label="Company Name" htmlFor="brand_name" error={brandForm.errors.company_name}>
                <Input
                  id="brand_name"
                  value={brandForm.data.company_name}
                  onChange={e => brandForm.setData('company_name', e.target.value)}
                  placeholder="Acme Corp"
                  error={brandForm.errors.company_name}
                />
              </Field>

              <Field label="Logo Path" htmlFor="brand_logo" error={brandForm.errors.logo_path} hint="URL or storage path to your company logo">
                <Input
                  id="brand_logo"
                  value={brandForm.data.logo_path}
                  onChange={e => brandForm.setData('logo_path', e.target.value)}
                  placeholder="/storage/logos/logo.png"
                  error={brandForm.errors.logo_path}
                />
              </Field>

              <Field label="Invoice Footer Text" htmlFor="brand_footer" error={brandForm.errors.footer_text} hint="Appears at the bottom of every invoice PDF">
                <Input
                  id="brand_footer"
                  value={brandForm.data.footer_text}
                  onChange={e => brandForm.setData('footer_text', e.target.value)}
                  placeholder="Thank you for your business. Payment due within 30 days."
                  error={brandForm.errors.footer_text}
                />
              </Field>

              {canManageBrand && (
                <HStack gap={2}>
                  <Button
                    intent="primary"
                    loading={savingBrand}
                    disabled={savingBrand}
                    onClick={saveBranding}
                  >
                    Save Branding
                  </Button>
                </HStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Templates */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3}>
                <Eyebrow>Invoice Templates</Eyebrow>
                {canManageTpl && (
                  <Button intent="soft" size="sm" leftIcon="plus" onClick={openCreateTpl}>
                    Add Template
                  </Button>
                )}
              </HStack>
              <DataTable
                columns={tplColumns}
                rows={templates ?? []}
                empty="No templates configured. The system will use the built-in default."
              />
            </VStack>
          </CardBody>
        </Card>

      </VStack>

      {/* Template Drawer */}
      <Modal
        open={tplDrawerOpen}
        onClose={() => { setTplDrawerOpen(false); tplForm.reset(); }}
        title={editTpl ? 'Edit Template' : 'New Invoice Template'}
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setTplDrawerOpen(false); tplForm.reset(); }}>
              Cancel
            </Button>
            <Button intent="primary" loading={savingTpl} disabled={savingTpl} onClick={saveTpl}>
              {editTpl ? 'Update Template' : 'Create Template'}
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Template Name" htmlFor="tpl_name" error={tplForm.errors.name} required>
            <Input
              id="tpl_name"
              value={tplForm.data.name}
              onChange={e => tplForm.setData('name', e.target.value)}
              placeholder="Standard Invoice"
              error={tplForm.errors.name}
            />
          </Field>

          <Field label="HTML Body" htmlFor="tpl_body" error={tplForm.errors.html_body} hint="Liquid/Blade template. Use {{ invoice.number }}, {{ tenant.name }}, etc.">
            <Input
              id="tpl_body"
              value={tplForm.data.html_body}
              onChange={e => tplForm.setData('html_body', e.target.value)}
              placeholder="<html>...</html>"
              error={tplForm.errors.html_body}
            />
          </Field>

          <Toggle
            label="Set as default template"
            checked={tplForm.data.is_default}
            onChange={e => tplForm.setData('is_default', e.target.checked)}
          />
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

InvoicingSettings.layout = page => <App title="Invoice Settings">{page}</App>;
