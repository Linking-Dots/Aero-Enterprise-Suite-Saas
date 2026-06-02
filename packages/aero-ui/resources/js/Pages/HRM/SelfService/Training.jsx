import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, DataTable, Pagination,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function statusIntent(status) {
  switch (status) {
    case 'enrolled':   return 'primary';
    case 'attended':   return 'success';
    case 'no_show':    return 'danger';
    case 'cancelled':  return 'neutral';
    default:           return 'neutral';
  }
}

function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function SelfServiceTraining({ enrollments }) {
  const totalPages  = enrollments.last_page    ?? 1;
  const currentPage = enrollments.current_page ?? 1;

  const columns = [
    {
      key: 'course', label: 'Course',
      render: row => <Text>{row.session?.course?.title ?? '—'}</Text>,
    },
    {
      key: 'category', label: 'Category',
      render: row => <Text tone="secondary">{row.session?.course?.category?.name ?? '—'}</Text>,
    },
    {
      key: 'session_date', label: 'Session Date',
      render: row => (
        <Text>
          {fmt(row.session?.starts_at)}
          {row.session?.ends_at && <> &ndash; {fmt(row.session.ends_at)}</>}
        </Text>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{statusLabel(row.status)}</Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        row.status === 'attended' && !row.feedback ? (
          <Button
            intent="soft"
            size="sm"
            onClick={() => router.get(route('hrm.training.feedback.create'), { enrollment_id: row.id })}
          >
            Submit Feedback
          </Button>
        ) : null
      ),
    },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">My Training</Text>
        </VStack>

        <DataTable
          columns={columns}
          rows={enrollments.data ?? []}
          empty="No training enrollments found."
        />

        {totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.self-service.training'), { page }, { preserveState: true })}
          />
        )}
      </VStack>
    </div>
  );
}

SelfServiceTraining.layout = page => <App title="My Training">{page}</App>;
