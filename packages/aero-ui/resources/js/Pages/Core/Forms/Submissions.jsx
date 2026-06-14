import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Pagination,
  HStack,
  Text, Mono,
  Select,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function FormsSubmissions({ form, submissions, filters = {} }) {
  const toast     = useToast();
  const canDelete = useHRMAC('forms.submissions.delete');
  const canExport = useHRMAC('forms.submissions.export');

  const handleDelete = (submission) => {
    if (!confirm('Delete this submission?')) return;
    router.delete(route('core.forms.submissions.destroy', { form: form.id, submission: submission.id }), {
      onSuccess: () => toast.success('Submission deleted.'),
      onError:   () => toast.error('Failed to delete submission.'),
    });
  };

  const handleExport = () => {
    window.open(route('core.forms.submissions.export', form.id), '_blank');
  };

  const truncateJson = (data) => {
    if (!data) return '—';
    const str = typeof data === 'string' ? data : JSON.stringify(data);
    return str.length > 80 ? str.slice(0, 80) + '…' : str;
  };

  const statusBadgeIntent = (s) => {
    const map = { submitted: 'success', reviewed: 'primary', archived: 'neutral' };
    return map[s] ?? 'neutral';
  };

  const columns = [
    {
      key: 'submitted_by', label: 'Submitted By', width: '18%',
      render: (row) => <Text size="sm">{row.user?.name || row.submitted_by || 'Anonymous'}</Text>,
    },
    {
      key: 'ip_address', label: 'IP Address', width: '14%',
      render: (row) => <Mono size="sm" tone="secondary">{row.ip_address || '—'}</Mono>,
    },
    {
      key: 'data', label: 'Data', width: '32%',
      render: (row) => (
        <Text tone="secondary" size="sm">{truncateJson(row.data)}</Text>
      ),
    },
    {
      key: 'status', label: 'Status', width: '10%',
      render: (row) => row.status
        ? <Badge intent={statusBadgeIntent(row.status)}>{row.status}</Badge>
        : null,
    },
    {
      key: 'created_at', label: 'Submitted', width: '12%',
      render: (row) => new Date(row.created_at).toLocaleString(),
    },
    {
      key: 'actions', label: '', width: '14%', align: 'right',
      render: (row) => (
        <HStack gap={2} justify="end">
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(row)}>
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title={`Submissions — ${form.title}`}
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Forms',     href: route('core.forms.index') },
        { label: form.title,  href: route('core.forms.edit', form.id) },
        { label: 'Submissions' },
      ]}
      description={`All submissions for form: ${form.title}`}
      actions={
        canExport && (
          <Button intent="soft" leftIcon="arrowDownTray" onClick={handleExport}>
            Export CSV
          </Button>
        )
      }
      filters={
        <Select
          value={filters.status || ''}
          onChange={e => router.get(
            route('core.forms.submissions.index', form.id),
            { status: e.target.value || undefined },
            { preserveState: true, preserveScroll: true },
          )}
          options={[
            { value: '',          label: 'All Statuses' },
            { value: 'submitted', label: 'Submitted' },
            { value: 'reviewed',  label: 'Reviewed' },
            { value: 'archived',  label: 'Archived' },
          ]}
        />
      }
      table={
        <DataTable
          columns={columns}
          rows={submissions?.data || []}
          empty="No submissions yet."
        />
      }
      pagination={
        submissions?.last_page > 1 && (
          <Pagination
            page={submissions.current_page}
            total={submissions.last_page}
            onChange={page => router.get(route('core.forms.submissions.index', form.id), { page }, {
              preserveState: true, preserveScroll: true, only: ['submissions'],
            })}
          />
        )
      }
    />
  );
}

FormsSubmissions.layout = page => <App title="Form Submissions">{page}</App>;
