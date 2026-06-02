import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack, VStack,
  Text,
  Input,
  Textarea,
  Select,
  Toggle,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = { draft: 'neutral', published: 'success', archived: 'neutral' };
const TYPE_INTENT   = { info: 'primary',  warning: 'warning',   success: 'success', danger: 'danger' };

export default function AnnouncementsIndex({ announcements, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.announcements.announcement_list.create');
  const canPublish = useHRMAC('core.announcements.announcement_list.publish');
  const canArchive = useHRMAC('core.announcements.announcement_list.archive');
  const canDelete  = useHRMAC('core.announcements.announcement_list.delete');

  const [showModal, setShowModal] = useState(false);

  const form = useForm({
    title:        '',
    body:         '',
    type:         'info',
    status:       'draft',
    audience:     'all',
    is_pinned:    false,
    published_at: '',
    expires_at:   '',
  });

  const submit = e => {
    e.preventDefault();
    form.post(route('core.announcements.store'), {
      onSuccess: () => {
        toast.success('Announcement created.');
        form.reset();
        setShowModal(false);
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to create announcement.');
      },
    });
  };

  const publish = id => {
    router.post(route('core.announcements.publish', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Announcement published.'),
      onError:   () => toast.error('Failed to publish'),
    });
  };

  const archive = id => {
    router.post(route('core.announcements.archive', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Announcement archived.'),
      onError:   () => toast.error('Failed to archive'),
    });
  };

  const destroy = id => {
    if (!confirm('Delete this announcement?')) return;
    router.delete(route('core.announcements.destroy', id), {
      onSuccess: () => toast.success('Announcement deleted.'),
      onError:   () => toast.error('Failed to delete'),
    });
  };

  const items = announcements?.data ?? [];

  const columns = [
    {
      key: 'title', label: 'Title', width: '30%',
      render: row => <Text size="sm">{row.title}</Text>,
    },
    {
      key: 'type', label: 'Type', width: '10%',
      render: row => (
        <Badge intent={TYPE_INTENT[row.type] ?? 'neutral'} size="sm">{row.type}</Badge>
      ),
    },
    {
      key: 'status', label: 'Status', width: '12%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'} size="sm">{row.status}</Badge>
      ),
    },
    { key: 'audience', label: 'Audience', width: '10%', render: row => row.audience },
    {
      key: 'pinned', label: 'Pinned', width: '8%',
      render: row => row.is_pinned
        ? <Badge intent="warning" size="sm">Pinned</Badge>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'actions', label: '', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {row.status === 'draft' && canPublish && (
            <Button intent="soft" size="sm" onClick={() => publish(row.id)}>Publish</Button>
          )}
          {row.status === 'published' && canArchive && (
            <Button intent="ghost" size="sm" onClick={() => archive(row.id)}>Archive</Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => destroy(row.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Announcements"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Announcements' },
        ]}
        description="Manage workspace announcements and notices."
        actions={
          canCreate && (
            <Button intent="primary" onClick={() => setShowModal(true)}>
              New Announcement
            </Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={items}
            empty="No announcements found."
          />
        }
        pagination={
          announcements?.last_page > 1 && (
            <Pagination
              page={announcements.current_page}
              total={announcements.last_page}
              onChange={page => router.get(route('core.announcements.index'), { page }, {
                preserveState: true, preserveScroll: true, only: ['announcements'],
              })}
            />
          )
        }
      />

      {/* Create Modal */}
      <Modal
        open={showModal}
        title="New Announcement"
        size="lg"
        onClose={() => setShowModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submit} loading={form.processing}>Create</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={3}>
            <Input
              label="Title *"
              value={form.data.title}
              onChange={e => form.setData('title', e.target.value)}
              error={form.errors.title}
              required
            />

            <Textarea
              label="Body *"
              rows={4}
              value={form.data.body}
              onChange={e => form.setData('body', e.target.value)}
              error={form.errors.body}
              required
            />

            <HStack gap={3} wrap>
              <Select
                label="Type"
                value={form.data.type}
                onChange={e => form.setData('type', e.target.value)}
                options={[
                  { value: 'info',    label: 'Info' },
                  { value: 'warning', label: 'Warning' },
                  { value: 'success', label: 'Success' },
                  { value: 'danger',  label: 'Danger' },
                ]}
              />

              <Select
                label="Audience"
                value={form.data.audience}
                onChange={e => form.setData('audience', e.target.value)}
                options={[
                  { value: 'all',       label: 'All' },
                  { value: 'admins',    label: 'Admins' },
                  { value: 'employees', label: 'Employees' },
                ]}
              />
            </HStack>

            <Toggle
              label="Pin announcement"
              checked={form.data.is_pinned}
              onChange={e => form.setData('is_pinned', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

AnnouncementsIndex.layout = page => (
  <App title="Announcements">{page}</App>
);
