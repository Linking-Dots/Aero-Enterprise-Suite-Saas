import { router, useForm } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import {
  DetailPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge,
  Button, Card, CardBody, Field, Select, Progress, Alert, DataTable,
} from '@aero/ui';

export default function CareerPathsShow({ careerPath, employeeProgress, employees }) {
  const canAssign = useHRMAC('hrm.career-pathing.employee-progressions.assign');

  const employeeOptions = [
    { value: '', label: 'Select employee' },
    ...(employees ?? []).map(e => ({
      value: String(e.id),
      label: `${e.first_name} ${e.last_name}`,
    })),
  ];

  const { data, setData, post, processing, errors, reset } = useForm({
    employee_id: '',
  });

  function assign(e) {
    e.preventDefault();
    post(route('hrm.career-paths.assign', careerPath.slug), {
      preserveScroll: true,
      onSuccess: () => reset(),
    });
  }

  const milestoneColumns = [
    { key: 'order', label: '#', render: (_, index) => index + 1 },
    { key: 'name',  label: 'Milestone Name' },
  ];

  const totalMilestones = (careerPath.milestones ?? []).length;

  return (
    <DetailPageLayout
      title={careerPath.name}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Succession' },
        { label: 'Career Paths', href: route('hrm.career-paths.index') },
        { label: careerPath.name },
      ]}
      actions={
        <Button intent="soft" onClick={() => router.get(route('hrm.career-paths.index'))}>
          Back
        </Button>
      }
    >
      <VStack gap={5}>

        {/* Path Details */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <HStack gap={2} align="center">
                <Text size="lg">{careerPath.name}</Text>
                <Badge intent={careerPath.is_active ? 'success' : 'neutral'}>
                  {careerPath.is_active ? 'Active' : 'Inactive'}
                </Badge>
              </HStack>
              {careerPath.description && (
                <Text tone="secondary">{careerPath.description}</Text>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Milestones */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Milestones ({totalMilestones})</Eyebrow>
              {totalMilestones === 0 ? (
                <Text tone="secondary">No milestones defined for this path.</Text>
              ) : (
                <DataTable
                  columns={milestoneColumns}
                  rows={careerPath.milestones ?? []}
                  empty="No milestones defined."
                />
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Employee Progress */}
        <Card>
          <CardBody>
            <VStack gap={4}>
              <Eyebrow>Employee Progress</Eyebrow>

              {(employeeProgress ?? []).length === 0 ? (
                <Text tone="secondary">No employees assigned to this career path yet.</Text>
              ) : (
                <VStack gap={4}>
                  {employeeProgress.map(ep => {
                    const done  = (ep.milestones ?? []).filter(m => m.completed).length;
                    const total = (ep.milestones ?? []).length;
                    const pct   = total > 0 ? Math.round((done / total) * 100) : 0;

                    return (
                      <VStack key={ep.employee_id} gap={2}>
                        <HStack gap={3} align="center">
                          <Box grow>
                            <Text>{ep.name}</Text>
                          </Box>
                          <Text tone="secondary" size="sm">{done}/{total} milestones</Text>
                          <Badge intent={pct === 100 ? 'success' : pct > 0 ? 'primary' : 'neutral'}>
                            {pct}%
                          </Badge>
                        </HStack>
                        <Progress value={pct} intent={pct === 100 ? 'success' : 'cyan'} />
                      </VStack>
                    );
                  })}
                </VStack>
              )}
            </VStack>
          </CardBody>
        </Card>

        {/* Assign Employee */}
        {canAssign && (
          <Card>
            <CardBody>
              <form onSubmit={assign}>
                <VStack gap={4}>
                  <Eyebrow>Assign Employee</Eyebrow>
                  {errors.employee_id && (
                    <Alert intent="danger" title={errors.employee_id} />
                  )}
                  <HStack gap={3} align="center">
                    <Field label="Employee" error={errors.employee_id} required>
                      <Select
                        options={employeeOptions}
                        value={data.employee_id}
                        onChange={e => setData('employee_id', e.target.value)}
                      />
                    </Field>
                    <Button type="submit" intent="primary" loading={processing}>
                      Assign
                    </Button>
                  </HStack>
                </VStack>
              </form>
            </CardBody>
          </Card>
        )}

      </VStack>
    </DetailPageLayout>
  );
}

CareerPathsShow.layout = page => <App title="Career Path Detail">{page}</App>;
