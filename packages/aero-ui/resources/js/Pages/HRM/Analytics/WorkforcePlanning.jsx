import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Field, Input, Text, Badge,
} from '@aero/ui';

export default function WorkforcePlanning({ fiscalYear, rows }) {
  const canEdit = useHRMAC('hrm.analytics.workforce-planning.update');

  const [year, setYear]       = useState(fiscalYear ?? new Date().getFullYear());
  const [plans, setPlans]     = useState(rows ?? []);
  const [editing, setEditing] = useState({}); // { rowId_field: true }
  const [saving, setSaving]   = useState(false);
  const [saved, setSaved]     = useState(false);

  function navigateYear(newYear) {
    setYear(newYear);
    router.get(
      route('hrm.analytics.workforce-planning.index'),
      { fiscal_year: newYear },
      { preserveState: true, preserveScroll: true, replace: true },
    );
  }

  function editKey(rowId, field) {
    return `${rowId}_${field}`;
  }

  function startEdit(rowId, field) {
    if (!canEdit) return;
    setEditing(prev => ({ ...prev, [editKey(rowId, field)]: true }));
  }

  function stopEdit(rowId, field) {
    setEditing(prev => {
      const next = { ...prev };
      delete next[editKey(rowId, field)];
      return next;
    });
  }

  function updateRow(rowId, field, value) {
    setPlans(prev =>
      prev.map(r => (r.id === rowId ? { ...r, [field]: value } : r)),
    );
  }

  function savePlan() {
    setSaving(true);
    setSaved(false);
    router.put(
      route('hrm.analytics.workforce-planning.update'),
      { fiscal_year: year, plans },
      {
        preserveState:  true,
        preserveScroll: true,
        onSuccess: () => setSaved(true),
        onFinish:  () => setSaving(false),
      },
    );
  }

  const EDITABLE_FIELDS = ['target_headcount', 'target_hires', 'target_attrition'];

  function EditableCell({ row, field }) {
    const key = editKey(row.id, field);
    const val = row[field] ?? '';

    if (editing[key]) {
      return (
        <Input
          type="number"
          value={val}
          onChange={e => updateRow(row.id, field, e.target.value === '' ? '' : Number(e.target.value))}
          onBlur={() => stopEdit(row.id, field)}
          className="aeos-input-sm"
        />
      );
    }

    return (
      <Button
        intent="ghost"
        size="sm"
        type="button"
        onClick={() => startEdit(row.id, field)}
      >
        {val !== '' ? String(val) : '—'}
      </Button>
    );
  }

  const columns = [
    {
      key: 'name',
      label: 'Department',
      render: row => <Text>{row.name}</Text>,
    },
    {
      key: 'target_headcount',
      label: 'Target Headcount',
      render: row => <EditableCell row={row} field="target_headcount" />,
    },
    {
      key: 'target_hires',
      label: 'Target Hires',
      render: row => <EditableCell row={row} field="target_hires" />,
    },
    {
      key: 'actual_headcount',
      label: 'Actual Headcount',
      render: row => <Text>{row.actual_headcount ?? '—'}</Text>,
    },
    {
      key: 'gap',
      label: 'Gap',
      render: row => {
        const target = Number(row.target_headcount ?? 0);
        const actual = Number(row.actual_headcount ?? 0);
        const gap    = target - actual;
        return (
          <Badge intent={gap < 0 ? 'danger' : gap === 0 ? 'neutral' : 'success'}>
            {gap > 0 ? `+${gap}` : String(gap)}
          </Badge>
        );
      },
    },
    {
      key: 'notes',
      label: 'Notes',
      render: row => {
        const key = editKey(row.id, 'notes');
        const val = row.notes ?? '';

        if (editing[key]) {
          return (
            <Input
              value={val}
              onChange={e => updateRow(row.id, 'notes', e.target.value)}
              onBlur={() => stopEdit(row.id, 'notes')}
              placeholder="Add note..."
            />
          );
        }

        return (
          <Button
            intent="ghost"
            size="sm"
            type="button"
            onClick={() => startEdit(row.id, 'notes')}
          >
            {val || 'Add note...'}
          </Button>
        );
      },
    },
  ];

  return (
    <IndexPageLayout
      title={`Workforce Planning — FY${year}`}
      breadcrumb={[{ label: 'HRM' }, { label: 'Analytics' }, { label: 'Workforce Planning' }]}
      actions={
        <HStack gap={3} align="center">
          <Field label="Fiscal Year" htmlFor="fiscal-year">
            <Input
              id="fiscal-year"
              type="number"
              value={year}
              onChange={e => {
                const v = parseInt(e.target.value, 10);
                if (!isNaN(v)) navigateYear(v);
              }}
              className="aeos-input-sm"
            />
          </Field>
          {canEdit && (
            <Button
              intent="primary"
              onClick={savePlan}
              loading={saving}
              disabled={saving}
            >
              {saved ? 'Saved' : 'Save Plan'}
            </Button>
          )}
        </HStack>
      }
      table={
        <DataTable
          columns={columns}
          rows={plans}
          empty="No workforce planning data for this fiscal year."
        />
      }
    />
  );
}

WorkforcePlanning.layout = page => <App title="Workforce Planning">{page}</App>;
