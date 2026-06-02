import { router, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Button, Badge, Alert, Card,
} from '@aero/ui';

const STATUS_INTENT = {
  present: 'success',
  late:    'warning',
  absent:  'danger',
};

function formatTime(ts) {
  if (!ts) return '—';
  const d = new Date(ts);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

export default function ClockIn({ today_record, employee, server_time }) {
  const { props } = usePage();
  const flash = props.flash ?? {};

  const isPunchedIn  = !!today_record?.punchin;
  const isPunchedOut = !!today_record?.punchout;

  const canClockIn  = !isPunchedIn;
  const canClockOut = isPunchedIn && !isPunchedOut;

  function clockIn() {
    router.post(route('hrm.attendance.clock-in'), { source: 'web' });
  }

  function clockOut() {
    router.post(route('hrm.attendance.clock-out'), {});
  }

  return (
    <>
      <style>{`
        .clockin-page {
          max-width: 480px;
          margin: 0 auto;
          padding: 2rem 1rem;
        }
        .clockin-card {
          padding: 2rem;
        }
        .clockin-time-display {
          font-size: 2.5rem;
          font-family: var(--aeos-font-mono);
          font-weight: 700;
          color: var(--aeos-text-primary);
          line-height: 1;
        }
        .clockin-actions {
          padding-top: 1rem;
        }
      `}</style>

      <div className="clockin-page">
        <VStack gap={5}>
          {flash.success && (
            <Alert intent="success" title={flash.success} />
          )}

          <Card>
            <div className="clockin-card">
              <VStack gap={5}>
                <VStack gap={1}>
                  <Eyebrow>Attendance</Eyebrow>
                  <Text size="lg">
                    {employee?.name ?? 'Unknown Employee'}
                  </Text>
                  {employee?.employee_code && (
                    <Mono tone="secondary">{employee.employee_code}</Mono>
                  )}
                </VStack>

                <VStack gap={2} align="center">
                  <Text tone="secondary">Server Time</Text>
                  <div className="clockin-time-display">
                    {formatTime(server_time) !== '—' ? formatTime(server_time) : server_time}
                  </div>
                </VStack>

                {today_record && (
                  <VStack gap={3}>
                    <HStack gap={2} align="center">
                      <Text tone="secondary">Today&apos;s Status:</Text>
                      <Badge intent={STATUS_INTENT[today_record.status] ?? 'neutral'}>
                        {today_record.is_late ? 'Late' : (today_record.status ?? 'Present')}
                      </Badge>
                    </HStack>

                    <HStack gap={4}>
                      <VStack gap={1}>
                        <Text tone="tertiary">Punch In</Text>
                        <Mono>{formatTime(today_record.punchin)}</Mono>
                      </VStack>
                      {today_record.punchout && (
                        <VStack gap={1}>
                          <Text tone="tertiary">Punch Out</Text>
                          <Mono>{formatTime(today_record.punchout)}</Mono>
                        </VStack>
                      )}
                    </HStack>
                  </VStack>
                )}

                <div className="clockin-actions">
                  <HStack gap={3}>
                    <Button
                      intent="primary"
                      fullWidth
                      disabled={!canClockIn}
                      onClick={clockIn}
                      leftIcon="play"
                    >
                      Clock In
                    </Button>
                    <Button
                      intent="soft"
                      fullWidth
                      disabled={!canClockOut}
                      onClick={clockOut}
                      leftIcon="stop"
                    >
                      Clock Out
                    </Button>
                  </HStack>
                </div>
              </VStack>
            </div>
          </Card>
        </VStack>
      </div>
    </>
  );
}

ClockIn.layout = page => <App title="Clock In / Out">{page}</App>;
