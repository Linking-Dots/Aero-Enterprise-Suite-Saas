import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Badge,
  Field,
  Input,
  Select,
  Textarea,
  Button,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const OP_STATUS_INTENT = {
  pending:    'warning',
  running:    'amber',
  completed:  'success',
  failed:     'danger',
  partial:    'warning',
};

const OPERATION_TYPES = [
  { value: '',          label: 'Select operation...' },
  { value: 'suspend',   label: 'Suspend' },
  { value: 'activate',  label: 'Activate' },
  { value: 'freeze',    label: 'Freeze' },
  { value: 'unfreeze',  label: 'Unfreeze' },
  { value: 'archive',   label: 'Archive' },
  { value: 'change_plan', label: 'Change Plan' },
  { value: 'email',     label: 'Send Email' },
];

const OPS_WITH_REASON   = ['suspend', 'freeze', 'archive'];
const OPS_WITH_PLAN     = ['change_plan'];
const OPS_WITH_EMAIL    = ['email'];

export default function TenantsBulk({ operations, plans }) {
  const toast = useToast();
  const canExecute = useHRMAC('tenant-operations.bulk-actions.bulk-suspend');

  const form = useForm({
    operation:  '',
    tenant_ids: '',
    reason:     '',
    plan_id:    '',
    subject:    '',
    body:       '',
  });

  const opType = form.data.operation;
  const needsReason = OPS_WITH_REASON.includes(opType);
  const needsPlan   = OPS_WITH_PLAN.includes(opType);
  const needsEmail  = OPS_WITH_EMAIL.includes(opType);

  function handleSubmit(e) {
    e.preventDefault();
    const tenantIds = form.data.tenant_ids
      .split(/[\n,]+/)
      .map(s => s.trim())
      .filter(Boolean);

    router.post(
      route('platform.admin.tenants.bulk.execute'),
      { ...form.data, tenant_ids: tenantIds },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Bulk operation queued successfully.');
          form.reset();
        },
        onError: () => {
          const first = Object.values(form.errors)[0];
          toast.error(first ?? 'Bulk operation failed.');
        },
      }
    );
  }

  const planOptions = [
    { value: '', label: 'Select a plan...' },
    ...(plans ?? []).map(p => ({ value: String(p.id), label: p.name })),
  ];

  const columns = [
    {
      key: 'id',
      label: 'ID',
      width: '80px',
      render: (row) => <Mono tone="secondary">#{row.id}</Mono>,
    },
    {
      key: 'operation',
      label: 'Operation',
      render: (row) => <Badge intent="neutral">{row.operation}</Badge>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={OP_STATUS_INTENT[row.status] ?? 'neutral'}>
          {row.status}
        </Badge>
      ),
    },
    {
      key: 'affected',
      label: 'Affected',
      render: (row) => (
        <Text tone="secondary">
          {row.affected_count ?? 0} / {row.total_count ?? 0}
        </Text>
      ),
    },
    {
      key: 'created_at',
      label: 'Initiated',
      render: (row) => (
        <Mono tone="secondary">
          {new Date(row.created_at).toLocaleString()}
        </Mono>
      ),
    },
    {
      key: 'initiated_by',
      label: 'By',
      render: (row) => (
        <Text tone="secondary">{row.initiated_by ?? '—'}</Text>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Bulk Operations"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Tenants', href: route('platform.admin.tenants.index') },
        { label: 'Bulk Operations' },
      ]}
      description="Execute batch operations across multiple tenants."
    >
      <VStack gap={6}>
        {/* New bulk operation form */}
        <Card>
          <CardBody>
            <form onSubmit={handleSubmit}>
              <VStack gap={4}>
                <Eyebrow>New Bulk Operation</Eyebrow>

                <Field
                  label="Operation Type"
                  htmlFor="operation"
                  error={form.errors.operation}
                  required
                >
                  <Select
                    id="operation"
                    value={form.data.operation}
                    onChange={e => form.setData('operation', e.target.value)}
                    options={OPERATION_TYPES}
                  />
                </Field>

                <Field
                  label="Tenant IDs"
                  htmlFor="tenant_ids"
                  error={form.errors.tenant_ids}
                  hint="Enter tenant IDs separated by commas or newlines."
                  required
                >
                  <Textarea
                    id="tenant_ids"
                    value={form.data.tenant_ids}
                    onChange={e => form.setData('tenant_ids', e.target.value)}
                    placeholder="tenant-uuid-1&#10;tenant-uuid-2&#10;tenant-uuid-3"
                    rows={4}
                  />
                </Field>

                {needsReason && (
                  <Field
                    label="Reason"
                    htmlFor="reason"
                    error={form.errors.reason}
                    required
                  >
                    <Input
                      id="reason"
                      value={form.data.reason}
                      onChange={e => form.setData('reason', e.target.value)}
                      placeholder="Reason for this operation..."
                      error={form.errors.reason}
                    />
                  </Field>
                )}

                {needsPlan && (
                  <Field
                    label="New Plan"
                    htmlFor="plan_id"
                    error={form.errors.plan_id}
                    required
                  >
                    <Select
                      id="plan_id"
                      value={form.data.plan_id}
                      onChange={e => form.setData('plan_id', e.target.value)}
                      options={planOptions}
                    />
                  </Field>
                )}

                {needsEmail && (
                  <>
                    <Field
                      label="Email Subject"
                      htmlFor="subject"
                      error={form.errors.subject}
                      required
                    >
                      <Input
                        id="subject"
                        value={form.data.subject}
                        onChange={e => form.setData('subject', e.target.value)}
                        placeholder="Important notice regarding your account"
                        error={form.errors.subject}
                      />
                    </Field>

                    <Field
                      label="Email Body"
                      htmlFor="body"
                      error={form.errors.body}
                      required
                    >
                      <Textarea
                        id="body"
                        value={form.data.body}
                        onChange={e => form.setData('body', e.target.value)}
                        placeholder="Enter the email message..."
                        rows={6}
                      />
                    </Field>
                  </>
                )}

                {canExecute && (
                  <HStack gap={2}>
                    <Button
                      type="submit"
                      intent="primary"
                      loading={form.processing}
                      disabled={form.processing || !form.data.operation}
                    >
                      Execute Bulk Operation
                    </Button>
                    <Button
                      type="button"
                      intent="ghost"
                      onClick={() => form.reset()}
                    >
                      Clear
                    </Button>
                  </HStack>
                )}
              </VStack>
            </form>
          </CardBody>
        </Card>

        {/* History table */}
        <VStack gap={3}>
          <Eyebrow>Operation History</Eyebrow>
          <DataTable
            columns={columns}
            rows={operations?.data ?? []}
            empty="No bulk operations have been executed yet."
          />
          {operations?.last_page > 1 && (
            <Pagination
              page={operations.current_page}
              total={operations.last_page}
              onChange={page =>
                router.get(
                  route('platform.admin.tenants.bulk.history'),
                  { page },
                  { preserveState: true, preserveScroll: true, only: ['operations'] }
                )
              }
            />
          )}
        </VStack>
      </VStack>
    </IndexPageLayout>
  );
}

TenantsBulk.layout = page => <App title="Bulk Operations">{page}</App>;
