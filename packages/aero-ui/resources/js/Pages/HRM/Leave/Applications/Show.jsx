import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Badge, Button, Modal, Field, Textarea, Alert, Avatar,
} from '@aero/ui';
import LeaveRail from '../LeaveRail.jsx';

const STATUS_INTENT = {
  pending:   'warning',
  approved:  'success',
  rejected:  'danger',
  cancelled: 'neutral',
};

const fmtDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

function InfoRow({ label, value }) {
  return (
    <VStack gap={0}>
      <Text size="xs" tone="tertiary">{label}</Text>
      <Text>{value ?? '—'}</Text>
    </VStack>
  );
}

export default function ApplicationsShow({ application, permissions }) {
  const [rejectOpen, setRejectOpen] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    reason: '',
  });

  const isPending = application.status === 'pending';
  const statusLabel = application.status
    ? application.status.charAt(0).toUpperCase() + application.status.slice(1)
    : '—';

  function approve() {
    if (!confirm('Approve this leave application?')) return;
    router.post(
      route('hrm.leave.applications.approve', application.id),
      {},
      { preserveScroll: true },
    );
  }

  function submitReject(e) {
    e.preventDefault();
    post(route('hrm.leave.applications.reject', application.id), {
      onSuccess: () => { setRejectOpen(false); reset(); },
    });
  }

  function cancel() {
    if (!confirm('Cancel this leave application?')) return;
    router.post(
      route('hrm.leave.applications.cancel', application.id),
      {},
      { preserveScroll: true },
    );
  }

  const actions = (
    <HStack gap={2}>
      {permissions?.canApprove && isPending && (
        <Button intent="primary" onClick={approve}>
          Approve
        </Button>
      )}
      {permissions?.canApprove && isPending && (
        <Button intent="danger" onClick={() => setRejectOpen(true)}>
          Reject
        </Button>
      )}
      {isPending && (
        <Button intent="ghost" onClick={cancel}>
          Cancel
        </Button>
      )}
      <Button
        intent="soft"
        onClick={() => router.get(route('hrm.leave.applications.index'))}
      >
        Back to List
      </Button>
    </HStack>
  );

  return (
    <>
      <style>{`
        .lapp-type-swatch {
          display: inline-block;
          width: 0.75rem;
          height: 0.75rem;
          border-radius: var(--aeos-r-sm);
          flex-shrink: 0;
        }
      `}</style>

      <DetailPageLayout
        title="Leave Application"
        breadcrumb={[
          { label: 'HRM' },
          { label: 'Leave' },
          { label: 'Applications', href: route('hrm.leave.applications.index') },
          { label: application.employee_name ?? 'Application' },
        ]}
        actions={actions}
      >
        <VStack gap={5}>
          <Card>
            <CardBody>
              <VStack gap={4}>
                <HStack gap={3} align="center">
                  <Avatar name={application.employee_name ?? '—'} size={40} />
                  <VStack gap={0}>
                    <HStack gap={2} align="center">
                      <Text size="lg">{application.employee_name ?? '—'}</Text>
                      <Badge intent={STATUS_INTENT[application.status] ?? 'neutral'}>
                        {statusLabel}
                      </Badge>
                    </HStack>
                    {application.employee_code && (
                      <Text size="sm" tone="secondary">{application.employee_code}</Text>
                    )}
                  </VStack>
                </HStack>

                <HStack gap={6} wrap>
                  <VStack gap={0}>
                    <Text size="xs" tone="tertiary">Leave Type</Text>
                    <HStack gap={2} align="center">
                      <span
                        className="lapp-type-swatch"
                        style={{ background: application.leave_type_color || 'var(--aeos-primary)' }}
                      />
                      <Text>{application.leave_type ?? '—'}</Text>
                    </HStack>
                  </VStack>
                  <InfoRow label="From"       value={fmtDate(application.start_date)} />
                  <InfoRow label="To"         value={fmtDate(application.end_date)} />
                  <InfoRow label="Total Days" value={application.total_days} />
                  <InfoRow label="Applied On" value={fmtDate(application.created_at)} />
                  {application.approver_name && (
                    <InfoRow label="Approved By" value={application.approver_name} />
                  )}
                  {application.rejector_name && (
                    <InfoRow label="Rejected By" value={application.rejector_name} />
                  )}
                </HStack>

                {application.reason && (
                  <VStack gap={1}>
                    <Text size="xs" tone="tertiary">Reason</Text>
                    <Text tone="secondary">{application.reason}</Text>
                  </VStack>
                )}

                {application.rejection_reason && (
                  <Alert intent="danger" title="Rejection Reason">
                    {application.rejection_reason}
                  </Alert>
                )}
              </VStack>
            </CardBody>
          </Card>
        </VStack>
      </DetailPageLayout>

      <Modal
        open={rejectOpen}
        onClose={() => { setRejectOpen(false); reset(); }}
        title="Reject Application"
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
              Please provide a reason for rejecting this leave application.
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

ApplicationsShow.layout = page => (
  <App title="Leave Application" railTitle="Leave management" rail={<LeaveRail />}>{page}</App>
);
