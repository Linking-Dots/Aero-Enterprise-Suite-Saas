import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Button, Badge, Card,
  DataTable, Modal, Field, Input,
} from '@aero/ui';

const EMPTY_BRACKET = {
  income_from: '',
  income_to:   '',
  rate:        '',
};

export default function TaxSettings({ brackets, current_year }) {
  const [modalOpen,  setModal]  = useState(false);
  const [confirmId,  setConfirmId]   = useState(null);
  const [confirmRate, setConfirmRate] = useState('');

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm(EMPTY_BRACKET);

  function openAdd() {
    clearErrors();
    reset();
    setModal(true);
  }

  function closeModal() {
    setModal(false);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.payroll.settings.tax.store'), { onSuccess: closeModal });
  }

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmRate(`${(Number(row.rate) * 100).toFixed(2)}%`);
  }

  function doDelete() {
    router.delete(route('hrm.payroll.settings.tax.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const columns = [
    {
      key: 'income_from', label: 'From',
      render: row => Number(row.income_from).toLocaleString(undefined, { minimumFractionDigits: 2 }),
    },
    {
      key: 'income_to', label: 'To',
      render: row =>
        row.income_to != null
          ? Number(row.income_to).toLocaleString(undefined, { minimumFractionDigits: 2 })
          : '∞',
    },
    {
      key: 'rate', label: 'Rate',
      render: row => `${(Number(row.rate) * 100).toFixed(2)}%`,
    },
    {
      key: 'effective_year', label: 'Year',
      render: row => <Mono>{row.effective_year}</Mono>,
    },
    {
      key: 'actions', label: '',
      render: row => (
        <Button intent="danger" size="sm" onClick={() => askDelete(row)}>
          Delete
        </Button>
      ),
    },
  ];

  return (
    <>
      <VStack gap={6}>
        {/* Page header */}
        <HStack gap={2} align="center">
          <Box grow>
            <VStack gap={1}>
              <Eyebrow>Payroll Settings</Eyebrow>
              <HStack gap={2} align="center">
                <Text size="lg">Tax Brackets</Text>
                <Badge intent="neutral">{current_year}</Badge>
              </HStack>
            </VStack>
          </Box>
          <Button intent="primary" onClick={openAdd}>
            Add Bracket
          </Button>
        </HStack>

        <DataTable
          columns={columns}
          rows={brackets ?? []}
          empty="No tax brackets configured for this year."
        />
      </VStack>

      {/* Add bracket modal */}
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title="Add Tax Bracket"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              Save Bracket
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
              <Field label="Income From" error={errors.income_from} required>
                <Input
                  type="number"
                  value={String(data.income_from)}
                  onChange={e => setData('income_from', e.target.value)}
                  placeholder="0.00"
                />
              </Field>
              <Field
                label="Income To"
                error={errors.income_to}
                hint="Leave blank for no upper limit (∞)"
              >
                <Input
                  type="number"
                  value={String(data.income_to)}
                  onChange={e => setData('income_to', e.target.value)}
                  placeholder="∞"
                />
              </Field>
            </HStack>

            <Field
              label="Rate (decimal)"
              error={errors.rate}
              hint="Enter as decimal fraction, e.g. 0.15 = 15%"
              required
            >
              <Input
                type="number"
                value={String(data.rate)}
                onChange={e => setData('rate', e.target.value)}
                placeholder="0.00"
              />
            </Field>
          </VStack>
        </form>
      </Modal>

      {/* Delete confirm modal */}
      <Modal
        open={!!confirmId}
        onClose={() => setConfirmId(null)}
        title="Delete Tax Bracket"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setConfirmId(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete the {confirmRate} tax bracket? This cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

TaxSettings.layout = page => <App title="Tax Settings">{page}</App>;
