/**
 * Email Templates — Tenant email template manager.
 *
 * Uses @aero/ui IndexPageLayout with a table of templates and
 * a modal editor for create/update. Templates flagged is_locked
 * cannot be deleted or have their slug changed.
 */
import { useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import {
  IndexPageLayout,
  Field, Input, Textarea,
  Button, Card, CardHeader, CardBody,
  HStack, VStack, Text,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function EmailTemplates({ templates = [] }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.settings.email_templates.create');
  const canEdit = useHRMAC('core.settings.email_templates.edit');
  const canDelete = useHRMAC('core.settings.email_templates.delete');

  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState(null);

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    slug: '',
    subject: '',
    body_html: '',
    category: 'system',
    is_active: true,
  });

  const openCreate = () => {
    reset();
    setEditing(null);
    setOpen(true);
  };

  const openEdit = (template) => {
    setData({
      name: template.name,
      slug: template.slug,
      subject: template.subject,
      body_html: template.body_html,
      category: template.category,
      is_active: !!template.is_active,
    });
    setEditing(template);
    setOpen(true);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const onSuccess = () => {
      toast.success(editing ? 'Template updated.' : 'Template created.');
      reset();
      setOpen(false);
    };
    const onError = () => toast.error('Failed to save template.');

    if (editing) {
      put(route('core.settings.email-templates.update', editing.id), {
        preserveScroll: true,
        onSuccess,
        onError,
      });
    } else {
      post(route('core.settings.email-templates.store'), {
        preserveScroll: true,
        onSuccess,
        onError,
      });
    }
  };

  const handleDelete = (template) => {
    if (!confirm(`Delete template "${template.name}"?`)) return;
    router.delete(route('core.settings.email-templates.destroy', template.id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Template deleted.'),
      onError: () => toast.error('Failed to delete template.'),
    });
  };

  const modalFooter = (
    <HStack gap={3} justify="end">
      <Button type="button" variant="secondary" onClick={() => setOpen(false)}>
        Cancel
      </Button>
      <Button type="submit" form="email-template-form" variant="primary" loading={processing}>
        {editing ? 'Save Changes' : 'Create Template'}
      </Button>
    </HStack>
  );

  return (
    <>
      <IndexPageLayout
        title="Email Templates"
        breadcrumb={[
          { label: 'Settings', href: route('core.settings.system.index') },
          { label: 'Email Templates' },
        ]}
        description="Manage transactional and system email templates for your tenant."
        actions={
          canCreate && (
            <Button variant="primary" onClick={openCreate}>
              New Template
            </Button>
          )
        }
      >
        <Card>
          <CardHeader>
            <Text weight="semibold">Templates ({templates.length})</Text>
          </CardHeader>
          <CardBody>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ borderBottom: '1px solid var(--border)' }}>
                  <th style={{ textAlign: 'left', padding: 8 }}>Name</th>
                  <th style={{ textAlign: 'left', padding: 8 }}>Slug</th>
                  <th style={{ textAlign: 'left', padding: 8 }}>Category</th>
                  <th style={{ textAlign: 'left', padding: 8 }}>Active</th>
                  <th style={{ textAlign: 'right', padding: 8 }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {templates.length === 0 && (
                  <tr>
                    <td colSpan={5} style={{ padding: 16, textAlign: 'center' }}>
                      <Text color="muted">No email templates yet.</Text>
                    </td>
                  </tr>
                )}
                {templates.map((t) => (
                  <tr key={t.id} style={{ borderBottom: '1px solid var(--border)' }}>
                    <td style={{ padding: 8 }}>{t.name}</td>
                    <td style={{ padding: 8, fontFamily: 'monospace', fontSize: 12 }}>{t.slug}</td>
                    <td style={{ padding: 8 }}>{t.category}</td>
                    <td style={{ padding: 8 }}>{t.is_active ? 'Yes' : 'No'}</td>
                    <td style={{ padding: 8, textAlign: 'right' }}>
                      <HStack gap={2} justify="end">
                        <Button
                          variant="secondary"
                          size="sm"
                          as="a"
                          href={route('core.settings.email-templates.preview', t.id)}
                          target="_blank"
                        >
                          Preview
                        </Button>
                        {!t.is_locked && canEdit && (
                          <Button variant="secondary" size="sm" onClick={() => openEdit(t)}>
                            Edit
                          </Button>
                        )}
                        {!t.is_locked && canDelete && (
                          <Button variant="danger" size="sm" onClick={() => handleDelete(t)}>
                            Delete
                          </Button>
                        )}
                      </HStack>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardBody>
        </Card>
      </IndexPageLayout>

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
                onChange={(e) => setData('name', e.target.value)}
                placeholder="Welcome Email"
              />
            </Field>
            <Field label="Slug" error={errors.slug} required>
              <Input
                value={data.slug}
                onChange={(e) => setData('slug', e.target.value)}
                placeholder="welcome-email"
                disabled={editing?.is_locked}
              />
            </Field>
            <Field label="Subject" error={errors.subject} required>
              <Input
                value={data.subject}
                onChange={(e) => setData('subject', e.target.value)}
                placeholder="Welcome to {{ company_name }}"
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
                <option value="system">System</option>
                <option value="transactional">Transactional</option>
                <option value="marketing">Marketing</option>
              </select>
            </Field>
            <Field label="HTML Body" error={errors.body_html} required>
              <Textarea
                value={data.body_html}
                onChange={(e) => setData('body_html', e.target.value)}
                rows={10}
                style={{ fontFamily: 'monospace' }}
                placeholder="<h1>Hello {{ name }}</h1>"
              />
            </Field>
            <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <input
                type="checkbox"
                checked={data.is_active}
                onChange={(e) => setData('is_active', e.target.checked)}
              />
              <Text size="sm">Active</Text>
            </label>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

EmailTemplates.layout = (page) => <App title="Email Templates">{page}</App>;
