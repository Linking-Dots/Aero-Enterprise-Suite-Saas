/**
 * Notification Templates (CA-3) — admin CRUD for system / transactional
 * notification templates. Backed by EmailTemplateService.
 */
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable, Pagination,
  Button, Badge,
  Modal,
  Field, Input,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

const CATEGORIES = [
  { value: 'transactional', label: 'Transactional' },
  { value: 'system', label: 'System' },
  { value: 'marketing', label: 'Marketing' },
  { value: 'notification', label: 'Notification' },
];

export default function NotificationTemplates({ templates = [], categories = [] }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.notifications.templates.create');
  const canEdit = useHRMAC('core.notifications.templates.edit');
  const canDelete = useHRMAC('core.notifications.templates.delete');

  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);

  const categoryOptions = categories?.length ? categories.map(c => ({
    value: typeof c === 'string' ? c : c.value,
    label: typeof c === 'string' ? c : c.label,
  })) : CATEGORIES;

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    slug: '',
    subject: '',
    body_html: '',
    category: 'transactional',
    is_active: true,
  });

  const openCreate = () => {
    reset();
    setData({
      name: '', slug: '', subject: '', body_html: '',
      category: 'transactional', is_active: true,
    });
    setEditing(null);
    setModalOpen(true);
  };

  const openEdit = (t) => {
    setData({
      name: t.name ?? '',
      slug: t.slug ?? t.name ?? '',
      subject: t.subject ?? '',
      body_html: t.body_html ?? t.body ?? '',
      category: t.category ?? 'transactional',
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
      onError: () => toast.error('Failed to delete template.'),
    });
  };

  const handlePreview = (t) => {
    window.open(route('admin.notifications.templates.preview', t.id), '_blank', 'noopener');
  };

  const rows = Array.isArray(templates) ? templates : (templates?.data ?? []);

  const columns = [
    { key: 'name', label: 'Name', width: '20%', render: (r) => <Text weight="semibold">{r.name}</Text> },
    {
      key: 'slug',
      label: 'Slug',
      width: '20%',
      render: (r) => <code style={{ fontSize: 12, background: 'var(--surface-muted)', padding: '2px 6px', borderRadius: 3 }}>{r.slug ?? '—'}</code>,
    },
    { key: 'subject', label: 'Subject', width: '25%' },
    {
      key: 'category',
      label: 'Category',
      width: '12%',
      render: (r) => <Badge>{r.category ?? '—'}</Badge>,
    },
    {
      key: 'is_active',
      label: 'Status',
      width: '10%',
      render: (r) => (
        <Badge intent={r.is_active ? 'success' : 'neutral'}>
          {r.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '13%',
      align: 'right',
      render: (r) => (
        <HStack gap={2} justify="end">
          <Button size="sm" variant="secondary" onClick={() => handlePreview(r)}>
            Preview
          </Button>
          {canEdit && (
            <Button size="sm" variant="secondary" onClick={() => openEdit(r)}>
              Edit
            </Button>
          )}
          {canDelete && (
            <Button size="sm" variant="danger" onClick={() => handleDelete(r.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Notification Templates"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Notification Templates' },
        ]}
        description="Manage notification and email content templates."
        actions={
          canCreate && (
            <Button variant="primary" onClick={openCreate}>
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
            <Field label="Name" error={errors.name}>
              <Input
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                required
              />
            </Field>

            <Field label="Slug" error={errors.slug} hint="e.g. leave-approved">
              <Input
                value={data.slug}
                onChange={(e) => setData('slug', e.target.value)}
                required
              />
            </Field>

            <Field label="Subject" error={errors.subject} hint="Supports {{variables}}">
              <Input
                value={data.subject}
                onChange={(e) => setData('subject', e.target.value)}
                required
              />
            </Field>

            <Field label="Category" error={errors.category}>
              <select
                value={data.category}
                onChange={(e) => setData('category', e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                }}
              >
                {categoryOptions.map((c) => (
                  <option key={c.value} value={c.value}>{c.label}</option>
                ))}
              </select>
            </Field>

            <Field label="HTML Body" error={errors.body_html}>
              <textarea
                value={data.body_html}
                onChange={(e) => setData('body_html', e.target.value)}
                rows={10}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  borderRadius: 4,
                  border: '1px solid var(--border)',
                  background: 'var(--surface)',
                  color: 'var(--text)',
                  fontFamily: 'monospace',
                  fontSize: 13,
                }}
                required
              />
            </Field>

            <HStack gap={3} justify="end">
              <Button type="button" variant="secondary" onClick={() => setModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" variant="primary" loading={processing}>
                {editing ? 'Save' : 'Create'}
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

NotificationTemplates.layout = (page) => (
  <App title="Notification Templates">{page}</App>
);
