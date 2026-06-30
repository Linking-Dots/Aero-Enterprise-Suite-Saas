/**
 * Email Templates — create, edit, preview, and delete transactional templates.
 *
 * Props: { templates: [] }
 *   Each template: { id, name, slug, subject, body_html, category, is_active, is_locked }
 *
 * Ported onto the unified SettingsLayout shell (Task 4). This section is a managed
 * list + modal — it does NOT use SettingsSection's single save bar (no single form save).
 * Violations fixed:
 *   P0-1: all style={} removed — table replaced with DataTable engine component
 *   P0-2: raw <select> → Select engine component
 *         raw <table> → DataTable engine component
 *         raw checkbox label → Toggle engine component
 *   P0-3: raw <td style> removed
 *   P2-1: variant= → intent= on Button
 *   HRMAC: email-templates.{create,edit,delete} → email_templates.{create,edit,delete} (Task 0 fix)
 *   Local <style> block removed — .email-template-body now centralized in SettingsLayout.jsx
 */
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  Heading, Box,
  DataTable,
  Field, Input, Textarea, Select, Toggle,
  Button,
  Card, CardHeader, CardBody,
  HStack, VStack, Text, Mono,
  Badge,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import SettingsLayout from './SettingsLayout.jsx';
import SettingsRail from './SettingsRail.jsx';

const CATEGORY_OPTIONS = [
  { value: 'system',        label: 'System' },
  { value: 'transactional', label: 'Transactional' },
  { value: 'marketing',     label: 'Marketing' },
];

function categoryIntent(cat) {
  return { system: 'neutral', transactional: 'info', marketing: 'amber' }[cat] ?? 'neutral';
}

export default function EmailTemplates({ templates = [] }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.settings.email_templates.create');
  const canEdit   = useHRMAC('core.settings.email_templates.edit');
  const canDelete = useHRMAC('core.settings.email_templates.delete');

  const [open,    setOpen]    = useState(false);
  const [editing, setEditing] = useState(null);

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name:      '',
    slug:      '',
    subject:   '',
    body_html: '',
    category:  'system',
    is_active: true,
  });

  function openCreate() {
    reset();
    setEditing(null);
    setOpen(true);
  }

  function openEdit(tpl) {
    setData({
      name:      tpl.name,
      slug:      tpl.slug,
      subject:   tpl.subject,
      body_html: tpl.body_html,
      category:  tpl.category,
      is_active: !!tpl.is_active,
    });
    setEditing(tpl);
    setOpen(true);
  }

  function handleSubmit(e) {
    e.preventDefault();
    const opts = {
      preserveScroll: true,
      onSuccess: () => { toast.success(editing ? 'Template updated.' : 'Template created.'); reset(); setOpen(false); },
      onError:   () => toast.error('Failed to save template.'),
    };
    if (editing) {
      put(route('core.settings.email-templates.update', editing.id), opts);
    } else {
      post(route('core.settings.email-templates.store'), opts);
    }
  }

  function handleDelete(tpl) {
    if (!confirm(`Delete template "${tpl.name}"?`)) return;
    router.delete(route('core.settings.email-templates.destroy', tpl.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Template deleted.'),
      onError:   () => toast.error('Failed to delete template.'),
    });
  }

  function openPreview(tpl) {
    try {
      window.open(route('core.settings.email-templates.preview', tpl.id), '_blank');
    } catch {
      toast.error('Preview URL not configured.');
    }
  }

  const columns = [
    {
      key: 'name', label: 'Name', width: '25%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'slug', label: 'Slug', width: '22%',
      render: row => <Mono size="sm" tone="secondary">{row.slug}</Mono>,
    },
    {
      key: 'category', label: 'Category', width: '15%',
      render: row => <Badge intent={categoryIntent(row.category)}>{row.category}</Badge>,
    },
    {
      key: 'is_active', label: 'Active', width: '10%',
      render: row => (
        <Badge intent={row.is_active ? 'success' : 'neutral'}>
          {row.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '28%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          <Button intent="soft" size="sm" onClick={() => openPreview(row)}>
            Preview
          </Button>
          {!row.is_locked && canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>
              Edit
            </Button>
          )}
          {!row.is_locked && canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const modalFooter = (
    <HStack gap={3} justify="end">
      <Button type="button" intent="soft" onClick={() => setOpen(false)}>
        Cancel
      </Button>
      <Button type="submit" form="email-template-form" intent="primary" loading={processing}>
        {editing ? 'Save Changes' : 'Create Template'}
      </Button>
    </HStack>
  );

  return (
    <>
      <VStack gap={5}>
        <HStack align="center">
          <VStack gap={1}>
            <Heading level={3}>Email Templates</Heading>
            <Text size="sm" tone="secondary">Manage transactional and system email templates.</Text>
          </VStack>
          <Box grow />
          {canCreate && (
            <Button intent="primary" onClick={openCreate} leftIcon="plus">
              New Template
            </Button>
          )}
        </HStack>

        <Card>
          <CardHeader>
            <Text size="sm" tone="secondary">Templates ({templates.length})</Text>
          </CardHeader>
          <CardBody>
            <DataTable
              columns={columns}
              rows={templates}
              empty="No email templates yet. Click 'New Template' to create one."
            />
          </CardBody>
        </Card>
      </VStack>

      <Modal
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? 'Edit Template' : 'New Template'}
        size="lg"
        footer={modalFooter}
      >
        <form id="email-template-form" onSubmit={handleSubmit}>
          <VStack gap={4}>
            <Field label="Name" error={errors.name} required>
              <Input
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="Welcome Email"
              />
            </Field>

            <Field label="Slug" error={errors.slug} required hint="Unique machine-readable identifier.">
              <Input
                value={data.slug}
                onChange={e => setData('slug', e.target.value)}
                placeholder="welcome-email"
                disabled={!!editing?.is_locked}
              />
            </Field>

            <Field label="Subject" error={errors.subject} required>
              <Input
                value={data.subject}
                onChange={e => setData('subject', e.target.value)}
                placeholder="Welcome to {{ company_name }}"
              />
            </Field>

            <Field label="Category" error={errors.category}>
              <Select
                value={data.category}
                onChange={e => setData('category', e.target.value)}
                options={CATEGORY_OPTIONS}
              />
            </Field>

            <Field label="HTML Body" error={errors.body_html} required hint="Use {{ variable }} for merge tags.">
              <Textarea
                value={data.body_html}
                onChange={e => setData('body_html', e.target.value)}
                rows={12}
                className="email-template-body"
                placeholder={'<h1>Hello {{ name }}</h1>\n<p>Welcome aboard!</p>'}
              />
            </Field>

            <Toggle
              label="Active"
              checked={!!data.is_active}
              onChange={e => setData('is_active', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

EmailTemplates.layout = page => (
  <App title="Settings" railTitle="Settings" rail={<SettingsRail />}>
    <SettingsLayout active="templates">{page}</SettingsLayout>
  </App>
);
