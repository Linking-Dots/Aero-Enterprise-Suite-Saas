import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack, Field, Input, Select,
  Pagination, Modal, Badge, Toggle, Text,
} from '@aero/ui';

const KIND_OPTIONS = [
  { value: 'earning',   label: 'Earning' },
  { value: 'deduction', label: 'Deduction' },
];

const CALC_TYPE_OPTIONS = [
  { value: 'fixed',             label: 'Fixed Amount' },
  { value: 'percent_of_basic',  label: '% of Basic' },
  { value: 'percent_of_gross',  label: '% of Gross' },
];

const EMPTY_FORM = {
  code:      '',
  name:      '',
  kind:      'earning',
  calc_type: 'fixed',
  value:     '',
  taxable:   false,
  active:    true,
};

export default function ComponentsIndex({ components }) {
  const [modalOpen,  setModal]  = useState(false);
  const [editing,    setEditing] = useState(null);
  const [confirmId,  setConfirmId]   = useState(null);
  const [confirmName, setConfirmName] = useState('');

  const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm(EMPTY_FORM);

  const totalPages  = components.last_page    ?? 1;
  const currentPage = components.current_page ?? 1;

  function openCreate() {
    clearErrors();
    reset();
    setEditing(null);
    setModal(true);
  }

  function openEdit(row) {
    clearErrors();
    setData({
      code:      row.code      ?? '',
      name:      row.name      ?? '',
      kind:      row.kind      ?? 'earning',
      calc_type: row.calc_type ?? 'fixed',
      value:     String(row.value ?? ''),
      taxable:   row.taxable   ?? false,
      active:    row.active    ?? true,
    });
    setEditing(row);
    setModal(true);
  }

  function closeModal() {
    setModal(false);
    setEditing(null);
  }

  function submit(e) {
    e.preventDefault();
    if (editing) {
      put(route('hrm.payroll.components.update', editing.id), { onSuccess: closeModal });
    } else {
      post(route('hrm.payroll.components.store'), { onSuccess: closeModal });
    }
  }

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmName(row.name);
  }

  function doDelete() {
    router.delete(route('hrm.payroll.components.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const columns = [
    { key: 'code', label: 'Code', mono: true },
    { key: 'name', label: 'Name' },
    {
      key: 'kind', label: 'Kind',
      render: row => (
        <Badge intent={row.kind === 'earning' ? 'success' : 'danger'}>
          {row.kind === 'earning' ? 'Earning' : 'Deduction'}
        </Badge>
      ),
    },
    {
      key: 'calc_type', label: 'Calc Type',
      render: row => {
        const opt = CALC_TYPE_OPTIONS.find(o => o.value === row.calc_type);
        return opt ? opt.label : row.calc_type;
      },
    },
    {
      key: 'value', label: 'Value',
      render: row => {
        if (row.calc_type === 'fixed') {
          return Number(row.value).toLocaleString(undefined, { minimumFractionDigits: 2 });
        }
        return `${(Number(row.value) * 100).toFixed(2)}%`;
      },
    },
    {
      key: 'taxable', label: 'Taxable',
      render: row => (
        <Badge intent={row.taxable ? 'warning' : 'neutral'}>
          {row.taxable ? 'Yes' : 'No'}
        </Badge>
      ),
    },
    {
      key: 'active', label: 'Status',
      render: row => (
        <Badge intent={row.active ? 'success' : 'neutral'}>
          {row.active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      key: 'actions', label: '',
      render: row => (
        <HStack gap={1}>
          <Button intent="ghost" size="sm" onClick={() => openEdit(row)}>Edit</Button>
          <Button intent="danger" size="sm" onClick={() => askDelete(row)}>Delete</Button>
        </HStack>
      ),
    },
  ];

  return (
    <>
      <IndexPageLayout
        title="Pay Components"
        breadcrumb={[{ label: 'HRM' }, { label: 'Payroll' }, { label: 'Components' }]}
        actions={
          <Button intent="primary" onClick={openCreate}>
            New Component
          </Button>
        }
        table={
          <DataTable
            columns={columns}
            rows={components.data ?? []}
            empty="No pay components configured."
          />
        }
        pagination={
          totalPages > 1 && (
            <Pagination
              page={currentPage}
              total={totalPages}
              onChange={page =>
                router.get(route('hrm.payroll.components.index'), { page }, { preserveState: true })
              }
            />
          )
        }
      />

      {/* Create / Edit modal */}
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={editing ? 'Edit Pay Component' : 'New Pay Component'}
        size="lg"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              {editing ? 'Save Changes' : 'Create'}
            </Button>
            <Button type="button" intent="ghost" onClick={closeModal}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <HStack gap={3}>
              <Field label="Code" error={errors.code} required>
                <Input
                  value={data.code}
                  onChange={e => setData('code', e.target.value)}
                  placeholder="e.g. BASIC"
                />
              </Field>
              <Field label="Name" error={errors.name} required>
                <Input
                  value={data.name}
                  onChange={e => setData('name', e.target.value)}
                  placeholder="e.g. Basic Salary"
                />
              </Field>
            </HStack>

            <HStack gap={3}>
              <Field label="Kind" error={errors.kind} required>
                <Select
                  options={KIND_OPTIONS}
                  value={data.kind}
                  onChange={e => setData('kind', e.target.value)}
                />
              </Field>
              <Field label="Calculation Type" error={errors.calc_type} required>
                <Select
                  options={CALC_TYPE_OPTIONS}
                  value={data.calc_type}
                  onChange={e => setData('calc_type', e.target.value)}
                />
              </Field>
            </HStack>

            <Field
              label="Value"
              error={errors.value}
              hint={
                data.calc_type === 'fixed'
                  ? 'Fixed monetary amount'
                  : 'Decimal fraction (e.g. 0.05 = 5%)'
              }
              required
            >
              <Input
                type="number"
                value={String(data.value)}
                onChange={e => setData('value', e.target.value)}
                placeholder={data.calc_type === 'fixed' ? '0.00' : '0.0000'}
              />
            </Field>

            <HStack gap={4} wrap>
              <Toggle
                label="Taxable"
                checked={data.taxable}
                onChange={e => setData('taxable', e.target.checked)}
              />
              <Toggle
                label="Active"
                checked={data.active}
                onChange={e => setData('active', e.target.checked)}
              />
            </HStack>
          </VStack>
        </form>
      </Modal>

      {/* Delete confirm modal */}
      <Modal
        open={!!confirmId}
        onClose={() => setConfirmId(null)}
        title="Delete Pay Component"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setConfirmId(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete component &ldquo;{confirmName}&rdquo;? This cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

ComponentsIndex.layout = page => <App title="Pay Components">{page}</App>;
