import { usePage } from '@inertiajs/react';
import RegistrationLayout from './RegistrationLayout.jsx';
import StepAccount      from './steps/StepAccount.jsx';
import StepDetails      from './steps/StepDetails.jsx';
import StepVerifyEmail  from './steps/StepVerifyEmail.jsx';
import StepVerifyPhone  from './steps/StepVerifyPhone.jsx';
import StepPlan         from './steps/StepPlan.jsx';
import StepBYOC         from './steps/StepBYOC.jsx';
import StepPayment      from './steps/StepPayment.jsx';
import StepProvisioning from './steps/StepProvisioning.jsx';
import StepSuccess      from './steps/StepSuccess.jsx';

const STEP_TITLES = {
  account:        'Create your account',
  details:        'Company details',
  'verify-email': 'Verify your email',
  'verify-phone': 'Verify your phone',
  plan:           'Choose a plan',
  byoc:           'Database setup',
  payment:        'Review & activate',
  provisioning:   'Setting up your workspace',
  success:        "You're all set!",
};

/*
 * Steps that need the wider (940px) card layout.
 * Payment is also wide now — it renders multiple review cards
 * that benefit from extra breathing room.
 */
const WIDE_STEPS = ['plan', 'payment'];

/*
 * shortLabel is used by the tablet step indicator (≤ 7 chars).
 * label is used by the desktop (full text) indicator.
 * The `key` must match the `currentStep` string from the server.
 */
const STEP_DEFINITIONS = [
  { key: 'account',        label: 'Account',      shortLabel: 'Acct' },
  { key: 'details',        label: 'Details',      shortLabel: 'Details' },
  { key: 'verify-email',   label: 'Verify Email', shortLabel: 'Email' },
  { key: 'verify-phone',   label: 'Verify Phone', shortLabel: 'Phone' },
  { key: 'plan',           label: 'Plan',         shortLabel: 'Plan' },
  { key: 'byoc',           label: 'Database',     shortLabel: 'DB' },
  { key: 'payment',        label: 'Activate',     shortLabel: 'Pay' },
  { key: 'provisioning',   label: 'Provisioning', shortLabel: 'Setup' },
  { key: 'success',        label: 'Done',         shortLabel: 'Done' },
];

export default function RegistrationPage(props) {
  const {
    steps         = [],   // array of step keys from server (may omit byoc/phone if not enabled)
    currentStep   = 'account',
    savedData     = {},
    // per-step props
    trialDays,
    accountType,
    baseDomain,
    existingSubdomain,
    email,
    phone,
    companyName,
    plans,
    modules,
    modulePricing,
    tenant,
    result,
  } = props;

  const title = STEP_TITLES[currentStep] ?? 'Sign up';
  const wide  = WIDE_STEPS.includes(currentStep);

  /*
   * Map the server-supplied step keys to full step definition objects.
   * This gives RegistrationLayout access to label + shortLabel for
   * the three-tier adaptive step indicator.
   *
   * If `steps` is empty (server didn't pass it), fall back to all steps.
   */
  const resolvedSteps = (steps.length > 0 ? steps : STEP_DEFINITIONS.map(s => s.key))
    .map(stepObjOrKey => {
      const stepKey = typeof stepObjOrKey === 'string' ? stepObjOrKey : stepObjOrKey.key;
      return STEP_DEFINITIONS.find(d => d.key === stepKey) ?? { 
        key: stepKey, 
        label: typeof stepObjOrKey === 'string' ? stepKey : (stepObjOrKey.label || stepKey), 
        shortLabel: typeof stepObjOrKey === 'string' ? stepKey : (stepObjOrKey.shortLabel || stepObjOrKey.label || stepKey)
      };
    });

  function renderStep() {
    switch (currentStep) {
      case 'account':
        return (
          <StepAccount
            trialDays={trialDays}
            savedData={savedData}
          />
        );

      case 'details':
        return (
          <StepDetails
            accountType={accountType}
            baseDomain={baseDomain}
            existingSubdomain={existingSubdomain}
            savedData={savedData}
          />
        );

      case 'verify-email':
        return (
          <StepVerifyEmail
            email={email}
            companyName={companyName}
          />
        );

      case 'verify-phone':
        return (
          <StepVerifyPhone
            phone={phone}
            companyName={companyName}
          />
        );

      case 'plan':
        return (
          <StepPlan
            plans={plans}
            modules={modules}
            modulePricing={modulePricing}
            savedData={savedData}
          />
        );

      case 'byoc':
        return (
          <StepBYOC
            savedByoc={savedData?.byoc ?? null}
          />
        );

      case 'payment':
        return (
          <StepPayment
            trialDays={trialDays}
            baseDomain={baseDomain}
            plans={plans}
            modules={modules}
            modulePricing={modulePricing}
            savedData={savedData}
          />
        );

      case 'provisioning':
        return (
          <StepProvisioning
            tenant={tenant}
            baseDomain={baseDomain}
          />
        );

      case 'success':
        return (
          <StepSuccess
            result={result}
            baseDomain={baseDomain}
          />
        );

      default:
        return null;
    }
  }

  return (
    <RegistrationLayout
      title={title}
      currentStep={currentStep}
      steps={resolvedSteps}
      wide={wide}
    >
      {renderStep()}
    </RegistrationLayout>
  );
}

// Layout is provided by RegistrationLayout internally
RegistrationPage.layout = page => page;
