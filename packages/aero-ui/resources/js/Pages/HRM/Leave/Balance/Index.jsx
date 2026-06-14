import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, Field, Input,
  Pagination, Text,
} from '@aero/ui';

export default function LeaveBalanceIndex({ balances, year }) {
  const [selectedYear, setSelectedYear] = useState(year ?? new Date().getFullYear());

  function handleYearChange(e) {
    const y = Number(e.target.value);
    setSelectedYear(y);
    router.get(
      route('hrm.leave.balance.index'),
      { year: y },
      { preserveState: true },
    );
  }

  const columns = [
    {
      key: 'employee', label: 'Employee',
      render: row => row.employee?.name ?? <Text tone="secondary">—</Text>,
    },
    {
      key: 'leave_type', label: 'Leave Type',
      render: row => row.leave_type?.name ?? <Text tone="secondary">—</Text>,
    },
    { key: 'entitled',        label: 'Entitled',        render: row => row.entitled },
    { key: 'carried_forward', label: 'Carried Forward', render: row => row.carried_forward },
    { key: 'used',            label: 'Used',            render: row => row.used },
    {
      key: 'remaining', label: 'Remaining',
      render: row => (
        <Text size="sm">
          <strong>{row.remaining}</strong>
        </Text>
      ),
    },
  ];

  const totalPages  = balances.last_page    ?? 1;
  const currentPage = balances.current_page ?? 1;

  return (
    <IndexPageLayout
      title="Leave Balances"
      breadcrumb={[{ label: 'HRM' }, { label: 'Leave' }, { label: 'Balances' }]}
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
                { preserveState: true },
              )
            }
          />
        )
      }
    />
  );
}

LeaveBalanceIndex.layout = page => <App title="Leave Balances">{page}</App>;
