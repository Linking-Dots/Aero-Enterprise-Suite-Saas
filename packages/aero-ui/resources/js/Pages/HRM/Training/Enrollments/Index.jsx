import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Field, Input, Select, Toggle, RadioGroup,
  Pagination, Modal, Badge, Text,
} from '@aero/ui';

const STATUS_OPTIONS = [
  { value: '',          label: 'All Statuses' },
  { value: 'enrolled',  label: 'Enrolled' },
  { value: 'attended',  label: 'Attended' },
  { value: 'no_show',   label: 'No Show' },
  { value: 'cancelled', label: 'Cancelled' },
];

function statusIntent(status) {
  switch (status) {
    case 'enrolled':  return 'info';
    case 'attended':  return 'success';
    case 'no_show':   return 'danger';
    case 'cancelled': return 'neutral';
    default:          return 'neutral';
  }
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function EnrollmentsIndex({ enrollments, filters }) {
  const [attendanceModal, setAttendanceModal] = useState(false);
  const [attendanceData, setAttendanceData]   = useState({});
  const [submitting, setSubmitting]            = useState(false);

  /* Enrolled-only rows for attendance marking */
  const enrolledRows = (enrollments.data ?? []).filter(e => e.status === 'enrolled');

  function openAttendance() {
    const init = {};
    enrolledRows.forEach(e => { init[e.id] = 'attended'; });
    setAttendanceData(init);
    setAttendanceModal(true);
  }

  function closeAttendance() {
    setAttendanceModal(false);
    setAttendanceData({});
  }

  function submitAttendance() {
    setSubmitting(true);
    router.post(
      route('hrm.training.enrollments.attendance'),
      { attendance: attendanceData },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => { setSubmitting(false); closeAttendance(); },
      },
    );
  }

  function cancelEnrollment(row) {
    router.delete(
      route('hrm.training.enrollments.cancel', row.id),
      { preserveScroll: true },
    );
  }

  function applyFilters(overrides = {}) {
    router.get(
      route('hrm.training.enrollments.index'),
      { ...filters, ...overrides },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const totalPages  = enrollments.last_page    ?? 1;
  const currentPage = enrollments.current_page ?? 1;

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'course', label: 'Course',
      render: row => <Text>{row.session?.course?.title ?? '—'}</Text>,
    },
    {
      key: 'session_date', label: 'Session Date',
      render: row => <Text>{fmtDate(row.session?.starts_at)}</Text>,
    },
    {
      key: 'source', label: 'Source',
      render: row => <Badge intent="neutral">{row.source ?? '—'}</Badge>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={statusIntent(row.status)}>{row.status ?? '—'}</Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => row.status !== 'cancelled' ? (
        <Button
          intent="danger"
          size="sm"
          onClick={() => cancelEnrollment(row)}
        >
          Cancel
        </Button>
      ) : null,
    },
  ];

  const attendanceStatusOptions = [
    { value: 'attended', label: 'Attended' },
    { value: 'no_show',  label: 'No Show' },
  ];

  return (
    <>
      <IndexPageLayout
        title="Enrollments"
        breadcrumb={[{ label: 'HRM' }, { label: 'Training' }, { label: 'Enrollments' }]}
        actions={
          <HStack gap={3} align="center" wrap>
            <Input
              placeholder="Search..."
              defaultValue={filters?.course_id ?? ''}
              onBlur={e => applyFilters({ course_id: e.target.value, page: 1 })}
            />
            <Input
              placeholder="Session ID..."
              defaultValue={filters?.session_id ?? ''}
              onBlur={e => applyFilters({ session_id: e.target.value, page: 1 })}
            />
            <Select
              options={STATUS_OPTIONS}
              value={filters?.status ?? ''}
              onChange={e => applyFilters({ status: e.target.value, page: 1 })}
            />
            {enrolledRows.length > 0 && (
              <Button intent="primary" onClick={openAttendance}>
                Mark Attendance
              </Button>
            )}
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={enrollments.data ?? []}
            empty="No enrollments found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page => applyFilters({ page })}
            />
          )
        }
      />

      {/* Mark Attendance Modal */}
      <Modal
        open={attendanceModal}
        onClose={closeAttendance}
        title="Mark Attendance"
        size="lg"
        footer={
          <HStack gap={2}>
            <Button
              type="button"
              intent="primary"
              loading={submitting}
              onClick={submitAttendance}
            >
              Save Attendance
            </Button>
            <Button type="button" intent="ghost" onClick={closeAttendance}>Cancel</Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          {enrolledRows.length === 0 ? (
            <Text tone="secondary">No enrolled participants to mark.</Text>
          ) : (
            enrolledRows.map(row => (
              <HStack key={row.id} gap={4} align="center">
                <Text>{row.employee?.user?.name ?? '—'}</Text>
                <RadioGroup
                  options={attendanceStatusOptions}
                  value={attendanceData[row.id] ?? 'attended'}
                  onChange={val => setAttendanceData(prev => ({ ...prev, [row.id]: val }))}
                  name={`attendance-${row.id}`}
                  dir="row"
                />
              </HStack>
            ))
          )}
        </VStack>
      </Modal>
    </>
  );
}

EnrollmentsIndex.layout = page => <App title="Enrollments">{page}</App>;
