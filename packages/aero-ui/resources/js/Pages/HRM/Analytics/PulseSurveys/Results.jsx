import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, Button, HStack, VStack,
  Text, Eyebrow, Alert, Badge,
} from '@aero/ui';

function ScaleResult({ questionId, aggregate }) {
  const avg = aggregate?.average ?? null;
  const pct = avg !== null ? Math.round((avg / 10) * 100) : 0;

  return (
    <VStack gap={1}>
      {avg !== null ? (
        <HStack gap={3} align="center">
          <Text>{avg.toFixed(2)} / 10</Text>
          <div className="aeos-progress-track">
            <div className="aeos-progress-fill" style={{ width: `${pct}%` }} />
          </div>
        </HStack>
      ) : (
        <Text tone="secondary">No responses yet.</Text>
      )}
    </VStack>
  );
}

function DistributionResult({ aggregate }) {
  const dist = aggregate?.distribution ?? {};
  const entries = Object.entries(dist);

  if (entries.length === 0) {
    return <Text tone="secondary">No responses yet.</Text>;
  }

  const columns = [
    { key: 'value', label: 'Choice', render: row => <Text>{row.value}</Text> },
    { key: 'count', label: 'Count',  render: row => <Text>{row.count}</Text> },
  ];

  return (
    <DataTable
      columns={columns}
      rows={entries.map(([v, c]) => ({ value: v, count: c }))}
      empty="No distribution data."
    />
  );
}

export default function PulseSurveysResults({ survey, suppressed, responseCount, aggregates }) {
  const canView = useHRMAC('hrm.analytics.pulse-surveys.results');

  const SUPPRESSION_THRESHOLD = 5;

  return (
    <IndexPageLayout
      title={`Results: ${survey.title}`}
      breadcrumb={[
        { label: 'HRM' },
        { label: 'Analytics' },
        { label: 'Pulse Surveys', href: route('hrm.analytics.pulse-surveys.index') },
        { label: 'Results' },
      ]}
      actions={
        <Button
          intent="ghost"
          onClick={() => router.get(route('hrm.analytics.pulse-surveys.index'))}
        >
          Back to Surveys
        </Button>
      }
      table={
        <VStack gap={6}>
          <HStack gap={3} align="center">
            <Eyebrow>Total Responses</Eyebrow>
            <Badge intent={suppressed ? 'warning' : 'success'}>
              {responseCount} / {SUPPRESSION_THRESHOLD}
            </Badge>
          </HStack>

          {suppressed ? (
            <Alert intent="warning" title="Results Hidden">
              Results are hidden until at least {SUPPRESSION_THRESHOLD} responses are received ({responseCount}/{SUPPRESSION_THRESHOLD}).
            </Alert>
          ) : (
            <VStack gap={6}>
              {(survey.questions ?? []).map(q => (
                <VStack key={q.id} gap={2}>
                  <Eyebrow>{q.text}</Eyebrow>
                  <Badge intent="neutral">{q.type}</Badge>
                  {q.type === 'scale' && (
                    <ScaleResult
                      questionId={q.id}
                      aggregate={aggregates?.[q.id]}
                    />
                  )}
                  {(q.type === 'choice' || q.type === 'text') && (
                    <DistributionResult
                      aggregate={aggregates?.[q.id]}
                    />
                  )}
                </VStack>
              ))}
            </VStack>
          )}
        </VStack>
      }
    />
  );
}

PulseSurveysResults.layout = page => <App title="Survey Results">{page}</App>;
