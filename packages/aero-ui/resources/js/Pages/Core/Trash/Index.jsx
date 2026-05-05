import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  Card, CardHeader, CardBody,
  Select,
  HStack, VStack, Stack,
  Text, Heading,
  Badge,
  Button,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function TrashIndex({ entity, items, counts, entity_names }) {
  const toast = useToast();
  const canRestore = useHRMAC('core.trash.restore');
  const canForceDelete = useHRMAC('core.trash.force_delete');
  const canEmpty = useHRMAC('core.trash.empty');

  const [selectedIds, setSelectedIds] = useState([]);

  const handleEntityChange = (newEntity) => {
    router.get(route('core.trash.index'), { entity: newEntity }, { preserveState: true });
  };

  const handleRestore = (id) => {
    router.post(route('core.trash.restore', { entity, id }), {}, {
      onSuccess: () => {
        toast.success('Item restored successfully');
        router.reload();
      },
      onError: () => {
        toast.error('Failed to restore item');
      },
    });
  };

  const handleBulkRestore = () => {
    if (selectedIds.length === 0) return;
    
    router.post(route('core.trash.bulk-restore', { entity }), { ids: selectedIds }, {
      onSuccess: () => {
        toast.success('Items restored successfully');
        setSelectedIds([]);
        router.reload();
      },
      onError: () => {
        toast.error('Failed to restore items');
      },
    });
  };

  const handleForceDelete = (id) => {
    if (confirm('Are you sure you want to permanently delete this item? This cannot be undone.')) {
      router.delete(route('core.trash.force-delete', { entity, id }), {
        onSuccess: () => {
          toast.success('Item permanently deleted');
          router.reload();
        },
        onError: () => {
          toast.error('Failed to delete item');
        },
      });
    }
  };

  const handleBulkForceDelete = () => {
    if (selectedIds.length === 0) return;
    
    if (confirm(`Are you sure you want to permanently delete ${selectedIds.length} items? This cannot be undone.`)) {
      router.delete(route('core.trash.bulk-force-delete', { entity }), { data: { ids: selectedIds } }, {
        onSuccess: () => {
          toast.success('Items permanently deleted');
          setSelectedIds([]);
          router.reload();
        },
        onError: () => {
          toast.error('Failed to delete items');
        },
      });
    }
  };

  const handleEmptyTrash = () => {
    if (confirm(`Are you sure you want to empty all ${entity_names[entity]} trash? This cannot be undone.`)) {
      router.delete(route('core.trash.empty', { entity }), {
        onSuccess: () => {
          toast.success('Trash emptied successfully');
          router.reload();
        },
        onError: () => {
          toast.error('Failed to empty trash');
        },
      });
    }
  };

  const handleSelectAll = () => {
    if (selectedIds.length === items.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(items.map(item => item.id));
    }
  };

  const handleSelectItem = (id) => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter(item => item !== id));
    } else {
      setSelectedIds([...selectedIds, id]);
    }
  };

  return (
    <App title="Trash & Recycle Bin">
      <IndexPageLayout
        title="Trash & Recycle Bin"
        breadcrumbs={[
          { label: 'Core', href: route('core.dashboard') },
          { label: 'Trash' },
        ]}
      >
        <VStack spacing={6}>
          <Card>
            <CardHeader>
              <Heading size="md">Entity Selector</Heading>
            </CardHeader>
            <CardBody>
              <Select
                label="Select Entity"
                value={entity}
                onChange={(e) => handleEntityChange(e.target.value)}
                options={Object.entries(entity_names).map(([key, label]) => ({ value: key, label }))}
              />
              <VStack spacing={2} mt={4}>
                {Object.entries(counts).map(([key, count]) => (
                  <HStack key={key} justify="space-between">
                    <Text>{entity_names[key]}</Text>
                    <Badge color={count > 0 ? 'error' : 'success'}>{count}</Badge>
                  </HStack>
                ))}
              </VStack>
            </CardBody>
          </Card>

          <Card>
            <CardHeader>
              <HStack justify="space-between" align="center">
                <Heading size="md">{entity_names[entity]} Trash</Heading>
                <HStack>
                  {canRestore && selectedIds.length > 0 && (
                    <>
                      <Button size="sm" onClick={handleBulkRestore}>
                        Restore Selected ({selectedIds.length})
                      </Button>
                      {canForceDelete && (
                        <Button size="sm" variant="ghost" onClick={handleBulkForceDelete}>
                          Delete Selected
                        </Button>
                      )}
                    </>
                  )}
                  {canEmpty && items.length > 0 && (
                    <Button size="sm" variant="ghost" onClick={handleEmptyTrash}>
                      Empty Trash
                    </Button>
                  )}
                </HStack>
              </HStack>
            </CardHeader>
            <CardBody>
              {items.length === 0 ? (
                <Text color="muted">No items in trash</Text>
              ) : (
                <Stack spacing={3}>
                  <HStack>
                    <Button size="sm" variant="ghost" onClick={handleSelectAll}>
                      {selectedIds.length === items.length ? 'Deselect All' : 'Select All'}
                    </Button>
                  </HStack>
                  {items.map((item) => (
                    <HStack key={item.id} justify="space-between" align="center">
                      <HStack>
                        <input
                          type="checkbox"
                          checked={selectedIds.includes(item.id)}
                          onChange={() => handleSelectItem(item.id)}
                        />
                        <VStack align="start" spacing={1}>
                          <Text fontWeight="medium">{item.name || item.email || item.description || `#${item.id}`}</Text>
                          <Text size="sm" color="muted">
                            Deleted: {item.deleted_at}
                          </Text>
                        </VStack>
                      </HStack>
                      <HStack>
                        {canRestore && (
                          <Button size="sm" onClick={() => handleRestore(item.id)}>
                            Restore
                          </Button>
                        )}
                        {canForceDelete && (
                          <Button size="sm" variant="ghost" onClick={() => handleForceDelete(item.id)}>
                            Delete
                          </Button>
                        )}
                      </HStack>
                    </HStack>
                  ))}
                </Stack>
              )}
            </CardBody>
          </Card>
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
