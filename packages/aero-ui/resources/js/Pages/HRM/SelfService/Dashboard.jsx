import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Badge, Button, Card, CardBody,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function fmtMoney(val) {
  return Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
}

export default function SelfServiceDashboard({
  employee,
  leaveBalances,
  pendingLeavesCount,
  latestPayslip,
  upcomingTrainings,
}) {
  const name = `${employee?.first_name ?? ''} ${employee?.last_name ?? ''}`.trim();

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">Welcome, {name}</Text>
          <Text tone="secondary">{employee?.employee_code}</Text>
        </VStack>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {/* Leave Balance card */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Leave Balances</Eyebrow>
                {(leaveBalances ?? []).length === 0 ? (
                  <Text tone="secondary">No leave balances found.</Text>
                ) : (
                  <VStack gap={2}>
                    {(leaveBalances ?? []).map((b, i) => (
                      <HStack key={i} gap={2} align="center">
                        <Box grow>
                          <Text>{b.leaveType?.name ?? '—'}</Text>
                        </Box>
                        <Text tone="secondary">
                          {b.remaining ?? 0} / {b.entitled ?? 0} days
                        </Text>
                      </HStack>
                    ))}
                  </VStack>
                )}
                <Button
                  intent="soft"
                  onClick={() => router.get(route('hrm.self-service.leaves'))}
                >
                  Apply for Leave
                  {pendingLeavesCount > 0 && ` (${pendingLeavesCount} pending)`}
                </Button>
              </VStack>
            </CardBody>
          </Card>

          {/* Latest Payslip card */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Latest Payslip</Eyebrow>
                {latestPayslip ? (
                  <>
                    <VStack gap={1}>
                      <Text tone="secondary">
                        Period: {fmt(latestPayslip.period_start)} &ndash; {fmt(latestPayslip.period_end)}
                      </Text>
                      <HStack gap={2} align="center">
                        <Text tone="secondary">Net Pay</Text>
                        <Text size="lg">{fmtMoney(latestPayslip.net)}</Text>
                      </HStack>
                    </VStack>
                    <Button
                      intent="soft"
                      onClick={() => router.get(route('hrm.self-service.payslips.show', latestPayslip.id))}
                    >
                      View Payslip
                    </Button>
                  </>
                ) : (
                  <Text tone="secondary">No payslips yet.</Text>
                )}
              </VStack>
            </CardBody>
          </Card>

          {/* Upcoming Training card */}
          <Card>
            <CardBody>
              <VStack gap={3}>
                <Eyebrow>Upcoming Training</Eyebrow>
                {(upcomingTrainings ?? []).length === 0 ? (
                  <Text tone="secondary">No upcoming sessions.</Text>
                ) : (
                  <VStack gap={2}>
                    {(upcomingTrainings ?? []).map((e, i) => (
                      <HStack key={i} gap={2} align="center">
                        <Box grow>
                          <Text>{e.session?.course?.title ?? '—'}</Text>
                        </Box>
                        <Mono tone="secondary">{fmt(e.session?.starts_at)}</Mono>
                      </HStack>
                    ))}
                  </VStack>
                )}
              </VStack>
            </CardBody>
          </Card>
        </div>
      </VStack>
    </div>
  );
}

SelfServiceDashboard.layout = page => <App title="My Dashboard">{page}</App>;
