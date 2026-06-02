import { router } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  DetailPageLayout, VStack, HStack, Box, Text, Eyebrow, Badge, Button,
  Card, CardBody, DataTable, Tabs, Alert,
} from '@aero/ui';

const DELIVERY_MODE_LABELS = {
  in_person:  'In Person',
  virtual:    'Virtual',
  self_paced: 'Self-Paced',
};

function sessionStatusIntent(status) {
  switch (status) {
    case 'scheduled':  return 'info';
    case 'completed':  return 'success';
    case 'cancelled':  return 'danger';
    default:           return 'neutral';
  }
}

function enrollmentStatusIntent(status) {
  switch (status) {
    case 'enrolled':   return 'info';
    case 'attended':   return 'success';
    case 'no_show':    return 'danger';
    case 'cancelled':  return 'neutral';
    default:           return 'neutral';
  }
}

function fmtDatetime(val) {
  if (!val) return '—';
  return new Date(val).toLocaleString();
}

function fmtDate(val) {
  if (!val) return '—';
  return new Date(val).toLocaleDateString();
}

export default function CoursesShow({ course }) {
  const canEdit = useHRMAC('hrm.training.courses.update');

  const [tab, setTab] = useState('overview');

  const tabDefs = [
    { value: 'overview',    label: 'Overview' },
    { value: 'sessions',    label: 'Sessions',    count: course.sessions?.length ?? 0 },
    { value: 'enrollments', label: 'Enrollments' },
    { value: 'analytics',   label: 'Analytics' },
  ];

  /* Flatten enrollments across all sessions */
  const allEnrollments = (course.sessions ?? []).flatMap(s =>
    (s.enrollments ?? []).map(en => ({ ...en, session: s }))
  );

  const sessionColumns = [
    {
      key: 'starts_at', label: 'Starts At',
      render: row => <Text>{fmtDatetime(row.starts_at)}</Text>,
    },
    {
      key: 'location', label: 'Location',
      render: row => <Text>{row.location || '—'}</Text>,
    },
    {
      key: 'capacity', label: 'Capacity',
      render: row => <Text>{row.capacity ?? '—'}</Text>,
    },
    {
      key: 'enrollments_count', label: 'Enrolled',
      render: row => <Text>{row.enrollments_count ?? 0}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={sessionStatusIntent(row.status)}>
          {row.status ?? 'scheduled'}
        </Badge>
      ),
    },
  ];

  const enrollmentColumns = [
    {
      key: 'employee', label: 'Employee',
      render: row => <Text>{row.employee?.user?.name ?? '—'}</Text>,
    },
    {
      key: 'session_date', label: 'Session Date',
      render: row => <Text>{fmtDate(row.session?.starts_at)}</Text>,
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={enrollmentStatusIntent(row.status)}>
          {row.status ?? '—'}
        </Badge>
      ),
    },
  ];

  /* Average rating helper */
  const feedbacks = (course.sessions ?? []).flatMap(s => s.feedbacks ?? []);
  const avgRating = feedbacks.length
    ? (feedbacks.reduce((sum, f) => sum + (f.overall_rating ?? 0), 0) / feedbacks.length).toFixed(1)
    : null;

  return (
    <DetailPageLayout
      title={course.title}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Training' },
        { label: 'Courses', href: route('hrm.training.courses.index') },
        { label: course.title },
      ]}
      actions={
        <HStack gap={2}>
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.training.courses.index'))}
          >
            Back
          </Button>
        </HStack>
      }
    >
      <VStack gap={4}>
        {/* Meta strip */}
        <HStack gap={3} wrap>
          {course.category && (
            <Badge intent="neutral">{course.category.name}</Badge>
          )}
          <Badge intent={course.delivery_mode === 'in_person' ? 'success' : course.delivery_mode === 'virtual' ? 'info' : 'warning'}>
            {DELIVERY_MODE_LABELS[course.delivery_mode] ?? course.delivery_mode}
          </Badge>
          {course.is_mandatory && <Badge intent="warning">Mandatory</Badge>}
          {course.duration_minutes && (
            <Text tone="secondary">{course.duration_minutes} min</Text>
          )}
        </HStack>

        {/* Tabs */}
        <Tabs tabs={tabDefs} value={tab} onChange={setTab}>
          {tab === 'overview' && (
            <VStack gap={4}>
              {course.summary && (
                <Card>
                  <CardBody>
                    <VStack gap={2}>
                      <Eyebrow>Description</Eyebrow>
                      <Text>{course.summary}</Text>
                    </VStack>
                  </CardBody>
                </Card>
              )}

              {(course.learning_objectives ?? []).length > 0 && (
                <Card>
                  <CardBody>
                    <VStack gap={2}>
                      <Eyebrow>Learning Objectives</Eyebrow>
                      <ul className="list-disc list-inside space-y-1 text-sm">
                        {course.learning_objectives.map((obj, i) => (
                          <li key={i}><Text>{obj}</Text></li>
                        ))}
                      </ul>
                    </VStack>
                  </CardBody>
                </Card>
              )}

              {(course.prerequisites ?? []).length > 0 && (
                <Card>
                  <CardBody>
                    <VStack gap={2}>
                      <Eyebrow>Prerequisites</Eyebrow>
                      <ul className="list-disc list-inside space-y-1 text-sm">
                        {course.prerequisites.map((pre, i) => (
                          <li key={i}><Text>{pre}</Text></li>
                        ))}
                      </ul>
                    </VStack>
                  </CardBody>
                </Card>
              )}

              {(course.materials ?? []).length > 0 && (
                <Card>
                  <CardBody>
                    <VStack gap={2}>
                      <Eyebrow>Materials</Eyebrow>
                      <VStack gap={2}>
                        {course.materials.map((mat, i) => (
                          <HStack key={i} gap={2} align="center">
                            <Badge intent="neutral">{mat.kind}</Badge>
                            {mat.kind === 'link' ? (
                              <a className="aeos-link" href={mat.url} target="_blank" rel="noopener noreferrer">
                                {mat.label}
                              </a>
                            ) : (
                              <Text>{mat.label} — download not available in browser</Text>
                            )}
                          </HStack>
                        ))}
                      </VStack>
                    </VStack>
                  </CardBody>
                </Card>
              )}

              {!(course.summary) && !(course.learning_objectives ?? []).length && !(course.materials ?? []).length && (
                <Text tone="secondary">No overview content has been added yet.</Text>
              )}
            </VStack>
          )}

          {tab === 'sessions' && (
            <VStack gap={3}>
              {canEdit && (
                <HStack>
                  <Button
                    intent="primary"
                    size="sm"
                    onClick={() => router.get(route('hrm.training.sessions.create', course.id))}
                  >
                    Schedule Session
                  </Button>
                </HStack>
              )}
              <DataTable
                columns={sessionColumns}
                rows={course.sessions ?? []}
                empty="No sessions scheduled yet."
              />
            </VStack>
          )}

          {tab === 'enrollments' && (
            <DataTable
              columns={enrollmentColumns}
              rows={allEnrollments}
              empty="No enrollments yet."
            />
          )}

          {tab === 'analytics' && (
            <VStack gap={4}>
              {avgRating ? (
                <Card>
                  <CardBody>
                    <VStack gap={3}>
                      <Eyebrow>Feedback Summary</Eyebrow>
                      <HStack gap={4}>
                        <VStack gap={1}>
                          <Text tone="secondary">Average Overall Rating</Text>
                          <Text size="lg">{avgRating} / 5</Text>
                        </VStack>
                        <VStack gap={1}>
                          <Text tone="secondary">Total Responses</Text>
                          <Text size="lg">{feedbacks.length}</Text>
                        </VStack>
                      </HStack>
                    </VStack>
                  </CardBody>
                </Card>
              ) : (
                <Alert intent="info" title="No feedback yet">
                  Feedback will appear here once participants complete the training and submit their ratings.
                </Alert>
              )}
            </VStack>
          )}
        </Tabs>
      </VStack>
    </DetailPageLayout>
  );
}

CoursesShow.layout = page => <App title="Course Detail">{page}</App>;
