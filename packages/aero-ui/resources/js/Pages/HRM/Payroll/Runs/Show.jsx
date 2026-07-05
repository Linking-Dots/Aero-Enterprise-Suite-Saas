import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  DetailPageLayout, VStack, HStack, Text, Eyebrow, Button, Badge, Card, CardBody,
  DataTable, Modal, Stat, Avatar,
} from '@aero/ui';
import { useHRMAC } from '@/hooks/useHRMAC';
import PayrollRail from '../PayrollRail.jsx';

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const moneyCompact = (v) =>
  Number(v ?? 0).toLocaleString(undefined, { notation: 'compact', maximumFractionDigits: 1 });
const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

function statusIntent(run) {
  if (run.is_locked || run.locked_at) return 'neutral';
  switch (run.status) {
    case 'approved': return 'success';
    case 'draft':    return 'warning';
    default:         return 'neutral';
  }
}

function statusLabel(run) {
  if (run.is_locked || run.locked_at) return 'Locked';
  return run.status ? run.status.charAt(0).toUpperCase() + run.status.slice(1) : '—';
}

export default function RunsShow({ run, payslips = [] }) {
  const canLock = useHRMAC('hrm.payroll.payroll-run.lock');
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [locking,     setLocking]     = useState(false);

  function doApprove() {
    setLocking(true);
    router.post(
      route('hrm.payroll.runs.approve', run.id),
      {},
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
          setLocking(false);
          setConfirmOpen(false);
        },
      },
    );
  }

  const payslipColumns = [
    {
      key: 'employee', label: 'Employee',
      render: row => (
        <HStack gap={2} align="center">
          <Avatar name={row.employee_name || row.employee_code || '—'} size={28} />
          <VStack gap={0}>
            <Text>{row.employee_name || '—'}</Text>
            {row.employee_code && <Text size="xs" tone="tertiary">{row.employee_code}</Text>}
          </VStack>
        </HStack>
      ),
    },
    { key: 'gross', label: 'Gross', render: row => money(row.gross) },
    { key: 'tax',   label: 'Tax',   render: row => money(row.tax) },
    { key: 'net',   label: 'Net',   render: row => money(row.net) },
    {
      key: 'actions', label: '',
      render: row => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('hrm.payroll.payslips.show', row.id))}
        >
          View Payslip
        </Button>
      ),
    },
  ];

  const isLocked = run.is_locked || run.locked_at;

  const actions = (
    <HStack gap={2}>
      {canLock && !isLocked && (
        <Button intent="primary" onClick={() => setConfirmOpen(true)}>
          Approve &amp; Lock
        </Button>
      )}
      <Button
        intent="ghost"
        leftIcon="arrowLeft"
        onClick={() => router.get(route('hrm.payroll.runs.index'))}
      >
        Back
      </Button>
    </HStack>
  );

  return (
    <>
      <DetailPageLayout
        title={run.label ?? 'Payroll Run'}
        breadcrumb={[
          { label: 'HRM' },
          { label: 'Payroll' },
          { label: 'Runs', href: route('hrm.payroll.runs.index') },
          { label: run.label ?? 'Run' },
        ]}
        actions={actions}
        status={<Badge intent={statusIntent(run)}>{statusLabel(run)}</Badge>}
      >
        <VStack gap={5}>
          <div className="aeos-kpi-row">
            <div className="aeos-kpi-col">
              <Stat title="Gross" value={moneyCompact(run.total_gross)} icon="trending" iconTone="indigo" />
            </div>
            <div className="aeos-kpi-col">
              <Stat title="Net" value={moneyCompact(run.total_net)} icon="check" iconTone="success" />
            </div>
            <div className="aeos-kpi-col">
              <Stat title="Payslips" value={payslips.length} icon="users" />
            </div>
          </div>

          <Card>
            <CardBody>
              <HStack gap={6} wrap>
                <VStack gap={0}>
                  <Eyebrow tone="secondary">Period</Eyebrow>
                  <Text>{fmtDate(run.period_start)} &rarr; {fmtDate(run.period_end)}</Text>
                </VStack>
                <VStack gap={0}>
                  <Eyebrow tone="secondary">Total Gross</Eyebrow>
                  <Text>{money(run.total_gross)}</Text>
                </VStack>
                <VStack gap={0}>
                  <Eyebrow tone="secondary">Total Net</Eyebrow>
                  <Text>{money(run.total_net)}</Text>
                </VStack>
                <VStack gap={0}>
                  <Eyebrow tone="secondary">Status</Eyebrow>
                  <Badge intent={statusIntent(run)}>{statusLabel(run)}</Badge>
                </VStack>
                {run.approved_at && (
                  <VStack gap={0}>
                    <Eyebrow tone="secondary">Approved</Eyebrow>
                    <Text>{fmtDate(run.approved_at)}</Text>
                  </VStack>
                )}
              </HStack>
            </CardBody>
          </Card>

          <VStack gap={2}>
            <Eyebrow>Payslips</Eyebrow>
            <DataTable
              columns={payslipColumns}
              rows={payslips}
              empty="No payslips generated yet."
            />
          </VStack>
        </VStack>
      </DetailPageLayout>

      {/* Approve & Lock confirm modal */}
      <Modal
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Approve & Lock Payroll Run"
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={locking} onClick={doApprove}>
              Confirm &amp; Lock
            </Button>
            <Button intent="ghost" onClick={() => setConfirmOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text>
            This will lock the payroll run permanently. This cannot be undone.
          </Text>
          <Text tone="secondary">
            Once locked, no changes can be made to this run or its payslips.
          </Text>
        </VStack>
      </Modal>
    </>
  );
}

RunsShow.layout = page => (
  <App title="Payroll Run" railTitle="Payroll" rail={<PayrollRail />}>{page}</App>
);
