import App from '@/Pages/App.jsx';
import { useHRMAC } from '@/hooks/useHRMAC';
import {
  IndexPageLayout, DataTable, HStack, VStack,
  Text, Eyebrow, Alert,
} from '@aero/ui';

export default function DEI({ gender, ageBands, payGap, leadership }) {
  const canView = useHRMAC('hrm.analytics.dei.view');

  const totalGender = (gender ?? []).reduce((sum, g) => sum + (g.count ?? 0), 0);

  const genderColumns = [
    { key: 'label', label: 'Gender',     render: row => <Text>{row.label}</Text> },
    { key: 'count', label: 'Count',      render: row => <Text>{row.count}</Text> },
    {
      key: 'pct',
      label: 'Share',
      render: row => (
        <Text>{totalGender > 0 ? `${Math.round((row.count / totalGender) * 100)}%` : '—'}</Text>
      ),
    },
  ];

  const ageBandColumns = [
    { key: 'label', label: 'Age Band', render: row => <Text>{row.label}</Text> },
    { key: 'count', label: 'Count',    render: row => <Text>{row.count}</Text> },
  ];

  return (
    <IndexPageLayout
      title="Diversity, Equity & Inclusion"
      breadcrumb={[{ label: 'HRM' }, { label: 'Analytics' }, { label: 'DEI' }]}
      table={
        <VStack gap={6}>
          <VStack gap={2}>
            <Eyebrow>Gender Distribution</Eyebrow>
            <DataTable
              columns={genderColumns}
              rows={gender ?? []}
              empty="No gender data available."
            />
          </VStack>

          <VStack gap={2}>
            <Eyebrow>Age Band Distribution</Eyebrow>
            <DataTable
              columns={ageBandColumns}
              rows={ageBands ?? []}
              empty="No age band data available."
            />
          </VStack>

          <VStack gap={2}>
            <Eyebrow>Pay Gap</Eyebrow>
            {Array.isArray(payGap) && payGap.length === 0 ? (
              <Alert intent="info" title="Coming soon">
                Pay gap analysis is not yet available.
              </Alert>
            ) : (
              <Text tone="secondary">Pay gap data present.</Text>
            )}
          </VStack>

          <VStack gap={2}>
            <Eyebrow>Leadership Representation</Eyebrow>
            {Array.isArray(leadership) && leadership.length === 0 ? (
              <Alert intent="info" title="Coming soon">
                Leadership representation analysis is not yet available.
              </Alert>
            ) : (
              <Text tone="secondary">Leadership data present.</Text>
            )}
          </VStack>
        </VStack>
      }
    />
  );
}

DEI.layout = page => <App title="DEI Report">{page}</App>;
