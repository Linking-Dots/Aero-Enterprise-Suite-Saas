import { useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Card, CardBody,
  Field, Input, Button, Alert,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function SelfServiceProfile({ employee }) {
  const ec = employee?.emergencyContacts?.[0] ?? {};

  const { data, setData, patch, processing, errors, wasSuccessful } = useForm({
    personal_email: employee?.personal_email ?? '',
    personal_phone: employee?.personal_phone ?? '',
    address:        employee?.address ?? '',
    emergency_name:         ec.name ?? '',
    emergency_relationship: ec.relationship ?? '',
    emergency_phone:        ec.phone ?? '',
  });

  function submit(e) {
    e.preventDefault();
    patch(route('hrm.self-service.profile.update'));
  }

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">My Profile</Text>
        </VStack>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
          {/* Employment info — read-only */}
          <Card>
            <CardBody>
              <VStack gap={4}>
                <Eyebrow>Employment Details</Eyebrow>

                <VStack gap={3}>
                  <HStack gap={3}>
                    <Box grow>
                      <VStack gap={1}>
                        <Text tone="tertiary" size="sm">Employee Code</Text>
                        <Text>{employee?.employee_code ?? '—'}</Text>
                      </VStack>
                    </Box>
                  </HStack>

                  <VStack gap={1}>
                    <Text tone="tertiary" size="sm">Designation</Text>
                    <Text>{employee?.designation?.name ?? '—'}</Text>
                  </VStack>

                  <VStack gap={1}>
                    <Text tone="tertiary" size="sm">Department</Text>
                    <Text>{employee?.department?.name ?? '—'}</Text>
                  </VStack>

                  <VStack gap={1}>
                    <Text tone="tertiary" size="sm">Manager</Text>
                    <Text>{employee?.manager?.user?.name ?? '—'}</Text>
                  </VStack>

                  <VStack gap={1}>
                    <Text tone="tertiary" size="sm">Date of Joining</Text>
                    <Text>{fmt(employee?.date_of_joining)}</Text>
                  </VStack>
                </VStack>
              </VStack>
            </CardBody>
          </Card>

          {/* Contact info — editable */}
          <Card>
            <CardBody>
              <form onSubmit={submit}>
                <VStack gap={4}>
                  <Eyebrow>Contact Information</Eyebrow>

                  {wasSuccessful && (
                    <Alert intent="success" title="Profile updated successfully." />
                  )}
                  {Object.keys(errors).length > 0 && (
                    <Alert intent="danger" title="Please fix the errors below." />
                  )}

                  <Field label="Personal Email" htmlFor="personal_email" error={errors.personal_email}>
                    <Input
                      id="personal_email"
                      type="email"
                      value={data.personal_email}
                      onChange={e => setData('personal_email', e.target.value)}
                      placeholder="personal@example.com"
                    />
                  </Field>

                  <Field label="Personal Phone" htmlFor="personal_phone" error={errors.personal_phone}>
                    <Input
                      id="personal_phone"
                      type="tel"
                      value={data.personal_phone}
                      onChange={e => setData('personal_phone', e.target.value)}
                      placeholder="+1 555 000 0000"
                    />
                  </Field>

                  <Field label="Address" htmlFor="address" error={errors.address}>
                    <Input
                      id="address"
                      value={data.address}
                      onChange={e => setData('address', e.target.value)}
                      placeholder="Street, City, Country"
                    />
                  </Field>

                  <Eyebrow>Emergency Contact</Eyebrow>

                  <Field label="Name" htmlFor="emergency_name" error={errors.emergency_name}>
                    <Input
                      id="emergency_name"
                      value={data.emergency_name}
                      onChange={e => setData('emergency_name', e.target.value)}
                      placeholder="Full name"
                    />
                  </Field>

                  <HStack gap={3}>
                    <Field label="Relationship" htmlFor="emergency_relationship" error={errors.emergency_relationship}>
                      <Input
                        id="emergency_relationship"
                        value={data.emergency_relationship}
                        onChange={e => setData('emergency_relationship', e.target.value)}
                        placeholder="e.g. Spouse"
                      />
                    </Field>

                    <Field label="Phone" htmlFor="emergency_phone" error={errors.emergency_phone}>
                      <Input
                        id="emergency_phone"
                        type="tel"
                        value={data.emergency_phone}
                        onChange={e => setData('emergency_phone', e.target.value)}
                        placeholder="+1 555 000 0000"
                      />
                    </Field>
                  </HStack>

                  <HStack gap={2}>
                    <Button type="submit" intent="primary" loading={processing}>
                      Save Changes
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        </div>
      </VStack>
    </div>
  );
}

SelfServiceProfile.layout = page => <App title="My Profile">{page}</App>;
