import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Button, Badge, Card, DataTable, Avatar,
} from '@aero/ui';

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

export default function PayslipsShow({ payslip }) {
  const snap   = payslip.employee_snapshot ?? {};
  const name   = payslip.employee_name || snap.name || 'Employee';
  const runId  = payslip.payroll_run_id;
  const acct   = payslip.bank_account_number ?? '';
  const masked = acct.length > 4 ? `••••${acct.slice(-4)}` : acct;

  const lineColumns = [
    { key: 'code', label: 'Code', mono: true },
    { key: 'name', label: 'Component' },
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
        <span className={row.kind === 'deduction' ? 'text-danger' : ''}>
          {row.kind === 'deduction' ? '−' : ''}
          {Number(row.amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
        </span>
      ),
    },
  ];

  return (
    <>
      <VStack gap={6}>
        {/* Header */}
        <div className="payslip-header">
          <HStack gap={3} align="center">
            <Avatar name={name} size={40} />
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>Payslip{payslip.run_label ? ` · ${payslip.run_label}` : ''}</Eyebrow>
                <Text size="lg">{name}</Text>
                <HStack gap={2}>
                  {snap.designation && <Text tone="secondary">{snap.designation}</Text>}
                  {snap.designation && snap.department && <Text tone="tertiary">&middot;</Text>}
                  {snap.department && <Text tone="secondary">{snap.department}</Text>}
                  {payslip.employee_code && (
                    <>
                      <Text tone="tertiary">&middot;</Text>
                      <Mono>{payslip.employee_code}</Mono>
                    </>
                  )}
                </HStack>
              </VStack>
            </Box>
            <HStack gap={2}>
              <Button
                as="a"
                href={route('hrm.payroll.payslips.download', payslip.id)}
                intent="soft"
                target="_blank"
              >
                Download PDF
              </Button>
              <Button
                intent="ghost"
                leftIcon="arrowLeft"
                onClick={() => router.get(route('hrm.payroll.runs.show', runId))}
              >
                Back
              </Button>
            </HStack>
          </HStack>
        </div>

        {/* Period / meta */}
        <Card>
          <div className="payslip-meta-grid">
            <div>
              <Eyebrow tone="secondary">Period</Eyebrow>
              <Text>{fmtDate(payslip.period_start)} &rarr; {fmtDate(payslip.period_end)}</Text>
            </div>
            <div>
              <Eyebrow tone="secondary">Gross Pay</Eyebrow>
              <Text size="lg">
                {Number(payslip.gross ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </Text>
            </div>
            <div>
              <Eyebrow tone="secondary">Total Deductions</Eyebrow>
              <Text size="lg">
                {Number(payslip.deductions_total ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </Text>
            </div>
            <div>
              <Eyebrow tone="secondary">Tax Deducted</Eyebrow>
              <Text>
                {Number(payslip.tax ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </Text>
            </div>
          </div>
        </Card>

        {/* Line items */}
        <VStack gap={2}>
          <Eyebrow>Earnings &amp; Deductions</Eyebrow>
          <DataTable
            columns={lineColumns}
            rows={payslip.line_items ?? []}
            empty="No line items."
          />
          <div className="payslip-totals">
            <HStack gap={4} align="center">
              <Box grow />
              <VStack gap={1} align="end">
                <HStack gap={3}>
                  <Text tone="secondary">Tax Deducted</Text>
                  <Text>
                    {Number(payslip.tax ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                  </Text>
                </HStack>
                <HStack gap={3}>
                  <Text>Net Pay</Text>
                  <Text size="lg">
                    {Number(payslip.net ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                  </Text>
                </HStack>
              </VStack>
            </HStack>
          </div>
        </VStack>

        {/* Bank details */}
        {(payslip.bank_name || acct) && (
          <Card>
            <div className="payslip-bank">
              <VStack gap={2}>
                <Eyebrow>Bank Details</Eyebrow>
                <HStack gap={4}>
                  {payslip.bank_name && (
                    <VStack gap={1}>
                      <Text tone="secondary" size="sm">Bank</Text>
                      <Text>{payslip.bank_name}</Text>
                    </VStack>
                  )}
                  {acct && (
                    <VStack gap={1}>
                      <Text tone="secondary" size="sm">Account</Text>
                      <Mono>{masked}</Mono>
                    </VStack>
                  )}
                </HStack>
              </VStack>
            </div>
          </Card>
        )}
      </VStack>
    </>
  );
}

PayslipsShow.layout = page => <App title="Payslip">{page}</App>;
