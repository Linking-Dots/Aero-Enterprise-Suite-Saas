/**
 * Notification Templates (CA-3) — admin CRUD for system / transactional
 * notification templates. Backed by EmailTemplateService.
 */
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button, Badge,
  Modal,
  Field, Input, Select, Toggle, Mono,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const DEFAULT_CATEGORIES = [
  { value: 'transactional', label: 'Transactional' },
  { value: 'system',        label: 'System' },
  { value: 'marketing',     label: 'Marketing' },
  { value: 'notification',  label: 'Notification' },
];

const CATEGORY_INTENT = {
  system:        'neutral',
  transactional: 'info',
  marketing:     'amber',
  notification:  'success',
};

export default function NotificationTemplates({ templates = [], categories = [] }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.notifications.templates.create');
  const canEdit   = useHRMAC('core.notifications.templates.edit');
  const canDelete = useHRMAC('core.notifications.templates.delete');

  const [modalOpen, setModalOpen] = useState(false);
  const [editing,   setEditing]   = useState(null);

  const categoryOptions = categories?.length
    ? categories.map(c => (typeof c === 'string' ? { value: c, label: c } : c))
    : DEFAULT_CATEGORIES;

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name:      '',
    slug:      '',
    subject:   '',
    body_html: '',
    category:  'transactional',
    is_active: true,
  });

  const openCreate = () => {
    reset();
    setData({ name: '', slug: '', subject: '', body_html: '', category: 'transactional', is_active: true });
    setEditing(null);
    setModalOpen(true);
  };

  const openEdit = (t) => {
    setData({
      name:      t.name      ?? '',
      slug:      t.slug      ?? t.name ?? '',
      subject:   t.subject   ?? '',
      body_html: t.body_html ?? t.body ?? '',
      category:  t.category  ?? 'transactional',
      is_active: t.is_active ?? true,
    });
    setEditing(t);
    setModalOpen(true);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const opts = {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(editing ? 'Template updated.' : 'Template created.');
        setModalOpen(false);
        reset();
      },
      onError: () => toast.error('Failed to save template.'),
    };
    if (editing) {
      put(route('admin.notifications.templates.update', editing.id), opts);
    } else {
      post(route('admin.notifications.templates.store'), opts);
    }
  };

  const handleDelete = (id) => {
    if (!confirm('Delete this template? This action cannot be undone.')) return;
    router.delete(route('admin.notifications.templates.destroy', id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Template deleted.'),
      onError:   () => toast.error('Failed to delete template.'),
    });
  };

  const handlePreview = (t) => {
    window.open(route('admin.notifications.templates.preview', t.id), '_blank', 'noopener');
  };

  const rows = Array.isArray(templates) ? templates : (templates?.data ?? []);

  const columns = [
    {
      key: 'name', label: 'Name', width: '20%',
      render: r => <Text size="sm">{r.name}</Text>,
    },
    {
      key: 'slug', label: 'Slug', width: '20%',
      render: r => <Mono tone="secondary">{r.slug ?? '—'}</Mono>,
    },
    { key: 'subject', label: 'Subject', width: '25%' },
    {
      key: 'category', label: 'Category', width: '12%',
      render: r => (
        <Badge intent={CATEGORY_INTENT[r.category] ?? 'neutral'}>
          {r.category ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'is_active', label: 'Status', width: '10%',
      render: r => (
        <Badge intent={r.is_active ? 'success' : 'neutral'}>
          {r.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '13%', align: 'right',
      render: r => (
        <HStack gap={2} justify="end">
          <Button intent="soft" size="sm" onClick={() => handlePreview(r)}>
            Preview
          </Button>
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(r)}>
              Edit
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(r.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <style>{`
        .tpl-textarea {
          width: 100%;
          padding: 8px 12px;
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
          background: var(--aeos-bg-surface);
          color: var(--aeos-text-primary);
          font-family: var(--aeos-font-mono);
          font-size: 0.8125rem;
          line-height: 1.6;
          resize: vertical;
        }
        .tpl-textarea:focus {
          outline: 2px solid var(--aeos-primary);
          outline-offset: -1px;
        }
      `}</style>

      <IndexPageLayout
        title="Notification Templates"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Notification Templates' },
        ]}
        description="Manage notification and email content templates."
        actions={
          canCreate && (
            <Button intent="primary" onClick={openCreate}>
              New Template
            </Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={rows}
            empty="No templates yet. Create your first one to get started."
          />
        }
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Edit Template' : 'New Template'}
        size="lg"
      >
        <form onSubmit={handleSubmit}>
          <VStack gap={4}>

            <Field label="Name" htmlFor="tpl_name" error={errors.name} required>
              <Input
                id="tpl_name"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                error={Boolean(errors.name)}
              />
            </Field>

            <Field label="Slug" htmlFor="tpl_slug" error={errors.slug} hint="e.g. leave-approved">
              <Input
                id="tpl_slug"
                value={data.slug}
                onChange={e => setData('slug', e.target.value)}
                error={Boolean(errors.slug)}
              />
            </Field>

            <Field label="Subject" htmlFor="tpl_subject" error={errors.subject} hint="Supports {{variables}}" required>
              <Input
                id="tpl_subject"
                value={data.subject}
                onChange={e => setData('subject', e.target.value)}
                error={Boolean(errors.subject)}
              />
            </Field>

            <Field label="Category" htmlFor="tpl_category" error={errors.category}>
              <Select
                options={categoryOptions}
                value={data.category}
                onChange={e => setData('category', e.target.value)}
              />
            </Field>

            <Field label="HTML Body" htmlFor="tpl_body_html" error={errors.body_html} required>
              <textarea
                id="tpl_body_html"
                className="tpl-textarea"
                value={data.body_html}
                onChange={e => setData('body_html', e.target.value)}
                rows={10}
              />
            </Field>

            <Toggle
              label="Active"
              checked={data.is_active}
              onChange={v => setData('is_active', v)}
            />

            <HStack gap={3} justify="end">
              <Button type="button" intent="ghost" onClick={() => setModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" intent="primary" loading={processing}>
                {editing ? 'Save Changes' : 'Create Template'}
              </Button>
            </HStack>

          </VStack>
        </form>
      </Modal>
    </>
  );
}

NotificationTemplates.layout = page => (
  <App title="Notification Templates">{page}</App>
);
