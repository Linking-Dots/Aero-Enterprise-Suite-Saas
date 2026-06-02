import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  FormPageLayout, VStack, HStack, Field, Input, Select,
  Textarea, Button, Text, Eyebrow, DataTable,
} from '@aero/ui';

export default function ClaimsCreate({ categories, currencies }) {
  const canCreate = useHRMAC('hrm.expenses.expense-claims.create');

  const categoryOptions = [
    { value: '', label: 'Select category' },
    ...(categories ?? []).map(c => ({ value: String(c.id), label: c.name })),
  ];

  const currencyOptions = [
    { value: '', label: 'Select currency' },
    ...(currencies ?? []).map(cur => ({
      value: typeof cur === 'string' ? cur : cur.code,
      label: typeof cur === 'string' ? cur : `${cur.code} — ${cur.name}`,
    })),
  ];

  const { data, setData, post, processing, errors } = useForm({
    title:      '',
    claim_date: '',
    currency:   '',
    notes:      '',
    items:      [],
    receipts:   [],
  });

  function addItem() {
    setData('items', [
      ...data.items,
      { category_id: '', expense_date: '', amount: '', description: '' },
    ]);
  }

  function updateItem(index, field, value) {
    const updated = data.items.map((item, i) =>
      i === index ? { ...item, [field]: value } : item,
    );
    setData('items', updated);
  }

  function removeItem(index) {
    setData('items', data.items.filter((_, i) => i !== index));
  }

  function handleReceipts(e) {
    setData('receipts', Array.from(e.target.files));
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.expenses.claims.store'), { forceFormData: true });
  }

  const itemColumns = [
    {
      key: 'category_id', label: 'Category',
      render: (row, index) => (
        <Field error={errors[`items.${index}.category_id`]}>
          <Select
            options={categoryOptions}
            value={row.category_id}
            onChange={e => updateItem(index, 'category_id', e.target.value)}
          />
        </Field>
      ),
    },
    {
      key: 'expense_date', label: 'Expense Date',
      render: (row, index) => (
        <Field error={errors[`items.${index}.expense_date`]}>
          <Input
            type="date"
            value={row.expense_date}
            onChange={e => updateItem(index, 'expense_date', e.target.value)}
          />
        </Field>
      ),
    },
    {
      key: 'amount', label: 'Amount',
      render: (row, index) => (
        <Field error={errors[`items.${index}.amount`]}>
          <Input
            type="number"
            value={row.amount}
            onChange={e => updateItem(index, 'amount', e.target.value)}
            placeholder="0.00"
          />
        </Field>
      ),
    },
    {
      key: 'description', label: 'Description',
      render: (row, index) => (
        <Field error={errors[`items.${index}.description`]}>
          <Input
            value={row.description}
            onChange={e => updateItem(index, 'description', e.target.value)}
            placeholder="Brief description"
          />
        </Field>
      ),
    },
    {
      key: 'actions', label: '',
      render: (row, index) => (
        <Button
          type="button"
          intent="danger"
          size="sm"
          onClick={() => removeItem(index)}
        >
          Remove
        </Button>
      ),
    },
  ];

  return (
    <FormPageLayout
      title="New Expense Claim"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Expenses' },
        { label: 'Claims', href: route('hrm.expenses.claims.index') },
        { label: 'New Claim' },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            type="button"
            intent="ghost"
            onClick={() => router.get(route('hrm.expenses.claims.index'))}
          >
            Cancel
          </Button>
          {canCreate && (
            <Button
              type="submit"
              intent="primary"
              loading={processing}
              onClick={submit}
            >
              Submit Claim
            </Button>
          )}
        </HStack>
      }
    >
      <form onSubmit={submit}>
        <VStack gap={6}>

          <VStack gap={4}>
            <Eyebrow>Claim Details</Eyebrow>

            <Field label="Title" error={errors.title} required>
              <Input
                value={data.title}
                onChange={e => setData('title', e.target.value)}
                placeholder="e.g. Q1 Travel Expenses"
              />
            </Field>

            <HStack gap={3}>
              <Field label="Claim Date" error={errors.claim_date} required>
                <Input
                  type="date"
                  value={data.claim_date}
                  onChange={e => setData('claim_date', e.target.value)}
                />
              </Field>

              <Field label="Currency" error={errors.currency} required>
                <Select
                  options={currencyOptions}
                  value={data.currency}
                  onChange={e => setData('currency', e.target.value)}
                />
              </Field>
            </HStack>

            <Field label="Notes" error={errors.notes} hint="Optional">
              <Textarea
                value={data.notes}
                onChange={e => setData('notes', e.target.value)}
                placeholder="Additional notes..."
                rows={3}
              />
            </Field>
          </VStack>

          <VStack gap={4}>
            <HStack gap={3} align="center">
              <Eyebrow>Expense Items</Eyebrow>
              {errors.items && (
                <Text tone="secondary" size="sm">{errors.items}</Text>
              )}
            </HStack>

            {data.items.length === 0 ? (
              <Text tone="secondary">No items added yet. Click &ldquo;Add Item&rdquo; to begin.</Text>
            ) : (
              <DataTable
                columns={itemColumns}
                rows={data.items.map((item, i) => ({ ...item, _index: i }))}
                empty=""
              />
            )}

            <Button type="button" intent="soft" size="sm" onClick={addItem}>
              Add Item
            </Button>
          </VStack>

          <VStack gap={4}>
            <Eyebrow>Receipts</Eyebrow>

            <Field label="Upload Receipts" error={errors.receipts} hint="Multiple files allowed">
              <Input
                type="file"
                multiple
                onChange={handleReceipts}
              />
            </Field>
          </VStack>

        </VStack>
      </form>
    </FormPageLayout>
  );
}

ClaimsCreate.layout = page => <App title="New Expense Claim">{page}</App>;
