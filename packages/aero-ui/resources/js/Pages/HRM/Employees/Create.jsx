import { useForm, Link } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { FormPageLayout } from '@aero/ui';
import EmployeeForm from './_form.jsx';

export default function CreateEmployee({ departments, designations, managers, statuses, employmentTypes }) {
  const { data, setData, post, processing, errors } = useForm({
    user_id:              null,
    employee_code:        '',
    date_of_joining:      '',
    department_id:        null,
    designation_id:       null,
    manager_id:           null,
    employment_type:      'full_time',
    status:               'active',
    basic_salary:         '',
    work_location:        '',
    shift:                '',
    passport_no:          '',
    visa_no:              '',
    emirates_id:          '',
    national_id:          '',
    bank_account_number:  '',
    notes:                '',
  });

  return (
    <FormPageLayout
      title="New Employee"
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Employees', href: route('hrm.employees.index') },
        { label: 'New' },
      ]}
    >
      <EmployeeForm
        mode="create"
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={e => { e.preventDefault(); post(route('hrm.employees.store')); }}
        departments={departments}
        designations={designations}
        managers={managers}
        statuses={statuses}
        employmentTypes={employmentTypes}
      />
    </FormPageLayout>
  );
}

CreateEmployee.layout = page => <App title="New Employee">{page}</App>;
