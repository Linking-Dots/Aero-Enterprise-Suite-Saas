import { Link } from '@inertiajs/react';
import {
  IndexPageLayout,
  Button,
  EmptyState,
  Select,
  Badge,
  DataTable,
  useHRMAC,
} from '@aero/ui';
import App from '../../App.jsx';

export default function FormsSubmissions({ form, submissions, status }) {
  const canDelete = useHRMAC('forms.submissions.delete');
  const canExport = useHRMAC('forms.submissions.export');

  const columns = [
    { key: 'id', label: 'ID' },
    { key: 'user', label: 'Submitted By' },
    { key: 'date', label: 'Date' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions' },
  ];

  const rows = submissions.data.map((submission) => ({
    id: `#${submission.id}`,
    user: submission.user?.name || 'Guest',
    date: new Date(submission.created_at).toLocaleString(),
    status: (
      <Badge intent={submission.status === 'submitted' ? 'success' : submission.status === 'reviewed' ? 'primary' : 'neutral'}>
        {submission.status}
      </Badge>
    ),
    actions: (
      <>
        <Button
          intent="ghost"
          size="sm"
          leftIcon="eye"
          onClick={() => window.location.href = route('core.forms.submissions.show', { form: form.id, submission: submission.id })}
        >
          View
        </Button>
        {canDelete && (
          <Button
            intent="ghost"
            size="sm"
            leftIcon="trash"
            onClick={() => {
              if (confirm('Are you sure you want to delete this submission?')) {
                window.location.href = route('core.forms.submissions.destroy', { form: form.id, submission: submission.id });
              }
            }}
          >
            Delete
          </Button>
        )}
      </>
    ),
  }));

  return (
    <IndexPageLayout
      title={`Submissions - ${form.name}`}
      breadcrumb={[{ label: 'Forms', href: route('core.forms.index') }, { label: form.name, href: route('core.forms.edit', form.id) }, { label: 'Submissions' }]}
      actions={
        canExport && (
          <Button intent="secondary" leftIcon="download" onClick={() => window.location.href = route('core.forms.submissions.export', form.id)}>
            Export CSV
          </Button>
        )
      }
      filters={
        <Select
          value={status || ''}
          onChange={(e) => window.location.href = route('core.forms.submissions.index', { form: form.id, status: e.target.value || undefined })}
          options={[
            { value: '', label: 'All Status' },
            { value: 'submitted', label: 'Submitted' },
            { value: 'reviewed', label: 'Reviewed' },
            { value: 'archived', label: 'Archived' },
          ]}
        />
      }
    >
      {submissions.data.length === 0 ? (
        <EmptyState
          icon="document-text"
          title="No submissions yet"
          description="Submissions to this form will appear here."
        />
      ) : (
        <DataTable columns={columns} rows={rows} />
      )}
    </IndexPageLayout>
  );
}

FormsSubmissions.layout = page => <App title="Form Submissions">{page}</App>;
