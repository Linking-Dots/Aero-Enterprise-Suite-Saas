import { useState } from 'react';
import { router } from '@inertiajs/react';
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
import App from '@/Pages/App.jsx';

export default function TrashedTags({ tags, filters }) {
  const toast = useToast();
  const canRestore = useHRMAC('core.tags_labels.tag_management.update');
  const canForceDelete = useHRMAC('core.tags_labels.tag_management.delete');

  const [search, setSearch] = useState(filters.search || '');
  const [restoringTag, setRestoringTag] = useState(null);
  const [deletingTag, setDeletingTag] = useState(null);

  const handleSearch = (value) => {
    setSearch(value);
    router.get(route('core.tags.trashed'), { search: value }, {
      preserveState: true,
      preserveScroll: true,
      only: ['tags', 'filters'],
    });
  };

  const handleRestore = (tag) => {
    router.post(route('core.tags.restore', tag.id), {}, {
      onSuccess: () => {
        toast.success(`Tag "${tag.name}" restored`);
        setRestoringTag(null);
      },
      onError: () => {
        toast.error('Failed to restore tag');
      },
    });
  };

  const handleForceDelete = () => {
    if (!deletingTag) return;
    router.delete(route('core.tags.force-delete', deletingTag.id), {
      onSuccess: () => {
        toast.success(`Tag "${deletingTag.name}" permanently deleted`);
        setDeletingTag(null);
      },
      onError: () => {
        toast.error('Failed to delete tag');
      },
    });
  };

  const tagList = tags?.data || [];

  return (
    <IndexPageLayout
      title="Deleted Tags"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Tags & Labels', href: route('core.tags.index') },
        { label: 'Deleted Tags' },
      ]}
      filters={
        <HStack gap={2} align="center">
          <Input
            type="search"
            placeholder="Search deleted tags..."
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
                  {search ? 'No deleted tags match your search.' : 'No deleted tags.'}
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
                      {canRestore && (
                        <Button
                          intent="primary"
                          size="sm"
                          onClick={() => setRestoringTag(tag)}
                          aria-label={`Restore ${tag.name}`}
                        >
                          Restore
                        </Button>
                      )}
                      {canForceDelete && (
                        <Button
                          intent="danger"
                          size="sm"
                          onClick={() => setDeletingTag(tag)}
                          aria-label={`Delete ${tag.name} permanently`}
                        >
                          Delete Forever
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
                    <Heading level={5} className="aeos-m-0">
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
                    <Text size="xs" tone="tertiary">
                      Deleted {tag.deleted_at}
                    </Text>
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
      {/* Restore Confirmation Modal */}
      <Modal
        open={restoringTag !== null}
        onClose={() => setRestoringTag(null)}
        title="Restore Tag"
        size="sm"
      >
        <VStack gap={3}>
          <Text>
            Restore <strong>{restoringTag?.name}</strong>? This will make the tag available again for tagging records.
          </Text>
          <HStack gap={2} justify="end">
            <Button type="button" intent="ghost" size="sm" onClick={() => setRestoringTag(null)}>
              Cancel
            </Button>
            <Button type="button" intent="primary" size="sm" onClick={() => handleRestore(restoringTag)}>
              Restore
            </Button>
          </HStack>
        </VStack>
      </Modal>

      {/* Force Delete Confirmation Modal */}
      <Modal
        open={deletingTag !== null}
        onClose={() => setDeletingTag(null)}
        title="Permanently Delete Tag"
        size="sm"
      >
        <VStack gap={3}>
          <Text>
            Are you sure you want to permanently delete <strong>{deletingTag?.name}</strong>?
            This action cannot be undone and all tag associations will be lost.
          </Text>
          <HStack gap={2} justify="end">
            <Button type="button" intent="ghost" size="sm" onClick={() => setDeletingTag(null)}>
              Cancel
            </Button>
            <Button type="button" intent="danger" size="sm" onClick={handleForceDelete}>
              Delete Forever
            </Button>
          </HStack>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

TrashedTags.layout = (page) => <App title="Deleted Tags">{page}</App>;
