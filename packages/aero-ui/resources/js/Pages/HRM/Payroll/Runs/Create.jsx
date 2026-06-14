import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Field, Input, Button, Text, Card, Eyebrow,
} from '@aero/ui';

export default function RunsCreate({ employees }) {
  const [search, setSearch] = useState('');

  const { data, setData, post, processing, errors } = useForm({
    label:        '',
    period_start: '',
    period_end:   '',
    employee_ids: [],
  });

  const filtered = (employees ?? []).filter(emp =>
    emp.name.toLowerCase().includes(search.toLowerCase()),
  );

  function toggleEmployee(id) {
    setData('employee_ids', data.employee_ids.includes(id)
      ? data.employee_ids.filter(e => e !== id)
      : [...data.employee_ids, id],
    );
  }

  function toggleAll() {
    const visibleIds = filtered.map(e => e.id);
    const allSelected = visibleIds.every(id => data.employee_ids.includes(id));
    if (allSelected) {
      setData('employee_ids', data.employee_ids.filter(id => !visibleIds.includes(id)));
    } else {
      const merged = [...new Set([...data.employee_ids, ...visibleIds])];
      setData('employee_ids', merged);
    }
  }

  function submit(e) {
    e.preventDefault();
    post(route('hrm.payroll.runs.store'));
  }

  const allVisibleSelected =
    filtered.length > 0 && filtered.every(e => data.employee_ids.includes(e.id));

  return (
    <>
      <form onSubmit={submit}>
        <VStack gap={6}>
          <div className="payroll-page-header">
            <HStack gap={2} align="center">
              <Box grow>
                <Text size="lg">New Payroll Run</Text>
              </Box>
              <HStack gap={2}>
                <Button type="submit" intent="primary" loading={processing}>
                  Create Run
                </Button>
                <Button
                  type="button"
                  intent="ghost"
                  onClick={() => router.get(route('hrm.payroll.runs.index'))}
                >
                  Cancel
                </Button>
              </HStack>
            </HStack>
          </div>

          <Field label="Label" error={errors.label} required>
            <Input
              value={data.label}
              onChange={e => setData('label', e.target.value)}
              placeholder="e.g. May 2026 Payroll"
            />
          </Field>

          <HStack gap={4}>
            <Field label="Period Start" error={errors.period_start} required>
              <Input
                type="date"
                value={data.period_start}
                onChange={e => setData('period_start', e.target.value)}
              />
            </Field>
            <Field label="Period End" error={errors.period_end} required>
              <Input
                type="date"
                value={data.period_end}
                onChange={e => setData('period_end', e.target.value)}
              />
            </Field>
          </HStack>

          <Field
            label="Employees"
            error={errors.employee_ids}
            hint={`${data.employee_ids.length} of ${(employees ?? []).length} selected`}
            required
          >
            <VStack gap={2}>
              <div className="mb-2">
                <Input
                  type="search"
                  value={search}
                  onChange={e => setSearch(e.target.value)}
                  placeholder="Search employees..."
                  leftIcon="magnifyingGlass"
                />
              </div>
              <div className="payroll-employee-list">
                <div className="payroll-select-all">
                  <input
                    type="checkbox"
                    id="pr-select-all"
                    checked={allVisibleSelected}
                    onChange={toggleAll}
                  />
                  <label htmlFor="pr-select-all">
                    <Text size="sm" tone="secondary">
                      {allVisibleSelected ? 'Deselect all visible' : 'Select all visible'}
                    </Text>
                  </label>
                </div>
                {filtered.map(emp => (
                  <label
                    key={emp.id}
                    className={`payroll-employee-item${data.employee_ids.includes(emp.id) ? ' selected' : ''}`}
                  >
                    <input
                      type="checkbox"
                      checked={data.employee_ids.includes(emp.id)}
                      onChange={() => toggleEmployee(emp.id)}
                    />
                    <Text size="sm">{emp.name}</Text>
                  </label>
                ))}
                {filtered.length === 0 && (
                  <Box grow>
                    <Text tone="secondary" size="sm">No employees match your search.</Text>
                  </Box>
                )}
              </div>
            </VStack>
          </Field>
        </VStack>
      </form>
    </>
  );
}

RunsCreate.layout = page => <App title="New Payroll Run">{page}</App>;
