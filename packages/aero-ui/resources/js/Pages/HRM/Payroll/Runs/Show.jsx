import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../../App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Button, Badge, Card,
  DataTable, Modal,
} from '@aero/ui';
import useHRMAC from '../../../../hooks/useHRMAC.js';

function statusIntent(run) {
  if (run.locked_at) return 'neutral';
  switch (run.status) {
    case 'approved': return 'success';
    case 'draft':    return 'warning';
    default:         return 'neutral';
  }
}

function statusLabel(run) {
  if (run.locked_at) return 'Locked';
  return run.status.charAt(0).toUpperCase() + run.status.slice(1);
}

export default function RunsShow({ run }) {
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
      render: row => row.employee?.name ?? '—',
    },
    {
      key: 'gross', label: 'Gross',
      render: row => Number(row.gross ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'tax', label: 'Tax',
      render: row => Number(row.tax ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'net', label: 'Net',
      render: row => Number(row.net ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
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

  return (
    <>
      <style>{`
        .run-show-header {
          padding-bottom: 1.25rem;
          border-bottom: 1px solid var(--aeos-divider);
          margin-bottom: 1.5rem;
        }
        .run-summary-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
          gap: 1rem;
        }
        .run-summary-item {
          padding: 1rem 1.25rem;
        }
        .run-summary-label {
          margin-bottom: 0.25rem;
        }
      `}</style>

      <VStack gap={6}>
        {/* Header */}
        <div className="run-show-header">
          <HStack gap={2} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>Payroll Run</Eyebrow>
                <HStack gap={2} align="center">
                  <Text size="lg">{run.label}</Text>
                  <Badge intent={statusIntent(run)}>{statusLabel(run)}</Badge>
                  {run.locked_at && (
                    <Badge intent="neutral">Locked</Badge>
                  )}
                </HStack>
              </VStack>
            </Box>
            <HStack gap={2}>
              {canLock && !run.locked_at && (
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
          </HStack>
        </div>

        {/* Summary cards */}
        <Card>
          <div className="run-summary-grid">
            <div className="run-summary-item">
              <div className="run-summary-label">
                <Eyebrow tone="secondary">Period</Eyebrow>
              </div>
              <Mono>{run.period_start} &rarr; {run.period_end}</Mono>
            </div>
            <div className="run-summary-item">
              <div className="run-summary-label">
                <Eyebrow tone="secondary">Total Gross</Eyebrow>
              </div>
              <Text size="lg">
                {Number(run.total_gross ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </Text>
            </div>
            <div className="run-summary-item">
              <div className="run-summary-label">
                <Eyebrow tone="secondary">Total Net</Eyebrow>
              </div>
              <Text size="lg">
                {Number(run.total_net ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
              </Text>
            </div>
          </div>
        </Card>

        {/* Payslips table */}
        <VStack gap={2}>
          <Eyebrow>Payslips</Eyebrow>
          <DataTable
            columns={payslipColumns}
            rows={run.payslips ?? []}
            empty="No payslips generated yet."
          />
        </VStack>
      </VStack>

      {/* Approve & Lock confirm modal */}
      <Modal
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Approve & Lock Payroll Run"
        footer={
          <HStack gap={2}>
            <Button intent="danger" loading={locking} onClick={doApprove}>
              Confirm & Lock
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

RunsShow.layout = page => <App title="Payroll Run">{page}</App>;
