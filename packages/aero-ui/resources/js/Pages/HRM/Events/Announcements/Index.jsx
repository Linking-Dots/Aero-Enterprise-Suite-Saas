import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Pagination, Badge, Text,
} from '@aero/ui';

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

export default function AnnouncementsIndex({ announcements }) {
  const canCreate = useHRMAC('hrm.events.announcements.edit');

  const totalPages  = announcements.last_page    ?? 1;
  const currentPage = announcements.current_page ?? 1;

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.announcements.index'),
      { ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function markRead(id) {
    router.post(
      route('hrm.announcements.read', id),
      {},
      { preserveScroll: true, preserveState: true },
    );
  }

  const columns = [
    {
      key: 'title', label: 'Title',
      render: row => (
        <HStack gap={2} align="center">
          <Text>{row.title || '—'}</Text>
          {!row.is_read && <Badge intent="warning">Unread</Badge>}
        </HStack>
      ),
    },
    {
      key: 'scope', label: 'Scope',
      render: row => (
        <Badge intent={row.is_global ? 'info' : 'neutral'}>
          {row.is_global ? 'Global' : 'Department'}
        </Badge>
      ),
    },
    {
      key: 'created_at', label: 'Posted At',
      render: row => <Text>{fmtDatetime(row.created_at)}</Text>,
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          {!row.is_read && (
            <Button
              intent="soft"
              size="sm"
              onClick={() => markRead(row.id)}
            >
              Mark Read
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Announcements"
      breadcrumb={[{ label: 'HRM' }, { label: 'Events' }, { label: 'Announcements' }]}
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.announcements.create'))}
          >
            New Announcement
          </Button>
        )
      }
      table={
        <DataTable
          columns={columns}
          rows={announcements.data ?? []}
          empty="No announcements found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => applyFilters({ page })}
          />
        )
      }
    />
  );
}

AnnouncementsIndex.layout = page => <App title="Announcements">{page}</App>;
