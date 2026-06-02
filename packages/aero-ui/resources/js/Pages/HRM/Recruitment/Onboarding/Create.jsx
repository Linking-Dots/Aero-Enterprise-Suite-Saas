import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '@/Pages/App.jsx';
import {
  VStack, HStack, Box, Text, Eyebrow, Badge, Button, Field, Input, Select,
  Textarea, Card,
} from '@aero/ui';

const EMPLOYMENT_TYPE_OPTIONS = [
  { value: '',          label: 'Select type' },
  { value: 'full_time', label: 'Full Time' },
  { value: 'part_time', label: 'Part Time' },
  { value: 'contract',  label: 'Contract' },
  { value: 'intern',    label: 'Intern' },
];

const STEP_LABELS = [
  'Confirm Hire',
  'Employee Details',
  'Checklist',
  'Review & Launch',
];

export default function OnboardingCreate({ application, templates }) {
  const [step, setStep] = useState(0);

  const { data, setData, post, processing, errors } = useForm({
    employment_type:  '',
    start_date:       '',
    department_id:    '',
    designation_id:   '',
    checklist_text:   '',
  });

  const checklistItems = data.checklist_text
    .split('\n')
    .map(l => l.trim())
    .filter(Boolean);

  function submit(e) {
    e.preventDefault();
    post(route('hrm.recruitment.onboarding.store', application.id));
  }

  function canGoNext() {
    if (step === 1) {
      return data.employment_type !== '' && data.start_date !== '';
    }
    return true;
  }

  return (
    <>
      <style>{`
        .onboarding-wizard {
          max-width: 680px;
          margin: 0 auto;
        }
        .onboarding-steps {
          display: flex;
          gap: 0;
          margin-bottom: 2rem;
        }
        .onboarding-step-item {
          flex: 1;
          display: flex;
          flex-direction: column;
          align-items: center;
          position: relative;
        }
        .onboarding-step-item:not(:last-child)::after {
          content: '';
          position: absolute;
          top: 14px;
          left: calc(50% + 14px);
          width: calc(100% - 28px);
          height: 2px;
          background: var(--aeos-divider);
        }
        .onboarding-step-item.active::after,
        .onboarding-step-item.done::after {
          background: var(--aeos-primary);
        }
        .onboarding-step-dot {
          width: 28px;
          height: 28px;
          border-radius: 50%;
          background: var(--aeos-bg-surface);
          border: 2px solid var(--aeos-divider);
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 0.375rem;
          font-size: 0.75rem;
          font-family: var(--aeos-font-mono);
          color: var(--aeos-text-tertiary);
          position: relative;
          z-index: 1;
        }
        .onboarding-step-item.active .onboarding-step-dot {
          border-color: var(--aeos-primary);
          background: var(--aeos-primary);
          color: #fff;
        }
        .onboarding-step-item.done .onboarding-step-dot {
          border-color: var(--aeos-success);
          background: var(--aeos-success);
          color: #fff;
        }
        .onboarding-step-label {
          font-size: 0.75rem;
          color: var(--aeos-text-secondary);
          text-align: center;
        }
        .onboarding-step-item.active .onboarding-step-label {
          color: var(--aeos-text-primary);
          font-weight: 600;
        }
        .onboarding-review-row {
          display: flex;
          justify-content: space-between;
          padding: 0.375rem 0;
          border-bottom: 1px solid var(--aeos-divider);
        }
        .onboarding-review-row:last-child { border-bottom: none; }
        .onboarding-checklist-preview {
          padding: 0.5rem 0;
        }
        .onboarding-checklist-item {
          display: flex;
          align-items: flex-start;
          gap: 0.5rem;
          padding: 0.25rem 0;
        }
        .onboarding-checklist-bullet {
          width: 6px;
          height: 6px;
          min-width: 6px;
          border-radius: 50%;
          background: var(--aeos-primary);
          margin-top: 0.4rem;
        }
      `}</style>

      <div className="onboarding-wizard">
        <VStack gap={6}>
          <VStack gap={1}>
            <Eyebrow>Recruitment</Eyebrow>
            <Text size="lg">Start Onboarding — {application?.applicant?.name ?? '—'}</Text>
          </VStack>

          {/* Step indicator */}
          <div className="onboarding-steps">
            {STEP_LABELS.map((label, i) => (
              <div
                key={i}
                className={`onboarding-step-item${i === step ? ' active' : ''}${i < step ? ' done' : ''}`}
              >
                <div className="onboarding-step-dot">
                  {i < step ? '✓' : i + 1}
                </div>
                <span className="onboarding-step-label">{label}</span>
              </div>
            ))}
          </div>

          {/* Step 0: Confirm Hire */}
          {step === 0 && (
            <Card>
              <VStack gap={4}>
                <Eyebrow>Confirm Hire Details</Eyebrow>
                <VStack gap={3}>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Applicant Name</Text>
                    <Text>{application?.applicant?.name ?? '—'}</Text>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Email</Text>
                    <Text>{application?.applicant?.email ?? '—'}</Text>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Job Title</Text>
                    <Text>{application?.job?.title ?? '—'}</Text>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Department</Text>
                    <Text>{application?.job?.department?.name ?? '—'}</Text>
                  </div>
                </VStack>
              </VStack>
            </Card>
          )}

          {/* Step 1: Employee Details */}
          {step === 1 && (
            <Card>
              <VStack gap={5}>
                <Eyebrow>Employee Details</Eyebrow>
                <Field label="Employment Type" htmlFor="employment_type" error={errors.employment_type} required>
                  <Select
                    id="employment_type"
                    options={EMPLOYMENT_TYPE_OPTIONS}
                    value={data.employment_type}
                    onChange={e => setData('employment_type', e.target.value)}
                  />
                </Field>
                <Field label="Start Date" htmlFor="start_date" error={errors.start_date} required>
                  <Input
                    id="start_date"
                    type="date"
                    value={data.start_date}
                    onChange={e => setData('start_date', e.target.value)}
                    error={!!errors.start_date}
                  />
                </Field>
                <Field label="Department ID" htmlFor="department_id" error={errors.department_id}>
                  <Input
                    id="department_id"
                    value={data.department_id}
                    onChange={e => setData('department_id', e.target.value)}
                    placeholder="Optional"
                    error={!!errors.department_id}
                  />
                </Field>
                <Field label="Designation ID" htmlFor="designation_id" error={errors.designation_id}>
                  <Input
                    id="designation_id"
                    value={data.designation_id}
                    onChange={e => setData('designation_id', e.target.value)}
                    placeholder="Optional"
                    error={!!errors.designation_id}
                  />
                </Field>
              </VStack>
            </Card>
          )}

          {/* Step 2: Checklist */}
          {step === 2 && (
            <Card>
              <VStack gap={4}>
                <Eyebrow>Onboarding Checklist</Eyebrow>
                <Text tone="secondary">Enter one checklist item per line.</Text>
                <Field label="Checklist Items" htmlFor="checklist_text" error={errors.checklist_text}>
                  <Textarea
                    id="checklist_text"
                    value={data.checklist_text}
                    onChange={e => setData('checklist_text', e.target.value)}
                    placeholder={"Set up email account\nProvide equipment\nComplete HR paperwork"}
                    error={!!errors.checklist_text}
                  />
                </Field>
                {checklistItems.length > 0 && (
                  <VStack gap={1}>
                    <Eyebrow>Preview</Eyebrow>
                    <div className="onboarding-checklist-preview">
                      {checklistItems.map((item, i) => (
                        <div key={i} className="onboarding-checklist-item">
                          <div className="onboarding-checklist-bullet" />
                          <Text>{item}</Text>
                        </div>
                      ))}
                    </div>
                  </VStack>
                )}
              </VStack>
            </Card>
          )}

          {/* Step 3: Review & Launch */}
          {step === 3 && (
            <Card>
              <VStack gap={4}>
                <Eyebrow>Review & Launch</Eyebrow>
                <VStack gap={2}>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Applicant</Text>
                    <Text>{application?.applicant?.name ?? '—'}</Text>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Job</Text>
                    <Text>{application?.job?.title ?? '—'}</Text>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Employment Type</Text>
                    <Badge intent="neutral">{data.employment_type || '—'}</Badge>
                  </div>
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Start Date</Text>
                    <Text>{data.start_date || '—'}</Text>
                  </div>
                  {data.department_id && (
                    <div className="onboarding-review-row">
                      <Text tone="secondary">Department ID</Text>
                      <Text>{data.department_id}</Text>
                    </div>
                  )}
                  {data.designation_id && (
                    <div className="onboarding-review-row">
                      <Text tone="secondary">Designation ID</Text>
                      <Text>{data.designation_id}</Text>
                    </div>
                  )}
                  <div className="onboarding-review-row">
                    <Text tone="secondary">Checklist Items</Text>
                    <Text>{checklistItems.length} item{checklistItems.length !== 1 ? 's' : ''}</Text>
                  </div>
                </VStack>
                {checklistItems.length > 0 && (
                  <div className="onboarding-checklist-preview">
                    {checklistItems.map((item, i) => (
                      <div key={i} className="onboarding-checklist-item">
                        <div className="onboarding-checklist-bullet" />
                        <Text>{item}</Text>
                      </div>
                    ))}
                  </div>
                )}
              </VStack>
            </Card>
          )}

          {/* Navigation */}
          <HStack gap={3}>
            {step > 0 && (
              <Button
                type="button"
                intent="ghost"
                leftIcon="arrowLeft"
                onClick={() => setStep(s => s - 1)}
              >
                Previous
              </Button>
            )}
            <Box grow />
            {step < STEP_LABELS.length - 1 && (
              <Button
                type="button"
                intent="primary"
                disabled={!canGoNext()}
                onClick={() => setStep(s => s + 1)}
              >
                Next
              </Button>
            )}
            {step === STEP_LABELS.length - 1 && (
              <Button
                type="button"
                intent="primary"
                loading={processing}
                onClick={submit}
              >
                Launch Onboarding
              </Button>
            )}
          </HStack>
        </VStack>
      </div>
    </>
  );
}

OnboardingCreate.layout = page => <App title="Start Onboarding">{page}</App>;
