import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, HStack, Field, Input, Pagination, Badge, Text, Mono, Stat, Avatar,
} from '@aero/ui';
import AttendanceRail from '../AttendanceRail.jsx';

const STATUS_INTENT = {
  present: 'success',
  late:    'warning',
  absent:  'danger',
};

function calcHours(punchin, punchout) {
  if (!punchin || !punchout) return '—';
  const diff = (new Date(punchout) - new Date(punchin)) / 1000 / 3600;
  return diff > 0 ? diff.toFixed(2) + 'h' : '—';
}

function formatTime(ts) {
  if (!ts) return '—';
  const d = new Date(ts);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export default function AttendanceDaily({ date, records, filters, stats }) {
  const [dateVal, setDateVal] = useState(filters?.date ?? date ?? '');

  function navigate(newDate) {
    router.get(route('hrm.attendance.daily'), { date: newDate }, { preserveState: true });
  }

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => row.employee?.user?.name ? (
        <HStack gap={2} align="center">
          <Avatar name={row.employee.user.name} size={28} />
          <Text>{row.employee.user.name}</Text>
        </HStack>
      ) : <Text tone="secondary">—</Text>,
    },
    {
      key: 'punchin', label: 'Punch In',
      render: row => <Mono>{formatTime(row.punchin)}</Mono>,
    },
    {
      key: 'punchout', label: 'Punch Out',
      render: row => <Mono>{formatTime(row.punchout)}</Mono>,
    },
    {
      key: 'status', label: 'Status',
      render: row => {
        const label = row.is_late ? 'Late' : (row.status ?? 'Present');
        const intent = row.is_late ? 'warning' : (STATUS_INTENT[row.status] ?? 'neutral');
        return <Badge intent={intent}>{label}</Badge>;
      },
    },
    {
      key: 'hours', label: 'Hours',
      render: row => <Mono tone="secondary">{calcHours(row.punchin, row.punchout)}</Mono>,
    },
  ];

  const totalPages  = records?.last_page    ?? 1;
  const currentPage = records?.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Daily Attendance"
      breadcrumb={[{ label: 'HRM' }, { label: 'Attendance' }, { label: 'Daily' }]}
      kpis={[
        <Stat key="present" title="Present" value={stats?.present ?? 0} icon="checkCircle" iconTone="success" />,
        <Stat key="late"    title="Late"    value={stats?.late    ?? 0} icon="clock"       iconTone="amber" />,
        <Stat key="absent"  title="Absent"  value={stats?.absent  ?? 0} icon="x"           iconTone="danger" />,
      ]}
      filters={
        <HStack gap={3}>
          <Field label="Date">
            <Input
              type="date"
              value={dateVal}
              onChange={e => {
                setDateVal(e.target.value);
                if (e.target.value) navigate(e.target.value);
              }}
            />
          </Field>
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={records?.data ?? []}
          empty="No attendance records for this date."
        />
      }
      pagination={
        totalPages > 1 && (
          <Pagination
            page={currentPage}
            total={totalPages}
            onChange={page => router.get(route('hrm.attendance.daily'), { date: dateVal, page }, { preserveState: true })}
          />
        )
      }
    />
  );
}

AttendanceDaily.layout = page => (
  <App title="Daily Attendance" railTitle="Attendance" rail={<AttendanceRail />}>{page}</App>
);
