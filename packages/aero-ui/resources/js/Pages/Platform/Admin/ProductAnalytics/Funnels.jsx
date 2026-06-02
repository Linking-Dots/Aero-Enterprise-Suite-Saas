import { useState } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
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
  KPI,
  Field,
  Input,
  Modal,
  useToast,
  useHRMAC,
  Progress,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const CONVERSION_INTENT = { 100: 'success' };

function conversionIntent(pct) {
  if (pct >= 75) return 'success';
  if (pct >= 50) return 'warning';
  return 'danger';
}

function CreateFunnelModal({ open, onClose }) {
  const { errors } = usePage().props;
  const toast = useToast();

  const form = useForm({
    name:  '',
    steps: [{ event: '', label: '' }, { event: '', label: '' }],
  });

  function addStep() {
    form.setData('steps', [...form.data.steps, { event: '', label: '' }]);
  }

  function removeStep(idx) {
    form.setData('steps', form.data.steps.filter((_, i) => i !== idx));
  }

  function updateStep(idx, field, value) {
    const updated = form.data.steps.map((s, i) => i === idx ? { ...s, [field]: value } : s);
    form.setData('steps', updated);
  }

  function submit(e) {
    e.preventDefault();
    form.post(route('platform.admin.product-analytics.funnels.store'), {
      onSuccess: () => {
        toast.success('Funnel created.');
        form.reset();
        onClose();
      },
      onError: () => toast.error('Failed to create funnel.'),
    });
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Create Funnel"
      description="Define a funnel by naming it and specifying the sequence of feature events."
      footer={
        <HStack gap={2}>
          <Button intent="primary" type="submit" form="create-funnel-form" loading={form.processing}>
            Create Funnel
          </Button>
          <Button intent="ghost" onClick={onClose}>
            Cancel
          </Button>
        </HStack>
      }
    >
      <form id="create-funnel-form" onSubmit={submit}>
        <VStack gap={4}>
          <Field label="Funnel Name" htmlFor="funnel-name" error={errors.name} required>
            <Input
              id="funnel-name"
              value={form.data.name}
              onChange={e => form.setData('name', e.target.value)}
              placeholder="e.g. Onboarding Funnel"
              error={!!errors.name}
            />
          </Field>

          <Eyebrow>Steps</Eyebrow>
          {form.data.steps.map((step, idx) => (
            <Card key={idx}>
              <CardBody>
                <VStack gap={3}>
                  <HStack gap={2}>
                    <Text>Step {idx + 1}</Text>
                    {form.data.steps.length > 2 && (
                      <Button intent="danger" size="sm" type="button" onClick={() => removeStep(idx)}>
                        Remove
                      </Button>
                    )}
                  </HStack>
                  <Field
                    label="Feature Event Code"
                    htmlFor={`step-event-${idx}`}
                    error={errors[`steps.${idx}.event`]}
                    required
                  >
                    <Input
                      id={`step-event-${idx}`}
                      value={step.event}
                      onChange={e => updateStep(idx, 'event', e.target.value)}
                      placeholder="e.g. hrm.employee.create"
                      error={!!errors[`steps.${idx}.event`]}
                    />
                  </Field>
                  <Field label="Label (optional)" htmlFor={`step-label-${idx}`}>
                    <Input
                      id={`step-label-${idx}`}
                      value={step.label}
                      onChange={e => updateStep(idx, 'label', e.target.value)}
                      placeholder="Human-readable step name"
                    />
                  </Field>
                </VStack>
              </CardBody>
            </Card>
          ))}

          <Button intent="soft" type="button" onClick={addStep}>
            Add Step
          </Button>
        </VStack>
      </form>
    </Modal>
  );
}

export default function Funnels({ funnels, selected, analysis }) {
  const canManage = useHRMAC('product-analytics.funnel-analysis.manage');
  const [createOpen, setCreateOpen] = useState(false);

  const funnelColumns = [
    {
      key: 'name',
      label: 'Funnel',
      render: (row) => (
        <Button
          intent="ghost"
          size="sm"
          onClick={() =>
            router.get(
              route('platform.admin.product-analytics.funnels'),
              { funnel_id: row.id }
            )
          }
        >
          {row.name}
        </Button>
      ),
    },
    {
      key: 'steps',
      label: 'Steps',
      render: (row) => <Mono>{(row.steps ?? []).length}</Mono>,
    },
    {
      key: 'created_at',
      label: 'Created',
      render: (row) => (
        <Mono tone="secondary">
          {row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'}
        </Mono>
      ),
    },
  ];

  const analysisColumns = [
    {
      key: 'order',
      label: '#',
      render: (row) => <Mono>{(row.order ?? 0) + 1}</Mono>,
    },
    {
      key: 'label',
      label: 'Step',
      render: (row) => <Text>{row.label ?? row.event}</Text>,
    },
    {
      key: 'event',
      label: 'Event Code',
      render: (row) => <Mono tone="secondary">{row.event}</Mono>,
    },
    {
      key: 'tenants',
      label: 'Tenants',
      render: (row) => <Mono>{row.tenants}</Mono>,
    },
    {
      key: 'conversion_pct',
      label: 'Conversion',
      render: (row) => (
        <HStack gap={2}>
          <Progress value={row.conversion_pct ?? 0} />
          <Badge intent={conversionIntent(row.conversion_pct ?? 0)}>
            {row.conversion_pct}%
          </Badge>
        </HStack>
      ),
    },
  ];

  return (
    <IndexPageLayout
      title="Funnel Analysis"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.dashboard') },
        { label: 'Product Analytics' },
        { label: 'Funnels' },
      ]}
      description="Define and analyse multi-step feature adoption funnels."
      actions={
        canManage && (
          <Button intent="primary" onClick={() => setCreateOpen(true)}>
            Create Funnel
          </Button>
        )
      }
    >
      <VStack gap={6}>

        {/* Funnel list */}
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Funnels</Eyebrow>
              <DataTable
                columns={funnelColumns}
                rows={funnels ?? []}
                empty="No funnels defined yet. Create your first funnel to get started."
              />
            </VStack>
          </CardBody>
        </Card>

        {/* Analysis for selected funnel */}
        {selected && (
          <Card>
            <CardBody>
              <VStack gap={3}>
                <HStack gap={2}>
                  <Eyebrow>Analysis — {selected.name}</Eyebrow>
                  <Badge intent="neutral">{(selected.steps ?? []).length} steps</Badge>
                </HStack>

                {analysis ? (
                  <>
                    <HStack gap={4} wrap>
                      <KPI
                        label="Entry Tenants"
                        value={analysis.steps?.[0]?.tenants ?? 0}
                      />
                      <KPI
                        label="Completion Tenants"
                        value={analysis.steps?.[analysis.steps.length - 1]?.tenants ?? 0}
                      />
                      <KPI
                        label="Overall Conversion"
                        value={
                          analysis.steps?.length > 0
                            ? `${analysis.steps[analysis.steps.length - 1]?.conversion_pct ?? 0}%`
                            : '—'
                        }
                      />
                    </HStack>
                    <DataTable
                      columns={analysisColumns}
                      rows={analysis.steps ?? []}
                      empty="No analysis data available."
                    />
                  </>
                ) : (
                  <Text tone="secondary">No analysis data available for this funnel.</Text>
                )}
              </VStack>
            </CardBody>
          </Card>
        )}

      </VStack>

      <CreateFunnelModal open={createOpen} onClose={() => setCreateOpen(false)} />

    </IndexPageLayout>
  );
}

Funnels.layout = page => <App title="Funnel Analysis">{page}</App>;
