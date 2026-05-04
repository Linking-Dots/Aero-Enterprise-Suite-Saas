import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Input,
  HStack, VStack, Stack,
  Text, Heading,
  Badge,
  Button,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function TagsIndex({ tags, counts, filters }) {
  const toast = useToast();
  const canCreate = useHRMAC('core.tags_labels.tag_management.create');
  const canEdit = useHRMAC('core.tags_labels.tag_management.update');
  const canDelete = useHRMAC('core.tags_labels.tag_management.delete');

  const [search, setSearch] = useState(filters.search || '');
  const [showCreate, setShowCreate] = useState(false);
  const [editingTag, setEditingTag] = useState(null);
  const [deletingTag, setDeletingTag] = useState(null);
  const [mergingTag, setMergingTag] = useState(null);
  const [mergeTargetId, setMergeTargetId] = useState('');

  const form = useForm({
    name: '',
    color: '#0ea5e9',
    description: '',
  });

  const mergeForm = useForm({
    source_tag_id: '',
    target_tag_id: '',
  });

  const handleSearch = (value) => {
    setSearch(value);
    router.get(route('core.tags.index'), { search: value }, {
      preserveState: true,
      preserveScroll: true,
      only: ['tags', 'filters'],
    });
  };

  const resetForm = () => {
    form.reset();
    setShowCreate(false);
    setEditingTag(null);
  };

  const handleCreate = (e) => {
    e.preventDefault();
    form.post(route('core.tags.store'), {
      onSuccess: () => {
        toast.success('Tag created successfully');
        resetForm();
      },
      onError: (errors) => {
        toast.error(errors.name || 'Failed to create tag');
      },
    });
  };

  const handleUpdate = (e) => {
    e.preventDefault();
    form.put(route('core.tags.update', editingTag.id), {
      onSuccess: () => {
        toast.success('Tag updated successfully');
        resetForm();
      },
      onError: (errors) => {
        toast.error(errors.name || 'Failed to update tag');
      },
    });
  };

  const handleDelete = () => {
    if (!deletingTag) return;
    router.delete(route('core.tags.destroy', deletingTag.id), {
      onSuccess: () => {
        toast.success(`Tag "${deletingTag.name}" deleted`);
        setDeletingTag(null);
      },
      onError: () => {
        toast.error('Failed to delete tag');
      },
    });
  };

  const handleMerge = (e) => {
    e.preventDefault();
    if (!mergingTag || !mergeTargetId) return;
    mergeForm.setData('source_tag_id', mergingTag.id);
    mergeForm.setData('target_tag_id', mergeTargetId);
    mergeForm.post(route('core.tags.merge'), {
      onSuccess: () => {
        toast.success(`Tag "${mergingTag.name}" merged successfully`);
        setMergingTag(null);
        setMergeTargetId('');
        mergeForm.reset();
      },
      onError: (errors) => {
        toast.error(errors.message || 'Failed to merge tags');
      },
    });
  };

  const handleExport = () => {
    window.location.href = route('core.tags.export');
  };

  const handleImport = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const data = new FormData();
    data.append('file', file);
    router.post(route('core.tags.import'), data, {
      onSuccess: (page) => {
        toast.success(page.props.flash?.success || 'Import completed');
        e.target.value = '';
      },
      onError: (errors) => {
        toast.error(errors.message || 'Import failed');
        e.target.value = '';
      },
    });
  };

  const openCreate = () => {
    form.reset();
    setShowCreate(true);
  };

  const openEdit = (tag) => {
    setEditingTag(tag);
    form.setData({
      name: tag.name,
      color: tag.color || '#0ea5e9',
      description: tag.description || '',
    });
  };

  const tagList = tags?.data || [];
  const totalTags = tags?.total || 0;

  return (
    <IndexPageLayout
      title="Tags & Labels"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Tags & Labels' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('core.tags.trashed'))}
          >
            Deleted Tags
          </Button>
          {canCreate && (
            <>
              <Button intent="ghost" size="sm" onClick={handleExport}>
                Export
              </Button>
              <Button intent="ghost" size="sm" as="label">
                Import
                <input
                  type="file"
                  accept=".csv,.txt"
                  style={{ display: 'none' }}
                  onChange={handleImport}
                />
              </Button>
            </>
          )}
          {canCreate && (
            <Button intent="primary" size="sm" onClick={openCreate}>
              New Tag
            </Button>
          )}
        </HStack>
      }
      filters={
        <HStack gap={2} align="center">
          <Input
            type="search"
            placeholder="Search tags..."
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            leftIcon="search"
          />
        </HStack>
      }
      table={
        <VStack gap={3}>
          {tagList.length === 0 && (
            <Card>
              <CardBody>
                <Text tone="muted" align="center">
                  {search ? 'No tags match your search.' : 'No tags yet. Create your first tag to get started.'}
                </Text>
              </CardBody>
            </Card>
          )}

          <div
            style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
              gap: '16px',
            }}
          >
            {tagList.map((tag) => (
              <Card key={tag.id}>
                <CardHeader
                  action={
                    <HStack gap={1}>
                      {canEdit && (
                        <Button
                          intent="ghost"
                          size="sm"
                          onClick={() => openEdit(tag)}
                          aria-label={`Edit ${tag.name}`}
                        >
                          Edit
                        </Button>
                      )}
                      {canEdit && tagList.length > 1 && (
                        <Button
                          intent="ghost"
                          size="sm"
                          onClick={() => { setMergingTag(tag); setMergeTargetId(''); }}
                          aria-label={`Merge ${tag.name}`}
                        >
                          Merge
                        </Button>
                      )}
                      {canDelete && (
                        <Button
                          intent="danger"
                          size="sm"
                          onClick={() => setDeletingTag(tag)}
                          aria-label={`Delete ${tag.name}`}
                        >
                          Delete
                        </Button>
                      )}
                    </HStack>
                  }
                >
                  <HStack gap={2} align="center">
                    <span
                      style={{
                        width: '14px',
                        height: '14px',
                        borderRadius: '50%',
                        background: tag.color || '#0ea5e9',
                        flexShrink: 0,
                      }}
                    />
                    <Heading level={5} style={{ margin: 0 }}>
                      {tag.name}
                    </Heading>
                  </HStack>
                </CardHeader>
                <CardBody>
                  <Stack gap={2}>
                    {tag.description && (
                      <Text size="sm" tone="muted">
                        {tag.description}
                      </Text>
                    )}
                    <HStack gap={2} align="center">
                      <Badge intent="neutral" size="sm">
                        {tag.records_count ?? 0} records
                      </Badge>
                      <Text size="xs" tone="tertiary">
                        #{tag.slug}
                      </Text>
                    </HStack>
                  </Stack>
                </CardBody>
              </Card>
            ))}
          </div>
        </VStack>
      }
      pagination={
        tags?.links && (
          <HStack gap={2} align="center" justify="center">
            {tags.links.map((link, i) => (
              <Button
                key={i}
                intent={link.active ? 'primary' : 'ghost'}
                size="sm"
                disabled={!link.url}
                onClick={() => {
                  if (link.url) router.get(link.url, {}, { preserveState: true });
                }}
                dangerouslySetInnerHTML={{ __html: link.label }}
              />
            ))}
          </HStack>
        )
      }
    >
      {/* Create / Edit Modal */}
      <Modal
        open={showCreate || editingTag !== null}
        onClose={resetForm}
        title={editingTag ? 'Edit Tag' : 'Create Tag'}
        size="sm"
      >
        <form onSubmit={editingTag ? handleUpdate : handleCreate}>
          <VStack gap={3}>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '0.875rem', fontWeight: 500 }}>
                Name
              </label>
              <Input
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                error={form.errors.name}
                autoFocus
              />
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '0.875rem', fontWeight: 500 }}>
                Color
              </label>
              <HStack gap={2} align="center">
                <input
                  type="color"
                  value={form.data.color}
                  onChange={(e) => form.setData('color', e.target.value)}
                  style={{
                    width: '40px',
                    height: '36px',
                    border: '1px solid var(--aeos-border)',
                    borderRadius: 'var(--aeos-radius-sm)',
                    cursor: 'pointer',
                    padding: '2px',
                  }}
                />
                <Input
                  value={form.data.color}
                  onChange={(e) => form.setData('color', e.target.value)}
                  error={form.errors.color}
                  style={{ flex: 1 }}
                />
              </HStack>
            </div>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '0.875rem', fontWeight: 500 }}>
                Description
              </label>
              <Input
                value={form.data.description}
                onChange={(e) => form.setData('description', e.target.value)}
                error={form.errors.description}
              />
            </div>
            <HStack gap={2} justify="end">
              <Button type="button" intent="ghost" size="sm" onClick={resetForm}>
                Cancel
              </Button>
              <Button
                type="submit"
                intent="primary"
                size="sm"
                loading={form.processing}
              >
                {editingTag ? 'Save Changes' : 'Create Tag'}
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>

      {/* Merge Tag Modal */}
      <Modal
        open={mergingTag !== null}
        onClose={() => { setMergingTag(null); setMergeTargetId(''); }}
        title="Merge Tag"
        size="sm"
      >
        <form onSubmit={handleMerge}>
          <VStack gap={3}>
            <Text>
              Merge <strong>{mergingTag?.name}</strong> into another tag. All records tagged with this tag will be reassigned.
            </Text>
            <div>
              <label style={{ display: 'block', marginBottom: '4px', fontSize: '0.875rem', fontWeight: 500 }}>
                Target Tag
              </label>
              <select
                value={mergeTargetId}
                onChange={(e) => setMergeTargetId(e.target.value)}
                style={{
                  width: '100%',
                  padding: '8px 12px',
                  border: '1px solid var(--aeos-border)',
                  borderRadius: 'var(--aeos-radius-sm)',
                  fontSize: '0.875rem',
                  background: 'var(--aeos-surface)',
                  color: 'var(--aeos-fg)',
                }}
                required
              >
                <option value="">Select a tag...</option>
                {tagList
                  .filter((t) => t.id !== mergingTag?.id)
                  .map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} (#{t.slug})
                    </option>
                  ))}
              </select>
            </div>
            <HStack gap={2} justify="end">
              <Button type="button" intent="ghost" size="sm" onClick={() => { setMergingTag(null); setMergeTargetId(''); }}>
                Cancel
              </Button>
              <Button type="submit" intent="primary" size="sm" loading={mergeForm.processing} disabled={!mergeTargetId}>
                Merge
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        open={deletingTag !== null}
        onClose={() => setDeletingTag(null)}
        title="Delete Tag"
        size="sm"
      >
        <VStack gap={3}>
          <Text>
            Are you sure you want to delete <strong>{deletingTag?.name}</strong>?
            This will remove the tag from all associated records.
          </Text>
          <HStack gap={2} justify="end">
            <Button type="button" intent="ghost" size="sm" onClick={() => setDeletingTag(null)}>
              Cancel
            </Button>
            <Button type="button" intent="danger" size="sm" onClick={handleDelete}>
              Delete
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

TagsIndex.layout = (page) => <App title="Tags & Labels">{page}</App>;
