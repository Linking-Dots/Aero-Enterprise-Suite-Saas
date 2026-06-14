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
  { value: '',             label: 'All Statuses' },
  { value: 'subscribed',   label: 'Subscribed' },
  { value: 'unsubscribed', label: 'Unsubscribed' },
  { value: 'bounced',      label: 'Bounced' },
];

const STATUS_INTENT = {
  subscribed:   'success',
  unsubscribed: 'neutral',
  bounced:      'danger',
};

function statusIntent(s) {
  return STATUS_INTENT[s] ?? 'neutral';
}

export default function NewsletterIndex({ subscribers, filters }) {
  const toast       = useToast();
  const canCreate   = useHRMAC('newsletter-management.subscriber-list.create');
  const canDelete   = useHRMAC('newsletter-management.subscriber-list.delete');
  const canImport   = useHRMAC('newsletter-management.subscriber-list.import');
  const canExport   = useHRMAC('newsletter-management.subscriber-list.export');

  const [form, setForm]             = useState(filters ?? {});
  const [addOpen, setAddOpen]       = useState(false);
  const [importOpen, setImportOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [target, setTarget]         = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const [newSub, setNewSub] = useState({ email: '', name: '', source: '' });
  const [csvFile, setCsvFile] = useState(null);
  const [importResult, setImportResult] = useState(null);

  function applyFilters() {
    router.get(
      route('platform.admin.newsletter.index'),
      form,
      { preserveState: true, preserveScroll: true, only: ['subscribers', 'filters'] }
    );
  }

  function resetFilters() {
    setForm({});
    router.get(
      route('platform.admin.newsletter.index'),
      {},
      { preserveState: true, preserveScroll: true, only: ['subscribers', 'filters'] }
    );
  }

  function doAdd() {
    setSubmitting(true);
    router.post(
      route('platform.admin.newsletter.store'),
      newSub,
      {
        onSuccess: () => {
          setAddOpen(false);
          setNewSub({ email: '', name: '', source: '' });
          toast.success('Subscriber added.');
        },
        onError: () => toast.error('Failed to add subscriber.'),
        onFinish: () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doImport() {
    if (!csvFile) return;
    setSubmitting(true);
    const formData = new FormData();
    formData.append('csv', csvFile);
    router.post(
      route('platform.admin.newsletter.import'),
      formData,
      {
        onSuccess: (page) => {
          setImportResult(page.props.importResult ?? null);
          setCsvFile(null);
          toast.success('CSV imported successfully.');
        },
        onError: () => toast.error('Failed to import CSV.'),
        onFinish: () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  function doExport() {
    window.location.href = route('platform.admin.newsletter.export') +
      (form.status ? '?status=' + form.status : '');
  }

  function openDelete(sub) {
    setTarget(sub);
    setDeleteOpen(true);
  }

  function doDelete() {
    setSubmitting(true);
    router.delete(
      route('platform.admin.newsletter.destroy', target.id),
      {
        onSuccess: () => { setDeleteOpen(false); toast.success('Subscriber removed.'); },
        onError:   () => toast.error('Failed to remove subscriber.'),
        onFinish:  () => setSubmitting(false),
        preserveState: true,
      }
    );
  }

  const columns = [
    {
      key: 'email',
      label: 'Email',
      render: (row) => <Mono>{row.email}</Mono>,
    },
    {
      key: 'name',
      label: 'Name',
      render: (row) => <Text tone="secondary">{row.name ?? '—'}</Text>,
    },
    {
      key: 'source',
      label: 'Source',
      render: (row) => <Text tone="secondary">{row.source ?? '—'}</Text>,
    },
    {
      key: 'status',
      label: 'Status',
      render: (row) => (
        <Badge intent={statusIntent(row.status)}>{row.status}</Badge>
      ),
    },
    {
      key: 'subscribed_at',
      label: 'Subscribed',
      render: (row) => <Mono tone="secondary">{row.subscribed_at ?? '—'}</Mono>,
    },
    {
      key: 'unsubscribed_at',
      label: 'Unsubscribed',
      render: (row) => <Mono tone="secondary">{row.unsubscribed_at ?? '—'}</Mono>,
    },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        canDelete && (
          <Button intent="ghost" size="sm" onClick={() => openDelete(row)}>
            Remove
          </Button>
        )
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Newsletter Subscribers"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Newsletter' },
      ]}
      description="Manage marketing newsletter subscribers, import, export, and settings."
      actions={
        <HStack gap={2}>
          {canExport && (
            <Button intent="ghost" leftIcon="arrowDown" onClick={doExport}>
              Export CSV
            </Button>
          )}
          {canImport && (
            <Button intent="soft" leftIcon="arrowUp" onClick={() => setImportOpen(true)}>
              Import CSV
            </Button>
          )}
          {canCreate && (
            <Button intent="primary" leftIcon="plus" onClick={() => setAddOpen(true)}>
              Add Subscriber
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
                  placeholder="Search email or name"
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
          rows={subscribers?.data ?? []}
          empty="No subscribers found."
        />

        {(subscribers?.last_page ?? 1) > 1 && (
          <Pagination
            page={subscribers.current_page}
            total={subscribers.last_page}
            onChange={page =>
              router.get(
                route('platform.admin.newsletter.index'),
                { ...form, page },
                { preserveState: true, preserveScroll: true, only: ['subscribers'] }
              )
            }
          />
        )}

      </VStack>

      {/* Add Subscriber Modal */}
      <Modal
        open={addOpen}
        onClose={() => setAddOpen(false)}
        title="Add Subscriber"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setAddOpen(false)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doAdd}>
              Add
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Field label="Email" htmlFor="sub_email" required>
            <Input
              id="sub_email"
              type="email"
              leftIcon="mail"
              value={newSub.email}
              onChange={e => setNewSub({ ...newSub, email: e.target.value })}
              placeholder="subscriber@example.com"
            />
          </Field>
          <Field label="Name" htmlFor="sub_name">
            <Input
              id="sub_name"
              value={newSub.name}
              onChange={e => setNewSub({ ...newSub, name: e.target.value })}
              placeholder="Jane Doe"
            />
          </Field>
          <Field label="Source" htmlFor="sub_source">
            <Input
              id="sub_source"
              value={newSub.source}
              onChange={e => setNewSub({ ...newSub, source: e.target.value })}
              placeholder="manual, landing-page, etc."
            />
          </Field>
        </VStack>
      </Modal>

      {/* Import CSV Modal */}
      <Modal
        open={importOpen}
        onClose={() => { setImportOpen(false); setImportResult(null); }}
        title="Import Subscribers from CSV"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setImportOpen(false); setImportResult(null); }}>
              Close
            </Button>
            {!importResult && (
              <Button intent="primary" loading={submitting} disabled={submitting || !csvFile} onClick={doImport}>
                Import
              </Button>
            )}
          </HStack>
        }
      >
        <VStack gap={3}>
          {importResult ? (
            <VStack gap={2}>
              <Text>Import complete.</Text>
              <HStack gap={4}>
                <VStack gap={0}>
                  <Text tone="secondary">Imported</Text>
                  <Text>{importResult.imported ?? 0}</Text>
                </VStack>
                <VStack gap={0}>
                  <Text tone="secondary">Skipped (duplicates)</Text>
                  <Text>{importResult.skipped ?? 0}</Text>
                </VStack>
              </HStack>
            </VStack>
          ) : (
            <VStack gap={2}>
              <Text tone="secondary">
                Upload a CSV file with columns: email, name (optional), source (optional).
              </Text>
              <input
                type="file"
                accept=".csv"
                className="aeos-text-sm"
                onChange={e => setCsvFile(e.target.files?.[0] ?? null)}
              />
            </VStack>
          )}
        </VStack>
      </Modal>

      {/* Remove Confirmation */}
      <Modal
        open={deleteOpen}
        onClose={() => setDeleteOpen(false)}
        title={`Remove subscriber "${target?.email}"?`}
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setDeleteOpen(false)}>Cancel</Button>
            <Button intent="danger" loading={submitting} disabled={submitting} onClick={doDelete}>
              Remove
            </Button>
          </HStack>
        }
      >
        <Text tone="secondary">
          This will permanently remove the subscriber record. This action cannot be undone.
        </Text>
      </Modal>

    </IndexPageLayout>
  );
}

NewsletterIndex.layout = page => <App title="Newsletter Subscribers">{page}</App>;
