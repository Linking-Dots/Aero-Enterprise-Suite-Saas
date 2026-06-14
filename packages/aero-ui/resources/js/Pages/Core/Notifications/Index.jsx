/**
 * Notifications Center — paginated notification list with read/unread/all tabs,
 * mark-as-read, mark-all-read, and delete actions.
 *
 * Uses Inertia page shell with JSON API fetch for notification data.
 */
import { useState, useEffect, useCallback } from 'react';
import {
  IndexPageLayout,
  Tabs,
  DataTable,
  Button, IconButton,
  Badge,
  Pagination,
  HStack, VStack, Text,
  EmptyState,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../hooks/useHRMAC';

const TAB_ALL = 'all';
const TAB_UNREAD = 'unread';
const TAB_READ = 'read';

export default function NotificationsIndex() {
  const toast = useToast();
  const canMarkRead = useHRMAC('core.self_service.my-notifications.mark_read');
  const canDelete = useHRMAC('core.self_service.my-notifications.view');

  const [activeTab, setActiveTab] = useState(TAB_ALL);
  const [notifications, setNotifications] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const fetchNotifications = useCallback(async (page = 1, filter = activeTab) => {
    setLoading(true);
    const params = new URLSearchParams();
    params.append('per_page', '15');
    params.append('page', String(page));
    if (filter !== TAB_ALL) params.append('filter', filter);

    try {
      const res = await fetch(`${route('core.notifications.list')}?${params.toString()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (res.ok) {
        const data = await res.json();
        setNotifications(data.data ?? []);
        setMeta(data.meta ?? { current_page: 1, last_page: 1, per_page: 15, total: 0 });
        setUnreadCount(data.unread_count ?? 0);
      } else {
        toast.error('Failed to load notifications.');
        setNotifications([]);
      }
    } catch {
      toast.error('Network error while loading notifications.');
      setNotifications([]);
    } finally {
      setLoading(false);
    }
  }, [activeTab, toast]);

  useEffect(() => {
    fetchNotifications(1, activeTab);
  }, [fetchNotifications, activeTab]);

  const handlePageChange = (page) => {
    fetchNotifications(page, activeTab);
  };

  const markAsRead = async (id) => {
    try {
      const res = await fetch(route('core.notifications.read', id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      if (res.ok) {
        const data = await res.json();
        setUnreadCount(data.unread_count ?? 0);
        setNotifications(prev => prev.map(n =>
          n.id === id ? { ...n, read_at: new Date().toISOString() } : n
        ));
        toast.success('Marked as read.');
      }
    } catch {
      toast.error('Failed to mark as read.');
    }
  };

  const markAllAsRead = async () => {
    try {
      const res = await fetch(route('core.notifications.read-all'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      if (res.ok) {
        const data = await res.json();
        setUnreadCount(data.unread_count ?? 0);
        setNotifications(prev => prev.map(n => ({ ...n, read_at: new Date().toISOString() })));
        toast.success(data.message || 'All notifications marked as read.');
      }
    } catch {
      toast.error('Failed to mark all as read.');
    }
  };

  const deleteNotification = async (id) => {
    try {
      const res = await fetch(route('core.notifications.destroy', id), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      if (res.ok) {
        setNotifications(prev => prev.filter(n => n.id !== id));
        toast.success('Notification deleted.');
      }
    } catch {
      toast.error('Failed to delete notification.');
    }
  };

  const columns = [
    {
      key: 'read_status',
      label: '',
      width: '40px',
      render: (row) => (
        <div style={{
          width: 8,
          height: 8,
          borderRadius: '50%',
          backgroundColor: row.read_at ? 'transparent' : 'var(--color-primary)',
          border: row.read_at ? '1px solid var(--color-muted)' : 'none',
        }} />
      ),
    },
    { key: 'type', label: 'Type', width: '15%', render: (row) => <Badge>{row.type || 'Notification'}</Badge> },
    {
      key: 'data',
      label: 'Message',
      width: '45%',
      render: (row) => {
        const message = row.data?.message || row.data?.title || JSON.stringify(row.data).slice(0, 60);
        return (
          <Text weight={row.read_at ? 'normal' : 'semibold'}>
            {message}
          </Text>
        );
      },
    },
    {
      key: 'created_at',
      label: 'Time',
      width: '18%',
      render: (row) => row.created_at ? new Date(row.created_at).toLocaleString() : '—',
    },
    {
      key: 'actions',
      label: '',
      width: '22%',
      align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {!row.read_at && canMarkRead && (
            <Button variant="secondary" size="sm" onClick={() => markAsRead(row.id)}>
              Mark Read
            </Button>
          )}
          {canDelete && (
            <Button variant="danger" size="sm" onClick={() => deleteNotification(row.id)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const tabs = [
    { value: TAB_ALL, label: 'All', count: meta.total },
    { value: TAB_UNREAD, label: 'Unread', count: unreadCount },
    { value: TAB_READ, label: 'Read' },
  ];

  return (
    <IndexPageLayout
      title="Notifications"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Notifications' },
      ]}
      description="View and manage your notifications."
      actions={
        unreadCount > 0 && canMarkRead && (
          <Button variant="primary" onClick={markAllAsRead}>
            Mark All as Read ({unreadCount})
          </Button>
        )
      }
      filters={
        <Tabs
          tabs={tabs}
          value={activeTab}
          onChange={setActiveTab}
        />
      }
      table={
        <DataTable
          columns={columns}
          rows={notifications}
          loading={loading}
          empty={
            loading
              ? 'Loading notifications...'
              : 'No notifications found.'
          }
        />
      }
      pagination={
        meta.last_page > 1 && (
          <Pagination
            page={meta.current_page}
            total={meta.last_page}
            onChange={handlePageChange}
          />
        )
      }
    />
  );
}

NotificationsIndex.layout = page => (
  <App title="Notifications">{page}</App>
);
