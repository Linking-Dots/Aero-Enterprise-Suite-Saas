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
  Select,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TYPE_INTENT = {
  users:       'neutral',
  employees:   'info',
  departments: 'amber',
  leaves:      'warning',
  payroll:     'success',
};

export default function TrashIndex({ items, filters }) {
  const toast          = useToast();
  const canRestore     = useHRMAC('core.trash.restore');
  const canForceDelete = useHRMAC('core.trash.force_delete');
  const canEmpty       = useHRMAC('core.trash.empty');

  const [selectedIds, setSelectedIds] = useState([]);
  const [typeFilter,  setTypeFilter]  = useState(filters?.type || '');

  const applyType = val => {
    setTypeFilter(val);
    setSelectedIds([]);
    router.get(route('core.trash.index'), { type: val }, {
      preserveState: true, preserveScroll: true, only: ['items', 'filters'],
    });
  };

  const toggleSelect = id =>
    setSelectedIds(prev => prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]);

  const toggleAll = () =>
    setSelectedIds(prev => prev.length === (items?.data?.length ?? 0)
      ? []
      : (items?.data ?? []).map(i => i.id));

  const restore = id => {
    router.post(route('core.trash.restore', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Item restored.'),
      onError:   () => toast.error('Failed to restore.'),
    });
  };

  const forceDelete = id => {
    if (!confirm('Permanently delete this item? This cannot be undone.')) return;
    router.delete(route('core.trash.force-delete', id), {
      preserveState: true,
      onSuccess: () => toast.success('Item permanently deleted.'),
      onError:   () => toast.error('Failed to delete.'),
    });
  };

  const bulkRestore = () => {
    if (!selectedIds.length) return;
    if (!confirm(`Restore ${selectedIds.length} item(s)?`)) return;
    router.post(route('core.trash.bulk-restore'), { ids: selectedIds }, {
      preserveState: true,
      onSuccess: () => { toast.success('Items restored.'); setSelectedIds([]); },
      onError:   () => toast.error('Bulk restore failed.'),
    });
  };

  const bulkForceDelete = () => {
    if (!selectedIds.length) return;
    if (!confirm(`Permanently delete ${selectedIds.length} item(s)?`)) return;
    router.post(route('core.trash.bulk-force-delete'), { ids: selectedIds }, {
      preserveState: true,
      onSuccess: () => { toast.success('Items permanently deleted.'); setSelectedIds([]); },
      onError:   () => toast.error('Bulk delete failed.'),
    });
  };

  const emptyTrash = () => {
    if (!confirm('Empty trash? All items will be permanently deleted.')) return;
    router.delete(route('core.trash.empty'), { data: { type: typeFilter || null } }, {
      preserveState: true,
      onSuccess: () => { toast.success('Trash emptied.'); setSelectedIds([]); },
      onError:   () => toast.error('Failed to empty trash.'),
    });
  };

  const rows     = items?.data ?? [];
  const allSelected = rows.length > 0 && selectedIds.length === rows.length;

  const columns = [
    {
      key: 'select', label: '', width: '40px',
      render: row => (
        <input
          type="checkbox"
          checked={selectedIds.includes(row.id)}
          onChange={() => toggleSelect(row.id)}
        />
      ),
    },
    {
      key: 'type', label: 'Type', width: '14%',
      render: row => (
        <Badge intent={TYPE_INTENT[row.type] || 'neutral'}>{row.type}</Badge>
      ),
    },
    {
      key: 'name', label: 'Name / Title', width: '28%',
      render: row => <Text size="sm">{row.name || row.title || `#${row.id}`}</Text>,
    },
    {
      key: 'deleted_by', label: 'Deleted By', width: '18%',
      render: row => <Text size="sm" tone="secondary">{row.deleted_by ?? '—'}</Text>,
    },
    {
      key: 'deleted_at', label: 'Deleted At', width: '16%',
      render: row => <Text size="sm">{new Date(row.deleted_at).toLocaleString()}</Text>,
    },
    {
      key: 'actions', label: '', width: '24%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canRestore && (
            <Button intent="soft" size="sm" onClick={() => restore(row.id)}>Restore</Button>
          )}
          {canForceDelete && (
            <Button intent="danger" size="sm" onClick={() => forceDelete(row.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Trash"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Trash' },
      ]}
      description="Restore or permanently remove soft-deleted records."
      actions={
        canEmpty && (
          <Button intent="danger" leftIcon="trash" onClick={emptyTrash}>
            Empty Trash
          </Button>
        )
      }
      kpis={[
        <Stat key="total" title="Items in Trash" value={items?.total ?? 0} icon="trash" iconTone="danger" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap>
          <Select
            value={typeFilter}
            onChange={e => applyType(e.target.value)}
            options={[
              { value: '',            label: 'All Types' },
              { value: 'users',       label: 'Users' },
              { value: 'employees',   label: 'Employees' },
              { value: 'departments', label: 'Departments' },
              { value: 'leaves',      label: 'Leaves' },
              { value: 'payroll',     label: 'Payroll' },
            ]}
          />
        </HStack>
      }
      table={
        <VStack gap={3}>
          {selectedIds.length > 0 && (
            <HStack gap={2}>
              <Text size="sm">{selectedIds.length} selected</Text>
              {canRestore && (
                <Button intent="soft" size="sm" onClick={bulkRestore}>Restore Selected</Button>
              )}
              {canForceDelete && (
                <Button intent="danger" size="sm" onClick={bulkForceDelete}>Delete Selected</Button>
              )}
            </HStack>
          )}
          <DataTable
            columns={columns}
            rows={rows}
            empty="Trash is empty."
            headerExtra={
              rows.length > 0 && (
                <input
                  type="checkbox"
                  checked={allSelected}
                  onChange={toggleAll}
                  title="Select all"
                />
              )
            }
          />
        </VStack>
      }
      pagination={
        items?.last_page > 1 && (
          <Pagination
            page={items.current_page}
            total={items.last_page}
            onChange={page => router.get(route('core.trash.index'), { page, type: typeFilter }, {
              preserveState: true, preserveScroll: true, only: ['items'],
            })}
          />
        )
      }
    />
  );
}

TrashIndex.layout = page => <App title="Trash">{page}</App>;
