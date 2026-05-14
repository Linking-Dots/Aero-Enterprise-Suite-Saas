import { usePage } from '@inertiajs/react';
import RegistrationLayout from './RegistrationLayout.jsx';
import StepAccount    from './steps/StepAccount.jsx';
import StepDetails    from './steps/StepDetails.jsx';
import StepVerifyEmail from './steps/StepVerifyEmail.jsx';
import StepVerifyPhone from './steps/StepVerifyPhone.jsx';
import StepPlan       from './steps/StepPlan.jsx';
import StepBYOC       from './steps/StepBYOC.jsx';
import StepPayment    from './steps/StepPayment.jsx';
import StepProvisioning from './steps/StepProvisioning.jsx';
import StepSuccess    from './steps/StepSuccess.jsx';

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

// Steps that use the wider (900px) card layout
const WIDE_STEPS = ['plan', 'payment'];

export default function RegistrationPage(props) {
  const {
    steps        = [],
    currentStep  = 'account',
    savedData    = {},
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

  function renderStep() {
    switch (currentStep) {
      case 'account':
        return <StepAccount trialDays={trialDays} savedData={savedData} />;

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
        return <StepVerifyEmail email={email} companyName={companyName} />;

      case 'verify-phone':
        return <StepVerifyPhone phone={phone} companyName={companyName} />;

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
        return <StepBYOC savedByoc={savedData?.byoc ?? null} />;

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
        return <StepProvisioning tenant={tenant} baseDomain={baseDomain} />;

      case 'success':
        return <StepSuccess result={result} baseDomain={baseDomain} />;

      default:
        return null;
    }
  }

  return (
    <RegistrationLayout
      title={title}
      currentStep={currentStep}
      steps={steps}
      wide={wide}
    >
      {renderStep()}
    </RegistrationLayout>
  );
}

// Layout is handled inside the component itself
RegistrationPage.layout = page => page;
