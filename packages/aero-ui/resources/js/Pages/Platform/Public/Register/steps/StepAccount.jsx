import { useState } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Card, Text } from '@aero/ui';
import { SR } from '../signupRoutes.js';

function IconBuilding() {
  return (
    <svg width="36" height="36" viewBox="0 0 40 40" fill="none" aria-hidden="true">
      <rect x="4" y="10" width="24" height="26" rx="2" stroke="currentColor" strokeWidth="1.8" fill="none" />
      <rect x="28" y="18" width="8" height="18" rx="1.5" stroke="currentColor" strokeWidth="1.8" fill="none" />
      <rect x="9"  y="15" width="4" height="4" rx=".8" fill="currentColor" />
      <rect x="17" y="15" width="4" height="4" rx=".8" fill="currentColor" />
      <rect x="9"  y="23" width="4" height="4" rx=".8" fill="currentColor" />
      <rect x="17" y="23" width="4" height="4" rx=".8" fill="currentColor" />
      <rect x="13" y="30" width="6" height="6" rx=".8" fill="currentColor" />
    </svg>
  );
}

function IconPerson() {
  return (
    <svg width="36" height="36" viewBox="0 0 40 40" fill="none" aria-hidden="true">
      <circle cx="20" cy="13" r="7" stroke="currentColor" strokeWidth="1.8" fill="none" />
      <path d="M6 36c0-7.732 6.268-14 14-14s14 6.268 14 14" stroke="currentColor" strokeWidth="1.8" fill="none" strokeLinecap="round" />
    </svg>
  );
}

export default function StepAccount({ trialDays = 14, savedData = {} }) {
  const [selected, setSelected]     = useState(savedData?.account?.type ?? null);
  const [submitting, setSubmitting] = useState(false);

  function choose(type) {
    if (submitting) return;
    setSelected(type);
    setSubmitting(true);
    router.post(SR.storeAccount, { type }, { onFinish: () => setSubmitting(false) });
  }

  return (
    <VStack gap={4}>
      <Text tone="secondary">
        Start your {trialDays}-day free trial. No credit card required.
      </Text>

      <VStack gap={3}>
        <Card
          as="button"
          interactive
          type="button"
          onClick={() => choose('company')}
          disabled={submitting}
          aria-pressed={selected === 'company'}
          className={selected === 'company' ? 'rl-card-selected' : ''}
        >
          <HStack gap={3} align="center">
            <span className="rl-type-icon"><IconBuilding /></span>
            <VStack gap={1}>
              <Text>Company</Text>
              <Text tone="secondary" as="span">For teams and businesses. Includes multi-user access and roles.</Text>
            </VStack>
          </HStack>
        </Card>

        <Card
          as="button"
          interactive
          type="button"
          onClick={() => choose('individual')}
          disabled={submitting}
          aria-pressed={selected === 'individual'}
          className={selected === 'individual' ? 'rl-card-selected' : ''}
        >
          <HStack gap={3} align="center">
            <span className="rl-type-icon"><IconPerson /></span>
            <VStack gap={1}>
              <Text>Individual</Text>
              <Text tone="secondary" as="span">For freelancers and solo operators. Full platform access.</Text>
            </VStack>
          </HStack>
        </Card>
      </VStack>
    </VStack>
  );
}
