import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack,
  Badge, Text, Mono, Pagination,
} from '@aero/ui';

function statusIntent(status) {
  switch (status) {
    case 'active': return 'success';
    case 'closed': return 'neutral';
    case 'draft':  return 'warning';
    default:       return 'neutral';
  }
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function PulseSurveysIndex({ surveys }) {
  const canCreate = useHRMAC('hrm.analytics.pulse-surveys.create');
  const canSend   = useHRMAC('hrm.analytics.pulse-surveys.send');

  const totalPages  = surveys.last_page    ?? 1;
  const currentPage = surveys.current_page ?? 1;

  function applyPage(page) {
    router.get(
      route('hrm.analytics.pulse-surveys.index'),
      { page },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function sendSurvey(id) {
    router.post(route('hrm.analytics.pulse-surveys.send', id));
  }

  const columns = [
    {
      key: 'title',
      label: 'Title',
      render: row => <Text>{row.title}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>
          {row.status ? row.status.charAt(0).toUpperCase() + row.status.slice(1) : '—'}
        </Badge>
      ),
    },
    {
      key: 'anonymous',
      label: 'Anonymous',
      render: row => <Text>{row.anonymous ? 'Yes' : 'No'}</Text>,
    },
    {
      key: 'opens_at',
      label: 'Opens',
      render: row => <Mono>{formatDate(row.opens_at)}</Mono>,
    },
    {
      key: 'closes_at',
      label: 'Closes',
      render: row => <Mono>{formatDate(row.closes_at)}</Mono>,
    },
    {
      key: 'responses_count',
      label: 'Responses',
      render: row => <Text>{row.responses_count ?? 0}</Text>,
    },
    {
      key: 'actions',
      label: 'Actions',
      render: row => (
        <HStack gap={2}>
          {row.status === 'draft' && canSend && (
            <Button
              intent="primary"
              size="sm"
              onClick={() => sendSurvey(row.id)}
            >
              Send
            </Button>
          )}
          {(row.status === 'active' || row.status === 'closed') && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => router.get(route('hrm.analytics.pulse-surveys.results', row.id))}
            >
              Results
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Pulse Surveys"
      breadcrumb={[{ label: 'HRM' }, { label: 'Analytics' }, { label: 'Pulse Surveys' }]}
      actions={
        canCreate && (
          <Button
            intent="primary"
            onClick={() => router.get(route('hrm.analytics.pulse-surveys.create'))}
          >
            New Survey
          </Button>
        )
      }
      table={
        <DataTable
          columns={columns}
          rows={surveys.data ?? []}
          empty="No pulse surveys found."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={applyPage}
          />
        )
      }
    />
  );
}

PulseSurveysIndex.layout = page => <App title="Pulse Surveys">{page}</App>;
