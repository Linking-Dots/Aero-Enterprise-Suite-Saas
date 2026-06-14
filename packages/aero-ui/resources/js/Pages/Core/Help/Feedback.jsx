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
  Mono,
  Input,
  Textarea,
  Select,
  Field,
  Modal,
  useToast,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TYPE_INTENT   = { feature: 'primary', bug: 'danger', improvement: 'warning' };
const STATUS_INTENT = { open: 'neutral', 'under-review': 'warning', planned: 'primary', done: 'success', declined: 'danger' };

export default function Feedback({ items = {} }) {
  const toast = useToast();
  const [showModal, setShowModal] = useState(false);

  const form = useForm({
    title:       '',
    description: '',
    type:        'feature',
  });

  const submit = e => {
    e.preventDefault();
    form.post('/help/feedback', {
      onSuccess: () => {
        toast.success('Feedback submitted. Thank you!');
        form.reset();
        setShowModal(false);
      },
      onError: errors => {
        const first = Object.values(errors)[0];
        toast.error(first || 'Failed to submit feedback.');
      },
    });
  };

  const voteUp = id => {
    router.post(`/help/feedback/${id}/vote`, {}, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Vote recorded.'),
      onError:   () => toast.error('Failed to record vote.'),
    });
  };

  const columns = [
    {
      key: 'title', label: 'Title', width: '30%',
      render: row => <Text size="sm">{row.title}</Text>,
    },
    {
      key: 'type', label: 'Type', width: '14%',
      render: row => (
        <Badge intent={TYPE_INTENT[row.type] ?? 'neutral'} size="sm">
          {row.type}
        </Badge>
      ),
    },
    {
      key: 'votes', label: 'Votes', width: '10%',
      render: row => (
        <Mono size="sm" tone="secondary">{row.votes ?? 0}</Mono>
      ),
    },
    {
      key: 'status', label: 'Status', width: '14%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] ?? 'neutral'} size="sm">
          {row.status ?? 'open'}
        </Badge>
      ),
    },
    {
      key: 'created_at', label: 'Submitted', width: '14%',
      render: row => (
        <Mono size="sm" tone="secondary">
          {row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
    {
      key: 'actions', label: '', width: '14%', align: 'right',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          leftIcon="handThumbUp"
          onClick={() => voteUp(row.id)}
        >
          Vote Up
        </Button>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Feedback"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Help Center', href: '/help' },
          { label: 'Feedback' },
        ]}
        description="Share ideas, report bugs, and vote on community suggestions."
        actions={
          <HStack gap={2}>
            <Button intent="ghost" leftIcon="arrowLeft" onClick={() => router.get('/help')}>
              Back to Help Center
            </Button>
            <Button intent="primary" leftIcon="plus" onClick={() => setShowModal(true)}>
              Submit Feedback
            </Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={items?.data ?? []}
            empty="No feedback submitted yet."
          />
        }
        pagination={
          items?.last_page > 1 && (
            <Pagination
              page={items.current_page}
              total={items.last_page}
              onChange={page => router.get('/help/feedback', { page }, {
                preserveState: true, preserveScroll: true, only: ['items'],
              })}
            />
          )
        }
      />

      <Modal
        open={showModal}
        title="Submit Feedback"
        size="lg"
        onClose={() => setShowModal(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowModal(false)}>Cancel</Button>
            <Button intent="primary" onClick={submit} loading={form.processing}>Submit</Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={3}>
            <Field label="Title" htmlFor="feedback-title" error={form.errors.title} required>
              <Input
                id="feedback-title"
                placeholder="Short summary of your feedback"
                value={form.data.title}
                onChange={e => form.setData('title', e.target.value)}
                error={form.errors.title}
              />
            </Field>

            <Field label="Description" htmlFor="feedback-description" error={form.errors.description}>
              <Textarea
                id="feedback-description"
                rows={5}
                placeholder="Describe your idea or issue in detail…"
                value={form.data.description}
                onChange={e => form.setData('description', e.target.value)}
                error={form.errors.description}
              />
            </Field>

            <Field label="Type" htmlFor="feedback-type" error={form.errors.type}>
              <Select
                id="feedback-type"
                value={form.data.type}
                onChange={e => form.setData('type', e.target.value)}
                options={[
                  { value: 'feature',     label: 'Feature Request' },
                  { value: 'bug',         label: 'Bug Report' },
                  { value: 'improvement', label: 'Improvement' },
                ]}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

Feedback.layout = page => (
  <App title="Feedback">{page}</App>
);
