import { useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Field, Input, Button,
  Alert, Badge, DataTable, Select, Toggle, Modal, Card,
} from '@aero/ui';

function buildYearOptions(availableYears) {
  return (availableYears ?? []).map(y => ({ value: String(y), label: String(y) }));
}

function formatDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function HolidaysIndex({ holidays, currentYear, availableYears }) {
  const { props } = usePage();
  const flash = props.flash ?? {};
  const canManage = useHRMAC('hrm.settings.holidays.manage');

  const [addOpen, setAddOpen]       = useState(false);
  const [confirmId, setConfirmId]   = useState(null);
  const [confirmName, setConfirmName] = useState('');

  const yearOptions = buildYearOptions(availableYears ?? [currentYear]);

  const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
    name:        '',
    date:        '',
    is_optional: false,
  });

  function changeYear(year) {
    router.get(
      route('hrm.settings.holidays.index'),
      { year },
      { preserveState: false },
    );
  }

  function openAdd() {
    clearErrors();
    reset();
    setAddOpen(true);
  }

  function closeAdd() {
    setAddOpen(false);
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.settings.holidays.store'), { onSuccess: closeAdd });
  }

  function askDelete(row) {
    setConfirmId(row.id);
    setConfirmName(row.name);
  }

  function doDelete() {
    router.delete(route('hrm.settings.holidays.destroy', confirmId), { preserveScroll: true });
    setConfirmId(null);
  }

  const columns = [
    {
      key: 'name',
      label: 'Holiday Name',
      render: row => <Text>{row.name}</Text>,
    },
    {
      key: 'date',
      label: 'Date',
      render: row => <Mono>{formatDate(row.date)}</Mono>,
    },
    {
      key: 'is_optional',
      label: 'Type',
      render: row => (
        <Badge intent={row.is_optional ? 'neutral' : 'success'}>
          {row.is_optional ? 'Optional' : 'Public'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      label: '',
      render: row => canManage ? (
        <Button intent="danger" size="sm" onClick={() => askDelete(row)}>
          Delete
        </Button>
      ) : null,
    },
  ];

  return (
    <>
      <div className="p-6 max-w-4xl">
        <VStack gap={5}>
          {/* Header */}
          <HStack gap={3} align="center">
            <Box grow>
              <div className="text-2xl font-bold mb-1">Public Holidays</div>
              <Text tone="secondary">Manage company holidays for each calendar year.</Text>
            </Box>
            {canManage && (
              <Button intent="primary" onClick={openAdd}>
                Add Holiday
              </Button>
            )}
          </HStack>

          {flash.success && <Alert intent="success" title={flash.success} />}
          {flash.error   && <Alert intent="danger"  title={flash.error}   />}

          {/* Year Selector */}
          <HStack gap={3} align="center">
            <Eyebrow>Year</Eyebrow>
            <Box>
              <Select
                options={yearOptions}
                value={String(currentYear)}
                onChange={e => changeYear(e.target.value)}
              />
            </Box>
          </HStack>

          <DataTable
            columns={columns}
            rows={holidays ?? []}
            empty={`No holidays configured for ${currentYear}.`}
          />
        </VStack>
      </div>

      {/* Add Holiday Modal */}
      <Modal
        open={addOpen}
        onClose={closeAdd}
        title="Add Holiday"
        footer={
          <HStack gap={2}>
            <Button type="button" intent="primary" loading={processing} onClick={submit}>
              Add Holiday
            </Button>
            <Button type="button" intent="ghost" onClick={closeAdd}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <form onSubmit={submit}>
          <VStack gap={4}>
            <Field label="Holiday Name" htmlFor="hol-name" error={errors.name} required>
              <Input
                id="hol-name"
                value={data.name}
                onChange={e => setData('name', e.target.value)}
                placeholder="e.g. National Day"
              />
            </Field>

            <Field label="Date" htmlFor="hol-date" error={errors.date} required>
              <Input
                id="hol-date"
                type="date"
                value={data.date}
                onChange={e => setData('date', e.target.value)}
              />
            </Field>

            <Toggle
              label="Optional holiday (employees may choose to take this day off)"
              checked={data.is_optional}
              onChange={e => setData('is_optional', e.target.checked)}
            />
          </VStack>
        </form>
      </Modal>

      {/* Delete Confirm Modal */}
      <Modal
        open={!!confirmId}
        onClose={() => setConfirmId(null)}
        title="Delete Holiday"
        footer={
          <HStack gap={2}>
            <Button intent="danger" onClick={doDelete}>Delete</Button>
            <Button intent="ghost" onClick={() => setConfirmId(null)}>Cancel</Button>
          </HStack>
        }
      >
        <Text>
          Are you sure you want to delete <strong>{confirmName}</strong>? This action cannot be undone.
        </Text>
      </Modal>
    </>
  );
}

HolidaysIndex.layout = page => <App title="Public Holidays">{page}</App>;
