/**
 * Print Templates — CRUD for HTML print templates (invoice, payslip, report, employee).
 * Create/Edit modal with HTML textarea, paper size, orientation, header/footer, is_default toggle.
 */
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button, Badge,
  Modal,
  Field, Input, Select, Toggle, Mono,
  HStack, VStack, Text,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const ENTITY_TYPE_OPTIONS = [
  { value: 'invoice',   label: 'Invoice' },
  { value: 'payslip',   label: 'Payslip' },
  { value: 'report',    label: 'Report' },
  { value: 'employee',  label: 'Employee' },
];

const ORIENTATION_OPTIONS = [
  { value: 'portrait',  label: 'Portrait' },
  { value: 'landscape', label: 'Landscape' },
];

const ENTITY_INTENT = {
  invoice:  'info',
  payslip:  'success',
  report:   'neutral',
  employee: 'amber',
};

export default function PrintTemplatesIndex({ templates = [], paper_sizes = [] }) {
  const toast     = useToast();
  const canCreate = useHRMAC('core.print_templates.create');
  const canEdit   = useHRMAC('core.print_templates.edit');
  const canDelete = useHRMAC('core.print_templates.delete');

  const [modalOpen, setModalOpen] = useState(false);
  const [editing,   setEditing]   = useState(null);

  const paperSizeOptions = paper_sizes?.length
    ? paper_sizes.map(s => (typeof s === 'string' ? { value: s, label: s } : s))
    : [
        { value: 'A4',     label: 'A4' },
        { value: 'Letter', label: 'Letter' },
        { value: 'Legal',  label: 'Legal' },
        { value: 'A3',     label: 'A3' },
      ];

  const blankForm = {
    name:        '',
    entity_type: 'invoice',
    template:    '',
    paper_size:  'A4',
    orientation: 'portrait',
    header_html: '',
    footer_html: '',
    is_default:  false,
  };

  const { data, setData, post, put, processing, errors, reset } = useForm(blankForm);

  const openCreate = () => {
    reset();
    setData(blankForm);
    setEditing(null);
    setModalOpen(true);
  };

  const openEdit = (t) => {
    setData({
      name:        t.name        ?? '',
      entity_type: t.entity_type ?? 'invoice',
      template:    t.template    ?? '',
      paper_size:  t.paper_size  ?? 'A4',
      orientation: t.orientation ?? 'portrait',
      header_html: t.header_html ?? '',
      footer_html: t.footer_html ?? '',
      is_default:  t.is_default  ?? false,
    });
    setEditing(t);
    setModalOpen(true);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const opts = {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(editing ? 'Template updated.' : 'Template created.');
        setModalOpen(false);
        reset();
      },
      onError: () => toast.error('Failed to save template.'),
    };
    if (editing) {
      put(route('core.print-templates.update', editing.id), opts);
    } else {
      post(route('core.print-templates.store'), opts);
    }
  };

  const handleDelete = (id) => {
    if (!confirm('Delete this print template? This cannot be undone.')) return;
    router.delete(route('core.print-templates.destroy', id), {
      preserveScroll: true,
      onSuccess: () => toast.success('Template deleted.'),
      onError:   () => toast.error('Failed to delete template.'),
    });
  };

  const handlePreview = (t) => {
    window.open(`/print-templates/${t.id}/preview`, '_blank', 'noopener');
  };

  const rows = Array.isArray(templates) ? templates : (templates?.data ?? []);

  const columns = [
    {
      key: 'name', label: 'Name', width: '22%',
      render: r => <Text size="sm">{r.name}</Text>,
    },
    {
      key: 'entity_type', label: 'Type', width: '14%',
      render: r => (
        <Badge intent={ENTITY_INTENT[r.entity_type] ?? 'neutral'}>
          {r.entity_type ?? '—'}
        </Badge>
      ),
    },
    {
      key: 'paper_size', label: 'Paper Size', width: '12%',
      render: r => <Mono tone="secondary">{r.paper_size ?? '—'}</Mono>,
    },
    {
      key: 'orientation', label: 'Orientation', width: '12%',
      render: r => <Text size="sm">{r.orientation ?? '—'}</Text>,
    },
    {
      key: 'is_default', label: 'Default', width: '10%',
      render: r => (
        <Badge intent={r.is_default ? 'success' : 'neutral'}>
          {r.is_default ? 'Default' : 'No'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '', width: '30%', align: 'right',
      render: r => (
        <HStack gap={2} justify="end">
          <Button intent="soft" size="sm" onClick={() => handlePreview(r)}>Preview</Button>
          {canEdit && (
            <Button intent="soft" size="sm" onClick={() => openEdit(r)}>Edit</Button>
          )}
          {canDelete && (
            <Button intent="danger" size="sm" onClick={() => handleDelete(r.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <style>{`
        .pt-textarea {
          width: 100%;
          padding: 8px 12px;
          border-radius: var(--aeos-r-md);
          border: 1px solid var(--aeos-divider);
          background: var(--aeos-bg-surface);
          color: var(--aeos-text-primary);
          font-family: var(--aeos-font-mono);
          font-size: 0.8125rem;
          line-height: 1.6;
          resize: vertical;
        }
        .pt-textarea:focus {
          outline: 2px solid var(--aeos-primary);
          outline-offset: -1px;
        }
      `}</style>

      <IndexPageLayout
        title="Print Templates"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Print Templates' },
        ]}
        description="Manage HTML templates used for invoices, payslips, reports, and employee documents."
        actions={
          canCreate && (
            <Button intent="primary" onClick={openCreate}>New Template</Button>
          )
        }
        table={
          <DataTable
            columns={columns}
            rows={rows}
            empty="No print templates yet. Create your first one to get started."
          />
        }
      />

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? 'Edit Print Template' : 'New Print Template'}
        size="lg"
      >
        <form onSubmit={handleSubmit}>
          <VStack gap={4}>

            <Field label="Template Name" htmlFor="pt_name" error={errors.name} required>
              <Input
                id="pt_name"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                error={Boolean(errors.name)}
                placeholder="e.g. Standard Invoice"
              />
            </Field>

            <HStack gap={3}>
              <Field label="Entity Type" htmlFor="pt_entity_type" error={errors.entity_type} required>
                <Select
                  options={ENTITY_TYPE_OPTIONS}
                  value={data.entity_type}
                  onChange={e => setData('entity_type', e.target.value)}
                />
              </Field>

              <Field label="Paper Size" htmlFor="pt_paper_size" error={errors.paper_size} required>
                <Select
                  options={paperSizeOptions}
                  value={data.paper_size}
                  onChange={e => setData('paper_size', e.target.value)}
                />
              </Field>

              <Field label="Orientation" htmlFor="pt_orientation" error={errors.orientation} required>
                <Select
                  options={ORIENTATION_OPTIONS}
                  value={data.orientation}
                  onChange={e => setData('orientation', e.target.value)}
                />
              </Field>
            </HStack>

            <Field label="Header HTML" htmlFor="pt_header_html" error={errors.header_html} hint="HTML rendered at the top of each page">
              <textarea
                id="pt_header_html"
                className="pt-textarea"
                value={data.header_html}
                onChange={e => setData('header_html', e.target.value)}
                rows={4}
                placeholder="<header>…</header>"
              />
            </Field>

            <Field label="Template HTML" htmlFor="pt_template" error={errors.template} hint="Main body HTML — use {{variables}} for dynamic data" required>
              <textarea
                id="pt_template"
                className="pt-textarea"
                value={data.template}
                onChange={e => setData('template', e.target.value)}
                rows={12}
                placeholder="<body>…</body>"
              />
            </Field>

            <Field label="Footer HTML" htmlFor="pt_footer_html" error={errors.footer_html} hint="HTML rendered at the bottom of each page">
              <textarea
                id="pt_footer_html"
                className="pt-textarea"
                value={data.footer_html}
                onChange={e => setData('footer_html', e.target.value)}
                rows={4}
                placeholder="<footer>…</footer>"
              />
            </Field>

            <Toggle
              label="Set as default template for this entity type"
              checked={data.is_default}
              onChange={v => setData('is_default', v)}
            />

            <HStack gap={3} justify="end">
              <Button type="button" intent="ghost" onClick={() => setModalOpen(false)}>Cancel</Button>
              <Button type="submit" intent="primary" loading={processing}>
                {editing ? 'Save Changes' : 'Create Template'}
              </Button>
            </HStack>

          </VStack>
        </form>
      </Modal>
    </>
  );
}

PrintTemplatesIndex.layout = page => (
  <App title="Print Templates">{page}</App>
);
