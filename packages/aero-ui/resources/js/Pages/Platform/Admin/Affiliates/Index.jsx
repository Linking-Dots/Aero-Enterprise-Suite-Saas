import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Card,
  CardBody,
  Input,
  Select,
  Field,
  Modal,
  Pagination,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const STATUS_OPTIONS = [
  { value: '',          label: 'All Statuses' },
  { value: 'pending',   label: 'Pending' },
  { value: 'approved',  label: 'Approved' },
  { value: 'suspended', label: 'Suspended' },
];

const STATUS_INTENT = {
  pending:   'warning',
  approved:  'success',
  suspended: 'danger',
};

function statusIntent(s) {
  return STATUS_INTENT[s] ?? 'neutral';
}

export default function AffiliatesIndex({ affiliates, filters }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('affiliate-program.affiliate-list.create');
  const canApprove  = useHRMAC('affiliate-program.affiliate-list.approve');
  const canSuspend  = useHRMAC('affiliate-program.affiliate-list.suspend');
  const canDelete   = useHRMAC('affiliate-program.affiliate-list.delete');

  const [form, setForm]             = useState(filters ?? {});
  const [createOpen, setCreateOpen] = useState(false);
  const [approveOpen, setApproveOpen] = useState(false);
  const [suspendOpen, setSuspendOpen] = useState(false);
  const [deleteOpen, setDeleteOpen]   = useState(false);
  const [target, setTarget]           = useState(null);
  const [submitting, setSubmitting]   = useState(false);

  const [newAffiliate, setNewAffiliate] = useState({
    name: '', email: '', company: '', commission_rate: '10',
  });

  function applyFilters() {
    router.get(
      route('platform.admin.affiliates.index'),
      form,
      { preserveState: true, preserveScroll: true, only: ['affiliates', 'filters'] }
    );
  }

  function resetFilters() {
    setForm({});
    router.get(
      route('platform.admin.affiliates.index'),
      {},
      { preserveState: true, preserveScroll: true, only: ['affiliates', 'filters'] }
    );
  }

  function doCreate() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.store'),
      newAffiliate,
      {
        onSuccess: () => {
          setCreateOpen(false);
          setNewAffiliate({ name: '', email: '', company: '', commission_rate: '10' });
          toast.success('Affiliate created.');
        },
        onError: () => toast.error('Failed to create affiliate.'),
        onFinish: () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doApprove() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.approve', target.id),
      {},
      {
        onSuccess: () => { setApproveOpen(false); toast.success('Affiliate approved.'); },
        onError:   () => toast.error('Failed to approve affiliate.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doSuspend() {
    setSubmitting(true);
    router.post(
      route('platform.admin.affiliates.suspend', target.id),
      {},
      {
        onSuccess: () => { setSuspendOpen(false); toast.success('Affiliate suspended.'); },
        onError:   () => toast.error('Failed to suspend affiliate.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.affiliates.destroy', target.id),
      {
        onSuccess: () => { setDeleteOpen(false); toast.success('Affiliate deleted.'); },
        onError:   () => toast.error('Failed to delete affiliate.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Affiliate',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() => router.get(route('platform.admin.affiliates.show', row.id))}
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'code',
      label: 'Code',
      render: (row) => <Mono>{row.code}</Mono>,
    },
    {
      key: 'email',
      label: 'Email',
      render: (row) => <Mono tone="secondary">{row.email}</Mono>,
    },
    {
      key: 'commission_rate',
      label: 'Commission',
      render: (row) => <Text>{row.commission_rate}%</Text>,
    },
    {
      key: 'total_earnings',
      label: 'Earnings',
      render: (row) => (
        <Mono>${Number(row.total_earnings ?? 0).toFixed(2)}</Mono>
      ),
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={statusIntent(row.status)}>{row.status}</Badge>
      ),
    },
    {
      key: 'approved_at',
      label: 'Approved',
      render: (row) => <Mono tone="secondary">{row.approved_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          <Button
            intent="ghost"
            size="sm"
            onClick={() => router.get(route('platform.admin.affiliates.show', row.id))}
          >
            View
          </Button>
          {canApprove && row.status !== 'approved' && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => { setTarget(row); setApproveOpen(true); }}
            >
              Approve
            </Button>
          )}
          {canSuspend && row.status === 'approved' && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => { setTarget(row); setSuspendOpen(true); }}
            >
              Suspend
            </Button>
          )}
          {canDelete && (
            <Button
              intent="ghost"
              size="sm"
              onClick={() => { setTarget(row); setDeleteOpen(true); }}
            >
              Delete
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Affiliates"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Affiliates' },
      ]}
      description="Manage affiliate accounts, commissions, and payout requests."
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('platform.admin.affiliates.payouts.index'))}
          >
            Payouts Queue
          </Button>
          {canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setCreateOpen(true)}>
              New Affiliate
            </Button>
          )}
        </HStack>
      }
    >
      <VStack gap={4}>

        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={3} wrap>
                <Select
                  value={form.status ?? ''}
                  onChange={e => setForm({ ...form, status: e.target.value })}
                  options={STATUS_OPTIONS}
                />
                <Input
                  placeholder="Search name or email"
                  value={form.search ?? ''}
                  onChange={e => setForm({ ...form, search: e.target.value })}
                  leftIcon="magnifyingGlass"
                />
              </HStack>
              <HStack gap={2}>
                <Button intent="soft" onClick={applyFilters}>Apply</Button>
                <Button intent="ghost" onClick={resetFilters}>Reset</Button>
              </HStack>
            </VStack>
          </CardBody>
        </Card>

        <DataTable
          columns={columns}
          rows={affiliates?.data ?? []}
          empty="No affiliates found."
        />

        {(affiliates?.last_page ?? 1) > 1 && (
          <Pagination
            page={affiliates.current_page}
            total={affiliates.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.affiliates.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['affiliates'] }
              )
            }
          />
        )}

      </VStack>

      {/* Create Affiliate Modal */}
      <Modal
        open={createOpen}
        onClose={() => setCreateOpen(false)}
        title="New Affiliate"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setCreateOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doCreate}>
              Create
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Field label="Name" htmlFor="aff_name" required>
            <Input
              id="aff_name"
              value={newAffiliate.name}
              onChange={e => setNewAffiliate({ ...newAffiliate, name: e.target.value })}
              placeholder="Partner Name"
            />
          </Field>
          <Field label="Email" htmlFor="aff_email" required>
            <Input
              id="aff_email"
              type="email"
              leftIcon="mail"
              value={newAffiliate.email}
              onChange={e => setNewAffiliate({ ...newAffiliate, email: e.target.value })}
              placeholder="partner@example.com"
            />
          </Field>
          <Field label="Company" htmlFor="aff_company">
            <Input
              id="aff_company"
              value={newAffiliate.company}
              onChange={e => setNewAffiliate({ ...newAffiliate, company: e.target.value })}
              placeholder="Partner Corp"
            />
          </Field>
          <Field label="Commission Rate (%)" htmlFor="aff_rate" required>
            <Input
              id="aff_rate"
              type="number"
              value={newAffiliate.commission_rate}
              onChange={e => setNewAffiliate({ ...newAffiliate, commission_rate: e.target.value })}
              placeholder="10"
            />
          </Field>
        </VStack>
      </Modal>

      {/* Approve Confirmation */}
      <Modal
        open={approveOpen}
        onClose={() => setApproveOpen(false)}
        title={`Approve "${target?.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setApproveOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doApprove}>
              Approve
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Approving this affiliate will activate their referral code and allow them to earn commissions.
        </Text>
      </Modal>

      {/* Suspend Confirmation */}
      <Modal
        open={suspendOpen}
        onClose={() => setSuspendOpen(false)}
        title={`Suspend "${target?.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setSuspendOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doSuspend}>
              Suspend
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          Suspending this affiliate will deactivate their referral code. Pending commissions will not be affected.
        </Text>
      </Modal>

      {/* Delete Confirmation */}
      <Modal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={`Delete affiliate "${target?.name}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setDeleteOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doDelete}>
              Delete
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This permanently removes the affiliate and all associated referral data. This action cannot be undone.
        </Text>
      </Modal>

    </IndexPageLayout>
  );
}

AffiliatesIndex.layout = page => <App title="Affiliates">{page}</App>;
