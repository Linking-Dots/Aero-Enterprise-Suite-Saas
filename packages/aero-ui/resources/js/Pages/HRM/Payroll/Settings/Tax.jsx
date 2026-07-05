import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  IndexPageLayout, VStack, HStack, Text, Mono, Button, Badge,
  DataTable, Modal, Field, Input, Stat,
} from '@aero/ui';
import PayrollRail from '../PayrollRail.jsx';

const money = (v) => Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
const pct = (v) => `${(Number(v ?? 0) * 100).toFixed(2)}%`;

export default function TaxSettings({ brackets, currentYear, defaultCountry, stats }) {
  const [modalOpen,   setModal]       = useState(false);
  const [confirmId,   setConfirmId]   = useState(null);
  const [confirmRate, setConfirmRate] = useState('');

  const { data, setData, post, processing, errors, reset, clearErrors, transform } = useForm({
    country_code: defaultCountry ?? 'US',
    income_from:  '',
    income_to:    '',
    rate:         '',
  });

  function openAdd() {
    clearErrors();
    reset();
    setData('country_code', defaultCountry ?? 'US');
    setModal(true);
  }

  function closeModal() {
    setModal(false);
  }

  function submit(e) {
    e.preventDefault();
    // Backend expects a `brackets[]` array of full bracket rows.
    transform((d) => ({
      brackets: [
        {
          country_code:   d.country_code,
          income_from:    d.income_from,
          income_to:      d.income_to === '' ? null : d.income_to,
          rate:           d.rate,
          effective_year: currentYear,
        },
      ],
    }));
    post(route('hrm.payroll.settings.tax.store'), { onSuccess: closeModal });
  }

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmRate(pct(row.rate));
  }

  function doDelete() {
    router.delete(route('hrm.payroll.settings.tax.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const columns = [
    { key: 'country_code', label: 'Country', render: row => <Mono>{row.country_code}</Mono> },
    { key: 'income_from',  label: 'From',    render: row => money(row.income_from) },
    {
      key: 'income_to', label: 'To',
      render: row => row.income_to != null ? money(row.income_to) : '∞',
    },
    {
      key: 'rate', label: 'Rate',
      render: row => <Badge intent="indigo">{pct(row.rate)}</Badge>,
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
      <IndexPageLayout
        title="Tax Settings"
        breadcrumb={[{ label: 'HRM' }, { label: 'Payroll' }, { label: 'Tax' }]}
        kpis={[
          <Stat key="br"  title="Tax Brackets" value={stats?.brackets_total ?? 0} icon="document" />,
          <Stat key="top" title="Top Rate"     value={pct(stats?.top_rate)}       icon="trending" iconTone="amber" />,
          <Stat key="yr"  title="Effective Year" value={currentYear ?? '—'}       icon="calendar" iconTone="indigo" />,
        ]}
        actions={
          <Button intent="primary" onClick={openAdd}>
            Add Bracket
          </Button>
        }
        table={
          <DataTable
            columns={columns}
            rows={brackets ?? []}
            empty="No tax brackets configured for this year."
          />
        }
      />

      {/* Add bracket modal */}
      <Modal
        open={modalOpen}
        onClose={closeModal}
        title={`Add Tax Bracket · ${currentYear ?? ''}`}
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
              <Field label="Country Code" error={errors.country_code} required>
                <Input
                  value={data.country_code}
                  onChange={e => setData('country_code', e.target.value.toUpperCase())}
                  placeholder="US"
                  maxLength={5}
                />
              </Field>
              <Field
                label="Rate (decimal)"
                error={errors.rate}
                hint="e.g. 0.15 = 15%"
                required
              >
                <Input
                  type="number"
                  step="0.0001"
                  value={String(data.rate)}
                  onChange={e => setData('rate', e.target.value)}
                  placeholder="0.00"
                />
              </Field>
            </HStack>

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
                hint="Blank = no upper limit (∞)"
              >
                <Input
                  type="number"
                  value={String(data.income_to)}
                  onChange={e => setData('income_to', e.target.value)}
                  placeholder="∞"
                />
              </Field>
            </HStack>
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

TaxSettings.layout = page => (
  <App title="Tax Settings" railTitle="Payroll" rail={<PayrollRail />}>{page}</App>
);
