import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Select,
  Text,
  Mono,
  Stat,
  EmptyState,
  Menu,
  useHRMAC,
} from '@aero/ui';
import { EllipsisHorizontalIcon } from '@heroicons/react/24/outline';
import App from '@/Pages/App.jsx';

const ACTION_INTENT = {
  created: 'success', updated: 'neutral', deleted: 'danger',
  login: 'success', logout: 'neutral', export: 'warning', import: 'warning',
};

const ACTION_OPTIONS = [
  { value: '', label: 'All Actions' },
  { value: 'created', label: 'Created' },
  { value: 'updated', label: 'Updated' },
  { value: 'deleted', label: 'Deleted' },
  { value: 'login', label: 'Login' },
  { value: 'logout', label: 'Logout' },
];

const MODULE_OPTIONS = [
  { value: '', label: 'All Modules' },
  { value: 'users', label: 'Users' },
  { value: 'roles', label: 'Roles' },
  { value: 'tags', label: 'Tags' },
  { value: 'settings', label: 'Settings' },
];

export default function ActivityIndex({ activities, stats, filters }) {
  const canExport = useHRMAC('core.activity_feed.feed.export');

  const [module, setModule] = useState(filters?.module ?? '');
  const [action, setAction] = useState(filters?.action ?? '');

  const [tableLoading, setTableLoading] = useState(false);
  useEffect(() => {
    const offStart  = router.on('start',  () => setTableLoading(true));
    const offFinish = router.on('finish', () => setTableLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const reload = (next = {}) => {
    const params = { ...next };
    if (module) params.module = module;
    if (action) params.action = action;
    router.get(route('core.activity.index'), params, {
      preserveState: true, preserveScroll: true, only: ['activities', 'filters', 'stats'],
    });
  };

  const applyFilters = () => reload({ page: 1 });
  const resetFilters = () => {
    setModule(''); setAction('');
    router.get(route('core.activity.index'), {}, {
      preserveState: true, preserveScroll: true, only: ['activities', 'filters', 'stats'],
    });
  };

  const exportFeed = () => window.open(route('core.activity.export', { module, action }), '_blank');

  const columns = [
    { key: 'actor', label: 'Actor', width: '18%',
      render: row => <Text size="sm">{row.user?.name || 'System'}</Text> },
    { key: 'description', label: 'Description', width: '34%',
      render: row => <Text size="sm">{row.description || '—'}</Text> },
    { key: 'action', label: 'Action', width: '14%',
      render: row => <Badge intent={ACTION_INTENT[row.action] ?? 'neutral'}>{row.action || '—'}</Badge> },
    { key: 'module', label: 'Module', width: '14%',
      render: row => row.module ? <Badge intent="indigo" size="sm">{row.module}</Badge> : <Text tone="tertiary" size="sm">—</Text> },
    { key: 'created_at', label: 'Time', width: '16%',
      render: row => <Mono size="sm">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</Mono> },
    { key: 'actions', label: '', width: '60px', align: 'right',
      render: row => (
        <Menu align="end"
          trigger={<Button intent="ghost" size="sm" aria-label="More actions"><EllipsisHorizontalIcon className="aeos-icon-sm" /></Button>}
          items={[{ label: 'View details', onClick: () => router.visit(route('core.activity.show', row.id)) }]} />
      ) },
  ];

  const rows = activities?.data ?? [];
  const hasFilter = !!(module || action);

  return (
    <IndexPageLayout
      title="Activity Feed"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Activity Feed' },
      ]}
      description="Cross-module timeline of user and system actions."
      actions={canExport && (
        <Button intent="ghost" type="button" leftIcon="download" onClick={exportFeed}>Export</Button>
      )}
      kpis={[
        <Stat key="total" title="Total activities" value={stats?.total_activities ?? 0} icon="document" />,
        <Stat key="today" title="Today"            value={stats?.today_activities ?? 0} icon="clock" />,
        <Stat key="week"  title="This week"        value={stats?.week_activities ?? 0} icon="calendar" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap>
          <Select value={module} onChange={e => setModule(e.target.value)} options={MODULE_OPTIONS} />
          <Select value={action} onChange={e => setAction(e.target.value)} options={ACTION_OPTIONS} />
          <Button intent="primary" type="button" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   type="button" onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        !tableLoading && rows.length === 0 ? (
          hasFilter ? (
            <EmptyState icon="filter" title="No matching activity"
              description="Try adjusting the module or action filter."
              action={<Button intent="ghost" type="button" onClick={resetFilters}>Reset filters</Button>} />
          ) : (
            <EmptyState icon="document" title="No activity yet"
              description="Activity will appear here as users interact with the system." />
          )
        ) : (
          <DataTable columns={columns} rows={rows} loading={tableLoading} />
        )
      }
      pagination={
        activities?.last_page > 1 && (
          <Pagination page={activities.current_page} total={activities.last_page}
            onChange={page => reload({ page })} />
        )
      }
    />
  );
}

ActivityIndex.layout = page => <App title="Activity Feed">{page}</App>;
