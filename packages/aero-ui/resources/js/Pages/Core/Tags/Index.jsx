import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Field,
  Input,
  Select,
  Modal,
  Alert,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function TagsIndex({ tags, filters }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.tags_labels.tag_management.create');
  const canEdit   = useHRMAC('core.tags_labels.tag_management.update');
  const canDelete = useHRMAC('core.tags_labels.tag_management.delete');

  const [search,       setSearch]       = useState(filters?.search || '');
  const [showModal,    setShowModal]    = useState(false);
  const [editingTag,   setEditingTag]   = useState(null);
  const [deletingTag,  setDeletingTag]  = useState(null);
  const [mergingTag,   setMergingTag]   = useState(null);
  const [mergeTarget,  setMergeTarget]  = useState('');
  const [submitting,   setSubmitting]   = useState(false);

  const [form, setForm] = useState({ name: '', color: '#0ea5e9' });

  const tagList = tags?.data ?? [];

  const applySearch = () => {
    router.get(route('core.tags.index'), { search }, {
      preserveState: true, preserveScroll: true, only: ['tags', 'filters'],
    });
  };

  const resetSearch = () => {
    setSearch('');
    router.get(route('core.tags.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['tags', 'filters'],
    });
  };

  const openCreate = () => {
    setForm({ name: '', color: '#0ea5e9' });
    setEditingTag(null);
    setShowModal(true);
  };

  const openEdit = tag => {
    setForm({ name: tag.name, color: tag.color || '#0ea5e9' });
    setEditingTag(tag);
    setShowModal(true);
  };

  const closeModal = () => { setShowModal(false); setEditingTag(null); };

  const handleSave = () => {
    setSubmitting(true);
    if (editingTag) {
      router.put(route('core.tags.update', editingTag.id), form, {
        preserveState: true,
        onSuccess: () => { toast.success('Tag updated.'); closeModal(); },
        onError:   () => toast.error('Failed to update tag.'),
        onFinish:  () => setSubmitting(false),
      });
    } else {
      router.post(route('core.tags.store'), form, {
        preserveState: true,
        onSuccess: () => { toast.success('Tag created.'); closeModal(); },
        onError:   () => toast.error('Failed to create tag.'),
        onFinish:  () => setSubmitting(false),
      });
    }
  };

  const handleDelete = () => {
    if (!deletingTag) return;
    router.delete(route('core.tags.destroy', deletingTag.id), {
      preserveState: true,
      onSuccess: () => { toast.success('Tag deleted.'); setDeletingTag(null); },
      onError:   () => toast.error('Failed to delete tag.'),
    });
  };

  const handleMerge = () => {
    if (!mergingTag || !mergeTarget) return;
    setSubmitting(true);
    router.post(route('core.tags.merge'), { source_tag_id: mergingTag.id, target_tag_id: mergeTarget }, {
      preserveState: true,
      onSuccess: () => { toast.success('Tags merged.'); setMergingTag(null); setMergeTarget(''); },
      onError:   () => toast.error('Failed to merge tags.'),
      onFinish:  () => setSubmitting(false),
    });
  };

  const columns = [
    {
      key: 'color', label: 'Color', width: '60px',
      render: row => (
        <span
          className="aeos-tag-swatch"
          title={row.color}
          data-color={row.color}
        />
      ),
    },
    {
      key: 'name', label: 'Name', width: '30%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'usage_count', label: 'Usage', width: '14%',
      render: row => <Badge intent="neutral">{row.usage_count ?? row.records_count ?? 0}</Badge>,
    },
    {
      key: 'created_at', label: 'Created', width: '16%',
      render: row => <Text size="sm">{new Date(row.created_at).toLocaleDateString()}</Text>,
    },
    {
      key: 'actions', label: '', width: '30%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canEdit && tagList.length > 1 && (
            <Button intent="ghost" size="sm" onClick={() => { setMergingTag(row); setMergeTarget(''); }}>
              Merge
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => setDeletingTag(row)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <style>{`.aeos-tag-swatch{display:inline-block;width:14px;height:14px;border-radius:50%;background:attr(data-color color,#0ea5e9);flex-shrink:0}`}</style>
      <IndexPageLayout
        title="Tags & Labels"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Tags & Labels' },
        ]}
        description="Manage tags used across entities."
        actions={
          canCreate && (
            <Button intent="primary" leftIcon="tag" onClick={openCreate}>
              New Tag
            </Button>
          )
        }
        kpis={[
          <Stat key="total" title="Total Tags" value={tags?.total ?? 0} icon="tag" />,
        ]}
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search tags…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applySearch()}
              leftIcon="search"
            />
            <Button intent="primary" onClick={applySearch}>Filter</Button>
            <Button intent="ghost"   onClick={resetSearch}>Reset</Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={tagList}
            empty="No tags found."
          />
        }
        pagination={
          tags?.last_page > 1 && (
            <Pagination
              page={tags.current_page}
              total={tags.last_page}
              onChange={page => router.get(route('core.tags.index'), { page, search }, {
                preserveState: true, preserveScroll: true, only: ['tags'],
              })}
            />
          )
        }
      >
        {/* Create / Edit Modal */}
        <Modal open={showModal} onClose={closeModal} title={editingTag ? 'Edit Tag' : 'New Tag'} size="sm">
          <VStack gap={4}>
            <Field label="Name" htmlFor="tag-name" required>
              <Input
                id="tag-name"
                value={form.name}
                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                placeholder="Tag name"
              />
            </Field>
            <Field label="Color" htmlFor="tag-color">
              <HStack gap={2} align="center">
                <input
                  id="tag-color"
                  type="color"
                  value={form.color}
                  onChange={e => setForm(f => ({ ...f, color: e.target.value }))}
                  className="aeos-color-picker"
                />
                <Input
                  value={form.color}
                  onChange={e => setForm(f => ({ ...f, color: e.target.value }))}
                  placeholder="#0ea5e9"
                />
              </HStack>
            </Field>
            <HStack gap={2} justify="end">
              <Button intent="ghost" onClick={closeModal}>Cancel</Button>
              <Button intent="primary" loading={submitting} disabled={!form.name} onClick={handleSave}>
                {editingTag ? 'Save Changes' : 'Create Tag'}
              </Button>
            </HStack>
          </VStack>
        </Modal>

        {/* Merge Modal */}
        <Modal
          open={mergingTag !== null}
          onClose={() => { setMergingTag(null); setMergeTarget(''); }}
          title="Merge Tag"
          size="sm"
        >
          <VStack gap={4}>
            <Text>
              Merge <strong>{mergingTag?.name}</strong> into another tag. All records will be reassigned to the target tag.
            </Text>
            <Field label="Target Tag" htmlFor="merge-target">
              <Select
                id="merge-target"
                value={mergeTarget}
                onChange={e => setMergeTarget(e.target.value)}
                options={[
                  { value: '', label: 'Select target tag…' },
                  ...tagList
                    .filter(t => t.id !== mergingTag?.id)
                    .map(t => ({ value: t.id, label: t.name })),
                ]}
              />
            </Field>
            <HStack gap={2} justify="end">
              <Button intent="ghost" onClick={() => { setMergingTag(null); setMergeTarget(''); }}>Cancel</Button>
              <Button intent="primary" loading={submitting} disabled={!mergeTarget} onClick={handleMerge}>
                Merge
              </Button>
            </HStack>
          </VStack>
        </Modal>

        {/* Delete Confirmation Modal */}
        <Modal
          open={deletingTag !== null}
          onClose={() => setDeletingTag(null)}
          title="Delete Tag"
          size="sm"
        >
          <VStack gap={4}>
            {deletingTag && (deletingTag.usage_count ?? deletingTag.records_count ?? 0) > 0 && (
              <Alert
                intent="warning"
                title={`This tag is used by ${deletingTag.usage_count ?? deletingTag.records_count} record(s). Deleting it will remove the tag from all those records.`}
              />
            )}
            <Text>
              Delete tag <strong>{deletingTag?.name}</strong>?
            </Text>
            <HStack gap={2} justify="end">
              <Button intent="ghost" onClick={() => setDeletingTag(null)}>Cancel</Button>
              <Button intent="danger" onClick={handleDelete}>Delete</Button>
            </HStack>
          </VStack>
        </Modal>
      </IndexPageLayout>
    </>
  );
}

TagsIndex.layout = page => <App title="Tags & Labels">{page}</App>;
