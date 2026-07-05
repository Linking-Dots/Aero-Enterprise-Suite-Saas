import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, HStack, Field, Input, Text, Stat,
} from '@aero/ui';
import LeaveRail from '../LeaveRail.jsx';

/**
 * Build an array of Date objects for every day in [from, to] inclusive.
 */
function buildDayRange(from, to) {
  const days = [];
  const end = new Date(to);
  const cur = new Date(from);
  end.setHours(0, 0, 0, 0);
  cur.setHours(0, 0, 0, 0);
  while (cur <= end) {
    days.push(new Date(cur));
    cur.setDate(cur.getDate() + 1);
  }
  return days;
}

/**
 * Returns true when `day` falls within [event.start, event.end).
 * `event.end` is FullCalendar-exclusive (day after last day).
 */
function eventCoversDay(event, day) {
  const dayTs   = day.getTime();
  const startTs = new Date(event.start).setHours(0, 0, 0, 0);
  const endTs   = new Date(event.end).setHours(0, 0, 0, 0);
  return dayTs >= startTs && dayTs < endTs;
}

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

/**
 * Group days by month for rendering month sections.
 */
function groupByMonth(days) {
  const groups = [];
  days.forEach(day => {
    const key = `${day.getFullYear()}-${day.getMonth()}`;
    const last = groups[groups.length - 1];
    if (last && last.key === key) {
      last.days.push(day);
    } else {
      groups.push({ key, year: day.getFullYear(), month: day.getMonth(), days: [day] });
    }
  });
  return groups;
}

export default function LeaveCalendarIndex({ events, range, stats }) {
  const [from, setFrom] = useState(range?.from ?? '');
  const [to,   setTo]   = useState(range?.to   ?? '');

  function navigate(newFrom, newTo) {
    router.get(
      route('hrm.leave.calendar.index'),
      { from: newFrom, to: newTo },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  const days         = from && to ? buildDayRange(from, to) : [];
  const monthGroups  = groupByMonth(days);
  const safeEvents   = events ?? [];

  const calendar = (
    <div className="lcal-body">
      {days.length === 0 && (
        <div className="lcal-empty-state">
          <Text tone="secondary">Select a date range to view the calendar.</Text>
        </div>
      )}

      {monthGroups.map(group => {
        // Compute leading empty cells so the grid starts on the correct weekday
        const firstDay = group.days[0];
        const leadingEmpties = firstDay.getDay(); // 0=Sun, 6=Sat

        return (
          <div key={group.key} className="lcal-month-section">
            <div className="lcal-month-label">
              {MONTH_NAMES[group.month]} {group.year}
            </div>

            <div className="lcal-grid">
              {DAY_NAMES.map(d => (
                <div key={d} className="lcal-day-header">{d}</div>
              ))}

              {Array.from({ length: leadingEmpties }, (_, i) => (
                <div key={`empty-${i}`} className="lcal-day-cell lcal-day-empty" />
              ))}

              {group.days.map(day => {
                const iso   = day.toISOString().slice(0, 10);
                const todayIso = new Date().toISOString().slice(0, 10);
                const dayEvents = safeEvents.filter(ev => eventCoversDay(ev, day));

                return (
                  <div key={iso} className="lcal-day-cell">
                    <div className={`lcal-day-number${iso === todayIso ? ' lcal-today' : ''}`}>
                      {day.getDate()}
                    </div>
                    {dayEvents.map(ev => (
                      <div
                        key={ev.id}
                        className="lcal-event-pill"
                        style={{ background: ev.color || 'var(--aeos-primary)' }}
                        title={ev.title}
                      >
                        {ev.title}
                      </div>
                    ))}
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );

  return (
    <>
      <style>{`
        .lcal-body { min-width: 0; }
        .lcal-month-section { margin-bottom: 2rem; }
        .lcal-month-section:last-child { margin-bottom: 0; }

        .lcal-month-label {
          font-family: var(--aeos-font-body);
          font-size: 0.75rem;
          font-weight: 600;
          letter-spacing: 0.08em;
          text-transform: uppercase;
          color: var(--aeos-text-secondary);
          margin-bottom: 0.75rem;
        }

        .lcal-grid {
          display: grid;
          grid-template-columns: repeat(7, minmax(0, 1fr));
          gap: 0.375rem;
        }

        .lcal-day-header {
          font-size: 0.7rem;
          font-weight: 600;
          color: var(--aeos-text-tertiary);
          text-align: center;
          padding: 0.25rem 0;
          text-transform: uppercase;
        }

        .lcal-day-cell {
          min-width: 0;
          min-height: 5rem;
          border: 1px solid var(--aeos-divider);
          border-radius: var(--aeos-r-sm);
          background: var(--aeos-bg-surface);
          padding: 0.375rem;
          display: flex;
          flex-direction: column;
          gap: 0.2rem;
        }

        .lcal-day-cell.lcal-day-empty {
          background: transparent;
          border-color: transparent;
        }

        .lcal-day-number {
          font-size: 0.75rem;
          font-weight: 600;
          color: var(--aeos-text-secondary);
          line-height: 1;
          margin-bottom: 0.15rem;
        }

        .lcal-day-number.lcal-today { color: var(--aeos-primary); }

        .lcal-event-pill {
          font-size: 0.65rem;
          font-weight: 500;
          color: #fff;
          border-radius: var(--aeos-r-sm);
          padding: 0.1rem 0.35rem;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          line-height: 1.4;
        }

        .lcal-empty-state {
          text-align: center;
          padding: 3rem 0;
          color: var(--aeos-text-secondary);
        }

        @media (max-width: 640px) {
          .lcal-day-cell { min-height: 3.5rem; }
          .lcal-event-pill { font-size: 0.55rem; }
        }
      `}</style>

      <IndexPageLayout
        title="Leave Calendar"
        breadcrumb={[{ label: 'HRM' }, { label: 'Leave' }, { label: 'Calendar' }]}
        kpis={[
          <Stat key="today" title="On Leave Today"    value={stats?.on_leave_today ?? 0}     icon="user" iconTone="amber" />,
          <Stat key="range" title="Approved in Range" value={stats?.in_range ?? 0}           icon="calendar" iconTone="indigo" />,
          <Stat key="emp"   title="Employees"         value={stats?.employees_affected ?? 0} icon="users" iconTone="success" />,
        ]}
        filters={
          <HStack gap={3} align="center" wrap>
            <Field label="From">
              <Input
                type="date"
                value={from}
                onChange={e => {
                  setFrom(e.target.value);
                  if (to) navigate(e.target.value, to);
                }}
              />
            </Field>
            <Field label="To">
              <Input
                type="date"
                value={to}
                onChange={e => {
                  setTo(e.target.value);
                  if (from) navigate(from, e.target.value);
                }}
              />
            </Field>
          </HStack>
        }
        table={calendar}
      />
    </>
  );
}

LeaveCalendarIndex.layout = page => (
  <App title="Leave Calendar" railTitle="Leave management" rail={<LeaveRail />}>{page}</App>
);
