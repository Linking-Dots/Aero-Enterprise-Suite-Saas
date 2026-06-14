import { useState } from 'react';
import { router } from '@inertiajs/react';
import { VStack, HStack, Card, Text, Badge } from '@aero/ui';
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
      <path d="M6 36c0-7.732 6.268-14 14-14s14 6.268 14 14"
        stroke="currentColor" strokeWidth="1.8" fill="none" strokeLinecap="round" />
    </svg>
  );
}

const ACCOUNT_TYPES = [
  {
    key:     'company',
    icon:    <IconBuilding />,
    label:   'Company',
    desc:    'For teams and businesses. Includes multi-user access, roles, and team collaboration tools.',
    badges:  ['Multi-user', 'Roles & permissions', 'Team workspace'],
  },
  {
    key:     'individual',
    icon:    <IconPerson />,
    label:   'Individual',
    desc:    'For freelancers and solo operators. Full platform access, single-user licence.',
    badges:  ['Full platform access', 'Single user'],
  },
];

export default function StepAccount({ trialDays = 14, savedData = {} }) {
  const [selected,   setSelected]   = useState(savedData?.account?.type ?? null);
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
        {ACCOUNT_TYPES.map(({ key, icon, label, desc, badges }) => (
          <Card
            key={key}
            as="button"
            interactive
            type="button"
            onClick={() => choose(key)}
            disabled={submitting}
            aria-pressed={selected === key}
            className={selected === key ? 'rl-card-selected' : ''}
            style={{ textAlign: 'left', width: '100%' }}
          >
            <HStack gap={3} align="flex-start">
              <span className="rl-type-icon" style={{ flexShrink: 0, paddingTop: 2 }}>{icon}</span>
              <VStack gap={2} align="stretch" style={{ flex: 1, minWidth: 0 }}>
                <HStack gap={2} align="center">
                  <Text weight="semibold" size="lg" as="span">{label}</Text>
                  {selected === key && <Badge intent="success">Selected</Badge>}
                </HStack>
                <Text tone="secondary" as="span">{desc}</Text>
                {/* Feature badges — always visible, not hidden behind expand */}
                <HStack gap={2} align="center" style={{ flexWrap: 'wrap' }}>
                  {badges.map(b => (
                    <Badge key={b} intent="neutral">{b}</Badge>
                  ))}
                </HStack>
              </VStack>
            </HStack>
          </Card>
        ))}
      </VStack>
    </VStack>
  );
}
