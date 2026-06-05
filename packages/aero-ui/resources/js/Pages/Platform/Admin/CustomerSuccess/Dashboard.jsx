import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Card,
  CardBody,
  Field,
  Input,
  Select,
  Modal,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

function healthScoreBadge(score) {
  if (score >= 80) return 'success';
  if (score >= 50) return 'warning';
  return 'danger';
}

function trendIcon(trend) {
  if (trend === 'up') return 'Up';
  if (trend === 'down') return 'Down';
  return 'Stable';
}

export default function CustomerSuccessDashboard({
  healthScores,
  churnRisks,
  nps,
  csmAgents,
}) {
  const toast = useToast();
  const canAssign  = useHRMAC('customer-success.csm-assignment.assign');
  const canSendNps = useHRMAC('customer-success.nps-csat.send');

  const [assignTarget, setAssignTarget] = useState(null);
  const [sendNpsOpen,  setSendNpsOpen]  = useState(false);

  const assignForm = useForm({ csm_user_id: '' });
  const npsForm    = useForm({ segment: 'all' });

  const csmOptions = (csmAgents ?? []).map(a => ({
    value: String(a.id),
    label: a.name,
  }));

  function openAssign(row) {
    setAssignTarget(row);
    assignForm.setData('csm_user_id', row.csm_user_id ? String(row.csm_user_id) : '');
  }

  function submitAssign(e) {
    e.preventDefault();
    assignForm.post(route('platform.admin.customer-success.csm.assign', assignTarget.tenant_id), {
      preserveState: true,
      onSuccess: () => {
        toast.success('CSM assigned.');
        setAssignTarget(null);
      },
      onError: () => toast.error('Assignment failed.'),
    });
  }

  function submitSendNps(e) {
    e.preventDefault();
    npsForm.post(route('platform.admin.customer-success.nps.send'), {
      preserveState: true,
      onSuccess: () => {
        toast.success('NPS survey queued.');
        setSendNpsOpen(false);
        npsForm.reset();
      },
      onError: () => toast.error('Failed to queue NPS survey.'),
    });
  }

  const healthColumns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: r => <Text>{r.tenant_name}</Text>,
    },
    {
      key: 'score',
      label: 'Health Score',
      render: r => (
        <Badge intent={healthScoreBadge(r.score)}>{r.score} / 100</Badge>
      ),
    },
    {
      key: 'trend',
      label: 'Trend',
      render: r => <Text tone="secondary">{trendIcon(r.trend)}</Text>,
    },
    {
      key: 'computed_at',
      label: 'Computed',
      render: r => <Mono>{r.computed_at}</Mono>,
    },
    {
      key: 'csm',
      label: 'CSM',
      render: r => <Text tone="secondary">{r.csm_name ?? '—'}</Text>,
    },
    {
      key: 'actions',
      label: '',
      render: r => (
        <HStack gap={1}>
          {canAssign && (
            <Button intent="ghost" size="sm" onClick={() => openAssign(r)}>
              Assign CSM
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const churnColumns = [
    {
      key: 'tenant',
      label: 'Tenant',
      render: r => <Text>{r.tenant_name}</Text>,
    },
    {
      key: 'risk_level',
      label: 'Risk',
      render: r => (
        <Badge intent={r.risk_level === 'high' ? 'danger' : 'warning'}>
          {r.risk_level}
        </Badge>
      ),
    },
    {
      key: 'signal',
      label: 'Top Signal',
      render: r => <Text tone="secondary">{r.top_signal}</Text>,
    },
    {
      key: 'last_login',
      label: 'Last Login',
      render: r => <Mono>{r.last_login_at ?? 'Never'}</Mono>,
    },
  ];

  const avgNps = nps?.avg_score ?? '—';
  const npsCount = nps?.response_count ?? 0;
  const promoters = nps?.promoters ?? 0;
  const detractors = nps?.detractors ?? 0;

  return (
    <IndexPageLayout
      title="Customer Success"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Customer Success' },
      ]}
      description="Monitor tenant health, churn risks, and NPS scores."
      actions={
        canSendNps && (
          <Button intent="primary" leftIcon="send" onClick={() => setSendNpsOpen(true)}>
            Send NPS Survey
          </Button>
        )
      }
    >
      <VStack gap={6}>
        {/* NPS Summary */}
        <VStack gap={2}>
          <Eyebrow>NPS Summary</Eyebrow>
          <HStack gap={4} wrap>
            <Card>
              <CardBody>
                <VStack gap={1}>
                  <Text tone="secondary">Average Score</Text>
                  <Text size="lg">{avgNps}</Text>
                </VStack>
              </CardBody>
            </Card>
            <Card>
              <CardBody>
                <VStack gap={1}>
                  <Text tone="secondary">Responses</Text>
                  <Text size="lg">{npsCount}</Text>
                </VStack>
              </CardBody>
            </Card>
            <Card>
              <CardBody>
                <VStack gap={1}>
                  <Text tone="secondary">Promoters</Text>
                  <Badge intent="success">{promoters}</Badge>
                </VStack>
              </CardBody>
            </Card>
            <Card>
              <CardBody>
                <VStack gap={1}>
                  <Text tone="secondary">Detractors</Text>
                  <Badge intent="danger">{detractors}</Badge>
                </VStack>
              </CardBody>
            </Card>
          </HStack>
        </VStack>

        {/* Churn Risk Alerts */}
        {(churnRisks ?? []).length > 0 && (
          <VStack gap={2}>
            <Eyebrow>Churn Risk Alerts</Eyebrow>
            <Alert intent="warning" title={`${churnRisks.length} tenant(s) at elevated churn risk.`} />
            <DataTable
              columns={churnColumns}
              rows={churnRisks}
              empty="No churn risks detected."
            />
          </VStack>
        )}

        {/* Health Score Grid */}
        <VStack gap={2}>
          <Eyebrow>Tenant Health Scores</Eyebrow>
          <DataTable
            columns={healthColumns}
            rows={healthScores?.data ?? []}
            empty="No health score data available."
          />
        </VStack>
      </VStack>

      {/* CSM Assignment Modal */}
      <Modal
        open={!!assignTarget}
        onClose={() => setAssignTarget(null)}
        title={`Assign CSM — ${assignTarget?.tenant_name}`}
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={assignForm.processing}
              disabled={assignForm.processing}
              onClick={submitAssign}
            >
              Save
            </Button>
            <Button intent="ghost" onClick={() => setAssignTarget(null)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Customer Success Manager" htmlFor="csm_agent" error={assignForm.errors.csm_user_id} required>
            <Select
              id="csm_agent"
              value={assignForm.data.csm_user_id}
              onChange={e => assignForm.setData('csm_user_id', e.target.value)}
              options={[{ value: '', label: '— Unassigned —' }, ...csmOptions]}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Send NPS Modal */}
      <Modal
        open={sendNpsOpen}
        onClose={() => setSendNpsOpen(false)}
        title="Send NPS Survey"
        footer={
          <HStack gap={2}>
            <Button
              intent="primary"
              loading={npsForm.processing}
              disabled={npsForm.processing}
              onClick={submitSendNps}
            >
              Send
            </Button>
            <Button intent="ghost" onClick={() => setSendNpsOpen(false)}>
              Cancel
            </Button>
          </HStack>
        }
      >
        <VStack gap={4}>
          <Field label="Segment" htmlFor="nps_segment" error={npsForm.errors.segment}>
            <Select
              id="nps_segment"
              value={npsForm.data.segment}
              onChange={e => npsForm.setData('segment', e.target.value)}
              options={[
                { value: 'all', label: 'All Tenants' },
                { value: 'active', label: 'Active Only' },
                { value: 'trial', label: 'Trial Only' },
              ]}
            />
          </Field>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

CustomerSuccessDashboard.layout = page => <App title="Customer Success">{page}</App>;
