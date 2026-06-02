import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  DetailPageLayout, VStack, HStack, Text, Eyebrow,
  Badge, Button, Card, CardBody, DataTable,
  Modal, Field, Textarea, Alert,
} from '@aero/ui';

const STATUS_INTENT = {
  draft:     'neutral',
  submitted: 'info',
  approved:  'success',
  rejected:  'danger',
  paid:      'amber',
};

function statusLabel(status) {
  return status
    ? status.charAt(0).toUpperCase() + status.slice(1)
    : '—';
}

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function ClaimsShow({ claim, can }) {
  const [rejectOpen, setRejectOpen] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    reason: '',
  });

  const isSubmitted = claim.status === 'submitted';
  const isRejected  = claim.status === 'rejected';

  function approve() {
    router.post(
      route('hrm.expenses.claims.approve', claim.id),
      {},
      { preserveScroll: true },
    );
  }

  function submitReject(e) {
    e.preventDefault();
    post(route('hrm.expenses.claims.reject', claim.id), {
      onSuccess: () => { setRejectOpen(false); reset(); },
    });
  }

  const itemColumns = [
    {
      key: 'category', label: 'Category',
      render: row => <Text>{row.category?.name ?? '—'}</Text>,
    },
    {
      key: 'expense_date', label: 'Expense Date',
      render: row => <Text>{row.expense_date ?? '—'}</Text>,
    },
    {
      key: 'amount', label: 'Amount',
      render: row => <Text>{Number(row.amount ?? 0).toFixed(2)}</Text>,
    },
    {
      key: 'description', label: 'Description',
      render: row => <Text tone="secondary">{row.description || '—'}</Text>,
    },
  ];

  const actions = (
    <HStack gap={2}>
      {can?.approve && isSubmitted && (
        <>
          <Button intent="primary" onClick={approve}>Approve</Button>
          <Button intent="danger" onClick={() => setRejectOpen(true)}>Reject</Button>
        </>
      )}
      <Button
        intent="soft"
        onClick={() => router.get(route('hrm.expenses.claims.index'))}
      >
        Back to List
      </Button>
    </HStack>
  );

  return (
    <>
      <DetailPageLayout
        title={claim.title ?? claim.reference ?? 'Expense Claim'}
        breadcrumb={[
          { label: 'HRM' },
          { label: 'Expenses' },
          { label: 'Claims', href: route('hrm.expenses.claims.index') },
          { label: claim.reference ?? `#${claim.id}` },
        ]}
        actions={actions}
      >
        <VStack gap={5}>
          <Card>
            <CardBody>
              <VStack gap={4}>
                <HStack gap={2} align="center">
                  <Text size="lg">{claim.reference ?? `#${claim.id}`}</Text>
                  <Badge intent={STATUS_INTENT[claim.status] ?? 'neutral'}>
                    {statusLabel(claim.status)}
                  </Badge>
                </HStack>

                <HStack gap={6} wrap>
                  <InfoRow label="Employee"    value={claim.employee?.name} />
                  <InfoRow label="Claim Date"  value={claim.claim_date} />
                  <InfoRow label="Total Amount"
                    value={`${claim.currency ?? ''} ${Number(claim.total_amount ?? 0).toFixed(2)}`}
                  />
                  {claim.reviewer && (
                    <InfoRow label="Reviewed By" value={claim.reviewer?.name} />
                  )}
                </HStack>

                {isRejected && claim.rejection_reason && (
                  <Alert intent="warning" title="Rejection Reason">
                    {claim.rejection_reason}
                  </Alert>
                )}
              </VStack>
            </CardBody>
          </Card>

          <VStack gap={3}>
            <Eyebrow>Expense Items</Eyebrow>
            <DataTable
              columns={itemColumns}
              rows={claim.items ?? []}
              empty="No items on this claim."
            />
          </VStack>

          {(claim.receipts ?? []).length > 0 && (
            <VStack gap={3}>
              <Eyebrow>Receipts</Eyebrow>
              <Card>
                <CardBody>
                  <VStack gap={2}>
                    {claim.receipts.map((receipt, i) => (
                      <HStack key={i} gap={2} align="center">
                        <Text tone="secondary">{receipt.filename ?? receipt.original_name ?? `Receipt ${i + 1}`}</Text>
                      </HStack>
                    ))}
                  </VStack>
                </CardBody>
              </Card>
            </VStack>
          )}
        </VStack>
      </DetailPageLayout>

      <Modal
        open={rejectOpen}
        onClose={() => { setRejectOpen(false); reset(); }}
        title="Reject Expense Claim"
        size="md"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="danger" loading={processing} onClick={submitReject}>
              Confirm Rejection
            </Button>
            <Button type="button" intent="ghost" onClick={() => { setRejectOpen(false); reset(); }}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submitReject}>
          <VStack gap={4}>
            <Text tone="secondary">
              Please provide a reason for rejecting this expense claim.
            </Text>
            <Field label="Rejection Reason" error={errors.reason} required>
              <Textarea
                value={data.reason}
                onChange={e => setData('reason', e.target.value)}
                placeholder="Enter reason for rejection"
                rows={4}
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

ClaimsShow.layout = page => <App title="Expense Claim">{page}</App>;
