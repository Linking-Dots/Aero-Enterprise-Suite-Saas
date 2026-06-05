import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
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
  Toggle,
  Modal,
  Drawer,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const CATEGORY_OPTIONS = [
  { value: 'getting-started', label: 'Getting Started' },
  { value: 'billing',         label: 'Billing' },
  { value: 'hrm',             label: 'HRM' },
  { value: 'integrations',    label: 'Integrations' },
  { value: 'troubleshooting', label: 'Troubleshooting' },
  { value: 'other',           label: 'Other' },
];

export default function Articles({ articles, filters }) {
  const toast = useToast();
  const canCreate  = useHRMAC('help-center.kb-articles.create');
  const canUpdate  = useHRMAC('help-center.kb-articles.update');
  const canPublish = useHRMAC('help-center.kb-articles.publish');
  const canDelete  = useHRMAC('help-center.kb-articles.delete');

  const [drawerOpen,    setDrawerOpen]    = useState(false);
  const [editTarget,    setEditTarget]    = useState(null);
  const [deleteTarget,  setDeleteTarget]  = useState(null);

  const form = useForm({
    title:        '',
    slug:         '',
    category:     'getting-started',
    tags:         '',
    body_md:      '',
    is_published: false,
  });

  function openCreate() {
    setEditTarget(null);
    form.reset();
    setDrawerOpen(true);
  }

  function openEdit(row) {
    setEditTarget(row);
    form.setData({
      title:        row.title,
      slug:         row.slug,
      category:     row.category,
      tags:         (row.tags ?? []).join(', '),
      body_md:      row.body_md ?? '',
      is_published: row.is_published,
    });
    setDrawerOpen(true);
  }

  function handleSave(e) {
    e.preventDefault();
    if (editTarget) {
      form.put(route('platform.admin.help-center.articles.update', editTarget.id), {
        preserveState: true,
        onSuccess: () => { toast.success('Article updated.'); setDrawerOpen(false); },
        onError:   () => toast.error('Update failed.'),
      });
    } else {
      form.post(route('platform.admin.help-center.articles.store'), {
        preserveState: true,
        onSuccess: () => { toast.success('Article created.'); setDrawerOpen(false); form.reset(); },
        onError:   () => toast.error('Create failed.'),
      });
    }
  }

  function handlePublishToggle(row) {
    const action = row.is_published
      ? route('platform.admin.help-center.articles.unpublish', row.id)
      : route('platform.admin.help-center.articles.publish', row.id);
    router.post(action, {}, {
      preserveState: true,
      onSuccess: () => toast.success(row.is_published ? 'Article unpublished.' : 'Article published.'),
      onError:   () => toast.error('Toggle failed.'),
    });
  }

  function confirmDelete() {
    if (!deleteTarget) return;
    router.delete(route('platform.admin.help-center.articles.destroy', deleteTarget.id), {
      preserveState: true,
      onSuccess: () => { toast.success('Article deleted.'); setDeleteTarget(null); },
      onError:   () => toast.error('Delete failed.'),
    });
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: r => <Text>{r.title}</Text>,
    },
    {
      key: 'category',
      label: 'Category',
      render: r => <Badge intent="neutral">{r.category}</Badge>,
    },
    {
      key: 'status',
      label: 'Status',
      render: r => (
        <Badge intent={r.is_published ? 'success' : 'neutral'}>
          {r.is_published ? 'Published' : 'Draft'}
        </Badge>
      ),
    },
    {
      key: 'view_count',
      label: 'Views',
      render: r => <Text tone="secondary">{r.view_count ?? 0}</Text>,
    },
    {
      key: 'published_at',
      label: 'Published',
      render: r => <Mono>{r.published_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canUpdate && (
            <Button intent="ghost" size="sm" onClick={() => openEdit(r)}>
              Edit
            </Button>
          )}
          {canPublish && (
            <Button intent="soft" size="sm" onClick={() => handlePublishToggle(r)}>
              {r.is_published ? 'Unpublish' : 'Publish'}
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => setDeleteTarget(r)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="KB Articles"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Help Center' },
        { label: 'Articles' },
      ]}
      description="Manage knowledge base articles for tenant self-service support."
      actions={
        canCreate && (
          <Button intent="primary" leftIcon="plus" onClick={openCreate}>
            New Article
          </Button>
        )
      }
    >
      <VStack gap={4}>
        <DataTable
          columns={columns}
          rows={articles?.data ?? []}
          empty="No articles yet."
        />

        {(articles?.last_page ?? 1) > 1 && (
          <Pagination
            page={articles.current_page}
            total={articles.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.help-center.articles.index'),
                { page },
                { preserveState: true, preserveScroll: true, only: ['articles'] }
              )
            }
          />
        )}
      </VStack>

      {/* Create / Edit Drawer */}
      <Drawer
        open={drawerOpen}
        onClose={() => { setDrawerOpen(false); form.reset(); }}
        title={editTarget ? 'Edit Article' : 'New Article'}
      >
        <VStack gap={4}>
          <Field label="Title" htmlFor="art_title" error={form.errors.title} required>
            <Input
              id="art_title"
              value={form.data.title}
              onChange={e => form.setData('title', e.target.value)}
              placeholder="Article title"
              error={form.errors.title}
            />
          </Field>

          <Field label="Slug" htmlFor="art_slug" error={form.errors.slug} required>
            <Input
              id="art_slug"
              value={form.data.slug}
              onChange={e => form.setData('slug', e.target.value)}
              placeholder="url-friendly-slug"
              leftIcon="link"
              error={form.errors.slug}
            />
          </Field>

          <Field label="Category" htmlFor="art_cat" error={form.errors.category} required>
            <Select
              id="art_cat"
              value={form.data.category}
              onChange={e => form.setData('category', e.target.value)}
              options={CATEGORY_OPTIONS}
            />
          </Field>

          <Field label="Tags (comma-separated)" htmlFor="art_tags" error={form.errors.tags}>
            <Input
              id="art_tags"
              value={form.data.tags}
              onChange={e => form.setData('tags', e.target.value)}
              placeholder="e.g. onboarding, setup, users"
              error={form.errors.tags}
            />
          </Field>

          <Field label="Body (Markdown)" htmlFor="art_body" error={form.errors.body_md} required>
            <Input
              id="art_body"
              value={form.data.body_md}
              onChange={e => form.setData('body_md', e.target.value)}
              placeholder="Write your article content in Markdown..."
              error={form.errors.body_md}
            />
          </Field>

          <Toggle
            label="Published"
            checked={form.data.is_published}
            onChange={e => form.setData('is_published', e.target.checked)}
          />

          <HStack gap={2}>
            <Button
              intent="primary"
              loading={form.processing}
              disabled={form.processing}
              onClick={handleSave}
            >
              {editTarget ? 'Update' : 'Create'}
            </Button>
            <Button intent="ghost" type="button" onClick={() => { setDrawerOpen(false); form.reset(); }}>
              Cancel
            </Button>
          </HStack>
        </VStack>
      </Drawer>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        title="Delete Article"
        description={`Permanently delete "${deleteTarget?.title}"? This cannot be undone.`}
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={confirmDelete}>
              Delete
            </Button>
            <Button intent="ghost" onClick={() => setDeleteTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      />
    </IndexPageLayout>
  );
}

Articles.layout = page => <App title="KB Articles">{page}</App>;
