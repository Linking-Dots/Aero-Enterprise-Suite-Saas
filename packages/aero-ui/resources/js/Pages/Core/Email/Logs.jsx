/**
 * Email Logs — paginated log of all outgoing emails.
 *
 * Props:
 *   logs    { data, total, from, to, current_page, last_page, per_page }
 *   filters { search, status }
 *   stats   { sent, failed, pending }
 */
import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Pagination,
  HStack, VStack,
  Text, Mono,
  Badge,
  Button,
  Input,
  Select,
  Stat,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',        label: 'All Statuses' },
  { value: 'sent',    label: 'Sent' },
  { value: 'failed',  label: 'Failed' },
  { value: 'pending', label: 'Pending' },
];

const STATUS_INTENT = {
  sent:    'success',
  failed:  'danger',
  pending: 'warning',
};

export default function EmailLogs({ logs, filters, stats }) {
  const toast    = useToast();
  const canResend = useHRMAC('core.email_engine.logs.resend');

  const [search, setSearch] = useState(filters?.search || '');
  const [status, setStatus] = useState(filters?.status || '');

  const applyFilters = () => {
    router.get(route('core.email.logs.index'), { search, status }, {
      preserveState: true,
      preserveScroll: true,
      only: ['logs', 'filters'],
    });
  };

  const resetFilters = () => {
    setSearch('');
    setStatus('');
    router.get(route('core.email.logs.index'), {}, {
      preserveState: true,
      preserveScroll: true,
      only: ['logs', 'filters'],
    });
  };

  const handleResend = (id) => {
    router.post(route('core.email.logs.resend', id), {}, {
      preserveState: true,
      onSuccess: () => toast.success('Email queued for resend.'),
      onError:   () => toast.error('Failed to resend email.'),
    });
  };

  const columns = [
    {
      key: 'recipient',
      label: 'Recipient',
      width: '22%',
      render: row => <Mono size="sm">{row.recipient}</Mono>,
    },
    {
      key: 'subject',
      label: 'Subject',
      width: '22%',
      render: row => (
        <Text size="sm" className="aeos-text-truncate">
          {row.subject}
        </Text>
      ),
    },
    {
      key: 'notification_type',
      label: 'Type',
      width: '14%',
      render: row => (
        <Badge intent="neutral" size="sm">{row.notification_type || '—'}</Badge>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      width: '10%',
      render: row => (
        <Badge intent={STATUS_INTENT[row.status] || 'neutral'} size="sm">
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'attempts',
      label: 'Attempts',
      width: '10%',
      render: row => (
        <Text size="sm">{row.attempts ?? 0} / {row.max_attempts ?? 3}</Text>
      ),
    },
    {
      key: 'sent_at',
      label: 'Sent At',
      width: '14%',
      render: row => row.sent_at
        ? <Mono size="sm">{new Date(row.sent_at).toLocaleString()}</Mono>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'actions',
      label: '',
      width: '8%',
      align: 'right',
      render: row => canResend && row.status === 'failed' ? (
        <Button
          intent="soft"
          size="sm"
          onClick={() => handleResend(row.id)}
        >
          Resend
        </Button>
      ) : null,
    },
  ];

  return (
    <IndexPageLayout
      title="Email Logs"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Email Engine', href: route('core.email.logs.index') },
        { label: 'Email Logs' },
      ]}
      description="View all outgoing email activity and delivery status."
      kpis={[
        <Stat key="sent"    title="Sent"    value={stats?.sent    ?? 0} icon="mail"       iconTone="success" />,
        <Stat key="failed"  title="Failed"  value={stats?.failed  ?? 0} icon="xCircle"    iconTone="danger" />,
        <Stat key="pending" title="Pending" value={stats?.pending ?? 0} icon="clock"      iconTone="amber" />,
      ]}
      filters={
        <HStack gap={3} align="end" wrap>
          <Input
            placeholder="Search recipient or subject…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && applyFilters()}
            leftIcon="search"
          />
          <Select
            value={status}
            onChange={e => setStatus(e.target.value)}
            options={STATUS_OPTIONS}
          />
          <Button intent="primary" onClick={applyFilters}>Filter</Button>
          <Button intent="ghost"   onClick={resetFilters}>Reset</Button>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={logs?.data || []}
          empty="No email logs found."
        />
      }
      pagination={
        logs?.last_page > 1 && (
          <Pagination
            page={logs.current_page}
            total={logs.last_page}
            onChange={page => router.get(route('core.email.logs.index'), { page, search, status }, {
              preserveState: true,
              preserveScroll: true,
              only: ['logs'],
            })}
          />
        )
      }
    />
  );
}

EmailLogs.layout = page => (
  <App title="Email Logs">{page}</App>
);
