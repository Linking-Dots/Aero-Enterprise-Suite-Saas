import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, HStack, Field, Input,
  Pagination, Text, Stat, Avatar,
} from '@aero/ui';
import LeaveRail from '../LeaveRail.jsx';

const fmt = (v) => Number(v ?? 0).toLocaleString(undefined, { maximumFractionDigits: 1 });

export default function LeaveBalanceIndex({ balances, year, stats }) {
  const [selectedYear, setSelectedYear] = useState(year ?? new Date().getFullYear());

  function handleYearChange(e) {
    const y = Number(e.target.value);
    setSelectedYear(y);
    router.get(
      route('hrm.leave.balance.index'),
      { year: y },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => row.employee ? (
        <HStack gap={2} align="center">
          <Avatar name={row.employee} size={28} />
          <Text>{row.employee}</Text>
        </HStack>
      ) : <Text tone="secondary">—</Text>,
    },
    {
      key: 'leave_type', label: 'Leave Type',
      render: row => row.leave_type ? (
        <HStack gap={2} align="center">
          <span className="lbal-type-swatch" style={{ background: row.leave_type_color || 'var(--aeos-primary)' }} />
          <Text>{row.leave_type}</Text>
        </HStack>
      ) : <Text tone="secondary">—</Text>,
    },
    { key: 'entitled',        label: 'Entitled',        render: row => fmt(row.entitled) },
    { key: 'carried_forward', label: 'Carried Forward', render: row => fmt(row.carried_forward) },
    { key: 'used',            label: 'Used',            render: row => fmt(row.used) },
    {
      key: 'remaining', label: 'Remaining',
      render: row => <Text weight={600}>{fmt(row.remaining)}</Text>,
    },
  ];

  const totalPages  = balances.last_page    ?? 1;
  const currentPage = balances.current_page ?? 1;

  return (
    <>
      <style>{`
        .lbal-type-swatch {
          display: inline-block;
          width: 0.75rem;
          height: 0.75rem;
          border-radius: var(--aeos-r-sm);
          flex-shrink: 0;
        }
      `}</style>

      <IndexPageLayout
        title="Leave Balances"
        breadcrumb={[{ label: 'HRM' }, { label: 'Leave' }, { label: 'Balances' }]}
        kpis={[
          <Stat key="emp"   title="Employees Tracked" value={stats?.tracked_employees ?? 0} icon="users" />,
          <Stat key="ent"   title="Total Entitled"    value={fmt(stats?.total_entitled)}   icon="calendar" iconTone="indigo" />,
          <Stat key="rem"   title="Total Remaining"   value={fmt(stats?.total_remaining)}  icon="check" iconTone="success" />,
        ]}
        filters={
          <HStack gap={3} align="center">
            <Field label="Year">
              <Input
                type="number"
                value={String(selectedYear)}
                onChange={handleYearChange}
                placeholder={String(new Date().getFullYear())}
              />
            </Field>
          </HStack>
        }
        table={
          <DataTable
            columns={columns}
            rows={balances.data ?? []}
            empty="No leave balance records found."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(
                  route('hrm.leave.balance.index'),
                  { year: selectedYear, page },
                  { preserveState: true, preserveScroll: true, replace: true },
                )
              }
            />
          )
        }
      />
    </>
  );
}

LeaveBalanceIndex.layout = page => (
  <App title="Leave Balances" railTitle="Leave management" rail={<LeaveRail />}>{page}</App>
);
