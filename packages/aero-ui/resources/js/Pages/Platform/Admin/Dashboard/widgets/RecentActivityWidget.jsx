import { Card, CardHeader, CardBody, Eyebrow, Text, Mono, VStack, HStack } from '@aero/ui';
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';

const TYPE_COLOR = {
  tenant_created:      'var(--aeos-success)',
  trial_started:       'var(--aeos-warning)',
  subscription_upgraded: 'var(--aeos-primary)',
  subscription_cancelled: 'var(--aeos-destructive)',
  tenant_suspended:    'var(--aeos-destructive)',
  provisioning_failed: 'var(--aeos-destructive)',
  payment_received:    'var(--aeos-success)',
  payment_failed:      'var(--aeos-destructive)',
  maintenance:         'var(--aeos-warning)',
  default:             'var(--aeos-text-tertiary)',
};

function dotColor(type) {
  return TYPE_COLOR[type] ?? TYPE_COLOR.default;
}

function groupByDate(activities) {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const groups = { Today: [], 'This week': [], Earlier: [] };

  activities.forEach((a) => {
    const d = a.createdAt ?? a.created_at;
    if (!d) { groups.Earlier.push(a); return; }
    const ts = new Date(d);
    ts.setHours(0, 0, 0, 0);
    const diff = (today - ts) / (1000 * 60 * 60 * 24);
    if (diff < 1)  groups.Today.push(a);
    else if (diff <= 7) groups['This week'].push(a);
    else groups.Earlier.push(a);
  });

  return groups;
}

function TimelineGroup({ label, items }) {
  if (!items.length) return null;
  return (
    <>
      {/* Group label */}
      <div
        style={{
          fontSize: 'var(--aeos-text-2xs)',
          fontWeight: 500,
          letterSpacing: '0.08em',
          textTransform: 'uppercase',
          color: 'var(--aeos-text-tertiary)',
          padding: 'var(--aeos-space-2) 0 var(--aeos-space-3)',
          borderBottom: 'var(--aeos-border-width) solid var(--aeos-divider)',
          marginBottom: 'var(--aeos-space-2)',
        }}
      >
        {label}
      </div>

      {items.map((a, i) => (
        <div
          key={a.id ?? i}
          style={{
            display: 'flex',
            gap: 'var(--aeos-space-3)',
            paddingBottom: 'var(--aeos-space-3)',
            minWidth: 0,
          }}
        >
          {/* Dot + line column */}
          <div
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              width: 16,
              flexShrink: 0,
            }}
          >
            <span
              style={{
                width: 8,
                height: 8,
                borderRadius: '50%',
                background: dotColor(a.type),
                flexShrink: 0,
                marginTop: 4,
              }}
            />
            {i < items.length - 1 && (
              <span
                style={{
                  flex: 1,
                  width: 1,
                  background: 'var(--aeos-divider)',
                  marginTop: 4,
                  minHeight: 12,
                }}
              />
            )}
          </div>

          {/* Content */}
          <VStack gap={0} style={{ flex: 1, minWidth: 0, paddingBottom: 'var(--aeos-space-1)' }}>
            <Text
              as="div"
              size="sm"
              style={{
                lineHeight: 1.4,
                overflow: 'hidden',
                display: '-webkit-box',
                WebkitLineClamp: 2,
                WebkitBoxOrient: 'vertical',
              }}
              dangerouslySetInnerHTML={{ __html: a.message ?? a.description ?? '' }}
            />
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 'var(--aeos-space-2)',
                marginTop: 2,
                flexWrap: 'wrap',
                rowGap: 0,
              }}
            >
              <Mono
                as="span"
                style={{
                  fontSize: 'var(--aeos-text-2xs)',
                  color: 'var(--aeos-text-tertiary)',
                  whiteSpace: 'nowrap',
                }}
              >
                {a.relativeTime ?? a.relative_time ?? a.createdAt ?? a.created_at ?? ''}
              </Mono>
              {a.href && (
                <button
                  type="button"
                  onClick={() => router.get(a.href)}
                  style={{
                    background: 'none',
                    border: 'none',
                    padding: 0,
                    cursor: 'pointer',
                    color: 'var(--aeos-primary)',
                    fontSize: 'var(--aeos-text-2xs)',
                    fontFamily: 'var(--aeos-font-body)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 2,
                    lineHeight: 1,
                  }}
                >
                  View
                  <ArrowTopRightOnSquareIcon style={{ width: 10, height: 10 }} />
                </button>
              )}
            </div>
          </VStack>
        </div>
      ))}
    </>
  );
}

export default function RecentActivityWidget({ recentActivity }) {
  const activities = recentActivity?.activities ?? recentActivity ?? [];
  const groups     = groupByDate(activities);
  const totalToday = groups.Today.length;
  const isEmpty    = activities.length === 0;

  return (
    <Card style={{ display: 'flex', flexDirection: 'column', minWidth: 0 }}>
      <CardHeader>
        <HStack gap={2} style={{ minWidth: 0, flexWrap: 'wrap', rowGap: 4 }}>
          <Eyebrow style={{ flex: 1, minWidth: 0 }}>Activity feed</Eyebrow>
          {totalToday > 0 && (
            <Mono
              as="span"
              style={{ fontSize: 'var(--aeos-text-xs)', color: 'var(--aeos-text-tertiary)', flexShrink: 0 }}
            >
              {totalToday} event{totalToday !== 1 ? 's' : ''} today
            </Mono>
          )}
        </HStack>
      </CardHeader>

      <CardBody style={{ flex: 1, minWidth: 0 }}>
        {isEmpty ? (
          <div style={{ textAlign: 'center', padding: 'var(--aeos-space-6) 0' }}>
            <Text size="sm" tone="secondary">No recent activity</Text>
          </div>
        ) : (
          <div style={{ minWidth: 0 }}>
            <TimelineGroup label="Today"     items={groups.Today}       />
            <TimelineGroup label="This week" items={groups['This week']} />
            <TimelineGroup label="Earlier"   items={groups.Earlier}     />
          </div>
        )}
      </CardBody>
    </Card>
  );
}
