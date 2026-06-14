import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Badge, Button, Card, CardBody, DataTable,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function fmtMoney(val) {
  return Number(val ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
}

export default function SelfServicePayslipShow({ payslip }) {
  const snap = payslip.employee_snapshot ?? {};
  const name = `${snap.first_name ?? ''} ${snap.last_name ?? ''}`.trim();

  const columns = [
    { key: 'code',  label: 'Code',      mono: true },
    { key: 'name',  label: 'Component' },
    {
      key: 'kind', label: 'Type',
      render: row => (
        <Badge intent={row.kind === 'earning' ? 'success' : 'danger'}>
          {row.kind === 'earning' ? 'Earning' : 'Deduction'}
        </Badge>
      ),
    },
    {
      key: 'amount', label: 'Amount',
      render: row => (
        <Mono tone={row.kind === 'deduction' ? 'danger' : undefined}>
          {row.kind === 'deduction' ? '−' : ''}{fmtMoney(row.amount)}
        </Mono>
      ),
    },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        {/* Header */}
        <HStack gap={2} align="center">
          <Box grow>
            <VStack gap={1}>
              <Eyebrow>Payslip</Eyebrow>
              <Text size="lg">{name || 'Employee'}</Text>
              <HStack gap={2}>
                <Text tone="secondary">{snap.designation}</Text>
                {snap.department && (
                  <>
                    <Text tone="tertiary">&middot;</Text>
                    <Text tone="secondary">{snap.department}</Text>
                  </>
                )}
              </HStack>
            </VStack>
          </Box>
          <HStack gap={2}>
            <Button
              as="a"
              href={route('hrm.self-service.payslips.download', payslip.id)}
              intent="soft"
              target="_blank"
            >
              Download PDF
            </Button>
            <Button
              intent="ghost"
              leftIcon="arrowLeft"
              onClick={() => router.get(route('hrm.self-service.payslips'))}
            >
              Back
            </Button>
          </HStack>
        </HStack>

        {/* Period / summary */}
        <Card>
          <CardBody>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
              <VStack gap={1}>
                <Eyebrow tone="secondary">Period</Eyebrow>
                <Mono>{fmt(payslip.period_start)} &rarr; {fmt(payslip.period_end)}</Mono>
              </VStack>
              <VStack gap={1}>
                <Eyebrow tone="secondary">Gross Pay</Eyebrow>
                <Text size="lg">{fmtMoney(payslip.gross)}</Text>
              </VStack>
              <VStack gap={1}>
                <Eyebrow tone="secondary">Total Deductions</Eyebrow>
                <Text size="lg">{fmtMoney(payslip.deductions_total)}</Text>
              </VStack>
              <VStack gap={1}>
                <Eyebrow tone="secondary">Tax Deducted</Eyebrow>
                <Text>{fmtMoney(payslip.tax)}</Text>
              </VStack>
            </div>
          </CardBody>
        </Card>

        {/* Line items */}
        <VStack gap={2}>
          <Eyebrow>Earnings &amp; Deductions</Eyebrow>
          <DataTable
            columns={columns}
            rows={payslip.line_items ?? []}
            empty="No line items."
          />

          <div
            className="flex flex-col gap-2 pt-4 mt-4 border-t aeos-border-divider"
          >
            <HStack gap={4} align="center">
              <Box grow />
              <VStack gap={1} align="end">
                <HStack gap={3}>
                  <Text tone="secondary">Tax Deducted</Text>
                  <Text>{fmtMoney(payslip.tax)}</Text>
                </HStack>
                <HStack gap={3}>
                  <Text>Net Pay</Text>
                  <Text size="lg">{fmtMoney(payslip.net)}</Text>
                </HStack>
              </VStack>
            </HStack>
          </div>
        </VStack>
      </VStack>
    </div>
  );
}

SelfServicePayslipShow.layout = page => <App title="Payslip">{page}</App>;
