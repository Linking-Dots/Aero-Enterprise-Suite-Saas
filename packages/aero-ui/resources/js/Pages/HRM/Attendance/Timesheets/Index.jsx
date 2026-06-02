import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Field, Input, Button, Badge, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  draft:     'neutral',
  submitted: 'warning',
  approved:  'success',
};

const WEEK_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

function addDays(dateStr, n) {
  const d = new Date(dateStr);
  d.setDate(d.getDate() + n);
  return d.toISOString().slice(0, 10);
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

export default function TimesheetsIndex({ timesheet, filters }) {
  const [weekVal, setWeekVal] = useState(filters?.week ?? timesheet?.week_start ?? '');

  // Build one row per day of the week
  const weekStart = timesheet?.week_start ?? '';
  const entries   = timesheet?.entries   ?? [];

  // Indexed by entry_date for quick lookup
  const entryMap = {};
  for (const e of entries) {
    entryMap[e.entry_date] = e;
  }

  // Build editable rows — one per weekday
  const initialRows = WEEK_DAYS.map((_, idx) => {
    const date  = weekStart ? addDays(weekStart, idx) : '';
    const entry = date ? (entryMap[date] ?? null) : null;
    return {
      entry_id:   entry?.id        ?? null,
      entry_date: date,
      project:    entry?.project   ?? '',
      task:       entry?.task      ?? '',
      hours:      entry?.hours     ?? '',
    };
  });

  const [rows,    setRows]    = useState(initialRows);
  const [saving,  setSaving]  = useState(false);
  const [saved,   setSaved]   = useState(false);
  const [saveErr, setSaveErr] = useState(null);

  function updateRow(idx, field, value) {
    setSaved(false);
    setRows(prev => prev.map((r, i) => i === idx ? { ...r, [field]: value } : r));
  }

  function navigate(newWeek) {
    router.get(route('hrm.attendance.timesheets.index'), { week: newWeek }, { preserveState: false });
  }

  function save() {
    if (!timesheet?.id) return;
    setSaving(true);
    setSaveErr(null);
    router.put(
      route('hrm.attendance.timesheets.update', timesheet.id),
      { entries: rows },
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => setSaved(true),
        onError:   (e) => setSaveErr(Object.values(e)[0] ?? 'Save failed.'),
        onFinish:  () => setSaving(false),
      },
    );
  }

  const totalHours = rows.reduce((sum, r) => sum + (parseFloat(r.hours) || 0), 0);

  const statusLabel = timesheet?.status
    ? timesheet.status.charAt(0).toUpperCase() + timesheet.status.slice(1)
    : 'Draft';

  return (
    <>
      <style>{`
        .ts-page {
          padding: 1.5rem;
        }
        .ts-header {
          margin-bottom: 1.5rem;
        }
        .ts-grid {
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
          overflow: hidden;
        }
        .ts-table {
          border-collapse: collapse;
          width: 100%;
        }
        .ts-table th,
        .ts-table td {
          padding: 0.625rem 0.75rem;
          border-bottom: 1px solid var(--aeos-divider);
          text-align: left;
        }
        .ts-table th {
          background: var(--aeos-bg-surface);
          font-size: 0.75rem;
          font-weight: 600;
          color: var(--aeos-text-secondary);
        }
        .ts-table tr:last-child td {
          border-bottom: none;
        }
        .ts-table td.ts-day-label {
          min-width: 100px;
          white-space: nowrap;
        }
        .ts-input-cell {
          min-width: 140px;
        }
        .ts-hours-cell {
          min-width: 80px;
        }
        .ts-total-row td {
          background: var(--aeos-bg-surface);
          font-weight: 600;
        }
        .ts-footer {
          margin-top: 1rem;
        }
      `}</style>

      <div className="ts-page">
        <VStack gap={5}>
          <div className="ts-header">
            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Eyebrow>Attendance</Eyebrow>
              </HStack>
              <HStack gap={3} align="center">
                <Text size="lg">Timesheet</Text>
                {timesheet?.status && (
                  <Badge intent={STATUS_INTENT[timesheet.status] ?? 'neutral'}>
                    {statusLabel}
                  </Badge>
                )}
              </HStack>
              {timesheet?.total_hours != null && (
                <Text tone="secondary">Total: {timesheet.total_hours}h this week</Text>
              )}
            </VStack>
          </div>

          <HStack gap={3}>
            <Field label="Week Starting">
              <Input
                type="date"
                value={weekVal}
                onChange={e => {
                  setWeekVal(e.target.value);
                  if (e.target.value) navigate(e.target.value);
                }}
              />
            </Field>
          </HStack>

          {saved && (
            <Alert intent="success" title="Timesheet saved successfully." />
          )}
          {saveErr && (
            <Alert intent="danger" title={saveErr} />
          )}

          {!timesheet ? (
            <Text tone="secondary">No timesheet found for this week.</Text>
          ) : (
            <>
              <div className="ts-grid">
                <table className="ts-table">
                  <thead>
                    <tr>
                      <th>Day</th>
                      <th>Date</th>
                      <th>Project</th>
                      <th>Task</th>
                      <th>Hours</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.map((row, idx) => (
                      <tr key={idx}>
                        <td className="ts-day-label">
                          <Text>{WEEK_DAYS[idx]}</Text>
                        </td>
                        <td>
                          <Mono tone="secondary">{formatDate(row.entry_date)}</Mono>
                        </td>
                        <td className="ts-input-cell">
                          <Input
                            value={row.project}
                            onChange={e => updateRow(idx, 'project', e.target.value)}
                            placeholder="Project"
                          />
                        </td>
                        <td className="ts-input-cell">
                          <Input
                            value={row.task}
                            onChange={e => updateRow(idx, 'task', e.target.value)}
                            placeholder="Task"
                          />
                        </td>
                        <td className="ts-hours-cell">
                          <Input
                            type="number"
                            value={row.hours}
                            onChange={e => updateRow(idx, 'hours', e.target.value)}
                            placeholder="0"
                          />
                        </td>
                      </tr>
                    ))}
                    <tr className="ts-total-row">
                      <td colSpan={4}>
                        <Text>Total</Text>
                      </td>
                      <td>
                        <Mono>{totalHours.toFixed(2)}h</Mono>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div className="ts-footer">
                <HStack gap={2}>
                  <Button
                    intent="primary"
                    loading={saving}
                    onClick={save}
                    disabled={timesheet?.status === 'approved'}
                  >
                    Save Timesheet
                  </Button>
                  <Button
                    intent="ghost"
                    type="button"
                    onClick={() => router.get(route('hrm.attendance.timesheets.index'))}
                  >
                    Reset
                  </Button>
                </HStack>
              </div>
            </>
          )}
        </VStack>
      </div>
    </>
  );
}

TimesheetsIndex.layout = page => <App title="Timesheet">{page}</App>;
