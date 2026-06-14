import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Field, Input, Badge, Text, Eyebrow, Button,
} from '@aero/ui';

const CELL_INTENT = {
  P: 'success',
  L: 'warning',
  A: 'danger',
};

function cellIntent(val) {
  return CELL_INTENT[val] ?? 'neutral';
}

export default function AttendanceMonthly({ month, grid, filters }) {
  const [monthVal, setMonthVal] = useState(filters?.month ?? month ?? '');

  function navigate(newMonth) {
    router.get(route('hrm.attendance.monthly'), { month: newMonth }, { preserveState: true });
  }

  const days = grid?.days ?? [];
  const rows = grid?.rows ?? [];

  return (
    <>
      <style>{`
        .monthly-wrapper {
          padding: 1.5rem;
        }
        .monthly-header {
          margin-bottom: 1.5rem;
        }
        .monthly-table-scroll {
          overflow-x: auto;
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
        }
        .monthly-table {
          border-collapse: collapse;
          width: 100%;
          min-width: max-content;
        }
        .monthly-table th,
        .monthly-table td {
          padding: 0.5rem 0.625rem;
          border-bottom: 1px solid var(--aeos-divider);
          text-align: center;
          white-space: nowrap;
        }
        .monthly-table th {
          background: var(--aeos-bg-surface);
          font-family: var(--aeos-font-body);
          font-size: 0.75rem;
          font-weight: 600;
          color: var(--aeos-text-secondary);
          position: sticky;
          top: 0;
          z-index: 1;
        }
        .monthly-table td:first-child,
        .monthly-table th:first-child {
          position: sticky;
          left: 0;
          z-index: 2;
          background: var(--aeos-bg-surface);
          text-align: left;
          min-width: 180px;
        }
        .monthly-table th:first-child {
          z-index: 3;
        }
        .monthly-table tr:last-child td {
          border-bottom: none;
        }
        .monthly-cell-empty {
          color: var(--aeos-text-tertiary);
          font-size: 0.75rem;
        }
      `}</style>

      <div className="monthly-wrapper">
        <VStack gap={5}>
          <div className="monthly-header">
            <VStack gap={4}>
              <HStack gap={2} align="center">
                <Eyebrow>Attendance</Eyebrow>
              </HStack>
              <HStack gap={3} align="center">
                <Text size="lg">Monthly View</Text>
                <Button
                  intent="ghost"
                  size="sm"
                  onClick={() => router.get(route('hrm.attendance.daily'))}
                >
                  Daily View
                </Button>
              </HStack>
              <HStack gap={3}>
                <Field label="Month">
                  <Input
                    type="month"
                    value={monthVal}
                    onChange={e => {
                      setMonthVal(e.target.value);
                      if (e.target.value) navigate(e.target.value);
                    }}
                  />
                </Field>
              </HStack>
            </VStack>
          </div>

          {rows.length === 0 ? (
            <Text tone="secondary">No attendance data for this month.</Text>
          ) : (
            <div className="monthly-table-scroll">
              <table className="monthly-table">
                <thead>
                  <tr>
                    <th>Employee</th>
                    {days.map(day => (
                      <th key={day}>{new Date(day).getDate()}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {rows.map(row => (
                    <tr key={row.employee_id}>
                      <td><Text>{row.name}</Text></td>
                      {row.cells.map((cell, idx) => (
                        <td key={idx}>
                          {cell ? (
                            <Badge intent={cellIntent(cell)}>{cell}</Badge>
                          ) : (
                            <span className="monthly-cell-empty">—</span>
                          )}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </VStack>
      </div>
    </>
  );
}

AttendanceMonthly.layout = page => <App title="Monthly Attendance">{page}</App>;
