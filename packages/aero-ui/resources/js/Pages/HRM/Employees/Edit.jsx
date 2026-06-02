import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { FormPageLayout } from '@aero/ui';
import EmployeeForm from './_form.jsx';

export default function EditEmployee({ employee, departments, designations, managers, statuses, employmentTypes }) {
  const { data, setData, put, processing, errors } = useForm({
    employee_code:        employee.employee_code ?? '',
    date_of_joining:      employee.date_of_joining ?? '',
    department_id:        employee.department_id ?? null,
    designation_id:       employee.designation_id ?? null,
    manager_id:           employee.manager_id ?? null,
    employment_type:      employee.employment_type ?? 'full_time',
    status:               employee.status ?? 'active',
    basic_salary:         employee.basic_salary ?? '',
    work_location:        employee.work_location ?? '',
    shift:                employee.shift ?? '',
    passport_no:          employee.passport_no ?? '',
    visa_no:              employee.visa_no ?? '',
    emirates_id:          employee.emirates_id ?? '',
    national_id:          employee.national_id ?? '',
    bank_account_number:  employee.bank_account_number ?? '',
    notes:                employee.notes ?? '',
  });

  return (
    <FormPageLayout
      title={`Edit ${employee.employee_code}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Employees', href: route('hrm.employees.index') },
        { label: employee.employee_code, href: route('hrm.employees.show', employee.id) },
        { label: 'Edit' },
      ]}
    >
      <EmployeeForm
        mode="edit"
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={e => { e.preventDefault(); put(route('hrm.employees.update', employee.id)); }}
        departments={departments}
        designations={designations}
        managers={managers}
        statuses={statuses}
        employmentTypes={employmentTypes}
      />
    </FormPageLayout>
  );
}

EditEmployee.layout = page => <App title="Edit Employee">{page}</App>;
