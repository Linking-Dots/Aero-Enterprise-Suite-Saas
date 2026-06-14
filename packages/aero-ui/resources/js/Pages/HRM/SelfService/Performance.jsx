import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Mono, Eyebrow, Badge, Card, CardBody, DataTable, Progress,
} from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

function goalStatusIntent(status) {
  switch (status) {
    case 'achieved': return 'success';
    case 'missed':   return 'danger';
    case 'open':     return 'primary';
    default:         return 'neutral';
  }
}

function reviewStatusIntent(status) {
  switch (status) {
    case 'finalized':         return 'success';
    case 'manager_submitted': return 'primary';
    case 'self_submitted':    return 'warning';
    default:                  return 'neutral';
  }
}

function statusLabel(status) {
  return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function fmt(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

export default function SelfServicePerformance({ goals, reviews }) {
  const goalColumns = [
    { key: 'title', label: 'Goal' },
    {
      key: 'progress', label: 'Progress',
      render: row => (
        <HStack gap={2} align="center">
          <Progress value={row.progress ?? 0} />
          <span className="aeos-text-sm aeos-text-secondary">{row.progress ?? 0}%</span>
        </HStack>
      ),
    },
    {
      key: 'status', label: 'Status',
      render: row => (
        <Badge intent={goalStatusIntent(row.status)}>
          {statusLabel(row.status)}
        </Badge>
      ),
    },
    {
      key: 'time_bound', label: 'Deadline',
      render: row => <Text tone="secondary">{fmt(row.time_bound)}</Text>,
    },
  ];

  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">My Performance</Text>
        </VStack>

        {/* Goals section */}
        <VStack gap={3}>
          <Eyebrow>My Goals</Eyebrow>
          <DataTable
            columns={goalColumns}
            rows={goals ?? []}
            empty="No goals found."
          />
        </VStack>

        {/* Reviews section */}
        <VStack gap={3}>
          <Eyebrow>My Reviews</Eyebrow>
          {(reviews ?? []).length === 0 ? (
            <Card>
              <CardBody>
                <Text tone="secondary">No performance reviews found.</Text>
              </CardBody>
            </Card>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {(reviews ?? []).map(r => (
                <Card key={r.id}>
                  <CardBody>
                    <VStack gap={3}>
                      <HStack gap={2} align="center">
                        <Box grow>
                          <Text>{r.cycle?.name ?? '—'}</Text>
                        </Box>
                        <Badge intent={reviewStatusIntent(r.status)}>
                          {statusLabel(r.status)}
                        </Badge>
                      </HStack>
                      <VStack gap={1}>
                        <Text tone="tertiary" size="sm">Final Rating</Text>
                        {r.final_rating != null ? (
                          <Text size="lg">{r.final_rating}</Text>
                        ) : (
                          <Text tone="secondary">In progress</Text>
                        )}
                      </VStack>
                    </VStack>
                  </CardBody>
                </Card>
              ))}
            </div>
          )}
        </VStack>
      </VStack>
    </div>
  );
}

SelfServicePerformance.layout = page => <App title="My Performance">{page}</App>;
