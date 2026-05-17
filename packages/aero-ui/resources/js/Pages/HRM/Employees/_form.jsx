import { useState } from 'react';
import { VStack, HStack, Card, CardBody, Field, Input, Select, Textarea, Button, Text, Tabs } from '@aero/ui';

const TABS = [
  { value: 'employment', label: 'Employment Details' },
  { value: 'identity',   label: 'Identity (PII)' },
  { value: 'notes',      label: 'Notes' },
];

export default function EmployeeForm({
  data, setData, errors, processing, onSubmit,
  departments, designations, managers, statuses, employmentTypes,
  mode = 'create',
}) {
  const [activeTab, setActiveTab] = useState('employment');

  return (
    <form onSubmit={onSubmit}>
      <VStack gap={4}>
        <Tabs tabs={TABS} value={activeTab} onChange={setActiveTab} />

        {activeTab === 'employment' && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <HStack gap={4}>
                  <Field label="Employee Code *" error={errors.employee_code} className="aeos-field-grow">
                    <Input
                      value={data.employee_code}
                      onChange={e => setData('employee_code', e.target.value)}
                      placeholder="EMP-001"
                      error={!!errors.employee_code}
                    />
                  </Field>
                  <Field label="Date of Joining *" error={errors.date_of_joining} className="aeos-field-grow">
                    <Input
                      type="date"
                      value={data.date_of_joining ?? ''}
                      onChange={e => setData('date_of_joining', e.target.value)}
                      error={!!errors.date_of_joining}
                    />
                  </Field>
                </HStack>

                <HStack gap={4}>
                  <Field label="Department" error={errors.department_id} className="aeos-field-grow">
                    <Select
                      value={data.department_id ?? ''}
                      onChange={e => setData('department_id', e.target.value || null)}
                    >
                      <option value="">— None —</option>
                      {departments.map(d => (
                        <option key={d.id} value={d.id}>{d.name}</option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="Designation" error={errors.designation_id} className="aeos-field-grow">
                    <Select
                      value={data.designation_id ?? ''}
                      onChange={e => setData('designation_id', e.target.value || null)}
                    >
                      <option value="">— None —</option>
                      {designations.map(d => (
                        <option key={d.id} value={d.id}>{d.title}</option>
                      ))}
                    </Select>
                  </Field>
                </HStack>

                <HStack gap={4}>
                  <Field label="Manager" error={errors.manager_id} className="aeos-field-grow">
                    <Select
                      value={data.manager_id ?? ''}
                      onChange={e => setData('manager_id', e.target.value || null)}
                    >
                      <option value="">— None —</option>
                      {managers.map(m => (
                        <option key={m.id} value={m.id}>{m.label}</option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="Employment Type *" error={errors.employment_type} className="aeos-field-grow">
                    <Select
                      value={data.employment_type ?? ''}
                      onChange={e => setData('employment_type', e.target.value)}
                      error={!!errors.employment_type}
                    >
                      <option value="">— Select —</option>
                      {employmentTypes.map(t => (
                        <option key={t} value={t}>{t.replace('_', ' ')}</option>
                      ))}
                    </Select>
                  </Field>
                  <Field label="Status *" error={errors.status} className="aeos-field-grow">
                    <Select
                      value={data.status ?? ''}
                      onChange={e => setData('status', e.target.value)}
                      error={!!errors.status}
                    >
                      <option value="">— Select —</option>
                      {statuses.map(s => (
                        <option key={s} value={s}>{s.replace('_', ' ')}</option>
                      ))}
                    </Select>
                  </Field>
                </HStack>

                <HStack gap={4}>
                  <Field label="Basic Salary *" error={errors.basic_salary} className="aeos-field-grow">
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.basic_salary ?? ''}
                      onChange={e => setData('basic_salary', e.target.value)}
                      error={!!errors.basic_salary}
                    />
                  </Field>
                  <Field label="Work Location" error={errors.work_location} className="aeos-field-grow">
                    <Input
                      value={data.work_location ?? ''}
                      onChange={e => setData('work_location', e.target.value)}
                    />
                  </Field>
                  <Field label="Shift" error={errors.shift} className="aeos-field-grow">
                    <Input
                      value={data.shift ?? ''}
                      onChange={e => setData('shift', e.target.value)}
                    />
                  </Field>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

        {activeTab === 'identity' && (
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Text tone="secondary">
                  These fields are encrypted at rest. Only users with bank-details or identity permissions can view them.
                </Text>
                <HStack gap={4} wrap>
                  <Field label="Passport No" error={errors.passport_no} className="aeos-field-grow">
                    <Input
                      value={data.passport_no ?? ''}
                      onChange={e => setData('passport_no', e.target.value)}
                    />
                  </Field>
                  <Field label="Visa No" error={errors.visa_no} className="aeos-field-grow">
                    <Input
                      value={data.visa_no ?? ''}
                      onChange={e => setData('visa_no', e.target.value)}
                    />
                  </Field>
                  <Field label="Emirates ID" error={errors.emirates_id} className="aeos-field-grow">
                    <Input
                      value={data.emirates_id ?? ''}
                      onChange={e => setData('emirates_id', e.target.value)}
                    />
                  </Field>
                  <Field label="National ID" error={errors.national_id} className="aeos-field-grow">
                    <Input
                      value={data.national_id ?? ''}
                      onChange={e => setData('national_id', e.target.value)}
                    />
                  </Field>
                  <Field label="Bank Account Number" error={errors.bank_account_number} className="aeos-field-grow">
                    <Input
                      value={data.bank_account_number ?? ''}
                      onChange={e => setData('bank_account_number', e.target.value)}
                    />
                  </Field>
                </HStack>
              </VStack>
            </CardBody>
          </Card>
        )}

        {activeTab === 'notes' && (
          <Card>
            <CardBody>
              <Field label="Internal Notes" error={errors.notes}>
                <Textarea
                  value={data.notes ?? ''}
                  onChange={e => setData('notes', e.target.value)}
                  rows={5}
                />
              </Field>
            </CardBody>
          </Card>
        )}

        <HStack gap={2}>
          <Button type="submit" intent="primary" loading={processing}>
            {mode === 'create' ? 'Create Employee' : 'Save Changes'}
          </Button>
        </HStack>
      </VStack>
    </form>
  );
}
