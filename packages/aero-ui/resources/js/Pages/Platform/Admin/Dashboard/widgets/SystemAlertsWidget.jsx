import { router } from '@inertiajs/react';
import { Card, CardHeader, CardBody, Eyebrow, Text, Badge, VStack, HStack, Flex1 } from '@aero/ui';
import {
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  ArrowTopRightOnSquareIcon,
} from '@heroicons/react/24/outline';

const SEVERITY = {
  critical: { intent: 'danger',  Icon: ExclamationCircleIcon  },
  warning:  { intent: 'warning', Icon: ExclamationTriangleIcon },
  info:     { intent: 'primary', Icon: InformationCircleIcon  },
};

export default function SystemAlertsWidget({ systemAlerts }) {
  const { alerts = [], totalCount = 0, hasCritical = false } = systemAlerts ?? {};

  const sorted = [...alerts].sort((a, b) => {
    const order = { critical: 0, warning: 1, info: 2 };
    return (order[a.severity] ?? 3) - (order[b.severity] ?? 3);
  });

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0, height: '100%' }}>
      <CardHeader>
        <HStack gap={2} style={{ minWidth: 0 }}>
          <ExclamationTriangleIcon
            style={{
              width: 16,
              height: 16,
              color: hasCritical ? 'var(--aeos-destructive)' : 'var(--aeos-warning)',
              flexShrink: 0,
            }}
          />
          <Eyebrow style={{ flex: 1, minWidth: 0 }}>System alerts</Eyebrow>
          {totalCount > 0 && (
            <Badge intent={hasCritical ? 'danger' : 'warning'} size="sm" mono>
              {totalCount}
            </Badge>
          )}
        </HStack>
      </CardHeader>

      <CardBody style={{ flex: 1, minWidth: 0, padding: 0 }}>
        {sorted.length === 0 ? (
          <div style={{ padding: 'var(--aeos-pad-card)', textAlign: 'center' }}>
            <Text size="sm" tone="secondary">No active alerts</Text>
          </div>
        ) : (
          <VStack gap={0} style={{ minWidth: 0 }}>
            {sorted.map((alert, i) => {
              const cfg = SEVERITY[alert.severity] ?? SEVERITY.info;
              return (
                <div
                  key={alert.id ?? i}
                  style={{
                    display: 'flex',
                    alignItems: 'flex-start',
                    gap: 'var(--aeos-space-3)',
                    padding: 'var(--aeos-space-3) var(--aeos-pad-card)',
                    borderBottom:
                      i < sorted.length - 1
                        ? 'var(--aeos-border-width) solid var(--aeos-divider)'
                        : 'none',
                    minWidth: 0,
                  }}
                >
                  {/* Icon */}
                  <div
                    style={{
                      width: 30,
                      height: 30,
                      borderRadius: 'var(--aeos-r-md)',
                      background:
                        alert.severity === 'critical'
                          ? 'var(--aeos-danger-bg)'
                          : alert.severity === 'warning'
                          ? 'var(--aeos-warning-bg)'
                          : 'var(--aeos-primary-bg)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      flexShrink: 0,
                    }}
                  >
                    <cfg.Icon
                      style={{
                        width: 15,
                        height: 15,
                        color:
                          alert.severity === 'critical'
                            ? 'var(--aeos-destructive)'
                            : alert.severity === 'warning'
                            ? 'var(--aeos-warning)'
                            : 'var(--aeos-primary)',
                      }}
                    />
                  </div>

                  {/* Body */}
                  <VStack gap={0} style={{ flex: 1, minWidth: 0 }}>
                    <Text
                      as="span"
                      size="sm"
                      weight={500}
                      style={{
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                        maxWidth: '100%',
                        display: 'block',
                      }}
                    >
                      {alert.title}
                    </Text>
                    <Text
                      as="span"
                      size="xs"
                      tone="secondary"
                      style={{
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                        maxWidth: '100%',
                        display: 'block',
                      }}
                    >
                      {alert.description}
                    </Text>
                    {alert.href && (
                      <button
                        type="button"
                        onClick={() => router.get(alert.href)}
                        style={{
                          background: 'none',
                          border: 'none',
                          padding: 0,
                          cursor: 'pointer',
                          color: 'var(--aeos-primary)',
                          fontSize: 'var(--aeos-text-xs)',
                          fontFamily: 'var(--aeos-font-body)',
                          textAlign: 'left',
                          display: 'flex',
                          alignItems: 'center',
                          gap: 3,
                          marginTop: 2,
                        }}
                      >
                        View details
                        <ArrowTopRightOnSquareIcon style={{ width: 11, height: 11 }} />
                      </button>
                    )}
                  </VStack>
                </div>
              );
            })}
          </VStack>
        )}
      </CardBody>
    </Card>
  );
}
