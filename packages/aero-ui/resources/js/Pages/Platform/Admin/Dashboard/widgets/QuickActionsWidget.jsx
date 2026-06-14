import { router } from '@inertiajs/react';
import { Card, CardHeader, CardBody, Eyebrow, Text, VStack } from '@aero/ui';
import {
  BuildingOffice2Icon,
  CreditCardIcon,
  ChartBarIcon,
  CommandLineIcon,
  EnvelopeIcon,
  Cog6ToothIcon,
  UserPlusIcon,
  ShieldCheckIcon,
  WrenchScrewdriverIcon,
  ArrowUpTrayIcon,
  BellIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';

const DEFAULT_ACTIONS = [
  {
    key:    'create-tenant',
    label:  'Create tenant',
    Icon:   BuildingOffice2Icon,
    bg:     'var(--aeos-success-bg)',
    color:  'var(--aeos-success)',
    route:  'admin.tenants.create',
  },
  {
    key:    'manage-plans',
    label:  'Manage plans',
    Icon:   CreditCardIcon,
    bg:     'var(--aeos-primary-bg)',
    color:  'var(--aeos-primary)',
    route:  'admin.plans.index',
  },
  {
    key:    'analytics',
    label:  'Analytics',
    Icon:   ChartBarIcon,
    bg:     'var(--aeos-warning-bg)',
    color:  'var(--aeos-warning)',
    route:  'admin.analytics.index',
  },
  {
    key:    'error-logs',
    label:  'Error logs',
    Icon:   CommandLineIcon,
    bg:     'var(--aeos-tertiary-bg)',
    color:  'var(--aeos-tertiary)',
    route:  'admin.error-logs.index',
  },
  {
    key:    'email-tenants',
    label:  'Email tenants',
    Icon:   EnvelopeIcon,
    bg:     '#E1F5EE',
    color:  'var(--aeos-success)',
    route:  'admin.tenant-comms.index',
  },
  {
    key:    'settings',
    label:  'Settings',
    Icon:   Cog6ToothIcon,
    bg:     'var(--aeos-bg-subtle)',
    color:  'var(--aeos-text-secondary)',
    route:  'admin.settings.index',
  },
];

export default function QuickActionsWidget({ quickActions }) {
  const actions = (quickActions?.actions ?? DEFAULT_ACTIONS).slice(0, 12);

  return (
    <Card style={{ minWidth: 0 }}>
      <CardHeader>
        <Eyebrow>Quick actions</Eyebrow>
      </CardHeader>

      <CardBody style={{ minWidth: 0 }}>
        <div
          style={{
            display: 'grid',
            /* 3 cols on sm+, 6 cols on md+, never fewer than 2 */
            gridTemplateColumns: 'repeat(auto-fill, minmax(min(88px, calc(50% - 5px)), 1fr))',
            gap: 'var(--aeos-space-3)',
            minWidth: 0,
          }}
        >
          {actions.map((a) => {
            const Icon = a.Icon;
            return (
              <button
                key={a.key}
                type="button"
                onClick={() => a.route && router.get(route(a.route))}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: 'var(--aeos-space-2)',
                  padding: 'var(--aeos-space-4) var(--aeos-space-2)',
                  background: 'var(--aeos-bg-subtle)',
                  border: 'var(--aeos-border-width) solid transparent',
                  borderRadius: 'var(--aeos-r-md)',
                  cursor: 'pointer',
                  transition: 'background var(--aeos-dur-fast), border-color var(--aeos-dur-fast)',
                  fontFamily: 'var(--aeos-font-body)',
                  minWidth: 0,
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background    = 'var(--aeos-bg-hover)';
                  e.currentTarget.style.borderColor   = 'var(--aeos-border-subtle)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background    = 'var(--aeos-bg-subtle)';
                  e.currentTarget.style.borderColor   = 'transparent';
                }}
              >
                {/* Icon bubble */}
                <div
                  style={{
                    width: 34,
                    height: 34,
                    borderRadius: 'var(--aeos-r-md)',
                    background: a.bg ?? 'var(--aeos-primary-bg)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    flexShrink: 0,
                  }}
                >
                  {Icon && (
                    <Icon
                      style={{
                        width: 17,
                        height: 17,
                        color: a.color ?? 'var(--aeos-primary)',
                      }}
                    />
                  )}
                </div>

                {/* Label */}
                <Text
                  as="span"
                  size="xs"
                  tone="secondary"
                  style={{
                    textAlign: 'center',
                    lineHeight: 1.3,
                    wordBreak: 'break-word',
                    overflow: 'hidden',
                  }}
                >
                  {a.label}
                </Text>
              </button>
            );
          })}
        </div>

        {/* DnD hint */}
        <div
          style={{
            marginTop: 'var(--aeos-space-4)',
            paddingTop: 'var(--aeos-space-4)',
            borderTop: 'var(--aeos-border-width) solid var(--aeos-divider)',
            display: 'flex',
            alignItems: 'center',
            gap: 'var(--aeos-space-2)',
          }}
        >
          <Text as="span" size="xs" tone="tertiary">
            Drag widgets to reorder · preferences saved locally
          </Text>
        </div>
      </CardBody>
    </Card>
  );
}
