import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  DetailPageLayout, Card, CardBody, VStack, HStack,
  Text, Badge, Button, Avatar, Divider, Tabs,
} from '@aero/ui';

const STATUS_COLORS = {
  active:      'success',
  probation:   'warning',
  on_leave:    'neutral',
  terminated:  'danger',
  resigned:    'neutral',
};

const PROFILE_TABS = [
  { value: 'overview',   label: 'Overview' },
  { value: 'documents',  label: 'Documents' },
  { value: 'bank',       label: 'Bank Details' },
  { value: 'emergency',  label: 'Emergency Contacts' },
  { value: 'history',    label: 'Employment History' },
];

function InfoRow({ label, value, masked = false }) {
  return (
    <VStack gap={0} className="emp-info-row">
      <Text size="xs" tone="tertiary" className="emp-info-label">{label}</Text>
      <Text>{masked ? '••••••••' : (value ?? '—')}</Text>
    </VStack>
  );
}

export default function ShowEmployee({ employee, permissions }) {
  const [activeTab, setActiveTab] = useState('overview');

  const user        = employee.user ?? {};
  const department  = employee.department ?? {};
  const designation = employee.designation ?? {};
  const manager     = employee.manager?.user ?? null;

  function handleDelete() {
    if (confirm('Are you sure you want to delete this employee?')) {
      router.delete(route('hrm.employees.destroy', employee.id));
    }
  }

  const headerCard = (
    <Card>
      <CardBody>
        <HStack gap={4} align="center">
          <Avatar size={56} name={user.name} />
          <VStack gap={1}>
            <Text size="xl">{user.name ?? '—'}</Text>
            <Text tone="secondary">{designation.title ?? '—'} · {department.name ?? '—'}</Text>
            <HStack gap={2}>
              <Badge intent="neutral">{employee.employee_code}</Badge>
              <Badge intent={STATUS_COLORS[employee.status] ?? 'neutral'}>{employee.status}</Badge>
              <Badge intent="neutral">{employee.employment_type}</Badge>
            </HStack>
          </VStack>
        </HStack>
      </CardBody>
    </Card>
  );

  return (
    <DetailPageLayout
      title={user.name ?? employee.employee_code}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Employees', href: route('hrm.employees.index') },
        { label: user.name ?? employee.employee_code },
      ]}
      actions={
        permissions.canEdit && (
          <HStack gap={2}>
            <Button as={Link} href={route('hrm.employees.edit', employee.id)} intent="primary">
              Edit
            </Button>
            <Button onClick={handleDelete} intent="danger">
              Delete
            </Button>
          </HStack>
        )
      }
      tabs={
        <Tabs tabs={PROFILE_TABS} value={activeTab} onChange={setActiveTab} />
      }
    >
      {headerCard}

      {activeTab === 'overview' && (
        <Card>
          <CardBody>
            <VStack gap={4}>
              <HStack gap={6} wrap>
                <InfoRow label="Email"           value={user.email} />
                <InfoRow label="Date of Joining" value={employee.date_of_joining} />
                <InfoRow label="Manager"         value={manager?.name} />
                <InfoRow label="Work Location"   value={employee.work_location} />
                <InfoRow label="Shift"           value={employee.shift} />
                <InfoRow
                  label="Basic Salary"
                  value={employee.basic_salary
                    ? `$${Number(employee.basic_salary).toLocaleString()}`
                    : null}
                />
              </HStack>

              <Divider />

              <Text>Identity Documents</Text>
              <HStack gap={6} wrap>
                <InfoRow
                  label="Passport No"
                  value={employee.passport_no}
                  masked={!employee.passport_no && permissions.canViewBank !== undefined}
                />
                <InfoRow
                  label="Emirates ID"
                  value={employee.emirates_id}
                  masked={!employee.emirates_id && permissions.canViewBank !== undefined}
                />
                <InfoRow
                  label="National ID"
                  value={employee.national_id}
                  masked={!employee.national_id && permissions.canViewBank !== undefined}
                />
              </HStack>
            </VStack>
          </CardBody>
        </Card>
      )}

      {activeTab === 'documents' && (
        <Card>
          <CardBody>
            {permissions.canViewDocs
              ? <Text tone="secondary">Document management will be available in a future update.</Text>
              : <Text tone="warning">You do not have permission to view employee documents.</Text>
            }
          </CardBody>
        </Card>
      )}

      {activeTab === 'bank' && (
        <Card>
          <CardBody>
            {permissions.canViewBank ? (
              <VStack gap={3}>
                <InfoRow label="Bank Account Number" value={employee.bank_account_number} />
                {permissions.canEditBank && (
                  <Button
                    as={Link}
                    href={route('hrm.employees.edit', employee.id)}
                    intent="soft"
                    size="sm"
                  >
                    Update Bank Details
                  </Button>
                )}
              </VStack>
            ) : (
              <Text tone="secondary">You do not have permission to view bank details.</Text>
            )}
          </CardBody>
        </Card>
      )}

      {activeTab === 'emergency' && (
        <Card>
          <CardBody>
            <Text tone="secondary">
              Emergency contacts (linked to EmergencyContact model — coming in a future update).
            </Text>
          </CardBody>
        </Card>
      )}

      {activeTab === 'history' && (
        <Card>
          <CardBody>
            <Text tone="secondary">
              Timeline of department/designation/manager changes — coming in a future update.
            </Text>
          </CardBody>
        </Card>
      )}
    </DetailPageLayout>
  );
}

ShowEmployee.layout = page => <App title="Employee Profile">{page}</App>;
