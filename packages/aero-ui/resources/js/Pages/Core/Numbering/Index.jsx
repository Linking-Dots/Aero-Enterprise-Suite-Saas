import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack, VStack,
  Text,
  Mono,
  Eyebrow,
  Input,
  Field,
  Modal,
  Card, CardHeader, CardBody,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

export default function NumberingIndex({ sequences, formats }) {
  const toast        = useToast();
  const canEditSeq   = useHRMAC('core.numbering.sequences.edit');
  const canResetSeq  = useHRMAC('core.numbering.sequences.reset');
  const canCreateFmt = useHRMAC('core.numbering.formats.create');
  const canDeleteFmt = useHRMAC('core.numbering.formats.delete');

  // ── Sequence Reset Modal ─────────────────────────────────────────
  const [resetTarget, setResetTarget] = useState(null);
  const resetForm = useForm({ next_value: '' });

  const openReset = seq => {
    setResetTarget(seq);
    resetForm.setData('next_value', String(seq.next_value ?? 1));
  };

  const closeReset = () => {
    setResetTarget(null);
    resetForm.reset();
  };

  const submitReset = e => {
    e.preventDefault();
    resetForm.post(route('core.numbering.sequences.reset', resetTarget.id), {
      preserveState: true,
      onSuccess: () => {
        toast.success(`Sequence reset for ${resetTarget.entity_type}.`);
        closeReset();
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to reset sequence.');
      },
    });
  };

  // ── Sequence Edit Modal ──────────────────────────────────────────
  const [editTarget, setEditTarget] = useState(null);
  const editForm = useForm({ prefix: '', next_value: '', padding: '' });

  const openEdit = seq => {
    setEditTarget(seq);
    editForm.setData({
      prefix:     seq.prefix     ?? '',
      next_value: String(seq.next_value ?? 1),
      padding:    String(seq.padding    ?? 4),
    });
  };

  const closeEdit = () => {
    setEditTarget(null);
    editForm.reset();
  };

  const submitEdit = e => {
    e.preventDefault();
    editForm.post(route('core.numbering.sequences.update', editTarget.id), {
      preserveState: true,
      onSuccess: () => {
        toast.success('Sequence updated.');
        closeEdit();
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to update sequence.');
      },
    });
  };

  // ── Format Create Modal ──────────────────────────────────────────
  const [showCreateFormat, setShowCreateFormat] = useState(false);
  const formatForm = useForm({ name: '', pattern: '', example: '' });

  const submitFormat = e => {
    e.preventDefault();
    formatForm.post(route('core.numbering.formats.store'), {
      onSuccess: () => {
        toast.success('Number format created.');
        formatForm.reset();
        setShowCreateFormat(false);
      },
      onError: errs => {
        const first = Object.values(errs)[0];
        toast.error(first || 'Failed to create format.');
      },
    });
  };

  const destroyFormat = id => {
    if (!confirm('Delete this number format?')) return;
    router.delete(route('core.numbering.formats.destroy', id), {
      onSuccess: () => toast.success('Format deleted.'),
      onError:   () => toast.error('Failed to delete format.'),
    });
  };

  // ── Columns ──────────────────────────────────────────────────────
  const sequenceColumns = [
    {
      key: 'entity_type', label: 'Entity Type', width: '28%',
      render: row => <Text size="sm">{row.entity_type}</Text>,
    },
    {
      key: 'prefix', label: 'Prefix', width: '14%',
      render: row => row.prefix
        ? <Mono size="sm">{row.prefix}</Mono>
        : <Text tone="secondary" size="sm">—</Text>,
    },
    {
      key: 'next_value', label: 'Next Value', width: '14%',
      render: row => <Mono size="sm">{row.next_value}</Mono>,
    },
    {
      key: 'padding', label: 'Padding', width: '12%',
      render: row => <Mono size="sm">{row.padding}</Mono>,
    },
    {
      key: 'actions', label: '', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canEditSeq && (
            <Button intent="soft" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          )}
          {canResetSeq && (
            <Button intent="ghost" size="sm" onClick={() => openReset(row)}>Reset</Button>
          )}
        </HStack>
      ),
    },
  ];

  const formatColumns = [
    {
      key: 'name', label: 'Name', width: '25%',
      render: row => <Text size="sm">{row.name}</Text>,
    },
    {
      key: 'pattern', label: 'Pattern', width: '30%',
      render: row => <Mono size="sm">{row.pattern}</Mono>,
    },
    {
      key: 'example', label: 'Example', width: '25%',
      render: row => <Mono size="sm">{row.example}</Mono>,
    },
    {
      key: 'actions', label: '', align: 'right',
      render: row => (
        <HStack gap={2} justify="end">
          {canDeleteFmt && (
            <Button intent="danger" size="sm" onClick={() => destroyFormat(row.id)}>Delete</Button>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Numbering"
        breadcrumb={[
          { label: 'Dashboard', href: route('core.dashboard') },
          { label: 'Numbering' },
        ]}
        description="Configure document sequences and number format patterns."
        table={
          <VStack gap={6}>
            {/* Sequences section */}
            <VStack gap={3}>
              <Eyebrow>Sequences</Eyebrow>
              <DataTable
                columns={sequenceColumns}
                rows={sequences ?? []}
                empty="No sequences configured."
              />
            </VStack>

            {/* Number Formats section */}
            <VStack gap={3}>
              <HStack gap={3} align="center">
                <Eyebrow>Number Formats</Eyebrow>
                {canCreateFmt && (
                  <Button intent="primary" size="sm" onClick={() => setShowCreateFormat(true)}>
                    Create Format
                  </Button>
                )}
              </HStack>
              <DataTable
                columns={formatColumns}
                rows={formats ?? []}
                empty="No number formats defined."
              />
            </VStack>
          </VStack>
        }
      />

      {/* Sequence Edit Modal */}
      <Modal
        open={!!editTarget}
        title={editTarget ? `Edit Sequence — ${editTarget.entity_type}` : ''}
        size="md"
        onClose={closeEdit}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={closeEdit}>Cancel</Button>
            <Button intent="primary" onClick={submitEdit} loading={editForm.processing}>Save</Button>
          </HStack>
        }
      >
        <form onSubmit={submitEdit}>
          <VStack gap={3}>
            <Field
              label="Prefix"
              htmlFor="seq-prefix"
              error={editForm.errors.prefix}
              hint="Prepended to each generated number, e.g. INV-"
            >
              <Input
                id="seq-prefix"
                value={editForm.data.prefix}
                onChange={e => editForm.setData('prefix', e.target.value)}
                placeholder="INV-"
              />
            </Field>

            <Field
              label="Next Value"
              htmlFor="seq-next-value"
              error={editForm.errors.next_value}
              required
            >
              <Input
                id="seq-next-value"
                type="number"
                value={editForm.data.next_value}
                onChange={e => editForm.setData('next_value', e.target.value)}
                placeholder="1"
                required
              />
            </Field>

            <Field
              label="Padding"
              htmlFor="seq-padding"
              error={editForm.errors.padding}
              hint="Minimum digit width, zero-padded. e.g. 4 produces 0001"
            >
              <Input
                id="seq-padding"
                type="number"
                value={editForm.data.padding}
                onChange={e => editForm.setData('padding', e.target.value)}
                placeholder="4"
              />
            </Field>
          </VStack>
        </form>
      </Modal>

      {/* Sequence Reset Modal */}
      <Modal
        open={!!resetTarget}
        title={resetTarget ? `Reset Sequence — ${resetTarget.entity_type}` : ''}
        size="sm"
        onClose={closeReset}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={closeReset}>Cancel</Button>
            <Button intent="danger" onClick={submitReset} loading={resetForm.processing}>Reset</Button>
          </HStack>
        }
      >
        <form onSubmit={submitReset}>
          <VStack gap={3}>
            <Text tone="secondary">
              Set a new starting value for this sequence. This cannot be undone.
            </Text>

            <Field
              label="New Next Value"
              htmlFor="reset-next-value"
              error={resetForm.errors.next_value}
              required
            >
              <Input
                id="reset-next-value"
                type="number"
                value={resetForm.data.next_value}
                onChange={e => resetForm.setData('next_value', e.target.value)}
                placeholder="1"
                required
              />
            </Field>
          </VStack>
        </form>
      </Modal>

      {/* Create Number Format Modal */}
      <Modal
        open={showCreateFormat}
        title="Create Number Format"
        size="md"
        onClose={() => setShowCreateFormat(false)}
        footer={
          <HStack gap={2}>
            <Button intent="soft" onClick={() => setShowCreateFormat(false)}>Cancel</Button>
            <Button intent="primary" onClick={submitFormat} loading={formatForm.processing}>Create</Button>
          </HStack>
        }
      >
        <form onSubmit={submitFormat}>
          <VStack gap={3}>
            <Field
              label="Name"
              htmlFor="fmt-name"
              error={formatForm.errors.name}
              required
            >
              <Input
                id="fmt-name"
                value={formatForm.data.name}
                onChange={e => formatForm.setData('name', e.target.value)}
                placeholder="Invoice Number"
                required
              />
            </Field>

            <Field
              label="Pattern"
              htmlFor="fmt-pattern"
              error={formatForm.errors.pattern}
              hint="Use placeholders like {PREFIX}, {YEAR}, {SEQ}."
              required
            >
              <Input
                id="fmt-pattern"
                value={formatForm.data.pattern}
                onChange={e => formatForm.setData('pattern', e.target.value)}
                placeholder="{PREFIX}{YEAR}-{SEQ}"
                required
              />
            </Field>

            <Field
              label="Example Output"
              htmlFor="fmt-example"
              error={formatForm.errors.example}
              hint="A rendered example of what this pattern produces."
            >
              <Input
                id="fmt-example"
                value={formatForm.data.example}
                onChange={e => formatForm.setData('example', e.target.value)}
                placeholder="INV2024-0001"
              />
            </Field>
          </VStack>
        </form>
      </Modal>
    </>
  );
}

NumberingIndex.layout = page => (
  <App title="Numbering">{page}</App>
);
