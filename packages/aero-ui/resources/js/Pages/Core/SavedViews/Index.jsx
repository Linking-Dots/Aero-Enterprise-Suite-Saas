import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Text,
  useToast,
  useHRMAC,
  Stat,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function SavedViewsIndex({ views }) {
  const toast      = useToast();
  const canDelete  = useHRMAC('core.saved_views.views.delete');
  const canShare   = useHRMAC('core.saved_views.views.share');
  const canDefault = useHRMAC('core.saved_views.views.set_default');

  const viewList = views?.data ?? [];

  const handleSetDefault = id => {
    router.post(route('core.saved-views.set-default', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Default view set.'),
      onError:   () => toast.error('Failed to set default.'),
    });
  };

  const handleToggleShare = id => {
    router.post(route('core.saved-views.toggle-share', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Share status updated.'),
      onError:   () => toast.error('Failed to update share status.'),
    });
  };

  const handleDelete = id => {
    if (!confirm('Delete this saved view?')) return;
    router.delete(route('core.saved-views.destroy', id), {
      preserveState: true,
      onSuccess: () => toast.success('Saved view deleted.'),
      onError:   () => toast.error('Failed to delete view.'),
    });
  };

  const columns = [
    {
      key: 'name', label: 'Name', width: '22%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'entity_type', label: 'Entity', width: '18%',
      render: row => <Text size="sm" tone="secondary">{row.entity_type}</Text>,
    },
    {
      key: 'is_default', label: 'Default', width: '10%',
      render: row => row.is_default
        ? <Badge intent="success">Default</Badge>
        : <Badge intent="neutral">—</Badge>,
    },
    {
      key: 'is_shared', label: 'Shared', width: '10%',
      render: row => row.is_shared
        ? <Badge intent="info">Shared</Badge>
        : <Badge intent="neutral">Private</Badge>,
    },
    {
      key: 'created_by', label: 'Created By', width: '18%',
      render: row => <Text size="sm" tone="secondary">{row.created_by ?? '—'}</Text>,
    },
    {
      key: 'created_at', label: 'Created', width: '12%',
      render: row => <Text size="sm">{new Date(row.created_at).toLocaleDateString()}</Text>,
    },
    {
      key: 'actions', label: '', width: '10%', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canDefault && !row.is_default && (
            <Button intent="soft" size="sm" onClick={() => handleSetDefault(row.id)}>
              Set Default
            </Button>
          )}
          {canShare && (
            <Button intent="ghost" size="sm" onClick={() => handleToggleShare(row.id)}>
              {row.is_shared ? 'Unshare' : 'Share'}
            </Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Saved Views"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Saved Views' },
      ]}
      description="Manage saved filter/column configurations across modules."
      kpis={[
        <Stat key="total"   title="Total Views"   value={views?.total ?? 0}                         icon="bookmark" />,
        <Stat key="shared"  title="Shared"         value={viewList.filter(v => v.is_shared).length}  icon="share"    iconTone="info" />,
        <Stat key="default" title="Default Views"  value={viewList.filter(v => v.is_default).length} icon="check"    iconTone="success" />,
      ]}
      table={
        <DataTable
          columns={columns}
          rows={viewList}
          empty="No saved views found."
        />
      }
      pagination={
        views?.last_page > 1 && (
          <Pagination
            page={views.current_page}
            total={views.last_page}
            onChange={page => router.get(route('core.saved-views.index'), { page }, {
              preserveState: true, preserveScroll: true, only: ['views'],
            })}
          />
        )
      }
    />
  );
}

SavedViewsIndex.layout = page => <App title="Saved Views">{page}</App>;
