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
  Field,
  Modal,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_INTENT = { draft: 'neutral', published: 'success', archived: 'neutral' };
const TYPE_INTENT   = { info: 'primary',  warning: 'warning',   success: 'success', danger: 'danger' };

export default function BannersIndex({ banners, filters }) {
  const toast      = useToast();
  const canCreate  = useHRMAC('core.announcements.banners.create');
  const canPublish = useHRMAC('core.announcements.banners.publish');
  const canArchive = useHRMAC('core.announcements.banners.archive');
  const canDelete  = useHRMAC('core.announcements.banners.delete');

  const [showModal, setShowModal] = useState(false);

  const form = useForm({
    title:     '',
    body:      '',
    type:      'info',
    is_pinned: false,
  });

  const submit = e => {
    e.preventDefault();
    form.post(route('core.banners.store'), {
      onSuccess: () => {
        toast.success('Banner created.');
        form.reset();
        setShowModal(false);
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to create banner.');
      },
    });
  };

  const publish = id => {
    router.post(route('core.banners.publish', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Banner published.'),
      onError:   () => toast.error('Failed to publish banner.'),
    });
  };

  const archive = id => {
    router.post(route('core.banners.archive', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Banner archived.'),
      onError:   () => toast.error('Failed to archive banner.'),
    });
  };

  const destroy = id => {
    if (!confirm('Delete this banner?')) return;
    router.delete(route('core.banners.destroy', id), {
      onSuccess: () => toast.success('Banner deleted.'),
      onError:   () => toast.error('Failed to delete banner.'),
    });
  };

  const items = banners?.data ?? [];

  const columns = [
    {
      key: 'title', label: 'Title', width: '35%',
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
    {
      key: 'pinned', label: 'Pinned', width: '10%',
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
        title="System Banners"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Announcements', href: route('core.announcements.index') },
          { label: 'Banners' },
        ]}
        description="Manage system-wide banners displayed across the application."
        actions={
          canCreate && (
            <Button intent="primary" onClick={() => setShowModal(true)}>
              Create Banner
            </Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={items}
            empty="No banners found."
          />
        }
        pagination={
          banners?.last_page > 1 && (
            <Pagination
              page={banners.current_page}
              total={banners.last_page}
              onChange={page => router.get(route('core.banners.index'), { page }, {
                preserveState: true, preserveScroll: true, only: ['banners'],
              })}
            />
          )
        }
      />

      {/* Create Banner Modal */}
      <Modal
        open={showModal}
        title="Create Banner"
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
            <Field
              label="Title"
              htmlFor="banner-title"
              error={form.errors.title}
              required
            >
              <Input
                id="banner-title"
                value={form.data.title}
                onChange={e => form.setData('title', e.target.value)}
                placeholder="e.g. Scheduled downtime this weekend"
                required
              />
            </Field>

            <Field
              label="Body"
              htmlFor="banner-body"
              error={form.errors.body}
              required
            >
              <Textarea
                id="banner-body"
                rows={4}
                value={form.data.body}
                onChange={e => form.setData('body', e.target.value)}
                placeholder="Banner message content…"
                required
              />
            </Field>

            <Field
              label="Type"
              htmlFor="banner-type"
              error={form.errors.type}
            >
              <Select
                id="banner-type"
                value={form.data.type}
                onChange={e => form.setData('type', e.target.value)}
                options={[
                  { value: 'info',    label: 'Info' },
                  { value: 'warning', label: 'Warning' },
                  { value: 'success', label: 'Success' },
                  { value: 'danger',  label: 'Danger' },
                ]}
              />
            </Field>

            <Toggle
              label="Pin banner to top"
              checked={form.data.is_pinned}
              onChange={e => form.setData('is_pinned', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>
    </>
  );
}

BannersIndex.layout = page => (
  <App title="System Banners">{page}</App>
);
