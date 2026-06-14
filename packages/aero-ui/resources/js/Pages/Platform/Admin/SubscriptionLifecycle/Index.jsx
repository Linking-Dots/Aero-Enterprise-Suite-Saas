import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import {
  IndexPageLayout,
  DataTable,
  Button,
  Badge,
  Card,
  CardBody,
  HStack,
  VStack,
  Text,
  Mono,
  Eyebrow,
  Field,
  Input,
  Select,
  Toggle,
  Modal,
  Alert,
  useToast,
  useHRMAC,
} from '@aero/ui';
import App from '@/Pages/App.jsx';

const TAB_OPTIONS = [
  { value: 'trials',        label: 'Trials' },
  { value: 'plan_changes',  label: 'Plan Changes' },
  { value: 'paused',        label: 'Paused' },
  { value: 'cancellations', label: 'Cancellations' },
];

const BILLABLE_INTENT = {
  plan:    'primary',
  product: 'info',
};

const BILLABLE_OPTIONS = [
  { value: '',        label: 'All types' },
  { value: 'plan',    label: 'Plan' },
  { value: 'product', label: 'Product' },
];

export default function SubscriptionLifecycleIndex({
  trials,
  planChanges,
  paused,
  cancellations,
  plans,
  products,
  cancellationConfig,
}) {
  const toast        = useToast();
  const canExtend    = useHRMAC('subscription-lifecycle.trials.extend');
  const canConvert   = useHRMAC('subscription-lifecycle.trials.convert');
  const canChange    = useHRMAC('subscription-lifecycle.plan-changes.execute');
  const canResume    = useHRMAC('subscription-lifecycle.pause-resume.resume');
  const canConfigure = useHRMAC('subscription-lifecycle.cancellations.configure');

  const [activeTab,        setActiveTab]        = useState('trials');
  const [typeFilter,       setTypeFilter]       = useState('');

  // Extend trial
  const [extendTarget,     setExtendTarget]     = useState(null);
  const [extendDays,       setExtendDays]       = useState('7');
  const [submitting,       setSubmitting]       = useState(false);

  // Convert trial
  const [convertTarget,    setConvertTarget]    = useState(null);
  const [convertTargetId,  setConvertTargetId]  = useState('');

  // Plan change
  const [changeTarget,     setChangeTarget]     = useState(null);
  const [changeTargetId,   setChangeTargetId]   = useState('');
  const [prorationPreview, setProrationPreview] = useState(null);
  const [loadingPreview,   setLoadingPreview]   = useState(false);

  // Resume
  const [resumeTarget,     setResumeTarget]     = useState(null);

  // Cancellation save-flow config
  const planCancelForm = useForm({
    plan_survey_enabled:       cancellationConfig?.plan?.survey_enabled   ?? false,
    plan_offer_enabled:        cancellationConfig?.plan?.offer_enabled    ?? false,
    plan_offer_discount_pct:   cancellationConfig?.plan?.offer_discount_pct  ?? '',
    plan_offer_extension_days: cancellationConfig?.plan?.offer_extension_days ?? '',
  });

  const productCancelForm = useForm({
    product_survey_enabled:       cancellationConfig?.product?.survey_enabled   ?? false,
    product_offer_enabled:        cancellationConfig?.product?.offer_enabled    ?? false,
    product_offer_discount_pct:   cancellationConfig?.product?.offer_discount_pct  ?? '',
    product_offer_extension_days: cancellationConfig?.product?.offer_extension_days ?? '',
  });

  // --- Actions ---

  function doExtend() {
    if (!extendTarget) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.subscription-lifecycle.trials.extend', extendTarget.id),
      { days: Number(extendDays), billable_type: extendTarget.billable_type },
      {
        preserveState: true,
        onSuccess: () => { setExtendTarget(null); toast.success(`Trial extended by ${extendDays} days.`); },
        onError:   () => toast.error('Failed to extend trial.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doConvert() {
    if (!convertTarget) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.subscription-lifecycle.trials.convert', convertTarget.id),
      { target_id: convertTargetId, billable_type: convertTarget.billable_type },
      {
        preserveState: true,
        onSuccess: () => { setConvertTarget(null); setConvertTargetId(''); toast.success('Trial converted to active subscription.'); },
        onError:   () => toast.error('Failed to convert trial.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function openPlanChange(row) {
    setChangeTarget(row);
    setChangeTargetId('');
    setProrationPreview(null);
  }

  function loadProrationPreview() {
    if (!changeTarget || !changeTargetId) return;
    setLoadingPreview(true);
    router.get(
      route('platform.admin.subscription-lifecycle.proration.preview', changeTarget.id),
      { new_target_id: changeTargetId, billable_type: changeTarget.billable_type },
      {
        preserveState: true,
        onSuccess: (page) => setProrationPreview(page.props.prorationPreview ?? null),
        onFinish:  () => setLoadingPreview(false),
      }
    );
  }

  function doChange() {
    if (!changeTarget || !changeTargetId) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.subscription-lifecycle.plan-changes.execute', changeTarget.id),
      { new_target_id: changeTargetId, billable_type: changeTarget.billable_type },
      {
        preserveState: true,
        onSuccess: () => { setChangeTarget(null); toast.success('Plan change executed.'); },
        onError:   () => toast.error('Failed to execute plan change.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function doResume() {
    if (!resumeTarget) return;
    setSubmitting(true);
    router.post(
      route('platform.admin.subscription-lifecycle.resume', resumeTarget.id),
      { billable_type: resumeTarget.billable_type },
      {
        preserveState: true,
        onSuccess: () => { setResumeTarget(null); toast.success('Subscription resumed.'); },
        onError:   () => toast.error('Failed to resume subscription.'),
        onFinish:  () => setSubmitting(false),
      }
    );
  }

  function savePlanCancelConfig(e) {
    e.preventDefault();
    planCancelForm.put(
      route('platform.admin.subscription-lifecycle.cancellations.update'),
      {
        preserveState: true,
        onSuccess: () => toast.success('Plan cancellation config saved.'),
        onError:   () => toast.error('Failed to save config.'),
      }
    );
  }

  function saveProductCancelConfig(e) {
    e.preventDefault();
    productCancelForm.put(
      route('platform.admin.subscription-lifecycle.cancellations.update'),
      {
        preserveState: true,
        onSuccess: () => toast.success('Product cancellation config saved.'),
        onError:   () => toast.error('Failed to save config.'),
      }
    );
  }

  // --- Filtered data ---

  function applyTypeFilter(rows) {
    if (!typeFilter) return rows ?? [];
    return (rows ?? []).filter(r => r.billable_type === typeFilter);
  }

  // --- Conversion target options ---

  function conversionOptions(row) {
    if (!row) return [];
    if (row.billable_type === 'plan') {
      return (plans ?? []).map(p => ({ value: String(p.id), label: p.name }));
    }
    return (products ?? []).map(p => ({ value: String(p.id), label: p.name }));
  }

  // --- Column definitions ---

  const billableTypeCell = (row) => (
    <Badge intent={BILLABLE_INTENT[row.billable_type] ?? 'neutral'}>
      {row.billable_type === 'plan' ? 'Plan' : 'Product'}
    </Badge>
  );

  const trialColumns = [
    { key: 'type',        label: 'Type',        render: billableTypeCell },
    { key: 'tenant',      label: 'Tenant',      render: (row) => <Mono>{row.tenant_id}</Mono> },
    { key: 'plan_name',   label: 'Plan/Product', render: (row) => <Text>{row.billable?.name ?? '—'}</Text> },
    { key: 'trial_ends',  label: 'Trial Ends',   render: (row) => <Mono>{row.trial_ends_at ?? '—'}</Mono> },
    { key: 'status',      label: 'Status',       render: (row) => <Badge intent="warning">{row.status}</Badge> },
    {
      key: 'actions',
      label: '',
      render: (row) => (
        <HStack gap={1}>
          {canExtend && (
            <Button intent="ghost" size="sm" onClick={() => { setExtendTarget(row); setExtendDays('7'); }}>
              Extend
            </Button>
          )}
          {canConvert && (
            <Button intent="soft" size="sm" onClick={() => { setConvertTarget(row); setConvertTargetId(''); }}>
              Convert
            </Button>
          )}
        </HStack>
      ),
    },
  ];

  const planChangeColumns = [
    { key: 'type',        label: 'Type',         render: billableTypeCell },
    { key: 'tenant',      label: 'Tenant',       render: (row) => <Mono>{row.tenant_id}</Mono> },
    { key: 'from',        label: 'From',         render: (row) => <Text>{row.from_plan ?? '—'}</Text> },
    { key: 'to',          label: 'To',           render: (row) => <Text>{row.to_plan ?? '—'}</Text> },
    { key: 'changed_at',  label: 'Changed At',   render: (row) => <Mono tone="secondary">{row.changed_at ?? '—'}</Mono> },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canChange && row.status !== 'completed' ? (
          <Button intent="ghost" size="sm" onClick={() => openPlanChange(row)}>
            Change Plan
          </Button>
        ) : null,
    },
  ];

  const pausedColumns = [
    { key: 'type',       label: 'Type',        render: billableTypeCell },
    { key: 'tenant',     label: 'Tenant',      render: (row) => <Mono>{row.tenant_id}</Mono> },
    { key: 'plan_name',  label: 'Plan/Product', render: (row) => <Text>{row.billable?.name ?? '—'}</Text> },
    { key: 'paused_at',  label: 'Paused At',   render: (row) => <Mono tone="secondary">{row.paused_at ?? '—'}</Mono> },
    { key: 'resume_at',  label: 'Resumes At',  render: (row) => <Mono tone="secondary">{row.resume_at ?? 'Manual'}</Mono> },
    {
      key: 'actions',
      label: '',
      render: (row) =>
        canResume && (
          <Button intent="soft" size="sm" onClick={() => setResumeTarget(row)}>
            Resume
          </Button>
        ),
    },
  ];

  const cancellationColumns = [
    { key: 'type',           label: 'Type',        render: billableTypeCell },
    { key: 'tenant',         label: 'Tenant',      render: (row) => <Mono>{row.tenant_id}</Mono> },
    { key: 'plan_name',      label: 'Plan/Product', render: (row) => <Text>{row.billable?.name ?? '—'}</Text> },
    { key: 'cancelled_at',   label: 'Cancelled At', render: (row) => <Mono tone="secondary">{row.cancelled_at ?? '—'}</Mono> },
    { key: 'cancel_reason',  label: 'Reason',       render: (row) => <Text tone="secondary">{row.cancel_reason ?? '—'}</Text> },
  ];

  return (
    <IndexPageLayout
      title="Subscription Lifecycle"
      breadcrumb={[
        { label: 'Platform Admin', href: route('platform.admin.onboarding.dashboard') },
        { label: 'Subscription Lifecycle' },
      ]}
      description="Manage trials, plan changes, paused subscriptions, and cancellation save flows. Covers both plan and product subscriptions."
    >
      <VStack gap={4}>

        {/* Tab switcher */}
        <HStack gap={2} wrap>
          {TAB_OPTIONS.map(tab => (
            <Button
              key={tab.value}
              intent={activeTab === tab.value ? 'primary' : 'ghost'}
              size="sm"
              onClick={() => setActiveTab(tab.value)}
            >
              {tab.label}
            </Button>
          ))}
        </HStack>

        {/* Billable type filter (not shown on cancellations config tab) */}
        {activeTab !== 'cancellations' && (
          <HStack gap={3}>
            <Text tone="secondary">Filter by type:</Text>
            {BILLABLE_OPTIONS.map(opt => (
              <Button
                key={opt.value}
                intent={typeFilter === opt.value ? 'soft' : 'ghost'}
                size="sm"
                onClick={() => setTypeFilter(opt.value)}
              >
                {opt.label}
              </Button>
            ))}
          </HStack>
        )}

        {/* --- Trials tab --- */}
        {activeTab === 'trials' && (
          <DataTable
            columns={trialColumns}
            rows={applyTypeFilter(trials)}
            empty="No active trials."
          />
        )}

        {/* --- Plan Changes tab --- */}
        {activeTab === 'plan_changes' && (
          <DataTable
            columns={planChangeColumns}
            rows={applyTypeFilter(planChanges)}
            empty="No plan change history."
          />
        )}

        {/* --- Paused tab --- */}
        {activeTab === 'paused' && (
          <DataTable
            columns={pausedColumns}
            rows={applyTypeFilter(paused)}
            empty="No paused subscriptions."
          />
        )}

        {/* --- Cancellations tab --- */}
        {activeTab === 'cancellations' && (
          <VStack gap={6}>
            <DataTable
              columns={cancellationColumns}
              rows={cancellations ?? []}
              empty="No cancellation records."
            />

            {canConfigure && (
              <VStack gap={4}>
                <Eyebrow>Cancellation Save-Flow Configuration</Eyebrow>
                <Alert
                  intent="info"
                  title="Plan and product cancellations can each have their own save offer and survey settings."
                />

                {/* Plan cancellation config */}
                <Card>
                  <CardBody>
                    <VStack gap={4}>
                      <Eyebrow>Plan Subscription Cancellations</Eyebrow>

                      <Toggle
                        label="Enable cancellation survey"
                        checked={planCancelForm.data.plan_survey_enabled}
                        onChange={e => planCancelForm.setData('plan_survey_enabled', e.target.checked)}
                      />

                      <Toggle
                        label="Enable save offer"
                        checked={planCancelForm.data.plan_offer_enabled}
                        onChange={e => planCancelForm.setData('plan_offer_enabled', e.target.checked)}
                      />

                      {planCancelForm.data.plan_offer_enabled && (
                        <HStack gap={3}>
                          <Field label="Discount %" htmlFor="plan_offer_discount" error={planCancelForm.errors.plan_offer_discount_pct} hint="One-time discount offered at cancellation">
                            <Input
                              id="plan_offer_discount"
                              type="number"
                              value={planCancelForm.data.plan_offer_discount_pct}
                              onChange={e => planCancelForm.setData('plan_offer_discount_pct', e.target.value)}
                              placeholder="20"
                              error={planCancelForm.errors.plan_offer_discount_pct}
                            />
                          </Field>
                          <Field label="Extension Days" htmlFor="plan_offer_ext" error={planCancelForm.errors.plan_offer_extension_days} hint="Free days offered as an alternative to cancel">
                            <Input
                              id="plan_offer_ext"
                              type="number"
                              value={planCancelForm.data.plan_offer_extension_days}
                              onChange={e => planCancelForm.setData('plan_offer_extension_days', e.target.value)}
                              placeholder="30"
                              error={planCancelForm.errors.plan_offer_extension_days}
                            />
                          </Field>
                        </HStack>
                      )}

                      <HStack gap={2}>
                        <Button
                          intent="primary"
                          loading={planCancelForm.processing}
                          disabled={planCancelForm.processing}
                          onClick={savePlanCancelConfig}
                        >
                          Save Plan Config
                        </Button>
                      </HStack>
                    </VStack>
                  </CardBody>
                </Card>

                {/* Product cancellation config */}
                <Card>
                  <CardBody>
                    <VStack gap={4}>
                      <Eyebrow>Product Subscription Cancellations</Eyebrow>

                      <Toggle
                        label="Enable cancellation survey"
                        checked={productCancelForm.data.product_survey_enabled}
                        onChange={e => productCancelForm.setData('product_survey_enabled', e.target.checked)}
                      />

                      <Toggle
                        label="Enable save offer"
                        checked={productCancelForm.data.product_offer_enabled}
                        onChange={e => productCancelForm.setData('product_offer_enabled', e.target.checked)}
                      />

                      {productCancelForm.data.product_offer_enabled && (
                        <HStack gap={3}>
                          <Field label="Discount %" htmlFor="prod_offer_discount" error={productCancelForm.errors.product_offer_discount_pct}>
                            <Input
                              id="prod_offer_discount"
                              type="number"
                              value={productCancelForm.data.product_offer_discount_pct}
                              onChange={e => productCancelForm.setData('product_offer_discount_pct', e.target.value)}
                              placeholder="20"
                              error={productCancelForm.errors.product_offer_discount_pct}
                            />
                          </Field>
                          <Field label="Extension Days" htmlFor="prod_offer_ext" error={productCancelForm.errors.product_offer_extension_days}>
                            <Input
                              id="prod_offer_ext"
                              type="number"
                              value={productCancelForm.data.product_offer_extension_days}
                              onChange={e => productCancelForm.setData('product_offer_extension_days', e.target.value)}
                              placeholder="30"
                              error={productCancelForm.errors.product_offer_extension_days}
                            />
                          </Field>
                        </HStack>
                      )}

                      <HStack gap={2}>
                        <Button
                          intent="primary"
                          loading={productCancelForm.processing}
                          disabled={productCancelForm.processing}
                          onClick={saveProductCancelConfig}
                        >
                          Save Product Config
                        </Button>
                      </HStack>
                    </VStack>
                  </CardBody>
                </Card>
              </VStack>
            )}
          </VStack>
        )}
      </VStack>

      {/* Extend Trial Modal */}
      <Modal
        open={!!extendTarget}
        onClose={() => setExtendTarget(null)}
        title="Extend Trial"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setExtendTarget(null)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doExtend}>
              Extend Trial
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Tenant: <Mono>{extendTarget?.tenant_id}</Mono>
          </Text>
          <Badge intent={BILLABLE_INTENT[extendTarget?.billable_type] ?? 'neutral'}>
            {extendTarget?.billable_type === 'plan' ? 'Plan Subscription' : 'Product Subscription'}
          </Badge>
          <Field label="Days to Extend" htmlFor="ext_days" required>
            <Input
              id="ext_days"
              type="number"
              value={extendDays}
              onChange={e => setExtendDays(e.target.value)}
              placeholder="7"
            />
          </Field>
        </VStack>
      </Modal>

      {/* Convert Trial Modal */}
      <Modal
        open={!!convertTarget}
        onClose={() => { setConvertTarget(null); setConvertTargetId(''); }}
        title="Convert Trial to Active"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setConvertTarget(null); setConvertTargetId(''); }}>
              Cancel
            </Button>
            <Button
              intent="primary"
              loading={submitting}
              disabled={submitting || !convertTargetId}
              onClick={doConvert}
            >
              Convert
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Tenant: <Mono>{convertTarget?.tenant_id}</Mono>
          </Text>
          <Badge intent={BILLABLE_INTENT[convertTarget?.billable_type] ?? 'neutral'}>
            {convertTarget?.billable_type === 'plan' ? 'Plan Subscription' : 'Product Subscription'}
          </Badge>
          <Alert intent="warning" title="Conversion fails if the subscription is already active." />
          <Field label={convertTarget?.billable_type === 'plan' ? 'Target Plan' : 'Target Product'} htmlFor="conv_target" required>
            <Select
              id="conv_target"
              value={convertTargetId}
              onChange={e => setConvertTargetId(e.target.value)}
              options={[{ value: '', label: 'Select...' }, ...conversionOptions(convertTarget)]}
            />
          </Field>
        </VStack>
      </Modal>

      {/* Plan Change Modal */}
      <Modal
        open={!!changeTarget}
        onClose={() => { setChangeTarget(null); setChangeTargetId(''); setProrationPreview(null); }}
        title="Execute Plan Change"
        size="md"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => { setChangeTarget(null); setChangeTargetId(''); setProrationPreview(null); }}>
              Cancel
            </Button>
            <Button
              intent="soft"
              loading={loadingPreview}
              disabled={loadingPreview || !changeTargetId}
              onClick={loadProrationPreview}
            >
              Preview Proration
            </Button>
            <Button
              intent="primary"
              loading={submitting}
              disabled={submitting || !changeTargetId}
              onClick={doChange}
            >
              Execute Change
            </Button>
          </HStack>
        }
      >
        <VStack gap={3}>
          <Text tone="secondary">
            Tenant: <Mono>{changeTarget?.tenant_id}</Mono>
          </Text>
          <Badge intent={BILLABLE_INTENT[changeTarget?.billable_type] ?? 'neutral'}>
            {changeTarget?.billable_type === 'plan' ? 'Plan Subscription' : 'Product Subscription'}
          </Badge>
          <Alert
            intent="info"
            title="Changing a plan subscription does not affect any product subscriptions for this tenant."
          />
          <Field label={changeTarget?.billable_type === 'plan' ? 'New Plan' : 'New Product'} htmlFor="change_target" required>
            <Select
              id="change_target"
              value={changeTargetId}
              onChange={e => { setChangeTargetId(e.target.value); setProrationPreview(null); }}
              options={[{ value: '', label: 'Select...' }, ...conversionOptions(changeTarget)]}
            />
          </Field>
          {prorationPreview && (
            <Card>
              <CardBody>
                <VStack gap={2}>
                  <Eyebrow>Proration Preview</Eyebrow>
                  <HStack gap={4}>
                    <VStack gap={0}>
                      <Text tone="secondary">Credit</Text>
                      <Mono>${Number(prorationPreview.credit ?? 0).toFixed(2)}</Mono>
                    </VStack>
                    <VStack gap={0}>
                      <Text tone="secondary">Charge</Text>
                      <Mono>${Number(prorationPreview.charge ?? 0).toFixed(2)}</Mono>
                    </VStack>
                    <VStack gap={0}>
                      <Text tone="secondary">Net</Text>
                      <Mono>${Number(prorationPreview.net ?? 0).toFixed(2)}</Mono>
                    </VStack>
                  </HStack>
                </VStack>
              </CardBody>
            </Card>
          )}
        </VStack>
      </Modal>

      {/* Resume Modal */}
      <Modal
        open={!!resumeTarget}
        onClose={() => setResumeTarget(null)}
        title="Resume Subscription?"
        size="sm"
        footer={
          <HStack gap={2}>
            <Button intent="ghost" onClick={() => setResumeTarget(null)}>Cancel</Button>
            <Button intent="primary" loading={submitting} disabled={submitting} onClick={doResume}>
              Resume
            </Button>
          </HStack>
        }
      >
        <VStack gap={2}>
          <Text tone="secondary">
            Tenant: <Mono>{resumeTarget?.tenant_id}</Mono>
          </Text>
          <Badge intent={BILLABLE_INTENT[resumeTarget?.billable_type] ?? 'neutral'}>
            {resumeTarget?.billable_type === 'plan' ? 'Plan Subscription' : 'Product Subscription'}
          </Badge>
          <Text tone="secondary">
            Resuming will reactivate billing and restore access.
          </Text>
        </VStack>
      </Modal>
    </IndexPageLayout>
  );
}

SubscriptionLifecycleIndex.layout = page => <App title="Subscription Lifecycle">{page}</App>;
