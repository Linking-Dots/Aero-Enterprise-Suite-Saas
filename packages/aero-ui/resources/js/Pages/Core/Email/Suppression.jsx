/**
 * Email Suppression — manage suppressed addresses.
 *
 * Props:
 *   entries { data, total, current_page, last_page, per_page }
 *   filters { search }
 */
import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Pagination,
  Modal,
  Field,
  Input,
  Select,
  Button,
  Badge,
  HStack, VStack,
  Text, Mono,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const REASON_OPTIONS = [
  { value: 'manual',      label: 'Manual' },
  { value: 'bounce',      label: 'Bounce' },
  { value: 'complaint',   label: 'Complaint' },
  { value: 'unsubscribe', label: 'Unsubscribe' },
];

const REASON_INTENT = {
  manual:      'neutral',
  bounce:      'danger',
  complaint:   'warning',
  unsubscribe: 'amber',
};

export default function Suppression({ entries, filters }) {
  const toast     = useToast();
  const canAdd    = useHRMAC('core.email_engine.suppression.create');
  const canRemove = useHRMAC('core.email_engine.suppression.delete');

  const [search, setSearch] = useState(filters?.search || '');
  const [showAdd, setShowAdd] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    email:  '',
    reason: 'manual',
    note:   '',
  });

  const applySearch = () => {
    router.get(route('core.email.suppression.index'), { search }, {
      preserveState: true,
      preserveScroll: true,
      only: ['entries', 'filters'],
    });
  };

  const handleAdd = (e) => {
    e.preventDefault();
    post(route('core.email.suppression.store'), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Address added to suppression list.');
        reset();
        setShowAdd(false);
      },
      onError: () => toast.error('Failed to add address.'),
    });
  };

  const handleRemove = (id) => {
    if (!confirm('Remove this address from the suppression list?')) return;
    router.delete(route('core.email.suppression.destroy', id), {
      preserveState: true,
      onSuccess: () => toast.success('Address removed.'),
      onError:   () => toast.error('Failed to remove address.'),
    });
  };

  const columns = [
    {
      key: 'email',
      label: 'Email',
      width: '24%',
      render: row => <Mono size="sm">{row.email}</Mono>,
    },
    {
      key: 'reason',
      label: 'Reason',
      width: '14%',
      render: row => (
        <Badge intent={REASON_INTENT[row.reason] || 'neutral'} size="sm">
          {row.reason}
        </Badge>
      ),
    },
    {
      key: 'note',
      label: 'Note',
      width: '24%',
      render: row => (
        <Text size="sm" tone="secondary">{row.note || '—'}</Text>
      ),
    },
    {
      key: 'added_by',
      label: 'Added By',
      width: '18%',
      render: row => (
        <Text size="sm">{row.added_by || '—'}</Text>
      ),
    },
    {
      key: 'created_at',
      label: 'Added',
      width: '12%',
      render: row => (
        <Mono size="sm">{new Date(row.created_at).toLocaleDateString()}</Mono>
      ),
    },
    {
      key: 'actions',
      label: '',
      width: '8%',
      align: 'right',
      render: row => canRemove ? (
        <Button
          intent="danger"
          size="sm"
          onClick={() => handleRemove(row.id)}
        >
          Remove
        </Button>
      ) : null,
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Suppression List"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Email Engine', href: route('core.email.logs.index') },
          { label: 'Suppression' },
        ]}
        description="Email addresses that will never receive outgoing mail."
        actions={
          canAdd && (
            <Button intent="primary" onClick={() => setShowAdd(true)}>
              Add Address
            </Button>
          )
        }
        filters={
          <HStack gap={3} align="end" wrap>
            <Input
              placeholder="Search email address…"
              value={search}
              onChange={e => setSearch(e.target.value)}
              onKeyDown={e => e.key === 'Enter' && applySearch()}
              leftIcon="search"
            />
            <Button intent="primary" onClick={applySearch}>Search</Button>
            <Button intent="ghost" onClick={() => {
              setSearch('');
              router.get(route('core.email.suppression.index'), {}, {
                preserveState: true, preserveScroll: true, only: ['entries', 'filters'],
              });
            }}>Reset</Button>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={entries?.data || []}
            empty="No suppressed addresses found."
          />
        }
        pagination={
          entries?.last_page > 1 && (
            <Pagination
              page={entries.current_page}
              total={entries.last_page}
              onChange={page => router.get(route('core.email.suppression.index'), { page, search }, {
                preserveState: true,
                preserveScroll: true,
                only: ['entries'],
              })}
            />
          )
        }
      />

      {/* Add Suppression Modal */}
      <Modal
        open={showAdd}
        onClose={() => { setShowAdd(false); reset(); }}
        title="Add to Suppression List"
        size="sm"
      >
        <form onSubmit={handleAdd}>
          <VStack gap={4}>
            <Field label="Email Address" error={errors.email} required>
              <Input
                type="email"
                value={data.email}
                onChange={e => setData('email', e.target.value)}
                placeholder="user@example.com"
                leftIcon="mail"
              />
            </Field>

            <Field label="Reason" error={errors.reason} required>
              <Select
                value={data.reason}
                onChange={e => setData('reason', e.target.value)}
                options={REASON_OPTIONS}
              />
            </Field>

            <Field label="Note" error={errors.note} hint="Optional internal note.">
              <Input
                value={data.note}
                onChange={e => setData('note', e.target.value)}
                placeholder="Optional note…"
              />
            </Field>

            <HStack gap={2} justify="end">
              <Button
                type="button"
                intent="ghost"
                onClick={() => { setShowAdd(false); reset(); }}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                intent="primary"
                loading={processing}
              >
                Add to Suppression
              </Button>
            </HStack>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

Suppression.layout = page => (
  <App title="Suppression List">{page}</App>
);
